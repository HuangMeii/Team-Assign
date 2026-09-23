# Nhóm 07 — Kiểm duyệt nội dung & giám sát chat

> **Mã nhóm**: `TC-MOD` · **Chức năng**: FEATURE_STATUS #13 (kiểm duyệt 3 tầng, flag-only), #14 (Admin giám sát chat: tab Bị gắn cờ, bỏ cờ, xóa, broadcast, gửi thông báo vào nhóm)
> **Số test case**: 27 — Pass: **26** · Fail: **0** · Chưa chạy tay: **1**
> **Môi trường**: MySQL team_assign_test; 3 tầng rules chạy offline (không cần service AI). Muốn kiểm tra tầng model: chạy `AI-Services/start-servers.ps1` (Vision 8888 · fraud 8889 · moderation 8890) và đặt `TEXT_MODERATION_MODE` / `MODERATION_MODE` = model|hybrid.
> ↻ File này **sinh tự động** từ `docs/test-cases/data/07-kiem-duyet-noi-dung.php` — sửa dữ liệu ở đó rồi chạy `php artisan testcases:export` (đừng sửa file .md này).

## 1. Mục tiêu & phạm vi

Kiểm thử 3 tầng kiểm duyệt nội dung (rules → PhoBERT fraud :8889 → PhoBERT moderation 5 nhãn :8890 → Vision :8888 cho ảnh) theo nguyên tắc FLAG-ONLY: tin nhắn nhạy cảm VẪN gửi được, chỉ bị gắn cờ; và bộ công cụ giám sát chat của Admin (badge, bỏ cờ, xóa tin, broadcast, gửi thông báo/cảnh báo vào khung chat nhóm).

## 2. Bảng tóm tắt test case

| Mã TC | Tên / Mục tiêu | Role | Loại | Ưu tiên | Trạng thái |
|---|---|---|---|---|---|
| `TC-MOD-01` | Tin nhắn học tập bình thường KHÔNG bị gắn cờ (tránh dương tính giả) | Hệ thống | API | Cao | Pass |
| `TC-MOD-02` | Gắn cờ tin nhắn bán đề tài kèm giá và liên hệ riêng (mẫu trong dataset) | Hệ thống | API | Cao | Pass |
| `TC-MOD-03` | Vẫn gắn cờ khi người dùng viết hoa hoặc bỏ dấu để né bộ lọc | Hệ thống | API | Trung bình | Pass |
| `TC-MOD-04` | Một câu chứa nhiều loại từ khoá trả NHIỀU nhãn cùng lúc | Hệ thống | API | Trung bình | Pass |
| `TC-MOD-05` | FlagHelper merge 3 đầu vào (text rules + ảnh Vision + sensitive) và CHỈ gắn cờ, không xoá | Hệ thống | API | Cao | Pass |
| `TC-MOD-06` | Chuẩn hoá danh sách `violations` dạng mảng/rỗng từ Vision | Hệ thống | API | Trung bình | Pass |
| `TC-MOD-07` | Đọc ngưỡng + dataset từ thư mục violation-detection/ (không hard-code trong code) | Hệ thống | API | Trung bình | Pass |
| `TC-MOD-08` | mode = model: gọi server PhoBERT và dùng kết quả trả về (fraud 8889 + moderation 8890) | Hệ thống | API | Cao | Pass |
| `TC-MOD-09` | mode = model: server AI chết thì fallback về rules — chat KHÔNG bị treo | Hệ thống | API | Cao | Pass |
| `TC-MOD-10` | Chat nhóm: tin nhắn xúc phạm VẪN gửi được và chỉ bị gắn cờ sensitive | Sinh viên | API | Cao | Pass |
| `TC-MOD-11` | Chat nhóm: tin nhắn sạch KHÔNG bị gắn cờ | Sinh viên | API | Cao | Pass |
| `TC-MOD-12` | Chat cá nhân: tin nhắn nhạy cảm/gian lận vẫn gửi được và chỉ bị gắn cờ | Sinh viên | API | Cao | Pass |
| `TC-MOD-13` | Ảnh nhạy cảm VẪN gửi được, KHÔNG bị xoá, chỉ gắn cờ (Vision :8888) | Sinh viên | API | Cao | Pass |
| `TC-MOD-14` | mode = model: server 8890 trả multi-label trong luồng chat thì dùng kết quả đó | Sinh viên | API | Trung bình | Pass |
| `TC-MOD-15` | Badge Giám sát Chat cộng cả tin nhắn cá nhân lẫn nhóm bị gắn cờ khi admin chưa xem; mở tab thì set mốc và badge về 0 | Admin | API | Cao | Pass |
| `TC-MOD-16` | Admin xem tab “Bị gắn cờ” và bỏ cờ được tin nhắn cá nhân/nhóm (không xoá tin) | Admin | API | Cao | Pass |
| `TC-MOD-17` | Admin xoá tin nhắn bị gắn cờ đã xác nhận vi phạm | Admin | API | Cao | Pass |
| `TC-MOD-18` | Admin broadcast tin nhắn tới một nhóm hoặc toàn hệ thống | Admin | API | Trung bình | Pass |
| `TC-MOD-19` | Admin gửi thông báo/cảnh báo vào khung chat nhóm mà KHÔNG cần là thành viên | Admin | API | Cao | Pass |
| `TC-MOD-20` | Cảnh báo của admin không bị bộ kiểm duyệt gắn cờ và payload realtime/polling mang đúng type | Admin | API | Trung bình | Pass |
| `TC-MOD-21` | Sinh viên/khách không vào được trang giám sát chat và không dùng được route gửi thông báo (case âm) | Sinh viên | API | Cao | Pass |
| `TC-MOD-22` | Tab “Bị gắn cờ” không lộ cột Lý do / Điểm; mở tab Chat cá nhân/nhóm KHÔNG reset badge | Admin | UI | Trung bình | Pass |
| `TC-MOD-23` | Link “Giám sát Chat” trên sidebar admin mở thẳng tab Bị gắn cờ và badge có URL đếm riêng | Admin | UI | Thấp | Pass |
| `TC-MOD-24` | Admin xem mọi khung chat nhóm, không cộng badge chưa đọc và tìm được theo tên | Admin | UI | Trung bình | Pass |
| `TC-MOD-25` | Validate dữ liệu gửi thông báo: thiếu nội dung, sai loại tin, nhóm không tồn tại đều bị từ chối (case âm) | Admin | API | Trung bình | Pass |
| `TC-MOD-26` | Danh sách kiểm duyệt vẫn hoạt động khi AJAX lỗi (không phụ thuộc JS) | Admin | UI | Thấp | Pass |
| `TC-MOD-27` | Cảnh báo / thông báo của Admin trong khung chat KHÔNG tự biến mất sau khi load trang | Sinh viên | UI | Cao | Chưa chạy tay |

## 3. Chi tiết test case

### TC-MOD-01 — Tin nhắn học tập bình thường KHÔNG bị gắn cờ (tránh dương tính giả)

- **Chức năng**: Rules — nội dung sạch (#13) · **Role**: Hệ thống · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Chạy test không cần service AI (mode = rules)
- **Các bước thực hiện**:
  1. Đưa các câu trao đổi học tập bình thường qua bộ kiểm duyệt
  2. Đọc kết quả trả về
- **Dữ liệu đầu vào**: “Mai nộp báo cáo chương 2, mọi người nhớ kiểm tra phần mở đầu nhé.”
- **Kết quả mong đợi**: is_flagged = false; flag_reason rỗng; điểm dưới ngưỡng
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Unit/ViolationDetectionTest.php + tests/Unit/SensitiveModerationTest.php`

### TC-MOD-02 — Gắn cờ tin nhắn bán đề tài kèm giá và liên hệ riêng (mẫu trong dataset)

- **Chức năng**: Rules — gian lận học thuật (#13) · **Role**: Hệ thống · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: mode = rules; dataset `violation-detection/` có mẫu tương ứng
- **Các bước thực hiện**:
  1. Đưa câu mẫu trong dataset qua bộ kiểm duyệt
  2. Kiểm tra lý do gắn cờ
- **Dữ liệu đầu vào**: “Ai cần mua đề tài liên hệ zalo 09xxx, giá 500k”
- **Kết quả mong đợi**: is_flagged = true; flag_reason chứa `fraud:` + nhãn gian lận; điểm ≥ ngưỡng
- **Kiểm tra thêm (DB / log / API)**: So khớp ngưỡng đọc từ `violation-detection/config/thresholds.json`
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Unit/ViolationDetectionTest.php`

### TC-MOD-03 — Vẫn gắn cờ khi người dùng viết hoa hoặc bỏ dấu để né bộ lọc

- **Chức năng**: Rules — viết hoa/không dấu (#13) · **Role**: Hệ thống · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: mode = rules
- **Các bước thực hiện**:
  1. Đưa các biến thể viết hoa / không dấu của từ khóa
  2. Kiểm tra kết quả
- **Dữ liệu đầu vào**: “LAM HO BAO CAO 500K”, “may ngu nhu cho, im mom di”
- **Kết quả mong đợi**: Chuẩn hóa chữ thường + bỏ dấu trước khi so khớp ⇒ is_flagged = true như bản có dấu
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Unit/ViolationDetectionTest.php + tests/Unit/SensitiveModerationTest.php`

### TC-MOD-04 — Một câu chứa nhiều loại từ khoá trả NHIỀU nhãn cùng lúc

- **Chức năng**: Rules — đa nhãn (#13) · **Role**: Hệ thống · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: mode = rules
- **Các bước thực hiện**:
  1. Đưa câu trộn nhiều nhóm từ khoá (xúc phạm + gian lận)
  2. Đọc danh sách nhãn trả về
- **Dữ liệu đầu vào**: Câu có cả từ xúc phạm và từ bán đề tài
- **Kết quả mong đợi**: Kết quả gồm nhiều nhãn (không chỉ nhãn đầu tiên); `flag_reason` ghi đủ các nhãn
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Unit/SensitiveModerationTest.php`

### TC-MOD-05 — FlagHelper merge 3 đầu vào (text rules + ảnh Vision + sensitive) và CHỈ gắn cờ, không xoá

- **Chức năng**: FlagHelper gộp nguồn (#13) · **Role**: Hệ thống · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Chuẩn bị dữ liệu: text có cờ, ảnh có violations, sensitive có nhãn
- **Các bước thực hiện**:
  1. Gọi FlagHelper với cả 3 nguồn
  2. Kiểm tra 4 cột trả về
- **Dữ liệu đầu vào**: text + image + sensitive
- **Kết quả mong đợi**: Luôn trả đủ 4 cột (is_flagged, flag_reason, moderation_score, flagged_at); gộp lý do và lấy ĐIỂM CAO NHẤT; không có hành vi xoá/chặn gửi
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Unit/ViolationDetectionTest.php + tests/Unit/SensitiveModerationTest.php`

### TC-MOD-06 — Chuẩn hoá danh sách `violations` dạng mảng/rỗng từ Vision

- **Chức năng**: FlagHelper gộp nguồn (#13) · **Role**: Hệ thống · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Nhiều biến thể payload từ Vision: mảng rỗng, thiếu key, chuỗi
- **Các bước thực hiện**:
  1. Gọi FlagHelper với từng biến thể
  2. Kiểm tra kết quả
- **Dữ liệu đầu vào**: violations = [] / null / ["adult"]
- **Kết quả mong đợi**: Không ném exception; biến thể rỗng ⇒ không gắn cờ ảnh; có nhãn ⇒ gắn cờ kèm tiền tố `image:`
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Unit/ViolationDetectionTest.php`

### TC-MOD-07 — Đọc ngưỡng + dataset từ thư mục violation-detection/ (không hard-code trong code)

- **Chức năng**: Cấu hình ngưỡng (#13) · **Role**: Hệ thống · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: File `violation-detection/config/thresholds.json` tồn tại
- **Các bước thực hiện**:
  1. Sửa tạm 1 ngưỡng trong file json
  2. Chạy lại bộ kiểm duyệt với câu sát ngưỡng
  3. Khôi phục file
- **Dữ liệu đầu vào**: thresholds.json
- **Kết quả mong đợi**: Hành vi gắn cờ thay đổi theo ngưỡng trong file; không cần sửa code PHP
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Unit/ViolationDetectionTest.php + tests/Unit/SensitiveModerationTest.php`

### TC-MOD-08 — mode = model: gọi server PhoBERT và dùng kết quả trả về (fraud 8889 + moderation 8890)

- **Chức năng**: Tầng model PhoBERT (#13) · **Role**: Hệ thống · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Http::fake() mô phỏng server trả kết quả vi phạm / trả sạch
- **Các bước thực hiện**:
  1. Đặt mode = model
  2. Gửi câu qua bộ kiểm duyệt
  3. Kiểm tra cờ theo kết quả server
- **Dữ liệu đầu vào**: mode=model
- **Kết quả mong đợi**: Server báo vi phạm ⇒ gắn cờ; server báo sạch ⇒ không gắn cờ (kết quả model được ưu tiên)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Unit/ViolationDetectionTest.php + tests/Unit/SensitiveModerationTest.php`
- **Ghi chú**: Khi kiểm tra thật cần bật service: `AI-Services/start-servers.ps1` (8888/8889/8890).

### TC-MOD-09 — mode = model: server AI chết thì fallback về rules — chat KHÔNG bị treo

- **Chức năng**: Fail-open khi AI chết (#13) · **Role**: Hệ thống · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Http::fake() trả lỗi/timeout; mode = model và hybrid
- **Các bước thực hiện**:
  1. Gửi tin nhắn qua luồng chat khi server AI lỗi
  2. Kiểm tra tin nhắn đã được lưu
- **Dữ liệu đầu vào**: mode=model / hybrid với server 8890 (hoặc 8889) lỗi
- **Kết quả mong đợi**: Không exception; tin nhắn VẪN được gửi và lưu; kết quả gắn cờ do rules quyết định
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Unit/ViolationDetectionTest.php + tests/Unit/SensitiveModerationTest.php`

### TC-MOD-10 — Chat nhóm: tin nhắn xúc phạm VẪN gửi được và chỉ bị gắn cờ sensitive

- **Chức năng**: Chat flag-only (#13) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: mode = rules; sv1 là trưởng nhóm Alpha
- **Các bước thực hiện**:
  1. Gửi tin nhắn xúc phạm vào khung chat nhóm
  2. Kiểm tra tin vẫn hiển thị
  3. Kiểm tra cột cờ trong DB
- **Dữ liệu đầu vào**: content=“May ngu nhu cho, im mom di.”
- **Kết quả mong đợi**: HTTP 200, tin nhắn hiển thị bình thường (KHÔNG bị chặn/xoá); chỉ bật cờ với lý do `sensitive:insult…`
- **Kiểm tra thêm (DB / log / API)**: chat_messages: is_flagged = 1, flag_reason chứa `sensitive:` + `insult`, moderation_score ≥ 0.5, flagged_at ≠ NULL
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/SensitiveModerationChatTest.php`

### TC-MOD-11 — Chat nhóm: tin nhắn sạch KHÔNG bị gắn cờ

- **Chức năng**: Chat flag-only (#13) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: mode = rules
- **Các bước thực hiện**:
  1. Gửi tin nhắn trao đổi học tập bình thường
- **Dữ liệu đầu vào**: content=“Mai nộp báo cáo chương 2, mọi người nhớ kiểm tra phần mở đầu nhé.”
- **Kết quả mong đợi**: Tin hiển thị bình thường; is_flagged = 0 và không có flagged_at
- **Kiểm tra thêm (DB / log / API)**: chat_messages.is_flagged = 0
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/SensitiveModerationChatTest.php + tests/Feature/Services/ViolationDetectionTest.php`

### TC-MOD-12 — Chat cá nhân: tin nhắn nhạy cảm/gian lận vẫn gửi được và chỉ bị gắn cờ

- **Chức năng**: Chat flag-only (#13) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: mode = rules; 2 tài khoản đang chat 1-1
- **Các bước thực hiện**:
  1. Gửi tin nhắn nhạy cảm và tin nhắn gian lận (2 lần)
  2. Kiểm tra khung chat và DB
- **Dữ liệu đầu vào**: content nhạy cảm / “Ai cần mua đề tài liên hệ zalo 09xxx, giá 500k”
- **Kết quả mong đợi**: Cả 2 tin đều gửi thành công; chỉ bật cờ tương ứng `sensitive:` / `fraud:`
- **Kiểm tra thêm (DB / log / API)**: direct_messages: is_flagged = 1 + flag_reason + moderation_score + flagged_at
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/ViolationDetectionTest.php + tests/Feature/Services/SensitiveModerationChatTest.php`

### TC-MOD-13 — Ảnh nhạy cảm VẪN gửi được, KHÔNG bị xoá, chỉ gắn cờ (Vision :8888)

- **Chức năng**: Kiểm duyệt ảnh flag-only (#13) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đã cấu hình `VISION_MODERATION_URL`; Vision trả danh sách nhãn vi phạm
- **Các bước thực hiện**:
  1. Gửi tin nhắn nhóm kèm ảnh nhạy cảm
  2. Kiểm tra file đính kèm còn tồn tại
  3. Kiểm tra cột cờ
- **Dữ liệu đầu vào**: POST /groups/{id}/chat/send kèm attachment ảnh
- **Kết quả mong đợi**: Tin + ảnh vẫn hiển thị với mọi thành viên; cờ `image:<nhãn>` được bật
- **Kiểm tra thêm (DB / log / API)**: chat_messages.attachment còn nguyên · flag_reason chứa `image:`
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/ViolationDetectionTest.php`

### TC-MOD-14 — mode = model: server 8890 trả multi-label trong luồng chat thì dùng kết quả đó

- **Chức năng**: Chat flag-only (#13) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: mode = model; Http::fake() server moderation trả nhiều nhãn
- **Các bước thực hiện**:
  1. Gửi tin nhắn nhóm
  2. Kiểm tra cờ theo kết quả server
- **Dữ liệu đầu vào**: mode=model (multi-label)
- **Kết quả mong đợi**: Cờ dùng nhãn do server trả về (không rơi về rules) và tin vẫn gửi bình thường
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/SensitiveModerationChatTest.php`

### TC-MOD-15 — Badge Giám sát Chat cộng cả tin nhắn cá nhân lẫn nhóm bị gắn cờ khi admin chưa xem; mở tab thì set mốc và badge về 0

- **Chức năng**: Badge giám sát chat (#14) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Có 2 tin bị gắn cờ: 1 tin 1-1 + 1 tin nhóm; admin chưa mở tab “Bị gắn cờ”
- **Các bước thực hiện**:
  1. Gọi GET /admin/chat-monitor/flagged-count kiểm tra = 2
  2. Mở tab “Bị gắn cờ”
  3. Kiểm tra lại badge = 0
  4. Tạo 1 tin bị gắn cờ mới
  5. Kiểm tra badge đếm lại từ 1
- **Dữ liệu đầu vào**: GET /admin/chat-monitor/flagged-count
- **Kết quả mong đợi**: Badge = 2 → mở tab ⇒ set `users.flagged_seen_at` và badge = 0 → tin mới ⇒ badge = 1
- **Kiểm tra thêm (DB / log / API)**: users.flagged_seen_at được cập nhật khi mở tab; tin thiếu `flagged_at` vẫn được đếm theo `created_at`
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Admin/ChatMonitorTest.php`

### TC-MOD-16 — Admin xem tab “Bị gắn cờ” và bỏ cờ được tin nhắn cá nhân/nhóm (không xoá tin)

- **Chức năng**: Bỏ cờ tin nhắn (#14) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Có 1 tin 1-1 và 1 tin nhóm bị gắn cờ
- **Các bước thực hiện**:
  1. Mở /admin/chat-monitor?tab=flagged
  2. Xác nhận tin không vi phạm với tin cá nhân (unflag)
  3. Làm tương tự với tin nhóm
  4. Kiểm tra khung chat của người dùng
- **Dữ liệu đầu vào**: PATCH /admin/chat-monitor/direct/{id}/unflag · PATCH /admin/chat-monitor/group/{id}/unflag
- **Kết quả mong đợi**: Cờ bị gỡ, badge giảm; nội dung tin nhắn VẪN còn trong khung chat của người dùng
- **Kiểm tra thêm (DB / log / API)**: is_flagged = 0 (flag_reason/moderation_score có thể giữ để đối soát)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/ViolationDetectionTest.php + tests/Feature/Admin/ChatMonitorTest.php`

### TC-MOD-17 — Admin xoá tin nhắn bị gắn cờ đã xác nhận vi phạm

- **Chức năng**: Xoá tin nhắn vi phạm (#14) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Có tin nhắn bị gắn cờ
- **Các bước thực hiện**:
  1. Mở tab Bị gắn cờ
  2. Bấm xoá ở tin nhắn
  3. Kiểm tra khung chat của người dùng
- **Dữ liệu đầu vào**: DELETE /admin/chat-monitor/direct/{id} · DELETE /admin/chat-monitor/group/{id}
- **Kết quả mong đợi**: Tin nhắn biến mất khỏi khung chat của cả hai bên; trang giám sát cập nhật ngay (không phụ thuộc AJAX)
- **Kiểm tra thêm (DB / log / API)**: Dòng tin nhắn đã bị xoá (hoặc đánh dấu xoá theo thiết kế)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Admin/ChatMonitorTest.php`

### TC-MOD-18 — Admin broadcast tin nhắn tới một nhóm hoặc toàn hệ thống

- **Chức năng**: Broadcast (#14) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập admin; có ít nhất 2 nhóm và một vài sinh viên
- **Các bước thực hiện**:
  1. Mở trang giám sát chat
  2. Chọn broadcast tới 1 nhóm rồi gửi
  3. Chọn broadcast toàn hệ thống rồi gửi
  4. Kiểm tra khung chat của sinh viên
- **Dữ liệu đầu vào**: POST /admin/chat-broadcast/group · POST /admin/chat-broadcast/all
- **Kết quả mong đợi**: Tin broadcast xuất hiện trong khung chat nhóm/tới mọi sinh viên; badge chưa đọc tăng tương ứng
- **Kiểm tra thêm (DB / log / API)**: chat_messages (nhóm) / direct_messages-hệ-thống tuỳ thiết kế · users.unread_message_count tăng
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Admin/ChatMonitorTest.php + tests/Feature/Admin/AdminGroupMessageTest.php`

### TC-MOD-19 — Admin gửi thông báo/cảnh báo vào khung chat nhóm mà KHÔNG cần là thành viên

- **Chức năng**: Gửi thông báo vào nhóm (#14) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập admin; nhóm Alpha có 2 thành viên
- **Các bước thực hiện**:
  1. Mở khung chat nhóm ở chế độ giám sát
  2. Soạn “Cảnh báo: nộp bài muộn sẽ bị trừ điểm.” với loại WARNING
  3. Gửi
  4. Kiểm tra khung chat của thành viên
- **Dữ liệu đầu vào**: POST /admin/chat-monitor/message-to-group {group_id, type=warning|announcement, content}
- **Kết quả mong đợi**: Tin hiện trong khung chat nhóm với định dạng riêng (“Cảnh báo từ Admin”); thành viên + trưởng nhóm đều thấy; badge nhóm tăng cho tới khi mở nhóm
- **Kiểm tra thêm (DB / log / API)**: chat_messages.type = warning · group_members KHÔNG có dòng mới cho admin · users.unread_message_count tăng
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Admin/AdminGroupMessageTest.php`

### TC-MOD-20 — Cảnh báo của admin không bị bộ kiểm duyệt gắn cờ và payload realtime/polling mang đúng type

- **Chức năng**: Gửi thông báo vào nhóm (#14) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: mode = rules; admin vừa gửi cảnh báo vào nhóm
- **Các bước thực hiện**:
  1. Gửi cảnh báo có nội dung dễ bị dính từ khoá
  2. Kiểm tra cột cờ
  3. Xem payload Reverb + payload polling
- **Dữ liệu đầu vào**: Cảnh báo của admin + GET /groups/{id}/chat/messages
- **Kết quả mong đợi**: Tin của admin KHÔNG bị gắn cờ; cả payload Realtime và polling đều có `type=warning` để client render đúng kiểu
- **Kiểm tra thêm (DB / log / API)**: chat_messages.is_flagged = 0
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Admin/AdminGroupMessageTest.php + tests/Feature/GroupChatPollingTest.php`

### TC-MOD-21 — Sinh viên/khách không vào được trang giám sát chat và không dùng được route gửi thông báo (case âm)

- **Chức năng**: Phân quyền giám sát chat (#14) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập sinh viên; và 1 phiên khách chưa đăng nhập
- **Các bước thực hiện**:
  1. Mở /admin/chat-monitor
  2. Gọi GET /admin/chat-monitor/flagged-count
  3. Gọi POST /admin/chat-monitor/message-to-group
- **Dữ liệu đầu vào**: URL/route dưới /admin/*
- **Kết quả mong đợi**: Sinh viên bị chuyển hướng/403; khách bị đưa về /login; không thao tác được dữ liệu
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Admin/ChatMonitorTest.php + tests/Feature/Admin/AdminGroupMessageTest.php`

### TC-MOD-22 — Tab “Bị gắn cờ” không lộ cột Lý do / Điểm; mở tab Chat cá nhân/nhóm KHÔNG reset badge

- **Chức năng**: Trang giám sát chat (#14) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Vừa mở tab Bị gắn cờ (badge = 0) và có 1 tin bị gắn cờ mới
- **Các bước thực hiện**:
  1. Mở tab “Chat cá nhân”
  2. Kiểm tra badge giám sát
  3. Mở tab “Chat nhóm”
  4. Kiểm tra badge lần nữa
  5. Mở lại tab “Bị gắn cờ”
- **Dữ liệu đầu vào**: URL /admin/chat-monitor?tab=direct|group|flagged
- **Kết quả mong đợi**: Chỉ tab “Bị gắn cờ” mới set `flagged_seen_at`; các tab khác không reset badge; tab Bị gắn cờ ẩn chi tiết lý do/điểm (chỉ hiện nhãn)
- **Kiểm tra thêm (DB / log / API)**: users.flagged_seen_at chỉ đổi khi mở tab flagged
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Admin/ChatMonitorTest.php`

### TC-MOD-23 — Link “Giám sát Chat” trên sidebar admin mở thẳng tab Bị gắn cờ và badge có URL đếm riêng

- **Chức năng**: Layout admin (#14) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Thấp
- **Tiền điều kiện**: Đăng nhập admin
- **Các bước thực hiện**:
  1. Mở bất kỳ trang admin
  2. Quan sát mục “Giám sát Chat” và badge cạnh đó
  3. Bấm vào mục
- **Dữ liệu đầu vào**: Layout admin
- **Kết quả mong đợi**: Link trỏ tới ?tab=flagged; badge có `data-*` URL gọi `/admin/chat-monitor/flagged-count` để tự cập nhật
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Admin/ChatMonitorTest.php`

### TC-MOD-24 — Admin xem mọi khung chat nhóm, không cộng badge chưa đọc và tìm được theo tên

- **Chức năng**: Giám sát chat nhóm (#14) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Có 2 nhóm khác lớp; admin không là thành viên nhóm nào
- **Các bước thực hiện**:
  1. Mở /admin/chat-monitor?tab=group
  2. Tìm theo tên nhóm bằng ô tìm kiếm
  3. Mở 1 khung chat nhóm ở chế độ giám sát
- **Dữ liệu đầu vào**: URL + ô tìm kiếm
- **Kết quả mong đợi**: Admin mở được mọi khung chat; KHÔNG bị cộng badge chưa đọc; chế độ giám sát chỉ có ô soạn thông báo/cảnh báo, KHÔNG có ô gửi tin thường
- **Kiểm tra thêm (DB / log / API)**: group_chat_reads của admin không phát sinh; users.unread_message_count của admin không đổi
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Admin/AdminGroupMessageTest.php`

### TC-MOD-25 — Validate dữ liệu gửi thông báo: thiếu nội dung, sai loại tin, nhóm không tồn tại đều bị từ chối (case âm)

- **Chức năng**: Gửi thông báo vào nhóm (#14) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập admin
- **Các bước thực hiện**:
  1. Gửi với content rỗng
  2. Gửi với type = “spam”
  3. Gửi với group_id không tồn tại
- **Dữ liệu đầu vào**: POST /admin/chat-monitor/message-to-group (3 tình huống)
- **Kết quả mong đợi**: Cả 3 bị từ chối kèm lỗi validate; không sinh dòng `chat_messages` nào
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Admin/AdminGroupMessageTest.php`

### TC-MOD-26 — Danh sách kiểm duyệt vẫn hoạt động khi AJAX lỗi (không phụ thuộc JS)

- **Chức năng**: Giám sát chat (#14) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Thấp
- **Tiền điều kiện**: Đăng nhập admin; có tin bị gắn cờ
- **Các bước thực hiện**:
  1. Tắt JavaScript trên trình duyệt
  2. Mở tab Bị gắn cờ
  3. Bấm bỏ cờ / xóa tin nhắn
- **Dữ liệu đầu vào**: Trang giám sát chat
- **Kết quả mong đợi**: Các nút bỏ cờ/xóa vẫn hoạt động bằng form thường (progressive enhancement), không cần AJAX
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Admin/ChatMonitorTest.php`

### TC-MOD-27 — Cảnh báo / thông báo của Admin trong khung chat KHÔNG tự biến mất sau khi load trang

- **Chức năng**: Cảnh báo admin hiển thị bền (#14) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Nhóm có 1 tin `warning` của admin; mở khung chat nhóm bằng tài khoản sinh viên
- **Các bước thực hiện**:
  1. Mở /groups/{id}/chat
  2. Quan sát cảnh báo của Admin
  3. Chờ > 15 giây (và tải lại trang) rồi quan sát lại
- **Dữ liệu đầu vào**: URL /groups/{groupId}/chat
- **Kết quả mong đợi**: Cảnh báo vẫn hiển thị nguyên vẹn sau 15 giây và sau khi tải lại trang; KHÔNG bị JS đóng
- **Kết quả thực tế**: Chưa chạy tay
- **Trạng thái**: **Chưa chạy tay**
- **Test tự động**: `tests/Feature/Admin/AdminGroupMessageTest.php (kiểm tra nội dung server-side)`
- **Ghi chú**: BUG đã sửa: `layouts/user.blade.php` trước đây sau 5 giây đóng MỌI `.alert` trên trang (kể cả cảnh báo trong khung chat). Nay chỉ đóng flash message trong `#flash-messages` — cần chạy tay để xác nhận.

## 4. Cách chạy nhóm test này

```powershell
cd G:\MyApp\laragon\www\Team-Assign
php artisan test tests/Unit/ViolationDetectionTest.php tests/Unit/SensitiveModerationTest.php tests/Feature/Services/ViolationDetectionTest.php tests/Feature/Services/SensitiveModerationChatTest.php tests/Feature/Admin/ChatMonitorTest.php tests/Feature/Admin/AdminGroupMessageTest.php
```

## 5. Ghi chú & rủi ro

- FLAG-ONLY là ràng buộc quan trọng nhất: mọi case phải khẳng định tin nhắn/nhóm/ảnh vẫn được gửi và KHÔNG bị xóa tự động — chỉ bật `is_flagged` + `flag_reason` + `moderation_score` + `flagged_at`.
- Tầng rules đọc ngưỡng + dataset từ thư mục `violation-detection/` (`config/thresholds.json`) — sửa ngưỡng ở đó, KHÔNG nhúng số vào code.
- Fail-open 3 lớp: server AI chết/không cấu hình ⇒ quay về rules và chat vẫn hoạt động bình thường (không bao giờ 500, không treo khung chat).
- `flag_reason` phải ghi rõ NGUỒN: tiền tố `fraud:` (bán đề tài/làm hộ/tống tiền), `sensitive:<nhãn>` (xúc phạm, thù địch…), `image:` (Vision) — gộp nhiều nguồn và lấy điểm CAO NHẤT.
- Badge giám sát chat = số tin bị gắn cờ MỚI HƠN `users.flagged_seen_at` (mở tab “Bị gắn cờ” reset mốc; mở tab chat cá nhân/nhóm KHÔNG reset).
- ĐÃ SỬA (2026-09-22): `layouts/user.blade.php` trước đây tự đóng **mọi** `.alert` sau 5 giây nên cảnh báo/thông báo của Admin trong khung chat nhóm biến mất ngay sau khi load. Nay flash message được bọc trong `#flash-messages` và JS chỉ đóng `#flash-messages .alert`.
- Case có nhãn `Chưa chạy tay` cần tự chạy trên trình duyệt (2 tài khoản nếu cần realtime) rồi đổi trạng thái trong `data/07-kiem-duyet-noi-dung.php` và export lại.

<sub>Sinh tự động bởi `php artisan testcases:export` · nguồn: `docs/test-cases/data/07-kiem-duyet-noi-dung.php`</sub>
