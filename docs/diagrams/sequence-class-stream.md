# Sequence — Bảng tin lớp học (kiểu Google Classroom)

> Chức năng #19 · `/classes/{id}/stream` · thông báo của giảng viên + hoạt động nhóm tự động + bình luận

```mermaid
sequenceDiagram
    autonumber
    actor GV as Giảng viên phụ trách
    actor SV as Sinh viên
    participant UI as Blade + fetch<br/>(classes/stream)
    participant CT as ClassStreamController
    participant SS as ClassStreamService
    participant DB as MySQL<br/>(class_posts, class_post_comments)
    participant NS as NotificationService
    participant RW as Reverb<br/>(private class.{id})

    Note over GV,SV: 1) Giảng viên đăng thông báo
    GV->>UI: Nhập nội dung (+ tuỳ chọn Ghim)
    UI->>CT: POST /classes/{id}/stream (JSON + X-CSRF-TOKEN)
    CT->>CT: canManage() — GV phụ trách / admin? (403 nếu không)
    CT->>SS: postAnnouncement(class, author, content, title, pin)
    SS->>DB: INSERT class_posts (type=announcement)
    SS->>NS: create(type=class_announcement) cho TỪNG sinh viên của lớp
    NS->>DB: INSERT notifications + tăng users.unread_notifications
    NS->>RW: broadcast NotificationCreated
    SS->>RW: broadcast ClassPostCreated
    CT-->>UI: {ok, message, html} → JS chèn bài vào bảng tin
    RW-->>SV: bài mới hiện ngay (banner "Có hoạt động mới")

    Note over SV,GV: 2) Bình luận / trả lời
    SV->>UI: Viết bình luận (hoặc bấm "Trả lời")
    UI->>CT: POST /classes/{id}/stream/{post}/comments
    CT->>CT: canView() — thuộc lớp? (403 nếu không)
    CT->>SS: addComment(post, author, content, parentId)
    SS->>DB: INSERT class_post_comments + tăng class_posts.comments_count
    SS->>NS: create(type=class_comment) cho chủ bài + tác giả bình luận gốc
    SS->>RW: broadcast ClassCommentCreated
    CT-->>UI: {ok, html} → JS thay khối bình luận của đúng bài
    RW-->>GV: GV đang mở trang ⇒ tự tải lại khối bình luận đó

    Note over SV,DB: 3) Hoạt động NHÓM tự động vào bảng tin
    SV->>DB: Tạo nhóm / thêm thành viên / rời nhóm (GroupService)
    DB-->>SS: model events (GroupObserver, GroupMemberObserver)
    SS->>DB: INSERT class_posts (group_created | group_member_joined | ...)
    SS->>RW: broadcast ClassPostCreated
    GV->>DB: Duyệt đề tài (TopicRegistrationService::approve)
    DB-->>SS: groups.topic_id thay đổi ⇒ GroupObserver
    SS->>DB: INSERT class_posts (type=group_topic)
```

## Ghi chú thiết kế

| Điểm | Chi tiết |
|---|---|
| Quyền | Đăng/ghim/xoá bài: GV **phụ trách lớp** hoặc admin · Xem/bình luận: thành viên lớp + GV phụ trách + admin · Xoá bình luận: tác giả / GV phụ trách / admin |
| Fail-open | Mọi thao tác ghi bảng tin nằm trong try/catch ⇒ lỗi KHÔNG làm hỏng tạo nhóm / duyệt đề tài (đã có test riêng) |
| Chống nhiễu | Observer bỏ qua thay đổi chỉ có `status`/`updated_at` ⇒ tạo nhóm chỉ sinh **1 bài** `group_created` (không thêm bài khi auto-update `status`) |
| Realtime | Private channel `class.{classId}` (admin + thành viên lớp subscribe được). Reverb tắt ⇒ trang vẫn chạy, chỉ không tự cập nhật |
| Backfill | `php artisan class-stream:backfill [--class=] [--dry-run] [--with-status]` sinh bài từ `groups` (group_created), `group_members` (group_member_joined), `topic_requests` Accepted (group_topic) và **giữ mốc thời gian gốc**; `source_key` unique ⇒ chạy lại KHÔNG nhân đôi |
| Không dựng lại được | Đổi tên nhóm / đổi trưởng nhóm / nhóm đã xoá (không có lịch sử) |
