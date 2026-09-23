# Nhóm 03 — Thống kê & thông báo

> **Mã nhóm**: `TC-STAT` · **Chức năng**: FEATURE_STATUS #16 (thống kê hệ thống 5 trang), #17 (biểu đồ dashboard admin), #12 (thông báo realtime + badge tổng + badge đã xem)
> **Số test case**: 9 — Pass: **9** · Fail: **0** · Chưa chạy tay: **0**
> **Môi trường**: MySQL team_assign_test; đăng nhập admin@test.com cho phần thống kê; BROADCAST_CONNECTION=null khi chạy test (Realtime phải kiểm tra tay bằng reverb:start).
> ↻ File này **sinh tự động** từ `docs/test-cases/data/03-thong-ke-va-thong-bao.php` — sửa dữ liệu ở đó rồi chạy `php artisan testcases:export` (đừng sửa file .md này).

## 1. Mục tiêu & phạm vi

Kiểm thử 5 trang thống kê của Admin (tổng quan, đề tài, nhóm, yêu cầu, người dùng) và toàn bộ hệ thống thông báo: admin gửi thông báo hệ thống cho giảng viên, chuông thông báo của người dùng, badge chưa đọc, đánh dấu đã đọc/đã xem, xóa thông báo.

## 2. Bảng tóm tắt test case

| Mã TC | Tên / Mục tiêu | Role | Loại | Ưu tiên | Trạng thái |
|---|---|---|---|---|---|
| `TC-STAT-01` | Admin xem trang tổng quan thống kê với các số liệu tổng hợp | Admin | UI | Cao | Pass |
| `TC-STAT-02` | Trang thống kê đề tài hiển thị đúng số còn trống và số đã có nhóm đăng ký | Admin | UI | Cao | Pass |
| `TC-STAT-03` | Trang thống kê nhóm tính đúng số thành viên trung bình mỗi nhóm | Admin | UI | Trung bình | Pass |
| `TC-STAT-04` | Admin gửi thông báo hệ thống đến TẤT CẢ giảng viên | Admin | UI | Cao | Pass |
| `TC-STAT-05` | Gửi thông báo tới danh sách giảng viên được chọn | Admin | UI | Trung bình | Pass |
| `TC-STAT-06` | Nav-bar hiển thị badge thông báo và badge yêu cầu tính sẵn từ server | Giảng viên | UI | Cao | Pass |
| `TC-STAT-07` | Đánh dấu tất cả đã đọc đưa badge thông báo về 0 | Giảng viên | UI | Trung bình | Pass |
| `TC-STAT-08` | Bấm mục Yêu cầu / Lời mời: badge về 0 và chỉ hiện lại khi có bản ghi mới | Sinh viên | DB | Trung bình | Pass |
| `TC-STAT-09` | Khách chưa đăng nhập không gọi được route đánh dấu đã xem (case âm) | Khách | API | Cao | Pass |

## 3. Chi tiết test case

### TC-STAT-01 — Admin xem trang tổng quan thống kê với các số liệu tổng hợp

- **Chức năng**: Thống kê tổng quan (#16) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập admin; DB có giảng viên, lớp, nhóm, đề tài mẫu
- **Các bước thực hiện**:
  1. Mở /admin/statistics
  2. Đối chiếu số liệu với DB
- **Dữ liệu đầu vào**: URL /admin/statistics
- **Kết quả mong đợi**: Trang render 200; hiện tiêu đề “Thống kê hệ thống”, khối “Người dùng” và “Yêu cầu đang chờ duyệt”
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Admin/StatisticsTest.php`

### TC-STAT-02 — Trang thống kê đề tài hiển thị đúng số còn trống và số đã có nhóm đăng ký

- **Chức năng**: Thống kê đề tài (#16) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Có 2 đề tài: 1 chưa gán nhóm, 1 đã gán (groups.topic_id + topics.assigned_group_id)
- **Các bước thực hiện**:
  1. Mở /admin/statistics/topics
  2. Đối chiếu “Tổng số đề tài” / “Còn trống” / “Đã có nhóm đăng ký”
  3. Xem bảng “Theo giảng viên”
- **Dữ liệu đầu vào**: URL /admin/statistics/topics
- **Kết quả mong đợi**: Tổng = 2 · Còn trống = 1 · Đã có nhóm = 1; có bảng thống kê theo giảng viên
- **Kiểm tra thêm (DB / log / API)**: Đếm theo `topics.assigned_group_id` (KHÔNG tự so `topics.topic_id`)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Admin/StatisticsTest.php`

### TC-STAT-03 — Trang thống kê nhóm tính đúng số thành viên trung bình mỗi nhóm

- **Chức năng**: Thống kê nhóm (#16) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Có 1 nhóm với 1 trưởng nhóm; các nhóm khác chưa gán đề tài
- **Các bước thực hiện**:
  1. Mở /admin/statistics/groups
  2. Kiểm tra các chỉ số
- **Dữ liệu đầu vào**: URL /admin/statistics/groups
- **Kết quả mong đợi**: Hiện “Thống kê nhóm”, “Chưa có đề tài” và “Số thành viên trung bình mỗi nhóm”
- **Kiểm tra thêm (DB / log / API)**: Số thành viên đếm qua bảng `group_members` (đã gồm trưởng nhóm)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Admin/StatisticsTest.php`

### TC-STAT-04 — Admin gửi thông báo hệ thống đến TẤT CẢ giảng viên

- **Chức năng**: Thông báo hệ thống (#12) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập admin; có ít nhất 2 tài khoản giảng viên
- **Các bước thực hiện**:
  1. Mở /admin/notifications/create
  2. Chọn phạm vi “tất cả giảng viên”, nhập tiêu đề + nội dung
  3. Bấm gửi
- **Dữ liệu đầu vào**: title=Kế hoạch học kỳ, content=..., scope=all
- **Kết quả mong đợi**: Gửi thành công; mọi giảng viên nhận 1 thông báo và badge chưa đọc tăng
- **Kiểm tra thêm (DB / log / API)**: notifications có 1 dòng/giảng viên · users.unread_notifications tăng tương ứng
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/AdminNotificationTest.php + tests/Feature/ViewSmokeTest.php`

### TC-STAT-05 — Gửi thông báo tới danh sách giảng viên được chọn

- **Chức năng**: Thông báo hệ thống (#12) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập admin; có 2 giảng viên, chọn đúng 1 người
- **Các bước thực hiện**:
  1. Mở form gửi thông báo
  2. Tick chọn 1 giảng viên
  3. Bấm gửi
- **Dữ liệu đầu vào**: recipients=[gv1]
- **Kết quả mong đợi**: Chỉ giảng viên được chọn nhận thông báo; giảng viên khác không thấy
- **Kiểm tra thêm (DB / log / API)**: notifications chỉ có dòng cho user_id của gv1
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/AdminNotificationTest.php`

### TC-STAT-06 — Nav-bar hiển thị badge thông báo và badge yêu cầu tính sẵn từ server

- **Chức năng**: Chuông thông báo (#12) · **Role**: Giảng viên · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Giảng viên đang có 1 thông báo chưa đọc + 1 yêu cầu đăng ký đang chờ
- **Các bước thực hiện**:
  1. Mở bất kỳ trang có nav-bar
  2. Quan sát badge trên nav-bar
- **Dữ liệu đầu vào**: GET /dashboard/lecturer
- **Kết quả mong đợi**: Badge thông báo và badge yêu cầu hiển thị đúng số tính từ DB (không phải JS tự đoán)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/JoinRequestNotificationTest.php`

### TC-STAT-07 — Đánh dấu tất cả đã đọc đưa badge thông báo về 0

- **Chức năng**: Đánh dấu đã đọc (#12) · **Role**: Giảng viên · **Loại**: UI · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đang có 2 thông báo chưa đọc
- **Các bước thực hiện**:
  1. Mở /notifications
  2. Bấm “Đánh dấu tất cả đã đọc”
  3. Quan sát badge
- **Dữ liệu đầu vào**: POST /notifications/mark-all-read
- **Kết quả mong đợi**: Danh sách chuyển trạng thái đã đọc; badge về 0
- **Kiểm tra thêm (DB / log / API)**: users.unread_notifications = 0
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/JoinRequestNotificationTest.php`

### TC-STAT-08 — Bấm mục Yêu cầu / Lời mời: badge về 0 và chỉ hiện lại khi có bản ghi mới

- **Chức năng**: Badge đã xem (#12) · **Role**: Sinh viên · **Loại**: DB · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Sinh viên đang có yêu cầu/lời mời chưa xem
- **Các bước thực hiện**:
  1. Mở mục “Yêu cầu” (POST /user/join_requests/seen)
  2. Kiểm tra badge = 0
  3. Tạo thêm 1 yêu cầu mới
  4. Kiểm tra badge = 1
- **Dữ liệu đầu vào**: POST /user/join_requests/seen · POST /user/invites/seen
- **Kết quả mong đợi**: Badge về 0 sau khi xem; chỉ tăng lại với bản ghi có thời điểm MỚI HƠN mốc `*_seen_at`
- **Kiểm tra thêm (DB / log / API)**: users.join_requests_seen_at / users.invites_seen_at được cập nhật
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/BadgeSeenTest.php`

### TC-STAT-09 — Khách chưa đăng nhập không gọi được route đánh dấu đã xem (case âm)

- **Chức năng**: Badge đã xem (#12) · **Role**: Khách · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Không đăng nhập
- **Các bước thực hiện**:
  1. Gọi POST /user/join_requests/seen
  2. Gọi POST /user/invites/seen
- **Dữ liệu đầu vào**: 2 request không session
- **Kết quả mong đợi**: Chuyển hướng /login (302); không thay đổi mốc `*_seen_at`
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/BadgeSeenTest.php`

## 4. Cách chạy nhóm test này

```powershell
cd G:\MyApp\laragon\www\Team-Assign
php artisan test tests/Feature/Admin/StatisticsTest.php tests/Feature/AdminNotificationTest.php tests/Feature/JoinRequestNotificationTest.php tests/Feature/BadgeSeenTest.php
```

## 5. Ghi chú & rủi ro

- Thống kê dùng ĐÚNG schema hiện tại: trưởng nhóm suy ra từ `groups.leader_id` (không dùng `users.role = leader` — đã xóa ở migration 2026_09_13), không dùng `users.is_have_group`, và status yêu cầu là `Pending / Accepted / Rejected` (KHÔNG có `Approved`).
- Badge "Chat" trên sidebar lấy từ `ChatUnreadService::totalFor()` (tính thật), KHÔNG tin cột cache `users.unread_message_count`.
- Badge "Yêu cầu"/"Lời mời" đếm trực tiếp từ bảng `join_requests`/`invites`: bấm vào mục ⇒ ghi mốc `*_seen_at` ⇒ badge về 0 tới khi có bản ghi MỚI hơn mốc.
- Badge "Thông báo" (`users.unread_notifications`) tăng khi có thông báo mới và về 0 sau `POST /notifications/mark-all-read`.
- FEATURE_STATUS #17 ghi rõ: trang Thống kê hiện là bảng + progress bar, CHƯA có Chart.js (đề xuất nâng cấp ở `docs/diagrams/admin-charts.md`) ⇒ các case biểu đồ đánh dấu Chưa chạy tay.
- Case có nhãn `Chưa chạy tay` cần tự chạy trên trình duyệt (2 tài khoản nếu cần realtime) rồi đổi trạng thái trong `data/03-thong-ke-va-thong-bao.php` và export lại.

<sub>Sinh tự động bởi `php artisan testcases:export` · nguồn: `docs/test-cases/data/03-thong-ke-va-thong-bao.php`</sub>
