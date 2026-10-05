# 08 — Toàn bộ khóa ngoại (Foreign Keys)

[← Mục lục](README.md) · File riêng theo yêu cầu · **Tổng: 37 FK**

Dữ liệu đọc trực tiếp từ `information_schema.KEY_COLUMN_USAGE` +
`information_schema.REFERENTIAL_CONSTRAINTS` trên DB `defaultdb`.
**Mọi FK đều có `ON UPDATE = NO ACTION`** ⇒ cột `On Update` được lược bỏ trong bảng dưới.

## 1. Bảng danh sách 37 khóa ngoại

| # | Bảng (con) | Cột | Tên constraint | → Bảng (cha) | Cột cha | ON DELETE |
|---|---|---|---|---|---|---|
| 1 | `class_sections` | `subject_id` | `class_sections_subject_id_foreign` | `subjects` | `subject_id` | CASCADE |
| 2 | `topics` | `subject_id` | `topics_subject_id_foreign` | `subjects` | `subject_id` | SET NULL |
| 3 | `topics` | `class_id` | `topics_class_id_foreign` | `class_sections` | `class_id` | CASCADE |
| 4 | `groups` | `leader_id` | `groups_leader_id_foreign` | `users` | `user_id` | CASCADE |
| 5 | `groups` | `topic_id` | `groups_topic_id_foreign` | `topics` | `topic_id` | SET NULL |
| 6 | `groups` | `class_id` | `groups_class_id_foreign` | `class_sections` | `class_id` | SET NULL |
| 7 | `group_members` | `group_id` | `group_members_group_id_foreign` | `groups` | `group_id` | CASCADE |
| 8 | `group_members` | `user_id` | `group_members_user_id_foreign` | `users` | `user_id` | CASCADE |
| 9 | `invites` | `group_id` | `invites_group_id_foreign` | `groups` | `group_id` | CASCADE |
| 10 | `invites` | `invitedBy` | `invites_invitedby_foreign` | `users` | `user_id` | CASCADE |
| 11 | `invites` | `member_id` | `invites_member_id_foreign` | `users` | `user_id` | CASCADE |
| 12 | `join_requests` | `group_id` | `join_requests_group_id_foreign` | `groups` | `group_id` | CASCADE |
| 13 | `join_requests` | `member_id` | `join_requests_member_id_foreign` | `users` | `user_id` | CASCADE |
| 14 | `topic_requests` | `topic_id` | `topic_requests_topic_id_foreign` | `topics` | `topic_id` | CASCADE |
| 15 | `topic_requests` | `group_id` | `topic_requests_group_id_foreign` | `groups` | `group_id` | CASCADE |
| 16 | `topic_requests` | `created_by` | `topic_requests_created_by_foreign` | `users` | `user_id` | CASCADE |
| 17 | `topic_embeddings` | `topic_id` | `topic_embeddings_topic_id_foreign` | `topics` | `topic_id` | CASCADE |
| 18 | `user_classes` | `user_id` | `user_classes_user_id_foreign` | `users` | `user_id` | CASCADE |
| 19 | `user_classes` | `class_id` | `user_classes_class_id_foreign` | `class_sections` | `class_id` | CASCADE |
| 20 | `notifications` | `user_id` | `notifications_user_id_foreign` | `users` | `user_id` | CASCADE |
| 21 | `chat_messages` | `group_id` | `chat_messages_group_id_foreign` | `groups` | `group_id` | CASCADE |
| 22 | `chat_messages` | `user_id` | `chat_messages_user_id_foreign` | `users` | `user_id` | CASCADE |
| 23 | `direct_messages` | `sender_id` | `direct_messages_sender_id_foreign` | `users` | `user_id` | CASCADE |
| 24 | `direct_messages` | `recipient_id` | `direct_messages_recipient_id_foreign` | `users` | `user_id` | CASCADE |
| 25 | `group_chat_reads` | `user_id` | `group_chat_reads_user_id_foreign` | `users` | `user_id` | CASCADE |
| 26 | `group_chat_reads` | `group_id` | `group_chat_reads_group_id_foreign` | `groups` | `group_id` | CASCADE |
| 27 | `blocked_users` | `blocker_id` | `blocked_users_blocker_id_foreign` | `users` | `user_id` | CASCADE |
| 28 | `blocked_users` | `blocked_id` | `blocked_users_blocked_id_foreign` | `users` | `user_id` | CASCADE |
| 29 | `password_histories` | `user_id` | `password_histories_user_id_foreign` | `users` | `user_id` | CASCADE |
| 30 | `password_histories` | `changed_by` | `password_histories_changed_by_foreign` | `users` | `user_id` | SET NULL |
| 31 | `class_posts` | `class_id` | `class_posts_class_id_foreign` | `class_sections` | `class_id` | CASCADE |
| 32 | `class_posts` | `user_id` | `class_posts_user_id_foreign` | `users` | `user_id` | SET NULL |
| 33 | `class_posts` | `group_id` | `class_posts_group_id_foreign` | `groups` | `group_id` | SET NULL |
| 34 | `class_posts` | `topic_id` | `class_posts_topic_id_foreign` | `topics` | `topic_id` | SET NULL |
| 35 | `class_post_comments` | `post_id` | `class_post_comments_post_id_foreign` | `class_posts` | `post_id` | CASCADE |
| 36 | `class_post_comments` | `user_id` | `class_post_comments_user_id_foreign` | `users` | `user_id` | CASCADE |
| 37 | `class_post_comments` | `parent_id` | `class_post_comments_parent_id_foreign` | `class_post_comments` (**tự tham chiếu**) | `comment_id` | CASCADE |

## 2. Thống kê theo bảng được tham chiếu (bảng cha)

| Bảng cha | Số FK trỏ tới | Các bảng con |
|---|---|---|
| `users` | **18** | `groups`(leader_id), `group_members`, `invites`(×2), `join_requests`, `topic_requests`(created_by), `user_classes`, `notifications`, `chat_messages`, `direct_messages`(×2), `group_chat_reads`, `blocked_users`(×2), `password_histories`(×2), `class_posts`, `class_post_comments` |
| `groups` | **7** | `group_members`, `invites`, `join_requests`, `topic_requests`, `chat_messages`, `group_chat_reads`, `class_posts` |
| `topics` | **4** | `groups`, `topic_requests`, `topic_embeddings`, `class_posts` |
| `class_sections` | **4** | `topics`, `groups`, `user_classes`, `class_posts` |
| `subjects` | **2** | `class_sections`, `topics` |
| `class_posts` | **1** | `class_post_comments` |
| `class_post_comments` | **1** | `class_post_comments` (self) |

## 3. Quy tắc xóa dữ liệu (suy ra từ ON DELETE)

| Hành động xóa | Hệ quả |
|---|---|
| Xóa **`users`** | Xóa cascade gần như toàn bộ dữ liệu liên quan (nhóm do user làm leader, thành viên, lời mời, yêu cầu, chat, thông báo…). `class_posts.user_id` và `password_histories.changed_by` chuyển **NULL** (giữ lại bài/bản ghi). |
| Xóa **`groups`** | Xóa cascade `group_members`, `invites`, `join_requests`, `topic_requests`, `chat_messages`, `group_chat_reads`; `groups` là FK SET NULL ở `class_posts.group_id`. |
| Xóa **`topics`** | Xóa cascade `topic_requests`, `topic_embeddings`; `groups.topic_id` và `class_posts.topic_id` chuyển **NULL**. |
| Xóa **`class_sections`** | Xóa cascade `topics`, `user_classes`, `class_posts`; `groups.class_id` chuyển **NULL**. |
| Xóa **`subjects`** | Xóa cascade `class_sections` (⇒ kéo theo lớp); `topics.subject_id` chuyển **NULL**. |
| Xóa **`class_posts`** | Xóa cascade `class_post_comments` của bài đó. |

> ⚠️ Vì xóa `users` lan rộng như vậy, hệ thống dùng **xóa mềm** cho `users`
> (`is_deleted` + `deleted_at`) thay vì `DELETE`.

## 4. Liên kết **không** có FK (liên kết "mềm", chỉ ràng buộc ở tầng code)

| Cột | Ý nghĩa | Ghi chú |
|---|---|---|
| `topics.assigned_group_id` | nhóm được gán đề tài | **legacy, không có FK**; liên kết thật là `groups.topic_id` |
| `topics.lecturer` | tên giảng viên (text) | không phải FK ⇒ không join được sang `users` |
| `class_posts.type` / `chat_messages.type` | loại bài / loại tin | enum dạng chuỗi, không có bảng danh mục |
| `notifications.type` | loại thông báo | chuỗi tự do (`topic_request`, `join_request`…) |
| `users.role`, `group_members.role` | vai trò | enum, không có bảng `roles` riêng |

## 5. Cách kiểm tra lại danh sách FK

```sql
SELECT k.TABLE_NAME, k.COLUMN_NAME, k.CONSTRAINT_NAME,
       k.REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME, r.DELETE_RULE
FROM information_schema.KEY_COLUMN_USAGE k
JOIN information_schema.REFERENTIAL_CONSTRAINTS r
  ON r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
 AND r.CONSTRAINT_SCHEMA = k.TABLE_SCHEMA
WHERE k.TABLE_SCHEMA = DATABASE()
ORDER BY k.TABLE_NAME, k.COLUMN_NAME;
```

Hoặc dùng Laravel: `php artisan db:table <tên_bảng>` (in cả Index và Foreign Key).

---

Xem tiếp: [09 — Liên kết / quan hệ](09-relations.md) · [Mục lục](README.md)

