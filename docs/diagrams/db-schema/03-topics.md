# 03 — Đề tài

[← Mục lục](README.md) · Bảng: `topics` · `topic_requests` · `topic_embeddings`

---

## 1. `topics` — đề tài / đồ án

Model: `App\Models\Topics` · PK: `topic_id`.

| Cột | Kiểu dữ liệu | Null | Ràng buộc | Ghi chú |
|---|---|---|---|---|
| `topic_id` | bigint unsigned | NN | **PK**, auto_increment | |
| `name` | varchar(255) | NN | | tên đề tài |
| `description` | longtext | NULL | | mô tả |
| `lecturer` | varchar(255) | NULL | | **chỉ là tên GV dạng text — KHÔNG phải FK** |
| `goal` | longtext | NULL | | mục tiêu |
| `requirements` | longtext | NULL | | yêu cầu |
| `min_members` | int unsigned | NN | default `1` | số thành viên tối thiểu |
| `max_members` | int unsigned | NN | default `5` | số thành viên tối đa |
| `registration_deadline` | timestamp | NULL | | hạn đăng ký |
| `is_active` | tinyint(1) | NN | default `1` | `0` = đề tài đóng |
| `report_type` | varchar(10) | NN | default `'final'` | `final` (cuối kì) \| `midterm` (giữa kì) |
| `assigned_group_id` | bigint unsigned | NULL | | ⚠ **KHÔNG có FK** — cột legacy, không dùng |
| `subject_id` | bigint unsigned | NULL | **FK** → `subjects.subject_id` ON DELETE **SET NULL** | |
| `class_id` | bigint unsigned | NULL | **FK** → `class_sections.class_id` ON DELETE **CASCADE** | |
| `created_at` | timestamp | NULL | | |
| `updated_at` | timestamp | NULL | | |
| | | | **IX** `topics_class_report_idx (class_id, report_type)` | lọc đề tài theo lớp + loại báo cáo |

**Lưu ý quan trọng:**

- Đề tài thuộc **lớp học phần** (`class_id`) — gợi ý đề tài theo ngữ nghĩa chỉ tìm trong lớp của sinh viên.
- `report_type` chỉ có `midterm` khi môn học có `subjects.report_count = 2`.
- Liên kết "đề tài đã được nhóm nhận" nằm ở **`groups.topic_id`** (không phải `topics.assigned_group_id`).

Quan hệ code: `Topics::subject()`, `class()` / `class_section()` (belongsTo),
`assignedGroup()` (hasOne `groups.topic_id`), `topic_requests()`, `embedding()` (hasOne),
scope `byClass()`.

---

## 2. `topic_requests` — yêu cầu nhận đề tài của nhóm

Model: `App\Models\Topic_requests` · PK: `request_id`.

| Cột | Kiểu dữ liệu | Null | Ràng buộc | Ghi chú |
|---|---|---|---|---|
| `request_id` | bigint unsigned | NN | **PK**, auto_increment | |
| `topic_id` | bigint unsigned | NN | **FK** → `topics.topic_id` ON DELETE **CASCADE** | |
| `group_id` | bigint unsigned | NN | **FK** → `groups.group_id` ON DELETE **CASCADE** | |
| `status` | enum('Pending','Accepted','Rejected','Cancelled','Expired') | NN | default `'Pending'` | |
| `rejection_reason` | text | NULL | | lý do từ chối (GV nhập) |
| `created_by` | bigint unsigned | NN | **FK** → `users.user_id` ON DELETE **CASCADE** | người gửi (trưởng nhóm) |
| `created_at` | timestamp | NN | default `CURRENT_TIMESTAMP` | ⚠ **bảng này KHÔNG có `updated_at`** |
| | | | **UQ** `topic_requests_topic_id_group_id_unique` `(topic_id, group_id)` | 1 nhóm chỉ 1 yêu cầu / đề tài |

---

## 3. `topic_embeddings` — vector ngữ nghĩa cho "gợi ý đề tài"

Model: `App\Models\TopicEmbedding` · PK: `topic_id` (quan hệ **1–1** với `topics`).

| Cột | Kiểu dữ liệu | Null | Ràng buộc | Ghi chú |
|---|---|---|---|---|
| `topic_id` | bigint unsigned | NN | **PK**, **FK** → `topics.topic_id` ON DELETE **CASCADE** | 1 hàng / 1 đề tài |
| `model` | varchar(120) | NN | **IX** `topic_embeddings_model_index` | nhãn model: `vietnamese-sbert` |
| `dim` | smallint unsigned | NN | | số chiều vector: `768` |
| `content_hash` | char(40) | NN | | sha1 của text đã embed (`name + description + goal + requirements`) |
| `embedding` | longtext | NN | | base64(float32 little-endian) ~4 KB |
| `embedded_at` | timestamp | NULL | | thời điểm embed |
| `created_at` | timestamp | NULL | | |
| `updated_at` | timestamp | NULL | | |

**Nguyên tắc thiết kế:**

- Mỗi đề tài **chỉ embed MỘT LẦN**, lưu lại để tái sử dụng (không embed lại mỗi lần gợi ý).
- `content_hash` đổi ⇒ nội dung đề tài đổi ⇒ chỉ hàng đó được embed lại.
- `model` đổi (vd nâng cấp model) ⇒ biết được hàng nào cần embed lại toàn bộ.
- MySQL 8.4 **chưa có kiểu `VECTOR`** (chỉ có từ MySQL 9.x) nên vector lưu dạng `LONGTEXT` base64,
  **không có index vector**; cosine similarity tính ở service AI (`topic-recommender-8891`, port 8891).

---

Xem tiếp: [04 — Nhóm](04-groups.md) · [08 — Khóa ngoại](08-foreign-keys.md) ·
[09 — Liên kết](09-relations.md)
