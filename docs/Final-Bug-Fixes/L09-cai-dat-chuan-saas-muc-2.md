# L09 — Cai dat chuan SaaS (Muc 2): bao mat + ho so mo rong + rieng tu

> Thu muc: `docs/Final-Bug-Fixes/` — **TRANG THAI: DA TRIEN KHAI (2026-10-08).**
> Chot: **L09 lam MUC 2** (bao mat, ho so mo rong, chat/quyen rieng tu).

## 1. Hien trang (da khao sat code)

- Trang "Thiet lap tai khoan" (`resources/views/users/profile-info.blade.php` + `profile-admin`): chi co that 2 tab — Thong tin chung (doi ten + doi email qua `users.profile.update`, xac thuc `pending_email` signed 60 phut) va Doi mat khau (`users.profile.password`, lich su `password_histories`).
- Card "Goi y cai dat tai khoan" (profile-info.blade.php:82-93) liet ke 4 muc **text chet**, khong link/backend.
- Ha tang san co: `notifications` + badge, `blocked_users` + `BlockUserController`, `last_seen_at` (online 2 phut), `remember_token`, `SESSION_DRIVER=file` (bang `sessions` ghi "khong duoc dung"), `APP_LOCALE=en` nhung UI tieng Viet.

## 2. Pham vi L09 Muc 2 (3 nhom)

### 2.1 Bao mat

- Lich su dang nhap (`login_histories`: user_id, ip, user_agent, at) + trang liet ke + canh bao login la (IP moi).
- Phien active + "Dang xuat phien khac" (`Auth::logoutOtherDevices`, can `SESSION_DRIVER=database` de dung bang `sessions` that).
- Thu hoi remember token (vo hieu hoa "ghi nho dang nhap" tren moi thiet bi).

### 2.2 Ho so mo rong

- Avatar upload (`storage/app/public/avatars`, validate image 2MB, accessor `avatar_url`, fallback chu cai dau khi chua co).
- Toggle ngon ngu `vi/en` (`users.locale`, middleware `SetLocale` ap dung moi request; mac dinh `vi` cho khop UI hien tai).
- Mui gio hien thi (`users.timezone`, dung `Carbon::setTimezone` khi render thoi gian).

### 2.3 Chat / quyen rieng tu

- UI quan ly chan trong Cai dat (danh sach `blocked_users` + bo chan, tai dung `BlockUserController`).
- Toggle an/hien trang thai online (`users.hide_online` — `PresenceService` ton trong, hien "An" thay vi cham xanh/xam).
- Ai duoc moi minh vao nhom (`users.invite_policy`: everyone/classmates/none — `InvitationService` kiem tra truoc khi tao invite).

## 3. Ke hoach file sua (khi trien khai)

- Migration: `login_histories`; `users.locale/timezone/hide_online/invite_policy/avatar_path`.
- `SESSION_DRIVER` → `database` + dung bang `sessions` that (bo ghi chu "khong duoc dung").
- Controller: `SettingsController` moi (5 tab: Thong tin | Mat khau | Bao mat | Ho so | Rieng tu) hoac mo rong `UserController@editProfile`.
- Middleware `SetLocale`; accessor avatar; `PresenceService` + `InvitationService` ton trong pref.
- Test moi `tests/Feature/SettingsLevel2Test.php`: avatar upload + fallback; locale vi/en; block UI (chan/bo chan); invite policy (chan invite khi none); session revoke; login history ghi + hien.
- Docs: mo rong use-case `2.2.2.04`, `FEATURE_STATUS` muc moi, test-case `TC-AUTH` bo sung.
- Verify: `php artisan test tests/Feature/SettingsLevel2Test.php` + click tay 3 vai tro.

## 4. Rui ro / ghi chu

- Doi `SESSION_DRIVER` anh huong moi phien dang nhap hien tai (user phai login lai) — nen thong bao truoc.
- Upload avatar can `php artisan storage:link` + don file cu khi doi anh.
- `invite_policy=none` phai co ngoai le cho admin/lecturer can thiet (neu khong nhom khong the moi duoc ai).

## 5. Ket qua trien khai (2026-10-08)

### 5.1 Migration (da chay `php artisan migrate --force`)

| File | Noi dung |
|------|----------|
| `2026_10_08_000001_add_settings_level2_to_users_and_login_histories` | `users.locale/timezone/hide_online/invite_policy/avatar_path` + bang `login_histories` |
| `2026_10_08_000002_rebuild_sessions_table_for_database_driver` | Dung lai bang `sessions` DUNG chuan Laravel (`id, user_id, ip_address, user_agent, payload, last_activity`) — bang cu chi co `id + timestamps` va chua tung dung |

Đổi `.env`: `SESSION_DRIVER=file` → `SESSION_DRIVER=database` (⚠️ mọi phiên đang đăng nhập sẽ phải login lại).

### 5.2 Code

| File | Noi dung |
|------|----------|
| `app/Models/LoginHistory.php` **(moi)** | Model + `deviceLabel()`/`browserLabel()` (suy từ user_agent) |
| `app/Services/LoginHistoryService.php` **(moi)** | `record()` (tra ve `wasNewIp`, giu toi da 30 ban ghi, fail-open), `latestFor()`, `countFor()` |
| `app/Models/User.php` | `fillable`/`cast hide_online`, hằng số `INVITE_*` + `LOCALES`, `invitePolicyLabel()`, relation `loginHistories()`, accessor `avatar_url` |
| `app/Http/Middleware/SetLocale.php` **(moi)** | Ap dung `users.locale` (`App::setLocale`) + `users.timezone` (`date_default_timezone_set`) moi request; gia tri sai ⇒ giu mac dinh |
| `app/Http/Controllers/SettingsController.php` **(moi)** | 3 tab GET (`security/profile/privacy`) + 5 POST (`updateProfile`, `destroyAvatar`, `updatePrivacy`, `revokeOtherSessions`, `revokeRememberToken`, `unblockUser`) |
| `routes/web.php` | 9 route moi duoi prefix `/settings/*`, middleware `auth` |
| `app/Http/Controllers/AuthController.php` | Sau khi dang nhap: ghi lich su + flash canh bao khi IP moi |
| `app/Services/PresenceService.php` | `isOnline()` tra ve false va `label()` tra ve "Ẩn" khi `hide_online` |
| `app/Services/InvitationService.php` | `checkInvitePolicy()`: `none` chan moi loi moi; `classmates` chi cho nguoi DANG HOC cung lop cua nhom |
| `resources/views/users/settings/*` **(moi)** | `_tabs.blade.php` (5 tab dung chung) + `security/profile/privacy.blade.php` (theo vai tro: `layouts.user` cho SV, `layouts.app` cho GV/Admin) |
| `resources/views/users/profile*.blade.php` | 4 trang cu dung chung thanh 5 tab; card "Goi y cai dat tai khoan" (text chet) → **5 link that** |
| `tests/Feature/SettingsLevel2Test.php` **(moi)** | 11 case |

Quyết định thiết kế đáng chú ý:
- **"Đăng xuất khỏi các phiên khác"** xoá trực tiếp các dòng `sessions` của chính mình (`id != session hiện tại`)
  thay vì bật `AuthenticateSession` middleware — tránh rủi ro đăng xuất hàng loạt khi thiếu `password_hash_web`
  trong phiên cũ (và vẫn kiểm thử được bằng cách chèn dòng `sessions` trong test).
- **`invite_policy=none`** là lựa chọn của CHÍNH người dùng đó nên mặc định `everyone` không ảnh hưởng ai khác;
  admin/giảng viên thêm sinh viên vào lớp bằng luồng `admin.classes.students.*` (không qua lời mời) nên không bị chặn.
- **Ngôn ngữ**: hạ tầng + tuỳ chọn + middleware đã xong (tôn trọng `vi/en`); dịch toàn bộ chuỗi giao diện sang `en`
  vẫn là việc riêng (UI hiện tại là tiếng Việt).

### 5.3 Kiem chung

```powershell
php artisan test tests/Feature/SettingsLevel2Test.php   # 11 passed
php artisan test                                        # 392 passed / 0 failed
```

Test bao phủ: upload/xoá avatar + `avatar_url`; locale `en` được middleware áp dụng; chặn locale/timezone sai;
`hide_online` ⇒ `PresenceService` báo "Ẩn"; danh sách chặn + bỏ chặn; `invite_policy=none` chặn, `classmates` chỉ cho
bạn cùng lớp; login ghi lịch sử + cảnh báo IP mới (lần 2 cùng IP không cảnh báo); thu hồi phiên khác chỉ xoá phiên
của chính mình; thu hồi `remember_token`; 3 trang mới render cho cả 3 vai trò.
