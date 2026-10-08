# Bộ test case — Team-Assign

> Thư mục này là **nguồn sự thật duy nhất (single source of truth)** cho test case của hệ thống Team-Assign.
> `test-cases.xlsx` và toàn bộ file `*.md` đều **SINH TỰ ĐỘNG** từ `docs/test-cases/data/*.php` — sửa dữ liệu ở đó rồi chạy lại lệnh export, **không sửa tay** file `.md`/`.xlsx`.

## 1. Mục lục

| # | File nhóm (Markdown) | Mã TC | Số TC | Pass | Fail | Chưa chạy tay | Chức năng (FEATURE_STATUS) |
|---|---|---|---|---|---|---|---|
| 01 | [01-xac-thuc-va-tai-khoan.md](01-xac-thuc-va-tai-khoan.md) | `TC-AUTH` | 29 | 28 | 0 | 1 | #1 đăng nhập/đăng ký/quên–đổi mật khẩu (**L01–L04 đã xanh**), #2 hồ sơ + đổi email (signed URL) + khóa/xóa mềm; **L09**: Thiết lập tài khoản mức 2 (Bảo mật: lịch sử đăng nhập + phiên + remember; Hồ sơ: avatar/ngôn ngữ/múi giờ; Riêng tư: chặn/ẩn online/lời mời); **hardening Bó-1/Bó-2**: bảo vệ route + bất biến múi giờ |
| 02 | [02-quan-tri-nguoi-dung-mon-hoc-lop.md](02-quan-tri-nguoi-dung-mon-hoc-lop.md) | `TC-ADMIN` | 30 | 21 | 0 | 9 | #2 quản lý người dùng + import + khóa/mở, #3 môn học + import, #4 lớp học phần (mã lớp 5 ký tự — L07), #5 sinh viên trong lớp (thao tác nhanh gửi email / reset mật khẩu — L06) |
| 03 | [03-thong-ke-va-thong-bao.md](03-thong-ke-va-thong-bao.md) | `TC-STAT` | 9 | 9 | 0 | 0 | #16 thống kê hệ thống (5 trang), #17 biểu đồ dashboard admin, #12 thông báo + badge |
| 04 | [04-giang-vien-lop-hoc-phan.md](04-giang-vien-lop-hoc-phan.md) | `TC-LECT` | 16 | 16 | 0 | 0 | #4 lớp học phần (phía giảng viên), #5 quản lý sinh viên trong lớp, tham gia lớp bằng mã |
| 05 | [05-sinh-vien-nhom-va-dang-ky-de-tai.md](05-sinh-vien-nhom-va-dang-ky-de-tai.md) | `TC-STU` | 51 | 51 | 0 | 0 | #6 quản lý đề tài + **import Excel/CSV**, #7 đăng ký/duyệt đề tài, #8 nhóm – lời mời – yêu cầu tham gia (tự hủy yêu cầu hết hiệu lực, **1 nhóm / 1 lớp học phần**, tìm nhóm theo từng lớp, **L10 xóa mềm nhóm**) |
| 06 | [06-chat-1-1-va-chat-nhom.md](06-chat-1-1-va-chat-nhom.md) | `TC-CHAT` | 10 | 6 | 0 | 4 | #9 chat 1-1, #10 chat nhóm, #11 chặn người dùng 2 chiều, polling dự phòng |
| 07 | [07-kiem-duyet-noi-dung.md](07-kiem-duyet-noi-dung.md) | `TC-MOD` | 28 | 27 | 0 | 1 | #13 kiểm duyệt 3 tầng (flag-only) + **L08** (song song `Http::pool` + gắn cờ SAU response), #14 Admin giám sát chat (+ cảnh báo admin hiển thị bền) |
| 08 | [08-goi-y-de-tai-ngu-nghia.md](08-goi-y-de-tai-ngu-nghia.md) | `TC-REC` | 20 | 20 | 0 | 0 | #18 gợi ý đề tài theo ngữ nghĩa (embedding + cosine, fail-open); **bắt buộc chọn môn học**, phạm vi môn theo vai trò SV/GV/Admin |
| 09 | [09-bang-tin-lop-hoc.md](09-bang-tin-lop-hoc.md) | `TC-STREAM` | 15 | 13 | 0 | 2 | #19 bảng tin lớp học (thông báo GV, hoạt động nhóm, bình luận, realtime, backfill) |
| 10 | [10-online-va-trang-thai-tin-nhan.md](10-online-va-trang-thai-tin-nhan.md) | `TC-PRES` | 14 | 13 | 0 | 1 | #20 online/offline + trạng thái tin nhắn (đã gửi/đã xem) + lịch sử trò chuyện |
| 11 | [11-chatbot-gemini.md](11-chatbot-gemini.md) | `TC-BOT` | 11 | 9 | 0 | 2 | #15 chatbot trợ lý đề tài (Groq chính + Gemini dự phòng, retry/fallback) + **dòng miễn trừ cuối mỗi câu trả lời** (`CHATBOT_DISCLAIMER`) |
| — | **TỔNG** | | **233** | **213** | **0** | **20** | |

Cấu trúc thư mục:

```text
docs/test-cases/
├── README.md                  ← viết tay: mục lục, quy ước, môi trường, cách chạy
├── test-cases.xlsx            ← SINH TỰ ĐỘNG (14 sheet = Tổng hợp + Hướng dẫn + Dữ liệu mẫu + 11 nhóm)
├── 01-…11-*.md                ← SINH TỰ ĐỘNG (mỗi nhóm 1 file, cùng tên với file dữ liệu)
└── data/                      ← NGUỒN DỮ LIỆU DUY NHẤT: 01-…11-*.php (mỗi test case = 1 mảng PHP, 15 trường)
```

Sinh lại toàn bộ:

```powershell
cd G:\MyApp\laragon\www\Team-Assign
php artisan testcases:export          # cả Excel + Markdown
php artisan testcases:export --xlsx   # chỉ Excel
php artisan testcases:export --md     # chỉ Markdown
```

## 2. Quy ước mã test case

- Định dạng: `TC-<NHÓM>-<số 2 chữ số>`, ví dụ `TC-CHAT-07` = nhóm Chat, test case thứ 7.
- Mã **tự sinh theo thứ tự khai báo trong `data/<nhóm>.php`** (vẫn có thể khai báo tay trường `code` để ghim mã nếu cần).
- Danh sách tiền tố: `TC-AUTH` (01) · `TC-ADMIN` (02) · `TC-STAT` (03) · `TC-LECT` (04) · `TC-STU` (05) · `TC-CHAT` (06) · `TC-MOD` (07) · `TC-REC` (08) · `TC-STREAM` (09) · `TC-PRES` (10) · `TC-BOT` (11).
- Mã nhóm **1-1 với sheet Excel** (`01-AUTH`, `02-ADMIN`, …) và với tên file dữ liệu ⇒ tra cứu nhanh: `TC-REC-04` nằm trong `data/08-goi-y-de-tai-ngu-nghia.php` (case thứ 4 của nhóm).

## 3. Quy ước 15 trường của mỗi test case

| Trường PHP | Cột Excel | Ý nghĩa |
|---|---|---|
| `role` | Role | Vai trò thực hiện: Khách / Sinh viên / Giảng viên / Admin / Hệ thống |
| `feature` | Chức năng | Nhóm chức năng con + số mục FEATURE_STATUS, ví dụ `Đăng ký đề tài (#7)` |
| `type` | Loại | `UI` · `API` · `DB` · `Realtime` · `CLI` |
| `prio` | Ưu tiên | `Cao` · `Trung bình` · `Thấp` |
| `goal` | Mô tả / Mục tiêu | Mục tiêu kiểm thử (cũng là tiêu đề case) |
| `pre` | Tiền điều kiện | Trạng thái dữ liệu/tài khoản cần có trước khi chạy |
| `steps[]` | Các bước thực hiện | Mảng bước, xuất ra Excel dạng nhiều dòng |
| `input` | Dữ liệu đầu vào | Giá trị cụ thể cần nhập / URL / route |
| `expect` | Kết quả mong đợi | Hành vi đúng cần quan sát |
| `db` (tuỳ chọn) | (ghép vào mô tả khi export) | Kiểm tra thêm ở DB / log / API |
| `actual` | Kết quả thực tế | Bỏ trống ⇒ tự sinh theo `status` |
| `status` | Trạng thái | `Pass` · `Fail` · `Chưa chạy tay` (mặc định `Chưa chạy tay`) |
| `auto` | Test tự động | File Pest phủ case đó; rỗng ⇒ `(thủ công)` |
| `note` | Ghi chú | Lý do Fail, cảnh báo môi trường, cách khắc phục |

Quy ước viết case (đang áp dụng cho cả 11 nhóm):

1. **Luôn có case âm** (dữ liệu sai / không đủ quyền) cho mỗi hành vi quan trọng — đánh dấu trong `goal` bằng “(case âm)”.
2. **Fail-open phải được kiểm thử**: tính năng phụ (AI, realtime, bảng tin, badge) hỏng thì luồng chính vẫn hoạt động.
3. Case chạm tới DB luôn ghi rõ **bảng + cột** cần kiểm chứng ở trường `db`.
4. Case chỉ kiểm chứng được bằng trình duyệt (JS/realtime/upload) đặt `status = 'Chưa chạy tay'` và nêu lý do ở `note`.

## 4. Quy ước trạng thái

- `Pass` — đã kiểm chứng, thường bằng test tự động ghi ở cột “Test tự động”.
- `Fail` — kết quả thực tế LỆCH mong đợi; bắt buộc có `actual` + `note` nêu nguyên nhân và cách khắc phục.
- `Chưa chạy tay` — case UI/realtime/upload chưa chạy trên trình duyệt (Pest không phủ được).
- Excel tô màu cột “Trạng thái”: xanh lá = Pass · đỏ = Fail · vàng = Chưa chạy tay.

> Sau khi chạy tay một case: sửa `status` (+ `actual`/`note`) trong `data/*.php` rồi export lại để Excel/Markdown luôn khớp.


## 5. Môi trường kiểm thử

| Thành phần | Giá trị |
|---|---|
| CSDL test | MySQL Laragon `team_assign_test` (phpunit.xml hardcode `127.0.0.1:3306`, user `root`, mật khẩu rỗng) — **phải đang chạy** trước khi test |
| Biến môi trường khi chạy Pest | `APP_ENV=testing`, `BCRYPT_ROUNDS=4`, `CACHE_STORE=array`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=array`, `BROADCAST_CONNECTION=null` |
| Dịch vụ AI (tuỳ case) | `AI-Services\start-servers.ps1` → Vision 8888 · PhoBERT fraud 8889 · PhoBERT moderation 8890 · topic-recommender-8891 (:8891) (`stop-servers.ps1` để tắt) |
| Realtime (tuỳ case) | `php artisan reverb:start` — cần cho case tick trạng thái, badge, bảng tin. Tắt Reverb ⇒ app vẫn đúng khi tải lại trang (fail-open) |
| Tài khoản & dữ liệu mẫu | Sheet **“Dữ liệu mẫu”** trong `test-cases.xlsx`: `admin@test.com`, `gv1@test.com`, `sv1@test.com`, `sv2@test.com` — mật khẩu `password` |
| Lưu ý dữ liệu | Tài khoản dùng để kiểm tra đăng nhập nên có `email_verified_at ≠ NULL` (xem mục 8 — bug TC-AUTH-13) |

## 6. Cách chạy

Toàn bộ bộ test tự động (chạy lần cuối 2026-09-23, sau đợt sửa lần 2: **4 failed / 310 passed**, 1186 assertions, ~27 giây):

```powershell
cd G:\MyApp\laragon\www\Team-Assign
php artisan test
```

Theo từng nhóm — dùng đúng lệnh ở mục “Cách chạy nhóm này” cuối mỗi file `.md` (và trong sheet “Hướng dẫn”), ví dụ:

```powershell
# TC-AUTH (01)
php artisan test tests/Feature/Auth/ForgotPasswordTest.php tests/Feature/ChangePasswordTest.php tests/Feature/EmailChangeVerificationTest.php tests/Feature/AdminSoftDeleteTest.php tests/Feature/MiddlewareRedirectTest.php tests/Feature/ViewSmokeTest.php

# TC-STU (05) — nhóm nhiều case nhất
php artisan test tests/Feature/Services/GroupServiceTest.php tests/Feature/Services/InvitationServiceTest.php tests/Feature/Services/TopicRegistrationServiceTest.php tests/Feature/TopicControllerTest.php tests/Feature/UserDashboardTopicsTest.php

# TC-MOD (07) — kiểm duyệt + giám sát chat
php artisan test tests/Unit/ViolationDetectionTest.php tests/Unit/SensitiveModerationTest.php tests/Feature/Services/ViolationDetectionTest.php tests/Feature/Services/SensitiveModerationChatTest.php tests/Feature/Admin/ChatMonitorTest.php tests/Feature/Admin/AdminGroupMessageTest.php
```

Chạy đúng 1 case (Pest hỗ trợ lọc theo tên):

```powershell
php artisan test tests/Feature/Admin/StatisticsTest.php --filter="trang thống kê đề tài"
```

Kiểm tra tay phần realtime/UI (các case `Chưa chạy tay`):

```powershell
php artisan reverb:start          # terminal 1
php artisan serve                 # terminal 2 (hoặc dùng vhost của Laragon)
# mở 2 trình duyệt ẩn danh với 2 tài khoản khác nhau rồi làm theo cột “Các bước thực hiện”
```

## 7. Quy trình cập nhật dữ liệu test case

1. Sửa/thêm test case **chỉ** trong `docs/test-cases/data/<nhóm>.php`.
2. Kiểm tra cú pháp: `php -l docs\test-cases\data\<nhóm>.php`.
3. Sinh lại: `php artisan testcases:export` (Excel + Markdown luôn khớp nhau).
4. Nếu trạng thái thay đổi do chạy tay ⇒ cập nhật `status`/`actual`/`note` rồi export lại.
5. Commit cả 3 loại file: `data/*.php` (nguồn) + `*.md` + `test-cases.xlsx` (bản sinh).

## 8. Lỗi & khác biệt đang tồn tại (5 case `Fail`)

**Đã sửa trong đợt 2026-09-23 (lần 2)** — 3 yêu cầu do người dùng báo:
1. **Không tạo được nhóm thứ 2 trong cùng lớp học phần** — combo box tạo nhóm ẩn lớp đã có nhóm; đã có nhóm ở mọi lớp ⇒ cảnh báo + khóa nút; chặn cả khi POST sửa `class_id` (6 test mới).
2. **“Nhóm của tôi”: Tạo nhóm / Tìm nhóm theo từng lớp** — vào form từ thẻ lớp thì lớp hiện dạng TEXT (không combo box); modal “Tìm nhóm” của mỗi lớp chỉ chứa nhóm thuộc lớp đó.
3. **Trợ lý gợi ý đề tài** — thêm combo box **bắt buộc chọn môn học**; giảng viên/admin cũng dùng được (kể cả ở trang quản lý đề tài); nhóm đã có đề tài được duyệt vẫn dùng được, chỉ hiện cảnh báo tham khảo (5 test mới; suite 08: 15 → 20 case).

**Đã sửa trong đợt 2026-09-22 (lần 2)** — 4 vấn đề do người dùng báo:
1. **Import đề tài** — thêm `TopicsImport` + 3 route + form + nút “Import đề tài” (10 test mới, tất cả Pass).
2. **Trạng thái hoạt động chỉ hiện chấm** — bỏ nhãn chữ ở danh sách hội thoại và header 1-1 (giữ tooltip).
3. **Cảnh báo của Admin không còn biến mất** — `layouts/user.blade.php` chỉ auto-close flash message trong `#flash-messages`.
4. **Yêu cầu tham gia nhóm hết hiệu lực** — tự chuyển `Expired` + ẩn khỏi tab mặc định, thêm tab “Hết hiệu lực” (6 test mới, tất cả Pass).

| Mã TC | Hiện tượng | Nguyên nhân | Hướng xử lý |
|---|---|---|---|
| `TC-AUTH-07` | `ForgotPasswordTest` đỏ: session thiếu key `status` | `PasswordResetLinkController::store()` trả `with('success'|'error')`, test khẳng định `status` | Thống nhất 1 tên key (khuyến nghị sửa test sang `success`) |
| `TC-AUTH-08` | `ForgotPasswordTest` đỏ: session thiếu key `errors` | Controller trả `with('error')` thay vì `withErrors()` | Dùng `withErrors()` để hiện lỗi đúng chuẩn Laravel |
| `TC-AUTH-09` | `ForgotPasswordTest` đỏ: sau khi đặt lại mật khẩu chuyển về `/user/dashboard` thay vì `/login` | `NewPasswordController::store()` đăng nhập luôn rồi điều hướng theo vai trò (thay đổi hành vi) | Cập nhật kỳ vọng của test cho khớp hành vi mới |
| `TC-AUTH-11`, `TC-AUTH-13` | **HTTP 500** khi đăng nhập bằng tài khoản có `email_verified_at = NULL` — `RouteNotFoundException: Route [verification.notice] not defined` | `bootstrap/app.php` chỉ nạp `routes/web.php` (+ console/channels), **không nạp `routes/auth.php`** — nơi định nghĩa `verification.notice`, `verification.verify`, `password.update`… trong khi `AuthController::login()` vẫn redirect tới route đó | Nạp `routes/auth.php` trong `bootstrap/app.php` (hoặc bỏ nhánh redirect) rồi chạy lại `php artisan test` |

Ngoài ra **20 case `Chưa chạy tay`** là các luồng UI/realtime/upload (chấm online chỉ hiện màu, cảnh báo Admin phải hiển thị bền, tick ✓✓, banner bảng tin, broadcast/cảnh báo của admin, upload ảnh chat, các nút import Excel) — cần chạy trên trình duyệt rồi cập nhật lại trạng thái.

## 9. Bản đồ test tự động ↔ nhóm test case

| File Pest | Nhóm TC được phủ |
|---|---|
| `tests/Feature/Auth/ForgotPasswordTest.php`, `ChangePasswordTest.php`, `EmailChangeVerificationTest.php`, `AdminSoftDeleteTest.php`, `MiddlewareRedirectTest.php`, `ViewSmokeTest.php` | 01 `TC-AUTH` (+ 02, 04 dùng chung `ViewSmokeTest`) |
| `tests/Feature/SubjectTest.php`, `AdminClassManagementTest.php` | 02 `TC-ADMIN` |
| `tests/Feature/Admin/StatisticsTest.php`, `AdminNotificationTest.php`, `JoinRequestNotificationTest.php`, `BadgeSeenTest.php` | 03 `TC-STAT` |
| `tests/Feature/LecturerClassTest.php`, `StudentClassDetailTest.php`, `ClassJoinTest.php`, `GroupCreationScopeTest.php` | 04 `TC-LECT` |
| `tests/Feature/Services/GroupServiceTest.php`, `Services/InvitationServiceTest.php`, `Services/TopicRegistrationServiceTest.php`, `TopicControllerTest.php`, `UserDashboardTopicsTest.php`, `TopicImportTest.php`, `JoinRequestExpiryTest.php`, `GroupCreationScopeTest.php` | 05 `TC-STU` |
| `tests/Feature/ChatUnreadBadgeTest.php`, `GroupChatPollingTest.php` | 06 `TC-CHAT` |
| `tests/Unit/ViolationDetectionTest.php`, `Unit/SensitiveModerationTest.php`, `Feature/Services/ViolationDetectionTest.php`, `Feature/Services/SensitiveModerationChatTest.php`, `Feature/Admin/ChatMonitorTest.php`, `Feature/Admin/AdminGroupMessageTest.php` | 07 `TC-MOD` |
| `tests/Unit/TopicRecommendationTest.php`, `tests/Feature/TopicRecommendationTest.php` | 08 `TC-REC` |
| `tests/Unit/ClassStreamTest.php`, `tests/Feature/ClassStreamTest.php` | 09 `TC-STREAM` |
| `tests/Unit/PresenceTest.php`, `tests/Feature/PresenceTest.php`, `ChatMessageStatusTest.php` | 10 `TC-PRES` |
| `tests/Feature/ChatbotTest.php` | 11 `TC-BOT` |

Công cụ sinh tài liệu: `app/Console/Commands/ExportTestCasesCommand.php` (`php artisan testcases:export`).
Dữ liệu mẫu dùng chung cho các bước test được khai báo ở hằng `SAMPLE_DATA` trong command đó (khớp `tests/Support/Fixtures.php`).

<sub>Tài liệu này viết tay · cập nhật lần cuối: 2026-09-23 · nguồn dữ liệu: `docs/test-cases/data/*.php`</sub>

