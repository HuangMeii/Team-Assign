<?php

use App\Models\User;
use App\Services\PresenceService;

/*
|--------------------------------------------------------------------------
| Trạng thái online/offline — logic thuần (KHÔNG cần DB)
|--------------------------------------------------------------------------
| Nhãn: "Đang hoạt động" · "Hoạt động 5 phút trước" · "Hoạt động 2 ngày trước"
| · "Hoạt động hơn 7 ngày trước" (>7 ngày) · "Chưa hoạt động" (chưa từng vào).
| Test cần DB (middleware, endpoint): tests/Feature/PresenceTest.php
*/

uses(Tests\TestCase::class);

function presence_service(): PresenceService
{
    return app(PresenceService::class);
}

/** User "trên giấy" — không cần DB vì chỉ đọc thuộc tính đã cast. */
function fake_user(?string $lastSeen): User
{
    return new User(['last_seen_at' => $lastSeen]);
}

it('user vừa hoạt động thì hiện "Đang hoạt động" và chấm XANH', function () {
    $user = fake_user(now()->subSeconds(30)->toDateTimeString());

    $status = presence_service()->statusFor($user);

    expect($status['online'])->toBeTrue()
        ->and($status['label'])->toBe('Đang hoạt động')
        ->and($status['color'])->toBe('success')
        ->and(presence_service()->isOnline($user))->toBeTrue();
});

it('quá 2 phút không hoạt động thì coi là offline và chấm XÁM', function () {
    $user = fake_user(now()->subMinutes(5)->toDateTimeString());

    $status = presence_service()->statusFor($user);

    expect($status['online'])->toBeFalse()
        ->and($status['label'])->toBe('Hoạt động 5 phút trước')
        ->and($status['color'])->toBe('secondary');
});

it('nhãn thời gian hiển thị theo phút / giờ / ngày', function () {
    $service = presence_service();

    expect($service->label(fake_user(now()->subMinutes(45)->toDateTimeString())))->toBe('Hoạt động 45 phút trước')
        ->and($service->label(fake_user(now()->subHours(3)->toDateTimeString())))->toBe('Hoạt động 3 giờ trước')
        ->and($service->label(fake_user(now()->subDays(2)->toDateTimeString())))->toBe('Hoạt động 2 ngày trước')
        ->and($service->label(fake_user(now()->subDays(7)->toDateTimeString())))->toBe('Hoạt động 7 ngày trước');
});

it('quá 7 ngày chỉ ghi "hơn 7 ngày trước" (không đếm tiếp số ngày)', function () {
    $service = presence_service();

    expect($service->label(fake_user(now()->subDays(8)->toDateTimeString())))->toBe('Hoạt động hơn 7 ngày trước')
        ->and($service->label(fake_user(now()->subDays(30)->toDateTimeString())))->toBe('Hoạt động hơn 7 ngày trước')
        ->and($service->label(fake_user(now()->subDays(400)->toDateTimeString())))->toBe('Hoạt động hơn 7 ngày trước');
});

it('user chưa từng hoạt động thì ghi "Chưa hoạt động" và offline', function () {
    $user = fake_user(null);

    expect(presence_service()->isOnline($user))->toBeFalse()
        ->and(presence_service()->label($user))->toBe('Chưa hoạt động');
});

it('chỉ ghi last_seen_at khi đã quá nhịp heartbeat (tránh ghi mỗi request)', function () {
    $service = presence_service();

    expect($service->shouldTouch(fake_user(null)))->toBeTrue()
        ->and($service->shouldTouch(fake_user(now()->subSeconds(10)->toDateTimeString())))->toBeFalse()
        ->and($service->shouldTouch(fake_user(now()->subSeconds(PresenceService::HEARTBEAT_SECONDS + 5)->toDateTimeString())))->toBeTrue();
});

it('statusesFor bỏ qua id không hợp lệ và trả về mảng rỗng khi không có id', function () {
    expect(presence_service()->statusesFor([]))->toBe([]);
});
