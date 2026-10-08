# 02 — Môn học & Lớp học phần

[← Mục lục](README.md) · Bảng: `subjects` · `class_sections` · `user_classes`

Luồng dữ liệu: **`subjects`** (môn) → **`class_sections`** (lớp học phần của môn) →
**`user_classes`** (ai tham gia lớp) → **`topics`** / **`groups`** (đề tài & nhóm trong lớp).

---

## 1. `subjects` — môn học

Model: `App\Models\Subject` · PK: `subject_id`.

| Cột | Kiểu dữ liệu | Null | Ràng buộc | Ghi chú |
|---|---|---|---|---|
| `subject_id` | bigint unsigned | NN | **PK**, auto_increment | |
| `subject_code` | varchar(255) | NN | **UQ** `subjects_subject_code_unique` | mã môn |
| `subject_name` | varchar(255) | NN | | tên môn |
| `credits` | tinyint unsigned | NN | default `3` | số tín chỉ |
| `report_count` | tinyint unsigned | NN | default `1` | `1` = chỉ cuối kì · `2` = giữa kì + cuối kì |
| `created_at` | timestamp | NULL | | |
| `updated_at` | timestamp | NULL | | |

**Cột đã bị xóa:** `lecturer_id` (bigint unsigned, FK → `users.user_id` ON DELETE SET NULL) —
xóa ở migration `2026_09_16_000001`. Lý do: phân công giảng viên chuyển hẳn xuống cấp **lớp học phần**
(`user_classes` + `users.role = 'lecturer'`), vì 1 môn có thể có nhiều lớp do nhiều GV phụ trách.

Quan hệ code: `Subject::classes()` (hasMany `class_sections.subject_id`),
`Subject::topics()` (hasMany `topics.subject_id`).

---

## 2. `class_sections` — lớp học phần

Model: `App\Models\ClassSection` · PK: `class_id`.

| Cột | Kiểu dữ liệu | Null | Ràng buộc | Ghi chú |
|---|---|---|---|---|
| `class_id` | bigint unsigned | NN | **PK**, auto_increment | |
| `subject_id` | bigint unsigned | NN | **FK** → `subjects.subject_id` ON DELETE **CASCADE** | |
| `class_name` | varchar(255) | NN | | vd `SE104.N11` |
| `class_code` | char(5) | NN | **UQ** `class_sections_class_code_unique` | mã để sinh viên **tự tham gia lớp** — **đúng 5 ký tự** (siết ở DB từ migration `2026_10_08_000004`) |
| `is_active` | tinyint(1) | NN | default `1` | `0` = lớp bị khóa |
| `created_at` | timestamp | NULL | | |
| `updated_at` | timestamp | NULL | | |

Quan hệ code: `ClassSection::subject()` (belongsTo), `groups()`, `topics()`, `posts()` (hasMany),
`users()` / `lecturers()` / `students()` (belongsToMany qua `user_classes`).

> ⚠️ Không có cột `lecturer_id` trong bảng này (tài liệu ERD cũ ghi sai). Giảng viên phụ trách lớp
> là user có `role = 'lecturer'` xuất hiện trong `user_classes` của lớp đó.

---

## 3. `user_classes` — bảng nối User ↔ Lớp học phần (N–N)

Model: `App\Models\user_class` · PK: `id`.

| Cột | Kiểu dữ liệu | Null | Ràng buộc | Ghi chú |
|---|---|---|---|---|
| `id` | bigint unsigned | NN | **PK**, auto_increment | |
| `user_id` | bigint unsigned | NN | **FK** → `users.user_id` ON DELETE **CASCADE** | |
| `class_id` | bigint unsigned | NN | **FK** → `class_sections.class_id` ON DELETE **CASCADE** | |
| `status` | enum('studying','left') | NN | DEFAULT `'studying'` · index `(class_id, status)` | **L05**: trạng thái từng (user, lớp) — `left` = đã rời lớp (xóa mềm) |
| `left_at` | timestamp | NULL | | **L05**: mốc rời lớp (`NULL` khi đang học) |
| `created_at` | timestamp | NULL | | mốc tham gia lớp |
| `updated_at` | timestamp | NULL | | |
| | | | **UQ** `user_classes_user_id_class_id_unique` `(user_id, class_id)` | 1 user không tham gia 1 lớp 2 lần |

**Đặc điểm:**

- Bảng này lưu **cả sinh viên và giảng viên** của lớp; phân biệt bằng `users.role`.
- **Không có cột `role`** riêng trong `user_classes`.
- Đây là nguồn xác định "sinh viên thuộc lớp nào" (thay cho `users.class_id` đã xóa).
- **L05 — xóa mềm**: "xóa sinh viên khỏi lớp" chỉ `UPDATE status='left', left_at=now()`, KHÔNG xóa dòng;
  "thêm lại" khôi phục `status='studying', left_at=NULL`. Vì UQ `(user_id, class_id)` vẫn giữ nên mọi thao tác
  thêm phải là *khôi phục* chứ không `attach()` lại.
  Đọc dữ liệu: `ClassSection::students()/users()` và `User::classes()` **chỉ lấy `studying`**
  (luồng sinh viên); `ClassSection::allStudents()` / `User::allClasses()` lấy tất cả (Admin/GV + thao tác quản lý).

---

Xem tiếp: [03 — Đề tài](03-topics.md) · [08 — Khóa ngoại](08-foreign-keys.md) ·
[09 — Liên kết](09-relations.md)
