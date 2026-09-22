# Trạng thái chức năng (Feature audit)

> Kiểm tra ngày 2026-09-18: đối chiếu `routes/web.php` ↔ Controller ↔ View ↔ Model/migration.

## Tổng quan

| # | Chức năng | Trạng thái | Ghi chú |
|---|-----------|------------|---------|
| 1 | Đăng nhập / đăng ký / quên mật khẩu | ✅ Hoàn thành | 4 test Auth (ForgotPassword/ChangePassword) đang **fail vì môi trường mail** — cần `MAIL_MAILER=log` hoặc SMTP thật khi chạy test |
| 2 | Quản lý người dùng (Admin CRUD + import + khóa/mở) | ✅ Hoàn thành | `AdminController`, `admin/users*` |
| 3 | Quản lý môn học + import Excel (Admin) | ✅ Hoàn thành | `SubjectController`, import/template routes |
| 4 | Quản lý lớp học phần (Admin + Giảng viên) | ✅ Hoàn thành | `ClassSectionController`, toggle-active |
| 5 | Quản lý sinh viên trong lớp | ✅ Hoàn thành | `StudentController`, import theo lớp |
| 6 | Quản lý đề tài (topics) + import | ✅ Hoàn thành | `TopicController` |
| 7 | Đăng ký đề tài (topic requests, duyệt/từ chối) | ✅ Hoàn thành | `TopicRequestController` |
| 8 | Nhóm: tạo / mời / yêu cầu tham gia / duyệt | ✅ Hoàn thành | `GroupController`, `InviteController`, `JoinRequestController` |
| 9 | Chat 1-1 (gửi, ảnh, block, badge đã đọc, broadcast Reverb) | ✅ Hoàn thành | `DirectChatController` |
| 10 | Chat nhóm (gửi, ảnh, badge đã đọc theo nhóm) | ✅ Hoàn thành | `GroupsChatController` |
| 11 | Chặn người dùng (block 2 chiều) | ✅ Hoàn thành | `BlockUserController` |
| 12 | Thông báo real-time + badge tổng | ✅ Hoàn thành | `NotificationController`, `AdminNotificationController` |
| 13 | Kiểm duyệt nội dung (fraud + nhạy cảm, flag-only) | ✅ Hoàn thành | 3 tầng: rules → PhoBERT fraud (8889) → PhoBERT moderation 5 nhãn (8890); `violation-detection/` |
| 14 | Admin giám sát chat (tab Bị gắn cờ, bỏ cờ, xóa, broadcast) | ✅ Hoàn thành | `AdminChatMonitorController`, `admin/chat-monitoring.blade.php` |
| 15 | Chatbot trợ lý đề tài (Gemini) | ✅ Hoàn thành | `ChatbotController` đọc `config('services.gemini.*')`; thiếu key ⇒ widget tự ẩn + trả thông báo thân thiện (không 500). LƯU Ý: key mới dạng `AQ...` chỉ dùng được model mới (`gemini-3.6-flash`) — `gemini-2.5-*` trả 404 |
| 16 | **Thống kê hệ thống** | ✅ Hoàn thành | `StatisticsController` viết lại + 5 route `admin/statistics*` + 5 view `statistics/*` + link sidebar admin. Trưởng nhóm tính qua `groups.leader_id`, "chưa có nhóm" qua `group_members`; status dùng đúng `Pending/Accepted/Rejected`. Test: `tests/Feature/Admin/StatisticsTest.php` |
| 17 | Biểu đồ/thống kê cho Admin dashboard | ⚠️ Đã có trang Thống kê (bảng + progress bar) | Chưa có Chart.js — đề xuất nâng cấp biểu đồ ở `docs/diagrams/admin-charts.md` |
| 18 | **Gợi ý đề tài theo NGỮ NGHĨA (semantic recommendation)** | ✅ Hoàn thành | `TopicRecommendationController` (`POST /api/recommend`) + `TopicRecommendationService` + `TopicEmbeddingService` + bảng `topic_embeddings` (vector chỉ sinh 1 lần) + service AI `AI-Services/topic-recommender` (:8891). UI: panel ở `user/topics` & `user/group_topics`. Test: `tests/Unit/TopicRecommendationTest.php` (12) + `tests/Feature/TopicRecommendationTest.php` (10) |

## Ghi chú sửa đổi 2026-09-20
- **Gợi ý đề tài theo ngữ nghĩa (mục 18)** — chức năng mới:
  - Kiến trúc: UI (Blade + fetch) → `POST /api/recommend` → `TopicRecommendationController`
    → `TopicRecommendationService` → service AI `AI-Services/topic-recommender` (:8891,
    model `keepitreal/vietnamese-sbert` 768 chiều, mean pooling, cosine similarity).
  - **Không embedding lại nhiều lần**: vector đề tài lưu ở bảng `topic_embeddings`
    (1 hàng/đề tài, kèm `content_hash` = sha1 text đã embed). Mỗi lần gợi ý chỉ sinh vector
    cho **câu truy vấn**; đề tài chỉ embed lại khi nội dung đổi hoặc đổi model
    (`TOPIC_RECOMMENDER_MODEL_TAG`).
  - Backfill chủ động: `php artisan topics:embed [--class=] [--topic=] [--force]`.
  - Giới hạn quyền: sinh viên/giảng viên chỉ gợi ý trong lớp mình tham gia (`user_classes`);
    truyền `class_id` của lớp khác ⇒ 403. Admin xem được mọi lớp.
  - Fail-open 2 tầng: `TOPIC_RECOMMENDER_ENABLED=false` ⇒ panel tự ẩn + API trả `ok:false`;
    service AI tắt/lỗi ⇒ HTTP 200 + thông báo "tạm thời không khả dụng" (không bao giờ 500),
    trang tìm kiếm/đăng ký đề tài không bị ảnh hưởng.
  - Docs: `AI-Services/topic-recommender/README.md` + `MODEL_NOTES.md`,
    `AI-Services/MODEL_INFO.md` (mục 10), `docs/diagrams/sequence-topic-recommendation.md`.

## Ghi chú sửa đổi 2026-09-18
- **Badge Chat / Yêu cầu / Lời mời + trang Yêu cầu** (mục 8, 9, 10):
  - Badge "Chat" trên sidebar tính từ `ChatUnreadService::totalFor()` (cột cache
    `users.unread_message_count` có thể lệch — đã backfill 1 lần cho toàn bộ user).
  - Badge "Lời mời" tính trực tiếp từ `invites` (trước đây dùng biến `$pendingInvites`
    chỉ tồn tại ở trang dashboard nên luôn = 0 ở các trang khác).
  - Trang `user.join-requests` có thêm tab **"Yêu cầu nhận được"** — khớp đúng số mà badge
    "Yêu cầu" (`pending_join_requests_count`) đang đếm; giữ tab "Yêu cầu đã gửi".
  - Yêu cầu hết hiệu lực (nhóm đủ thành viên / sinh viên đã có nhóm khác / đã xử lý) được
    chuyển `Expired`, ẩn nút Chấp nhận–Từ chối (cả trang Yêu cầu và trang Thông báo) và
    phát event realtime `JoinRequestResolved` để badge giảm + tắt nút ngay không cần tải lại.
- **StatisticsController** đã hoàn thiện (mục 16): 5 route + 5 view + sửa 4 bug schema
  (`Users` model không tồn tại, `is_have_group` đã bị xóa, `role='leader'` đã bị xóa,
  status `'Approved'` không tồn tại — đúng là `Accepted`).
- **Chatbot** đã cấu hình + hardening (mục 15): `config/services.php` có `gemini.key/url`,
  controller dùng `config()` thay `env()`, timeout 15s, thiếu key trả 200 + thông báo,
  component tự ẩn khi thiếu key. Phát hiện: key mới (AQ...) chỉ chạy model mới.

## Hành động đề xuất cho từng điểm dở dang

### 1) StatisticsController (mục 16) — chọn 1 trong 2 hướng
- **Hướng A (khuyến nghị, ít công):** xoá `app/Http/Controllers/StatisticsController.php` — các số liệu tương đương đã/đang được đáp ứng bởi admin dashboard + trang chat-monitor; tránh code chết gây nhầm lẫn.
- **Hướng B (hoàn thiện):**
  1. Đổi import `App\Models\Users` → `App\Models\User` (và `Group_Members` → kiểm tra lại PK);
  2. Sửa `Topics::whereNotNull('topic_id')` — đang tự so cột với chính nó, vô nghĩa;
  3. Tạo route `admin/statistics/*` (middleware admin);
  4. Tạo 5 view `resources/views/statistics/{index,topics,groups,requests,users}.blade.php`;
  5. Thêm test.

### 2) Chatbot (mục 15)
1. Thêm vào `.env.example`:
   ```env
   GEMINI_API_KEY=
   GEMINI_BASE_URL=https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent
   ```
2. Ghi chú: khi thiếu key, nên thêm check để hiển thị "chatbot chưa cấu hình" thay vì trả lỗi chung chung "Lỗi kết nối".
3. Prompt chỉ lấy 20 đề tài mới nhất — cân nhắc lọc theo `class_id` của user để trả lời chính xác hơn.

### 3) Mail/Auth test (mục 1)
- `phpunit.xml` nên ép `MAIL_MAILER=log` để 4 test Auth pass mà không cần SMTP thật.

## Kết luận test hiện tại
- `php artisan test`: **244 passed / 4 failed** (4 fail đều là Auth + mail: `ForgotPasswordTest` ×3 +
  `ChangePasswordTest` ×1, nguyên nhân môi trường, không phải logic).
- Suite moderation (unit + feature): 33/33 pass.
- Suite gợi ý đề tài (mục 18): **22/22 pass** — `tests/Unit/TopicRecommendationTest.php` (12,
  không cần DB/service AI) + `tests/Feature/TopicRecommendationTest.php` (10, cần MySQL `team_assign_test`;
  trong đó có 1 test kiểm panel UI + script gọi API render đúng ở trang `user/topics`).
