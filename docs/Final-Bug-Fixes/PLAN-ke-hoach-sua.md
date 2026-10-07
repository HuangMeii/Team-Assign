# Kế hoạch sửa 4 lỗi cuối cùng (thứ tự + checklist + lệnh chạy)

> Nguyên tắc: L04 sửa CODE (bug thật). L01–L03 sửa TEST (giữ controller + view
> vì UI đang hiển thị đúng key `success`/`error`).

## Bước 1 — L04: gỡ gate xác thực mail ở đăng nhập (làm TRƯỚC)

- [ ] 1.1. Mở `app/Http/Controllers/AuthController.php`, xóa nhánh dòng 49-52
      (`hasVerifiedEmail` → `verification.notice`).
- [ ] 1.2. Xóa nhánh dòng 54-58 (`just_verified_email` → `login.verify.done`).
- [ ] 1.3. Xóa method `verifyDone()` (dòng 88-103, code chết).
- [ ] 1.4. Giữ nguyên chặn `is_deleted` / `is_active` và redirect theo role.
- [ ] 1.5. Không nạp `routes/auth.php` trong `bootstrap/app.php` (tránh xung đột
      route `login/register/logout`).
- [ ] 1.6. Chạy: `php artisan test tests/Feature/ChangePasswordTest.php` → xanh.
- [ ] 1.7. Thêm case mới: tài khoản `email_verified_at = NULL` đăng nhập thành công,
      về đúng dashboard theo role (student/lecturer/admin).
- [ ] 1.8. Quyết định file Breeze chết (giữ mặc định, hoặc xóa):
      `routes/auth.php`, `ProfileController.php`,
      `resources/views/auth/verify-email.blade.php`, `verify-done.blade.php`,
      `register.blade.php`, `confirm-password.blade.php`.

Chi tiết: xem `L04-login-chan-xac-thuc-mail-500.md`.

## Bước 2 — L01: sửa kỳ vọng `status` → `success` (sửa TEST)

- [ ] 2.1. `tests/Feature/Auth/ForgotPasswordTest.php:25`:
      `assertSessionHas('status')` → `assertSessionHas('success')`.
- [ ] 2.2. Chạy: `php artisan test tests/Feature/Auth/ForgotPasswordTest.php --filter="gửi email đặt lại"`.

Chi tiết: xem `L01-quen-mat-khau-thieu-status.md`.

## Bước 3 — L02: sửa kỳ vọng `errors` → `error` (sửa TEST, phương án A)

- [ ] 3.1. `tests/Feature/Auth/ForgotPasswordTest.php:34`:
      `assertSessionHasErrors('email')` → `assertSessionHas('error')`.
      (Phương án B — sửa controller sang `withErrors` — chỉ làm nếu chủ repo yêu cầu.)
- [ ] 3.2. Chạy: `php artisan test tests/Feature/Auth/ForgotPasswordTest.php --filter="không tồn tại"`.

Chi tiết: xem `L02-email-khong-ton-tai-thieu-errors.md`.

## Bước 4 — L03: sửa kỳ vọng redirect sau reset (sửa TEST)

- [ ] 4.1. `tests/Feature/Auth/ForgotPasswordTest.php:58`:
      `assertRedirect(route('login'))->assertSessionHas('status')` →
      `assertRedirect(route('user.dashboard'))->assertSessionHas('success')`
      (user trong test là role student).
- [ ] 4.2. Chạy: `php artisan test tests/Feature/Auth/ForgotPasswordTest.php --filter="click link"`.

Chi tiết: xem `L03-reset-password-redirect-sai-ky-vong.md`.

## Bước 5 — Chạy full suite + cập nhật docs

- [ ] 5.1. `php artisan test` → kỳ vọng 351/351 pass (hiện 347 pass / 4 fail).
- [ ] 5.2. Cập nhật `docs/test-cases/01-xac-thuc-va-tai-khoan.md`
      (TC-AUTH-07/08/09 + TC-AUTH-11/13/25: kỳ vọng mới).
- [ ] 5.3. Cập nhật `docs/test-cases/README.md` (bảng dòng 153-158) và
      `docs/FEATURE_STATUS.md` (dòng ~200-202: xóa ghi chú bug `verification.notice`).

## Lệnh chạy nhanh (copy-paste)

```powershell
php artisan test tests/Feature/ChangePasswordTest.php
php artisan test tests/Feature/Auth/ForgotPasswordTest.php
php artisan test
```
