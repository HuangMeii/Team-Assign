# ERD — Sơ đồ cơ sở dữ liệu (các bảng chính)

```mermaid
erDiagram
    USERS ||--o{ GROUPS : "dẫn dắt (leader_id)"
    USERS ||--o{ GROUP_MEMBERS : "tham gia"
    GROUPS ||--o{ GROUP_MEMBERS : "có"
    GROUPS ||--o{ JOIN_REQUESTS : "nhận"
    USERS ||--o{ JOIN_REQUESTS : "gửi"
    GROUPS ||--o{ INVITES : "nhận"
    USERS ||--o{ INVITES : "mời / được mời"
    CLASS_SECTIONS ||--o{ TOPICS : "thuộc"
    SUBJECTS ||--o{ CLASS_SECTIONS : "có"
    GROUPS }o--o| TOPICS : "đăng ký (topic_id)"
    TOPICS ||--o{ TOPIC_REQUESTS : "yêu cầu"
    TOPICS ||--o| TOPIC_EMBEDDINGS : "vector ngữ nghĩa (gợi ý đề tài)"
    USERS ||--o{ TOPIC_REQUESTS : "gửi"
    USERS ||--o{ DIRECT_MESSAGES : "gửi (sender)"
    USERS ||--o{ DIRECT_MESSAGES : "nhận (recipient)"
    USERS ||--o{ CHAT_MESSAGES : "gửi trong nhóm"
    GROUPS ||--o{ CHAT_MESSAGES : "có"
    USERS ||--o{ BLOCKED_USERS : "chặn / bị chặn"
    USERS ||--o{ GROUP_CHAT_READS : "đánh dấu đã đọc"
    GROUPS ||--o{ GROUP_CHAT_READS : "theo nhóm"
    USERS ||--o{ NOTIFICATIONS : "nhận"

    USERS {
        bigint user_id PK
        string name
        string email
        string password
        string role "student|leader|lecturer|admin"
        boolean is_active
        string student_code
        boolean is_have_group
        int unread_message_count
        timestamp pending_email
    }
    GROUPS {
        bigint group_id PK
        string group_name
        bigint leader_id FK
        bigint class_id FK
        bigint topic_id FK "nullable"
        string status
    }
    TOPICS {
        bigint topic_id PK
        string name
        string lecturer
        bigint class_id FK
        bigint assigned_group_id FK "nullable"
        string status
    }
    CHAT_MESSAGES {
        bigint id PK
        bigint group_id FK
        bigint user_id FK
        text content
        string attachment "nullable"
        boolean is_flagged
        text flag_reason "nullable"
        decimal moderation_score "nullable"
        timestamp flagged_at "nullable"
    }
    DIRECT_MESSAGES {
        bigint id PK
        bigint sender_id FK
        bigint recipient_id FK
        text content
        string attachment "nullable"
        boolean is_flagged
        text flag_reason "nullable"
        decimal moderation_score "nullable"
        timestamp flagged_at "nullable"
    }
    CLASS_SECTIONS {
        bigint class_id PK
        string class_name
        bigint subject_id FK
        bigint lecturer_id FK
        boolean is_active
    }
    SUBJECTS {
        bigint subject_id PK
        string subject_name
        string subject_code
    }
    TOPIC_EMBEDDINGS {
        bigint topic_id PK "FK -> topics, cascade"
        string model "vietnamese-sbert"
        smallint dim "768"
        char content_hash "sha1(text đã embed)"
        longtext embedding "base64(float32 LE)"
        timestamp embedded_at
    }
```

## Cột kiểm duyệt (thêm ở migration `2026_09_17_213035_add_flag_columns_to_chat_tables`)
- `chat_messages` và `direct_messages` cùng có: `is_flagged`, `flag_reason`, `moderation_score`, `flagged_at`.
- Chỉ mục `direct_messages (recipient_id, created_at)` phục vụ badge chưa đọc.

## `topic_embeddings` (thêm ở migration `2026_09_20_120000_create_topic_embeddings_table`)

Bảng phục vụ **gợi ý đề tài theo ngữ nghĩa** (chức năng #18):

- **1 hàng / đề tài** (PK `topic_id`, FK → `topics.topic_id` ON DELETE CASCADE) — vector chỉ sinh
  **một lần** rồi tái sử dụng, không embedding lại ở mỗi lần gợi ý.
- `embedding` = **base64(float32 little-endian)**, 768 chiều ⇒ ~4 KB/hàng. MySQL 8.4 chưa có kiểu
  `VECTOR` nên không lưu native và **không** có index vector; cosine similarity được tính ở
  service AI (`AI-Services/topic-recommender`, port 8891).
- `content_hash` = sha1 của text đã embed (`name + description + goal + requirements`) ⇒ đổi nội dung
  là biết ngay phải embed lại; `model` cho biết vector thuộc model nào (đổi model ⇒ embed lại toàn bộ).
- Chỉ mục `model` để liệt kê/đối chiếu nhanh theo model.
