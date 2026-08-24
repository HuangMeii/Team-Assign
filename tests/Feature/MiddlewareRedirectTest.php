<?php

use Illuminate\Support\Facades\Route;

use function Tests\Support\make_user;

it('giảng viên bị chặn truy cập trang admin sẽ được chuyển về dashboard.lecturer', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');

    $response = $this->actingAs($lecturer)->get(route('admin.users.index'));

    $response->assertRedirect(route('dashboard.lecturer'));
});

it('sinh viên bị chặn truy cập trang admin sẽ được chuyển về user.dashboard', function () {
    $student = make_user('student', 'Sinh viên B');

    $response = $this->actingAs($student)->get(route('admin.users.index'));

    $response->assertRedirect(route('user.dashboard'));
});

it('admin bị chặn truy cập route middleware lecturer sẽ được chuyển về dashboard.admin', function () {
    $admin = make_user('admin', 'Admin hệ thống');

    // Route tạm dùng middleware 'lecturer' (CheckLecturerRole)
    Route::get('/__test_lecturer', fn () => 'ok')->middleware(['auth', 'lecturer']);

    $response = $this->actingAs($admin)->get('/__test_lecturer');

    $response->assertRedirect(route('dashboard.admin'));
});

it('giảng viên bị chặn truy cập route middleware user sẽ được chuyển về dashboard.lecturer', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');

    // Route tạm dùng middleware 'user' (CheckUserRole)
    Route::get('/__test_user', fn () => 'ok')->middleware(['auth', 'user']);

    $response = $this->actingAs($lecturer)->get('/__test_user');

    $response->assertRedirect(route('dashboard.lecturer'));
});
