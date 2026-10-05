# 01 — Người dùng & xác thực

[← Mục lục](README.md) · Bảng: `users` · `password_reset_tokens` · `password_histories`

---

## 1. `users` — người dùng (sinh viên / giảng viên / admin)

Model: `App\Models\User` · PK: `user_id` (không phải `id`) · dùng `SoftDeletes`.

| Cột | Kiểu dữ liệu | Null | Ràng buộc | Ghi chú |
|---|---|---|---|---|
| `user_id` | bigint unsigned | NN | **PK**, auto_increment | |
| `name` | varchar(255) | NN | | họ tên |
| `email` | varchar(255) | NN | **UQ** `users_email_unique` | dùng để đăng nhập |
| `password` | varchar(255) | NN | | hash bcrypt (`BCRYPT_ROUNDS=12`) |
| `role` | enum('student','lecturer','admin') | NN | default `'student'` | **3 vai trò hệ thống**; "nhóm trưởng" derive từ `groups.leader_id` |
| `isFirstLogin` | tinyint(1) | NN | default `1` | cờ đăng nhập lần đầu (tên camelCase) |
| `is_active` | tinyint(1) | NN | default `1` | `0` = tài khoản bị khóa |
| `is_deleted` | tinyint(1) | NN | default `0` | cờ xóa mềm (song song `deleted_at`) |
| `deleted_at` | timestamp | NULL | | SoftDeletes — xóa mềm |
| `must_change_password` | tinyint(1) | NN | default `0` | buộc đổi MK ở lần đăng nhập kế tiếp |
| `unread_message_count` | int unsigned | NN | default `0` | badge chat (render ngay từ server) |
| `unread_notifications` | int unsigned | NN | default `0` | badge chuông thông báo |
| `flagged_seen_at` | timestamp | NULL | | mốc admin đã mở trang "Bị gắn cờ" |
| `join_requests_seen_at` | timestamp | NULL | | mốc đã xem badge "Yêu cầu" (leader) |
| `invites_seen_at` | timestamp | NULL | | mốc đã xem badge "Lời mời" |
| `remember_token` | varchar(100) | NULL | | "remember me" của Laravel |
| `created_at` | timestamp | NULL | | |
| `updated_at` | timestamp | NULL | | |
| `email_verified_at` | timestamp | NULL | | xác thực email |
| `pending_email` | varchar(255) | NULL | **UQ** `users_pending_email_unique` | email mới đang chờ click xác thực |
| `last_seen_at` | timestamp | NULL | **IX** `users_last_seen_at_index` | online = trong vòng 2 phút (`PresenceService::ONLINE_WINDOW_SECONDS`) |

**Cột đã bị xóa khỏi `users`:**

| Cột đã xóa | Migration | Lý do |
|---|---|---|
| `class_id` (+ FK, index) | `2025_11_10_064450` | quan hệ lớp chuyển sang bảng nối `user_classes` (1 user học nhiều lớp) |
| `isHaveGroup` | `2026_09_13_000001` | bỏ cờ "chỉ 1 nhóm duy nhất"; lọc nhóm động qua `group_members` + `groups.class_id` |

> ⚠️ [`../erd.md`](../erd.md) có nhắc `student_code`, `is_have_group`, `class_id` — các cột này
> **không tồn tại** trong DB thật.

---

## 2. `password_reset_tokens` — token "Quên mật khẩu"

| Cột | Kiểu dữ liệu | Null | Ràng buộc | Ghi chú |
|---|---|---|---|---|
| `email` | varchar(255) | NN | **PK** | Laravel password broker dùng email làm PK |
| `token` | varchar(255) | NN | | token đặt lại MK |
| `created_at` | timestamp | NULL | | |

Bảng này từng **thiếu** (migration `users` gốc chỉ `dropIfExists` ở `down()`), được tạo lại ở
`2026_09_16_000002` để chức năng "Quên mật khẩu" chạy được.

---

## 3. `password_histories` — lịch sử đổi mật khẩu

Model: `App\Models\PasswordHistory`.

| Cột | Kiểu dữ liệu | Null | Ràng buộc | Ghi chú |
|---|---|---|---|---|
| `id` | bigint unsigned | NN | **PK**, auto_increment | |
| `user_id` | bigint unsigned | NN | **FK** → `users.user_id` ON DELETE **CASCADE** | người bị đổi MK |
| `changed_by` | bigint unsigned | NULL | **FK** → `users.user_id` ON DELETE **SET NULL** | NULL = tự đổi; có giá trị = admin đổi hộ |
| `source` | varchar(255) | NN | default `'self'` | `self` \| `admin` … |
| `created_at` | timestamp | NULL | | **IX** `(user_id, created_at)` |
| `updated_at` | timestamp | NULL | | |

Dùng để chặn người dùng đặt mật khẩu **trùng với các mật khẩu cũ**.

---

Xem tiếp: [02 — Môn học & lớp học phần](02-subjects-classes.md) ·
[08 — Khóa ngoại](08-foreign-keys.md) · [09 — Liên kết](09-relations.md)
