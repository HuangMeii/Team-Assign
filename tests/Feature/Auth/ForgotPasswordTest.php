<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;

use function Tests\Support\make_user;

it('trang quên mật khẩu hiển thị và có link từ trang đăng nhập', function () {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Quên mật khẩu');

    $this->get(route('login'))
        ->assertOk()
        ->assertSee(route('password.request'), false);
});

it('gửi email đặt lại mật khẩu khi nhập email đã đăng ký', function () {
    Notification::fake();
    $user = make_user('student', 'Sinh viên A');

    $response = $this->post(route('password.email'), ['email' => $user->email]);

    $response->assertSessionHas('status');
    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

it('email không tồn tại thì không gửi và báo lỗi', function () {
    Notification::fake();

    $response = $this->post(route('password.email'), ['email' => 'khongton@tai.com']);

    $response->assertSessionHasErrors('email');
    Notification::assertNothingSent();
});

it('click link trong email (đúng token) để đặt lại mật khẩu mới thành công', function () {
    Notification::fake();
    $user = make_user('student', 'Sinh viên B');

    $this->post(route('password.email'), ['email' => $user->email]);

    // Lấy token từ notification (giống hành động click link trong email)
    $token = Notification::sent($user, ResetPasswordNotification::class)->first()->token;

    // Trang reset hiển thị với token
    $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
        ->assertOk()
        ->assertSee('Đặt lại mật khẩu');

    // Đặt mật khẩu mới
    $this->post(route('password.store'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'matkhaumoi123',
        'password_confirmation' => 'matkhaumoi123',
    ])->assertRedirect(route('login'))->assertSessionHas('status');

    // Mật khẩu mới được hash và đăng nhập lại được
    expect(\Illuminate\Support\Facades\Hash::check('matkhaumoi123', $user->fresh()->password))->toBeTrue();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'matkhaumoi123',
    ])->assertRedirect(route('user.dashboard'));
});

it('token sai thì không đặt lại được mật khẩu', function () {
    Notification::fake();
    $user = make_user('student', 'Sinh viên C');

    $this->post(route('password.email'), ['email' => $user->email]);

    $this->post(route('password.store'), [
        'token' => 'token-sai',
        'email' => $user->email,
        'password' => 'matkhaumoi123',
        'password_confirmation' => 'matkhaumoi123',
    ])->assertSessionHasErrors('email');

    expect(\Illuminate\Support\Facades\Hash::check('password', $user->fresh()->password))->toBeTrue();
});

it('nút gửi email đặt lại mật khẩu trong thiết lập tài khoản hoạt động', function () {
    Notification::fake();
    $user = make_user('student', 'Sinh viên D');

    // Nút hiển thị trên trang Mật khẩu (thiết lập tài khoản)
    $this->actingAs($user)->get(route('users.profile.password'))
        ->assertOk()
        ->assertSee(route('users.password.send-reset-link'), false);

    $response = $this->actingAs($user)->post(route('users.password.send-reset-link'));

    $response->assertSessionHas('success');
    Notification::assertSentTo($user, ResetPasswordNotification::class);
});
