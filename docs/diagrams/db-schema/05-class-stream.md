# 05 — Bảng tin lớp học

[← Mục lục](README.md) · Bảng: `class_posts` · `class_post_comments`

Bảng tin kiểu **Google Classroom**: mỗi lớp học phần có 1 dòng thời gian gồm **thông báo của
giảng viên phụ trách / admin** và **hoạt động nhóm do hệ thống tự ghi**.

---

## 1. `class_posts` — bài đăng trên bảng tin lớp

Model: `App\Models\ClassPost` · PK: `post_id`.

| Cột | Kiểu dữ liệu | Null | Ràng buộc | Ghi chú |
|---|---|---|---|---|
| `post_id` | bigint unsigned | NN | **PK**, auto_increment | |
| `class_id` | bigint unsigned | NN | **FK** → `class_sections.class_id` ON DELETE **CASCADE** | bài thuộc lớp nào |
| `user_id` | bigint unsigned | NULL | **FK** → `users.user_id` ON DELETE **SET NULL** | **NULL = bài do HỆ THỐNG sinh** (hoạt động nhóm) |
| `type` | varchar(40) | NN | default `'announcement'` | `announcement` (GV/admin đăng) \| `group_created` \| `group_member_added` \| `group_member_removed` \| `group_leader_changed` \| `topic_approved` … |
| `title` | varchar(255) | NULL | | tiêu đề |
| `content` | text | NULL | | nội dung |
| `group_id` | bigint unsigned | NULL | **FK** → `groups.group_id` ON DELETE **SET NULL** | link nhanh tới nhóm liên quan |
| `topic_id` | bigint unsigned | NULL | **FK** → `topics.topic_id` ON DELETE **SET NULL** | link nhanh tới đề tài liên quan |
| `meta` | json | NULL | | dữ liệu phụ: tên nhóm, số thành viên, tên đề tài… |
| `is_pinned` | tinyint(1) | NN | default `0` | ghim lên đầu bảng tin |
| `comments_count` | int unsigned | NN | default `0` | **cache** số bình luận (tăng/giảm khi thêm/xóa) |
| `source_key` | varchar(120) | NULL | **UQ** `class_posts_source_key_unique` | khoá chống trùng khi backfill (vd `backfill:group:12:created`) |
| `created_at` | timestamp | NULL | | |
| `updated_at` | timestamp | NULL | | |
| | | | **IX** `class_posts_feed_index (class_id, is_pinned, created_at)` | truy vấn bảng tin: lọc lớp → ghim trước → mới nhất |

**Ghi chú:**

- Thứ tự hiển thị bảng tin: `ORDER BY is_pinned DESC, created_at DESC`.
- `source_key` cho phép chạy lại `php artisan class-stream:backfill` **nhiều lần mà không nhân đôi** bài.
- `meta` giữ sẵn tên nhóm / số thành viên / tên đề tài để render **không cần query thêm**.

---

## 2. `class_post_comments` — bình luận dưới bài đăng

Model: `App\Models\ClassPostComment` · PK: `comment_id`.

| Cột | Kiểu dữ liệu | Null | Ràng buộc | Ghi chú |
|---|---|---|---|---|
| `comment_id` | bigint unsigned | NN | **PK**, auto_increment | |
| `post_id` | bigint unsigned | NN | **FK** → `class_posts.post_id` ON DELETE **CASCADE** | bài được bình luận |
| `user_id` | bigint unsigned | NN | **FK** → `users.user_id` ON DELETE **CASCADE** | người bình luận |
| `parent_id` | bigint unsigned | NULL | **FK** → `class_post_comments.comment_id` ON DELETE **CASCADE** | **tự tham chiếu** — trả lời 1 cấp |
| `content` | text | NN | | nội dung bình luận |
| `created_at` | timestamp | NULL | | |
| `updated_at` | timestamp | NULL | | |
| | | | **IX** `(post_id, created_at)` | tải bình luận theo bài |

**Ai được bình luận:** sinh viên trong lớp, giảng viên phụ trách lớp và admin.
**Reply:** chỉ **1 cấp** (`parent_id` trỏ tới bình luận gốc, không lồng nhiều tầng).

---

Xem tiếp: [06 — Chat & kiểm duyệt](06-chat-moderation.md) ·
[08 — Khóa ngoại](08-foreign-keys.md) · [09 — Liên kết](09-relations.md)
