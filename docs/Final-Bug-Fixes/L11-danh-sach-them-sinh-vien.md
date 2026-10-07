# L11 — Modal chon nhieu sinh vien chuan format (admin + giang vien)

> Thu muc: `docs/Final-Bug-Fixes/` — DA TRIEN KHAI (view + JS + controller + test).
> Yeu cau user: danh sach sinh vien xau, format chuan hon, cho phep chon nhieu de them cung luc.

## 1. Hien trang truoc sua

- 2 view (`admin/classes/show`, `lecturer/classes/show`) dung `<select multiple size=6>` tho: option chi `Ten (email)`, khong avatar/nhom/lop, khong dem da chon, khong phan trang (controller load toan bo SV chua trong lop).
- Backend da ho tro mang `student_ids[]` (`syncWithoutDetaching` + bao them/bo qua) nen chi sua view/JS.

## 2. Da sua

- Partial moi `resources/views/components/student-picker-modal.blade.php`: modal Bootstrap — o tim kiem (giu tick khi loc), bang checkbox (avatar chu cai + Ten + Email + badge Nhom/Lop hien tai), checkbox header, badge dem da chon, nut Chon tat ca dang hien / Bo chon / Them da chon; submit giu `student_ids[]` ve route cu.
- `public/js/student-picker.js` (JS dung chung): loc, dem, check-all/indeterminate, chan submit khi chua chon, confirm so luong.
- `lecturer/classes/show.blade.php` + `admin/classes/show.blade.php`: thay form select cu bang nut mo modal + include partial; xoa JS select cu.
- `ClassSectionController::show()` + `lecturerClassesShow()`: `$availableStudents` them `with(['classes.subject','groupsJoined','groupsLed'])` (chong N+1 cho modal).
- Test: cap nhat `AdminClassManagementTest` (modal thay select) + them case modal hien checkbox khi con SV ngoai lop.

## 3. Verify

- `php artisan test LecturerClassTest + AdminClassManagementTest + ClassJoinTest + StudentClassDetailTest`: **42 passed (152 assertions)**.
