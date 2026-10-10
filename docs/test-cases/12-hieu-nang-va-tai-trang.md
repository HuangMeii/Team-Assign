# Nhóm 12 — Hiệu năng & tải trang (P1: D1→D5)

> **Mã nhóm**: `TC-PERF` · **Chức năng**: docs/Final-Bug-Fixes/P1-toi-uu-hieu-nang-giao-dien.md (không gắn số FEATURE_STATUS): D1 asset local thay CDN, D2 route/view/event cache + OPcache + CACHE_STORE=file, D3 chống N+1 danh sách, D4 broadcast sau response + MAIL_TIMEOUT
> **Số test case**: 6 — Pass: **4** · Fail: **0** · Chưa chạy tay: **2**
> **Môi trường**: MySQL team_assign_test; npm đã chạy `npm ci && npm run build` (public/build là gitignored); máy dev đã bật OPcache ở php.ini Laragon và restart web server.
> ↻ File này **sinh tự động** từ `docs/test-cases/data/12-hieu-nang-va-tai-trang.php` — sửa dữ liệu ở đó rồi chạy `php artisan testcases:export` (đừng sửa file .md này).

## 1. Mục tiêu & phạm vi

Kiểm tra giao diện không còn phụ thuộc mạng ngoài (CDN), trang không bị chặn bởi broadcast tới Reverb hay SMTP chậm, route cache hoạt động đúng, và N+1 ở trang danh sách đã được gộp.

## 2. Bảng tóm tắt test case

| Mã TC | Tên / Mục tiêu | Role | Loại | Ưu tiên | Trạng thái |
|---|---|---|---|---|---|
| `TC-PERF-01` | Không trang nào còn request CSS/JS tới CDN bên thứ ba (cdnjs/jsdelivr) | Hệ thống | UI | Cao | Pass |
| `TC-PERF-02` | Gửi tin/bài/thông báo không phải chờ publish tới Reverb trước khi trả response | Hệ thống | Realtime | Cao | Pass |
| `TC-PERF-03` | `/` redirect theo vai trò sau khi closure chuyển sang HomeController; routes đóng gói cache được | Hệ thống | API | Trung bình | Pass |
| `TC-PERF-04` | Gửi mail không treo vô hạn khi Gmail chậm/throttle | Hệ thống | API | Trung bình | Pass |
| `TC-PERF-05` | Trang đăng nhập load nhanh ngay cả khi mạng ngoài chập chờn | Tester | UI | Trung bình | Chưa chạy tay |
| `TC-PERF-06` | Số query mở trang danh sách sinh viên/nhóm không tăng theo số dòng | Tester | DB | Trung bình | Chưa chạy tay |

## 3. Chi tiết test case

### TC-PERF-01 — Không trang nào còn request CSS/JS tới CDN bên thứ ba (cdnjs/jsdelivr)

- **Chức năng**: D1 — asset local, không CDN · **Role**: Hệ thống · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đã npm run build
- **Các bước thực hiện**:
  1. Mở DevTools tab Network
  2. Vào /login, /, /students
  3. Lọc theo domain ngoài localhost
- **Dữ liệu đầu vào**: Trang đăng nhập + trang sau đăng nhập
- **Kết quả mong đợi**: Không request tới cdnjs.cloudflare.com / cdn.jsdelivr.net; CSS vendor tải từ chính origin
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/PerformanceBatchTest.php`

### TC-PERF-02 — Gửi tin/bài/thông báo không phải chờ publish tới Reverb trước khi trả response

- **Chức năng**: D4 — broadcast sau response · **Role**: Hệ thống · **Loại**: Realtime · **Ưu tiên**: Cao
- **Tiền điều kiện**: Reverb bật hoặc tắt đều được (fail-open)
- **Các bước thực hiện**:
  1. Gửi tin chat 1-1
  2. Gửi tin chat nhóm
  3. Gửi bài bảng tin
  4. Xác nhận client nhận được broadcast
- **Dữ liệu đầu vào**: Bất kỳ nội dung chat/thông báo nào
- **Kết quả mong đợi**: Response trả ngay; event vẫn dispatch trong phase terminating của cùng request; lỗi publish chỉ log warning
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/PerformanceBatchTest.php + tests/Feature/Admin/AdminGroupMessageTest.php`

### TC-PERF-03 — `/` redirect theo vai trò sau khi closure chuyển sang HomeController; routes đóng gói cache được

- **Chức năng**: D2 — route cache + HomeController · **Role**: Hệ thống · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đã php artisan route:cache
- **Các bước thực hiện**:
  1. Khách bấm /
  2. SV đăng nhập bấm /
  3. GV bấm /
  4. Admin bấm /
- **Dữ liệu đầu vào**: 4 trường hợp vai trò
- **Kết quả mong đợi**: Khách → /login; SV → user.dashboard; GV → dashboard; Admin → admin.users.index; không lỗi route closure
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/PerformanceBatchTest.php + tests/Feature/Auth/RememberLoginTest.php`

### TC-PERF-04 — Gửi mail không treo vô hạn khi Gmail chậm/throttle

- **Chức năng**: D4 — SMTP timeout · **Role**: Hệ thống · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: config/mail.php có MAIL_TIMEOUT (mặc định 10s)
- **Các bước thực hiện**:
  1. Đọc config mailers.smtp.timeout
  2. Gửi email L06 với SMTP chậm (mô phỏng)
- **Dữ liệu đầu vào**: MAIL_TIMEOUT=10
- **Kết quả mong đợi**: timeout = 10 (không null); request gửi mail chờ tối đa 10s rồi trả lỗi
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/PerformanceBatchTest.php`

### TC-PERF-05 — Trang đăng nhập load nhanh ngay cả khi mạng ngoài chập chờn

- **Chức năng**: D1 — first paint không phụ thuộc mạng ngoài · **Role**: Tester · **Loại**: UI · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đã build asset; DevTools đang mở
- **Các bước thực hiện**:
  1. Hard reload /login (disable cache)
  2. Xem tab Performance tới FCP
  3. Tắt mạng ngoài reload lại
- **Dữ liệu đầu vào**: Trang /login
- **Kết quả mong đợi**: FCP không còn do chờ DNS/TLS cdnjs; reload offline vẫn hiện nền + form + icon
- **Kết quả thực tế**: Chưa chạy tay
- **Trạng thái**: **Chưa chạy tay**
- **Test tự động**: (thủ công — chạy trên trình duyệt)

### TC-PERF-06 — Số query mở trang danh sách sinh viên/nhóm không tăng theo số dòng

- **Chức năng**: D3 — không N+1 ở danh sách · **Role**: Tester · **Loại**: DB · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Mở Debugbar (nếu cài) hoặc bật MySQL general log
- **Các bước thực hiện**:
  1. Vào /students với ~15 SV
  2. Vào /groups với ~10 nhóm
  3. Đếm số query
- **Dữ liệu đầu vào**: Danh sách có dữ liệu thật
- **Kết quả mong đợi**: Số query gần như không đổi khi tăng số dòng (không còn +2 query/dòng; không còn per-group member count)
- **Kết quả thực tế**: Chưa chạy tay
- **Trạng thái**: **Chưa chạy tay**
- **Test tự động**: (thủ công — chạy trên trình duyệt)

## 4. Cách chạy nhóm test này

```powershell
cd G:\MyApp\laragon\www\Team-Assign
php artisan test tests/Feature/PerformanceBatchTest.php tests/Feature/Admin/AdminGroupMessageTest.php tests/Feature/Auth/RememberLoginTest.php
```

## 5. Ghi chú & rủi ro

- D1: `vendor.css` (Bootstrap + FA) nạp trong <head> qua @vite; trang guest dùng `fa.css` RIÊNG vì dựng bằng Tailwind.
- Chống tái phát: test quét toàn bộ resources/views — thêm lại 1 link cdnjs/jsdelivr là test đỏ.
- D2: sửa routes/views phải chạy lại route:cache/view:cache; TUYỆT ĐỐI không bật config:cache (nuốt env phpunit).
- D4: broadcast bọc AfterResponse::broadcast() chạy ở phase terminating — assert được trong cùng request.
- Badge tin nhắn cố ý KHÔNG cache (giữ tươi 4 query indexed) — xem P1 mục D3.
- Case có nhãn `Chưa chạy tay` cần tự chạy trên trình duyệt (2 tài khoản nếu cần realtime) rồi đổi trạng thái trong `data/12-hieu-nang-va-tai-trang.php` và export lại.

<sub>Sinh tự động bởi `php artisan testcases:export` · nguồn: `docs/test-cases/data/12-hieu-nang-va-tai-trang.php`</sub>
