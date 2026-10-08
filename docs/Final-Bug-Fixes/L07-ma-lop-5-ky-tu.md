# L07 — Ma lop thong nhat 5 ky tu (admin + giang vien) + quy doi ma cu

> Thu muc: `docs/Final-Bug-Fixes/` — DA TRIEN KHAI (code + migration + test).
> Yeu cau user: ma lop tu sinh toi da 5 ky tu; sua tat ca ma hien tai qua 5 ky tu ve 5.

## 1. Hien trang truoc sua

- Giang vien (`lecturerStore` → `generateUniqueClassCode()`): dung 5 ky tu, alphabet `ABCDEFGHJKLMNPQRSTUVWXYZ23456789` (bo I,O,0,1), duy nhat qua `do...while exists`.
- Admin (`store` → `generateClassCode($subject)`): format `{subject_code}-NN` (vd `LTHDT001-01`, `php2-01`), dai tuy ma mon → vi pham yeu cau 5 ky tu.
- SV join (`ClassJoinController::joinByCode`): validate `max:50`, so khop chuoi tuyet doi.
- DB that luc audit (8 lop): 4 ma dai (`WEB-K1-2026`, `LTHDT001-01`, `php2-01`, `LTDĐ001-01`), 2 ma NULL, 2 ma dung 5 (`9R9RX`, `X29KB`).
- Factory test (`tests/Support/Fixtures.php::make_class`): tao `CLASS+uniqid()` (dai) → sau khi siet validate, 4 test `ClassJoinTest` do vi ma test dai / ma sai `MA-KHONG-TON-TAI` dai 15 ky tu.

## 2. Da sua

- `app/Http/Controllers/ClassSectionController.php::store()`: admin dung chung `generateUniqueClassCode()`; xoa method `generateClassCode()` cu (`{subject_code}-NN`).
- `app/Http/Controllers/ClassJoinController.php::joinByCode()`: `max:50` → `size:5` + message tieng Viet `Ma lop gom dung 5 ky tu...`.
- Migration `database/migrations/2026_10_07_000001_normalize_class_code_5_chars.php`: voi moi dong NULL/rong hoac `CHAR_LENGTH != 5`, sinh ma 5 ky tu moi (bao duy nhat trong batch + DB), `UPDATE class_sections`, ghi log mapping cu → moi. Chay `php artisan migrate --force`: DONE.
- Ket qua DB sau migrate (8/8 ma 5 ky tu): U565P, UCTCK, ANUSZ, 9R9RX (giu), X29KB (giu), 7LUMT, SQRSF, NGNPD.
- `tests/Support/Fixtures.php::make_class()`: sinh ma test dung 5 ky tu (bao duy nhat).
- `tests/Feature/ClassJoinTest.php`: ma sai `MA-KHONG-TON-TAI` → `ZZZZZ` (dung 5 ky tu, khong ton tai).
- Lien ket `user_classes/groups/topics` theo `class_id` nen khong anh huong; ma cu da phat cho SV het hieu luc → can thong bao lai ma moi.

## 3. Verify

- `php artisan test LecturerClassTest + AdminClassManagementTest + ClassJoinTest + StudentClassDetailTest`: **41 passed (147 assertions)**.

## 4. Bổ sung đợt 2 (2026-10-08) — dọn nốt chỗ còn sinh mã sai

Rà lại toàn bộ `app/ database/ resources/ tests/` bằng grep thấy còn 2 chỗ vi phạm quy tắc 5 ký tự + 1 chỗ liên quan:

- `database/seeders/DatabaseSeeder.php`: tạo lớp mẫu với `class_code = 'WEB-K1-2026'` (10 ký tự) ⇒ đổi thành `'LTWEB'`.
  Ngoài ra seeder còn ghi **2 cột đã bị xóa khỏi schema** (`users.isHaveGroup` — migration `2026_09_13_000001`;
  `subjects.lecturer_id` — migration `2026_09_16_000001`) nên `php artisan db:seed` **không chạy được**; đã bỏ 2 cột này.
- `tests/Feature/LecturerClassTest.php`: dòng dựng lớp "trùng tên" dùng `'class_code' => 'MA1-' . uniqid()` ⇒ đổi `'DUPXY'`.
- `tests/Feature/AdminClassManagementTest.php`: test "admin tạo lớp sinh mã tự động" chỉ khẳng định `not->toBeNull()`
  ⇒ siết thêm `toMatch('/^[A-Z0-9]{5}$/')` đúng đặc tả L07.
- Test mới `tests/Feature/ClassCodeFiveCharsTest.php` (5 case): admin tạo 5 lớp → mã 5 ký tự + duy nhất; giảng viên
  tạo lớp → mã 5 ký tự; SV join mã 4/6 ký tự → báo lỗi tiếng Việt, mã 5 ký tự không tồn tại → "không tìm thấy lớp";
  `$this->seed()` + `make_class()` không sinh mã lệch 5 ký tự + bất biến `CHAR_LENGTH(class_code) <> 5` = 0;
  `generateClassCode()` không còn tồn tại (chống tái phát).
- Verify đợt 2: `ClassCodeFiveCharsTest` **5 passed**; full suite **376 passed / 0 failed** (trước đợt này: 371).
