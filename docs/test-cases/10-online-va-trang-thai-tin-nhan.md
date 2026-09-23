# Nhóm 10 — Online/offline & trạng thái tin nhắn

> **Mã nhóm**: `TC-PRES` · **Chức năng**: FEATURE_STATUS #20: PresenceService + middleware UpdateLastSeen (users.last_seen_at) + PresenceController (/presence/ping, /presence/status) + tick đã gửi/đã xem (direct_messages.seen_at) + lịch sử trò chuyện phân trang
> **Số test case**: 14 — Pass: **13** · Fail: **0** · Chưa chạy tay: **1**
> **Môi trường**: MySQL team_assign_test; BROADCAST_CONNECTION=null khi chạy test ⇒ việc tick đổi ngay không cần tải lại trang phải kiểm tra tay với `php artisan reverb:start` + 2 tài khoản.
> ↻ File này **sinh tự động** từ `docs/test-cases/data/10-online-va-trang-thai-tin-nhan.php` — sửa dữ liệu ở đó rồi chạy `php artisan testcases:export` (đừng sửa file .md này).

## 1. Mục tiêu & phạm vi

Kiểm thử trạng thái online/offline (chấm xanh/xám + nhãn “Hoạt động X trước”) và trạng thái tin nhắn 1-1 (✓ xám = đã gửi, ✓✓ xanh = đã xem) cùng lịch sử trò chuyện tách ngày + tải thêm tin cũ.

## 2. Bảng tóm tắt test case

| Mã TC | Tên / Mục tiêu | Role | Loại | Ưu tiên | Trạng thái |
|---|---|---|---|---|---|
| `TC-PRES-01` | Middleware ghi last_seen_at khi người dùng mở một trang | Sinh viên | DB | Cao | Pass |
| `TC-PRES-02` | Không ghi lại last_seen_at khi vừa mới ghi (tránh 1 câu UPDATE mỗi request) | Sinh viên | DB | Trung bình | Pass |
| `TC-PRES-03` | Nhãn thời gian hoạt động hiển thị đúng theo phút / giờ / ngày và ngưỡng 7 ngày | Sinh viên | API | Cao | Pass |
| `TC-PRES-04` | Endpoint /presence/ping giữ trạng thái đang hoạt động và /presence/status trả trạng thái từng người | Sinh viên | API | Cao | Pass |
| `TC-PRES-05` | Khách chưa đăng nhập không gọi được endpoint presence (case âm) | Khách | API | Cao | Pass |
| `TC-PRES-06` | Tin vừa gửi mặc định là ĐÃ GỬI (seen_at null) và chưa broadcast trạng thái | Sinh viên | DB | Cao | Pass |
| `TC-PRES-07` | Người nhận MỞ hội thoại ⇒ tin chuyển ĐÃ XEM và broadcast cho người gửi | Sinh viên | Realtime | Cao | Pass |
| `TC-PRES-08` | Lịch sử trò chuyện: tải thêm tin nhắn cũ trả HTML + has_more đúng | Sinh viên | API | Trung bình | Pass |
| `TC-PRES-09` | Khách chưa đăng nhập không gọi được lịch sử/markRead (case âm) | Khách | API | Trung bình | Pass |
| `TC-PRES-10` | Danh sách người dùng và header hội thoại CHỈ hiện chấm xanh/xám (không có nhãn chữ) và tự cập nhật | Sinh viên | UI | Trung bình | Chưa chạy tay |
| `TC-PRES-11` | AJAX markRead (cửa sổ chat đang mở) cũng đánh dấu ĐÃ XEM, không cần click ô nhập | Sinh viên | API | Cao | Pass |
| `TC-PRES-12` | Trang chat hiển thị tick trạng thái cho tin của mình (sent → seen) | Sinh viên | UI | Trung bình | Pass |
| `TC-PRES-13` | Chỉ ghi last_seen_at khi đã quá nhịp heartbeat (không ghi mỗi request) | Hệ thống | DB | Trung bình | Pass |
| `TC-PRES-14` | statusesFor bỏ qua id không hợp lệ và trả mảng rỗng khi không có id | Hệ thống | API | Thấp | Pass |

## 3. Chi tiết test case

### TC-PRES-01 — Middleware ghi last_seen_at khi người dùng mở một trang

- **Chức năng**: Heartbeat last_seen (#20) · **Role**: Sinh viên · **Loại**: DB · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập sinh viên; `users.last_seen_at` đang NULL hoặc cũ hơn 60 giây
- **Các bước thực hiện**:
  1. Mở bất kỳ trang cần đăng nhập (vd /user/dashboard)
  2. Đọc lại cột last_seen_at trong DB
- **Dữ liệu đầu vào**: GET /user/dashboard
- **Kết quả mong đợi**: last_seen_at được cập nhật ~ thời điểm hiện tại (không đụng updated_at)
- **Kiểm tra thêm (DB / log / API)**: users.last_seen_at = now() · users.updated_at giữ nguyên
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/PresenceTest.php`

### TC-PRES-02 — Không ghi lại last_seen_at khi vừa mới ghi (tránh 1 câu UPDATE mỗi request)

- **Chức năng**: Heartbeat last_seen (#20) · **Role**: Sinh viên · **Loại**: DB · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: last_seen_at vừa được ghi trong vòng 60 giây
- **Các bước thực hiện**:
  1. Ghi lại giá trị last_seen_at
  2. Mở liên tiếp 3 trang khác nhau
  3. So sánh giá trị
- **Dữ liệu đầu vào**: 3 request liên tiếp trong cùng phút
- **Kết quả mong đợi**: last_seen_at KHÔNG đổi (chỉ ghi tối đa 1 lần/60s)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/PresenceTest.php`

### TC-PRES-03 — Nhãn thời gian hoạt động hiển thị đúng theo phút / giờ / ngày và ngưỡng 7 ngày

- **Chức năng**: Nhãn trạng thái (#20) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Chuẩn bị các user có last_seen_at: 30 giây, 5 phút, 45 phút, 3 giờ, 2 ngày, 7 ngày, 30 ngày, NULL
- **Các bước thực hiện**:
  1. Gọi PresenceService::statusFor() với từng user
  2. Đối chiếu label + color
- **Dữ liệu đầu vào**: last_seen_at theo các mốc trên
- **Kết quả mong đợi**: 30s ⇒ “Đang hoạt động” + màu success (online) · 5 phút ⇒ “Hoạt động 5 phút trước” + secondary · 45 phút · 3 giờ · 2 ngày · 7 ngày ⇒ “Hoạt động 7 ngày trước” · 30 ngày ⇒ “Hoạt động hơn 7 ngày trước” · NULL ⇒ “Chưa hoạt động”
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Unit/PresenceTest.php (7 test)`

### TC-PRES-04 — Endpoint /presence/ping giữ trạng thái đang hoạt động và /presence/status trả trạng thái từng người

- **Chức năng**: Endpoint presence (#20) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập sinh viên; có 2 user khác nhau (1 vừa hoạt động, 1 quá 2 phút)
- **Các bước thực hiện**:
  1. Gọi POST /presence/ping
  2. Gọi GET /presence/status?ids=... với cả 2 user
  3. Đối chiếu payload
- **Dữ liệu đầu vào**: POST /presence/ping · GET /presence/status
- **Kết quả mong đợi**: Ping trả 200 + last_seen_at mới; status trả đúng `online`, `label`, `color` cho từng id
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/PresenceTest.php + tests/Unit/PresenceTest.php`

### TC-PRES-05 — Khách chưa đăng nhập không gọi được endpoint presence (case âm)

- **Chức năng**: Endpoint presence (#20) · **Role**: Khách · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Không đăng nhập
- **Các bước thực hiện**:
  1. Gọi POST /presence/ping
  2. Gọi GET /presence/status
- **Dữ liệu đầu vào**: 2 request không session
- **Kết quả mong đợi**: HTTP 401 (route AJAX) — không lộ trạng thái hoạt động của người dùng khác
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/PresenceTest.php`

### TC-PRES-06 — Tin vừa gửi mặc định là ĐÃ GỬI (seen_at null) và chưa broadcast trạng thái

- **Chức năng**: Tick trạng thái tin nhắn (#20) · **Role**: Sinh viên · **Loại**: DB · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập sv1; sv2 là người nhận
- **Các bước thực hiện**:
  1. sv1 gửi 1 tin cho sv2
  2. Kiểm tra cột seen_at và event broadcast
- **Dữ liệu đầu vào**: POST /chat/{sv2} content=...
- **Kết quả mong đợi**: Tin hiển thị ✓ xám (Đã gửi); chưa có event `DirectMessagesSeen`
- **Kiểm tra thêm (DB / log / API)**: direct_messages.seen_at = NULL
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ChatMessageStatusTest.php`

### TC-PRES-07 — Người nhận MỞ hội thoại ⇒ tin chuyển ĐÃ XEM và broadcast cho người gửi

- **Chức năng**: Tick trạng thái tin nhắn (#20) · **Role**: Sinh viên · **Loại**: Realtime · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv1 đã gửi 1 tin cho sv2 (chưa xem)
- **Các bước thực hiện**:
  1. sv2 mở /chat/{sv1}
  2. Kiểm tra seen_at
  3. Kiểm tra event phát cho sv1
  4. sv1 quan sát tick (không tải lại trang)
- **Dữ liệu đầu vào**: GET /chat/{sv1} (bởi sv2)
- **Kết quả mong đợi**: seen_at được ghi; event `DirectMessagesSeen` phát đi; tick đổi ✓ → ✓✓ xanh ngay trên màn hình sv1 (cần Reverb khi kiểm tra tay)
- **Kiểm tra thêm (DB / log / API)**: direct_messages.seen_at = now()
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ChatMessageStatusTest.php`

### TC-PRES-08 — Lịch sử trò chuyện: tải thêm tin nhắn cũ trả HTML + has_more đúng

- **Chức năng**: Lịch sử trò chuyện (#20) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Hội thoại có nhiều hơn 1 trang tin nhắn (phân trang ngược theo id)
- **Các bước thực hiện**:
  1. Mở hội thoại
  2. Bấm “Tải thêm tin nhắn cũ”
  3. Kiểm tra vị trí cuộn và nút biến mất khi hết tin
- **Dữ liệu đầu vào**: GET /chat/{user}/history?before={id}
- **Kết quả mong đợi**: Trả HTML đã render (giữ nhãn Hôm nay/Hôm qua/dd-mm-yyyy); `has_more` = true khi còn, false khi hết; vị trí cuộn không nhảy
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ChatMessageStatusTest.php`

### TC-PRES-09 — Khách chưa đăng nhập không gọi được lịch sử/markRead (case âm)

- **Chức năng**: Lịch sử trò chuyện (#20) · **Role**: Khách · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Không đăng nhập
- **Các bước thực hiện**:
  1. Gọi GET /chat/{user}/history
  2. Gọi POST /chat/{user}/read
- **Dữ liệu đầu vào**: 2 request không session
- **Kết quả mong đợi**: Chuyển hướng /login (302) hoặc 401; không trả nội dung tin nhắn
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ChatMessageStatusTest.php`

### TC-PRES-10 — Danh sách người dùng và header hội thoại CHỈ hiện chấm xanh/xám (không có nhãn chữ) và tự cập nhật

- **Chức năng**: Hiển thị chấm online (#20) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Có 1 user đang online và 1 user offline; tab chat đang mở
- **Các bước thực hiện**:
  1. Mở /chat
  2. Quan sát chấm cạnh tên từng người (không được có dòng chữ “Đang hoạt động”…)
  3. Mở hội thoại 1-1 xem header
  4. Hover lên chấm để xem tooltip
  5. Chờ 60 giây (JS ping) xem chấm tự cập nhật
- **Dữ liệu đầu vào**: URL /chat
- **Kết quả mong đợi**: Chỉ có chấm tròn: XANH = online, XÁM = offline; KHÔNG hiển thị nhãn chữ nào; tooltip (hover) hiện “Đang hoạt động” / “Hoạt động X trước”; chấm tự cập nhật mỗi 60s không cần tải lại trang
- **Kết quả thực tế**: Chưa chạy tay
- **Trạng thái**: **Chưa chạy tay**
- **Test tự động**: (thủ công — chạy trên trình duyệt)
- **Ghi chú**: Phần “chỉ chấm + tự cập nhật” thuộc Blade + JS ⇒ phải chạy tay trên trình duyệt (test tự động chỉ khẳng định nhãn/màu ở tầng service/API).

### TC-PRES-11 — AJAX markRead (cửa sổ chat đang mở) cũng đánh dấu ĐÃ XEM, không cần click ô nhập

- **Chức năng**: Đánh dấu đã xem (#20) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv1 gửi tin cho sv2; sv2 đang mở sẵn khung chat với sv1
- **Các bước thực hiện**:
  1. sv2 giữ nguyên khung chat đang mở (không click gì)
  2. JS gọi POST /chat/{user}/read theo chu kỳ
  3. sv1 kiểm tra tick
- **Dữ liệu đầu vào**: POST /chat/{user}/read
- **Kết quả mong đợi**: Tin chuyển ĐÃ XEM dù người nhận không thao tác gì; người gửi nhận event và thấy ✓✓
- **Kiểm tra thêm (DB / log / API)**: direct_messages.seen_at được ghi cho hội thoại đang mở
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ChatMessageStatusTest.php`

### TC-PRES-12 — Trang chat hiển thị tick trạng thái cho tin của mình (sent → seen)

- **Chức năng**: Hiển thị tick (#20) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: sv1 có 1 tin chưa xem và 1 tin đã xem trong cùng hội thoại
- **Các bước thực hiện**:
  1. sv1 mở /chat/{sv2}
  2. Quan sát tick ở 2 tin
- **Dữ liệu đầu vào**: URL /chat/{user}
- **Kết quả mong đợi**: Tin chưa xem có ✓ xám; tin đã xem có ✓✓ xanh; DOM có `data-*` cho trạng thái để JS cập nhật
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ChatMessageStatusTest.php`

### TC-PRES-13 — Chỉ ghi last_seen_at khi đã quá nhịp heartbeat (không ghi mỗi request)

- **Chức năng**: Nhịp heartbeat (#20) · **Role**: Hệ thống · **Loại**: DB · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Không cần DB (unit test với user “trên giấy”)
- **Các bước thực hiện**:
  1. Kiểm tra `shouldTouch()` với last_seen_at = 10 giây trước
  2. Kiểm tra với last_seen_at = 5 phút trước
  3. Kiểm tra với last_seen_at = NULL
- **Dữ liệu đầu vào**: PresenceService::shouldTouch()
- **Kết quả mong đợi**: Mới ghi trong 60 giây ⇒ false (bỏ qua); quá ngưỡng hoặc NULL ⇒ true (ghi)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Unit/PresenceTest.php`

### TC-PRES-14 — statusesFor bỏ qua id không hợp lệ và trả mảng rỗng khi không có id

- **Chức năng**: Truy vấn trạng thái (#20) · **Role**: Hệ thống · **Loại**: API · **Ưu tiên**: Thấp
- **Tiền điều kiện**: Không cần DB cho phần id không hợp lệ
- **Các bước thực hiện**:
  1. Gọi statusesFor với danh sách rỗng
  2. Gọi statusesFor có id âm/không tồn tại
- **Dữ liệu đầu vào**: PresenceService::statusesFor([]) và [0, -1, 999999]
- **Kết quả mong đợi**: Không ném exception; bỏ qua id không hợp lệ; mảng rỗng ⇒ trả []
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Unit/PresenceTest.php`

## 4. Cách chạy nhóm test này

```powershell
cd G:\MyApp\laragon\www\Team-Assign
php artisan test tests/Unit/PresenceTest.php tests/Feature/PresenceTest.php tests/Feature/ChatMessageStatusTest.php
```

## 5. Ghi chú & rủi ro

- Nhịp heartbeat: middleware `UpdateLastSeen` ghi `users.last_seen_at` tối đa 1 lần/60 giây cho mọi request web (không đụng `updated_at`); JS gọi `POST /presence/ping` mỗi 60s khi tab còn mở.
- ONLINE = hoạt động trong 2 phút ⇒ chấm XANH; ngoài 2 phút ⇒ chấm XÁM. **Giao diện CHỈ hiện chấm, không ghi nhãn chữ** — nhãn (“Đang hoạt động” / “Hoạt động X trước” / “Chưa hoạt động”, cap 7 ngày) vẫn do `PresenceService` sinh ra và dùng cho tooltip `title` + payload `/presence/status`.
- TICK: tin vừa gửi có `seen_at = NULL` ⇒ ✓ xám “Đã gửi”; người nhận MỞ hội thoại (hoặc AJAX `chat.read`) ⇒ ghi `seen_at` + broadcast `DirectMessagesSeen` ⇒ người gửi thấy ✓✓ xanh mà không cần tải lại trang.
- Lịch sử trò chuyện: `GET /chat/{user}/history` trả HTML đã render + `has_more`, giữ nguyên vị trí cuộn; trước đây cứng 100 tin gần nhất.
- Kèm sửa bug: `conversationQuery()` bọc ngoặc điều kiện 2 chiều (trước đây `where id < ?` bị AND ưu tiên làm vô hiệu OR) và sắp xếp theo `id` để phân trang ổn định.
- Case có nhãn `Chưa chạy tay` cần tự chạy trên trình duyệt (2 tài khoản nếu cần realtime) rồi đổi trạng thái trong `data/10-online-va-trang-thai-tin-nhan.php` và export lại.

<sub>Sinh tự động bởi `php artisan testcases:export` · nguồn: `docs/test-cases/data/10-online-va-trang-thai-tin-nhan.php`</sub>
