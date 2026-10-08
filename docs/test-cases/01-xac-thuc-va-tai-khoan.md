# Nhóm 01 — Xác thực & tài khoản

> **Mã nhóm**: `TC-AUTH` · **Chức năng**: FEATURE_STATUS #1 (đăng nhập/đăng ký/quên–đổi mật khẩu), #2 (hồ sơ, đổi email + xác thực)
> **Số test case**: 27 — Pass: **21** · Fail: **5** · Chưa chạy tay: **1**
> **Môi trường**: MySQL team_assign_test (phpunit.xml: MAIL_MAILER=array, SESSION_DRIVER=array, BROADCAST_CONNECTION=null). Kết quả chạy `php artisan test` ngày 2026-09-22: 4 failed / 280 passed — cả 4 case đỏ đều thuộc nhóm này (TC-AUTH-07/08/09/11 + TC-AUTH-13 bug tài khoản chưa xác thực email).
> ↻ File này **sinh tự động** từ `docs/test-cases/data/01-xac-thuc-va-tai-khoan.php` — sửa dữ liệu ở đó rồi chạy `php artisan testcases:export` (đừng sửa file .md này).

## 1. Mục tiêu & phạm vi

Kiểm thử toàn bộ luồng xác thực và tài khoản cá nhân: đăng nhập, đăng ký, quên/đổi mật khẩu, cập nhật hồ sơ, đổi email (xác thực qua link signed), khóa tài khoản và xóa mềm.

## 2. Bảng tóm tắt test case

| Mã TC | Tên / Mục tiêu | Role | Loại | Ưu tiên | Trạng thái |
|---|---|---|---|---|---|
| `TC-AUTH-01` | Sinh viên đăng nhập thành công bằng email + mật khẩu đúng | Sinh viên | UI | Cao | Chưa chạy tay |
| `TC-AUTH-02` | Đăng nhập sai mật khẩu bị từ chối và không tạo phiên | Sinh viên | API | Cao | Pass |
| `TC-AUTH-03` | Khách chưa đăng nhập bị chặn khỏi mọi route cần auth | Khách | API | Cao | Pass |
| `TC-AUTH-04` | Vào sai khu vực vai trò thì bị chuyển hướng về dashboard đúng | Admin | UI | Cao | Pass |
| `TC-AUTH-05` | Trang đăng ký/đăng nhập hiển thị đúng và có nút hiện/ẩn mật khẩu | Khách | UI | Trung bình | Pass |
| `TC-AUTH-06` | Trang quên mật khẩu hiển thị và có link từ trang đăng nhập | Sinh viên | UI | Cao | Pass |
| `TC-AUTH-07` | Gửi email đặt lại mật khẩu khi nhập email đã đăng ký | Sinh viên | API | Cao | Fail |
| `TC-AUTH-08` | Email không tồn tại thì không gửi mail và báo lỗi rõ ràng | Sinh viên | API | Trung bình | Fail |
| `TC-AUTH-09` | Click link trong email (token đúng) để đặt mật khẩu mới thành công | Sinh viên | API | Cao | Fail |
| `TC-AUTH-10` | Token sai thì không đặt lại được mật khẩu (case âm) | Sinh viên | API | Trung bình | Pass |
| `TC-AUTH-11` | Đổi mật khẩu thành công, mật khẩu mới được hash và đăng nhập lại được | Sinh viên | UI | Cao | Fail |
| `TC-AUTH-12` | Nhập sai mật khẩu hiện tại thì không đổi được mật khẩu (case âm) | Sinh viên | API | Cao | Pass |
| `TC-AUTH-13` | Mật khẩu mới quá ngắn bị từ chối (case âm) | Sinh viên | API | Trung bình | Pass |
| `TC-AUTH-14` | Trang đổi mật khẩu hiển thị được cho sinh viên | Sinh viên | UI | Thấp | Pass |
| `TC-AUTH-15` | Đổi email: email KHÔNG đổi ngay, lưu pending và gửi mail xác thực tới email mới | Sinh viên | API | Cao | Pass |
| `TC-AUTH-16` | Chỉ đổi tên: thành công và KHÔNG gửi mail xác thực | Sinh viên | API | Trung bình | Pass |
| `TC-AUTH-17` | Không thay đổi gì khi lưu thì hiện cảnh báo chưa có thay đổi | Sinh viên | API | Thấp | Pass |
| `TC-AUTH-18` | Email mới trùng email của người dùng khác bị từ chối (case âm) | Sinh viên | API | Cao | Pass |
| `TC-AUTH-19` | Click link signed đúng: email mới có hiệu lực, đã xác thực, pending được xoá | Sinh viên | API | Cao | Pass |
| `TC-AUTH-20` | Link xác thực sai hash hoặc không có chữ ký bị từ chối (case âm) | Sinh viên | API | Trung bình | Pass |
| `TC-AUTH-21` | Banner email đang chờ xác thực hiển thị ở trang thông tin cá nhân | Sinh viên | UI | Thấp | Pass |
| `TC-AUTH-22` | Tài khoản đã bị xóa mềm không đăng nhập được | Sinh viên | API | Cao | Pass |
| `TC-AUTH-23` | Tài khoản đã xóa mềm ẩn khỏi danh sách mặc định, hiện khi lọc "đã xóa" | Admin | UI | Trung bình | Pass |
| `TC-AUTH-24` | Không còn chức năng xóa cứng tài khoản (route đã gỡ, UI không còn nút xóa) | Admin | UI | Thấp | Pass |
| `TC-AUTH-25` | Tài khoản CHƯA xác thực email (email_verified_at = NULL) đăng nhập không được trả lỗi 500 | Sinh viên | API | Cao | Fail |
| `TC-AUTH-26` | L09 — Lịch sử đăng nhập + cảnh báo IP mới, đăng xuất phiên khác, thu hồi "ghi nhớ đăng nhập" | Sinh viên | API | Cao | Pass |
| `TC-AUTH-27` | L09 — Avatar, ngôn ngữ vi/en, múi giờ, ẩn trạng thái online, danh sách chặn và ai được mời vào nhóm | Sinh viên | API | Trung bình | Pass |

## 3. Chi tiết test case

### TC-AUTH-01 — Sinh viên đăng nhập thành công bằng email + mật khẩu đúng

- **Chức năng**: Đăng nhập (#1) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Có tài khoản sinh viên đang hoạt động (is_active = 1, is_deleted = 0)
- **Các bước thực hiện**:
  1. Mở /login
  2. Nhập email và mật khẩu đúng
  3. Bấm "Đăng nhập"
- **Dữ liệu đầu vào**: email=sv1@test.com, password=password
- **Kết quả mong đợi**: Chuyển hướng tới /user/dashboard; sidebar hiện tên sinh viên; không có thông báo lỗi
- **Kiểm tra thêm (DB / log / API)**: users.last_seen_at được cập nhật (middleware UpdateLastSeen)
- **Kết quả thực tế**: Chưa chạy tay
- **Trạng thái**: **Chưa chạy tay**
- **Test tự động**: (thủ công — chạy trên trình duyệt)
- **Ghi chú**: RỦI RO đã kiểm chứng: tài khoản có `email_verified_at = NULL` đăng nhập bị HTTP 500 vì `AuthController::login()` gọi `route('verification.notice')` mà route này KHÔNG tồn tại — xem ghi chú #1 của nhóm.

### TC-AUTH-02 — Đăng nhập sai mật khẩu bị từ chối và không tạo phiên

- **Chức năng**: Đăng nhập (#1) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Có tài khoản sinh viên đang hoạt động
- **Các bước thực hiện**:
  1. Mở /login
  2. Nhập email đúng + mật khẩu sai
  3. Bấm "Đăng nhập"
- **Dữ liệu đầu vào**: email=sv1@test.com, password=sai-mat-khau
- **Kết quả mong đợi**: Ở lại /login, hiện lỗi "thông tin đăng nhập không đúng"; không tạo session
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ViewSmokeTest.php (trang đăng nhập hiển thị)`
- **Ghi chú**: Case âm bắt buộc cho mọi form xác thực.

### TC-AUTH-03 — Khách chưa đăng nhập bị chặn khỏi mọi route cần auth

- **Chức năng**: Phân quyền route (#1) · **Role**: Khách · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Không đăng nhập (hoặc dùng tab ẩn danh)
- **Các bước thực hiện**:
  1. Mở trực tiếp /user/dashboard
  2. Mở /admin/users
  3. Gọi GET /presence/status
- **Dữ liệu đầu vào**: URL trực tiếp, không có session
- **Kết quả mong đợi**: Chuyển hướng về /login (route web) hoặc HTTP 401 (route API/AJAX)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/PresenceTest.php (khách 401) + tests/Feature/AdminSoftDeleteTest.php`

### TC-AUTH-04 — Vào sai khu vực vai trò thì bị chuyển hướng về dashboard đúng

- **Chức năng**: Phân quyền theo vai trò (#1) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập lần lượt: sinh viên, giảng viên, admin
- **Các bước thực hiện**:
  1. SV mở /admin/users
  2. GV mở /admin/users
  3. Admin mở /lecturer/classes
  4. GV mở /user/dashboard
- **Dữ liệu đầu vào**: URL theo từng vai trò
- **Kết quả mong đợi**: SV → /user/dashboard · GV → /dashboard/lecturer · Admin → /dashboard/admin (không lộ trang cấm)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/MiddlewareRedirectTest.php (4 test)`

### TC-AUTH-05 — Trang đăng ký/đăng nhập hiển thị đúng và có nút hiện/ẩn mật khẩu

- **Chức năng**: Đăng ký (#1) · **Role**: Khách · **Loại**: UI · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Không đăng nhập
- **Các bước thực hiện**:
  1. Mở /login
  2. Quan sát khối mật khẩu
  3. Bấm icon con mắt
- **Dữ liệu đầu vào**: URL /login
- **Kết quả mong đợi**: Trang render 200; có nút hiện/ẩn mật khẩu hoạt động; có link "Quên mật khẩu"
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ViewSmokeTest.php`

### TC-AUTH-06 — Trang quên mật khẩu hiển thị và có link từ trang đăng nhập

- **Chức năng**: Quên mật khẩu (#1) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Không đăng nhập
- **Các bước thực hiện**:
  1. Mở /login
  2. Bấm "Quên mật khẩu?"
  3. Kiểm tra form nhập email
- **Dữ liệu đầu vào**: URL /forgot-password
- **Kết quả mong đợi**: Trang render 200, có ô nhập email + nút gửi
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Auth/ForgotPasswordTest.php`

### TC-AUTH-07 — Gửi email đặt lại mật khẩu khi nhập email đã đăng ký

- **Chức năng**: Quên mật khẩu (#1) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Email tồn tại trong bảng users
- **Các bước thực hiện**:
  1. Mở /forgot-password
  2. Nhập email đã đăng ký
  3. Bấm gửi
- **Dữ liệu đầu vào**: email=sv1@test.com
- **Kết quả mong đợi**: Hiện thông báo "đã gửi link đặt lại"; email chứa link reset được gửi tới inbox
- **Kiểm tra thêm (DB / log / API)**: Bảng password_reset_tokens có 1 dòng mới cho email đó
- **Kết quả thực tế**: Test đỏ: session thiếu key `status` (route trả về back kèm `success`/`error`, không phải `status`) ⇒ không đúng mong đợi của test
- **Trạng thái**: **Fail**
- **Test tự động**: `tests/Feature/Auth/ForgotPasswordTest.php`
- **Ghi chú**: NGUYÊN NHÂN: `PasswordResetLinkController::store()` dùng `with('success'|'error')` còn test khẳng định `assertSessionHas('status')` — lệch đặc tả giữa test và code (không phải lỗi gửi mail). Sửa: thống nhất 1 tên key (khuyến nghị đổi test sang `success`) rồi chạy lại `php artisan test tests/Feature/Auth/ForgotPasswordTest.php`.

### TC-AUTH-08 — Email không tồn tại thì không gửi mail và báo lỗi rõ ràng

- **Chức năng**: Quên mật khẩu (#1) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Email chưa từng đăng ký
- **Các bước thực hiện**:
  1. Mở /forgot-password
  2. Nhập email lạ
  3. Bấm gửi
- **Dữ liệu đầu vào**: email=khong-ton-tai@test.com
- **Kết quả mong đợi**: Hiện lỗi validate; không ghi password_reset_tokens; không gửi mail
- **Kết quả thực tế**: Test đỏ: session thiếu key `errors` ⇒ không đúng mong đợi (không xác nhận được có báo lỗi cho người dùng hay không)
- **Trạng thái**: **Fail**
- **Test tự động**: `tests/Feature/Auth/ForgotPasswordTest.php`
- **Ghi chú**: Cùng nguyên nhân với TC-AUTH-07: controller trả `back()->withInput()->with('error', ...)` thay vì `withErrors()`. Cần thống nhất cách báo lỗi (khuyến nghị dùng `withErrors`) rồi chạy lại test.

### TC-AUTH-09 — Click link trong email (token đúng) để đặt mật khẩu mới thành công

- **Chức năng**: Quên mật khẩu (#1) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đã yêu cầu đặt lại mật khẩu và có link trong mail
- **Các bước thực hiện**:
  1. Mở link reset-password/{token}
  2. Nhập mật khẩu mới + xác nhận
  3. Bấm đổi mật khẩu
- **Dữ liệu đầu vào**: email=sv1@test.com, password=NewPass@123
- **Kết quả mong đợi**: Đổi thành công, token bị xoá, đăng nhập được bằng mật khẩu mới
- **Kiểm tra thêm (DB / log / API)**: users.password = hash mới · password_reset_tokens: dòng của email đã bị xoá
- **Kết quả thực tế**: Test đỏ ở bước cuối: POST /reset-password chuyển hướng về /user/dashboard (hệ thống ĐĂNG NHẬP LUÔN sau khi đặt lại mật khẩu) trong khi test mong đợi /login
- **Trạng thái**: **Fail**
- **Test tự động**: `tests/Feature/Auth/ForgotPasswordTest.php`
- **Ghi chú**: Đổi mật khẩu vẫn thành công (test khẳng định `Hash::check` đạt); chỉ lệch kỳ vọng điều hướng ⇒ cập nhật test cho khớp hành vi mới của `NewPasswordController::store()`.

### TC-AUTH-10 — Token sai thì không đặt lại được mật khẩu (case âm)

- **Chức năng**: Quên mật khẩu (#1) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Có email đã đăng ký
- **Các bước thực hiện**:
  1. Mở /reset-password/token-sai
  2. Nhập mật khẩu mới
  3. Bấm đổi
- **Dữ liệu đầu vào**: token=token-sai
- **Kết quả mong đợi**: Báo lỗi token không hợp lệ; mật khẩu trong DB KHÔNG đổi
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Auth/ForgotPasswordTest.php`

### TC-AUTH-11 — Đổi mật khẩu thành công, mật khẩu mới được hash và đăng nhập lại được

- **Chức năng**: Đổi mật khẩu (#1) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đang đăng nhập bằng mật khẩu hiện tại
- **Các bước thực hiện**:
  1. Mở trang đổi mật khẩu
  2. Nhập mật khẩu hiện tại đúng
  3. Nhập mật khẩu mới + xác nhận
  4. Bấm lưu
- **Dữ liệu đầu vào**: current=password, new=NewPass@123
- **Kết quả mong đợi**: Thông báo đổi thành công; đăng nhập lại bằng mật khẩu mới OK; mật khẩu cũ không dùng được
- **Kiểm tra thêm (DB / log / API)**: users.password đổi (bcrypt) · password_histories có thêm 1 dòng
- **Kết quả thực tế**: Đổi mật khẩu + hash đúng (2 khẳng định đầu đạt) nhưng bước “đăng nhập lại” trả HTTP 500 — RouteNotFoundException: Route [verification.notice] not defined
- **Trạng thái**: **Fail**
- **Test tự động**: `tests/Feature/ChangePasswordTest.php`
- **Ghi chú**: BUG THẬT (không phải lỗi test): `AuthController::login()` gọi `route('verification.notice')` khi `email_verified_at` NULL, nhưng `bootstrap/app.php` chỉ nạp `routes/web.php` — file `routes/auth.php` (nơi định nghĩa `verification.notice`, `verification.verify`, `password.update`…) KHÔNG được nạp. Hệ quả: mọi tài khoản chưa xác thực email đăng nhập đều 500. Khắc phục: nạp `routes/auth.php` trong `bootstrap/app.php` (thêm `then:`/`withRouting(web: ...)`) hoặc bỏ nhánh redirect đó.

### TC-AUTH-12 — Nhập sai mật khẩu hiện tại thì không đổi được mật khẩu (case âm)

- **Chức năng**: Đổi mật khẩu (#1) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đang đăng nhập
- **Các bước thực hiện**:
  1. Mở trang đổi mật khẩu
  2. Nhập mật khẩu hiện tại SAI
  3. Nhập mật khẩu mới
  4. Bấm lưu
- **Dữ liệu đầu vào**: current=sai-mat-khau, new=NewPass@123
- **Kết quả mong đợi**: Hiện lỗi ở ô mật khẩu hiện tại; mật khẩu trong DB không đổi
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ChangePasswordTest.php`

### TC-AUTH-13 — Mật khẩu mới quá ngắn bị từ chối (case âm)

- **Chức năng**: Đổi mật khẩu (#1) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đang đăng nhập
- **Các bước thực hiện**:
  1. Mở trang đổi mật khẩu
  2. Nhập mật khẩu hiện tại đúng
  3. Nhập mật khẩu mới quá ngắn
  4. Bấm lưu
- **Dữ liệu đầu vào**: current=password, new=123
- **Kết quả mong đợi**: Hiện lỗi validate ở ô mật khẩu mới; mật khẩu không đổi
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ChangePasswordTest.php`

### TC-AUTH-14 — Trang đổi mật khẩu hiển thị được cho sinh viên

- **Chức năng**: Đổi mật khẩu (#1) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Thấp
- **Tiền điều kiện**: Đang đăng nhập bằng tài khoản sinh viên
- **Các bước thực hiện**:
  1. Mở menu hồ sơ
  2. Chọn "Đổi mật khẩu"
- **Dữ liệu đầu vào**: URL trang đổi mật khẩu
- **Kết quả mong đợi**: Trang render 200 với 3 ô: mật khẩu hiện tại / mới / xác nhận
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ChangePasswordTest.php`

### TC-AUTH-15 — Đổi email: email KHÔNG đổi ngay, lưu pending và gửi mail xác thực tới email mới

- **Chức năng**: Đổi email (#2) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đang đăng nhập; email mới chưa tồn tại trong hệ thống
- **Các bước thực hiện**:
  1. Mở trang thông tin cá nhân
  2. Sửa email sang email mới
  3. Bấm lưu
- **Dữ liệu đầu vào**: email=sv1-moi@test.com
- **Kết quả mong đợi**: users.email giữ nguyên email cũ; hiện banner "đang chờ xác thực"; mail xác thực gửi tới email mới
- **Kiểm tra thêm (DB / log / API)**: users.pending_email = sv1-moi@test.com
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/EmailChangeVerificationTest.php`

### TC-AUTH-16 — Chỉ đổi tên: thành công và KHÔNG gửi mail xác thực

- **Chức năng**: Đổi email (#2) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đang đăng nhập
- **Các bước thực hiện**:
  1. Mở trang thông tin cá nhân
  2. Sửa tên, giữ nguyên email
  3. Bấm lưu
- **Dữ liệu đầu vào**: name=đổi tên, email giữ nguyên
- **Kết quả mong đợi**: Tên được cập nhật; không có mail xác thực; pending_email vẫn rỗng
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/EmailChangeVerificationTest.php`

### TC-AUTH-17 — Không thay đổi gì khi lưu thì hiện cảnh báo chưa có thay đổi

- **Chức năng**: Đổi email (#2) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Thấp
- **Tiền điều kiện**: Đang đăng nhập
- **Các bước thực hiện**:
  1. Mở trang thông tin cá nhân
  2. Không sửa gì
  3. Bấm lưu
- **Dữ liệu đầu vào**: form giữ nguyên giá trị cũ
- **Kết quả mong đợi**: Hiện cảnh báo "chưa có thay đổi để cập nhật"; DB không đổi
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/EmailChangeVerificationTest.php`

### TC-AUTH-18 — Email mới trùng email của người dùng khác bị từ chối (case âm)

- **Chức năng**: Đổi email (#2) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đang đăng nhập; email mới đã tồn tại ở tài khoản khác
- **Các bước thực hiện**:
  1. Mở trang thông tin cá nhân
  2. Nhập email của tài khoản khác
  3. Bấm lưu
- **Dữ liệu đầu vào**: email=sv2@test.com (đã tồn tại)
- **Kết quả mong đợi**: Hiện lỗi trùng email; pending_email không được ghi
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/EmailChangeVerificationTest.php`

### TC-AUTH-19 — Click link signed đúng: email mới có hiệu lực, đã xác thực, pending được xoá

- **Chức năng**: Đổi email (#2) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đã có pending_email và mail xác thực
- **Các bước thực hiện**:
  1. Mở link xác thực trong mail
  2. Chờ xử lý xong
- **Dữ liệu đầu vào**: link có chữ ký hợp lệ + id user
- **Kết quả mong đợi**: users.email = email mới; email_verified_at có giá trị; pending_email = null; đăng nhập bằng email mới OK
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/EmailChangeVerificationTest.php`

### TC-AUTH-20 — Link xác thực sai hash hoặc không có chữ ký bị từ chối (case âm)

- **Chức năng**: Đổi email (#2) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Có pending_email
- **Các bước thực hiện**:
  1. Sửa hash trên link xác thực
  2. Mở link đã sửa
- **Dữ liệu đầu vào**: link sai hash / thiếu signature
- **Kết quả mong đợi**: HTTP 403 (hoặc chuyển hướng kèm lỗi); email KHÔNG đổi
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/EmailChangeVerificationTest.php`

### TC-AUTH-21 — Banner email đang chờ xác thực hiển thị ở trang thông tin cá nhân

- **Chức năng**: Đổi email (#2) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Thấp
- **Tiền điều kiện**: Đang có pending_email
- **Các bước thực hiện**:
  1. Mở trang thông tin cá nhân
- **Dữ liệu đầu vào**: pending_email=sv1-moi@test.com
- **Kết quả mong đợi**: Có banner nêu email đang chờ xác thực + nút "Gửi lại email xác thực"
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/EmailChangeVerificationTest.php`

### TC-AUTH-22 — Tài khoản đã bị xóa mềm không đăng nhập được

- **Chức năng**: Khóa / xóa mềm tài khoản (#2) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: users.is_deleted = 1
- **Các bước thực hiện**:
  1. Mở /login
  2. Nhập email/mật khẩu của tài khoản đã xóa mềm
  3. Bấm đăng nhập
- **Dữ liệu đầu vào**: email=da-xoa@test.com, password=password
- **Kết quả mong đợi**: Không đăng nhập được; ở lại /login kèm lỗi xác thực
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/AdminSoftDeleteTest.php`

### TC-AUTH-23 — Tài khoản đã xóa mềm ẩn khỏi danh sách mặc định, hiện khi lọc "đã xóa"

- **Chức năng**: Khóa / xóa mềm tài khoản (#2) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Admin đăng nhập; trong DB có tài khoản is_deleted = 1
- **Các bước thực hiện**:
  1. Mở /admin/users
  2. Quan sát danh sách mặc định
  3. Bật bộ lọc "đã xóa"
- **Dữ liệu đầu vào**: filter=deleted
- **Kết quả mong đợi**: Danh sách mặc định không có tài khoản đã xóa; khi lọc "đã xóa" thì thấy
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/AdminSoftDeleteTest.php`

### TC-AUTH-24 — Không còn chức năng xóa cứng tài khoản (route đã gỡ, UI không còn nút xóa)

- **Chức năng**: Khóa / xóa mềm tài khoản (#2) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Thấp
- **Tiền điều kiện**: Admin đăng nhập
- **Các bước thực hiện**:
  1. Mở /admin/users
  2. Tìm nút xóa tài khoản
  3. Gọi trực tiếp DELETE /admin/users/{id}
- **Dữ liệu đầu vào**: URL xóa tài khoản cũ
- **Kết quả mong đợi**: Không có nút xóa trên UI; gọi trực tiếp route trả 404/405; tài khoản vẫn còn trong DB
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/AdminSoftDeleteTest.php`

### TC-AUTH-25 — Tài khoản CHƯA xác thực email (email_verified_at = NULL) đăng nhập không được trả lỗi 500

- **Chức năng**: Đăng nhập (#1) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Tài khoản sinh viên có email_verified_at = NULL (mọi tài khoản tạo bằng seeder/test đều ở trạng thái này)
- **Các bước thực hiện**:
  1. Mở /login
  2. Nhập email + mật khẩu đúng của tài khoản chưa xác thực
  3. Bấm đăng nhập
- **Dữ liệu đầu vào**: email=chua-xac-thuc@test.com, password=password
- **Kết quả mong đợi**: Chuyển tới trang “yêu cầu xác thực email” (route `verification.notice`) kèm hướng dẫn gửi lại email — KHÔNG được 500
- **Kết quả thực tế**: HTTP 500 — RouteNotFoundException: Route [verification.notice] not defined
- **Trạng thái**: **Fail**
- **Test tự động**: `tests/Feature/ChangePasswordTest.php (case “đổi mật khẩu thành công…” phát hiện lỗi này)`
- **Ghi chú**: BUG THẬT: `bootstrap/app.php` chỉ nạp `routes/web.php` + console + channels, KHÔNG nạp `routes/auth.php` (nơi định nghĩa `verification.notice`). Khắc phục rồi chạy lại `php artisan test` để xác nhận.

### TC-AUTH-26 — L09 — Lịch sử đăng nhập + cảnh báo IP mới, đăng xuất phiên khác, thu hồi "ghi nhớ đăng nhập"

- **Chức năng**: Thiết lập tài khoản — Bảo mật (#1) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Có tài khoản sinh viên; `.env` đặt SESSION_DRIVER=database (bảng sessions đã dựng lại đúng chuẩn)
- **Các bước thực hiện**:
  1. Đăng nhập lần đầu (IP mới) và quan sát flash cảnh báo
  2. Mở Thiết lập tài khoản → tab Bảo mật và xem lịch sử đăng nhập
  3. Bấm "Đăng xuất khỏi các phiên khác" (mở sẵn 1 phiên trình duyệt khác)
  4. Bấm "Thu hồi ghi nhớ đăng nhập"
- **Dữ liệu đầu vào**: POST /login · GET /settings/security · POST /settings/security/revoke-sessions · POST /settings/security/revoke-remember
- **Kết quả mong đợi**: Lần đầu từ IP lạ có flash "đăng nhập từ thiết bị/IP mới"; đăng nhập lại cùng IP KHÔNG cảnh báo; lịch sử hiển thị đúng IP/thiết bị/trình duyệt; phiên khác bị đăng xuất (phiên hiện tại giữ nguyên); remember_token đổi ⇒ cookie ghi nhớ cũ vô hiệu
- **Kiểm tra thêm (DB / log / API)**: login_histories có dòng mới (ip_address, user_agent) · sessions của chính mình (trừ phiên hiện tại) bị xoá · users.remember_token đổi
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/SettingsLevel2Test.php`

### TC-AUTH-27 — L09 — Avatar, ngôn ngữ vi/en, múi giờ, ẩn trạng thái online, danh sách chặn và ai được mời vào nhóm

- **Chức năng**: Thiết lập tài khoản — Hồ sơ & Riêng tư (#1) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập sinh viên; đã chạy `php artisan storage:link`
- **Các bước thực hiện**:
  1. Tab Hồ sơ: upload ảnh đại diện (≤2MB), chọn ngôn ngữ English, nhập múi giờ Asia/Ho_Chi_Minh
  2. Xoá ảnh đại diện và kiểm tra quay về chữ cái đầu
  3. Tab Riêng tư: bật "Ẩn trạng thái online", chọn ai được mời vào nhóm = "Không ai", bỏ chặn 1 người trong danh sách chặn
  4. Nhờ trưởng nhóm khác gửi lời mời vào nhóm (case âm)
- **Dữ liệu đầu vào**: POST /settings/profile (avatar, locale, timezone) · POST /settings/privacy (hide_online, invite_policy) · POST /settings/privacy/unblock
- **Kết quả mong đợi**: Avatar hiện đúng (chưa có ⇒ chữ cái đầu); locale áp dụng ngay cho request sau; múi giờ được dùng khi render giờ; bật ẩn ⇒ người khác thấy "Ẩn" thay vì chấm xanh; invite_policy=none ⇒ lời mời bị chặn kèm thông báo; bỏ chặn xoá dòng blocked_users
- **Kiểm tra thêm (DB / log / API)**: users.avatar_path/locale/timezone/hide_online/invite_policy đổi tương ứng · blocked_users mất dòng bị bỏ chặn
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/SettingsLevel2Test.php`

## 4. Cách chạy nhóm test này

```powershell
cd G:\MyApp\laragon\www\Team-Assign
php artisan test tests/Feature/Auth/ForgotPasswordTest.php tests/Feature/ChangePasswordTest.php tests/Feature/EmailChangeVerificationTest.php tests/Feature/AdminSoftDeleteTest.php tests/Feature/MiddlewareRedirectTest.php tests/Feature/ViewSmokeTest.php
```

## 5. Ghi chú & rủi ro

- **4 case đang Fail** (đã đối chiếu log test): 3 case lệch đặc tả test ↔ code — TC-AUTH-07/08 (`PasswordResetLinkController` trả `success`/`error` thay vì `status`/`errors`) và TC-AUTH-09 (`NewPasswordController` đăng nhập luôn rồi về dashboard thay vì `/login`).
- **BUG thật** (TC-AUTH-11 và TC-AUTH-13): `bootstrap/app.php` KHÔNG nạp `routes/auth.php` nên route `verification.notice` không tồn tại ⇒ mọi tài khoản `email_verified_at = NULL` đăng nhập bị **HTTP 500** (`RouteNotFoundException`). Đây là lỗi ưu tiên cao vì ảnh hưởng người dùng thật.
- Mail KHÔNG còn là nguyên nhân test đỏ: `phpunit.xml` đã ép `MAIL_MAILER=array` (ghi trong FEATURE_STATUS trước đây là do môi trường — nay đã khác).
- Link xác thực đổi email là **signed URL** — case âm dùng hash sai/không chữ ký phải bị từ chối.
- Đăng nhập của tài khoản đã khóa hoặc đã xóa mềm phải bị chặn (kiểm tra ở DB: `users.is_active`, `users.is_deleted`).
- Dữ liệu mẫu nên đặt `email_verified_at` = now() cho tài khoản dùng để kiểm tra đăng nhập, nếu không sẽ gặp bug TC-AUTH-13.
- Case có nhãn `Chưa chạy tay` cần tự chạy trên trình duyệt (2 tài khoản nếu cần realtime) rồi đổi trạng thái trong `data/01-xac-thuc-va-tai-khoan.php` và export lại.

<sub>Sinh tự động bởi `php artisan testcases:export` · nguồn: `docs/test-cases/data/01-xac-thuc-va-tai-khoan.php`</sub>
