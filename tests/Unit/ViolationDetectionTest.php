<?php

use App\Services\ViolationDetection\FlagHelper;
use App\Services\ViolationDetection\TextModerationService;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Violation detection (flag-only) — logic thuần, KHÔNG cần DB
|--------------------------------------------------------------------------
| Nguyên tắc: mọi vi phạm CHỈ gắn cờ (is_flagged = 1), KHÔNG chặn gửi.
| Dataset + config: violation-detection/ (datasets/, config/thresholds.json).
| Test của luồng HTTP/DB nằm ở tests/Feature/Services/ViolationDetectionTest.php.
*/

uses(Tests\TestCase::class);

it('không gắn cờ với tin nhắn học tập bình thường', function () {
    $result = TextModerationService::check('Nhóm mình họp lúc 8h tối nay nhé, mọi người đọc tài liệu chương 2.');

    expect($result['is_violation'])->toBeFalse()
        ->and($result['score'])->toBe(0.0)
        ->and($result['reasons'])->toBe([]);
});

it('gắn cờ tin nhắn bán đề tài kèm giá và liên hệ riêng (mẫu trong dataset)', function () {
    $result = TextModerationService::check('Bán slot đăng ký đề tài, giá 200k, ai cần ib telegram @abc.');

    expect($result['is_violation'])->toBeTrue()
        ->and($result['score'])->toBeGreaterThanOrEqual(0.55)
        ->and($result['reasons'])->not->toBeEmpty();
});

it('gắn cờ tin nhắn làm hộ dù viết hoa không dấu', function () {
    $result = TextModerationService::check('LAM HO DO AN CHO BAN, GIA 500K');

    expect($result['is_violation'])->toBeTrue()
        ->and($result['reasons'])->toContain('làm hộ');
});

it('gắn cờ tin nhắn đe doạ tống tiền', function () {
    $result = TextModerationService::check('Nếu không chuyển khoản 2 triệu thì mình sẽ tống tiền cả nhóm.');

    expect($result['is_violation'])->toBeTrue()
        ->and($result['score'])->toBeGreaterThanOrEqual(0.55);
});

it('đọc ngưỡng + dataset từ folder violation-detection/', function () {
    $thresholds = TextModerationService::thresholds();

    expect($thresholds)->toHaveKeys(['text_threshold', 'text_review_threshold'])
        ->and(TextModerationService::configPath())->toContain('violation-detection')
        ->and(is_file(TextModerationService::configPath()))->toBeTrue()
        ->and(is_file(TextModerationService::datasetPath()))->toBeTrue();
});

it('FlagHelper luôn trả đủ 4 cột và không gắn cờ khi text + ảnh đều sạch', function () {
    $merged = FlagHelper::merge(TextModerationService::check('Chào mọi người, nay học nhóm nhé'), null);

    expect($merged)->toHaveKeys(['is_flagged', 'flag_reason', 'moderation_score', 'flagged_at'])
        ->and($merged['is_flagged'])->toBeFalse()
        ->and($merged['flag_reason'])->toBeNull()
        ->and($merged['flagged_at'])->toBeNull();
});

it('FlagHelper gộp cờ text + ảnh vào flag_reason và lấy điểm cao nhất', function () {
    $merged = FlagHelper::merge(
        TextModerationService::check('Bán đáp án môn CNPM giá 1 triệu, ib zalo.'),
        ['passed' => false, 'violations' => 'adult VERY_LIKELY']
    );

    expect($merged['is_flagged'])->toBeTrue()
        ->and($merged['flag_reason'])->toContain('text:')
        ->and($merged['flag_reason'])->toContain('image:adult VERY_LIKELY')
        ->and($merged['moderation_score'])->toBeGreaterThanOrEqual(0.9)
        ->and($merged['flagged_at'])->not->toBeNull();
});

it('FlagHelper chuẩn hoá violations dạng mảng/rỗng từ Vision', function () {
    expect(FlagHelper::describeViolations(['adult', 'racy']))->toBe('adult, racy')
        ->and(FlagHelper::describeViolations([['name' => 'adult'], ['name' => 'racy']]))->toBe('adult, racy')
        ->and(FlagHelper::describeViolations(null))->toBe('sensitive')
        ->and(FlagHelper::describeViolations('   '))->toBe('sensitive')
        ->and(FlagHelper::describeViolations([]))->toBe('sensitive');
});

/*
|--------------------------------------------------------------------------
| Phase 2: server PhoBERT (violation-detection/python/app.py, port 8889)
|--------------------------------------------------------------------------
| mode = model | hybrid. Mọi lỗi/timeout PHẢI fallback về rules (fail-open)
| để chat không bao giờ bị chặn/treo vì moderation.
*/

it('mode=model: gọi server PhoBERT và dùng kết quả trả về', function () {
    config()->set('services.text_moderation.url', 'http://127.0.0.1:8889');
    config()->set('services.text_moderation.mode', 'model');

    Http::fake([
        '*/predict' => Http::response([
            'is_violation' => true,
            'score' => 0.93,
            'reasons' => ['phobert-fraud (0.93)'],
            'needs_review' => false,
        ], 200),
    ]);

    $result = TextModerationService::check('Câu gì cũng được, model tự quyết.');

    expect($result['is_violation'])->toBeTrue()
        ->and($result['score'])->toBe(0.93)
        ->and($result['reasons'])->toContain('phobert-fraud (0.93)');

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/predict')
        && $request['text'] === 'Câu gì cũng được, model tự quyết.');
});

it('mode=model: server trả sạch thì không gắn cờ', function () {
    config()->set('services.text_moderation.url', 'http://127.0.0.1:8889');
    config()->set('services.text_moderation.mode', 'model');

    Http::fake([
        '*/predict' => Http::response(['is_violation' => false, 'score' => 0.12, 'reasons' => []], 200),
    ]);

    $result = TextModerationService::check('Bán slot đề tài giá 200k ib telegram @abc.');

    expect($result['is_violation'])->toBeFalse()
        ->and($result['score'])->toBe(0.12);
});

it('mode=model: server chết thì fallback về rules (chat vẫn gửi bình thường)', function () {
    config()->set('services.text_moderation.url', 'http://127.0.0.1:8889');
    config()->set('services.text_moderation.mode', 'model');

    Http::fake(['*/predict' => Http::response('loi server', 500)]);

    $result = TextModerationService::check('Bán đáp án môn CNPM giá 1 triệu, ib zalo.');

    expect($result['is_violation'])->toBeTrue()
        ->and($result['reasons'])->toContain('bán đáp án');
});

it('mode=hybrid: lấy điểm cao nhất và gộp lý do của rules + model', function () {
    config()->set('services.text_moderation.url', 'http://127.0.0.1:8889');
    config()->set('services.text_moderation.mode', 'hybrid');

    Http::fake(['*/predict' => Http::response(['is_violation' => false, 'score' => 0.4], 200)]);

    $result = TextModerationService::check('Bán slot đề tài giá 200k ib telegram @abc.');

    expect($result['is_violation'])->toBeTrue()
        ->and($result['score'])->toBeGreaterThanOrEqual(0.55)
        ->and($result['reasons'])->not->toBeEmpty();
});

it('mode=rules (mặc định) không gọi mạng tới server model', function () {
    config()->set('services.text_moderation.url', 'http://127.0.0.1:8889');
    config()->set('services.text_moderation.mode', 'rules');

    Http::fake();

    $result = TextModerationService::check('Chào mọi người, nay học nhóm nhé.');

    expect($result['is_violation'])->toBeFalse();
    Http::assertNothingSent();
});

