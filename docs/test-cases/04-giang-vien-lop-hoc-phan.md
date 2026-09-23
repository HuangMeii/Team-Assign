# Nhóm 04 — Giảng viên & lớp học phần

> **Mã nhóm**: `TC-LECT` · **Chức năng**: FEATURE_STATUS #4 (lớp học phần — phía giảng viên), #5 (quản lý sinh viên trong lớp), luồng sinh viên tham gia lớp bằng mã lớp
> **Số test case**: 15 — Pass: **15** · Fail: **0** · Chưa chạy tay: **0**
> **Môi trường**: MySQL team_assign_test; đăng nhập gv1@test.com (giảng viên) và sv1@test.com (sinh viên); phân công giảng viên lưu ở pivot `user_classes`.
> ↻ File này **sinh tự động** từ `docs/test-cases/data/04-giang-vien-lop-hoc-phan.php` — sửa dữ liệu ở đó rồi chạy `php artisan testcases:export` (đừng sửa file .md này).

## 1. Mục tiêu & phạm vi

Kiểm thử nghiệp vụ lớp học phần ở phía giảng viên: tạo lớp (mã lớp tự sinh 5 ký tự, duy nhất), xem danh sách lớp mình phụ trách, chi tiết lớp + danh sách sinh viên, thêm/xóa sinh viên, khóa–mở lớp, giới hạn quyền (chỉ lớp mình phụ trách); và phía sinh viên: tham gia lớp bằng mã, xem chi tiết lớp mình đã tham gia.

## 2. Bảng tóm tắt test case

| Mã TC | Tên / Mục tiêu | Role | Loại | Ưu tiên | Trạng thái |
|---|---|---|---|---|---|
| `TC-LECT-01` | Giảng viên tạo lớp học phần thành công, mã lớp tự sinh 5 ký tự | Giảng viên | UI | Cao | Pass |
| `TC-LECT-02` | Mã lớp tự sinh là duy nhất giữa các lớp khác nhau | Giảng viên | API | Trung bình | Pass |
| `TC-LECT-03` | Tên lớp trùng bị từ chối kèm thông báo tiếng Việt (case âm) | Giảng viên | API | Trung bình | Pass |
| `TC-LECT-04` | Giảng viên thấy danh sách lớp mình phụ trách kèm mã lớp | Giảng viên | UI | Cao | Pass |
| `TC-LECT-05` | Giảng viên xem chi tiết lớp kèm mã lớp và danh sách sinh viên | Giảng viên | UI | Cao | Pass |
| `TC-LECT-06` | Giảng viên thêm sinh viên vào lớp và KHÔNG thêm trùng sinh viên đã có | Giảng viên | API | Cao | Pass |
| `TC-LECT-07` | Xóa sinh viên khỏi lớp thì dữ liệu nhóm của sinh viên đó được dọn dẹp | Giảng viên | API | Cao | Pass |
| `TC-LECT-08` | Giảng viên không quản lý được lớp mình KHÔNG phụ trách (case âm) | Giảng viên | API | Cao | Pass |
| `TC-LECT-09` | Giảng viên khóa và mở khóa lớp học phần của mình | Giảng viên | API | Cao | Pass |
| `TC-LECT-10` | Sinh viên không truy cập được trang quản lý lớp của giảng viên (case âm) | Sinh viên | API | Cao | Pass |
| `TC-LECT-11` | Sinh viên thấy menu lớp học và chỉ thấy các lớp mình đã tham gia | Sinh viên | UI | Trung bình | Pass |
| `TC-LECT-12` | Sinh viên xem chi tiết lớp của mình và thấy mã lớp để chia sẻ | Sinh viên | UI | Cao | Pass |
| `TC-LECT-13` | Sinh viên không xem được chi tiết lớp mình chưa tham gia (case âm) | Sinh viên | API | Cao | Pass |
| `TC-LECT-14` | Sinh viên tham gia lớp thành công bằng mã lớp; các trường hợp sai bị từ chối | Sinh viên | UI | Cao | Pass |
| `TC-LECT-15` | Mỗi lớp học phần chỉ có 1 nhóm/sinh viên: combo box tạo nhóm ẩn lớp đã có nhóm và nút ở thẻ lớp mang sẵn class_id | Sinh viên | UI | Cao | Pass |

## 3. Chi tiết test case

### TC-LECT-01 — Giảng viên tạo lớp học phần thành công, mã lớp tự sinh 5 ký tự

- **Chức năng**: Tạo lớp học phần (#4) · **Role**: Giảng viên · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập gv1@test.com; có ít nhất 1 môn học trong `subjects`
- **Các bước thực hiện**:
  1. Mở /lecturer/classes/create
  2. Chọn môn học, nhập tên lớp, để trống ô mã lớp
  3. Bấm tạo
- **Dữ liệu đầu vào**: class_name=CNTT01-K1, subject_id={môn bất kỳ}
- **Kết quả mong đợi**: Tạo thành công; lớp mới xuất hiện ở /lecturer/classes kèm mã lớp 5 ký tự; giảng viên được gắn vào lớp
- **Kiểm tra thêm (DB / log / API)**: class_sections.class_code dài 5 ký tự · user_classes có 1 dòng (giảng viên phụ trách)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/LecturerClassTest.php`

### TC-LECT-02 — Mã lớp tự sinh là duy nhất giữa các lớp khác nhau

- **Chức năng**: Tạo lớp học phần (#4) · **Role**: Giảng viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập giảng viên; tạo liên tiếp nhiều lớp
- **Các bước thực hiện**:
  1. Tạo 3 lớp liên tiếp với cùng môn học
  2. So sánh mã lớp của 3 lớp
- **Dữ liệu đầu vào**: 3 lần POST /lecturer/classes
- **Kết quả mong đợi**: Cả 3 lớp tạo thành công với mã lớp KHÁC NHAU (không trùng)
- **Kiểm tra thêm (DB / log / API)**: COUNT(DISTINCT class_code) = COUNT(*) trong `class_sections`
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/LecturerClassTest.php`

### TC-LECT-03 — Tên lớp trùng bị từ chối kèm thông báo tiếng Việt (case âm)

- **Chức năng**: Tạo lớp học phần (#4) · **Role**: Giảng viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đã có lớp tên “CNTT01-K1” trong môn A
- **Các bước thực hiện**:
  1. Mở /lecturer/classes/create
  2. Nhập lại tên lớp đã tồn tại trong cùng môn A
  3. Bấm tạo
- **Dữ liệu đầu vào**: class_name=CNTT01-K1 (trùng), subject_id=môn A
- **Kết quả mong đợi**: Không tạo lớp mới; hiện thông báo lỗi bằng tiếng Việt giải thích tên lớp đã tồn tại
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/LecturerClassTest.php`

### TC-LECT-04 — Giảng viên thấy danh sách lớp mình phụ trách kèm mã lớp

- **Chức năng**: Danh sách lớp phụ trách (#4) · **Role**: Giảng viên · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập gv1@test.com; được gán vào lớp CNTT01-K1 và CNTT02-K1
- **Các bước thực hiện**:
  1. Mở /lecturer/classes
  2. Đối chiếu với bảng `user_classes`
- **Dữ liệu đầu vào**: URL /lecturer/classes
- **Kết quả mong đợi**: Trang render 200; CHỈ hiện các lớp mình phụ trách, mỗi lớp kèm mã lớp 5 ký tự
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/LecturerClassTest.php`

### TC-LECT-05 — Giảng viên xem chi tiết lớp kèm mã lớp và danh sách sinh viên

- **Chức năng**: Chi tiết lớp (#4) · **Role**: Giảng viên · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Lớp CNTT01-K1 có 3 sinh viên; giảng viên phụ trách
- **Các bước thực hiện**:
  1. Mở /lecturer/classes/{id}
  2. Kiểm tra mã lớp, thông tin môn học, danh sách sinh viên
  3. Bấm vào thẻ bảng tin của lớp
- **Dữ liệu đầu vào**: URL /lecturer/classes/{id}
- **Kết quả mong đợi**: Trang 200: mã lớp hiển thị, danh sách 3 sinh viên + kèm thẻ xem trước 3 bài mới nhất của bảng tin lớp
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/LecturerClassTest.php`

### TC-LECT-06 — Giảng viên thêm sinh viên vào lớp và KHÔNG thêm trùng sinh viên đã có

- **Chức năng**: Quản lý sinh viên trong lớp (#5) · **Role**: Giảng viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập giảng viên phụ trách lớp; sv1 đã ở trong lớp, sv3 chưa
- **Các bước thực hiện**:
  1. Mở chi tiết lớp
  2. Thêm sv3 vào lớp (thành công)
  3. Thêm lại sv3 và thêm lại sv1 (trùng)
- **Dữ liệu đầu vào**: POST /lecturer/classes/{id}/students {student_id}
- **Kết quả mong đợi**: sv3 được thêm 1 lần; lần thêm trùng bị từ chối kèm thông báo; không sinh dòng pivot trùng
- **Kiểm tra thêm (DB / log / API)**: user_classes chỉ có 1 dòng cho (sv3, lớp) — unique chặn trùng
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/LecturerClassTest.php`

### TC-LECT-07 — Xóa sinh viên khỏi lớp thì dữ liệu nhóm của sinh viên đó được dọn dẹp

- **Chức năng**: Quản lý sinh viên trong lớp (#5) · **Role**: Giảng viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Lớp có nhóm 2 thành viên (trưởng nhóm sv1 + thành viên sv3)
- **Các bước thực hiện**:
  1. Xóa sv3 khỏi lớp
  2. Kiểm tra nhóm và bảng tin
  3. Xóa tiếp trưởng nhóm sv1 rồi kiểm tra nhóm
- **Dữ liệu đầu vào**: POST /lecturer/classes/{id}/students/{studentId}/remove
- **Kết quả mong đợi**: sv3 bị gỡ khỏi `user_classes` và khỏi nhóm; khi xóa trưởng nhóm: quyền trưởng nhóm chuyển cho thành viên còn lại; nhóm rỗng thì giải tán
- **Kiểm tra thêm (DB / log / API)**: group_members/user_classes được dọn; groups.leader_id đổi hoặc nhóm bị xóa
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/LecturerClassTest.php`

### TC-LECT-08 — Giảng viên không quản lý được lớp mình KHÔNG phụ trách (case âm)

- **Chức năng**: Phân quyền lớp học phần (#4) · **Role**: Giảng viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: gv1 phụ trách lớp A; lớp B thuộc giảng viên khác
- **Các bước thực hiện**:
  1. Đăng nhập gv1
  2. Mở /lecturer/classes/{id của lớp B}
  3. Thử khóa lớp B và xóa sinh viên khỏi lớp B
- **Dữ liệu đầu vào**: URL + POST tới lớp B
- **Kết quả mong đợi**: Bị chặn 403/chuyển hướng ở mọi hành động; dữ liệu lớp B không đổi
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/LecturerClassTest.php`

### TC-LECT-09 — Giảng viên khóa và mở khóa lớp học phần của mình

- **Chức năng**: Khóa / mở lớp (#4) · **Role**: Giảng viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập giảng viên phụ trách lớp
- **Các bước thực hiện**:
  1. Mở chi tiết lớp
  2. Bấm khóa lớp
  3. Kiểm tra DB + thử sinh viên tạo nhóm trong lớp
  4. Bấm mở khóa lại
- **Dữ liệu đầu vào**: PATCH /lecturer/classes/{id}/toggle-active
- **Kết quả mong đợi**: Khóa ⇒ is_active = 0 và sinh viên không tạo nhóm/tham gia lớp được; mở ⇒ hoạt động bình thường
- **Kiểm tra thêm (DB / log / API)**: class_sections.is_active = 0 → 1
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/LecturerClassTest.php`

### TC-LECT-10 — Sinh viên không truy cập được trang quản lý lớp của giảng viên (case âm)

- **Chức năng**: Phân quyền trang giảng viên (#4) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập sinh viên
- **Các bước thực hiện**:
  1. Mở /lecturer/classes
  2. Mở /lecturer/classes/create
  3. Gọi POST tạo lớp
- **Dữ liệu đầu vào**: URL /lecturer/*
- **Kết quả mong đợi**: Bị chuyển hướng về dashboard sinh viên (middleware `lecturer`); không tạo được lớp
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/LecturerClassTest.php`

### TC-LECT-11 — Sinh viên thấy menu lớp học và chỉ thấy các lớp mình đã tham gia

- **Chức năng**: Danh sách lớp của tôi (#4) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: sv1 đã tham gia CNTT01-K1, KHÔNG tham gia CNTT02-K1
- **Các bước thực hiện**:
  1. Đăng nhập sv1
  2. Mở /user/classes
  3. Tìm tên lớp CNTT02-K1
- **Dữ liệu đầu vào**: URL /user/classes
- **Kết quả mong đợi**: Trang 200 có menu lớp học; chỉ hiện CNTT01-K1; KHÔNG thấy CNTT02-K1
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/StudentClassDetailTest.php + tests/Feature/ViewSmokeTest.php`

### TC-LECT-12 — Sinh viên xem chi tiết lớp của mình và thấy mã lớp để chia sẻ

- **Chức năng**: Chi tiết lớp của tôi (#4) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv1 đã tham gia lớp
- **Các bước thực hiện**:
  1. Mở /user/classes/{id}
  2. Kiểm tra mã lớp, giảng viên, danh sách đề tài/nhóm của lớp
- **Dữ liệu đầu vào**: URL /user/classes/{id}
- **Kết quả mong đợi**: Trang 200 có mã lớp 5 ký tự hiển thị rõ (kèm nút copy nếu có) và thẻ xem trước bảng tin lớp
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/StudentClassDetailTest.php`

### TC-LECT-13 — Sinh viên không xem được chi tiết lớp mình chưa tham gia (case âm)

- **Chức năng**: Chi tiết lớp của tôi (#4) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv1 không thuộc lớp CNTT02-K1
- **Các bước thực hiện**:
  1. Mở /user/classes/{id của CNTT02-K1}
- **Dữ liệu đầu vào**: URL /user/classes/{id}
- **Kết quả mong đợi**: Bị chặn (403/chuyển hướng) — không lộ dữ liệu lớp khác
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/StudentClassDetailTest.php`

### TC-LECT-14 — Sinh viên tham gia lớp thành công bằng mã lớp; các trường hợp sai bị từ chối

- **Chức năng**: Tham gia lớp bằng mã (#4) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Lớp CNTT01-K1 đang mở; sv3 chưa tham gia; lớp CNTT09-K1 đã bị khóa
- **Các bước thực hiện**:
  1. Mở /user/classes, nhập mã lớp ĐÚNG rồi gửi
  2. Thử lại với mã SAI
  3. Thử với mã của lớp đã khóa
  4. Thử lại với lớp đã tham gia
- **Dữ liệu đầu vào**: POST /user/classes/join {class_code}
- **Kết quả mong đợi**: Mã đúng ⇒ vào lớp thành công; mã sai ⇒ “không tìm thấy lớp”; lớp khóa ⇒ bị chặn; đã tham gia ⇒ thông báo đã ở trong lớp
- **Kiểm tra thêm (DB / log / API)**: user_classes có thêm 1 dòng cho (sv3, lớp) — không sinh dòng trùng
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ClassJoinTest.php`

### TC-LECT-15 — Mỗi lớp học phần chỉ có 1 nhóm/sinh viên: combo box tạo nhóm ẩn lớp đã có nhóm và nút ở thẻ lớp mang sẵn class_id

- **Chức năng**: Tạo nhóm theo từng lớp học phần (#4 + #8) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv1 đã tham gia 2 lớp A, B (cùng môn); sv1 đã có nhóm trong lớp A
- **Các bước thực hiện**:
  1. sv1 mở /user/groups/create
  2. Kiểm tra combo box “Lớp học phần”
  3. Mở /user/my_groups và bấm “Tạo nhóm mới” ở thẻ lớp B
- **Dữ liệu đầu vào**: URL /user/groups/create · /user/my_groups
- **Kết quả mong đợi**: Combo box KHÔNG còn lớp A (đã có nhóm) nhưng vẫn có lớp B; nút “Tạo nhóm mới” của lớp B trỏ tới /user/groups/create?class_id={B}
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/GroupCreationScopeTest.php`
- **Ghi chú**: Nguồn danh sách: `UserDashboardController::createGroupForm()` chỉ trả về lớp user ĐANG tham gia VÀ CHƯA có nhóm.

## 4. Cách chạy nhóm test này

```powershell
cd G:\MyApp\laragon\www\Team-Assign
php artisan test tests/Feature/LecturerClassTest.php tests/Feature/StudentClassDetailTest.php tests/Feature/ClassJoinTest.php
```

## 5. Ghi chú & rủi ro

- Mã lớp (`class_sections.class_code`) tự sinh 5 ký tự và DUY NHẤT — có test riêng cho tính duy nhất giữa nhiều lớp.
- Tên lớp trùng nhau trong cùng môn phải báo lỗi bằng TIẾNG VIỆT (thông báo thân thiện, không phải message mặc định của Laravel).
- Xóa sinh viên khỏi lớp phải dọn dữ liệu nhóm: nhóm còn thành viên ⇒ chuyển quyền trưởng nhóm; nhóm rỗng ⇒ giải tán (và ghi bài hệ thống lên bảng tin lớp — xem nhóm 09).
- Giảng viên KHÔNG quản lý được lớp mình không phụ trách (kể cả xóa sinh viên) — kiểm tra quyền ở cả 2 chiều: giảng viên với lớp khác và sinh viên với trang giảng viên.
- Case có nhãn `Chưa chạy tay` cần tự chạy trên trình duyệt (2 tài khoản nếu cần realtime) rồi đổi trạng thái trong `data/04-giang-vien-lop-hoc-phan.php` và export lại.

<sub>Sinh tự động bởi `php artisan testcases:export` · nguồn: `docs/test-cases/data/04-giang-vien-lop-hoc-phan.php`</sub>
