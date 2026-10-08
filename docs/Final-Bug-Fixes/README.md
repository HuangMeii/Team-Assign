# Những lỗi cuối cùng cần sửa (L01–L09, L11 ĐÃ SỬA xong — trước đó 347 pass / 4 fail + backlog L10)

Thư mục này gom toàn bộ lỗi còn đỏ + kế hoạch sửa chi tiết.
Xem file `PLAN-ke-hoach-sua.md` để biết thứ tự làm.

## Bảng tổng hợp (11 mục: L01–L09, L11 ĐÃ TRIỂN KHAI + L10 backlog)

| Mã | Test đỏ | Triệu chứng | Nguyên nhân gốc | Mức độ | File chi tiết |
|----|---------|-------------|-----------------|--------|---------------|
| L01 | `ForgotPasswordTest > gửi email đặt lại mật khẩu khi nhập email đã đăng ký` | `Session is missing expected key [status]` | `PasswordResetLinkController::store()` trả `with('success', ...)` nhưng test khẳng định `assertSessionHas('status')` (kỳ vọng kiểu Breeze mặc định) | Thấp — lệch đặc tả test ↔ code, mail vẫn gửi được | `L01-quen-mat-khau-thieu-status.md` |
| L02 | `ForgotPasswordTest > email không tồn tại thì không gửi và báo lỗi` | `Session is missing expected key [errors]` | Controller trả `back()->with('error', ...)` còn test khẳng định `assertSessionHasErrors('email')` (chuẩn `withErrors`) | Thấp — lệch đặc tả, UI vẫn hiện lỗi vì view đọc cả 2 key | `L02-email-khong-ton-tai-thieu-errors.md` |
| L03 | `ForgotPasswordTest > click link trong email (đúng token) để đặt lại mật khẩu mới thành công` | Redirect về `/user/dashboard` thay vì `/login` như test kỳ vọng | `NewPasswordController::store()` **đăng nhập luôn** rồi redirect theo role + flash `success`; test vẫn giữ kỳ vọng Breeze cũ (về `/login` + flash `status`) | Trung bình — hành vi chủ ý, test cũ chưa cập nhật | `L03-reset-password-redirect-sai-ky-vong.md` |
| L04 | `ChangePasswordTest > đổi mật khẩu thành công...` (bước đăng nhập lại) | `Route [verification.notice] not defined` → HTTP 500 | `AuthController::login():49-58` chặn tài khoản `email_verified_at = NULL`, redirect tới route `verification.notice` nằm trong `routes/auth.php` — file này **không được nạp** trong `bootstrap/app.php` | Cao — theo yêu cầu mới thì **bỏ hẳn gate này**: đăng nhập / tạo tài khoản KHÔNG cần xác thực mail; chỉ quên mật khẩu + đổi email mới cần | `L04-login-chan-xac-thuc-mail-500.md` |
| L05 | Trạng thái Đang học / Đã rời lớp + lỗi thêm/xóa SV khỏi lớp, nhóm còn sót SV đã rời | Chưa có cột trạng thái `(user_id, class_id)`; `detach/sync` trên relation `students()` bị lọc `role` gây lỗi thêm/xóa; nhóm không lọc trạng thái lớp | Chốt 1a: mở rộng `user_classes` (`status`, `left_at`), xóa mềm thay vì `detach`; **Chốt 2a: KHÔNG bỏ trưởng nhóm — giữ `groups.leader_id`, trưởng nhóm = người CUỐI CÙNG rời lớp (nhóm rỗng vẫn giữ lại, đếm thành viên theo `studying`, quay lại lớp thì nhóm hồi sinh)** | — ĐÃ TRIỂN KHAI: migrate DONE, full suite 364 passed / 0 failed (`ClassMembershipStatusTest` 12 case) | `L05-trang-thai-sinh-vien-trong-lop.md` |
| L06 | Thao tác nhanh Gửi email / Reset mật khẩu: backend có, nút web là alert giả | `students/show.blade.php:192-197` 2 nút chỉ `alert('đang phát triển')`; `students/index` chưa có nút; thiếu `use Log`; docs nhắc `check-email` không tồn tại | Chốt 5a-B (chỉ Admin, GV 403), 5b=reset về `password` + flash, 5c=đơn lẻ; đấu dây modal + form confirm + phân quyền. **Phát hiện thêm bug thật: `App\Mail\StudentNotification` fatal vì trùng `$subject` với `Mailable`** | — ĐÃ TRIỂN KHAI: modal + form confirm ở `show` (Admin), nút ở `index`, cờ `must_change_password` chặn ở login; full suite 371 passed / 0 failed (`StudentQuickActionsTest` 7 case) | `L06-thao-tac-nhanh-email-reset-mat-khau.md` |
| L07 | Mã lớp admin dạng `{subject_code}-NN` vượt quá 5 ký tự, không thống nhất với giảng viên | `ClassSectionController::store` dùng `generateClassCode()` riêng; DB có 4 mã dài + 2 NULL | Admin dùng chung `generateUniqueClassCode()` 5 ký tự; join siết `size:5`; migration quy đổi mã cũ; factory/test cập nhật. **Đợt 2: `DatabaseSeeder` còn mã `WEB-K1-2026` + ghi 2 cột đã xóa (`isHaveGroup`, `subjects.lecturer_id`); `LecturerClassTest` còn mã `'MA1-'.uniqid()`** | — ĐÃ TRIỂN KHAI (đợt 1 + đợt 2): migrate DONE; full suite **376 passed / 0 failed** (`ClassCodeFiveCharsTest` 5 case) | `L07-ma-lop-5-ky-tu.md` |
| L08 | Gửi tin nhắn chậm vì 3 check AI chạy nối tiếp đồng bộ trước INSERT | `DirectChat`/ `GroupChatService` gọi `Text(8889) → Sensitive(8890) → Vision(8888)` nối tiếp (timeout 3s/3s/15-20s) + broadcast Now + frontend chờ response | (a) `Http::pool()` song song + giảm timeout (3s→2s, 15s→5s); (c) INSERT sạch → trả JSON → job `afterResponse()` gắn cờ sau; BỎ (b) | — ĐÃ TRIỂN KHAI: `ChatModerationService` dùng `Http::pool` + 2 job `ModerateDirectMessage`/`ModerateGroupMessage` (không cần worker); full suite **392 passed / 0 failed** (`ChatModerationAsyncTest` 5 case) | `L08-chat-song-song-hien-truoc-gan-co-sau.md` |
| L09 | Mục Cài đặt chỉ có Hồ sơ + Đổi MK, 4 gợi ý còn lại là text chết | `users/profile-info.blade.php:82-93` card gợi ý không link/backend; thiếu lịch sử login, session DB, avatar, locale, pref riêng tư | Mức 2: Bảo mật (login_histories, phiên + revoke, remember) + Hồ sơ (avatar, vi/en, timezone) + Riêng tư (block UI, ẩn online, invite_policy); 5 tab Settings | — ĐÃ TRIỂN KHAI: migrate DONE (users 5 cột + `login_histories` + dựng lại `sessions`, `SESSION_DRIVER=database`), `SettingsController` 5 tab; full suite **392 passed / 0 failed** (`SettingsLevel2Test` 11 case) | `L09-cai-dat-chuan-saas-muc-2.md` |
| L10 | Admin/GV không xóa được nhóm (GroupController chỉ đọc, destroy() là code chết xóa cứng) | Route `groups.*` chỉ 2 GET; `Groups` không `SoftDeletes`/không `deleted_at`; logic 1-nhóm/1-lớp chưa loại nhóm xóa | Xóa mềm + nhả đề tài về chưa đăng ký (transaction) + SV tạo/Tham gia nhóm mới; restore (về chưa đề tài) + forceDelete (chỉ admin); route + nút phân quyền | Trung bình — PLAN để đó, chưa triển khai code | `L10-xoa-mem-nhom.md` |
| L11 | Danh sách thêm SV xấu (select thô, không format, khó chọn nhiều) | 2 view admin/lecturer dùng `<select multiple>` chỉ Tên (email); không avatar/nhóm/lớp, không đếm, không phân trang | Modal dùng chung (tìm kiếm + checkbox + badge nhóm/lớp + đếm + chọn tất cả/bỏ chọn) + JS chung + eager-load; giữ route/backend mảng cũ | — ĐÃ TRIỂN KHAI: 42 test xanh | `L11-danh-sach-them-sinh-vien.md` |

## Quy ước sửa (ĐÃ HOÀN THÀNH)

- L01–L03: **sửa TEST** (giữ nguyên controller + view, vì UI đang dùng `success`/`error` và hiển thị đúng) — commit `027a4f3` (L01), `7d54489` (L02), `6d18aa8` (L03).
- L04: **sửa CODE** (gỡ gate `hasVerifiedEmail` + nhánh `just_verified_email` + method `verifyDone()` chết trong `AuthController`) — commit `e1a53ad`.
- L05: **sửa CODE + VIEW + TEST** (migration `2026_10_07_000002` chạy ngày 2026-10-08):
  `user_classes.status/left_at` (xóa mềm), model `ClassSection::students()/users()` + `User::classes()` chỉ đọc
  `studying` (thêm `allStudents()`/`allClasses()` cho Admin/GV), sửa lỗi thêm/xóa SV (`addStudents/removeStudent/
  lecturerClassesAddStudents/lecturerClassesRemoveStudent` + `ClassJoinController`), **Chốt 2a — giữ trưởng nhóm:
  người cuối cùng rời lớp vẫn là trưởng nhóm và nhóm KHÔNG bị giải tán** (`GroupService::transferLeadershipOnLeave()`
  + `restoreMembershipOnRejoin()`), đếm thành viên theo `Groups::activeMemberCount()`, view Admin/GV có cột trạng thái
  + bộ lọc + nút "Cho rời lớp ↔ Thêm lại" + nhãn "Đã rời hết".
- L06: **sửa CODE + VIEW + MAIL + TEST** (2026-10-08): 2 nút thao tác nhanh đấu dây thật (modal gửi email +
  form confirm reset) và **chỉ Admin** thấy/dùng được (`abort_unless(admin)` phía controller), reset về mật khẩu
  mặc định `password` + `must_change_password` (login chặn buộc đổi), sửa bug fatal của `StudentNotification`
  (trùng thuộc tính `$subject` với `Mailable`), thêm `use Log`.
- L08: **sửa CODE + TEST** (2026-10-08): song song hoá 3 check bằng `Http::pool()` trong `ChatModerationService`
  (giảm timeout 3s→2s, 15s→5s) + hiện tin trước/gắn cờ sau bằng job `ModerateDirectMessage`/`ModerateGroupMessage`
  qua `dispatch()->afterResponse()` (không cần `queue:work`); giữ nguyên flag-only + fail-open.
- L09: **sửa CODE + MIGRATE + VIEW + TEST** (2026-10-08): migrate `users.locale/timezone/hide_online/invite_policy/avatar_path`
  + bảng `login_histories` + **dựng lại bảng `sessions`** để dùng `SESSION_DRIVER=database`; `SettingsController` 5 tab
  (Thông tin | Mật khẩu | Bảo mật | Hồ sơ | Riêng tư); middleware `SetLocale`; `PresenceService`/`InvitationService`
  tôn trọng tuỳ chọn riêng tư; card "gợi ý" text chết → 5 link thật.
- Verify: `ForgotPasswordTest` 6/6, `ChangePasswordTest` + `RememberLoginTest` xanh (17 passed nhóm auth), chống regression `EmailChangeVerificationTest + AdminSoftDeleteTest` 12 passed.
- L05 verify: `tests/Feature/ClassMembershipStatusTest.php` **12 passed**; full suite **364 passed / 0 failed**.
- L06 verify: `tests/Feature/StudentQuickActionsTest.php` **7 passed**; full suite **371 passed / 0 failed**.
- L07 verify: `tests/Feature/ClassCodeFiveCharsTest.php` **5 passed**; full suite **376 passed / 0 failed**.
- L08 verify: `tests/Feature/ChatModerationAsyncTest.php` **5 passed**; full suite **381 passed / 0 failed**.
- L09 verify: `tests/Feature/SettingsLevel2Test.php` **11 passed**; full suite **392 passed / 0 failed**.

## Thứ tự đọc (đã làm xong L01–L09 trừ L10)

1. `L04-login-chan-xac-thuc-mail-500.md` (làm trước — bug thật, chặn người dùng thật). ✅
2. `L01`, `L02`, `L03` (lệch test, làm sau). ✅
3. `L05-trang-thai-sinh-vien-trong-lop.md` (mục 2 = Chốt 2a: giữ trưởng nhóm = người cuối cùng rời lớp). ✅
4. `L06-thao-tac-nhanh-email-reset-mat-khau.md` (thao tác nhanh gửi email / reset mật khẩu — chỉ Admin). ✅
5. `L07-ma-lop-5-ky-tu.md` (mã lớp 5 ký tự thống nhất + dọn seeder/test). ✅
6. `L08-chat-song-song-hien-truoc-gan-co-sau.md` (chat song song + gắn cờ sau response). ✅
7. `L09-cai-dat-chuan-saas-muc-2.md` (Thiết lập tài khoản mức 2: bảo mật + hồ sơ + riêng tư). ✅
8. Backlog còn lại: **L10** (xóa mềm nhóm).
