# L01 — Quên mật khẩu: session thiếu key `status` (mức THẤP)

## Triệu chứng

- Test đỏ: `tests/Feature/Auth/ForgotPasswordTest.php:25`
  `Session is missing expected key [status]`.

## Nguyên nhân gốc

- `app/Http/Controllers/Auth/PasswordResetLinkController.php:44-46` khi gửi link
  thành công trả `back()->with('success', 'Đã gửi email đặt lại mật khẩu...')`.
- Test lại khẳng định `$response->assertSessionHas('status')` — đây là kỳ vọng kiểu
  Breeze mặc định (`Password::sendResetLink` + `with('status', __($status))`), trong
  khi code dự án đã Việt hóa sang key `success`.
- Mail **vẫn gửi được** (`Notification::assertSentTo` sẽ pass nếu key đúng) và view
  `resources/views/auth/forgot-password.blade.php:119-123` đọc đúng key `success`
  → người dùng thật không bị ảnh hưởng. Lệch đặc tả test ↔ code.

## Cách sửa (sửa TEST, giữ controller + view)

Trong `tests/Feature/Auth/ForgotPasswordTest.php:25`, đổi:

```php
$response->assertSessionHas('status');
```

thành:

```php
$response->assertSessionHas('success');
```

(Luồng gửi từ trang Thiết lập tài khoản — case cuối file — đã dùng `success` và đang pass.)

## Kiểm chứng

- `php artisan test tests/Feature/Auth/ForgotPasswordTest.php --filter="gửi email đặt lại"`

## File liên quan

- `app/Http/Controllers/Auth/PasswordResetLinkController.php:44-46`
- `resources/views/auth/forgot-password.blade.php:118-129`
- `tests/Feature/Auth/ForgotPasswordTest.php:19-27`
