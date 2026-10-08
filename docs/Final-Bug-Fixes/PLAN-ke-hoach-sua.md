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

## Bước 6 — L06: thao tác nhanh Gửi email / Reset mật khẩu — ✅ ĐÃ XONG (2026-10-08)

- [x] 6.1. Controller: `abort_unless(admin, 403)` cho `resetPassword()`/`sendEmail()`; reset về `Hash::make('password')`;
      bỏ `generateTempPassword()`; thêm `use Illuminate\Support\Facades\Log;`.
- [x] 6.2. View `students/show.blade.php`: modal gửi email + form confirm reset (chỉ Admin).
- [x] 6.3. View `students/index.blade.php`: nút gửi email (modal dùng chung) + reset (form confirm) mỗi dòng, chỉ Admin.
- [x] 6.4. Sửa bug fatal `App\Mail\StudentNotification` (trùng `$subject` với `Mailable`) ⇒ gửi email mới chạy được.
- [x] 6.5. Login chặn tài khoản `must_change_password` (buộc đổi trước khi vào dashboard) + gỡ cờ khi đổi xong.
- [x] 6.6. Test `tests/Feature/StudentQuickActionsTest.php` (6 case) + `TC-ADMIN-29` trong bộ test case.

Chi tiết: xem `L06-thao-tac-nhanh-email-reset-mat-khau.md`.

## Bước 7 — L07: dọn nốt chỗ sinh mã lớp sai — ✅ ĐÃ XONG (2026-10-08)

- [x] 7.1. `DatabaseSeeder`: mã `WEB-K1-2026` → `LTWEB`; bỏ 2 cột đã xoá (`users.isHaveGroup`, `subjects.lecturer_id`).
- [x] 7.2. `LecturerClassTest` hết mã dài; `AdminClassManagementTest` siết khẳng định 5 ký tự.
- [x] 7.3. Test mới `tests/Feature/ClassCodeFiveCharsTest.php` (5 case, gồm cả kiểm chứng seeder).

Chi tiết: xem `L07-ma-lop-5-ky-tu.md`.

## Bước 8 — L08: chat song song + gắn cờ sau response — ✅ ĐÃ XONG (2026-10-08)

- [x] 8.1. `ChatModerationService`: `Http::pool()` cho 3 bộ lọc (8889 + 8890 + 8888), giữ fail-open.
- [x] 8.2. Job `ModerateDirectMessage` / `ModerateGroupMessage` + `dispatch()->afterResponse()` ở 2 luồng gửi.
- [x] 8.3. Test `tests/Feature/ChatModerationAsyncTest.php` (5 case).

Chi tiết: xem `L08-chat-song-song-hien-truoc-gan-co-sau.md`.

## Bước 9 — L09: Thiết lập tài khoản mức 2 — ✅ ĐÃ XONG (2026-10-08)

- [x] 9.1. Migrate: `users.locale/timezone/hide_online/invite_policy/avatar_path` + `login_histories` + dựng lại `sessions`.
- [x] 9.2. `SettingsController` (Bảo mật | Hồ sơ | Riêng tư) + middleware `SetLocale` + presence/invite policy.
- [x] 9.3. Test `tests/Feature/SettingsLevel2Test.php` (11 case).

Chi tiết: xem `L09-cai-dat-chuan-saas-muc-2.md`.

## Bước 10 — L10: xóa mềm nhóm — ✅ ĐÃ XONG (2026-10-08)

- [x] 10.1. Migrate `groups.deleted_at` + `Groups` dùng `SoftDeletes`.
- [x] 10.2. `GroupService::destroy()` → xóa mềm + nhả đề tài + hủy lời mời/yêu cầu treo (giữ lịch sử);
      thêm `restore()` + `forceDelete()` (chỉ Admin) + `denyGroupManagement()`.
- [x] 10.3. Route `DELETE groups/{id}`, `POST groups/{id}/restore`, `DELETE groups/{id}/force`
      (+ sửa lỗi thiếu middleware `auth` cho nhóm route `groups.*`).
- [x] 10.4. View `groups.index` (tab "Đã xóa") + `groups.show` (nút xóa).
- [x] 10.5. Test `tests/Feature/GroupSoftDeleteTest.php` (8 case).

Chi tiết: xem `L10-xoa-mem-nhom.md`.

## Lệnh chạy nhanh (copy-paste)

```powershell
php artisan test tests/Feature/ChangePasswordTest.php
php artisan test tests/Feature/Auth/ForgotPasswordTest.php
php artisan test tests/Feature/StudentQuickActionsTest.php
php artisan test
```
