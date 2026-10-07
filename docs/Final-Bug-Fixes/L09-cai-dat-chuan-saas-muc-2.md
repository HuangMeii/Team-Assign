# L09 — Cai dat chuan SaaS (Muc 2): bao mat + ho so mo rong + rieng tu

> Thu muc: `docs/Final-Bug-Fixes/` — ke hoach sua sau (chua trien khai code).
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
