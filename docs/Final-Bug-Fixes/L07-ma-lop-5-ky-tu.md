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
