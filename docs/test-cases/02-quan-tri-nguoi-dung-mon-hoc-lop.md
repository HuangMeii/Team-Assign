# Nhóm 02 — Quản trị người dùng, môn học & lớp học phần

> **Mã nhóm**: `TC-ADMIN` · **Chức năng**: FEATURE_STATUS #2 (người dùng: CRUD + import + khóa/mở), #3 (môn học + import Excel), #4 (lớp học phần), #5 (sinh viên trong lớp)
> **Số test case**: 26 — Pass: **17** · Fail: **0** · Chưa chạy tay: **9**
> **Môi trường**: MySQL team_assign_test; đăng nhập admin (admin@test.com / password); mọi route quản trị nằm dưới /admin (middleware auth + admin).
> ↻ File này **sinh tự động** từ `docs/test-cases/data/02-quan-tri-nguoi-dung-mon-hoc-lop.php` — sửa dữ liệu ở đó rồi chạy `php artisan testcases:export` (đừng sửa file .md này).

## 1. Mục tiêu & phạm vi

Kiểm thử toàn bộ nghiệp vụ quản trị của Admin: danh sách/tạo/sửa người dùng, import từ Excel, khóa–mở tài khoản (không cho tự khóa admin); CRUD môn học với mã tự sinh và số tín chỉ bắt buộc; CRUD lớp học phần (mã lớp tự sinh 5 ký tự), phân công giảng viên ở cấp lớp, thêm/xóa sinh viên trong lớp, lọc theo môn + trạng thái.

## 2. Bảng tóm tắt test case

| Mã TC | Tên / Mục tiêu | Role | Loại | Ưu tiên | Trạng thái |
|---|---|---|---|---|---|
| `TC-ADMIN-01` | Admin xem được danh sách người dùng kèm bộ lọc | Admin | UI | Cao | Pass |
| `TC-ADMIN-02` | Nút khóa tài khoản bị ẨN với tài khoản admin, vẫn hiện với sinh viên | Admin | UI | Cao | Pass |
| `TC-ADMIN-03` | Gọi trực tiếp route khóa tài khoản của admin vẫn bị chặn (case âm) | Admin | API | Cao | Pass |
| `TC-ADMIN-04` | Admin tạo người dùng mới và người đó đăng nhập được bằng mật khẩu khởi tạo | Admin | UI | Trung bình | Chưa chạy tay |
| `TC-ADMIN-05` | Không tạo được người dùng khi email trùng hoặc thiếu trường bắt buộc (case âm) | Admin | API | Trung bình | Chưa chạy tay |
| `TC-ADMIN-06` | Import danh sách người dùng từ file Excel/CSV của nhà trường | Admin | UI | Trung bình | Chưa chạy tay |
| `TC-ADMIN-07` | File import sai định dạng bị từ chối kèm hướng dẫn (case âm) | Admin | UI | Thấp | Chưa chạy tay |
| `TC-ADMIN-08` | Admin sửa thông tin người dùng và thay đổi vai trò | Admin | UI | Trung bình | Chưa chạy tay |
| `TC-ADMIN-09` | Khóa tài khoản sinh viên ⇒ sinh viên không đăng nhập được; mở lại ⇒ đăng nhập bình thường | Admin | API | Cao | Chưa chạy tay |
| `TC-ADMIN-10` | Trang chi tiết người dùng hiển thị đủ thông tin và lịch sử tham gia | Admin | UI | Thấp | Chưa chạy tay |
| `TC-ADMIN-11` | Admin thêm môn học mới (không còn phân công giảng viên ở cấp môn) | Admin | UI | Cao | Pass |
| `TC-ADMIN-12` | Mã môn học luôn tự sinh, bỏ qua mã do client gửi lên | Admin | API | Cao | Pass |
| `TC-ADMIN-13` | Thiếu số tín chỉ thì không thêm được môn học (case âm) | Admin | API | Trung bình | Pass |
| `TC-ADMIN-14` | Sửa môn học nhưng KHÔNG đổi được mã môn | Admin | API | Trung bình | Pass |
| `TC-ADMIN-15` | Trang quản lý môn học không còn cột giảng viên phụ trách; giảng viên thấy mọi môn khi tạo lớp | Giảng viên | UI | Trung bình | Pass |
| `TC-ADMIN-16` | Admin xem danh sách lớp học phần và lọc theo nhiều môn + nhiều trạng thái | Admin | UI | Cao | Pass |
| `TC-ADMIN-17` | Admin tạo lớp học phần: mã lớp tự sinh, gán được giảng viên phụ trách | Admin | API | Cao | Pass |
| `TC-ADMIN-18` | Admin xem chi tiết lớp kèm danh sách sinh viên và nhóm (gồm cả trưởng nhóm + thành viên) | Admin | UI | Cao | Pass |
| `TC-ADMIN-19` | Admin thêm sinh viên vào lớp (không thêm trùng) và xóa sinh viên khỏi lớp | Admin | API | Cao | Pass |
| `TC-ADMIN-20` | Xóa trưởng nhóm khỏi lớp: chuyển quyền trưởng nhóm cho thành viên còn lại; nhóm rỗng thì giải tán; lớp khác không bị ảnh hưởng | Admin | API | Cao | Pass |
| `TC-ADMIN-21` | Đổi giảng viên phụ trách lớp KHÔNG xóa sinh viên; bỏ phân công giảng viên cũng không mất sinh viên | Admin | API | Trung bình | Pass |
| `TC-ADMIN-22` | Giảng viên và sinh viên không truy cập được trang quản lý lớp của admin; giảng viên không xóa sinh viên khỏi lớp | Admin | API | Cao | Pass |
| `TC-ADMIN-23` | Admin import môn học từ Excel/CSV và tải được file template | Admin | UI | Trung bình | Chưa chạy tay |
| `TC-ADMIN-24` | Admin khóa/mở lớp học phần và xóa lớp học phần rỗng | Admin | API | Trung bình | Chưa chạy tay |
| `TC-ADMIN-25` | Môn học có 2 bài báo cáo (giữa kì + cuối kì) tạo được và lưu đúng | Admin | API | Cao | Pass |
| `TC-ADMIN-26` | Import môn học: cột so_bai_bao_cao=2 ⇒ 2 bài; thiếu cột/để trống ⇒ 1; KHÔNG hạ môn đang 2 bài | Admin | API | Cao | Pass |

## 3. Chi tiết test case

### TC-ADMIN-01 — Admin xem được danh sách người dùng kèm bộ lọc

- **Chức năng**: Quản lý người dùng (#2) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập admin; DB có sẵn tài khoản admin, giảng viên, sinh viên
- **Các bước thực hiện**:
  1. Mở /admin/users
  2. Quan sát bảng danh sách
  3. Thử lọc theo vai trò và từ khóa tên/email
- **Dữ liệu đầu vào**: URL /admin/users
- **Kết quả mong đợi**: Trang render 200; có cột tên/email/vai trò/trạng thái; lọc trả đúng tập kết quả
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ViewSmokeTest.php`

### TC-ADMIN-02 — Nút khóa tài khoản bị ẨN với tài khoản admin, vẫn hiện với sinh viên

- **Chức năng**: Khóa / mở tài khoản (#2) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập admin; danh sách có ít nhất 1 admin và 1 sinh viên
- **Các bước thực hiện**:
  1. Mở /admin/users
  2. Tìm dòng của tài khoản admin
  3. So sánh với dòng của tài khoản sinh viên
- **Dữ liệu đầu vào**: URL /admin/users
- **Kết quả mong đợi**: Dòng admin KHÔNG có nút khóa/mở; dòng sinh viên CÓ nút khóa/mở
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ViewSmokeTest.php`
- **Ghi chú**: Chống tự khóa chính mình ⇒ mất quyền quản trị hệ thống.

### TC-ADMIN-03 — Gọi trực tiếp route khóa tài khoản của admin vẫn bị chặn (case âm)

- **Chức năng**: Khóa / mở tài khoản (#2) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập admin; biết user_id của một tài khoản admin khác
- **Các bước thực hiện**:
  1. Gọi PATCH /admin/users/{id}/toggle-active với id của admin
  2. Kiểm tra DB
- **Dữ liệu đầu vào**: PATCH /admin/users/{id}/toggle-active (id là admin)
- **Kết quả mong đợi**: Bị chặn (chuyển hướng kèm lỗi) và `users.is_active` của admin KHÔNG đổi
- **Kiểm tra thêm (DB / log / API)**: users.is_active giữ nguyên giá trị cũ
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ViewSmokeTest.php`

### TC-ADMIN-04 — Admin tạo người dùng mới và người đó đăng nhập được bằng mật khẩu khởi tạo

- **Chức năng**: Quản lý người dùng (#2) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập admin; email mới chưa tồn tại
- **Các bước thực hiện**:
  1. Mở /admin/users/create
  2. Nhập tên, email, vai trò, mật khẩu
  3. Bấm lưu
  4. Đăng xuất và đăng nhập bằng tài khoản mới
- **Dữ liệu đầu vào**: name=Nguyễn Văn Mới, email=moi@test.com, role=student, password=password
- **Kết quả mong đợi**: Tạo thành công, quay về danh sách kèm thông báo; tài khoản mới đăng nhập được
- **Kiểm tra thêm (DB / log / API)**: users có 1 dòng mới với mật khẩu đã hash (không phải plaintext)
- **Kết quả thực tế**: Chưa chạy tay
- **Trạng thái**: **Chưa chạy tay**
- **Test tự động**: (thủ công — chạy trên trình duyệt)

### TC-ADMIN-05 — Không tạo được người dùng khi email trùng hoặc thiếu trường bắt buộc (case âm)

- **Chức năng**: Quản lý người dùng (#2) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập admin; email sv1@test.com đã tồn tại
- **Các bước thực hiện**:
  1. Mở /admin/users/create
  2. Nhập email sv1@test.com
  3. Bấm lưu
  4. Lặp lại với form để trống
- **Dữ liệu đầu vào**: email=sv1@test.com (trùng) · form rỗng
- **Kết quả mong đợi**: Hiện lỗi validate ở ô email (đã tồn tại) / các ô bắt buộc; không tạo dòng mới
- **Kiểm tra thêm (DB / log / API)**: users không tăng số dòng
- **Kết quả thực tế**: Chưa chạy tay
- **Trạng thái**: **Chưa chạy tay**
- **Test tự động**: (thủ công — chạy trên trình duyệt)

### TC-ADMIN-06 — Import danh sách người dùng từ file Excel/CSV của nhà trường

- **Chức năng**: Import người dùng (#2) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập admin; có file mẫu tải từ /admin/users-import
- **Các bước thực hiện**:
  1. Mở /admin/users-import
  2. Chọn file .xlsx/.csv có 3 dòng hợp lệ
  3. Bấm import
- **Dữ liệu đầu vào**: file: name, email, role (student/lecturer)
- **Kết quả mong đợi**: Báo số dòng import thành công/thất bại; tài khoản mới hiện ở /admin/users với mật khẩu mặc định
- **Kiểm tra thêm (DB / log / API)**: users tăng đúng số dòng hợp lệ; email trùng vẫn bị bỏ qua (không ghi đè)
- **Kết quả thực tế**: Chưa chạy tay
- **Trạng thái**: **Chưa chạy tay**
- **Test tự động**: (thủ công — chạy trên trình duyệt)

### TC-ADMIN-07 — File import sai định dạng bị từ chối kèm hướng dẫn (case âm)

- **Chức năng**: Import người dùng (#2) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Thấp
- **Tiền điều kiện**: Đăng nhập admin
- **Các bước thực hiện**:
  1. Mở /admin/users-import
  2. Chọn file .txt hoặc file thiếu cột email
  3. Bấm import
- **Dữ liệu đầu vào**: file sai định dạng / thiếu cột bắt buộc
- **Kết quả mong đợi**: Hiện lỗi validate rõ ràng; không thêm dòng nào vào `users`
- **Kết quả thực tế**: Chưa chạy tay
- **Trạng thái**: **Chưa chạy tay**
- **Test tự động**: (thủ công — chạy trên trình duyệt)

### TC-ADMIN-08 — Admin sửa thông tin người dùng và thay đổi vai trò

- **Chức năng**: Quản lý người dùng (#2) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập admin; có 1 tài khoản sinh viên cần đổi sang giảng viên
- **Các bước thực hiện**:
  1. Mở /admin/users/{id}/edit
  2. Đổi tên và vai trò
  3. Bấm lưu
  4. Đăng nhập lại bằng tài khoản đó
- **Dữ liệu đầu vào**: name=..., role=lecturer
- **Kết quả mong đợi**: Lưu thành công; sau khi đăng nhập, người dùng bị điều hướng theo vai trò mới (dashboard giảng viên)
- **Kiểm tra thêm (DB / log / API)**: users.name và users.role đã đổi
- **Kết quả thực tế**: Chưa chạy tay
- **Trạng thái**: **Chưa chạy tay**
- **Test tự động**: (thủ công — chạy trên trình duyệt)

### TC-ADMIN-09 — Khóa tài khoản sinh viên ⇒ sinh viên không đăng nhập được; mở lại ⇒ đăng nhập bình thường

- **Chức năng**: Khóa / mở tài khoản (#2) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập admin; có tài khoản sinh viên đang hoạt động
- **Các bước thực hiện**:
  1. Mở /admin/users
  2. Bấm nút khóa ở dòng sinh viên
  3. Đăng xuất, thử đăng nhập bằng tài khoản đó
  4. Mở lại khóa và thử đăng nhập lại
- **Dữ liệu đầu vào**: PATCH /admin/users/{id}/toggle-active (2 lần)
- **Kết quả mong đợi**: Khi khóa: đăng nhập bị từ chối kèm lỗi; khi mở: đăng nhập thành công
- **Kiểm tra thêm (DB / log / API)**: users.is_active = 0 → 1 tương ứng
- **Kết quả thực tế**: Chưa chạy tay
- **Trạng thái**: **Chưa chạy tay**
- **Test tự động**: (thủ công — chạy trên trình duyệt)

### TC-ADMIN-10 — Trang chi tiết người dùng hiển thị đủ thông tin và lịch sử tham gia

- **Chức năng**: Quản lý người dùng (#2) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Thấp
- **Tiền điều kiện**: Đăng nhập admin; chọn một sinh viên đã có lớp + nhóm
- **Các bước thực hiện**:
  1. Mở /admin/users/{id}
  2. Quan sát các khối thông tin
- **Dữ liệu đầu vào**: URL /admin/users/{id}
- **Kết quả mong đợi**: Trang render 200 với hồ sơ, vai trò, trạng thái và thông tin lớp/nhóm của người dùng
- **Kết quả thực tế**: Chưa chạy tay
- **Trạng thái**: **Chưa chạy tay**
- **Test tự động**: (thủ công — chạy trên trình duyệt)

### TC-ADMIN-11 — Admin thêm môn học mới (không còn phân công giảng viên ở cấp môn)

- **Chức năng**: Quản lý môn học (#3) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập admin
- **Các bước thực hiện**:
  1. Mở /admin/subjects/create
  2. Nhập tên môn + số tín chỉ
  3. Bấm lưu
  4. Mở /admin/subjects kiểm tra
- **Dữ liệu đầu vào**: subject_name=Lập trình web, credits=3
- **Kết quả mong đợi**: Tạo thành công; mã môn tự sinh; form KHÔNG còn ô chọn giảng viên phụ trách
- **Kiểm tra thêm (DB / log / API)**: subjects có dòng mới: subject_code tự sinh, credits = 3
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/SubjectTest.php`

### TC-ADMIN-12 — Mã môn học luôn tự sinh, bỏ qua mã do client gửi lên

- **Chức năng**: Quản lý môn học (#3) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập admin
- **Các bước thực hiện**:
  1. Mở form thêm môn học
  2. Xem nguồn HTML để xác nhận KHÔNG có ô nhập mã
  3. Gửi request kèm `subject_code=HACK01` bằng devtools/HTTP client
  4. Kiểm tra DB
- **Dữ liệu đầu vào**: POST /admin/subjects kèm subject_code=HACK01
- **Kết quả mong đợi**: Mã do client gửi bị bỏ qua; môn học được tạo với mã tự sinh
- **Kiểm tra thêm (DB / log / API)**: subjects.subject_code ≠ HACK01
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/SubjectTest.php`

### TC-ADMIN-13 — Thiếu số tín chỉ thì không thêm được môn học (case âm)

- **Chức năng**: Quản lý môn học (#3) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập admin
- **Các bước thực hiện**:
  1. Mở /admin/subjects/create
  2. Nhập tên môn, để trống số tín chỉ
  3. Bấm lưu
- **Dữ liệu đầu vào**: subject_name=..., credits=
- **Kết quả mong đợi**: Hiện lỗi validate ở ô số tín chỉ; không tạo dòng mới trong `subjects`
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/SubjectTest.php`

### TC-ADMIN-14 — Sửa môn học nhưng KHÔNG đổi được mã môn

- **Chức năng**: Quản lý môn học (#3) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập admin; có 1 môn học
- **Các bước thực hiện**:
  1. Mở /admin/subjects/{id}/edit
  2. Gửi request kèm `subject_code=XXXXX` và đổi tên
  3. Kiểm tra DB
- **Dữ liệu đầu vào**: PUT /admin/subjects/{id}
- **Kết quả mong đợi**: Tên môn đổi nhưng `subject_code` giữ nguyên giá trị cũ
- **Kiểm tra thêm (DB / log / API)**: subjects.subject_code không đổi
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/SubjectTest.php`

### TC-ADMIN-15 — Trang quản lý môn học không còn cột giảng viên phụ trách; giảng viên thấy mọi môn khi tạo lớp

- **Chức năng**: Quản lý môn học (#3) · **Role**: Giảng viên · **Loại**: UI · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập admin và giảng viên
- **Các bước thực hiện**:
  1. Admin mở /admin/subjects quan sát tiêu đề cột
  2. Giảng viên mở /lecturer/classes/create và xem danh sách môn
- **Dữ liệu đầu vào**: 2 URL trên
- **Kết quả mong đợi**: Bảng môn học không có cột “Giảng viên phụ trách”; dropdown của giảng viên liệt kê TẤT CẢ môn học (phân công ở cấp lớp)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/SubjectTest.php`

### TC-ADMIN-16 — Admin xem danh sách lớp học phần và lọc theo nhiều môn + nhiều trạng thái

- **Chức năng**: Lớp học phần (#4) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập admin; có lớp thuộc 2 môn khác nhau, gồm cả lớp đã khóa
- **Các bước thực hiện**:
  1. Mở /admin/classes
  2. Chọn nhiều môn học cùng lúc
  3. Bật lọc trạng thái “đang mở” + “đã khóa”
  4. Thử lại với tham số đơn kiểu link cũ (?subject_id=...)
- **Dữ liệu đầu vào**: GET /admin/classes?subjects[]=..&status[]=..
- **Kết quả mong đợi**: Danh sách lọc đúng theo nhiều môn + nhiều trạng thái; tham số đơn cũ vẫn hoạt động (tương thích bookmark)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/AdminClassManagementTest.php`

### TC-ADMIN-17 — Admin tạo lớp học phần: mã lớp tự sinh, gán được giảng viên phụ trách

- **Chức năng**: Lớp học phần (#4) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập admin; có môn học và giảng viên trong hệ thống
- **Các bước thực hiện**:
  1. Mở /admin/classes/create
  2. Chọn môn, nhập tên lớp, chọn giảng viên phụ trách
  3. Bấm tạo
- **Dữ liệu đầu vào**: class_name=CNTT01-K1, subject_id=..., lecturer_id=...
- **Kết quả mong đợi**: Tạo thành công; mã lớp tự sinh 5 ký tự; giảng viên được gắn làm người phụ trách lớp
- **Kiểm tra thêm (DB / log / API)**: class_sections có dòng mới · user_classes có dòng cho giảng viên
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/AdminClassManagementTest.php`

### TC-ADMIN-18 — Admin xem chi tiết lớp kèm danh sách sinh viên và nhóm (gồm cả trưởng nhóm + thành viên)

- **Chức năng**: Lớp học phần (#4) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Lớp có 3 sinh viên, trong đó 1 nhóm đã có trưởng nhóm và thành viên
- **Các bước thực hiện**:
  1. Mở /admin/classes/{id}
  2. Đối chiếu danh sách sinh viên
  3. Đối chiếu thông tin nhóm
- **Dữ liệu đầu vào**: URL /admin/classes/{id}
- **Kết quả mong đợi**: Trang 200 hiển thị đủ sinh viên và nhóm của cả trưởng nhóm lẫn thành viên
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/AdminClassManagementTest.php`

### TC-ADMIN-19 — Admin thêm sinh viên vào lớp (không thêm trùng) và xóa sinh viên khỏi lớp

- **Chức năng**: Sinh viên trong lớp (#5) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Lớp có sv1; sv3 chưa thuộc lớp
- **Các bước thực hiện**:
  1. Thêm sv3 vào lớp
  2. Thêm lại sv3 (case âm)
  3. Xóa sv1 khỏi lớp
  4. Thử xóa một sv không thuộc lớp (case âm)
- **Dữ liệu đầu vào**: POST /admin/classes/{id}/students · POST /admin/classes/{id}/students/{studentId}/remove
- **Kết quả mong đợi**: Thêm mới OK; thêm trùng bị chặn; xóa OK; xóa sinh viên không thuộc lớp chỉ hiện cảnh báo và KHÔNG đổi dữ liệu
- **Kiểm tra thêm (DB / log / API)**: user_classes đúng số dòng, không phát sinh dòng trùng
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/AdminClassManagementTest.php`

### TC-ADMIN-20 — Xóa trưởng nhóm khỏi lớp: chuyển quyền trưởng nhóm cho thành viên còn lại; nhóm rỗng thì giải tán; lớp khác không bị ảnh hưởng

- **Chức năng**: Sinh viên trong lớp (#5) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Nhóm Alpha (trưởng nhóm sv1 + thành viên sv3); sv1 đồng thời thuộc lớp khác
- **Các bước thực hiện**:
  1. Xóa sv1 khỏi lớp CNTT01-K1
  2. Kiểm tra nhóm Alpha
  3. Kiểm tra lớp còn lại của sv1
  4. Lặp lại với tình huống nhóm chỉ có 1 người
- **Dữ liệu đầu vào**: POST /admin/classes/{id}/students/{studentId}/remove
- **Kết quả mong đợi**: Nhóm còn thành viên ⇒ người còn lại thành trưởng nhóm; nhóm rỗng ⇒ bị giải tán; lớp khác của sv1 giữ nguyên
- **Kiểm tra thêm (DB / log / API)**: groups.leader_id đổi hoặc dòng nhóm bị xóa · user_classes của lớp khác không đổi
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/AdminClassManagementTest.php`

### TC-ADMIN-21 — Đổi giảng viên phụ trách lớp KHÔNG xóa sinh viên; bỏ phân công giảng viên cũng không mất sinh viên

- **Chức năng**: Phân công giảng viên (#4) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Lớp có 3 sinh viên và 1 giảng viên phụ trách
- **Các bước thực hiện**:
  1. Đổi sang giảng viên khác
  2. Kiểm tra danh sách sinh viên
  3. Bỏ phân công giảng viên (để trống)
  4. Kiểm tra lại
- **Dữ liệu đầu vào**: PUT /admin/classes/{id} {lecturer_id}
- **Kết quả mong đợi**: Danh sách sinh viên giữ nguyên ở cả 2 thao tác; chỉ pivot `user_classes` của giảng viên thay đổi
- **Kiểm tra thêm (DB / log / API)**: user_classes: giảng viên cũ bị gỡ, giảng viên mới được thêm; sinh viên không đổi
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/AdminClassManagementTest.php`

### TC-ADMIN-22 — Giảng viên và sinh viên không truy cập được trang quản lý lớp của admin; giảng viên không xóa sinh viên khỏi lớp

- **Chức năng**: Phân quyền lớp học phần (#4) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập lần lượt giảng viên phụ trách và sinh viên
- **Các bước thực hiện**:
  1. GV mở /admin/classes
  2. SV mở /admin/classes
  3. GV gọi POST xóa sinh viên khỏi lớp
  4. Admin mở URL cũ /classes
- **Dữ liệu đầu vào**: URL /admin/classes*
- **Kết quả mong đợi**: GV/SV bị chuyển hướng hoặc 403 (không lộ trang quản trị); GV không xóa được sinh viên; admin mở URL cũ /classes ⇒ tự chuyển hướng về /admin/classes
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/AdminClassManagementTest.php`

### TC-ADMIN-23 — Admin import môn học từ Excel/CSV và tải được file template

- **Chức năng**: Import môn học (#3) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập admin; có file mẫu tải từ /admin/subjects/template
- **Các bước thực hiện**:
  1. Mở /admin/subjects/import/form
  2. Tải file template
  3. Điền 3 dòng môn học rồi upload
- **Dữ liệu đầu vào**: file .xlsx/.csv: subject_name, credits (mã môn tự sinh)
- **Kết quả mong đợi**: Template tải được; import báo số dòng thành công; môn mới xuất hiện trong /admin/subjects với mã tự sinh
- **Kiểm tra thêm (DB / log / API)**: subjects tăng đúng số dòng hợp lệ
- **Kết quả thực tế**: Chưa chạy tay
- **Trạng thái**: **Chưa chạy tay**
- **Test tự động**: (thủ công — chạy trên trình duyệt)

### TC-ADMIN-24 — Admin khóa/mở lớp học phần và xóa lớp học phần rỗng

- **Chức năng**: Lớp học phần (#4) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập admin; có 1 lớp đang mở và 1 lớp trống chưa có sinh viên/nhóm
- **Các bước thực hiện**:
  1. Bấm khóa lớp đang mở rồi kiểm tra trạng thái
  2. Mở khóa lại
  3. Xóa lớp trống
  4. Thử xóa lớp đang có sinh viên (case âm)
- **Dữ liệu đầu vào**: PATCH /admin/classes/{id}/toggle-active · DELETE /admin/classes/{id}
- **Kết quả mong đợi**: Khóa/mở cập nhật đúng `is_active`; xóa lớp trống thành công; xóa lớp đang có dữ liệu bị chặn kèm cảnh báo
- **Kiểm tra thêm (DB / log / API)**: class_sections.is_active đổi tương ứng · lớp rỗng bị xóa khỏi DB
- **Kết quả thực tế**: Chưa chạy tay
- **Trạng thái**: **Chưa chạy tay**
- **Test tự động**: (thủ công — chạy trên trình duyệt)

### TC-ADMIN-25 — Môn học có 2 bài báo cáo (giữa kì + cuối kì) tạo được và lưu đúng

- **Chức năng**: Quản lý môn học (#3) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập admin
- **Các bước thực hiện**:
  1. Mở /admin/subjects/create
  2. Chọn 2 bài — Giữa kì + Cuối kì
  3. Bấm Lưu
- **Dữ liệu đầu vào**: subject_name=Môn 2 bài, credits=3, report_count=2
- **Kết quả mong đợi**: Tạo thành công; danh sách môn học hiển thị badge 2 bài (Giữa kì + Cuối kì) 
- **Kiểm tra thêm (DB / log / API)**: subjects.report_count = 2
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/SubjectTest.php`
- **Ghi chú**: Không chọn ⇒ mặc định 1 (chỉ cuối kì); giá trị khác 1/2 bị chặn (HTTP 422).

### TC-ADMIN-26 — Import môn học: cột so_bai_bao_cao=2 ⇒ 2 bài; thiếu cột/để trống ⇒ 1; KHÔNG hạ môn đang 2 bài

- **Chức năng**: Quản lý môn học (#3) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập admin; file CSV có cột ten_mon, so_tc, so_bai_bao_cao
- **Các bước thực hiện**:
  1. Mở /admin/subjects/import/form
  2. Chọn file CSV
  3. Bấm Import ngay
- **Dữ liệu đầu vào**: ten_mon,so_tc,so_bai_bao_cao (2 / trống / thiếu cột / 3)
- **Kết quả mong đợi**: Môn mới 2 ⇒ 2 · trống/thiếu cột (môn mới) ⇒ 1 · môn đã có 2 bài + ô trống ⇒ giữ 2 · giá trị 3 ⇒ dòng lỗi
- **Kiểm tra thêm (DB / log / API)**: subjects.report_count đúng theo từng dòng
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/SubjectTest.php`

## 4. Cách chạy nhóm test này

```powershell
cd G:\MyApp\laragon\www\Team-Assign
php artisan test tests/Feature/SubjectTest.php tests/Feature/AdminClassManagementTest.php tests/Feature/ViewSmokeTest.php tests/Feature/AdminSoftDeleteTest.php
```

## 5. Ghi chú & rủi ro

- Mã môn học (`subjects.subject_code`) và mã lớp (`class_sections.class_code`) do hệ thống TỰ SINH 5 ký tự — client gửi mã lên cũng bị bỏ qua (test khẳng định điều này).
- Phân công giảng viên nằm ở cấp LỚP HỌC PHẦN (pivot `user_classes`), không còn ở cấp môn học (migration 2026_09_16_000001 đã bỏ `subjects.lecturer_id`).
- Xóa sinh viên khỏi lớp phải DỌN dữ liệu nhóm: chuyển quyền trưởng nhóm cho thành viên còn lại, nhóm rỗng thì giải tán (kèm bài hệ thống trên bảng tin — xem nhóm 09).
- Route CRUD lớp học phần cũ (`/classes`, chỉ middleware `auth`) đã bị gỡ: URL cũ nay chỉ chuyển hướng admin về `/admin/classes`; nghiệp vụ thật nằm ở nhóm `admin/*`.
- Case có nhãn `Chưa chạy tay` cần tự chạy trên trình duyệt (2 tài khoản nếu cần realtime) rồi đổi trạng thái trong `data/02-quan-tri-nguoi-dung-mon-hoc-lop.php` và export lại.

<sub>Sinh tự động bởi `php artisan testcases:export` · nguồn: `docs/test-cases/data/02-quan-tri-nguoi-dung-mon-hoc-lop.php`</sub>
