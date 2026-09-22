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
        ->postJson(route('api.recommend'), ['query' => 'ab'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('query');
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
        ->postJson(route('api.recommend'), ['query' => 'em muốn làm web quản lý thư viện']);

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
        ->postJson(route('api.recommend'), ['query' => 'web quản lý thư viện'])
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
        ->postJson(route('api.recommend'), ['query' => 'web quản lý thư viện']);

    $response->assertOk()->assertJsonPath('ok', false);

    expect($response->json('message'))->toContain('không khả dụng');
});

it('tắt tính năng ⇒ ok=false và không gọi service AI', function () {
    config()->set('services.topic_recommender.enabled', false);
    Http::fake();

    $this->actingAs($this->student)
        ->postJson(route('api.recommend'), ['query' => 'web quản lý thư viện'])
        ->assertOk()
        ->assertJsonPath('ok', false);

    Http::assertNothingSent();
});

it('không cho hỏi đề tài của lớp mà sinh viên không tham gia', function () {
    Http::fake();

    $this->actingAs($this->student)->postJson(route('api.recommend'), [
        'query' => 'web quản lý thư viện',
        'class_id' => $this->otherClass->class_id,
    ])->assertStatus(403);
});

it('panel gợi ý render ở trang danh sách đề tài kèm script gọi API (qua @stack)', function () {
    $response = $this->actingAs($this->student)->get(route('user.topics'));

    // Lưu ý: URL API được @json() escape dấu "/" thành "\/" nên chỉ assert phần chữ 'recommend'.
    $response->assertOk()
        ->assertSee('data-role="query"', false)
        ->assertSee('data-role="results"', false)
        ->assertSee('Chỉ đề tài còn trống')
        ->assertSee('X-CSRF-TOKEN', false)
        ->assertSee('recommend', false)
        ->assertSee('Đang phân tích', false);
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
        ->postJson(route('api.recommend'), ['query' => 'web quản lý thư viện'])
        ->assertOk();

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/embed'));

    expect(TopicEmbedding::where('topic_id', $this->myTopic->topic_id)->value('content_hash'))
        ->toBe(TopicEmbeddingService::hashFor(TopicEmbeddingService::embeddingText(Topics::find($this->myTopic->topic_id))));
});
