# L06 — Thao tac nhanh Gui email / Reset mat khau (Admin thay, GV cam)

> Thu muc: `docs/Final-Bug-Fixes/` — ke hoach sua sau. **TRANG THAI: DA TRIEN KHAI (2026-10-08).**
> Quyet dinh da chot: **5a-B** (chi Admin), **5b=password co dinh**, **5c=don le**.

## 1. Hien trang (da khao sat code)

- Backend da co san trong group `auth` (`routes/web.php:206-207`):
  - `POST students/{id}/send-email` → `students.send-email` → `StudentController@sendEmail` (dong 490-511): validate `subject` + `message`, gui qua `Mail::to()->send(new StudentNotification(...))`.
  - `POST students/{id}/reset-password` → `students.reset-password` → `StudentController@resetPassword` (dong 449-463): hien tai sinh MK ngau nhien 8 ky tu + `must_change_password = true` + flash MK tam.
- View **chua dau day**:
  - `resources/views/students/show.blade.php:183-199` (card "Thao tac nhanh"): ca 2 nut chi la `onclick="alert('Chuc nang ... dang phat trien')"`, khong goi route.
  - `resources/views/students/index.blade.php:224-249`: moi dong chi co Xem / Sua / Xoa, khong co nut email/reset.
- `StudentController@index/create` da loc SV theo lop GV day, nhung 2 route send/reset nam chung group `auth` — ai dang nhap biet URL deu goi duoc (thieu phan quyen admin).
- Tai lieu `2.2.2.08` nhac route `check-email` nhung `web.php` **khong co** route nay.

## 2. Quyet dinh da chot voi user

- **5a-B (cam giang vien):** chi `role=admin` thay va bam duoc 2 nut. GV khong thay nut; goi URL truc tiep → **403**. SV tu phuc vu qua luong `password.*` (quen MK) + doi MK khi login, khong co nut reset ho.
- **5b (MK co dinh):** bo sinh ngau nhien, reset luon thanh `password` (hash luu DB), `must_change_password = true`, hien flash thong bao tren man hinh. Vi du: `Da reset mat khau cho sinh vien X thanh 'password'. SV se phai doi o lan dang nhap tiep theo`.
- **5c (don le):** chi lam cho tung user, khong lam bulk/checkbox chon nhieu.


## 3. Ke hoach sua (khi trien khai)

### 3.1 View Admin — trang chi tiet (`students/show.blade.php`)

- Nut "Gui email": thay `alert()` bang nut mo **modal** (2 field `subject`, `message`) → form `POST {{ route('students.send-email', $student->user_id) }}` + `@csrf`.
- Nut "Reset mat khau": thay `alert()` bang **form confirm** (`onsubmit="return confirm('Reset mat khau ve password?')"`) → `POST {{ route('students.reset-password', $student->user_id) }}` + `@csrf`.
- Hien thi flash `success` chua MK `password` tu session (controller tra ve).
- Ca 2 nut chi render khi `Auth::user()->role === 'admin'` (dieu kien `@if` trong blade).

### 3.2 View Admin — danh sach (`students/index.blade.php`)

- Them dropdown "Thao tac" moi dong (khi admin): Xem / Sua / Gui email (mo modal chung, truyen id qua data-attribute) / Reset mat khau (form confirm inline) / Xoa.
- Modal gui email dat 1 lan o cuoi trang, JS gan `action` theo nut duoc bam.

### 3.3 Controller (`StudentController.php`)

- `resetPassword($id)`: doi thanh `Hash::make('password')` + `must_change_password = true`; xoa `generateTempPassword()` neu khong con dung; flash: `Da reset mat khau cho sinh vien {name} thanh 'password'. SV se phai doi o lan dang nhap tiep theo`.
- Phan quyen ca 2 ham: dau ham them `abort_unless(Auth::user()->role === 'admin', 403);` (can `use Illuminate\Support\Facades\Auth;` — da co).
- Them thieu `use Illuminate\Support\Facades\Log;` (hien `sendEmail()` goi `Log::error` nhung chua import → catch se vang `Class not found`).
- `sendEmail()` giu nguyen logic `StudentNotification`; kiem tra class `App\Mail\StudentNotification` + cau hinh `.env` `MAIL_*` ton tai.

### 3.4 Luong SV sau reset

- Dam bao login kiem tra `must_change_password` va bat doi MK truoc khi vao dashboard (lien quan ChangePasswordTest — khong pha vo test hien tai).
- Khong them nut reset ho cho vai tro student/lecturer.

### 3.5 Docs + Test

- Sua `docs/business-flows/quy-trinh-nghiep-vu/2.2.2.08-quan-ly-sinh-vien-trong-lop.md`: bo `check-email` (route khong ton tai) hoac bo sung route neu muon giu.
- Cap nhat test-case `#5` (quan ly SV trong lop) + `docs/FEATURE_STATUS.md` muc 5.
- Test moi `tests/Feature/StudentQuickActionsTest.php`:
  1. Admin reset → `Hash::check('password', $student->password)` true + `must_change_password = 1` + session co `success`.
  2. Admin gui email → `Mail::fake()` nhan dung `subject/message` den email SV.
  3. GV goi truc tiep 2 route → 403; GV khong thay nut (view test).
  4. SV login sau reset → bi buoc doi MK.
- Lenh kiem chung: `php artisan test tests/Feature/StudentQuickActionsTest.php` + click tay vai tro admin.

## 4. Rui ro / ghi chu

- `password` la MK yeu, de doan — bat buoc giu co `must_change_password` + verify luong login chan den khi doi xong.
- Route hien chi can `auth` — khi them `abort_unless admin` phai dam bao khong pha cac test cu goi route nay voi user non-admin (neu co, cap nhat test).
- Gui mail phu thuoc `MAIL_*`; moi truong testing dung `MAIL_MAILER=log`.

## 5. Ket qua trien khai (2026-10-08)

### 5.1 File da sua / tao

| File | Thay doi |
|------|----------|
| `app/Http/Controllers/StudentController.php` | `resetPassword()`: `abort_unless(admin, 403)` + `Hash::make('password')` (bo `generateTempPassword()`); `sendEmail()`: `abort_unless(admin, 403)`; them `use Illuminate\Support\Facades\Log;` |
| `resources/views/students/show.blade.php` | 2 nut that (modal gui email + form confirm reset), boc `@if(Auth::user()->role === 'admin')`; them alert `error` |
| `resources/views/students/index.blade.php` | Cot "Thao tac" moi dong them nut "Gui email" (modal dung chung, gan `action` bang JS theo `data-action`) + nut "Reset mat khau" (form confirm), chi hien voi Admin |
| `app/Mail/StudentNotification.php` | **Bug that**: `public string $subject;` trung ten thuoc tinh `public $subject` cua `Illuminate\Mail\Mailable` ⇒ fatal error khi khoi tao (gui email chua tung chay duoc). Doi sang `$mailSubject`/`$mailMessage` + truyen `subject`/`message` qua `Content::with([...])` |
| `app/Http/Controllers/AuthController.php` | Muc 3.4: `login()` chan tai khoan `must_change_password = true` ⇒ redirect `users.profile.password` + flash `warning` |
| `app/Http/Controllers/UserController.php` | `changePassword()` go co `must_change_password` sau khi doi thanh cong (ca nhanh thuong va nhanh reset-token) |
| `tests/Feature/StudentQuickActionsTest.php` | Test moi (7 case) |
| `docs/test-cases/data/02-...php` + `docs/test-cases/README.md` | Them `TC-ADMIN-29`, cap nhat so luong test case |
| `docs/FEATURE_STATUS.md` | Muc #5 + ghi chu sua doi L06 |

### 5.2 Kiem chung

```powershell
php artisan test tests/Feature/StudentQuickActionsTest.php   # 7 passed
php artisan test                                             # 371 passed / 0 failed
php artisan testcases:export --md
```

### 5.3 Khac biet so voi ke hoach ban dau

- Muc 3.5 noi `check-email` "khong ton tai" la **thong tin cu sai**: route van co
  (`GET /check-student-email` → `students.check-email` → `StudentController::checkEmail`), khong phai sua gi.
- Muc 3.4 ("dam bao login kiem tra `must_change_password`") truoc day **chua he co trong code** — da bo sung
  tai `AuthController::login()` + go co tai `UserController::changePassword()`.
