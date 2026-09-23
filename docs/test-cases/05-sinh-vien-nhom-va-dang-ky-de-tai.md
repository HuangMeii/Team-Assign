# Nhóm 05 — Sinh viên: nhóm & đăng ký đề tài

> **Mã nhóm**: `TC-STU` · **Chức năng**: FEATURE_STATUS #6 (quản lý đề tài + import), #7 (đăng ký đề tài: gửi / duyệt / từ chối), #8 (nhóm: tạo, mời, yêu cầu tham gia, duyệt)
> **Số test case**: 50 — Pass: **50** · Fail: **0** · Chưa chạy tay: **0**
> **Môi trường**: MySQL team_assign_test; tài khoản sv1@test.com / sv2@test.com + gv1@test.com; mỗi sinh viên CHỈ thuộc 1 nhóm trong 1 lớp (nhưng có thể thuộc nhiều lớp).
> ↻ File này **sinh tự động** từ `docs/test-cases/data/05-sinh-vien-nhom-va-dang-ky-de-tai.php` — sửa dữ liệu ở đó rồi chạy `php artisan testcases:export` (đừng sửa file .md này).

## 1. Mục tiêu & phạm vi

Kiểm thử vòng đời nhóm của sinh viên (tạo nhóm, lời mời, yêu cầu tham gia, chấp nhận/từ chối, giải tán) và toàn bộ luồng đăng ký đề tài (trưởng nhóm gửi yêu cầu → giảng viên/admin duyệt hoặc từ chối, gán đề tài cho nhóm, tự từ chối các yêu cầu còn lại).

## 2. Bảng tóm tắt test case

| Mã TC | Tên / Mục tiêu | Role | Loại | Ưu tiên | Trạng thái |
|---|---|---|---|---|---|
| `TC-STU-01` | Sinh viên tạo nhóm thành công và trở thành trưởng nhóm | Sinh viên | UI | Cao | Pass |
| `TC-STU-02` | Sinh viên đã thuộc nhóm trong cùng lớp thì không tạo được nhóm mới (case âm) | Sinh viên | API | Cao | Pass |
| `TC-STU-03` | Không tạo được nhóm trong lớp đã bị khóa (case âm) | Sinh viên | API | Trung bình | Pass |
| `TC-STU-04` | Giảng viên tạo nhóm và chỉ định trưởng nhóm là sinh viên (phân quyền bổ sung) | Giảng viên | API | Trung bình | Pass |
| `TC-STU-05` | Không thể chỉ định giảng viên làm trưởng nhóm (case âm) | Giảng viên | API | Trung bình | Pass |
| `TC-STU-06` | Không thể xóa nhóm đã được gán đề tài (case âm) | Giảng viên | API | Cao | Pass |
| `TC-STU-07` | Đếm thành viên nhóm bao gồm cả trưởng nhóm | Sinh viên | DB | Trung bình | Pass |
| `TC-STU-08` | Cập nhật trạng thái nhóm: complete khi đạt tối thiểu, incomplete khi chưa đạt | Hệ thống | DB | Trung bình | Pass |
| `TC-STU-09` | Trưởng nhóm gửi lời mời thành công cho sinh viên cùng lớp | Sinh viên | API | Cao | Pass |
| `TC-STU-10` | Thành viên thường không được gửi lời mời (case âm) | Sinh viên | API | Cao | Pass |
| `TC-STU-11` | Không mời được khi nhóm đã đủ thành viên tối đa, và không mời vượt quá số chỗ còn thiếu | Sinh viên | API | Cao | Pass |
| `TC-STU-12` | Không mời sinh viên đã có nhóm ở CÙNG LỚP; vẫn mời được sinh viên có nhóm ở LỚP KHÁC | Sinh viên | API | Cao | Pass |
| `TC-STU-13` | Không gửi trùng lời mời đang chờ cho cùng một sinh viên (case âm) | Sinh viên | API | Trung bình | Pass |
| `TC-STU-14` | Chấp nhận lời mời thì gia nhập nhóm; nhóm đã đủ thì lời mời hết hiệu lực (Expired) | Sinh viên | UI | Cao | Pass |
| `TC-STU-15` | Sinh viên gửi yêu cầu tham gia nhóm và trưởng nhóm chấp nhận | Sinh viên | API | Cao | Pass |
| `TC-STU-16` | Quy tắc “1 nhóm / 1 lớp”: chặn yêu cầu vào nhóm thứ 2 cùng lớp, nhưng cho phép với lớp khác | Sinh viên | API | Cao | Pass |
| `TC-STU-17` | Yêu cầu/lời mời Pending tự chuyển Expired khi hết điều kiện | Sinh viên | DB | Cao | Pass |
| `TC-STU-18` | Trang Thông báo và trang Yêu cầu ẩn nút Chấp nhận/Từ chối khi yêu cầu hết hiệu lực | Sinh viên | UI | Cao | Pass |
| `TC-STU-19` | Trưởng nhóm đăng ký đề tài thành công khi nhóm đủ thành viên tối thiểu | Sinh viên | API | Cao | Pass |
| `TC-STU-20` | Các trường hợp không được đăng ký đề tài (case âm): thành viên thường, nhóm chưa đủ người, lớp khác, quá hạn, đã có đề tài, đề tài đã gán nhóm khác | Sinh viên | API | Cao | Pass |
| `TC-STU-21` | Gửi lại được sau khi bị từ chối; không tạo yêu cầu trùng khi đang có yêu cầu chờ | Sinh viên | API | Trung bình | Pass |
| `TC-STU-22` | Giảng viên duyệt yêu cầu: gán đề tài cho nhóm và TỰ ĐỘNG từ chối các yêu cầu khác của nhóm | Giảng viên | API | Cao | Pass |
| `TC-STU-23` | Giảng viên từ chối yêu cầu kèm lý do; sinh viên thấy lý do và gửi lại được | Giảng viên | API | Cao | Pass |
| `TC-STU-24` | Thành viên thường, giảng viên khác lớp và các yêu cầu đã xử lý KHÔNG duyệt/từ chối được (case âm) | Sinh viên | API | Cao | Pass |
| `TC-STU-25` | Trưởng nhóm hủy yêu cầu khi chưa được duyệt | Sinh viên | API | Trung bình | Pass |
| `TC-STU-26` | Giảng viên trực tiếp gán đề tài cho nhóm; không gán được đề tài đã thuộc nhóm khác | Giảng viên | API | Trung bình | Pass |
| `TC-STU-27` | Admin duyệt/từ chối được yêu cầu đăng ký đề tài | Admin | API | Trung bình | Pass |
| `TC-STU-28` | Sinh viên chỉ thấy đề tài của lớp học phần mình tham gia | Sinh viên | UI | Cao | Pass |
| `TC-STU-29` | Admin tạo đề tài cho lớp bất kỳ, ghi đúng giảng viên phụ trách lớp | Admin | UI | Cao | Pass |
| `TC-STU-30` | Giảng viên khác lớp không tạo được đề tài cho lớp mình không phụ trách (case âm) | Giảng viên | API | Cao | Pass |
| `TC-STU-31` | Sửa đề tài: không cho ĐỔI lớp học phần; không sửa được khi đã có sinh viên đăng ký | Admin | API | Trung bình | Pass |
| `TC-STU-32` | Vẫn chỉnh sửa được đề tài khi yêu cầu đăng ký duy nhất đã bị từ chối | Giảng viên | API | Trung bình | Pass |
| `TC-STU-33` | Import đề tài từ Excel/CSV: gán đúng lớp, môn học và giảng viên phụ trách | Admin | UI | Cao | Pass |
| `TC-STU-34` | Giảng viên chỉ import được đề tài cho lớp mình phụ trách (case âm) | Giảng viên | API | Cao | Pass |
| `TC-STU-35` | Dòng lỗi (mã lớp sai / mô tả < 10 ký tự) bị báo theo dòng và không tạo đề tài | Admin | API | Trung bình | Pass |
| `TC-STU-36` | Sinh viên đã có nhóm: yêu cầu Pending cùng lớp tự chuyển Expired và bị ẩn khỏi tab mặc định | Sinh viên | UI | Cao | Pass |
| `TC-STU-37` | Trang “Nhóm của tôi” không còn hiện “Đang chờ duyệt” khi sinh viên đã có nhóm trong lớp | Sinh viên | UI | Cao | Pass |
| `TC-STU-38` | Yêu cầu còn hiệu lực KHÔNG bị dọn oan | Sinh viên | DB | Trung bình | Pass |
| `TC-STU-39` | Nhóm đã đầy ⇒ yêu cầu Pending của nhóm đó chuyển Expired | Sinh viên | DB | Trung bình | Pass |
| `TC-STU-40` | Vào form tạo nhóm từ thẻ lớp: lớp học phần hiện dạng TEXT, KHÔNG bắt chọn lại ở combo box | Sinh viên | UI | Cao | Pass |
| `TC-STU-41` | Sinh viên đã có nhóm ở TẤT CẢ lớp học phần ⇒ hiện cảnh báo và khóa nút tạo nhóm (case âm) | Sinh viên | UI | Cao | Pass |
| `TC-STU-42` | Modal “Tìm nhóm” của mỗi lớp CHỈ liệt kê nhóm còn chỗ thuộc ĐÚNG lớp học phần đó | Sinh viên | UI | Cao | Pass |
| `TC-STU-43` | POST tạo nhóm với class_id của lớp mình KHÔNG tham gia bị chặn (bịt lỗ hổng sửa class_id) | Sinh viên / Bảo mật | API | Cao | Pass |
| `TC-STU-44` | File CSV phân cách TAB (sao chép từ Excel sang Notepad) hoặc dấu chấm phẩy vẫn import đúng | Giảng viên / Admin | API | Cao | Pass |
| `TC-STU-45` | Dòng trống ở cuối file KHÔNG bị tính là dòng lỗi | Giảng viên / Admin | API | Trung bình | Pass |
| `TC-STU-46` | File sai tên cột ở dòng tiêu đề nhận MỘT thông báo rõ ràng thay vì lỗi lặp cho từng dòng | Giảng viên / Admin | API | Cao | Pass |
| `TC-STU-47` | Form import ghi rõ hệ thống tự dò dấu phân cách (dấu phẩy / chấm phẩy / TAB) | Giảng viên / Admin | UI | Thấp | Pass |
| `TC-STU-48` | Môn có 2 bài báo cáo: tạo được đề tài GIỮA KÌ và CUỐI KÌ cho cùng lớp | Giảng viên / Admin | API | Cao | Pass |
| `TC-STU-49` | Nhóm của lớp đăng ký và được duyệt CẢ 2 đề tài (1 giữa kì + 1 cuối kì), không cần thứ tự | Sinh viên | API | Cao | Pass |
| `TC-STU-50` | Nhóm KHÔNG đăng ký được đề tài thứ 2 CÙNG LOẠI; duyệt giữa kì không làm rớt yêu cầu cuối kì | Sinh viên | API | Cao | Pass |

## 3. Chi tiết test case

### TC-STU-01 — Sinh viên tạo nhóm thành công và trở thành trưởng nhóm

- **Chức năng**: Tạo nhóm (#8) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập sv1@test.com; đã tham gia lớp CNTT01-K1; chưa có nhóm trong lớp đó
- **Các bước thực hiện**:
  1. Mở /user/groups/create
  2. Chọn lớp học phần, nhập tên nhóm
  3. Bấm tạo nhóm
- **Dữ liệu đầu vào**: group_name=Nhóm Alpha, class_id=CNTT01-K1
- **Kết quả mong đợi**: Tạo thành công; chuyển tới chi tiết nhóm; vai trò hiển thị là Trưởng nhóm; trạng thái nhóm = incomplete
- **Kiểm tra thêm (DB / log / API)**: groups.leader_id = sv1 · group_members có dòng (sv1, role=leader) · groups.status = incomplete
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/GroupServiceTest.php`

### TC-STU-02 — Sinh viên đã thuộc nhóm trong cùng lớp thì không tạo được nhóm mới (case âm)

- **Chức năng**: Tạo nhóm (#8) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv1 đã là trưởng nhóm của “Nhóm Alpha” trong lớp CNTT01-K1
- **Các bước thực hiện**:
  1. Mở /user/groups/create
  2. Chọn lại lớp CNTT01-K1
  3. Bấm tạo nhóm
- **Dữ liệu đầu vào**: group_name=Nhóm Alpha 2, class_id=CNTT01-K1
- **Kết quả mong đợi**: Bị từ chối kèm thông báo đã có nhóm trong lớp; không sinh dòng `groups` mới
- **Kiểm tra thêm (DB / log / API)**: Số nhóm của sv1 trong lớp CNTT01-K1 vẫn = 1
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/GroupServiceTest.php`

### TC-STU-03 — Không tạo được nhóm trong lớp đã bị khóa (case âm)

- **Chức năng**: Tạo nhóm (#8) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Lớp CNTT02-K1 có is_active = 0 (admin đã khóa)
- **Các bước thực hiện**:
  1. Mở /user/groups/create
  2. Chọn lớp đã bị khóa
  3. Bấm tạo nhóm
- **Dữ liệu đầu vào**: class_id=lớp is_active = 0
- **Kết quả mong đợi**: Bị chặn kèm thông báo lớp đã đóng/khóa; không tạo nhóm
- **Kiểm tra thêm (DB / log / API)**: groups không tăng số dòng
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/GroupServiceTest.php`

### TC-STU-04 — Giảng viên tạo nhóm và chỉ định trưởng nhóm là sinh viên (phân quyền bổ sung)

- **Chức năng**: Tạo nhóm hộ sinh viên (#8) · **Role**: Giảng viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập giảng viên phụ trách lớp; sv3 chưa có nhóm trong lớp
- **Các bước thực hiện**:
  1. Gọi luồng tạo nhóm với `leader_id` = sv3
  2. Kiểm tra nhóm và vai trò của sv3
- **Dữ liệu đầu vào**: group_name=Nhóm Beta, class_id=CNTT01-K1, leader_id=sv3
- **Kết quả mong đợi**: Nhóm được tạo; sv3 là trưởng nhóm dù người tạo là giảng viên
- **Kiểm tra thêm (DB / log / API)**: groups.leader_id = sv3 · group_members(sv3, leader)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/GroupServiceTest.php`

### TC-STU-05 — Không thể chỉ định giảng viên làm trưởng nhóm (case âm)

- **Chức năng**: Tạo nhóm hộ sinh viên (#8) · **Role**: Giảng viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập giảng viên; có user_id của một giảng viên khác
- **Các bước thực hiện**:
  1. Gọi luồng tạo nhóm với `leader_id` là giảng viên
  2. Kiểm tra kết quả
- **Dữ liệu đầu vào**: leader_id={giảng viên}
- **Kết quả mong đợi**: Bị từ chối kèm thông báo trưởng nhóm phải là sinh viên; không tạo nhóm
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/GroupServiceTest.php`

### TC-STU-06 — Không thể xóa nhóm đã được gán đề tài (case âm)

- **Chức năng**: Giải tán nhóm (#8) · **Role**: Giảng viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Nhóm Alpha đã có `groups.topic_id` sau khi yêu cầu đăng ký được duyệt
- **Các bước thực hiện**:
  1. Thử xóa nhóm Alpha
  2. Kiểm tra kết quả
- **Dữ liệu đầu vào**: DELETE nhóm đã gán đề tài
- **Kết quả mong đợi**: Bị chặn kèm thông báo nhóm đang có đề tài; nhóm vẫn còn trong DB
- **Kiểm tra thêm (DB / log / API)**: groups vẫn còn dòng của nhóm Alpha
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/GroupServiceTest.php`

### TC-STU-07 — Đếm thành viên nhóm bao gồm cả trưởng nhóm

- **Chức năng**: Đếm thành viên nhóm (#8) · **Role**: Sinh viên · **Loại**: DB · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Nhóm có 1 trưởng nhóm + 1 thành viên thường
- **Các bước thực hiện**:
  1. Đọc số thành viên hiển thị ở chi tiết nhóm
  2. So với bảng group_members
- **Dữ liệu đầu vào**: URL /user/groups/{id}
- **Kết quả mong đợi**: Số thành viên = 2 (đã gồm trưởng nhóm); hiển thị dạng “2/4” theo max_members của đề tài
- **Kiểm tra thêm (DB / log / API)**: COUNT(group_members) = 2
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/GroupServiceTest.php`

### TC-STU-08 — Cập nhật trạng thái nhóm: complete khi đạt tối thiểu, incomplete khi chưa đạt

- **Chức năng**: Trạng thái nhóm (#8) · **Role**: Hệ thống · **Loại**: DB · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Nhóm có min_members = 2 nhưng mới 1 thành viên
- **Các bước thực hiện**:
  1. Kiểm tra groups.status = incomplete
  2. Thêm 1 thành viên để đủ tối thiểu
  3. Kiểm tra lại trạng thái
- **Dữ liệu đầu vào**: Thêm thành viên vào nhóm
- **Kết quả mong đợi**: Chưa đủ ⇒ incomplete · đủ tối thiểu ⇒ complete
- **Kiểm tra thêm (DB / log / API)**: groups.status = incomplete → complete
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/GroupServiceTest.php`

### TC-STU-09 — Trưởng nhóm gửi lời mời thành công cho sinh viên cùng lớp

- **Chức năng**: Gửi lời mời (#8) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv1 là trưởng nhóm “Nhóm Alpha” (lớp CNTT01-K1); sv3 chưa có nhóm trong lớp
- **Các bước thực hiện**:
  1. Mở /user/groups/{groupId}/invite
  2. Nhập email/mã của sv3
  3. Bấm gửi lời mời
  4. sv3 mở /user/invites
- **Dữ liệu đầu vào**: POST /user/invites/send {email=sv3@test.com}
- **Kết quả mong đợi**: Gửi thành công; sv3 thấy lời mời ở mục “Lời mời” và badge tăng
- **Kiểm tra thêm (DB / log / API)**: invites có 1 dòng status = Pending; sinh viên chưa vào group_members (chờ chấp nhận)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/InvitationServiceTest.php`

### TC-STU-10 — Thành viên thường không được gửi lời mời (case âm)

- **Chức năng**: Gửi lời mời (#8) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv3 là thành viên thường (không phải trưởng nhóm) của nhóm Alpha
- **Các bước thực hiện**:
  1. Đăng nhập sv3
  2. Gọi POST /user/invites/send cho một sinh viên khác
- **Dữ liệu đầu vào**: POST /user/invites/send
- **Kết quả mong đợi**: Bị từ chối kèm thông báo chỉ trưởng nhóm mới mời được; không sinh dòng `invites`
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/InvitationServiceTest.php`

### TC-STU-11 — Không mời được khi nhóm đã đủ thành viên tối đa, và không mời vượt quá số chỗ còn thiếu

- **Chức năng**: Giới hạn số thành viên (#8) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đề tài của nhóm có max_members = 4; nhóm đang có 3 thành viên và 1 lời mời Pending
- **Các bước thực hiện**:
  1. Gửi thêm 1 lời mời nữa (đã đủ chỗ)
  2. Chấp nhận lời mời cho đủ 4 thành viên
  3. Thử mời thêm
- **Dữ liệu đầu vào**: POST /user/invites/send (nhiều lần)
- **Kết quả mong đợi**: Khi hết chỗ: bị chặn kèm thông báo nhóm đã đủ thành viên; số chỗ tính CẢ lời mời đang chờ
- **Kiểm tra thêm (DB / log / API)**: Số dòng `invites` Pending không vượt quá (max_members − số thành viên hiện tại)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/InvitationServiceTest.php`

### TC-STU-12 — Không mời sinh viên đã có nhóm ở CÙNG LỚP; vẫn mời được sinh viên có nhóm ở LỚP KHÁC

- **Chức năng**: Điều kiện nhận lời mời (#8) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv4 đã có nhóm trong lớp CNTT01-K1; sv5 có nhóm ở lớp khác nhưng chưa có nhóm trong lớp này
- **Các bước thực hiện**:
  1. Thử mời sv4
  2. Thử mời sv5
- **Dữ liệu đầu vào**: POST /user/invites/send
- **Kết quả mong đợi**: sv4 bị từ chối (đã có nhóm cùng lớp); sv5 nhận được lời mời bình thường
- **Kiểm tra thêm (DB / log / API)**: invites chỉ có dòng cho sv5
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/InvitationServiceTest.php`

### TC-STU-13 — Không gửi trùng lời mời đang chờ cho cùng một sinh viên (case âm)

- **Chức năng**: Chống trùng lời mời (#8) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đã có lời mời Pending từ nhóm Alpha cho sv3
- **Các bước thực hiện**:
  1. Gửi lại lời mời cho sv3
- **Dữ liệu đầu vào**: POST /user/invites/send (lần 2)
- **Kết quả mong đợi**: Bị từ chối kèm thông báo đã có lời mời đang chờ; không sinh dòng mới
- **Kiểm tra thêm (DB / log / API)**: Số dòng invites Pending cho (nhóm, sv3) = 1
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/InvitationServiceTest.php`

### TC-STU-14 — Chấp nhận lời mời thì gia nhập nhóm; nhóm đã đủ thì lời mời hết hiệu lực (Expired)

- **Chức năng**: Chấp nhận / từ chối lời mời (#8) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv3 có lời mời Pending từ nhóm Alpha (min_members = 2)
- **Các bước thực hiện**:
  1. sv3 mở /user/invites
  2. Bấm chấp nhận
  3. Kiểm tra thành viên nhóm
  4. Tạo tình huống nhóm đủ người rồi chấp nhận lời mời thứ 2
- **Dữ liệu đầu vào**: POST /user/invites/{id}/accept
- **Kết quả mong đợi**: Chấp nhận ⇒ sv3 vào nhóm + nhóm chuyển `complete` nếu đủ tối thiểu; khi nhóm đã đủ ⇒ lời mời chuyển `Expired` và nút bị ẩn
- **Kiểm tra thêm (DB / log / API)**: invites.status = Accepted (hoặc Expired) · group_members có thêm sv3
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/InvitationServiceTest.php`

### TC-STU-15 — Sinh viên gửi yêu cầu tham gia nhóm và trưởng nhóm chấp nhận

- **Chức năng**: Yêu cầu tham gia nhóm (#8) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv3 chưa có nhóm trong lớp; nhóm Alpha còn chỗ (min 2 / max 4)
- **Các bước thực hiện**:
  1. sv3 mở /user/available-groups
  2. Bấm “Xin vào nhóm” ở nhóm Alpha
  3. Trưởng nhóm mở /user/groups/{id}/join-requests
  4. Bấm chấp nhận
- **Dữ liệu đầu vào**: POST /user/join_requests/send · POST /user/join-requests/{id}/approve
- **Kết quả mong đợi**: Yêu cầu Pending hiện cho trưởng nhóm; chấp nhận ⇒ sv3 vào nhóm, nhóm đạt `complete` nếu đủ tối thiểu
- **Kiểm tra thêm (DB / log / API)**: join_requests.status = Accepted · group_members có thêm sv3 · groups.status cập nhật
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/InvitationServiceTest.php`

### TC-STU-16 — Quy tắc “1 nhóm / 1 lớp”: chặn yêu cầu vào nhóm thứ 2 cùng lớp, nhưng cho phép với lớp khác

- **Chức năng**: Điều kiện gửi yêu cầu (#8) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv3 đã có nhóm trong lớp CNTT01-K1; sv4 có nhóm ở lớp khác nhưng chưa có nhóm trong lớp này
- **Các bước thực hiện**:
  1. sv3 gửi yêu cầu vào nhóm khác CÙNG lớp
  2. sv4 gửi yêu cầu vào nhóm trong lớp này
  3. Thử chấp nhận lời mời mời sv3 vào nhóm thứ 2 cùng lớp
- **Dữ liệu đầu vào**: POST /user/join_requests/send
- **Kết quả mong đợi**: sv3 bị chặn (cùng lớp) và lời mời cho sv3 chuyển `Expired`; sv4 gửi được yêu cầu bình thường
- **Kiểm tra thêm (DB / log / API)**: join_requests chỉ sinh cho sv4; invites của sv3 = Expired
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/InvitationServiceTest.php`

### TC-STU-17 — Yêu cầu/lời mời Pending tự chuyển Expired khi hết điều kiện

- **Chức năng**: Yêu cầu hết hiệu lực (#8) · **Role**: Sinh viên · **Loại**: DB · **Ưu tiên**: Cao
- **Tiền điều kiện**: Nhóm có nhiều yêu cầu Pending và max_members = 2
- **Các bước thực hiện**:
  1. Chấp nhận 1 yêu cầu cho nhóm đủ thành viên
  2. Kiểm tra các yêu cầu Pending còn lại
  3. Trường hợp sinh viên đã vào nhóm khác ⇒ kiểm tra yêu cầu cũ của sinh viên đó
- **Dữ liệu đầu vào**: Duyệt yêu cầu/Chấp nhận lời mời
- **Kết quả mong đợi**: Các yêu cầu còn lại chuyển `Expired`; UI ẩn nút Chấp nhận/Từ chối; badge realtime giảm nhờ event `JoinRequestResolved`
- **Kiểm tra thêm (DB / log / API)**: join_requests/invites.status = Expired
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/InvitationServiceTest.php + tests/Feature/JoinRequestExpiryTest.php`
- **Ghi chú**: Ngoài các luồng đã tự hủy (tạo nhóm / chấp nhận lời mời / duyệt yêu cầu), khi mở trang “Nhóm của tôi” hoặc “Yêu cầu tham gia nhóm” còn chạy `expireStalePendingRequestsFor()` để dọn dữ liệu cũ (idempotent).

### TC-STU-18 — Trang Thông báo và trang Yêu cầu ẩn nút Chấp nhận/Từ chối khi yêu cầu hết hiệu lực

- **Chức năng**: Trang Yêu cầu (#12) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Có 1 yêu cầu còn hiệu lực và 1 yêu cầu hết hiệu lực (sinh viên đã có nhóm khác)
- **Các bước thực hiện**:
  1. Mở /user/join_requests (tab “Yêu cầu nhận được”)
  2. Mở /notifications
  3. So sánh 2 yêu cầu
- **Dữ liệu đầu vào**: URL /user/join_requests · /notifications
- **Kết quả mong đợi**: Yêu cầu còn hiệu lực có nút xử lý; yêu cầu hết hiệu lực bị ẩn nút ở CẢ 2 trang và ghi rõ lý do
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/JoinRequestNotificationTest.php`

### TC-STU-19 — Trưởng nhóm đăng ký đề tài thành công khi nhóm đủ thành viên tối thiểu

- **Chức năng**: Đăng ký đề tài (#7) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Nhóm Alpha (2 thành viên, min_members = 2); đề tài còn trống và chưa quá hạn
- **Các bước thực hiện**:
  1. sv1 mở /user/topics
  2. Chọn đề tài và bấm “Đăng ký cho nhóm”
  3. Kiểm tra trang Đề tài của tôi
- **Dữ liệu đầu vào**: POST /user/topics/register {topic_id}
- **Kết quả mong đợi**: Tạo yêu cầu trạng thái Pending; trang “Đề tài của tôi” hiển thị yêu cầu đang chờ
- **Kiểm tra thêm (DB / log / API)**: topic_requests: 1 dòng status = Pending (unique theo topic_id + group_id)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/TopicRegistrationServiceTest.php`

### TC-STU-20 — Các trường hợp không được đăng ký đề tài (case âm): thành viên thường, nhóm chưa đủ người, lớp khác, quá hạn, đã có đề tài, đề tài đã gán nhóm khác

- **Chức năng**: Điều kiện đăng ký (#7) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Chuẩn bị dữ liệu cho từng tình huống trong danh sách bước
- **Các bước thực hiện**:
  1. sv2 (thành viên thường) thử đăng ký
  2. Nhóm chỉ có 1 thành viên thử đăng ký
  3. Đăng ký đề tài của lớp khác
  4. Đăng ký đề tài đã quá `registration_deadline`
  5. Nhóm đã có đề tài được duyệt thử đăng ký thêm
  6. Đăng ký đề tài đã gán cho nhóm khác
- **Dữ liệu đầu vào**: POST /user/topics/register (6 tình huống)
- **Kết quả mong đợi**: Mọi tình huống bị từ chối kèm thông báo tương ứng; không sinh dòng `topic_requests` nào
- **Kiểm tra thêm (DB / log / API)**: topic_requests không tăng
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/TopicRegistrationServiceTest.php`

### TC-STU-21 — Gửi lại được sau khi bị từ chối; không tạo yêu cầu trùng khi đang có yêu cầu chờ

- **Chức năng**: Đăng ký đề tài (#7) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Nhóm có 1 yêu cầu đã bị từ chối cho đề tài A và 1 yêu cầu Pending cho đề tài B
- **Các bước thực hiện**:
  1. Gửi lại yêu cầu cho đề tài A
  2. Gửi lại yêu cầu cho đề tài B (đang Pending)
  3. Hủy yêu cầu B rồi gửi lại
- **Dữ liệu đầu vào**: POST /user/topics/register · DELETE /user/topics/cancel/{requestId}
- **Kết quả mong đợi**: Đề tài A: gửi lại thành công; đề tài B: bị chặn khi còn Pending; sau khi hủy thì gửi lại được
- **Kiểm tra thêm (DB / log / API)**: topic_requests: không có 2 dòng Pending cho cùng (topic, group)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/TopicRegistrationServiceTest.php`

### TC-STU-22 — Giảng viên duyệt yêu cầu: gán đề tài cho nhóm và TỰ ĐỘNG từ chối các yêu cầu khác của nhóm

- **Chức năng**: Duyệt đề tài (#7) · **Role**: Giảng viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Nhóm Alpha có 2 yêu cầu Pending (đề tài A, B); giảng viên phụ trách lớp
- **Các bước thực hiện**:
  1. Mở /topic-requests
  2. Duyệt yêu cầu cho đề tài A
  3. Kiểm tra nhóm và các yêu cầu còn lại
- **Dữ liệu đầu vào**: PATCH /topic-requests/{id}/approve
- **Kết quả mong đợi**: Nhóm được gán đề tài A (`groups.topic_id` + `topics.assigned_group_id`); yêu cầu B tự chuyển Rejected; bảng tin lớp có bài `group_topic`
- **Kiểm tra thêm (DB / log / API)**: topic_requests: A = Accepted, B = Rejected · groups.topic_id = A
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/TopicRegistrationServiceTest.php`

### TC-STU-23 — Giảng viên từ chối yêu cầu kèm lý do; sinh viên thấy lý do và gửi lại được

- **Chức năng**: Từ chối đề tài (#7) · **Role**: Giảng viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Nhóm Alpha có 1 yêu cầu Pending
- **Các bước thực hiện**:
  1. Mở /topic-requests
  2. Bấm từ chối và nhập lý do
  3. Kiểm tra màn hình sinh viên
- **Dữ liệu đầu vào**: PATCH /topic-requests/{id}/reject {rejection_reason=Đề tài chưa phù hợp}
- **Kết quả mong đợi**: Yêu cầu chuyển Rejected; sinh viên thấy lý do từ chối và có thể đăng ký lại đề tài khác
- **Kiểm tra thêm (DB / log / API)**: topic_requests.status = Rejected + rejection_reason có nội dung
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/TopicRegistrationServiceTest.php`

### TC-STU-24 — Thành viên thường, giảng viên khác lớp và các yêu cầu đã xử lý KHÔNG duyệt/từ chối được (case âm)

- **Chức năng**: Phân quyền duyệt (#7) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Có yêu cầu Pending; chuẩn bị tài khoản sv3 (thành viên), gv2 (khác lớp) và 1 yêu cầu đã Rejected
- **Các bước thực hiện**:
  1. sv3 gọi approve/reject
  2. gv2 gọi approve/reject
  3. Duyệt lại yêu cầu đã Rejected
- **Dữ liệu đầu vào**: PATCH /topic-requests/{id}/approve|reject
- **Kết quả mong đợi**: Cả 3 tình huống bị chặn kèm thông báo không có quyền/đã xử lý; trạng thái yêu cầu không đổi
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/TopicRegistrationServiceTest.php`

### TC-STU-25 — Trưởng nhóm hủy yêu cầu khi chưa được duyệt

- **Chức năng**: Hủy yêu cầu đăng ký (#7) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Nhóm Alpha có 1 yêu cầu Pending do chính sv1 tạo
- **Các bước thực hiện**:
  1. sv1 mở /user/my-topics
  2. Bấm hủy yêu cầu đang chờ
  3. Kiểm tra danh sách và trang giảng viên
- **Dữ liệu đầu vào**: DELETE /user/topics/cancel/{requestId}
- **Kết quả mong đợi**: Yêu cầu biến mất khỏi danh sách chờ của cả sinh viên và giảng viên; có thể đăng ký lại
- **Kiểm tra thêm (DB / log / API)**: Dòng `topic_requests` bị xóa (hoặc chuyển trạng thái hủy theo thiết kế)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/TopicRegistrationServiceTest.php`

### TC-STU-26 — Giảng viên trực tiếp gán đề tài cho nhóm; không gán được đề tài đã thuộc nhóm khác

- **Chức năng**: Duyệt đề tài (#7) · **Role**: Giảng viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập giảng viên phụ trách; nhóm Beta chưa có đề tài; đề tài A đã gán cho nhóm Alpha
- **Các bước thực hiện**:
  1. Gán đề tài trống cho nhóm Beta
  2. Thử gán đề tài A (đã thuộc nhóm khác)
- **Dữ liệu đầu vào**: Thao tác gán đề tài cho nhóm
- **Kết quả mong đợi**: Gán thành công cho đề tài trống; đề tài đã thuộc nhóm khác bị từ chối kèm thông báo
- **Kiểm tra thêm (DB / log / API)**: groups.topic_id của Beta = đề tài trống; đề tài A không đổi chủ
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/TopicRegistrationServiceTest.php`

### TC-STU-27 — Admin duyệt/từ chối được yêu cầu đăng ký đề tài

- **Chức năng**: Duyệt đề tài (#7) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập admin; có 2 yêu cầu Pending ở 2 lớp khác nhau
- **Các bước thực hiện**:
  1. Mở /topic-requests
  2. Duyệt 1 yêu cầu
  3. Từ chối 1 yêu cầu kèm lý do
- **Dữ liệu đầu vào**: PATCH /topic-requests/{id}/approve|reject
- **Kết quả mong đợi**: Duyệt ⇒ gán đề tài + bài `group_topic`; từ chối ⇒ kèm lý do cho sinh viên
- **Kiểm tra thêm (DB / log / API)**: topic_requests.status và rejection_reason đúng
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/Services/TopicRegistrationServiceTest.php`

### TC-STU-28 — Sinh viên chỉ thấy đề tài của lớp học phần mình tham gia

- **Chức năng**: Danh sách đề tài (#6) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv1 thuộc CNTT01-K1; môn CNTT có 2 lớp (CNTT01-K1 và CNTT02-K1) cùng đề tài khác nhau
- **Các bước thực hiện**:
  1. sv1 mở /user/topics
  2. Kiểm tra danh sách
  3. So sánh với đề tài của lớp CNTT02-K1
- **Dữ liệu đầu vào**: URL /user/topics
- **Kết quả mong đợi**: Chỉ thấy đề tài của CNTT01-K1; KHÔNG thấy đề tài riêng của CNTT02-K1 (cùng môn nhưng khác lớp)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/UserDashboardTopicsTest.php`

### TC-STU-29 — Admin tạo đề tài cho lớp bất kỳ, ghi đúng giảng viên phụ trách lớp

- **Chức năng**: Quản lý đề tài (#6) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập admin; có 2 lớp học phần ở 2 môn khác nhau
- **Các bước thực hiện**:
  1. Mở /topics/create
  2. Chọn lớp học phần, nhập tên/mô tả/min-max thành viên/hạn đăng ký
  3. Bấm lưu
  4. Kiểm tra tên giảng viên trên đề tài
- **Dữ liệu đầu vào**: name=..., class_id=..., min_members=2, max_members=4
- **Kết quả mong đợi**: Tạo thành công; form cho chọn MỌI lớp; cột giảng viên của đề tài = giảng viên phụ trách lớp đã chọn
- **Kiểm tra thêm (DB / log / API)**: topics có dòng mới (class_id, subject_id, lecturer khớp lớp)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicControllerTest.php`

### TC-STU-30 — Giảng viên khác lớp không tạo được đề tài cho lớp mình không phụ trách (case âm)

- **Chức năng**: Quản lý đề tài (#6) · **Role**: Giảng viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: gv1 phụ trách lớp A; gv2 phụ trách lớp B
- **Các bước thực hiện**:
  1. Đăng nhập gv2
  2. Mở /topics/create và thử chọn lớp A
  3. Gửi POST tạo đề tài với class_id = lớp A
- **Dữ liệu đầu vào**: POST /topics {class_id=lớp A}
- **Kết quả mong đợi**: Bị từ chối kèm thông báo không phụ trách lớp; không sinh dòng `topics`
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicControllerTest.php`

### TC-STU-31 — Sửa đề tài: không cho ĐỔI lớp học phần; không sửa được khi đã có sinh viên đăng ký

- **Chức năng**: Quản lý đề tài (#6) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đề tài X chưa ai đăng ký; đề tài Y đã có yêu cầu đăng ký Pending/Accepted
- **Các bước thực hiện**:
  1. Mở /topics/{X}/edit, thử đổi sang lớp khác
  2. Lưu và kiểm tra DB
  3. Mở /topics/{Y}/edit
  4. Thử cập nhật đề tài Y
- **Dữ liệu đầu vào**: PUT /topics/{id}
- **Kết quả mong đợi**: Đề tài X: lớp học phần giữ nguyên dù client gửi giá trị khác; đề tài Y: không vào được trang sửa / không cập nhật được (kèm thông báo)
- **Kiểm tra thêm (DB / log / API)**: topics.class_id của X không đổi · topics của Y không đổi
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicControllerTest.php`
- **Ghi chú**: Trường hợp duy nhất được sửa lại: yêu cầu đăng ký duy nhất đã bị TỪ CHỐI (xem case kế tiếp).

### TC-STU-32 — Vẫn chỉnh sửa được đề tài khi yêu cầu đăng ký duy nhất đã bị từ chối

- **Chức năng**: Quản lý đề tài (#6) · **Role**: Giảng viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đề tài Z có đúng 1 yêu cầu đăng ký với status = Rejected
- **Các bước thực hiện**:
  1. Mở /topics/{Z}/edit
  2. Đổi mô tả và hạn đăng ký
  3. Bấm lưu
- **Dữ liệu đầu vào**: PUT /topics/{Z}
- **Kết quả mong đợi**: Cập nhật thành công (nhóm đăng ký cũ đã bị từ chối nên đề tài trở lại trạng thái còn trống)
- **Kiểm tra thêm (DB / log / API)**: topics của Z đã đổi theo dữ liệu mới
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicControllerTest.php`

### TC-STU-33 — Import đề tài từ Excel/CSV: gán đúng lớp, môn học và giảng viên phụ trách

- **Chức năng**: Import đề tài (#6) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập admin (hoặc giảng viên phụ trách lớp); có file mẫu tải từ /topics/template
- **Các bước thực hiện**:
  1. Mở /topics/import/form
  2. Tải file mẫu để biết cột cần điền
  3. Điền 2 dòng đề tài rồi upload
  4. Kiểm tra danh sách đề tài
- **Dữ liệu đầu vào**: file CSV: ten_de_tai, mo_ta, muc_tieu, yeu_cau, ma_lop, so_tv_min, so_tv_max, han_dang_ky
- **Kết quả mong đợi**: Báo “thêm mới N, bỏ qua M”; đề tài mới xuất hiện với đúng lớp/môn/giảng viên; min-max rỗng ⇒ 2/4; hạn rỗng ⇒ +30 ngày
- **Kiểm tra thêm (DB / log / API)**: topics: class_id + subject_id suy từ `ma_lop`, lecturer = giảng viên phụ trách lớp, is_active = 1
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicImportTest.php`
- **Ghi chú**: Tên đề tài đã tồn tại bị BỎ QUA (không ghi đè) — đã có test riêng.

### TC-STU-34 — Giảng viên chỉ import được đề tài cho lớp mình phụ trách (case âm)

- **Chức năng**: Import đề tài (#6) · **Role**: Giảng viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: gv1 phụ trách lớp A; file có dòng dùng mã lớp B (của giảng viên khác)
- **Các bước thực hiện**:
  1. Đăng nhập gv1
  2. Upload file có mã lớp B
  3. Đọc thông báo lỗi
- **Dữ liệu đầu vào**: ma_lop={mã lớp B}
- **Kết quả mong đợi**: Bị chặn kèm thông báo “Bạn không phụ trách lớp …”; KHÔNG tạo đề tài nào
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicImportTest.php`

### TC-STU-35 — Dòng lỗi (mã lớp sai / mô tả < 10 ký tự) bị báo theo dòng và không tạo đề tài

- **Chức năng**: Import đề tài (#6) · **Role**: Admin · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập admin; file có 1 dòng mã lớp sai + 1 dòng mô tả 4 ký tự
- **Các bước thực hiện**:
  1. Upload file lỗi
  2. Đọc thông báo (kèm số dòng)
  3. Kiểm tra danh sách đề tài
- **Dữ liệu đầu vào**: ma_lop=KHONGCO · mo_ta=“Ngắn”
- **Kết quả mong đợi**: Quay lại form import kèm lỗi “Dòng N: …”; không có đề tài nào được tạo
- **Kiểm tra thêm (DB / log / API)**: topics không tăng số dòng
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicImportTest.php`

### TC-STU-36 — Sinh viên đã có nhóm: yêu cầu Pending cùng lớp tự chuyển Expired và bị ẩn khỏi tab mặc định

- **Chức năng**: Yêu cầu hết hiệu lực (#8) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv1 đã có nhóm trong lớp; trước đó đã gửi yêu cầu vào nhóm khác cùng lớp (dữ liệu “treo”)
- **Các bước thực hiện**:
  1. sv1 mở /user/join_requests
  2. Kiểm tra tab mặc định “Đang chờ”
  3. Bấm tab “Hết hiệu lực”
  4. Bấm tab “Tất cả”
- **Dữ liệu đầu vào**: URL /user/join_requests (mặc định) · ?status=Expired · ?status=all
- **Kết quả mong đợi**: Yêu cầu tự chuyển `Expired`; tab mặc định KHÔNG liệt kê nhóm đó; tab “Hết hiệu lực”/“Tất cả” vẫn xem được (giữ lịch sử trong DB)
- **Kiểm tra thêm (DB / log / API)**: join_requests.status = Expired (không xóa dòng)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/JoinRequestExpiryTest.php`
- **Ghi chú**: Dọn dẹp chạy khi mở trang nhờ `InvitationService::expireStalePendingRequestsFor()` (idempotent).

### TC-STU-37 — Trang “Nhóm của tôi” không còn hiện “Đang chờ duyệt” khi sinh viên đã có nhóm trong lớp

- **Chức năng**: Yêu cầu hết hiệu lực (#8) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv1 đã có nhóm trong lớp và còn yêu cầu Pending ở nhóm khác cùng lớp
- **Các bước thực hiện**:
  1. sv1 mở /user/my_groups
  2. Mở modal “Xin tham gia nhóm”
  3. Tìm thẻ của nhóm khác cùng lớp
- **Dữ liệu đầu vào**: URL /user/my_groups
- **Kết quả mong đợi**: Thẻ hiện “Bạn đã có nhóm lớp này” (KHÔNG còn “Đang chờ duyệt”)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/JoinRequestExpiryTest.php`

### TC-STU-38 — Yêu cầu còn hiệu lực KHÔNG bị dọn oan

- **Chức năng**: Yêu cầu hết hiệu lực (#8) · **Role**: Sinh viên · **Loại**: DB · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: sv1 chưa có nhóm trong lớp; nhóm đích vẫn còn chỗ
- **Các bước thực hiện**:
  1. sv1 gửi yêu cầu tham gia nhóm còn chỗ
  2. Mở /user/join_requests
- **Dữ liệu đầu vào**: POST /user/join_requests/send
- **Kết quả mong đợi**: Yêu cầu vẫn `Pending` và vẫn hiện ở tab mặc định
- **Kiểm tra thêm (DB / log / API)**: join_requests.status = Pending
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/JoinRequestExpiryTest.php`

### TC-STU-39 — Nhóm đã đầy ⇒ yêu cầu Pending của nhóm đó chuyển Expired

- **Chức năng**: Yêu cầu hết hiệu lực (#8) · **Role**: Sinh viên · **Loại**: DB · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đề tài của lớp quy định max 2 thành viên; nhóm đã có 1 trưởng + 1 thành viên; sv1 đang có yêu cầu Pending
- **Các bước thực hiện**:
  1. sv1 mở /user/join_requests
- **Dữ liệu đầu vào**: URL /user/join_requests
- **Kết quả mong đợi**: Yêu cầu chuyển `Expired` với lý do “Nhóm đã đủ thành viên”
- **Kiểm tra thêm (DB / log / API)**: join_requests.status = Expired
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/JoinRequestExpiryTest.php`

### TC-STU-40 — Vào form tạo nhóm từ thẻ lớp: lớp học phần hiện dạng TEXT, KHÔNG bắt chọn lại ở combo box

- **Chức năng**: Tạo nhóm theo từng lớp học phần (#8) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv1 đã tham gia lớp B và chưa có nhóm trong lớp B
- **Các bước thực hiện**:
  1. sv1 bấm “Tạo nhóm mới” ở thẻ lớp B (link có ?class_id={B})
  2. Quan sát ô “Lớp học phần” trong form
  3. Nhập tên nhóm và gửi
- **Dữ liệu đầu vào**: GET /user/groups/create?class_id={B}
- **Kết quả mong đợi**: Ô “Lớp học phần” chỉ hiển thị TÊN LỚP dạng text (kèm input hidden class_id), không có combo box; gửi form ⇒ tạo nhóm đúng lớp B
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/GroupCreationScopeTest.php`
- **Ghi chú**: `class_id` sai/không thuộc quyền ⇒ quay lại combo box đã lọc (không tin dữ liệu từ URL).

### TC-STU-41 — Sinh viên đã có nhóm ở TẤT CẢ lớp học phần ⇒ hiện cảnh báo và khóa nút tạo nhóm (case âm)

- **Chức năng**: Tạo nhóm theo từng lớp học phần (#8) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv1 đã có nhóm trong lớp A và lớp B (học cùng lúc 2 lớp)
- **Các bước thực hiện**:
  1. sv1 mở /user/groups/create
  2. Quan sát cảnh báo và trạng thái nút “Tạo nhóm”
- **Dữ liệu đầu vào**: URL /user/groups/create
- **Kết quả mong đợi**: Hiện cảnh báo “Bạn đã có nhóm ở tất cả lớp học phần”; nút tạo nhóm bị vô hiệu hóa, không chọn được lớp nào
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/GroupCreationScopeTest.php`

### TC-STU-42 — Modal “Tìm nhóm” của mỗi lớp CHỈ liệt kê nhóm còn chỗ thuộc ĐÚNG lớp học phần đó

- **Chức năng**: Tìm nhóm theo từng lớp học phần (#8) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Lớp A có 1 nhóm còn chỗ, lớp B cũng có 1 nhóm còn chỗ; sv1 học cả 2 lớp và chưa có nhóm
- **Các bước thực hiện**:
  1. sv1 mở /user/my_groups
  2. Bấm “Tìm nhóm” ở thẻ lớp A
  3. Bấm “Tìm nhóm” ở thẻ lớp B
- **Dữ liệu đầu vào**: URL /user/my_groups (mỗi lớp 1 modal riêng: findGroupModal-{class_id})
- **Kết quả mong đợi**: Modal của lớp A chỉ chứa nhóm lớp A (không lẫn nhóm lớp B) và ngược lại; mỗi lớp có 1 modal riêng
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/GroupCreationScopeTest.php`

### TC-STU-43 — POST tạo nhóm với class_id của lớp mình KHÔNG tham gia bị chặn (bịt lỗ hổng sửa class_id)

- **Chức năng**: Tạo nhóm theo từng lớp học phần (#8) · **Role**: Sinh viên / Bảo mật · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv1 không tham gia lớp CNTT09-K1
- **Các bước thực hiện**:
  1. Gửi POST /user/groups/store với class_id của lớp chưa tham gia
  2. Kiểm tra DB bảng `groups`
- **Dữ liệu đầu vào**: POST /user/groups/store {group_name, class_id=<lớp khác>}
- **Kết quả mong đợi**: Bị chặn kèm thông báo lỗi; KHÔNG tạo nhóm mới
- **Kiểm tra thêm (DB / log / API)**: `groups` không phát sinh dòng cho nhóm giả mạo
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/GroupCreationScopeTest.php`
- **Ghi chú**: Kiểm tra bổ sung ở tầng service (`GroupService::createGroupByStudent`) nên không thể lách qua HTTP.

### TC-STU-44 — File CSV phân cách TAB (sao chép từ Excel sang Notepad) hoặc dấu chấm phẩy vẫn import đúng

- **Chức năng**: Import đề tài (#6) · **Role**: Giảng viên / Admin · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Admin (hoặc giảng viên phụ trách lớp); file CSV phân cách TAB/chấm phẩy, dòng 1 là tiêu đề 8 cột
- **Các bước thực hiện**:
  1. Mở /topics/import/form
  2. Chọn file CSV phân cách TAB
  3. Bấm Import ngay
- **Dữ liệu đầu vào**: File CSV: ten_de_tai TAB mo_ta TAB ... TAB han_dang_ky
- **Kết quả mong đợi**: Import thành công với số “thêm mới” đúng; KHÔNG còn thông báo “Tên đề tài không được để trống” cho mọi dòng
- **Kiểm tra thêm (DB / log / API)**: topics có đủ số dòng mới; class_id / subject_id / lecturer suy ra từ mã lớp
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicImportTest.php`
- **Ghi chú**: Nguyên nhân lỗi cũ: Maatwebsite khoá cứng dấu phân cách là dấu phẩy (dự án không có config/excel.php) nên file TAB bị đọc thành 1 cột. Nay TopicsImport::detectCsvDelimiter() tự dò dấu phẩy / chấm phẩy / TAB / sổ đứng.

### TC-STU-45 — Dòng trống ở cuối file KHÔNG bị tính là dòng lỗi

- **Chức năng**: Import đề tài (#6) · **Role**: Giảng viên / Admin · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: File CSV có 1 dòng dữ liệu và 3 dòng trống ở cuối (Excel hay để lại)
- **Các bước thực hiện**:
  1. Import file có dòng trống cuối
  2. Đọc thông báo kết quả
- **Dữ liệu đầu vào**: File CSV + 3 dòng trống cuối
- **Kết quả mong đợi**: Thông báo chỉ có “thêm mới 1, bỏ qua 0”, KHÔNG có phần “dòng lỗi”
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicImportTest.php`
- **Ghi chú**: TopicsImport nay implements SkipsEmptyRows nên dòng trống bị bỏ qua trước khi validate.

### TC-STU-46 — File sai tên cột ở dòng tiêu đề nhận MỘT thông báo rõ ràng thay vì lỗi lặp cho từng dòng

- **Chức năng**: Import đề tài (#6) · **Role**: Giảng viên / Admin · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: File CSV có tiêu đề “Mô tả đề tài”, “Mã lớp học phần” (slug thành mo_ta_de_tai / ma_lop_hoc_phan)
- **Các bước thực hiện**:
  1. Mở /topics/import/form
  2. Chọn file sai tiêu đề
  3. Bấm Import ngay
- **Dữ liệu đầu vào**: File CSV sai tên cột tiêu đề
- **Kết quả mong đợi**: Quay lại form kèm 1 thông báo “Không đọc được dòng tiêu đề…”, liệt kê cột cần có + cột thiếu; không tạo đề tài nào
- **Kiểm tra thêm (DB / log / API)**: topics không tăng số dòng
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicImportTest.php`
- **Ghi chú**: TopicController::import() đọc trước dòng tiêu đề (TopicsHeadingRowImport) để chẩn đoán; trước đây file 9 dòng sai tiêu đề sinh ra 27 dòng lỗi.

### TC-STU-47 — Form import ghi rõ hệ thống tự dò dấu phân cách (dấu phẩy / chấm phẩy / TAB)

- **Chức năng**: Import đề tài (#6) · **Role**: Giảng viên / Admin · **Loại**: UI · **Ưu tiên**: Thấp
- **Tiền điều kiện**: Đăng nhập admin hoặc giảng viên
- **Các bước thực hiện**:
  1. Mở /topics/import/form
  2. Đọc phần hướng dẫn cột file
- **Dữ liệu đầu vào**: URL /topics/import/form
- **Kết quả mong đợi**: Có gạch đầu dòng “Dấu phân cách: hệ thống TỰ DÒ dấu phẩy, dấu chấm phẩy hoặc TAB…”
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicImportTest.php`
- **Ghi chú**: Kiểm tra hiển thị bằng mắt trên trình duyệt (test tự động chỉ khẳng định nội dung trang).

### TC-STU-48 — Môn có 2 bài báo cáo: tạo được đề tài GIỮA KÌ và CUỐI KÌ cho cùng lớp

- **Chức năng**: Quản lý đề tài (#6) · **Role**: Giảng viên / Admin · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Môn của lớp có report_count = 2
- **Các bước thực hiện**:
  1. Mở /topics/create
  2. Chọn lớp (môn 2 bài)
  3. Chọn loại báo cáo Giữa kì / Cuối kì
  4. Bấm Lưu
- **Dữ liệu đầu vào**: class_id + report_type=midterm|final
- **Kết quả mong đợi**: Lưu thành công; danh sách đề tài hiển thị nhãn Giữa kì / Cuối kì
- **Kiểm tra thêm (DB / log / API)**: topics.report_type = midterm / final
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicReportTypeTest.php`
- **Ghi chú**: Môn 1 bài: gửi midterm bị ÉP về final (chỉ có đồ án cuối kì).

### TC-STU-49 — Nhóm của lớp đăng ký và được duyệt CẢ 2 đề tài (1 giữa kì + 1 cuối kì), không cần thứ tự

- **Chức năng**: Đăng ký đề tài 2 bài (#7) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Nhóm đủ thành viên tối thiểu; lớp có 1 đề tài giữa kì + 1 đề tài cuối kì
- **Các bước thực hiện**:
  1. Trưởng nhóm đăng ký đề tài giữa kì
  2. GV duyệt
  3. Trưởng nhóm đăng ký đề tài cuối kì
  4. GV duyệt
- **Dữ liệu đầu vào**: POST /topics/register (2 lần, khác loại)
- **Kết quả mong đợi**: Cả 2 yêu cầu Accepted; nhóm có 2 đề tài; groups.topic_id = đề tài CUỐI KÌ
- **Kiểm tra thêm (DB / log / API)**: topic_requests: 2 dòng Accepted · topics.assigned_group_id = group_id cho cả 2 · groups.topic_id = topic cuối kì
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicReportTypeTest.php`

### TC-STU-50 — Nhóm KHÔNG đăng ký được đề tài thứ 2 CÙNG LOẠI; duyệt giữa kì không làm rớt yêu cầu cuối kì

- **Chức năng**: Đăng ký đề tài 2 bài (#7) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Nhóm đã có đề tài cuối kì được duyệt; còn 1 đề tài cuối kì khác + 1 đề tài giữa kì đang chờ
- **Các bước thực hiện**:
  1. Đăng ký đề tài cuối kì thứ 2
  2. Duyệt yêu cầu giữa kì đang chờ
- **Dữ liệu đầu vào**: POST /topics/register + PATCH /topic-requests/{id}/approve
- **Kết quả mong đợi**: Đăng ký cùng loại bị chặn kèm thông báo; duyệt giữa kì chỉ từ chối các yêu cầu CÙNG LOẠI ⇒ yêu cầu cuối kì vẫn Pending
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/TopicReportTypeTest.php`

## 4. Cách chạy nhóm test này

```powershell
cd G:\MyApp\laragon\www\Team-Assign
php artisan test tests/Feature/Services/GroupServiceTest.php tests/Feature/Services/InvitationServiceTest.php tests/Feature/Services/TopicRegistrationServiceTest.php tests/Feature/TopicControllerTest.php tests/Feature/UserDashboardTopicsTest.php tests/Feature/TopicImportTest.php tests/Feature/JoinRequestExpiryTest.php
```

## 5. Ghi chú & rủi ro

- Quy tắc “1 nhóm / 1 lớp”: sinh viên đã có nhóm CÙNG LỚP thì không gửi được yêu cầu / bị từ chối lời mời vào nhóm thứ 2 của lớp đó; nhưng vẫn tham gia được nhóm của LỚP KHÁC (1 sinh viên học nhiều lớp).
- Trạng thái nhóm: `incomplete` khi chưa đủ `min_members`, `complete` khi đạt tối thiểu; xóa nhóm chỉ được khi nhóm CHƯA gán đề tài.
- Yêu cầu/lời mời hết hiệu lực (nhóm đủ thành viên, sinh viên đã có nhóm khác, đã xử lý) được chuyển `Expired` — UI ẩn nút Chấp nhận/Từ chối và badge realtime giảm.
- Trưởng nhóm là người duy nhất gửi/hủy yêu cầu đăng ký đề tài và duyệt yêu cầu tham gia nhóm (phân quyền kiểm ở `GroupService`/`TopicRegistrationService`).
- Ghi chú dữ liệu: `topic_requests.status ∈ {Pending, Accepted, Rejected}`; đăng ký trùng (cùng topic_id + group_id) bị chặn ở cả tầng unique DB và tầng service.
- Case có nhãn `Chưa chạy tay` cần tự chạy trên trình duyệt (2 tài khoản nếu cần realtime) rồi đổi trạng thái trong `data/05-sinh-vien-nhom-va-dang-ky-de-tai.php` và export lại.

<sub>Sinh tự động bởi `php artisan testcases:export` · nguồn: `docs/test-cases/data/05-sinh-vien-nhom-va-dang-ky-de-tai.php`</sub>
