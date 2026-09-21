<?php

use Illuminate\Support\Facades\Route;

use function Tests\Support\make_user;

/*
 * Chức năng "Xóa tài khoản" đã được GỠ BỎ khỏi admin/người dùng:
 * - Không còn route admin.users.destroy
 * - Trang danh sách không còn nút xóa
 * - Cơ chế soft delete của dữ liệu cũ vẫn đảm bảo: tài khoản đã xóa mềm
 *   không đăng nhập được và ẩn khỏi danh sách mặc định
 */

it('đã gỡ bỏ route xóa tài khoản người dùng', function () {
    expect(Route::has('admin.users.destroy'))->toBeFalse();
});

it('trang danh sách người dùng không còn nút xóa tài khoản', function () {
    $admin = make_user('admin', 'Quản trị viên');
    make_user('student', 'Sinh viên B');

    $response = $this->actingAs($admin)->get(route('admin.users.index'));

    $response->assertOk()
        ->assertDontSee('fa-trash', false)   // icon nút xóa
        ->assertSee('fa-edit', false);       // nút sửa vẫn còn
});

it('người dùng đã đánh dấu xóa mềm không đăng nhập được', function () {
    $student = make_user('student', 'Sinh viên bị xóa');
    $email = $student->email;

    // Đánh dấu xóa mềm ở tầng dữ liệu (tài khoản cũ đã xóa trước đây)
    $student->is_deleted = true;
    $student->is_active = false;
    $student->save();
    $student->delete();

    $response = $this->post(route('login'), [
        'email' => $email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('người dùng đã xóa mềm ẩn khỏi danh sách mặc định và hiện khi lọc đã xóa', function () {
    $admin = make_user('admin', 'Quản trị viên');
    $student = make_user('student', 'Sinh viên đã xóa');
    $student->delete();

    $this->actingAs($admin)->get(route('admin.users.index'))
        ->assertOk()
        ->assertDontSee($student->email);

    $this->actingAs($admin)->get(route('admin.users.index', ['is_deleted' => 'deleted']))
        ->assertOk()
        ->assertSee($student->email);
});