# L04 — Đăng nhập tài khoản chưa xác thực mail bị HTTP 500 (ưu tiên CAO)

## Triệu chứng

- Test đỏ: `tests/Feature/ChangePasswordTest.php:26-29` — sau khi đổi mật khẩu, bước
  `POST /login` với tài khoản mới tạo (`make_user()` không set `email_verified_at`
  → `NULL`) bị `RouteNotFoundException: Route [verification.notice] not defined`.
- Thực tế ngoài trình duyệt: bất kỳ tài khoản nào có `email_verified_at = NULL`
  đăng nhập đều 500 (TC-AUTH-11, TC-AUTH-13, TC-AUTH-25 trong
  `docs/test-cases/01-xac-thuc-va-tai-khoan.md`).

## Nguyên nhân gốc

1. `app/Http/Controllers/AuthController.php:49-58` — sau khi `Auth::attempt()`
   thành công, code kiểm tra `!$user->hasVerifiedEmail()` rồi
   `redirect()->route("verification.notice")`; nhánh `just_verified_email` trỏ tới
   `route("login.verify.done")`.
2. Cả 2 route (`verification.notice`, `login.verify.done`) đều **không tồn tại**:
   `verification.notice` nằm trong `routes/auth.php:39-40` (Breeze), nhưng
   `bootstrap/app.php:8-13` chỉ nạp `routes/web.php` + console + channels —
   **không nạp `routes/auth.php`**. `login.verify.done` không được định nghĩa ở đâu
   (search toàn repo = 0 kết quả); method `verifyDone()` là code chết.
3. `User` model không `implements MustVerifyEmail`, không route nào gắn middleware
   `verified` → gate trong `login()` là điểm chặn duy nhất.

## Chính sách mới (theo yêu cầu)

- Đăng nhập và tạo tài khoản **KHÔNG cần xác thực mail**.
- Chỉ **quên mật khẩu** (`password.request/email/reset/store`) và **đổi email**
  (`pending_email` + signed URL 60 phút → `users.email.verify` + `users.email.resend`)
  mới cần xác thực qua mail. Hai luồng này giữ nguyên, không đụng tới.

## Cách sửa (sửa CODE)

Trong `app/Http/Controllers/AuthController.php`:

1. Xóa nhánh dòng 49-52:
   `if (!$user->hasVerifiedEmail()) { return redirect()->route("verification.notice"); }`
2. Xóa nhánh dòng 54-58 (`just_verified_email` → `login.verify.done`).
3. Xóa method `verifyDone()` (dòng 88-103) — không còn ai gọi.
4. Giữ nguyên chặn `is_deleted` / `is_active` và redirect theo role.
5. **KHÔNG** nạp `routes/auth.php` trong `bootstrap/app.php` (nạp vào sẽ xung đột
   route `login/register/logout` với `AuthController` hiện tại).
6. Tùy chọn (hỏi chủ repo): xóa file/view Breeze chết —
   `routes/auth.php`, `ProfileController.php` (dòng 32 set `email_verified_at = null`),
   `resources/views/auth/verify-email.blade.php`, `verify-done.blade.php`,
   `register.blade.php`, `confirm-password.blade.php`, các controller `Auth\*`
   không còn dùng. Mặc định **giữ lại** nếu chưa chốt.

## Kiểm chứng

- `php artisan test tests/Feature/ChangePasswordTest.php` → xanh.
- Case mới: tài khoản `email_verified_at = NULL` đăng nhập thành công, về đúng
  dashboard theo role (student → `user.dashboard`, lecturer → `dashboard`,
  admin → `admin.users.index`).
- Quên mật khẩu + đổi email vẫn chạy như cũ (không regression).

## File liên quan

- `app/Http/Controllers/AuthController.php:49-58, 88-103`
- `routes/auth.php:38-59` (code chết, chưa nạp)
- `bootstrap/app.php:8-13`
- `tests/Feature/ChangePasswordTest.php:7-30`
- `tests/Support/Fixtures.php:11-20` (`make_user` không set `email_verified_at`)
