<?php

use App\Models\Notifications;
use App\Services\NotificationService;

use function Tests\Support\make_user;

it('gửi thông báo hệ thống đến tất cả giảng viên', function () {
    $lecturer1 = make_user('lecturer', 'Giảng viên 1');
    $lecturer2 = make_user('lecturer', 'Giảng viên 2');

    $count = NotificationService::broadcastToLecturers('Thông báo', 'Nội dung');

    expect($count)->toBe(2)
        ->and(Notifications::where('type', 'system')->count())->toBe(2)
        ->and(Notifications::where('user_id', $lecturer1->user_id)->count())->toBe(1)
        ->and(Notifications::where('user_id', $lecturer2->user_id)->count())->toBe(1);
});

it('gửi thông báo đến giảng viên cụ thể khi được chọn', function () {
    $lecturer1 = make_user('lecturer', 'Giảng viên 1');
    $lecturer2 = make_user('lecturer', 'Giảng viên 2');

    $count = NotificationService::broadcastToLecturers('Thông báo', 'Nội dung', null, [$lecturer1->user_id]);

    expect($count)->toBe(1)
        ->and(Notifications::where('user_id', $lecturer1->user_id)->count())->toBe(1)
        ->and(Notifications::where('user_id', $lecturer2->user_id)->count())->toBe(0);
});

it('admin gửi thông báo qua controller thành công', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    $lecturer = make_user('lecturer', 'Giảng viên A');

    $response = $this->actingAs($admin)->post(route('admin.notifications.send'), [
        'title' => 'Tiêu đề thông báo',
        'message' => 'Nội dung thông báo hệ thống',
    ]);

    $response->assertRedirect(route('admin.notifications.create'));
    $response->assertSessionHas('success');
    expect(Notifications::where('user_id', $lecturer->user_id)
        ->where('type', 'system')->count())->toBe(1);
});
