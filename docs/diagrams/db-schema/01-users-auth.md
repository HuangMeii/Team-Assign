# 01 — Người dùng & xác thực

[← Mục lục](README.md) · Bảng: `users` · `password_reset_tokens` · `password_histories` · `login_histories`

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
| `locale` | varchar(5) | NULL | default `'vi'` | **L09** — ngôn ngữ giao diện (`vi`/`en`) — middleware `SetLocale` |
| `timezone` | varchar(64) | NULL | | **L09** — múi giờ hiển thị (`Asia/Ho_Chi_Minh`) |
| `hide_online` | tinyint(1) | NN | default `0` | **L09** — ẩn trạng thái online (`PresenceService` trả về "Ẩn") |
| `invite_policy` | varchar(16) | NN | default `'everyone'` | **L09** — ai được mời mình vào nhóm: `everyone`/`classmates`/`none` |
| `avatar_path` | varchar(255) | NULL | | **L09** — đường dẫn ảnh đại diện trên disk `public` (accessor `avatar_url`) |

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

## 4. `login_histories` — lịch sử đăng nhập (L09)

Model: `App\Models\LoginHistory` · tạo ở migration `2026_10_08_000001`.

| Cột | Kiểu dữ liệu | Null | Ràng buộc | Ghi chú |
|---|---|---|---|---|
| `id` | bigint unsigned | NN | **PK**, auto_increment | |
| `user_id` | bigint unsigned | NN | **IX** `(user_id, created_at)` (FK logic tới `users.user_id`) | |
| `ip_address` | varchar(45) | NULL | | IP của lần đăng nhập |
| `user_agent` | text | NULL | | chuỗi UA (rút gọn OS/trình duyệt khi hiển thị) |
| `created_at` | timestamp | NULL | | thời điểm đăng nhập |
| `updated_at` | timestamp | NULL | | |

Ghi bởi `LoginHistoryService::record()` trong `AuthController::login()` (giữ tối đa 30 bản ghi/người, fail-open).
Nếu `ip_address` **chưa từng xuất hiện** ⇒ hệ thống flash cảnh báo "đăng nhập từ thiết bị/IP mới".
Hiển thị ở **Thiết lập tài khoản → Bảo mật**.

---

Xem tiếp: [02 — Môn học & lớp học phần](02-subjects-classes.md) ·
[08 — Khóa ngoại](08-foreign-keys.md) · [09 — Liên kết](09-relations.md)
