<?php

use Illuminate\Support\Facades\Hash;

use function Tests\Support\make_user;

it('đổi mật khẩu thành công, mật khẩu mới được hash và đăng nhập lại được', function () {
    $user = make_user('student', 'Sinh viên A');

    $response = $this->actingAs($user)->put(route('users.password.update'), [
        'current_password' => 'password',
        'new_password' => 'newpassword123',
        'new_password_confirmation' => 'newpassword123',
    ]);

    $response->assertSessionHas('success');

    // Mật khẩu trong DB phải là HASH (không được là plaintext)
    $fresh = $user->fresh();
    expect($fresh->password)->not->toBe('newpassword123')
        ->and(Hash::check('newpassword123', $fresh->password))->toBeTrue();

    // Đăng xuất rồi đăng nhập lại bằng mật khẩu MỚI -> phải thành công
    $this->post(route('logout'));

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'newpassword123',
    ])->assertRedirect(route('user.dashboard'));
});

it('sai mật khẩu hiện tại thì không đổi được', function () {
    $user = make_user('student', 'Sinh viên B');

    $response = $this->actingAs($user)->put(route('users.password.update'), [
        'current_password' => 'sai-mat-khau',
        'new_password' => 'newpassword123',
        'new_password_confirmation' => 'newpassword123',
    ]);

    $response->assertSessionHasErrors('current_password');

    // Mật khẩu cũ vẫn còn hiệu lực
    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

it('mật khẩu mới quá ngắn bị từ chối', function () {
    $user = make_user('student', 'Sinh viên C');

    $response = $this->actingAs($user)->put(route('users.password.update'), [
        'current_password' => 'password',
        'new_password' => '123',
        'new_password_confirmation' => '123',
    ]);

    $response->assertSessionHasErrors('new_password');
    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

it('trang đổi mật khẩu hiển thị cho sinh viên', function () {
    $user = make_user('student', 'Sinh viên D');

    $this->actingAs($user)->get(route('users.profile.password'))->assertOk();
});
