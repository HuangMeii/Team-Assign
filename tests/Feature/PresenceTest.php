<?php

use App\Models\User;
use App\Services\PresenceService;

use function Tests\Support\make_user;

/*
|--------------------------------------------------------------------------
| Trạng thái online/offline — Feature test (cần MySQL test)
|--------------------------------------------------------------------------
| Middleware UpdateLastSeen + /presence/ping + /presence/status.
*/

it('middleware ghi last_seen_at khi người dùng mở một trang', function () {
    $student = make_user('student', 'Sinh viên Online');

    expect($student->last_seen_at)->toBeNull();

    $this->actingAs($student)->get(route('user.dashboard'))->assertOk();

    $fresh = $student->fresh();

    expect($fresh->last_seen_at)->not->toBeNull()
        ->and($fresh->isOnline())->toBeTrue()
        ->and($fresh->presenceLabel())->toBe('Đang hoạt động');
});

it('không ghi lại last_seen_at khi vừa mới ghi (tránh 1 câu UPDATE mỗi request)', function () {
    $student = make_user('student', 'Sinh viên Vừa Hoạt Động');
    $recent = now()->subSeconds(10)->toDateTimeString();

    $student->forceFill(['last_seen_at' => $recent])->saveQuietly();
    $before = $student->fresh()->last_seen_at->toDateTimeString();

    $this->actingAs($student)->get(route('user.dashboard'))->assertOk();

    expect($student->fresh()->last_seen_at->toDateTimeString())->toBe($before);
});

it('endpoint /presence/ping giữ trạng thái đang hoạt động', function () {
    $student = make_user('student', 'Sinh viên Ping');
    $student->forceFill(['last_seen_at' => now()->subMinutes(10)->toDateTimeString()])->saveQuietly();

    expect($student->fresh()->isOnline())->toBeFalse();

    $this->actingAs($student)
        ->postJson(route('presence.ping'))
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('online', true)
        ->assertJsonPath('written', true);

    expect($student->fresh()->isOnline())->toBeTrue();
});

it('/presence/status trả trạng thái đúng cho từng người (online / offline + nhãn)', function () {
    $online = make_user('student', 'Sinh viên Đang Online');
    $online->forceFill(['last_seen_at' => now()->subSeconds(20)->toDateTimeString()])->saveQuietly();

    $offline = make_user('student', 'Sinh viên Offline');
    $offline->forceFill(['last_seen_at' => now()->subDays(9)->toDateTimeString()])->saveQuietly();

    $never = make_user('student', 'Sinh viên Chưa Vào');

    $this->actingAs($online)
        ->getJson(route('presence.status', ['user_ids' => [$online->user_id, $offline->user_id, $never->user_id]]))
        ->assertOk()
        ->assertJsonPath("statuses.{$online->user_id}.online", true)
        ->assertJsonPath("statuses.{$online->user_id}.label", 'Đang hoạt động')
        ->assertJsonPath("statuses.{$online->user_id}.color", 'success')
        ->assertJsonPath("statuses.{$offline->user_id}.online", false)
        ->assertJsonPath("statuses.{$offline->user_id}.label", 'Hoạt động hơn 7 ngày trước')
        ->assertJsonPath("statuses.{$never->user_id}.label", 'Chưa hoạt động');
});

it('khách chưa đăng nhập không gọi được endpoint presence', function () {
    $this->postJson(route('presence.ping'))->assertStatus(401);
    $this->getJson(route('presence.status'))->assertStatus(401);
});

it('PresenceService::statusesFor đọc đúng dữ liệu thật từ DB', function () {
    $student = make_user('student', 'Sinh viên DB');
    $student->forceFill(['last_seen_at' => now()->subMinutes(3)->toDateTimeString()])->saveQuietly();

    $statuses = app(PresenceService::class)->statusesFor([$student->user_id]);

    expect($statuses)->toHaveKey($student->user_id)
        ->and($statuses[$student->user_id]['online'])->toBeFalse()
        ->and($statuses[$student->user_id]['label'])->toBe('Hoạt động 3 phút trước');
});
