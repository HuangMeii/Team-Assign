<?php

use Illuminate\Support\Facades\Auth;

use function Tests\Support\make_user;

/*
 * Chức năng "Ghi nhớ đăng nhập" (remember-me) ở trang đăng nhập:
 * - POST /login có remember=1 ⇒ cấp cookie remember_web_* (recaller) + ghi remember_token
 * - POST /login không remember ⇒ KHÔNG cấp cookie
 * - Cookie remember tự đăng nhập lại khi phiên (session) đã hết hạn
 * - Đăng xuất làm cookie remember hết hiệu lực
 * - Tài khoản bị khóa (is_active=0) KHÔNG thể lợi dụng cookie remember để vào hệ thống
 */

/** Tên cookie recaller động (remember_web_ + sha1(SessionGuard)). */
function recaller_name(): string
{
    return Auth::guard('web')->getRecallerName();
}

/** Tạo user đã xác thực email (tránh vấp bug route verification.notice khi đăng nhập). */
function make_verified_user(string $name = 'Sinh viên remember'): \App\Models\User
{
    $user = make_user('student', $name);
    $user->update(['email_verified_at' => now()]);

    return $user;
}

it('trang đăng nhập hiển thị ô Ghi nhớ đăng nhập', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Ghi nhớ đăng nhập')
        ->assertSee('name="remember"', false);
});

it('đăng nhập có tick Ghi nhớ đăng nhập thì cấp cookie remember', function () {
    $user = make_verified_user();

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
        'remember' => 1,
    ]);

    $response->assertRedirect(route('user.dashboard'))
        ->assertCookie(recaller_name());

    // remember_token được ghi vào DB để xác thực cho những lần sau
    expect($user->fresh()->remember_token)->not->toBeNull();
    $this->assertAuthenticated();
});

it('đăng nhập KHÔNG tick thì không cấp cookie remember', function () {
    $user = make_verified_user();

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('user.dashboard'))
        ->assertCookieMissing(recaller_name());

    $this->assertAuthenticated();
});

it('cookie remember tự đăng nhập lại khi phiên đã hết hạn', function () {
    $user = make_verified_user();

    $login = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
        'remember' => 1,
    ]);
    $login->assertRedirect(route('user.dashboard'));

    // Lấy giá trị thật (đã decrypt) của cookie remember từ response login
    $plain = $login->getCookie(recaller_name(), true)->getValue();

    // Mô phỏng phiên hết hạn: xóa session + reset guard (chỉ cookie remember còn lại)
    $this->flushSession();
    Auth::forgetGuards();

    // Request không có session nhưng có cookie remember ⇒ vẫn được đăng nhập
    $this->withCookie(recaller_name(), $plain)
        ->get('/')
        ->assertRedirect(route('user.dashboard'));

    $this->assertAuthenticated();
});

it('đăng xuất làm cookie remember hết hiệu lực', function () {
    $user = make_verified_user();

    $login = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
        'remember' => 1,
    ]);
    $plain = $login->getCookie(recaller_name(), true)->getValue();

    // Logout KÈM cookie remember (như trình duyệt gửi lại) ⇒ response trả về cookie hết hạn
    $logout = $this->withCookie(recaller_name(), $plain)->post(route('logout'));

    $logout->assertRedirect(route('login'))
        ->assertCookieExpired(recaller_name());
    $this->assertGuest();
});

it('tài khoản bị khóa không thể dùng cookie remember để vào hệ thống', function () {
    $user = make_verified_user();

    $login = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
        'remember' => 1,
    ]);
    $plain = $login->getCookie(recaller_name(), true)->getValue();

    // Admin khóa tài khoản SAU khi người dùng đã đăng nhập có remember
    $user->update(['is_active' => false]);

    $this->flushSession();
    Auth::forgetGuards();

    // Cookie remember còn hạn nhưng tài khoản đã bị khóa ⇒ phải bị chặn
    $this->withCookie(recaller_name(), $plain)
        ->get('/')
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('đăng nhập sai vẫn giữ trạng thái tick Ghi nhớ đăng nhập', function () {
    $user = make_verified_user();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'sai-mat-khau',
        'remember' => 1,
    ])->assertSessionHasErrors('email');

    // old('remember') được flash ⇒ checkbox vẫn đang tick
    $this->get(route('login'))
        ->assertOk()
        ->assertSeeHtml('value="1" checked');
});
