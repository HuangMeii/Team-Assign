# Trạng thái chức năng (Feature audit)

> Kiểm tra ngày 2026-09-18: đối chiếu `routes/web.php` ↔ Controller ↔ View ↔ Model/migration.

## Tổng quan

| # | Chức năng | Trạng thái | Ghi chú |
|---|-----------|------------|---------|
| 1 | Đăng nhập / đăng ký / quên mật khẩu | ✅ Hoàn thành | Nhóm test Auth (ForgotPassword/ChangePassword) **đã xanh** sau L01–L04 (trước đây đỏ do lệch đặc tả session key + gate xác thực mail gây 500); **L09**: ghi **lịch sử đăng nhập** (`login_histories`) + cảnh báo khi IP mới; cờ `must_change_password` buộc đổi MK (L06) |
| 2 | Quản lý người dùng (Admin CRUD + import + khóa/mở) | ✅ Hoàn thành | `AdminController`, `admin/users*`; **2026-10-10**: trang chi tiết user hiển thị **lớp & nhóm theo vai trò** (GV ⇒ lớp phụ trách; SV ⇒ lớp kèm trạng thái Đang học/Đã rời + nhóm kèm vai trò trưởng nhóm và trạng thái đề tài). Test: `tests/Feature/AdminUserDetailTest.php` (4) |
| 3 | Quản lý môn học + import Excel (Admin) | ✅ Hoàn thành | `SubjectController`, import/template routes; import CSV **tự dò dấu phân cách** (`, ; TAB \|`) như import đề tài + bỏ qua dòng trống; cột file `ten_mon, so_tc, so_bai_bao_cao`. Test: `tests/Feature/SubjectTest.php` (21) |
| 4 | Quản lý lớp học phần (Admin + Giảng viên) | ✅ Hoàn thành | `ClassSectionController`, toggle-active; **L07**: mã lớp do hệ thống tự sinh **ĐÚNG 5 ký tự** cho cả Admin và Giảng viên (`generateUniqueClassCode()`, bỏ generator cũ `{subject_code}-NN`), SV tham gia bằng mã với validate `size:5`; mã cũ >5 ký tự đã được migration quy đổi. Test: `tests/Feature/ClassCodeFiveCharsTest.php` (5) |
| 5 | Quản lý sinh viên trong lớp | ✅ Hoàn thành | `StudentController`, import theo lớp; **L05**: trạng thái `user_classes.status` (Đang học / Đã rời lớp — xóa mềm + `left_at`), bộ lọc/badge/nút "Cho rời lớp ↔ Thêm lại" ở trang chi tiết lớp Admin & Giảng viên; sinh viên đã rời bị ẩn khỏi mọi luồng phía sinh viên (dashboard, nhóm, đề tài, chat nhóm). Test: `tests/Feature/ClassMembershipStatusTest.php` (12). **L06**: thao tác nhanh **Reset mật khẩu** (về mặc định `password`; **2026-10-10: KHÔNG còn bắt buộc đổi ở lần đăng nhập sau**) trên trang chi tiết & danh sách sinh viên — **chỉ Admin** (giảng viên gọi URL trực tiếp ⇒ 403). **2026-10-10**: gỡ hẳn **Gửi email** (nút/modal/route/`sendEmail()`/Mailable/view) và admin KHÔNG còn trang danh sách sinh viên (ẩn menu + `/students` redirect Dashboard; vào chi tiết SV từ bảng sinh viên ở chi tiết lớp). Test: `tests/Feature/StudentQuickActionsTest.php` (6) |
| 6 | Quản lý đề tài (topics) + **import Excel/CSV** | ✅ Hoàn thành | `TopicController` (CRUD) + `TopicController::import/importForm/downloadTemplate` + `App\Imports\TopicsImport`; cột file: `ten_de_tai, mo_ta, muc_tieu, yeu_cau, ma_lop, so_tv_min, so_tv_max, han_dang_ky` (+ `loai_bao_cao`: rỗng ⇒ cuối kì). file CSV **tự dò dấu phân cách** (dấu phẩy / chấm phẩy / TAB / sổ đứng) + bỏ qua dòng trống; file sai dòng tiêu đề ⇒ báo 1 lỗi rõ ràng. **loại báo cáo** `topics.report_type` (final/midterm — môn 1 bài luôn final) + cột import `loai_bao_cao`. Test: `tests/Feature/TopicImportTest.php` (15) + `tests/Feature/TopicReportTypeTest.php` (7) |
| 7 | Đăng ký đề tài (topic requests, duyệt/từ chối) | ✅ Hoàn thành | `TopicRequestController` |
| 8 | Nhóm: tạo / mời / yêu cầu tham gia / duyệt | ✅ Hoàn thành | `GroupController`, `InviteController`, `JoinRequestController`; yêu cầu hết hiệu lực tự chuyển `Expired` + ẩn khỏi tab mặc định; **1 nhóm / 1 lớp học phần**: lớp đã có nhóm không hiện trong combo box tạo nhóm, form vào từ thẻ lớp hiện dạng TEXT, “Tìm nhóm” theo từng lớp (xem ghi chú 2026-09-22 lần 2 & 2026-09-23 lần 2); **L10**: Admin/GV phụ trách **xóa MỀM** nhóm (`groups.deleted_at`) + nhả đề tài về chưa đăng ký + tab “Đã xóa”/khôi phục/xóa vĩnh viễn (chỉ Admin) |
| 9 | Chat 1-1 (gửi, ảnh, block, badge đã đọc, broadcast Reverb) | ✅ Hoàn thành | `DirectChatController`; **L08**: tin hiện NGAY, 3 check AI chạy song song (`Http::pool`) rồi gắn cờ SAU response (`ModerateDirectMessage` + `afterResponse()`) — không cần `queue:work` |
| 10 | Chat nhóm (gửi, ảnh, badge đã đọc theo nhóm) | ✅ Hoàn thành | `GroupsChatController`; **L08**: như chat 1-1 qua `GroupChatService` + `ModerateGroupMessage` (tin thông báo/cảnh báo của admin không bị gắn cờ) |
| 11 | Chặn người dùng (block 2 chiều) | ✅ Hoàn thành | `BlockUserController` |
| 12 | Thông báo real-time + badge tổng | ✅ Hoàn thành | `NotificationController`, `AdminNotificationController` |
| 13 | Kiểm duyệt nội dung (fraud + nhạy cảm, flag-only) | ✅ Hoàn thành | 3 tầng: rules → PhoBERT fraud (8889) → PhoBERT moderation 5 nhãn (8890); `violation-detection/`; **L08**: 3 tầng chạy **song song** trong `ChatModerationService` (`Http::pool`, timeout 2s/2s/5s) và gắn cờ **sau response** (flag-only giữ nguyên, fail-open) |
| 14 | Admin giám sát chat (tab Bị gắn cờ, bỏ cờ, xóa, broadcast) | ✅ Hoàn thành | `AdminChatMonitorController`, `admin/chat-monitoring.blade.php` |
| 15 | Chatbot trợ lý đề tài (Groq/Gemini) | ✅ Hoàn thành | `ChatbotService` đa provider: `CHATBOT_PROVIDER=groq` (chuẩn OpenAI, free 1.000 req/ngày) + `CHATBOT_FALLBACKS=gemini`; retry cho timeout/429/5xx, key gửi qua header (không lộ trong log); thiếu key ⇒ widget tự ẩn, không 500. **Cuối mỗi câu trả lời của bot có dòng miễn trừ** “Nội dung này chỉ mang tính chất tham khảo, … trao đổi với giảng viên phụ trách.” — câu chữ đổi được bằng `CHATBOT_DISCLAIMER` trong .env (để trống ⇒ ẩn), chỉ ở tầng giao diện nên API `/chatbot/ask` không đổi. Test: `tests/Feature/ChatbotTest.php` (10 case) |
| 16 | **Thống kê hệ thống** | ✅ Hoàn thành | `StatisticsController` viết lại + 5 route `admin/statistics*` + 5 view `statistics/*` + link sidebar admin. Trưởng nhóm tính qua `groups.leader_id`, "chưa có nhóm" qua `group_members`; status dùng đúng `Pending/Accepted/Rejected`. Test: `tests/Feature/Admin/StatisticsTest.php` |
| 17 | Biểu đồ/thống kê cho Admin dashboard | ⚠️ Đã có trang Thống kê (bảng + progress bar) | Chưa có Chart.js — đề xuất nâng cấp biểu đồ ở `docs/diagrams/admin-charts.md` |
| 18 | **Gợi ý đề tài theo NGỮ NGHĨA (semantic recommendation)** | ✅ Hoàn thành | `TopicRecommendationController` (`POST /api/recommend`) + `TopicRecommendationService` + `TopicEmbeddingService` + bảng `topic_embeddings` (vector chỉ sinh 1 lần) + service AI `AI-Services/services/topic-recommender-8891` (:8891). UI: panel ở `user/topics`, `user/group_topics` & `topics/index`. **Bắt buộc chọn môn học** (`subject_id`) và mở cho cả **giảng viên / admin**. Test: `tests/Unit/TopicRecommendationTest.php` (12) + `tests/Feature/TopicRecommendationTest.php` (17) |
| 19 | **Bảng tin lớp học kiểu Google Classroom (thông báo GV + hoạt động nhóm + bình luận)** | ✅ Hoàn thành | `ClassStreamController` (`/classes/{id}/stream`) + `ClassStreamService` + observer `GroupObserver`/`GroupMemberObserver` + bảng `class_posts`/`class_post_comments` + realtime `class.{id}` + backfill `php artisan class-stream:backfill`. UI: trang bảng tin + thẻ preview ở 3 trang lớp. Test: `tests/Unit/ClassStreamTest.php` (5) + `tests/Feature/ClassStreamTest.php` (10) |
| 20 | **Trạng thái online/offline + trạng thái tin nhắn (đã gửi / đã xem) + lịch sử trò chuyện** | ✅ Hoàn thành | `PresenceService` + middleware `UpdateLastSeen` (`users.last_seen_at`, heartbeat 60s) + `PresenceController` (`/presence/ping`, `/presence/status`); **UI chỉ hiện CHẤM xanh/xám** (nhãn chữ "Đang hoạt động / Hoạt động X trước" chỉ còn trong `title` tooltip + payload API); tick ✓ xám / ✓✓ xanh dựa trên `direct_messages.seen_at` + event `DirectMessagesSeen`; lịch sử: tách ngày + "Tải thêm tin nhắn cũ" (`chat.history`). Test: `tests/Unit/PresenceTest.php` (7) + `tests/Feature/PresenceTest.php` (6) + `tests/Feature/ChatMessageStatusTest.php` (6) |

## Ghi chú sửa đổi 2026-09-22 (lần 2)

- **Import đề tài (mục 6)** — chức năng mới:
  - `App\Imports\TopicsImport` (bám pattern `SubjectsImport`: `WithHeadingRow` + `WithValidation` + `SkipsOnFailure` + `WithChunkReading`), cột file:
    `ten_de_tai, mo_ta, muc_tieu, yeu_cau, ma_lop, so_tv_min, so_tv_max, han_dang_ky`.
  - `TopicController::importForm()/import()/downloadTemplate()` + 3 route `topics/import/form`, `topics/import`,
    `topics/template` (khai TRƯỚC `Route::resource('topics')`), view `topics/import.blade.php`, nút “Import đề tài” ở `topics/index`.
  - Quy tắc: `ma_lop` là **mã lớp học phần** → tự suy ra `class_id`, `subject_id`, giảng viên phụ trách;
    giảng viên CHỈ import được lớp mình phụ trách; tên đề tài trùng ⇒ bỏ qua (không ghi đè);
    min/max rỗng ⇒ 2/4; hạn đăng ký rỗng ⇒ +30 ngày (nhận cả serial ngày của Excel).
  - Kèm sửa: `Route::resource('topics')` nay có middleware `auth` (trước đây khách truy cập `/topics` bị lỗi 500
    do controller đọc `$user->role` trên `null`).
- **Trạng thái hoạt động chỉ hiện chấm (mục 20)**: bỏ nhãn chữ ở danh sách hội thoại + header hội thoại 1-1
  (`resources/views/chat/index.blade.php`) và bỏ đoạn JS ghi nhãn; giữ chấm `presence-dot` (xanh = online,
  xám = offline) + tooltip `title`. `PresenceService` / `/presence/status` không đổi (test cũ vẫn xanh).
- **Cảnh báo của Admin trong khung chat không còn tự biến mất**: `layouts/user.blade.php` trước đây sau 5 giây
  gọi `document.querySelectorAll('.alert')` và đóng **mọi** `.alert` trên trang — kéo theo cảnh báo/thông báo của
  Admin trong khung chat nhóm (và banner “Đang xem với quyền Admin”). Nay flash message được bọc trong
  `#flash-messages` và JS chỉ đóng `#flash-messages .alert`.
- **Yêu cầu tham gia nhóm hết hiệu lực (mục 8)**:
  - `InvitationService::expireStalePendingRequestsFor()` — dọn an toàn (idempotent) khi mở trang “Nhóm của tôi”
    và “Yêu cầu tham gia nhóm”: yêu cầu `Pending` của sinh viên đã có nhóm cùng lớp / nhóm đã đầy / nhóm không còn
    ⇒ chuyển `Expired` + phát `JoinRequestResolved` (fail-open).
  - Trang “Yêu cầu đã gửi” mặc định **chỉ hiện yêu cầu đang chờ**; thêm tab **“Hết hiệu lực”** và tab **“Tất cả”**
    (`?status=all`) để xem lịch sử — dữ liệu vẫn giữ trong DB.
  - `user/my_groups.blade.php`: đảo thứ tự kiểm tra ⇒ sinh viên đã có nhóm trong lớp thấy “Bạn đã có nhóm lớp này”
    thay vì “Đang chờ duyệt”.
  - Test mới: `tests/Feature/JoinRequestExpiryTest.php` (6).

## Ghi chú sửa đổi 2026-09-23 (lần 2)

- **1 nhóm / 1 lớp học phần — chặn tạo nhóm thứ 2 (mục 8)**:
  - `UserDashboardController::createGroupForm()` chỉ trả về lớp sinh viên **đang tham gia VÀ CHƯA có nhóm**
    (`hasGroupInClass`) ⇒ combo box “Lớp học phần” không còn hiện lớp đã có nhóm; đã có nhóm ở mọi lớp ⇒ cờ
    `hasNoAvailableClass` + view hiện cảnh báo và khóa nút tạo.
  - `GroupService::createGroupByStudent()` bổ sung kiểm tra **sinh viên phải thuộc lớp** (bịt lỗ hổng sửa
    `class_id` trong POST để tạo nhóm ở lớp mình không học).
  - Test: `tests/Feature/GroupCreationScopeTest.php` (6) + 1 case trong `tests/Feature/Services/GroupServiceTest.php`.
- **“Nhóm của tôi”: Tạo nhóm / Tìm nhóm theo TỪNG lớp**:
  - Vào `user.create_group?class_id={id}` ⇒ lớp hiện dạng **TEXT + hidden input**, không bắt chọn lại combo box
    (id không hợp lệ/không thuộc quyền ⇒ tự quay về combo box đã lọc).
  - Mỗi thẻ lớp có **modal “Tìm nhóm” riêng** (`#findGroupModal-{class_id}`) chỉ liệt kê nhóm còn chỗ thuộc đúng
    lớp đó — dùng partial `resources/views/user/partials/available-group-cards.blade.php`.
- **Trợ lý gợi ý đề tài (mục 18) — bắt buộc chọn môn học + mở cho giảng viên/admin**:
  - `components/topic-recommender.blade.php` có thêm combo box **“Môn học cần gợi ý”** (`data-role="subject"`,
    prop `subjects`); JS chặn khi chưa chọn; request gửi kèm `subject_id`.
  - `TopicRecommendationController::recommend()` validate `subject_id` (`required|integer|exists:subjects,subject_id`,
    thông báo “Bạn hãy chọn môn học cần gợi ý.”) và giới hạn phạm vi = **lớp theo vai trò GIAO lớp thuộc môn đã chọn**:
    sinh viên/giảng viên = `user_classes`, admin = mọi lớp của môn. Rỗng ⇒ HTTP 200 + `ok:false` (không 403/500,
    không gọi service AI).
  - Sinh viên đã có đề tài được duyệt **vẫn dùng được**, panel chỉ hiện `alert-warning` tham khảo (không chặn).
  - Panel cũng render ở `topics/index` (trang quản lý đề tài) cho giảng viên/admin; `TopicController::index()`
    truyền `$subjects` theo vai trò.
  - Test: `tests/Feature/TopicRecommendationTest.php` tăng 10 → **17** (thêm bắt buộc chọn môn, phạm vi môn,
    cảnh báo không chặn, giảng viên/admin, panel ở trang quản lý đề tài).

## Ghi chú sửa đổi 2026-09-23
- **Trạng thái online/offline + trạng thái tin nhắn (mục 20)** — chức năng mới:
  - `users.last_seen_at` + middleware `UpdateLastSeen` (mọi request web, **tối đa 1 lần/60s**,
    không đụng `updated_at`) + `POST /presence/ping` (JS gọi mỗi 60s khi tab mở).
    ONLINE = hoạt động trong 2 phút ⇒ chấm **xanh**, ngoài ra chấm **xám** kèm nhãn
    "Hoạt động X phút/giờ/ngày trước"; **quá 7 ngày chỉ ghi "Hoạt động hơn 7 ngày trước"**.
    Hiển thị ở danh sách người dùng trong trang chat + header hội thoại 1-1 (tự cập nhật 60s).
  - Tin nhắn 1-1 có **2 trạng thái**: **Đã gửi** (✓ xám — `seen_at IS NULL`) và
    **Đã xem** (✓✓ xanh — người nhận đang mở trang hội thoại). `seen_at` được ghi trong
    `ChatUnreadService::markDirectRead()` (mở `chat.show` hoặc AJAX `chat.read` khi cửa sổ mở,
    **không cần click ô nhập**) và broadcast `DirectMessagesSeen` để người gửi thấy tick đổi ngay.
  - **Lịch sử trò chuyện trên trình duyệt**: tách nhãn ngày (Hôm nay / Hôm qua / dd/mm/yyyy) +
    nút **"Tải thêm tin nhắn cũ"** (`GET /chat/{user}/history`, trả HTML render từ partial chung,
    giữ nguyên vị trí cuộn). Trước đây cứng 100 tin gần nhất.
  - Kèm sửa: `conversationQuery()` bọc ngoặc điều kiện 2 chiều (trước đây ghép thêm
    `where id < ?` bị vô hiệu do AND ưu tiên hơn OR) và sắp xếp theo `id` để phân trang ổn định.
  - Kiểm tra cảnh báo admin: tin `announcement`/`warning` **có** hiện trong khung chat nhóm
    (lịch sử + Reverb + polling) — đồng bộ thêm `renderMessage` bản inline trong `groups/chat.blade.php`
    và thêm test khẳng định payload realtime/polling mang `type=warning`.

## Ghi chú sửa đổi 2026-09-22
- **Bảng tin lớp học kiểu Google Classroom (mục 19)** — chức năng mới:
  - Trang `/classes/{id}/stream` dùng chung cho mọi vai trò (giao diện tự đổi theo quyền): giảng viên
    phụ trách/admin đăng thông báo; sinh viên và giảng viên bình luận/trả lời.
  - **Hoạt động nhóm tự động hiện trong lớp** nhờ observer: thành lập nhóm, thêm/rời thành viên,
    đổi trưởng nhóm, nhóm được duyệt/gán đề tài, nhóm giải tán (bài hệ thống `user_id = NULL`).
  - Ghim thông báo quan trọng (bài ghim luôn đầu bảng tin), xoá bài/bình luận theo quyền,
    thẻ tóm tắt 3 bài mới nhất ở trang lớp của sinh viên/giảng viên/admin.
  - Realtime Reverb (private channel `class.{classId}`): bài mới hiện banner, bình luận mới tự chèn.
  - Thông báo (chuông): `class_announcement` khi giảng viên đăng bài (toàn bộ SV của lớp),
    `class_comment` khi có bình luận (chủ bài + tác giả bình luận gốc).
  - **Đưa dữ liệu cũ vào bảng tin**: `php artisan class-stream:backfill [--class=] [--dry-run] [--with-status]`
    — idempotent (`source_key` unique), giữ mốc thời gian gốc. Trên dữ liệu hiện tại đã tạo **9 bài**
    (4 nhóm thành lập + 4 thành viên tham gia + 1 đề tài được duyệt).
  - Kèm sửa: `Groups::members()` thêm `withTimestamps()` (bảng pivot `group_members` trước đây không ghi
    `created_at`), `GroupService::addMember()` dùng `Group_Members::firstOrCreate` thay `attach()`
    (attach không bắn model events), `removeUserFromClassGroups()` xoá từng dòng pivot để observer chạy.
  - Docs: `docs/diagrams/sequence-class-stream.md`, cập nhật ERD/use-case/FEATURE_STATUS/README.

## Ghi chú sửa đổi 2026-09-20
- **Gợi ý đề tài theo ngữ nghĩa (mục 18)** — chức năng mới:
  - Kiến trúc: UI (Blade + fetch) → `POST /api/recommend` → `TopicRecommendationController`
    → `TopicRecommendationService` → service AI `AI-Services/services/topic-recommender-8891` (:8891,
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
  - Docs: `AI-Services/services/topic-recommender-8891/README.md` + `MODEL_NOTES.md`,
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

## Ghi chú sửa đổi 2026-09-23 (lần 3)

- **Chatbot (mục 15) — dòng miễn trừ ở CUỐI mỗi câu trả lời của bot:**
  - Câu chữ có MỘT nguồn duy nhất: `.env` `CHATBOT_DISCLAIMER` → `config/services.php`
    (`services.chatbot.disclaimer`, có default) → `resources/views/components/chatbot.blade.php`.
  - Widget render câu chữ vào khối ẩn `#chat-disclaimer`; JS đọc 1 lần lúc `DOMContentLoaded` rồi chèn
    `<div class="msg-disclaimer">` (chữ nhỏ, nghiêng, xám, có đường kẻ nét đứt phía trên) vào cuối **mỗi bong bóng
    bot** — kể cả câu chào đầu; thông báo lỗi mạng “⚠️ Lỗi kết nối…” thì cố ý không kèm.
  - `.env` / `.env.example`: `CHATBOT_DISCLAIMER` **phải đặt trong ngoặc kép** vì giá trị có khoảng trắng
    (thiếu ngoặc ⇒ Dotenv báo `The environment file is invalid!` và mọi lệnh `php artisan` dừng).
  - Tầng backend KHÔNG đổi: API `/chatbot/ask` vẫn trả `reply` như trước ⇒ 8 test API cũ giữ nguyên.
  - Test: `tests/Feature/ChatbotTest.php` 8 → **10** (thêm 2 case: widget hiện dòng miễn trừ + để trống
    `CHATBOT_DISCLAIMER` thì không render). Docs: nhóm `TC-BOT` 10 → 11 case.

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
1. `CHATBOT_DISCLAIMER` (dòng miễn trừ cuối mỗi câu trả lời) đã có trong `.env` + `.env.example`; `.env.example` cũng đã có `GROQ_*` và `GEMINI_*`.
   ```env
   # Câu có khoảng trắng ⇒ BẮT BUỘC ngoặc kép, thiếu ngoặc là Dotenv lỗi "The environment file is invalid!"
   CHATBOT_DISCLAIMER="Nội dung này chỉ mang tính chất tham khảo, nếu vui lòng cân nhắc hoặc trao đổi với giảng viên phụ trách."
   ```
2. Ghi chú: khi thiếu key, widget tự ẩn + API trả 200 kèm “chưa được cấu hình” (đã làm, mục 15) thay vì “Lỗi kết nối”.
3. Prompt chỉ lấy 20 đề tài mới nhất — cân nhắc lọc theo `class_id` của user để trả lời chính xác hơn.

### 3) Mail/Auth test (mục 1)
- `phpunit.xml` nên ép `MAIL_MAILER=log` để 4 test Auth pass mà không cần SMTP thật.

## Kết luận test hiện tại
- `php artisan test` (2026-09-23, sau đợt sửa lần 2): **310 passed / 4 failed** (1186 assertions).
  4 fail đều thuộc nhóm Auth: `ForgotPasswordTest` ×3 (lệch đặc tả test ↔ code: key session + điều hướng) và
  `ChangePasswordTest` ×1 (**BUG**: `routes/auth.php` không được nạp trong `bootstrap/app.php` ⇒ tài khoản
  `email_verified_at = NULL` đăng nhập bị HTTP 500 ở route `verification.notice`).
- Suite mới của đợt này: `tests/Feature/GroupCreationScopeTest.php` (6) + 1 case thêm ở
  `tests/Feature/Services/GroupServiceTest.php` + `tests/Feature/TopicRecommendationTest.php` (17) — **tất cả pass**.
- Suite đợt 2026-09-22 lần 2: `tests/Feature/TopicImportTest.php` (10) + `tests/Feature/JoinRequestExpiryTest.php` (6) — **16/16 pass**.
- Suite moderation (unit + feature): 33/33 pass.
- Suite gợi ý đề tài (mục 18): **29/29 pass** — `tests/Unit/TopicRecommendationTest.php` (12,
  không cần DB/service AI) + `tests/Feature/TopicRecommendationTest.php` (17, cần MySQL `team_assign_test`;
  gồm bắt buộc chọn môn, phạm vi môn theo vai trò, cảnh báo nhóm đã có đề tài, giảng viên/admin,
  và panel UI render đúng ở `user/topics` + `topics/index`).
- Suite bảng tin lớp (mục 19): **15/15 pass** — `tests/Unit/ClassStreamTest.php` (5, không cần DB) +
  `tests/Feature/ClassStreamTest.php` (10: ACL, notify sinh viên, hoạt động nhóm tự sinh bài, ghim/xoá,
  bình luận/reply/xoá đúng quyền, backfill idempotent, fail-open).
- Suite online/offline + trạng thái tin nhắn (mục 20): **19/19 pass** — `tests/Unit/PresenceTest.php` (7,
  nhãn "Đang hoạt động" / "Hoạt động X trước" / cap 7 ngày), `tests/Feature/PresenceTest.php` (6:
  middleware ghi last_seen_at + guard 60s, ping, status, khách 401), `tests/Feature/ChatMessageStatusTest.php`
  (6: đã gửi → đã xem khi mở hội thoại, broadcast `DirectMessagesSeen`, tick render, phân trang lịch sử).

## Ghi chú sửa đổi 2026-09-23 (lần 3) — Fix import đề tài (CSV phân cách TAB)

- **Hiện tượng người dùng báo**: import file CSV thu được “thêm mới 0, bỏ qua 0, dòng lỗi 27”, mọi dòng đều
  báo “Tên đề tài / Mô tả đề tài / Mã lớp học phần không được để trống” dù dữ liệu có trong file.
- **Nguyên nhân gốc**: file dùng dấu phân cách **TAB** (sao chép từ Excel sang Notepad), nhưng Maatwebsite
  **khoá cứng dấu phẩy** (`MapsCsvSettings::$delimiter` + `ReaderFactory::make()` luôn gọi `setDelimiter()`;
  dự án không có `config/excel.php` nên `config('excel.imports.csv')` rỗng) ⇒ PhpSpreadsheet **không tự dò**
  (`Reader\Csv::inferSeparator()` chỉ chạy khi delimiter là `null`) ⇒ cả file bị đọc thành **1 cột**.
  Dòng trống còn bị tính là dòng lỗi nên số lỗi bị nhân lên (27 = 3 cột bắt buộc × 9 dòng).
- **Đã sửa**:
  1. `App\Imports\TopicsImport::detectCsvDelimiter()` — tự dò `,` `;` TAB `|` (bỏ qua dòng trống, không nhầm
     dấu phẩy nằm trong phần mô tả) + `csvSettings()` / `getCsvSettings()` (`WithCustomCsvSettings`);
  2. `TopicsImport` thêm `SkipsEmptyRows` ⇒ dòng trống không còn sinh lỗi;
  3. `TopicController::import()` chẩn đoán **trước** dòng tiêu đề qua `App\Imports\TopicsHeadingRowImport`
     ⇒ file sai tiêu đề nhận **1 thông báo rõ ràng** (liệt kê cột cần có / đã đọc được / còn thiếu);
  4. Form import ghi rõ hệ thống tự dò dấu phân cách.
- **Xác minh**: `tests/Feature/TopicImportTest.php` lên **15 test** (thêm TAB, chấm phẩy, dòng trống cuối,
  tiêu đề sai, dò delimiter). Kiểm chứng ngoài DB: file TAB đọc đúng **8 cột** với delimiter dò được, và chỉ
  **1 cột** nếu ép dấu phẩy — tái hiện đúng lỗi cũ.
- **Tài liệu**: bộ test case lên **212 case / 14 sheet** (nhóm 05: 43 → 47 case), đã chạy `php artisan testcases:export`.
- **Phát hiện thêm khi viết test**: `parseDeadline()` trước đây để `Carbon::parse(31/12/2026)` tự xử lý — Carbon/PHP hiểu chuỗi có dấu gạch chéo theo kiểu Mỹ (m/d/Y) nên **fail** ⇒ hạn đăng ký rơi về mặc định +30 ngày dù tài liệu ghi hỗ trợ d/m/Y. Nay `TopicsImport::parseDeadline()` xử lý d/m/Y trước (nếu hợp lệ) rồi mới để Carbon tự quyết định.
- **Kết quả chạy test (2026-09-23, sau fix import)**: `php artisan test` ⇒ **315 passed / 4 failed** (1214 assertions); 4 fail vẫn là nhóm Auth cũ đã ghi ở trên. Riêng `tests/Feature/TopicImportTest.php` ⇒ **15 passed (74 assertions)**.
## Ghi chú sửa đổi 2026-09-23 (lần 4) - MỘT MÔN CÓ 2 BÀI BÁO CÁO (giữa kì + cuối kì)

- **Yêu cầu**: 1 lớp học phần có thể có 2 đồ án (giữa kì + cuối kì). Môn học khai báo số bài báo cáo; import để trống ⇒ 1;
  dữ liệu cũ giữ mặc định 1 (chỉ cuối kì). Ràng buộc giữ nguyên: **1 sinh viên / 1 lớp = 1 nhóm duy nhất** (nhóm của lớp làm CẢ 2 bài).
- **Schema**: `subjects.report_count` (unsignedTinyInteger, default 1) + `topics.report_type` (string 10, default `final`,
  index `(class_id, report_type)`). Migration backfill: 13/13 môn = 1; các đề tài hiện có = `final` (đã chạy trên DB thật).
- **Môn học**: form thêm/sửa có chọn 1 hoặc 2 bài (radio, mặc định 1); danh sách hiển thị badge; `SubjectsImport` nhận cột
  `so_bai_bao_cao` (thiếu cột/trống ⇒ 1; môn ĐÃ CÓ + ô trống ⇒ GIỮ NGUYÊN giá trị cũ để không vô tình hạ 2 ⇒ 1);
  file mẫu `/admin/subjects/template` thêm cột; `Subject::$attributes` mặc định 1 (khớp default DB).
- **Đề tài**: `topics.report_type` (`final` mặc định / `midterm`); form tạo/sửa có chọn loại (JS khoá Giữa kì khi môn chỉ 1 bài
  theo `data-report-count`); **server ÉP về `final`** nếu môn có `report_count = 1`; danh sách đề tài có badge Giữa kì / Cuối kì;
  `TopicsImport` nhận cột `loai_bao_cao` (`giua_ki|midterm|1` ⇒ giữa kì; còn lại/trống ⇒ cuối kì; môn 1 bài ⇒ ép cuối kì).
- **Luật đăng ký/duyệt/gán** (nhóm làm cả 2 bài, KHÔNG bắt buộc thứ tự giữa → cuối):
  - `GroupService::hasApprovedTopic($group, ?$reportType)` đếm theo loại; thêm `approvedTopics()`, `approvedTopicFor()`,
    `syncFinalTopic()`; `destroy()` chặn khi nhóm có bất kỳ đề tài được duyệt.
  - `TopicRegistrationService::register()` chặn **cùng loại**; `approve()` chỉ tự `Rejected` các yêu cầu **cùng loại**
    (duyệt giữa kì KHÔNG làm rớt yêu cầu cuối kì) và chỉ ghi `groups.topic_id` khi duyệt đề tài **cuối kì**; `assignDirectly()` tương tự.
  - **Nguồn sự thật**: `topic_requests.status = Accepted` cho cả 2 loại; `groups.topic_id` = đề tài CUỐI KÌ (tương thích UI/test cũ).
    `Topics::$attributes` mặc định `final` vì Eloquent không tự nạp default của DB (bug phát hiện qua test).
- **Test**: `tests/Feature/TopicReportTypeTest.php` (**7 mới**) + `tests/Feature/SubjectTest.php` (**17**, +8 case mới);
  `php artisan test` ⇒ **330 passed / 4 failed** (4 fail Auth cũ). Bộ test case: **217 case / 14 sheet** (02: 24→26, 05: 47→50).
- **Tuỳ chọn còn lại**: lọc danh sách đề tài theo loại; nhãn loại ở trang sinh viên (`user/topics`, `user/group_topics`);
  bộ lọc thống kê theo loại; bổ sung ERD `docs/diagrams/erd.md` (hiện mới ghi nhận 2 cột mới ở phần migration/notes).

## Ghi chú sửa đổi 2026-10-03 - SỬA IMPORT MÔN HỌC: TỰ DÒ DẤU PHÂN CÁCH CSV (TAB / chấm phẩy)

- **Lỗi**: import môn học thất bại ("Import môn học thành công! Thêm mới: 0, Cập nhật: 0, Lỗi: 1") với file CSV phân cách
  **TAB** (sao chép từ Excel) dù nội dung/định dạng cột đúng (`ten_mon, so_tc, so_bai_bao_cao`).
- **Nguyên nhân gốc**: giống lỗi import đề tài trước đây — Maatwebsite KHOÁ CỨNG dấu phẩy (`MapsCsvSettings::$delimiter`
  + `ReaderFactory::make()` luôn gọi `setDelimiter()`) và dự án KHÔNG có `config/excel.php` nên `config('excel.imports.csv')` rỗng;
  `SubjectsImport` khi đó **chưa** implements `WithCustomCsvSettings` (chỉ `TopicsImport` có) ⇒ cả file bị đọc thành 1 cột ⇒
  `ten_mon` không tồn tại ⇒ rule `required` fail ở mọi dòng.
- **Đã sửa**:
  1. Tách logic dò dấu phân cách ra trait dùng chung `App\Imports\Concerns\DetectsCsvDelimiter` (`csvSettings()`,
     `detectCsvDelimiter()`, `getCsvSettings()`); `TopicsImport` và `SubjectsImport` cùng dùng (hết trùng lặp;
     `TopicsHeadingRowImport` vẫn gọi `TopicsImport::csvSettings()`).
  2. `SubjectsImport` implements thêm `WithCustomCsvSettings` + `SkipsEmptyRows`, nhận `?string $csvDelimiter` qua constructor.
  3. `SubjectController::import()` dò dấu phân cách (chỉ với file `.csv`) rồi truyền vào `new SubjectsImport($delimiter)`
     — giống `TopicController::import()`.
- **Xác minh**: `tests/Feature/SubjectTest.php` lên **21 test** (thêm TAB, chấm phẩy, dòng trống cuối file, dò delimiter);
  `tests/Feature/TopicImportTest.php` **15 passed** (không hồi quy sau khi tách trait);
  `SubjectTest + TopicControllerTest + TopicReportTypeTest + AdminClassManagementTest` ⇒ **71 passed / 0 failed**.
- **Lưu ý cho người dùng**: file `.xlsx` không bị ảnh hưởng. Với `.csv`, nay hệ thống tự dò `,` `;` TAB `|`.

## Ghi chú sửa đổi 2026-10-08 — L05: TRẠNG THÁI ĐANG HỌC / ĐÃ RỜI LỚP + CHỐT 2A (GIỮ TRƯỞNG NHÓM)

- **Trạng thái (user, lớp)**: `user_classes` thêm `status ENUM('studying','left') DEFAULT 'studying'`
  + `left_at NULL` + index `(class_id, status)` (migration `2026_10_07_000002`).
  "Xóa sinh viên khỏi lớp" nay là **xóa mềm**: `UPDATE status='left', left_at=now()` — dòng pivot
  vẫn còn để Admin/GV xem lịch sử (dòng xám + badge "Đã rời lớp"), KHÔNG còn `detach()`.
  "Thêm lại" = `UPDATE status='studying', left_at=NULL` (khôi phục, không sinh dòng trùng UQ).
- **Đọc dữ liệu 2 hướng**: `ClassSection::students()/users()`, `User::classes()` mặc định chỉ lấy
  dòng `studying` (mọi luồng phía sinh viên tự động ẩn lớp đã rời); `ClassSection::allStudents()`
  và `User::allClasses()` lấy TẤT CẢ cho Admin/GV + các thao tác thêm/xóa.
- **Sửa lỗi thêm/xóa sinh viên**: `ClassSectionController::addStudents()/lecturerClassesAddStudents()`
  chuyển sang `DB::transaction` + `user_class::create/update` (trước đây `students()->syncWithoutDetaching()`
  trên relation bị lọc `role` ⇒ thêm lại sinh viên đã rời bị lỗi trùng unique); `removeStudent()/lecturerClassesRemoveStudent()`
  dùng xóa mềm + dọn nhóm trong cùng transaction. `ClassJoinController::joinByCode()` khôi phục `left → studying`.
- **Chốt 2a — giữ trưởng nhóm**: KHÔNG bỏ `groups.leader_id` (không migration `groups`, không null-safe).
  Trưởng nhóm rời lớp mà còn thành viên đang học ⇒ chuyển quyền cho người vào nhóm sớm nhất; người **CUỐI CÙNG**
  rời lớp ⇒ **nhóm được giữ lại**, `leader_id` = người cuối cùng rời (nhóm "ghost" 0 thành viên) để bảo toàn
  lịch sử đề tài/chat/bảng tin; chỉ đóng các lời mời/yêu cầu còn treo.
  Logic tập trung ở `GroupService::transferLeadershipOnLeave()` (dùng chung cho cả luồng Admin xóa SV và
  luồng Admin sửa lớp của SV trong `StudentController`).
- **Đếm thành viên = đếm ĐANG HỌC**: `Groups::activeMemberIds()/activeMemberCount()/hasActiveMembers()`
  — đếm **hợp** (pivot ∪ trưởng nhóm, loại người đã rời lớp) nên hết đếm dư `members + 1` sau khi chuyển
  quyền; mọi view/observer/command dùng lại hàm này (`GroupService::memberCount()`, `GroupObserver`,
  `GroupMemberObserver`, `ClassStreamBackfillCommand`, `groups/*`, `user/*`, `chat/index`).
- **Quay lại lớp (R10)**: trưởng nhóm của nhóm "ghost" nhập lại mã lớp hoặc được Admin/GV "Thêm lại" ⇒
  `GroupService::restoreMembershipOnRejoin()` đưa nhóm cũ **hồi sinh** (không tạo nhóm mới, nhất quán quy tắc
  1 sinh viên = 1 nhóm/lớp). Thành viên thường quay lại dùng lại luồng mời/tham gia nhóm như bình thường.
- **Chặn truy cập nhóm của lớp đã rời**: `UserDashboardController::assertNotLeftGroupClass()` (chi tiết nhóm,
  mời thành viên, yêu cầu tham gia), `GroupChatService::isMember()/hasLeftClass()` + `GroupsChatController::authorizeGroupAccess()`
  (chat nhóm 403), và mọi truy vấn nhóm phía sinh viên scope theo lớp đang học.
- **Xác minh**: `tests/Feature/ClassMembershipStatusTest.php` **12 passed**; full suite **364 passed / 0 failed**
  (baseline trước khi làm: 352 test, trong đó 4 fail cũ đã xử lý).
- **Mục còn để lại (giai đoạn sau)**: khôi phục **thành viên thường** vào nhóm cũ khi quay lại sẽ cần thêm cột
  `group_members.left_at` (soft-leave pivot nhóm) — hiện chưa làm để tránh đụng mọi truy vấn member.

## Ghi chú sửa đổi 2026-10-08 — L06: THAO TÁC NHANH GỬI EMAIL / RESET MẬT KHẨU (ADMIN)

- **Vị trí UI**: card "Thao tác nhanh" (`resources/views/students/show.blade.php`) và cột "Thao tác" mỗi dòng
  (`resources/views/students/index.blade.php`). 2 nút trước đây chỉ là `alert('đang phát triển')` → nay đấu dây thật:
  "Gửi email" mở **modal** (tiêu đề + nội dung) POST `students.send-email`; "Reset mật khẩu" là **form confirm**
  POST `students.reset-password`.
- **Phân quyền (Chốt 5a-B)**: chỉ `role = admin`. Blade bọc `@if(Auth::user()->role === 'admin')` nên giảng viên /
  sinh viên không thấy nút; controller chặn lại bằng `abort_unless(..., 403)` cho trường hợp gọi URL trực tiếp.
- **Reset (Chốt 5b)**: bỏ sinh mật khẩu ngẫu nhiên; luôn `Hash::make('password')` + `must_change_password = true`,
  flash `success` nêu rõ mật khẩu mới. Lần đăng nhập kế tiếp: `AuthController::login()` thấy cờ
  `must_change_password` ⇒ chuyển thẳng tới trang Đổi mật khẩu (`users.profile.password`), KHÔNG vào dashboard;
  `UserController::changePassword()` gỡ cờ sau khi đổi thành công (cả nhánh đổi thường và nhánh reset-token).
- **Đơn lẻ (Chốt 5c)**: chỉ làm từng sinh viên, không checkbox/bulk.
- **Bug thật phát hiện khi đấu dây**: `App\Mail\StudentNotification` khai báo `public string $subject;` trùng tên
  thuộc tính `public $subject` (untyped) của `Illuminate\Mail\Mailable` ⇒ **fatal error** khi khởi tạo ⇒ chức năng
  gửi email chưa từng chạy được. Đã đổi sang `$mailSubject`/`$mailMessage` + truyền `subject`/`message` cho view qua
  `Content::with([...])` (view `emails/student-notification.blade.php` giữ nguyên).
- **Thiếu import**: thêm `use Illuminate\Support\Facades\Log;` trong `StudentController` (trước đây nhánh `catch`
  của `sendEmail()` gọi `Log::error` nhưng chưa import ⇒ sẽ văng `Class not found`).
- **`check-email`**: tài liệu cũ nói route không tồn tại — thực tế `GET /check-student-email`
  (`students.check-email` → `StudentController::checkEmail`) **vẫn còn** trong `routes/web.php`; không cần sửa.
- **Xác minh**: `tests/Feature/StudentQuickActionsTest.php` **7 passed**; full suite **371 passed / 0 failed**
  (baseline sau L05: 364).

## Ghi chú sửa đổi 2026-10-10 — GỠ "GỬI EMAIL", BỎ TRANG SINH VIÊN CỦA ADMIN, CHI TIẾT USER THEO VAI TRÒ

- **Gỡ hẳn tính năng Gửi email**: xóa nút + modal ở `students/show` và `students/index`, route `students.send-email`,
  `StudentController::sendEmail()`, `App\Mail\StudentNotification`, view `emails/student-notification.blade.php`.
  Giữ nguyên **Reset mật khẩu** (L06 5b/5c). `StudentQuickActionsTest` rút 7 → 6 case + thêm khẳng định không còn
  chuỗi "Gửi email".
- **Admin mất trang quản lý sinh viên**: mục "Sinh viên" ẩn khỏi sidebar với admin (`layouts/app.blade.php`);
  `StudentController::index()` redirect admin ⇒ Dashboard (giảng viên giữ nguyên). Lối vào chi tiết sinh viên của
  admin: bảng sinh viên trong `/admin/classes/{id}` — tên sinh viên link sang `students.show`; nút "Quay lại" ở
  `students/show` của admin trỏ về `/admin/classes`.
- **Trang chi tiết user (`/admin/users/{id}`) theo vai trò**: giảng viên ⇒ khối "Lớp học phần" (lớp phụ trách,
  badge `Giảng viên phụ trách`); sinh viên ⇒ lớp (`user_classes.status` Đang học / Đã rời + `left_at`) + khối
  "Nhóm" (gộp `groupsJoined` ∪ `groupsLed` để không bỏ sót trưởng nhóm — vai trò + trạng thái đề tài). Link sang
  `/admin/classes/{id}` và `/groups/{id}`. Test mới: `tests/Feature/AdminUserDetailTest.php` (4).
- **Fix lỗi Blade**: `admin/classes/show` & `lecturer/classes/show` dùng `@if` dính sát chữ (`… đang học@if(...)`)
  ⇒ regex `\B@` của `BladeCompiler` không biên dịch ⇒ header "Sinh viên của lớp (…)" hiện THÔ directive ra HTML.
  Đã thêm khoảng trắng trước `@if`/`@endif`.
- **Xác minh**: `AdminUserDetailTest` **4 passed**; full suite **419 passed / 0 failed** (trước thay đổi: 415).
- **Lưu ý vận hành**: nếu MySQL của Laragon không chạy, mọi lệnh `pest` sẽ treo/kết nối bị từ chối — cần bật MySQL trước khi test.

## Ghi chú sửa đổi 2026-10-10 (lần 2) — TRANG "CÀI ĐẶT" (/settings) + NỐI MENU

- **`GET /settings`** (`users.settings.index` → `SettingsController::index` + view `users/settings/index.blade.php`):
  trang TỔNG QUAN "Thiết lập tài khoản" liệt kê **5 thẻ** (Thông tin chung, Đổi mật khẩu, Bảo mật, Hồ sơ, Riêng tư)
  + thanh tab; chọn layout theo vai trò (sinh viên → `layouts.user`, còn lại → `layouts.app`).
- **`_tabs.blade.php`**: thêm tab đầu **"Tổng quan"** ⇒ từ mọi tab quay lại được trang chủ cài đặt.
- **Nối menu "Cài đặt" (trước đây là link chết `href="#"`)**: sidebar + dropdown user của `layouts/app.blade.php`
  và sidebar + dropdown của `layouts/user.blade.php` (sinh viên nay CÓ mục "Cài đặt") → `users.settings.index`,
  kèm trạng thái active cho `users.settings.*` / `users.profile.*`.
- **Lưu ý vận hành**: thêm route mới thì phải **rebuild route cache**
  (`php artisan route:clear && php artisan route:cache`) — nếu không, `route('users.settings.index')` ném
  `RouteNotFoundException` (đã gặp khi chạy test lần đầu).
- **Xác minh**: `SettingsLevel2Test` **16 passed** (+3 case mới); full suite **422 passed / 0 failed** (trước: 419).

## Ghi chú sửa đổi 2026-10-10 (lần 3) — TINH GỌN "CÀI ĐẶT"

- **Bỏ khối "Gợi ý cài đặt tài khoản"** (5 dòng gợi ý) ở `resources/views/users/profile-admin.blade.php`.
- **Tab Hồ sơ**: bỏ ô **Ngôn ngữ hiển thị** + **Múi giờ** (GIỮ ảnh đại diện: upload/xoá).
  `SettingsController::updateProfile()` đổi `locale`/`timezone` sang **nullable** (client cũ gửi lên vẫn nhận —
  backward-compatible; không gửi ⇒ giữ nguyên giá trị hiện có) nên form chỉ có avatar vẫn lưu được.
- **Tab Bảo mật**: bỏ 2 nút hành động ("Đăng xuất khỏi các phiên khác", "Thu hồi ghi nhớ đăng nhập") khỏi UI
  (route + `SettingsController::revokeOtherSessions()/revokeRememberToken()` VẪN GIỮ, chỉ không còn nút);
  thêm **dòng riêng "Số phiên đăng nhập: N"** và dòng **"Phiên hiện tại"** (IP · thời gian truy cập cuối ·
  User-Agent, khớp theo `currentSessionId`; thiếu dữ liệu ⇒ ghi rõ cần `SESSION_DRIVER=database`);
  bỏ ghi chú "— hiển thị theo múi giờ …". Giữ nguyên bảng **Lịch sử đăng nhập**.
- **Tab Riêng tư**: mục **"Ai được mời tôi vào nhóm?"** chỉ hiển thị với **sinh viên** (admin/giảng viên không có nhóm);
  `SettingsController::updatePrivacy()` đổi `invite_policy` sang **nullable** để admin/giảng viên lưu form không lỗi validation.
- **`/settings`**: cập nhật mô tả thẻ "Hồ sơ" → "Ảnh đại diện.".
- **Xác minh**: `SettingsLevel2Test` **22 passed** (thêm 6 case: 2 case phiên đăng nhập, 1 case form Hồ sơ,
  1 case bỏ khối gợi ý, 2 case invite-policy theo vai trò); full suite **428 passed / 0 failed** (trước: 422).
- **Ghi chú test**: trong môi trường test `SESSION_DRIVER=array` và **session id đổi mỗi request** ⇒ phần
  "Phiên hiện tại" được kiểm chứng bằng cách render view trực tiếp với `currentSession` khớp id (không thể
  chèn trước dòng `sessions` đúng id).

## Ghi chú sửa đổi 2026-10-10 (lần 4) — BỎ TRANG TỔNG QUAN "CÀI ĐẶT" + BIỂU ĐỒ THỐNG KÊ ĐỀ TÀI

### Cài đặt
- **Gỡ trang tổng quan `/settings`**: xóa route `users.settings.index`, `SettingsController::index()`, view
  `users/settings/index.blade.php` và tab "Tổng quan" trong `_tabs.blade.php` (thanh tab trở lại 5 mục).
- **Menu "Cài đặt"** (sidebar + dropdown của `layouts/app.blade.php` và `layouts/user.blade.php`) nay trỏ về
  **`users.profile.info`** (tab Thông tin chung).
- **Tab Bảo mật**: xóa hẳn card "Bảo mật & phiên đăng nhập"; **"Số phiên đăng nhập: N"** được đưa vào card
  **Lịch sử đăng nhập**; bỏ dòng "Phiên hiện tại" (controller chỉ còn truyền `sessionCount`).
- Nhắc lại: xóa route ⇒ phải **rebuild route cache** (`route:clear` + `route:cache`) — đã thực hiện.

### Biểu đồ thống kê đề tài (`admin/statistics/topics`)
- **Chart.js 4 nạp qua CDN** trong `layouts/app.blade.php` (đặt trước `@stack('scripts')`).
  Muốn offline hoàn toàn: `npm i chart.js` + thêm Vite entry `resources/js/charts.js` rồi thay thẻ `<script>`
  (code biểu đồ không phải sửa).
- `StatisticsController::topicStatistics()` tính thêm `$topicsTimeline` (12 tháng gần nhất: **tạo mới** theo tháng +
  **lũy kế**, cộng cả phần trước cửa sổ) và `$topicsByReportType` (final/midterm).
- **5 biểu đồ**: ① Line "Sự gia tăng số lượng đề tài" (tạo mới + lũy kế) · ② Doughnut trạng thái (còn trống /
  đã có nhóm) · ③ Doughnut loại báo cáo · ④ Bar ngang Top 10 giảng viên · ⑤ Bar Top 10 lớp học phần.
- Test: `tests/Feature/Admin/StatisticsTest.php` thêm case kiểm chứng canvas + dữ liệu timeline
  (12 nhãn, tổng tạo mới, lũy kế cuối). Test Cài đặt cập nhật theo việc bỏ trang tổng quan.

## Ghi chú sửa đổi 2026-10-10 (lần 5) — BỎ ÉP ĐỔI MẬT KHẨU KHI RESET + HIỂN THỊ GIỜ THEO MÚI GIỜ NGƯỜI XEM

### Reset mật khẩu KHÔNG ép đổi (L06 · Chốt 5b thay đổi)
- `StudentController::resetPassword()`: `must_change_password` **= false** (không bật mới + gỡ cờ cũ), flash mới
  "…Sinh viên có thể đăng nhập ngay bằng mật khẩu này." ⇒ sinh viên đăng nhập bằng `password` và **vào thẳng dashboard**.
- UI: câu confirm nút Reset ở `students/show` + `students/index` ghi rõ "đăng nhập ngay được (không bắt buộc đổi)".
- Cơ chế "buộc đổi" (middleware `EnsurePasswordIsChanged` + gate ở `AuthController::login()`) **vẫn giữ** nhưng không còn
  nguồn nào bật cờ; test chuyển sang **set cờ trực tiếp** để kiểm chứng.
- Test: `StudentQuickActionsTest` cập nhật 4 case (reset ⇒ cờ false + vào thẳng dashboard; 2 case cơ chế set cờ trực tiếp).

### Múi giờ hiển thị (Bó-2) — đồng bộ toàn hệ thống
- Thêm `->displayTz()` cho ~30 chỗ hiển thị mốc thời gian ở ~20 view: **`admin/chat-monitoring` (4 chỗ, gồm `flagged_at`)**
  · chat 1-1 (`chat/partials/messages` — cả nhãn "Hôm nay/Hôm qua" + `data-message-date` + tooltip) · chat nhóm
  (`groups/chat`, `groups/show`) · `chat/index` · admin users (`index`, `show` — created_at/left_at/lịch sử MK) ·
  lịch sử đổi MK (`users/profile-password`, `profile-admin-password`) · lớp học phần (`admin|lecturer classes/show` —
  `left_at`) · thông báo · đề tài/yêu cầu đăng ký · dashboard "hôm nay" (`dashboard/admin`) · `dashboard/class-detail` ·
  `students/show` · lời mời/yêu cầu (SV).
  Trước đây các chỗ này in **giờ UTC thô** ⇒ admin/người dùng thấy lệch giờ địa phương (vd −7h với `Asia/Ho_Chi_Minh`).
  **Dữ liệu trong DB vẫn UTC** (không đổi) — chỉ đổi khâu hiển thị.

### Deadline đề tài — chuẩn hoá 2 CHIỀU
- `App\Support\DisplayTime::toUtc()` (mới): input `datetime-local` / ô `han_dang_ky` khi import được hiểu theo
  **múi giờ người nhập** rồi đổi sang UTC.
- Áp dụng ở `TopicController::store()/update()` và `App\Imports\TopicsImport::parseDeadline()`.
- Hiển thị + **prefill form sửa** (`topics/edit`) dùng `displayTz()` ⇒ round-trip đúng: người nhập thấy lại đúng giá trị
  đã gõ, dữ liệu vẫn chuẩn UTC.
- Không cần migration; test hiện có (`TopicImportTest`) giữ nguyên kết quả vì user trong test không đặt `timezone`
  (múi giờ hiển thị = UTC).

## Ghi chú sửa đổi 2026-10-10 (lần 6) — MẶC ĐỊNH MÚI GIỜ HIỂN THỊ + SỬA NHÃN NGÀY CHAT

**Bối cảnh/lỗi**: sau khi thêm `displayTz()` cho ~20 view (lần 5), giờ vẫn hiển thị **UTC** (chat hiện `11:28` thay vì
`18:28`) vì `config('app.display_timezone')` **chỉ được set khi `users.timezone` có giá trị** — mà **ô cài múi giờ đã bị bỏ**
khỏi tab Hồ sơ (lần 3) ⇒ hầu hết user (gồm admin) có `timezone = NULL` ⇒ `DisplayTime::timezone()` rơi về
`config('app.timezone')` = UTC ⇒ **mọi `displayTz()` thành no-op**. Phụ: `$dateKey` tách ngày trong chat còn dùng
`toDateString()` theo UTC nên nhãn ngày lệch với giờ hiển thị.

**Sửa**:
- `config/app.php`: thêm **`'display_timezone' => env('APP_DISPLAY_TIMEZONE', 'Asia/Ho_Chi_Minh')`**;
  `.env` + `.env.example` thêm `APP_DISPLAY_TIMEZONE=Asia/Ho_Chi_Minh`. `timezone` (lưu trữ) **vẫn UTC**;
  `users.timezone` (nếu có) vẫn override qua middleware `SetLocale`.
- `resources/views/chat/partials/messages.blade.php`: `$dateKey = $message->created_at?->displayTz()?->toDateString()`
  ⇒ nhãn "Hôm nay/Hôm qua/dd/mm/yyyy" và `data-message-date` (dùng khi "Tải thêm tin nhắn cũ") cùng một múi giờ.
- **Sau khi sửa `.env` PHẢI chạy `php artisan config:clear`** (đã thực hiện; `php artisan config:show app` xác nhận
  `display_timezone = Asia/Ho_Chi_Minh`).
- Không đổi `app.timezone` (UTC) ⇒ dữ liệu + logic so sánh không đổi; test `SettingsLevel2Test` (user có tz `Asia/Bangkok`)
  vẫn pass vì `SetLocale` override.

## Ghi chú sửa đổi 2026-10-08 — L07: MÃ LỚP THỐNG NHẤT 5 KÝ TỰ (ADMIN + GIẢNG VIÊN)

- **Sinh mã (app)**: `ClassSectionController::generateUniqueClassCode()` (alphabet `ABCDEFGHJKLMNPQRSTUVWXYZ23456789` —
  bỏ `I,O,0,1` để khỏi nhầm, đảm bảo duy nhất qua `do…while exists`) dùng CHUNG cho `store()` (Admin) và
  `lecturerStore()` (Giảng viên); method cũ `generateClassCode()` (format `{subject_code}-NN`) đã bị xóa.
- **Tham gia lớp**: `ClassJoinController::joinByCode()` validate `size:5` kèm thông báo tiếng Việt
  “Mã lớp gồm đúng 5 ký tự. Vui lòng kiểm tra lại mã được giảng viên cung cấp!”.
- **Dữ liệu cũ**: migration `2026_10_07_000001_normalize_class_code_5_chars` quy đổi mọi `class_code`
  NULL/rỗng hoặc khác 5 ký tự (đã chạy). Liên kết `user_classes`/`groups`/`topics` theo `class_id` nên không ảnh hưởng.
- **Dọn dẹp còn sót (2026-10-08, đợt này)**: `DatabaseSeeder` còn tạo lớp với mã dài `WEB-K1-2026` **và** ghi 2 cột
  đã bị xóa khỏi schema (`users.isHaveGroup`, `subjects.lecturer_id`) ⇒ seeder không chạy được; đã sửa (mã `LTWEB`,
  bỏ 2 cột cũ). `LecturerClassTest` còn dùng mã `'MA1-'.uniqid()` ⇒ đổi về `DUPXY`. `AdminClassManagementTest` siết
  thêm khẳng định mã admin tự sinh khớp `/^[A-Z0-9]{5}$/`.
- **Xác minh**: `tests/Feature/ClassCodeFiveCharsTest.php` (5 case: admin/GV sinh mã 5 ký tự + duy nhất, join chặn mã
  4/6 ký tự, seeder + fixtures không sinh mã lệch, không còn `generateClassCode()`); full suite **376 passed / 0 failed**.

## Ghi chú sửa đổi 2026-10-08 — L08: CHAT SONG SONG + HIỆN TRƯỚC GẮN CỜ SAU

- **(a) Song song**: `app/Services/ChatModerationService.php` (mới) chạy 3 bộ lọc bằng `Http::pool()` — fraud 8889 +
  sensitive 8890 + vision 8888 (chỉ khi có ảnh) — thay cho 3 lời gọi NỐI TIẾP. Timeout giảm: text/sensitive 3s→2s,
  vision 15s→5s (cấu hình qua `services.*_moderation.timeout` / `services.vision.timeout`).
  Để dùng được pool, 2 service kiểm duyệt tách thêm `needsModel()`, `parseModelResponse()`, `combineWithModel()`;
  `ImageModerationService` tách `requestSpec()` + `parseResponse()`. `check()` (đường tuần tự) giữ nguyên hành vi.
- **(c) Hiện trước — gắn cờ sau**: `DirectChatController::send()` và `GroupChatService::send()` nay INSERT tin **sạch**
  (is_flagged=false mặc định) → bump unread → broadcast → `ModerateXMessage::dispatch($id)->afterResponse()`.
  2 job mới `app/Jobs/ModerateDirectMessage.php` + `ModerateGroupMessage.php` gọi `ChatModerationService::analyze()`
  rồi `update()` 4 cột cờ. Job KHÔNG `ShouldQueue` ⇒ `afterResponse()` chạy sau khi đã trả response, **không cần worker**.
- **Fail-open giữ nguyên**: `Http::pool()` ném `ConnectionException` khi 1 server AI chết ⇒ `runPool()` bắt lại,
  coi như không có kết quả model và rơi về rules; tin nhắn không bao giờ bị chặn/treo.
- **Xác minh**: `tests/Feature/ChatModerationAsyncTest.php` (5 case); full suite **381 passed / 0 failed**
  (48 test chat/kiểm duyệt cũ vẫn xanh vì test dùng `QUEUE_CONNECTION=sync`).

## Ghi chú sửa đổi 2026-10-08 — L09: THIẾT LẬP TÀI KHOẢN CHUẨN SAAS (MỨC 2)

- **Hạ tầng**: migration `2026_10_08_000001` thêm `users.locale/timezone/hide_online/invite_policy/avatar_path` +
  bảng `login_histories`; migration `2026_10_08_000002` **dựng lại bảng `sessions`** đúng chuẩn Laravel (bảng cũ chỉ có
  `id + timestamps`) và `.env` chuyển `SESSION_DRIVER=database` — dòng ghi chú "không được dùng" đã hết hiệu lực.
  ⚠️ Đổi driver ⇒ mọi phiên đang đăng nhập phải login lại.
- **2.1 Bảo mật**: `LoginHistoryService::record()` gọi trong `AuthController::login()` (ghi IP/user_agent, giữ 30 bản ghi,
  fail-open) + flash cảnh báo khi **IP mới**. `SettingsController::revokeOtherSessions()` xoá các dòng `sessions` của
  chính mình (trừ phiên hiện tại); `revokeRememberToken()` đổi `remember_token` (vô hiệu cookie "ghi nhớ" mọi thiết bị).
- **2.2 Hồ sơ**: upload/xoá avatar (`storage/app/public/avatars`, ≤2MB, accessor `avatar_url`, fallback chữ cái đầu);
  `SetLocale` middleware áp dụng `users.locale` (`vi|en`) + `users.timezone` cho mỗi request.
- **2.3 Riêng tư**: danh sách chặn + bỏ chặn ngay trong Cài đặt; `hide_online` được `PresenceService` tôn trọng
  (`isOnline=false`, nhãn "Ẩn"); `invite_policy` (`everyone|classmates|none`) được `InvitationService::checkInvitePolicy()`
  kiểm tra trước khi tạo lời mời.
- **UI**: `resources/views/users/settings/{_tabs,security,profile,privacy}.blade.php` (mới) — thanh 5 tab dùng chung
  (Thông tin chung | Đổi mật khẩu | Bảo mật | Hồ sơ | Riêng tư); 4 trang profile cũ dùng lại thanh tab này; card
  "Gợi ý cài đặt tài khoản" (text chết) thay bằng 5 link thật. View chọn layout theo vai trò (`layouts.user`/`layouts.app`).
- **Xác minh**: `tests/Feature/SettingsLevel2Test.php` (11 case); full suite **392 passed / 0 failed**.

## Ghi chú sửa đổi 2026-10-08 — L10: XÓA MỀM NHÓM (ADMIN/GV) + NHẢ ĐỀ TÀI

- **DB/Model**: migration `2026_10_08_000003` thêm `groups.deleted_at`; `Groups` dùng `SoftDeletes` ⇒ mọi truy vấn
  `Groups::…` (và relation `group_members.group`, `topic_requests.group`, `chat_messages.group`…) tự động loại nhóm
  đã xóa ⇒ logic "1 nhóm / 1 lớp" đúng ngay, không phải sửa từng chỗ.
- **Service**: `GroupService::destroy()` nay XÓA MỀM + `releaseTopic()` (`groups.topic_id → NULL`,
  `topics.assigned_group_id → NULL`, `topic_requests` Pending/Accepted → `Cancelled` + lý do) + lời mời/yêu cầu treo
  → `Expired` (GIỮ dòng để đối chiếu). **Bỏ chặn cứng "nhóm đã gán đề tài"** (theo chốt: đề tài về chưa đăng ký).
  Thêm `restore()` (về chưa đề tài, tính lại `status` theo thành viên) và `forceDelete()` (chỉ Admin; chat/bảng tin
  xóa theo FK CASCADE). GV chỉ xóa/khôi phục được nhóm thuộc lớp mình phụ trách.
- **Route/View**: thêm `DELETE groups/{id}` (`groups.destroy`), `POST groups/{id}/restore`, `DELETE groups/{id}/force`;
  **sửa lỗi thiếu `middleware('auth')`** ở nhóm route `groups.*` (khách vào `/groups` gây HTTP 500 vì `Auth::user()`
  là null) ⇒ nay chuyển hướng về `/login`; view `groups.index` có tab "Đang hoạt động | Đã xóa" + nút Xóa/Khôi phục/
  Xóa vĩnh viễn (ẩn với sinh viên), `groups.show` có nút "Xóa nhóm".
- **Xác minh**: `tests/Feature/GroupSoftDeleteTest.php` (8 case); `GroupServiceTest` case cũ "không thể xóa nhóm đã
  được gán đề tài" được cập nhật theo chốt mới; full suite **400 passed / 0 failed**.
- **Tài liệu test-case cũng được đồng bộ**: 5 case `TC-AUTH` từng đánh dấu **Fail** (L01–L04) đã chuyển **Pass**
  (kèm ghi chú commit tương ứng) và mục "#1" ở bảng trên đã bỏ ghi chú "test đỏ vì môi trường mail" (đã lỗi thời).

## Ghi chú sửa đổi 2026-10-08 — HARDENING SAU RÀ SOÁT (Bó-1 → Bó-4)

Rà soát lại toàn hệ thống sau khi hoàn thành L01–L11; các lỗi dưới đây đều đã sửa + có test tự động.

### Bó-1 — Lỗi đang chặn người dùng (route/view)

- `GET /groups/{groupId}/chat` (`groups.chat.show`) **thiếu middleware `auth`** ⇒ KHÁCH truy cập gây **HTTP 500**
  (`GroupsChatController::isAdmin()` đọc `Auth::user()->role` khi user = null). Nay bắt buộc đăng nhập.
- `GET /invites/{id}/approve` trỏ tới `InviteController::approve()` **KHÔNG tồn tại** ⇒ bấm "Duyệt" ở trang
  `/invites` là HTTP 500; `GET /invites/{id}/reject` là GET đổi trạng thái (CSRF) + `Auth::user()` null.
  ⇒ **GỠ** 2 route cũ; trang `/invites` dùng form **POST** đúng luồng `user.accept-invite` / `user.reject-invite`;
  `invites.index` vào nhóm `auth`; bỏ 3 method chết của `InviteController`.
- `GET /requests` render `view('requests')` **KHÔNG tồn tại** ⇒ **GỠ** route chết.
- Test: `tests/Feature/RouteGuardsTest.php`.

### Bó-2 — Bất biến thời gian (lỗi do L09 gây ra)

- `SetLocale` gọi `date_default_timezone_set($user->timezone)` ⇒ (1) mọi timestamp ghi trong request đó **lệch giờ**
  so với UTC, (2) **rò rỉ** timezone sang request của user khác trên cùng PHP-FPM worker,
  (3) badge chưa đọc (`chat_messages.created_at` vs `group_chat_reads.last_read_at`) và online/offline
  (`PresenceService`) sai giữa 2 người khác múi giờ.
- Sửa: **luôn** reset về `config('app.timezone')`; múi giờ người dùng chỉ lưu vào `config('app.display_timezone')`;
  hiển thị qua macro `Carbon::displayTz()` / `App\Support\DisplayTime` (đã dùng ở bảng Lịch sử đăng nhập).
- Test: `SettingsLevel2Test` (khoá bất biến `created_at`/`last_seen_at` ≈ now() UTC + nhãn múi giờ).

### Bó-3 — L10 hardening

- `GroupObserver::restored()` (mới) ⇒ khôi phục nhóm ghi **"Nhóm X đã được khôi phục."** vào bảng tin lớp
  (trước đây bảng tin vẫn nói "đã giải tán" dù nhóm hoạt động lại).
- `GroupController::index()/show()` giới hạn theo lớp của người dùng cho MỌI vai trò ≠ admin (trước đây
  **sinh viên gõ URL xem được nhóm của mọi lớp**). Sinh viên không có link tới `/groups` nên không phá UI.
- Đính chính tài liệu: `class_posts.group_id` là **SET NULL** (không CASCADE) — xóa cứng nhóm không xóa bài bảng tin.
- Test: `GroupSoftDeleteTest` (+2 case).

### Bó-4 — Nợ kỹ thuật / phòng ngừa

- **L06**: thêm middleware `EnsurePasswordIsChanged` — cờ `must_change_password` nay **CHẶN mọi trang khác**
  cho tới khi đổi mật khẩu (trước đây chỉ gate mềm ở bước đăng nhập ⇒ đổi URL là vào được).
- **L07**: siết DB `class_sections.class_code` thành **`CHAR(5) NOT NULL`** (migration `2026_10_08_000004`,
  có bước quy đổi an toàn trước khi `change()`) + `createClassWithUniqueCode()` tự **thử lại 3 lần** khi đua unique.
- GỠ route **trùng tên `dashboard`** (`/dashboard/dashboard` làm `route('dashboard')` sinh URL lặp) và route
  `admin/classes` khai báo 2 lần; XOÁ code chết `ImageModerationService::checkImageUrls()`;
  guard `Schema::hasTable('sessions')` ở tab Bảo mật (DB chưa migrate không còn 500).
- Test: `StudentQuickActionsTest`, `ClassCodeFiveCharsTest`, `RouteGuardsTest` (+4 case).
- **Rủi ro CHẤP NHẬN** (ghi rõ trong doc L08/L09): `Http::pool` all-or-nothing; cờ gắn sau response phụ thuộc
  `terminating()`; `sendEmail` gửi mail đồng bộ (không phụ thuộc worker); fail-open nuốt mọi Throwable;
  `users.locale` chưa dịch chuỗi UI; `SESSION_DRIVER=database` phải set ở từng môi trường.

**Xác minh**: `php artisan test` ⇒ **412 passed / 0 failed** (trước hardening: 400).

---

## Ghi chú sửa đổi 2026-10-09 — P1 TỐI ƯU HIỆU NĂNG GIAO DIỆN (D1 → D5)

Kế hoạch chi tiết: `docs/Final-Bug-Fixes/P1-toi-uu-hieu-nang-giao-dien.md` (không đánh số feature).

- **D1 — CDN → local (Vite)**: Bootstrap + Font Awesome + `marked` đóng gói từ npm; sửa luôn 4 trang auth, `layouts/guest` (entry `fa.css` riêng vì dựng bằng Tailwind), `components/chatbot`; **test quét toàn bộ views** không còn domain CDN.
- **D2 — Cache server**: `route:cache` + `view:cache` + `event:cache` đã bật (2 route closure → `HomeController`); `.env` đổi `CACHE_STORE=database → file`; OPcache bật ở `php.ini` Laragon (file ngoài repo — mỗi máy tự bật + restart web server). **KHÔNG dùng `config:cache`** (nuốt env của phpunit).
- **D3 — Chống N+1**: `students.index` (`withCount`/`with`) và `groups.index` (`GroupService::memberStatsFor` gộp per-group); badge tin nhắn **giữ tươi, không cache** (đổi lấy 4 query indexed — tránh invalidate đa điểm sai lệch giữa 2 thiết bị).
- **D4 — Không chặn response**: 6 điểm broadcast (chat 1-1/nhóm, bảng tin, tick đã xem, thông báo) bọc `App\Support\AfterResponse::broadcast()` — chạy phase `terminating`, không cần queue worker, fail-open; `MAIL_TIMEOUT=10` cho SMTP (trước `null` = treo vô hạn).
- **D5 — Đo**: chưa cài Debugbar/Telescope/Pulse (tuỳ máy dev) — hướng dẫn ở mục D5 của doc P1.

**Xác minh**: `PerformanceBatchTest` **4 passed**; full suite **416 passed / 0 failed**. Test-case: nhóm **12 · TC-PERF** (6 case) → tổng **239 case / 12 nhóm**.

