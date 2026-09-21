<?php

use App\Mail\EmailChangeVerificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

use function Tests\Support\make_user;

/*
 * Đổi email phải xác thực email mới:
 * - Email mới KHÔNG có hiệu lực ngay: lưu pending_email, gửi mail xác thực tới email mới
 * - Click liên kết signed trong email -> email mới có hiệu lực + email_verified_at
 * - Email cũ vẫn dùng để đăng nhập cho đến khi xác thực
 * - Email không thay đổi -> thông báo "Chưa có thay đổi nào để cập nhật."
 */

it('đổi email: email không đổi ngay, vào pending và gửi mail xác thực tới email mới', function () {
    Mail::fake();
    $user = make_user('student', 'Sinh viên A');
    $newEmail = 'emailmoi_' . uniqid() . '@test.com';

    $response = $this->actingAs($user)->put(route('users.profile.update'), [
        'name' => $user->name,
        'email' => $newEmail,
    ]);

    $response->assertSessionHas('success');

    $fresh = $user->fresh();
    expect($fresh->email)->toBe($user->email)
        ->and($fresh->pending_email)->toBe($newEmail);

    Mail::assertSent(EmailChangeVerificationMail::class, function ($mail) use ($newEmail) {
        return $mail->hasTo($newEmail) && !empty($mail->verificationUrl);
    });
});

it('chỉ đổi tên, email giữ nguyên: thành công và không gửi mail xác thực', function () {
    Mail::fake();
    $user = make_user('student', 'Sinh viên B');

    $this->actingAs($user)->put(route('users.profile.update'), [
        'name' => 'Tên mới',
        'email' => $user->email,
    ])->assertSessionHas('success')->assertSessionHas('info');

    $fresh = $user->fresh();
    expect($fresh->name)->toBe('Tên mới')
        ->and($fresh->pending_email)->toBeNull();

    Mail::assertNothingSent();
});

it('không thay đổi gì: cảnh báo chưa có thay đổi để cập nhật', function () {
    Mail::fake();
    $user = make_user('student', 'Sinh viên C');

    $this->actingAs($user)->put(route('users.profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
    ])->assertSessionHas('warning', 'Chưa có thay đổi nào để cập nhật.');

    Mail::assertNothingSent();

    expect($user->fresh()->name)->toBe($user->name)
        ->and($user->fresh()->pending_email)->toBeNull();
});

it('email mới trùng email của user khác: bị từ chối', function () {
    $other = make_user('student', 'Sinh viên khác');
    $user = make_user('student', 'Sinh viên D');

    $response = $this->actingAs($user)->put(route('users.profile.update'), [
        'name' => $user->name,
        'email' => $other->email,
    ]);

    $response->assertSessionHasErrors('email');

    $fresh = $user->fresh();
    expect($fresh->email)->toBe($user->email)
        ->and($fresh->pending_email)->toBeNull();
});

it('click link signed đúng: email mới có hiệu lực, đã xác thực, pending xóa', function () {
    $user = make_user('student', 'Sinh viên E');
    $newEmail = 'xacthuc_' . uniqid() . '@test.com';
    $user->update(['pending_email' => $newEmail]);

    $url = URL::temporarySignedRoute('users.email.verify', now()->addMinutes(60), [
        'id' => $user->user_id,
        'hash' => sha1($newEmail),
    ]);

    $this->get($url)
        ->assertRedirect(route('users.profile.info'))
        ->assertSessionHas('success');

    $fresh = $user->fresh();
    expect($fresh->email)->toBe($newEmail)
        ->and($fresh->pending_email)->toBeNull()
        ->and($fresh->email_verified_at)->not->toBeNull();

    // Đăng nhập bằng email mới
    $this->post('/login', [
        'email' => $newEmail,
        'password' => 'password',
    ])->assertRedirect(route('user.dashboard'));
});

it('hash sai hoặc link không có chữ ký thì từ chối', function () {
    $user = make_user('student', 'Sinh viên F');
    $user->update(['pending_email' => 'sai_hash_' . uniqid() . '@test.com']);

    // Hash sai (URL vẫn signed)
    $url = URL::temporarySignedRoute('users.email.verify', now()->addMinutes(60), [
        'id' => $user->user_id,
        'hash' => sha1('khac_hoan_toan@test.com'),
    ]);

    $this->get($url)
        ->assertRedirect(route('users.profile.info'))
        ->assertSessionHas('error');

    // Không có chữ ký -> 403
    $this->get(route('users.email.verify', [
        'id' => $user->user_id,
        'hash' => sha1($user->pending_email),
    ]))->assertStatus(403);

    $fresh = $user->fresh();
    expect($fresh->email)->toBe($user->email)
        ->and($fresh->pending_email)->toBe($user->pending_email);
});

it('nút gửi lại email xác thực gửi mail tới email đang chờ', function () {
    Mail::fake();
    $user = make_user('student', 'Sinh viên G');
    $newEmail = 'guelai_' . uniqid() . '@test.com';
    $user->update(['pending_email' => $newEmail]);

    $this->actingAs($user)->post(route('users.email.resend'))
        ->assertSessionHas('success');

    Mail::assertSent(EmailChangeVerificationMail::class, fn ($mail) => $mail->hasTo($newEmail));
});

it('trang thông tin hiển thị banner email đang chờ xác thực', function () {
    $user = make_user('student', 'Sinh viên H');
    $user->update(['pending_email' => 'cho_xac_thuc_' . uniqid() . '@test.com']);

    $this->actingAs($user)->get(route('users.profile.info'))
        ->assertOk()
        ->assertSee($user->pending_email)
        ->assertSee('đang chờ xác thực');
});