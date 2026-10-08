# DB Schema — Cấu trúc cơ sở dữ liệu Team-Assign

> **Nguồn sự thật:** tài liệu này đối chiếu **2 nguồn**:
> 1. Toàn bộ file `database/migrations/*.php` (53 file) — lịch sử tạo/sửa bảng.
> 2. **Schema thật đang chạy** trên MySQL, đọc qua `information_schema`
>    (`php artisan db:show`, `php artisan db:table <tên>`, truy vấn `COLUMNS` / `STATISTICS` / `KEY_COLUMN_USAGE`).
>
> Khi code và DB khác nhau, tài liệu ghi theo **DB thật** và có ghi chú riêng.

| Mục | Giá trị |
|---|---|
| Hệ quản trị | MySQL **8.4.8** (Aiven), engine **InnoDB**, collation `utf8mb4_unicode_ci` |
| Database | `defaultdb` (`DB_DATABASE` trong `.env`) |
| **Tổng số bảng** | **27 bảng** = **26 bảng nghiệp vụ/hạ tầng app** + **1 bảng `migrations`** |
| Số khóa ngoại | **37 FK** |
| Số migration | 53 file (xem [§5](#5-lịch-sử-schema-theo-migration)) |
| Tổng dung lượng | ~1.17 MB |

**Ký hiệu:** `PK` khóa chính · `FK` khóa ngoại · `UQ` unique · `IX` index · `NN` NOT NULL.

---

## Danh mục tài liệu

| File | Nội dung | Bảng |
|---|---|---|
| [`01-users-auth.md`](01-users-auth.md) | Người dùng & xác thực | `users`, `password_reset_tokens`, `password_histories` |
| [`02-subjects-classes.md`](02-subjects-classes.md) | Môn học – Lớp học phần | `subjects`, `class_sections`, `user_classes` |
| [`03-topics.md`](03-topics.md) | Đề tài | `topics`, `topic_requests`, `topic_embeddings` |
| [`04-groups.md`](04-groups.md) | Nhóm | `groups`, `group_members`, `invites`, `join_requests` |
| [`05-class-stream.md`](05-class-stream.md) | Bảng tin lớp học | `class_posts`, `class_post_comments` |
| [`06-chat-moderation.md`](06-chat-moderation.md) | Chat, kiểm duyệt, chặn | `chat_messages`, `direct_messages`, `group_chat_reads`, `blocked_users` |
| [`07-notifications-infra.md`](07-notifications-infra.md) | Thông báo & hạ tầng Laravel | `notifications`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `migrations` |
| [`08-foreign-keys.md`](08-foreign-keys.md) | **Toàn bộ 37 khóa ngoại** (bảng riêng) | — |
| [`09-relations.md`](09-relations.md) | **Liên kết / quan hệ giữa các bảng** + ERD + Eloquent | — |
| [`../erd.md`](../erd.md) | Sơ đồ ERD rút gọn (các bảng chính) | — |

---

## 1. Danh sách 27 bảng theo thứ tự alphabet (đúng như DB)

| # | Bảng | Nhóm (file chi tiết) | Số cột | PK |
|---|---|---|---|---|
| 1 | `blocked_users` | Chat | 5 | `id` |
| 2 | `cache` | Hạ tầng | 3 | `key` |
| 3 | `cache_locks` | Hạ tầng | 3 | `key` |
| 4 | `chat_messages` | Chat | 12 | `id` |
| 5 | `class_post_comments` | Bảng tin | 7 | `comment_id` |
| 6 | `class_posts` | Bảng tin | 14 | `post_id` |
| 7 | `class_sections` | Môn – Lớp | 7 | `class_id` |
| 8 | `direct_messages` | Chat | 13 | `id` |
| 9 | `failed_jobs` | Hạ tầng | 7 | `id` |
| 10 | `group_chat_reads` | Chat | 6 | `id` |
| 11 | `group_members` | Nhóm | 6 | `id` |
| 12 | `groups` | Nhóm | 9 | `group_id` |
| 13 | `invites` | Nhóm | 7 | `id` |
| 14 | `job_batches` | Hạ tầng | 10 | `id` |
| 15 | `jobs` | Hạ tầng | 7 | `id` |
| 16 | `join_requests` | Nhóm | 6 | `id` |
| 17 | `migrations` | Hạ tầng | 3 | `id` |
| 18 | `notifications` | Thông báo | 10 | `notification_id` |
| 19 | `password_histories` | Xác thực | 6 | `id` |
| 20 | `password_reset_tokens` | Xác thực | 3 | `email` |
| 21 | `sessions` | Hạ tầng | 6 | `id` |
| 22 | `subjects` | Môn – Lớp | 7 | `subject_id` |
| 23 | `topic_embeddings` | Đề tài | 8 | `topic_id` |
| 24 | `topic_requests` | Đề tài | 7 | `request_id` |
| 25 | `topics` | Đề tài | 16 | `topic_id` |
| 26 | `user_classes` | Môn – Lớp | 5 | `id` |
| 27 | `users` | Người dùng | 26 | `user_id` |

## 2. Phân nhóm 26 bảng nghiệp vụ

| Nhóm | Bảng | Vai trò |
|---|---|---|
| Người dùng & xác thực | `users`, `password_reset_tokens`, `password_histories` | tài khoản 3 vai trò, quên/đổi mật khẩu, chống dùng lại MK cũ |
| Môn học – Lớp học phần | `subjects`, `class_sections`, `user_classes` | môn → lớp học phần → người tham gia (N–N) |
| Đề tài | `topics`, `topic_requests`, `topic_embeddings` | đề tài/đồ án, yêu cầu nhận đề tài, vector gợi ý ngữ nghĩa |
| Nhóm | `groups`, `group_members`, `invites`, `join_requests` | nhóm sinh viên, thành viên, lời mời, yêu cầu xin vào |
| Bảng tin lớp | `class_posts`, `class_post_comments` | bảng tin kiểu Google Classroom + bình luận |
| Chat & kiểm duyệt | `chat_messages`, `direct_messages`, `group_chat_reads`, `blocked_users` | chat nhóm, chat 1–1, mốc đã đọc, chặn |
| Thông báo | `notifications` | thông báo trong app |
| Hạ tầng Laravel | `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` | session/cache/queue (framework) |

## 3. Cách kiểm tra lại schema (read-only)

```bash
php artisan db:show                     # tổng quan + danh sách bảng
php artisan db:table users              # cột, index, FK của 1 bảng
php artisan db:table groups
php artisan migrate:status              # migration nào đã chạy
```

## 4. Ghi chú & nợ kỹ thuật

1. **`topics.assigned_group_id` không có khóa ngoại** — cột legacy; liên kết thật là `groups.topic_id`
   (`Topics::assignedGroup()` khai báo `hasOne(Groups, 'topic_id')`).
2. **Không tồn tại bảng `classes`** — migration `2025_11_10_064450` vẫn tham chiếu
   `references('id')->on('classes')` trong `down()` ⇒ **rollback sẽ lỗi**; thực tế dùng `class_sections`.
3. **`sessions` đã dùng được** (từ **L09**): migration `2026_10_08_000002` dựng lại bảng đúng chuẩn Laravel
   (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) và `.env` đặt `SESSION_DRIVER=database`
   ⇒ tính năng "Đăng xuất khỏi các phiên khác" trong Thiết lập tài khoản đọc/ghi trực tiếp bảng này.
   (Trước L09: bảng chỉ có `id`, `created_at`, `updated_at` và driver là `file` nên không được dùng.)
4. **`topics.lecturer` là text**, không phải FK ⇒ không truy vấn quan hệ được. Giảng viên phụ trách lớp
   nằm ở `user_classes` + `users.role = 'lecturer'`.
5. **`topic_requests` không có `updated_at`** (chỉ `created_at` NOT NULL default `CURRENT_TIMESTAMP`).
6. **Tên cột camelCase:** `users.isFirstLogin`, `invites.invitedBy` ⇒ phải quote khi viết SQL thuần.
7. **Ràng buộc nghiệp vụ chỉ nằm ở tầng code (không có ở DB):** `groups` không UNIQUE
   `(class_id, group_name)`; `topics.min_members/max_members` không có CHECK; `class_sections.class_code`
   unique nhưng nullable.
8. **Xóa cứng vs xóa mềm:** chỉ `users` có xóa mềm (`is_deleted` + `deleted_at`); các bảng khác xóa cứng.

## 5. Lịch sử schema theo migration

| Migration | Thay đổi |
|---|---|
| `0001_01_01_000000_create_users_table` | tạo `users` (có `class_id`, `isHaveGroup`) |
| `0001_01_01_000001_create_cache_table` | tạo `cache`, `cache_locks` |
| `0001_01_01_000002_create_jobs_table` | tạo `jobs`, `job_batches`, `failed_jobs` |
| `2025_10_22_151331_create_subjects_table` | tạo `subjects` (có `lecturer_id`) |
| `2025_10_22_151338_create_class_sections_table` | tạo `class_sections` |
| `2025_10_22_151346_create_topics_table` | tạo `topics` |
| `2025_10_22_151352_create_groups_table` | tạo `groups` |
| `2025_10_22_151357_create_group_members_table` | tạo `group_members` |
| `2025_10_22_151403_create_invites_table` | tạo `invites` |
| `2025_10_22_151408_create_join_requests_table` | tạo `join_requests` |
| `2025_10_22_151414_create_topic_requests_table` | tạo `topic_requests` |
| `2025_10_22_151421_add_foreign_keys_to_users` | FK `users.class_id` → `class_sections` |
| `2025_10_29_125936_create_user_classes_table` | tạo `user_classes` |
| `2025_10_29_130518_drop_class_id_column` | rỗng (không làm gì) |
| `2025_11_10_064450_remove_class_id_from_users_table` | xóa `users.class_id` + FK |
| `2025_11_10_072144_add_column_class_id_to_topic` | thêm `topics.class_id` + FK |
| `2025_11_15_064634_create_notifications` | tạo `notifications` |
| `2025_11_28_205315_create_chat_messages_table` | tạo `chat_messages` |
| `2026_08_18_164007_create_sessions_table` | tạo `sessions` (tối giản) |
| `2026_08_24_050724_add_role_leader_to_users_table` | thêm `leader` vào enum `users.role` |
| `2026_08_24_050725_add_is_active_to_users_table` | thêm `users.is_active` |
| `2026_08_24_050726_add_class_code_and_is_active_to_class_sections_table` | thêm `class_code`, `is_active` |
| `2026_08_24_050726_add_member_limits_and_deadline_to_topics_table` | thêm `min_members`, `max_members`, `registration_deadline`, `is_active` |
| `2026_08_24_050727_add_expired_status_to_join_requests_table` | enum `join_requests.status` += `Expired` |
| `2026_08_24_050727_add_rejection_reason_to_topic_requests_table` | thêm `rejection_reason`; enum += `Cancelled`, `Expired` |
| `2026_08_24_050727_add_status_to_groups_table` | thêm `groups.status` |
| `2026_08_24_050728_add_expired_status_to_invites_table` | enum `invites.status` += `Expired` |

| `2026_09_13_000001_remove_leader_role_and_is_have_group_from_users_table` | bỏ `leader` khỏi enum; xóa `users.isHaveGroup` |
| `2026_09_13_000002_add_credits_to_subjects_table` | thêm `subjects.credits` |
| `2026_09_13_000003_add_must_change_password_to_users_table` | thêm `users.must_change_password` |
| `2026_09_13_000004_add_is_deleted_to_users_table` | thêm `users.is_deleted`, `users.deleted_at` |
| `2026_09_16_000001_drop_lecturer_id_from_subjects_table` | xóa `subjects.lecturer_id` + FK |
| `2026_09_16_000002_create_password_reset_tokens_table` | tạo `password_reset_tokens` |
| `2026_09_16_000003_add_email_verification_columns_to_users_table` | thêm `email_verified_at`, `pending_email` |
| `2026_09_16_000004_create_password_histories_table` | tạo `password_histories` |
| `2026_09_16_000005_create_direct_messages_table` | tạo `direct_messages` |
| `2026_09_17_000001_add_attachment_to_chat_tables` | thêm `attachment` (chat), `is_read` (DM) |
| `2026_09_17_000002_add_unread_message_count_to_users` | thêm `users.unread_message_count` |
| `2026_09_17_000003_create_blocked_users_table` | tạo `blocked_users` |
| `2026_09_17_213035_add_flag_columns_to_chat_tables` | thêm `is_flagged`, `flag_reason`, `moderation_score`, `flagged_at` |
| `2026_09_17_214500_add_unread_notifications_to_users` | thêm `users.unread_notifications` |
| `2026_09_18_100000_create_group_chat_reads_table` | tạo `group_chat_reads` |
| `2026_09_18_100001_add_unread_index_to_direct_messages_table` | IX `(recipient_id, is_read)` |
| `2026_09_19_100000_add_flagged_seen_at_to_users_table` | thêm `users.flagged_seen_at` |
| `2026_09_19_110000_add_type_to_chat_messages_table` | thêm `chat_messages.type` |
| `2026_09_20_000001_add_badge_seen_timestamps_to_users_table` | thêm `join_requests_seen_at`, `invites_seen_at` |
| `2026_09_20_120000_create_topic_embeddings_table` | tạo `topic_embeddings` |
| `2026_09_22_120000_create_class_posts_table` | tạo `class_posts` |
| `2026_09_22_120100_create_class_post_comments_table` | tạo `class_post_comments` |
| `2026_09_23_000001_add_report_count_to_subjects_table` | thêm `subjects.report_count` |
| `2026_09_23_000002_add_report_type_to_topics_table` | thêm `topics.report_type` + IX `(class_id, report_type)` |
| `2026_09_23_120000_add_last_seen_at_to_users_table` | thêm `users.last_seen_at` + IX |
| `2026_09_23_120100_add_seen_at_to_direct_messages_table` | thêm `direct_messages.seen_at` |
| `2026_10_07_000001_normalize_class_code_5_chars` | **L07** — quy đổi `class_sections.class_code` về đúng 5 ký tự |
| `2026_10_07_000002_add_status_to_user_classes_table` | **L05** — thêm `user_classes.status` (`studying`/`left`) + `left_at` + IX |
| `2026_10_08_000001_add_settings_level2_to_users_and_login_histories` | **L09** — thêm `users.locale/timezone/hide_online/invite_policy/avatar_path` + tạo `login_histories` |
| `2026_10_08_000002_rebuild_sessions_table_for_database_driver` | **L09** — dựng lại `sessions` đúng chuẩn Laravel (6 cột) để dùng `SESSION_DRIVER=database` |
| `2026_10_08_000003_add_deleted_at_to_groups_table` | **L10** — thêm `groups.deleted_at` (SoftDeletes) để Admin/GV xóa mềm nhóm |



