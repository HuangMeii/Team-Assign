<?php

/*
|--------------------------------------------------------------------------
| L06 — Thao tác nhanh: Gửi email / Reset mật khẩu sinh viên
|--------------------------------------------------------------------------
| Chốt đã thống nhất:
|  5a-B — CHỈ Admin thấy & gọi được 2 thao tác; giảng viên gọi URL -> 403.
|  5b   — reset mật khẩu về mặc định 'password' + must_change_password = true.
|  5c   — đơn lẻ từng sinh viên (không bulk).
|
| Bao phủ:
|  1. Admin reset: hash = 'password', bật cờ buộc đổi, flash success.
|  2. Admin gửi email: Mail::fake() nhận đúng người nhận / tiêu đề / nội dung.
|  3. Giảng viên gọi trực tiếp 2 route -> 403 (không đổi dữ liệu, không gửi mail).
|  4. Chỉ Admin thấy nút trên trang chi tiết; giảng viên không thấy.
|  5. Sinh viên sau reset đăng nhập -> bị buộc đổi mật khẩu trước khi vào dashboard.
|  6. Đổi mật khẩu xong -> cờ được gỡ, đăng nhập lại vào thẳng dashboard.
*/

use App\Mail\StudentNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

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

it('Admin reset mật khẩu sinh viên về mặc định password và bật cờ buộc đổi', function () {
    expect(Hash::check('old-secret-123', $this->student->fresh()->password))->toBeTrue()
        ->and($this->student->fresh()->must_change_password)->toBeFalse();

    $this->actingAs($this->admin)
        ->post(route('students.reset-password', $this->student->user_id))
        ->assertSessionHas('success');

    $fresh = $this->student->fresh();
    expect(Hash::check('password', $fresh->password))->toBeTrue()
        ->and(Hash::check('old-secret-123', $fresh->password))->toBeFalse()
        ->and($fresh->must_change_password)->toBeTrue();
});

it('Admin gửi email cho sinh viên đúng người nhận, tiêu đề và nội dung', function () {
    Mail::fake();

    $this->actingAs($this->admin)
        ->post(route('students.send-email', $this->student->user_id), [
            'subject' => 'Thông báo nộp báo cáo',
            'message' => 'Bạn cần nộp báo cáo trước 20h hôm nay.',
        ])
        ->assertSessionHas('success');

    Mail::assertSent(StudentNotification::class, function (StudentNotification $mail) {
        return $mail->hasTo($this->student->email)
            && $mail->mailSubject === 'Thông báo nộp báo cáo'
            && $mail->mailMessage === 'Bạn cần nộp báo cáo trước 20h hôm nay.';
    });
});

it('Giảng viên gọi trực tiếp 2 route thao tác nhanh thì bị 403', function () {
    Mail::fake();
    $passwordBefore = $this->student->fresh()->password;

    $this->actingAs($this->lecturer)
        ->post(route('students.send-email', $this->student->user_id), [
            'subject' => 'Không được phép',
            'message' => 'Không được phép',
        ])
        ->assertForbidden();

    $this->actingAs($this->lecturer)
        ->post(route('students.reset-password', $this->student->user_id))
        ->assertForbidden();

    Mail::assertNothingSent();
    expect($this->student->fresh()->password)->toBe($passwordBefore)
        ->and($this->student->fresh()->must_change_password)->toBeFalse();
});

it('Chỉ Admin thấy nút Gửi email / Reset mật khẩu ở trang chi tiết sinh viên', function () {
    $this->actingAs($this->admin)
        ->get(route('students.show', $this->student->user_id))
        ->assertOk()
        ->assertSee('Gửi email')
        ->assertSee('Reset mật khẩu');

    // Giảng viên vẫn xem được sinh viên lớp mình nhưng KHÔNG thấy 2 nút.
    $this->actingAs($this->lecturer)
        ->get(route('students.show', $this->student->user_id))
        ->assertOk()
        ->assertDontSee('Gửi email')
        ->assertDontSee('Reset mật khẩu');
});

it('Chỉ Admin thấy nút Gửi email / Reset mật khẩu ở danh sách sinh viên', function () {
    $this->actingAs($this->admin)
        ->get(route('students.index'))
        ->assertOk()
        ->assertSee('Gửi email')
        ->assertSee('Reset mật khẩu');

    // Giảng viên vẫn xem được danh sách lớp mình nhưng KHÔNG thấy 2 nút.
    $this->actingAs($this->lecturer)
        ->get(route('students.index'))
        ->assertOk()
        ->assertDontSee('Gửi email')
        ->assertDontSee('Reset mật khẩu');
});

it('Sinh viên sau khi bị reset phải đổi mật khẩu ở lần đăng nhập kế tiếp', function () {
    $this->actingAs($this->admin)
        ->post(route('students.reset-password', $this->student->user_id))
        ->assertSessionHas('success');

    $this->post(route('logout'));

    $this->post('/login', [
        'email' => $this->student->email,
        'password' => 'password',
    ])->assertRedirect(route('users.profile.password'));

    $this->assertAuthenticatedAs($this->student);
});

it('Đổi mật khẩu xong thì cờ buộc đổi được gỡ và lần sau vào thẳng dashboard', function () {
    $this->actingAs($this->admin)
        ->post(route('students.reset-password', $this->student->user_id));

    $this->post(route('logout'));

    $this->post('/login', [
        'email' => $this->student->email,
        'password' => 'password',
    ])->assertRedirect(route('users.profile.password'));

    $this->actingAs($this->student->fresh())
        ->put(route('users.password.update'), [
            'current_password' => 'password',
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
