<?php

/*
|--------------------------------------------------------------------------
| L06 — Thao tác nhanh: Reset mật khẩu sinh viên
|--------------------------------------------------------------------------
| Chốt đã thống nhất:
|  5a-B — CHỈ Admin thấy & gọi được thao tác reset; giảng viên gọi URL -> 403.
|  5b   — reset mật khẩu về mặc định 'password' (2026-10-10: KHÔNG bật cờ buộc đổi nữa).
|  5c   — đơn lẻ từng sinh viên (không bulk).
|
| Bao phủ:
|  1. Admin reset: hash = 'password', must_change_password = FALSE, flash success.
|  2. Giảng viên gọi trực tiếp route reset -> 403 (không đổi dữ liệu).
|  3. Chỉ Admin thấy nút Reset ở trang chi tiết; giảng viên không thấy.
|  4. Admin bị redirect khỏi /students; giảng viên vẫn xem được danh sách.
|  5. Sinh viên sau reset đăng nhập bằng 'password' -> vào THẲNG dashboard.
|  6. Cơ chế "buộc đổi" (Bó-4): set cờ trực tiếp -> middleware chặn, đổi xong gỡ cờ.
*/

use Illuminate\Support\Facades\Hash;

use function Tests\Support\make_class;
use function Tests\Support\make_subject;
use function Tests\Support\make_user;

beforeEach(function () {
    $this->admin = make_user('admin', 'Admin hệ thống');
    $this->lecturer = make_user('lecturer', 'Giảng viên A');
    $this->subject = make_subject($this->lecturer);
    $this->class = make_class($this->subject, $this->lecturer);

    $this->student = make_user('student', 'Sinh viên A');
    $this->class->users()->attach($this->student->user_id);

    // Đặt mật khẩu ban đầu KHÁC 'password' để chứng minh reset thực sự ghi đè.
    $this->student->update(['password' => 'old-secret-123']);
});

it('Admin reset mật khẩu sinh viên về mặc định password và KHÔNG bật cờ buộc đổi', function () {
    expect(Hash::check('old-secret-123', $this->student->fresh()->password))->toBeTrue()
        ->and($this->student->fresh()->must_change_password)->toBeFalse();

    $this->actingAs($this->admin)
        ->post(route('students.reset-password', $this->student->user_id))
        ->assertSessionHas('success');

    $fresh = $this->student->fresh();
    expect(Hash::check('password', $fresh->password))->toBeTrue()
        ->and(Hash::check('old-secret-123', $fresh->password))->toBeFalse()
        // 2026-10-10: admin reset KHÔNG ép sinh viên đổi mật khẩu.
        ->and($fresh->must_change_password)->toBeFalse();
});

it('Giảng viên gọi trực tiếp route reset mật khẩu thì bị 403', function () {
    $passwordBefore = $this->student->fresh()->password;

    $this->actingAs($this->lecturer)
        ->post(route('students.reset-password', $this->student->user_id))
        ->assertForbidden();

    expect($this->student->fresh()->password)->toBe($passwordBefore)
        ->and($this->student->fresh()->must_change_password)->toBeFalse();
});

it('Chỉ Admin thấy nút Reset mật khẩu ở trang chi tiết sinh viên (không còn Gửi email)', function () {
    $this->actingAs($this->admin)
        ->get(route('students.show', $this->student->user_id))
        ->assertOk()
        ->assertSee('Reset mật khẩu')
        ->assertDontSee('Gửi email');

    // Giảng viên vẫn xem được sinh viên lớp mình nhưng KHÔNG thấy thao tác nhanh.
    $this->actingAs($this->lecturer)
        ->get(route('students.show', $this->student->user_id))
        ->assertOk()
        ->assertDontSee('Gửi email')
        ->assertDontSee('Reset mật khẩu');
});

it('Admin KHÔNG truy cập được danh sách sinh viên; giảng viên vẫn xem được', function () {
    $this->actingAs($this->admin)
        ->get(route('students.index'))
        ->assertRedirect(route('dashboard'));

    // Giảng viên vẫn xem được danh sách lớp mình (đã xóa hẳn nút Gửi email).
    $this->actingAs($this->lecturer)
        ->get(route('students.index'))
        ->assertOk()
        ->assertDontSee('Gửi email');
});

it('Sinh viên sau khi bị reset đăng nhập bằng password và vào THẲNG dashboard', function () {
    $this->actingAs($this->admin)
        ->post(route('students.reset-password', $this->student->user_id))
        ->assertSessionHas('success');

    // 2026-10-10: reset KHÔNG bật cờ ⇒ đăng nhập là vào thẳng dashboard, không bị đá sang trang đổi MK.
    expect($this->student->fresh()->must_change_password)->toBeFalse();

    $this->post(route('logout'));

    $this->post('/login', [
        'email' => $this->student->email,
        'password' => 'password',
    ])->assertRedirect(route('user.dashboard'));

    $this->assertAuthenticatedAs($this->student);
});

it('Đổi mật khẩu xong thì cờ buộc đổi được gỡ và lần sau vào thẳng dashboard', function () {
    // 2026-10-10: Reset mật khẩu KHÔNG bật cờ nữa ⇒ set cờ trực tiếp để kiểm chứng cơ chế.
    $this->student->update(['must_change_password' => true]);

    $this->post('/login', [
        'email' => $this->student->email,
        'password' => 'old-secret-123',
    ])->assertRedirect(route('users.profile.password'));

    $this->actingAs($this->student->fresh())
        ->put(route('users.password.update'), [
            'current_password' => 'old-secret-123',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ])
        ->assertSessionHas('success');

    $fresh = $this->student->fresh();
    expect($fresh->must_change_password)->toBeFalse()
        ->and(Hash::check('newpassword123', $fresh->password))->toBeTrue();

    $this->post(route('logout'));

    $this->post('/login', [
        'email' => $this->student->email,
        'password' => 'newpassword123',
    ])->assertRedirect(route('user.dashboard'));
});

it('Bó-4: cờ must_change_password CHẶN mọi trang khác cho tới khi đổi mật khẩu xong', function () {
    // 2026-10-10: reset không bật cờ ⇒ set cờ trực tiếp (mật khẩu 'password' như sau khi reset).
    $this->student->update(['must_change_password' => true, 'password' => 'password']);

    $student = $this->student->fresh();

    // 1) Vào trang khác ⇒ bị đẩy về trang Đổi mật khẩu (trước đây chỉ chặn ở bước login).
    $this->actingAs($student)
        ->get(route('user.dashboard'))
        ->assertRedirect(route('users.profile.password'));

    $this->actingAs($student)
        ->get(route('users.settings.security'))
        ->assertRedirect(route('users.profile.password'));

    // 2) Trang đổi mật khẩu vẫn truy cập được.
    $this->actingAs($student)->get(route('users.profile.password'))->assertOk();

    // 3) Đổi xong ⇒ cờ được gỡ và vào được dashboard.
    $this->actingAs($student)->put(route('users.password.update'), [
        'current_password' => 'password',
        'new_password' => 'newpassword123',
        'new_password_confirmation' => 'newpassword123',
    ])->assertSessionHas('success');

    $this->actingAs($this->student->fresh())
        ->get(route('user.dashboard'))
        ->assertOk();
});
