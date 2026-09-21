<?php

use App\Services\ViolationDetection\FlagHelper;
use App\Services\ViolationDetection\SensitiveModerationService;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Sensitive moderation Phase 3 (flag-only) — logic thuần, KHÔNG cần DB
|--------------------------------------------------------------------------
| PhoBERT v2 5 nhãn multi-label (violation-detection/python/app_moderation.py):
| 0=profanity, 1=insult, 2=threat, 3=dangerous, 4=adult.
| Không có output "clean": clean = không nhãn nào >= moderation_threshold.
| Một câu có thể ra NHIỀU nhãn. Test HTTP/DB: tests/Feature/Services/SensitiveModerationChatTest.php
*/

uses(Tests\TestCase::class);

it('không gắn cờ với tin nhắn học tập bình thường (rules)', function () {
    config()->set('services.content_moderation.mode', 'rules');

    $result = SensitiveModerationService::check('Nhóm mình họp lúc 8h tối nay nhé, mọi người đọc tài liệu chương 2.');

    expect($result['is_violation'])->toBeFalse()
        ->and($result['score'])->toBe(0.0)
        ->and($result['reasons'])->toBe([]);
});

it('gắn cờ tin nhắn xúc phạm dù viết hoa / không dấu (rules)', function () {
    config()->set('services.content_moderation.mode', 'rules');

    $result = SensitiveModerationService::check('MAY NGU NHU CHO, IM MOM DI');

    expect($result['is_violation'])->toBeTrue()
        ->and($result['score'])->toBeGreaterThanOrEqual(0.5)
        ->and($result['reasons'])->toContain('insult');
});

it('một câu chứa nhiều loại từ khoá trả NHIỀU nhãn cùng lúc', function () {
    config()->set('services.content_moderation.mode', 'rules');

    $result = SensitiveModerationService::checkRules('dit me may, tao se giet may, mua ma tuy di');

    expect($result['reasons'])->toContain('profanity')
        ->and($result['reasons'])->toContain('threat')
        ->and($result['reasons'])->toContain('dangerous')
        ->and(count($result['reasons']))->toBeGreaterThanOrEqual(3);
});

it('đọc ngưỡng + tên nhãn từ violation-detection/config/thresholds.json', function () {
    $thresholds = SensitiveModerationService::thresholds();

    expect($thresholds)->toHaveKeys(['moderation_threshold', 'moderation_labels'])
        ->and($thresholds['moderation_labels'])->toBe([
            '0' => 'profanity',
            '1' => 'insult',
            '2' => 'threat',
            '3' => 'dangerous',
            '4' => 'adult',
        ]);
});

it('FlagHelper merge 3 đầu vào: text + ảnh + sensitive đều CHỈ gắn cờ', function () {
    $merged = FlagHelper::merge(
        ['is_violation' => false, 'score' => 0.0, 'reasons' => []],
        null,
        ['is_violation' => true, 'score' => 0.94, 'reasons' => ['insult', 'threat']]
    );

    expect($merged['is_flagged'])->toBeTrue()
        ->and($merged['flag_reason'])->toContain('sensitive:insult,threat')
        ->and($merged['moderation_score'])->toBe(0.94)
        ->and($merged['flagged_at'])->not->toBeNull();
});

it('FlagHelper lấy điểm cao nhất giữa text fraud và sensitive', function () {
    $merged = FlagHelper::merge(
        ['is_violation' => true, 'score' => 0.96, 'reasons' => ['tống tiền']],
        null,
        ['is_violation' => true, 'score' => 0.6, 'reasons' => ['insult']]
    );

    expect($merged['flag_reason'])->toContain('text:tống tiền')
        ->and($merged['flag_reason'])->toContain('sensitive:insult')
        ->and($merged['moderation_score'])->toBe(0.96);
});

/*
|--------------------------------------------------------------------------
| Phase 3: server PhoBERT (port 8890) — fail-open về rules
|--------------------------------------------------------------------------
*/

it('mode=model: gọi server và dùng kết quả multi-label trả về', function () {
    config()->set('services.content_moderation.url', 'http://127.0.0.1:8890');
    config()->set('services.content_moderation.mode', 'model');

    Http::fake([
        '*/predict' => Http::response([
            'is_violation' => true,
            'score' => 0.94,
            'reasons' => ['insult', 'threat'],
            'needs_review' => false,
            'labels' => ['profanity' => 0.1, 'insult' => 0.94, 'threat' => 0.71, 'dangerous' => 0.1, 'adult' => 0.02],
        ], 200),
    ]);

    $result = SensitiveModerationService::check('Câu gì cũng được, model tự quyết.');

    expect($result['is_violation'])->toBeTrue()
        ->and($result['score'])->toBe(0.94)
        ->and($result['reasons'])->toBe(['insult', 'threat']);

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/predict'));
});

it('mode=model: server chết thì fallback về rules (chat không bị treo)', function () {
    config()->set('services.content_moderation.url', 'http://127.0.0.1:8890');
    config()->set('services.content_moderation.mode', 'model');

    Http::fake(['*/predict' => Http::response('loi server', 503)]);

    $result = SensitiveModerationService::check('May ngu nhu cho, im mom di');

    expect($result['is_violation'])->toBeTrue()
        ->and($result['reasons'])->toContain('insult');
});

it('mode=hybrid: gộp lý do của rules + model', function () {
    config()->set('services.content_moderation.url', 'http://127.0.0.1:8890');
    config()->set('services.content_moderation.mode', 'hybrid');

    Http::fake(['*/predict' => Http::response([
        'is_violation' => true, 'score' => 0.91, 'reasons' => ['threat'],
    ], 200)]);

    $result = SensitiveModerationService::check('tao se giet may');

    expect($result['is_violation'])->toBeTrue()
        ->and($result['reasons'])->toContain('threat')
        ->and($result['score'])->toBeGreaterThanOrEqual(0.9);
});

it('mode=rules (mặc định) không gọi mạng tới server model', function () {
    config()->set('services.content_moderation.url', 'http://127.0.0.1:8890');
    config()->set('services.content_moderation.mode', 'rules');

    Http::fake();

    SensitiveModerationService::check('Chào mọi người, nay học nhóm nhé.');

    Http::assertNothingSent();
});
