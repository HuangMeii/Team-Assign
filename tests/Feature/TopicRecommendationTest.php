<?php

use App\Models\TopicEmbedding;
use App\Models\Topics;
use App\Services\TopicEmbeddingService;
use Illuminate\Support\Facades\Http;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_topic;
use function Tests\Support\make_user;

/*
|--------------------------------------------------------------------------
| POST /api/recommend — gợi ý đề tài theo NGỮ NGHĨA (Feature, cần MySQL test)
|--------------------------------------------------------------------------
| Http::fake() nên KHÔNG cần service AI :8891 thật. Kiểm tra:
|  - yêu cầu đăng nhập + validate input
|  - sinh viên CHỈ nhận đề tài trong lớp học phần mình tham gia
|  - vector đề tài được LƯU vào `topic_embeddings` ⇒ lần sau không embed lại
|  - fail-open khi service AI tắt/lỗi (không bao giờ 500)
*/

beforeEach(function () {
    config()->set('services.topic_recommender.enabled', true);
    config()->set('services.topic_recommender.url', 'http://127.0.0.1:8891');
    config()->set('services.topic_recommender.model_tag', 'vietnamese-sbert');
    config()->set('services.topic_recommender.top_k', 5);
    config()->set('services.topic_recommender.min_score', 0.0);

    $this->student = make_user('student', 'Sinh viên Test');
    $this->lecturer = make_user('lecturer', 'Giảng viên A');
    $this->subject = make_subject($this->lecturer);

    // Hai lớp học phần cùng môn; sinh viên chỉ tham gia lớp đầu
    $this->myClass = make_class($this->subject, $this->lecturer);
    $this->otherClass = make_class($this->subject, $this->lecturer);
    $this->myClass->users()->attach($this->student->user_id);

    $this->myTopic = make_topic($this->myClass, $this->subject);
    $this->otherTopic = make_topic($this->otherClass, $this->subject);
});

it('yêu cầu đăng nhập mới gọi được API gợi ý', function () {
    $this->postJson(route('api.recommend'), ['query' => 'web quản lý thư viện'])
        ->assertStatus(401);
});

it('validate mô tả quá ngắn', function () {
    $this->actingAs($this->student)
        ->postJson(route('api.recommend'), [
            'query' => 'ab',
            'subject_id' => $this->subject->subject_id,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('query');
});

it('bắt buộc chọn môn học mới gợi ý được', function () {
    Http::fake();

    // Thiếu subject_id -> 422 kèm đúng thông báo hướng dẫn người dùng
    $this->actingAs($this->student)
        ->postJson(route('api.recommend'), ['query' => 'web quản lý thư viện'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['subject_id' => 'Bạn hãy chọn môn học cần gợi ý.']);

    // Môn học không tồn tại -> 422
    $this->actingAs($this->student)
        ->postJson(route('api.recommend'), [
            'query' => 'web quản lý thư viện',
            'subject_id' => 999999,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('subject_id');

    Http::assertNothingSent();
});

it('gợi ý đề tài trong lớp của sinh viên và LƯU vector vào DB', function () {
    Http::fake([
        '*/embed' => Http::response(['model' => 'vietnamese-sbert', 'dim' => 3, 'embeddings' => [[0.5, 0.5, 0.5]]], 200),
        '*/recommend' => Http::response([
            'model' => 'vietnamese-sbert',
            'top_k' => 5,
            'candidates' => 1,
            'scored' => 1,
            'results' => [['id' => $this->myTopic->topic_id, 'score' => 0.87, 'rank' => 1]],
            'embedded' => [(string) $this->myTopic->topic_id => [0.5, 0.5, 0.5]],
            'embedded_count' => 1,
            'latency_ms' => 12.3,
        ], 200),
    ]);

    $response = $this->actingAs($this->student)
        ->postJson(route('api.recommend'), [
            'query' => 'em muốn làm web quản lý thư viện',
            'subject_id' => $this->subject->subject_id,
        ]);

    $response->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('results.0.topic_id', $this->myTopic->topic_id)
        ->assertJsonPath('results.0.similarity_percent', 87);

    // Vector vừa sinh đã được lưu lại (lần sau chỉ gửi embedding, không gửi text)
    expect(TopicEmbedding::where('topic_id', $this->myTopic->topic_id)->exists())->toBeTrue();

    // Ứng viên CHỈ gồm đề tài của lớp sinh viên tham gia
    Http::assertSent(function ($request) {
        if (! str_ends_with($request->url(), '/recommend')) {
            return false;
        }

        $ids = collect($request['topics'])->pluck('id')->all();

        return in_array($this->myTopic->topic_id, $ids, true)
            && ! in_array($this->otherTopic->topic_id, $ids, true);
    });
});

it('lần gợi ý thứ 2 KHÔNG gọi lại /embed cho đề tài đã có vector', function () {
    $vector = [0.1, 0.2, 0.3];

    TopicEmbedding::create([
        'topic_id' => $this->myTopic->topic_id,
        'model' => 'vietnamese-sbert',
        'dim' => 3,
        'content_hash' => TopicEmbeddingService::hashFor(TopicEmbeddingService::embeddingText($this->myTopic)),
        'embedding' => TopicEmbedding::encodeVector($vector),
        'embedded_at' => now(),
    ]);

    Http::fake([
        '*/recommend' => Http::response([
            'model' => 'vietnamese-sbert',
            'results' => [['id' => $this->myTopic->topic_id, 'score' => 0.5, 'rank' => 1]],
            'embedded' => [],
            'embedded_count' => 0,
        ], 200),
    ]);

    $this->actingAs($this->student)
        ->postJson(route('api.recommend'), [
            'query' => 'web quản lý thư viện',
            'subject_id' => $this->subject->subject_id,
        ])
        ->assertOk()
        ->assertJsonPath('ok', true);

    Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/embed'));

    Http::assertSent(function ($request) {
        if (! str_ends_with($request->url(), '/recommend')) {
            return false;
        }

        $topic = $request['topics'][0] ?? null;

        return $topic !== null
            && ! array_key_exists('text', $topic)
            && abs(((array) $topic['embedding'])[0] - 0.1) < 1e-6;
    });
});

it('only_available=true thì bỏ đề tài đã có nhóm khỏi danh sách ứng viên', function () {
    $assigned = make_topic($this->myClass, $this->subject);
    $group = make_group($this->student, $this->myClass);
    $assigned->update(['assigned_group_id' => $group->group_id]);

    Http::fake([
        '*/embed' => Http::response(['embeddings' => [[0.5, 0.5, 0.5]]], 200),
        '*/recommend' => Http::response(['results' => [], 'embedded' => [], 'embedded_count' => 0], 200),
    ]);

    $this->actingAs($this->student)->postJson(route('api.recommend'), [
        'query' => 'web quản lý thư viện',
        'subject_id' => $this->subject->subject_id,
        'only_available' => true,
    ])->assertOk();

    Http::assertSent(function ($request) use ($assigned) {
        if (! str_ends_with($request->url(), '/recommend')) {
            return false;
        }

        $ids = collect($request['topics'])->pluck('id')->all();

        return ! in_array($assigned->topic_id, $ids, true)
            && in_array($this->myTopic->topic_id, $ids, true);
    });
});

it('service AI lỗi ⇒ ok=false + thông báo, KHÔNG trả 500', function () {
    Http::fake(['*/embed' => Http::response('loi server', 503)]);

    $response = $this->actingAs($this->student)
        ->postJson(route('api.recommend'), [
            'query' => 'web quản lý thư viện',
            'subject_id' => $this->subject->subject_id,
        ]);

    $response->assertOk()->assertJsonPath('ok', false);

    expect($response->json('message'))->toContain('không khả dụng');
});

it('tắt tính năng ⇒ ok=false và không gọi service AI', function () {
    config()->set('services.topic_recommender.enabled', false);
    Http::fake();

    $this->actingAs($this->student)
        ->postJson(route('api.recommend'), [
            'query' => 'web quản lý thư viện',
            'subject_id' => $this->subject->subject_id,
        ])
        ->assertOk()
        ->assertJsonPath('ok', false);

    Http::assertNothingSent();
});

it('không cho hỏi đề tài của lớp mà sinh viên không tham gia', function () {
    Http::fake();

    $this->actingAs($this->student)->postJson(route('api.recommend'), [
        'query' => 'web quản lý thư viện',
        'subject_id' => $this->subject->subject_id,
        'class_id' => $this->otherClass->class_id,
    ])->assertStatus(403);
});

it('panel gợi ý render ở trang danh sách đề tài kèm script gọi API (qua @stack)', function () {
    $response = $this->actingAs($this->student)->get(route('user.topics'));

    // Lưu ý: URL API được @json() escape dấu "/" thành "\/" nên chỉ assert phần chữ 'recommend'.
    $response->assertOk()
        ->assertSee('data-role="subject"', false)
        ->assertSee('Môn học cần gợi ý')
        ->assertSee('data-role="query"', false)
        ->assertSee('data-role="results"', false)
        ->assertSee('Chỉ đề tài còn trống')
        ->assertSee('X-CSRF-TOKEN', false)
        ->assertSee('recommend', false)
        ->assertSee('Đang phân tích', false);
});

it('panel gợi ý chỉ cho chọn môn học của lớp mình (không hiện môn của lớp khác)', function () {
    $otherSubject = make_subject($this->lecturer);

    $response = $this->actingAs($this->student)->get(route('user.topics'));

    $response->assertOk()
        ->assertSee($this->subject->subject_name)
        ->assertDontSee($otherSubject->subject_name);
});

it('chọn môn học không có lớp nào của sinh viên ⇒ ok=false, không gọi service AI', function () {
    $otherSubject = make_subject($this->lecturer);
    make_class($otherSubject, $this->lecturer);

    Http::fake();

    $response = $this->actingAs($this->student)->postJson(route('api.recommend'), [
        'query' => 'web quản lý thư viện',
        'subject_id' => $otherSubject->subject_id,
    ]);

    $response->assertOk()
        ->assertJsonPath('ok', false)
        ->assertJsonPath('meta.user_role', 'student');

    expect($response->json('message'))->toContain('chưa có lớp học phần nào');

    Http::assertNothingSent();
});

it('sinh viên đã có đề tài được duyệt VẪN dùng được panel (chỉ hiện cảnh báo)', function () {
    $group = make_group($this->student, $this->myClass);
    $group->update(['topic_id' => $this->myTopic->topic_id]);

    // 1. Panel vẫn render + có cảnh báo tham khảo
    $this->actingAs($this->student)->get(route('user.topics'))
        ->assertOk()
        ->assertSee('data-role="subject"', false)
        ->assertSee('đã có đề tài được duyệt', false);

    // 2. API vẫn trả kết quả bình thường (KHÔNG bị chặn)
    Http::fake([
        '*/embed' => Http::response(['embeddings' => [[0.5, 0.5, 0.5]]], 200),
        '*/recommend' => Http::response([
            'results' => [['id' => $this->otherTopic->topic_id, 'score' => 0.6, 'rank' => 1]],
            'embedded' => [],
            'embedded_count' => 0,
        ], 200),
    ]);

    $this->actingAs($this->student)->postJson(route('api.recommend'), [
        'query' => 'web quản lý thư viện',
        'subject_id' => $this->subject->subject_id,
    ])->assertOk()->assertJsonPath('ok', true);
});

it('giảng viên gợi ý được theo môn của lớp mình phụ trách (không cần truyền class_id)', function () {
    // 2 lớp của giảng viên ⇒ 2 đề tài ứng viên ⇒ /embed phải trả đúng 2 vector
    Http::fake([
        '*/embed' => Http::response(['embeddings' => [[0.5, 0.5, 0.5], [0.4, 0.4, 0.4]]], 200),
        '*/recommend' => Http::response([
            'results' => [['id' => $this->myTopic->topic_id, 'score' => 0.9, 'rank' => 1]],
            'embedded' => [],
            'embedded_count' => 0,
        ], 200),
    ]);

    $response = $this->actingAs($this->lecturer)->postJson(route('api.recommend'), [
        'query' => 'web quản lý thư viện',
        'subject_id' => $this->subject->subject_id,
    ]);

    $response->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('meta.user_role', 'lecturer');

    // Phạm vi = tất cả lớp của môn đó mà giảng viên phụ trách (2 lớp trong fixture)
    expect($response->json('meta.class_ids'))
        ->toContain($this->myClass->class_id)
        ->toContain($this->otherClass->class_id);
});

it('admin gợi ý được cho mọi môn học (không cần truyền class_id)', function () {
    $admin = make_user('admin', 'Quản trị viên');

    // Admin không có lớp nào ⇒ phạm vi = mọi lớp của môn = 2 lớp ⇒ /embed trả 2 vector
    Http::fake([
        '*/embed' => Http::response(['embeddings' => [[0.5, 0.5, 0.5], [0.4, 0.4, 0.4]]], 200),
        '*/recommend' => Http::response([
            'results' => [['id' => $this->myTopic->topic_id, 'score' => 0.8, 'rank' => 1]],
            'embedded' => [],
            'embedded_count' => 0,
        ], 200),
    ]);

    $response = $this->actingAs($admin)->postJson(route('api.recommend'), [
        'query' => 'web quản lý thư viện',
        'subject_id' => $this->subject->subject_id,
    ]);

    $response->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('meta.user_role', 'admin');

    expect($response->json('meta.class_ids'))
        ->toContain($this->myClass->class_id)
        ->toContain($this->otherClass->class_id);
});

it('panel gợi ý cũng render ở trang quản lý đề tài (giảng viên/admin)', function () {
    $this->actingAs($this->lecturer)->get(route('topics.index'))
        ->assertOk()
        ->assertSee('data-role="subject"', false)
        ->assertSee($this->subject->subject_name)
        ->assertSee('recommend', false);

    $admin = make_user('admin', 'Quản trị viên');
    $this->actingAs($admin)->get(route('topics.index'))
        ->assertOk()
        ->assertSee('data-role="subject"', false)
        ->assertSee($this->subject->subject_name);
});

it('đề tài đã có vector nhưng nội dung đổi ⇒ embed lại (content_hash đổi)', function () {
    TopicEmbedding::create([
        'topic_id' => $this->myTopic->topic_id,
        'model' => 'vietnamese-sbert',
        'dim' => 3,
        'content_hash' => sha1('nội dung cũ đã bị sửa'),
        'embedding' => TopicEmbedding::encodeVector([0.1, 0.1, 0.1]),
        'embedded_at' => now()->subDay(),
    ]);

    Http::fake([
        '*/embed' => Http::response(['embeddings' => [[0.9, 0.9, 0.9]]], 200),
        '*/recommend' => Http::response(['results' => [], 'embedded' => [], 'embedded_count' => 0], 200),
    ]);

    $this->actingAs($this->student)
        ->postJson(route('api.recommend'), [
            'query' => 'web quản lý thư viện',
            'subject_id' => $this->subject->subject_id,
        ])
        ->assertOk();

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/embed'));

    expect(TopicEmbedding::where('topic_id', $this->myTopic->topic_id)->value('content_hash'))
        ->toBe(TopicEmbeddingService::hashFor(TopicEmbeddingService::embeddingText(Topics::find($this->myTopic->topic_id))));
});
