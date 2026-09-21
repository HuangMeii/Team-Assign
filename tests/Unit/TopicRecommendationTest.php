<?php

use App\Models\TopicEmbedding;
use App\Models\Topics;
use App\Services\TopicEmbeddingService;
use App\Services\TopicRecommendationService;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Gợi ý đề tài theo NGỮ NGHĨA — logic thuần (KHÔNG cần DB, KHÔNG cần service AI)
|--------------------------------------------------------------------------
| Vector đề tài được lưu ở bảng `topic_embeddings` nên mỗi đề tài chỉ embedding MỘT LẦN.
| Toàn bộ HTTP được Http::fake() nên không cần server :8891 thật.
| Test cần DB (lưu vector, lọc theo lớp): tests/Feature/TopicRecommendationTest.php
*/

uses(Tests\TestCase::class);

/** Đề tài "trên giấy" — không cần DB vì chỉ đọc thuộc tính. */
function fake_topic(array $attributes = []): Topics
{
    return new Topics(array_merge([
        'name' => 'Quản lý thư viện',
        'description' => 'Website quản lý sách và bạn đọc.',
    ], $attributes));
}

function enable_recommender(string $url = 'http://127.0.0.1:8891'): void
{
    config()->set('services.topic_recommender.enabled', true);
    config()->set('services.topic_recommender.url', $url);
}

it('gộp name + description + goal + requirements thành text đem đi embedding', function () {
    $topic = fake_topic(['goal' => 'Quản lý mượn/trả sách', 'requirements' => 'PHP 8, MySQL']);

    $text = TopicEmbeddingService::embeddingText($topic);

    expect($text)->toContain('Quản lý thư viện')
        ->and($text)->toContain('Website quản lý sách và bạn đọc.')
        ->and($text)->toContain('Mục tiêu: Quản lý mượn/trả sách')
        ->and($text)->toContain('Yêu cầu: PHP 8, MySQL');
});

it('bỏ qua field rỗng khi tạo text embedding', function () {
    $text = TopicEmbeddingService::embeddingText(fake_topic(['goal' => null, 'requirements' => '   ']));

    expect($text)->not->toContain('Mục tiêu')
        ->and($text)->not->toContain('Yêu cầu')
        ->and($text)->toContain('Quản lý thư viện');
});

it('content_hash (sha1) đổi khi nội dung đề tài đổi ⇒ chỉ đề tài đó được embed lại', function () {
    $before = TopicEmbeddingService::hashFor(TopicEmbeddingService::embeddingText(fake_topic()));
    $after = TopicEmbeddingService::hashFor(
        TopicEmbeddingService::embeddingText(fake_topic(['description' => 'Mô tả mới hoàn toàn.']))
    );

    expect($before)->toHaveLength(40)
        ->and($after)->not->toBe($before);
});

it('mã hoá/giải mã vector base64(float32) giữ nguyên giá trị', function () {
    $encoded = TopicEmbedding::encodeVector([0.123456789, -0.5, 1.0, 0.0]);
    $decoded = TopicEmbedding::decodeVector($encoded);

    expect(strlen($encoded))->toBe(24)   // 4 float × 4 byte = 16 byte => 24 ký tự base64
        ->and($decoded)->toHaveCount(4)
        ->and(abs($decoded[0] - 0.123456789))->toBeLessThan(1e-6)
        ->and(abs($decoded[1] + 0.5))->toBeLessThan(1e-6)
        ->and($decoded[2])->toEqual(1.0)
        ->and($decoded[3])->toEqual(0.0);
});

it('dữ liệu vector rỗng/hỏng trả về mảng rỗng (fail-open)', function () {
    expect(TopicEmbedding::decodeVector(null))->toBe([])
        ->and(TopicEmbedding::decodeVector(''))->toBe([])
        ->and(TopicEmbedding::decodeVector('!!!khong-phai-base64!!!'))->toBe([]);
});

it('gọi POST /embed đúng URL + payload và trả về vector', function () {
    enable_recommender();

    Http::fake([
        '*/embed' => Http::response([
            'model' => 'vietnamese-sbert',
            'dim' => 3,
            'embeddings' => [[0.1, 0.2, 0.3], [0.4, 0.5, 0.6]],
        ], 200),
    ]);

    $vectors = app(TopicEmbeddingService::class)->embedTexts(['quản lý thư viện', 'dự đoán điểm sinh viên']);

    expect($vectors)->toHaveCount(2)
        ->and($vectors[0])->toBe([0.1, 0.2, 0.3])
        ->and($vectors[1])->toBe([0.4, 0.5, 0.6]);

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/embed')
        && $request['texts'] === ['quản lý thư viện', 'dự đoán điểm sinh viên']);
});

it('ném RuntimeException khi service AI lỗi (caller tự fail-open)', function () {
    enable_recommender();
    Http::fake(['*/embed' => Http::response('loi server', 503)]);

    expect(fn () => app(TopicEmbeddingService::class)->embedTexts(['quản lý thư viện']))
        ->toThrow(RuntimeException::class);
});

it('không gọi mạng khi danh sách text rỗng', function () {
    enable_recommender();
    Http::fake();

    expect(app(TopicEmbeddingService::class)->embedTexts([]))->toBe([])
        ->and(app(TopicEmbeddingService::class)->embedTexts(['   ', '']))->toBe([]);

    Http::assertNothingSent();
});

it('coi như tắt khi thiếu URL cấu hình', function () {
    config()->set('services.topic_recommender.enabled', true);
    config()->set('services.topic_recommender.url', '');

    expect(app(TopicEmbeddingService::class)->enabled())->toBeFalse();
});

it('trả ok=false + thông báo khi tính năng đang tắt (không gọi mạng)', function () {
    config()->set('services.topic_recommender.enabled', false);
    config()->set('services.topic_recommender.url', 'http://127.0.0.1:8891');
    Http::fake();

    $result = app(TopicRecommendationService::class)->recommend([1], 'web quản lý sách');

    expect($result['ok'])->toBeFalse()
        ->and($result['message'])->toContain('đang tắt')
        ->and($result['results'])->toHaveCount(0);

    Http::assertNothingSent();
});

it('yêu cầu nhập mô tả trước khi gợi ý', function () {
    enable_recommender();

    $result = app(TopicRecommendationService::class)->recommend([1], '   ');

    expect($result['ok'])->toBeFalse()
        ->and($result['message'])->toContain('mô tả');
});

it('báo chưa tham gia lớp học phần nào khi danh sách lớp rỗng', function () {
    enable_recommender();

    $result = app(TopicRecommendationService::class)->recommend([], 'web quản lý sách');

    expect($result['ok'])->toBeFalse()
        ->and($result['message'])->toContain('lớp học phần');
});
