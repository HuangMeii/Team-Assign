# 06 — Chat, kiểm duyệt, chặn

[← Mục lục](README.md) · Bảng: `chat_messages` · `direct_messages` · `group_chat_reads` · `blocked_users`

---

## 1. `chat_messages` — tin nhắn chat **nhóm**

Model: `App\Models\ChatMessage` · PK: `id`.

| Cột | Kiểu dữ liệu | Null | Ràng buộc | Ghi chú |
|---|---|---|---|---|
| `id` | bigint unsigned | NN | **PK**, auto_increment | |
| `group_id` | bigint unsigned | NN | **FK** → `groups.group_id` ON DELETE **CASCADE**, **IX** `chat_messages_group_id_index` | nhóm |
| `user_id` | bigint unsigned | NN | **FK** → `users.user_id` ON DELETE **CASCADE** | người gửi (admin gửi thông báo cũng ghi vào đây) |
| `content` | text | NN | | nội dung |
| `type` | varchar(20) | NN | default `'member'` | `member` (thành viên) \| `announcement` (admin thông báo) \| `warning` (admin cảnh báo) |
| `attachment` | varchar(255) | NULL | | đường dẫn ảnh/file đính kèm |
| `is_flagged` | tinyint(1) | NN | default `0`, **IX** `chat_messages_is_flagged_index` | bị gắn cờ kiểm duyệt |
| `flag_reason` | varchar(500) | NULL | | lý do / nhãn (CSV nhiều nhãn) |
| `moderation_score` | double | NULL | | điểm kiểm duyệt |
| `flagged_at` | timestamp | NULL | | thời điểm gắn cờ |
| `created_at` | timestamp | NULL | | |
| `updated_at` | timestamp | NULL | | |

> **Flag-only moderation:** cả text (CSV/PhoBERT) và ảnh (Vision) **chỉ set cờ** `is_flagged` +
> `flag_reason`, **không bao giờ chặn gửi tin**.

---

## 2. `direct_messages` — tin nhắn **1–1**

Model: `App\Models\DirectMessage` · PK: `id`.

| Cột | Kiểu dữ liệu | Null | Ràng buộc | Ghi chú |
|---|---|---|---|---|
| `id` | bigint unsigned | NN | **PK**, auto_increment | |
| `sender_id` | bigint unsigned | NN | **FK** → `users.user_id` ON DELETE **CASCADE** | người gửi |
| `recipient_id` | bigint unsigned | NN | **FK** → `users.user_id` ON DELETE **CASCADE** | người nhận |
| `content` | text | NN | | nội dung |
| `attachment` | varchar(255) | NULL | | ảnh/file đính kèm |
| `is_read` | tinyint(1) | NN | default `0` | người nhận đã đọc hay chưa |
| `is_flagged` | tinyint(1) | NN | default `0`, **IX** `direct_messages_is_flagged_index` | bị gắn cờ |
| `flag_reason` | varchar(500) | NULL | | lý do / nhãn |
| `moderation_score` | double | NULL | | điểm kiểm duyệt |
| `flagged_at` | timestamp | NULL | | |
| `created_at` | timestamp | NULL | | |
| `updated_at` | timestamp | NULL | | |
| `seen_at` | timestamp | NULL | | `NULL` = **đã gửi** (✓ xám) · có giá trị = **đã xem** (✓✓ xanh) |
| | | | **IX** `(sender_id, recipient_id, created_at)` | tải hội thoại 1–1 |
| | | | **IX** `direct_messages_recipient_unread_index (recipient_id, is_read)` | badge chưa đọc theo người gửi |

`seen_at` được set trong `ChatUnreadService::markDirectRead()` (mở trang `chat.show` hoặc AJAX `markRead`).

---

## 3. `group_chat_reads` — mốc "đã đọc" chat nhóm (1 dòng / user / nhóm)

Model: `App\Models\GroupChatRead` · PK: `id`.

| Cột | Kiểu dữ liệu | Null | Ràng buộc | Ghi chú |
|---|---|---|---|---|
| `id` | bigint unsigned | NN | **PK**, auto_increment | |
| `user_id` | bigint unsigned | NN | **FK** → `users.user_id` ON DELETE **CASCADE** | |
| `group_id` | bigint unsigned | NN | **FK** → `groups.group_id` ON DELETE **CASCADE** | |
| `last_read_at` | timestamp | NULL | | mốc đọc cuối cùng của user trong nhóm |
| `created_at` | timestamp | NULL | | |
| `updated_at` | timestamp | NULL | | |
| | | | **UQ** `group_chat_reads_user_id_group_id_unique` `(user_id, group_id)` | |

**Quy tắc:** số tin chưa đọc của nhóm = số `chat_messages` có `created_at > last_read_at`.
Nhóm chưa từng mở ⇒ **chưa có dòng** ⇒ coi như chưa đọc gì.
(Chat 1–1 không dùng bảng này mà dùng `direct_messages.is_read` theo từng tin.)

---

## 4. `blocked_users` — danh sách chặn

Model: `App\Models\BlockedUser` · PK: `id`.

| Cột | Kiểu dữ liệu | Null | Ràng buộc | Ghi chú |
|---|---|---|---|---|
| `id` | bigint unsigned | NN | **PK**, auto_increment | |
| `blocker_id` | bigint unsigned | NN | **FK** → `users.user_id` ON DELETE **CASCADE** | người chặn |
| `blocked_id` | bigint unsigned | NN | **FK** → `users.user_id` ON DELETE **CASCADE** | người bị chặn |
| `created_at` | timestamp | NULL | | |
| `updated_at` | timestamp | NULL | | |
| | | | **UQ** `blocked_users_blocker_id_blocked_id_unique` `(blocker_id, blocked_id)` | |

Kiểm tra nhanh bằng `BlockedUser::isBlocked($blockerId, $blockedId)` /
`User::hasBlocked($userId)`.

---

Xem tiếp: [07 — Thông báo & hạ tầng](07-notifications-infra.md) ·
[08 — Khóa ngoại](08-foreign-keys.md) · [09 — Liên kết](09-relations.md)
