# L02 — Email không tồn tại: session thiếu key `errors` (mức THẤP)

## Triệu chứng

- Test đỏ: `tests/Feature/Auth/ForgotPasswordTest.php:34`
  `Session is missing expected key [errors]`.

## Nguyên nhân gốc

- `app/Http/Controllers/Auth/PasswordResetLinkController.php:53-54` khi
  `Password::sendResetLink()` trả về trạng thái khác `RESET_LINK_SENT`
  (email chưa đăng ký) thì code trả
  `back()->withInput(...)->with('error', 'Không thể gửi email...')`.
- Test lại khẳng định `$response->assertSessionHasErrors('email')` — kỳ vọng chuẩn
  `withErrors(['email' => ...])` của Breeze.
- View `resources/views/auth/forgot-password.blade.php` đọc **cả 2 key**
  (`session('error')` dòng 125-129 và `$errors` dòng 132-136) → người dùng thật
  vẫn thấy thông báo lỗi. Lệch đặc tả test ↔ code.

## Cách sửa (2 phương án — khuyến nghị A)

- **Phương án A (sửa TEST, ít rủi ro):** trong
  `tests/Feature/Auth/ForgotPasswordTest.php:34`, đổi
  `$response->assertSessionHasErrors('email')` thành
  `$response->assertSessionHas('error')`. Mail vẫn không gửi
  (`Notification::assertNothingSent()` giữ nguyên).
- **Phương án B (sửa CONTROLLER theo chuẩn Laravel):** trong
  `PasswordResetLinkController.php:53-54`, đổi `->with('error', ...)` thành
  `->withErrors(['email' => ...])`. Chuẩn hơn nhưng phải kiểm tra lại view
  (view hiện đã hỗ trợ `$errors` nên an toàn) và toàn bộ test liên quan.

## Kiểm chứng

- `php artisan test tests/Feature/Auth/ForgotPasswordTest.php --filter="không tồn tại"`

## File liên quan

- `app/Http/Controllers/Auth/PasswordResetLinkController.php:48-54`
- `resources/views/auth/forgot-password.blade.php:125-136`
- `tests/Feature/Auth/ForgotPasswordTest.php:29-36`
