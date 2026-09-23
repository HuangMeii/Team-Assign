# Nhóm 09 — Bảng tin lớp học (kiểu Google Classroom)

> **Mã nhóm**: `TC-STREAM` · **Chức năng**: FEATURE_STATUS #19: ClassStreamController/ClassStreamService + observer GroupObserver/GroupMemberObserver + bảng class_posts, class_post_comments + realtime `class.{id}` + lệnh class-stream:backfill
> **Số test case**: 15 — Pass: **13** · Fail: **0** · Chưa chạy tay: **2**
> **Môi trường**: MySQL team_assign_test; BROADCAST_CONNECTION=null khi chạy test ⇒ phần realtime (banner bài mới, bình luận tự chèn) phải kiểm tra tay với `php artisan reverb:start`.
> ↻ File này **sinh tự động** từ `docs/test-cases/data/09-bang-tin-lop-hoc.php` — sửa dữ liệu ở đó rồi chạy `php artisan testcases:export` (đừng sửa file .md này).

## 1. Mục tiêu & phạm vi

Kiểm thử bảng tin của lớp học phần: giảng viên phụ trách/admin đăng thông báo, hoạt động nhóm được hệ thống tự ghi (thành lập nhóm, thêm/rời thành viên, đổi trưởng nhóm, nhóm được duyệt đề tài, giải tán), bình luận + trả lời 1 cấp, ghim/xoá theo quyền, thông báo qua chuông và realtime Reverb, cùng lệnh backfill dữ liệu cũ (idempotent).

## 2. Bảng tóm tắt test case

| Mã TC | Tên / Mục tiêu | Role | Loại | Ưu tiên | Trạng thái |
|---|---|---|---|---|---|
| `TC-STREAM-01` | Giảng viên phụ trách đăng thông báo: 1 bài vào bảng tin + sinh viên trong lớp nhận thông báo | Giảng viên | API | Cao | Pass |
| `TC-STREAM-02` | Sinh viên và giảng viên không phụ trách KHÔNG được đăng thông báo (case âm) | Sinh viên | API | Cao | Pass |
| `TC-STREAM-03` | Sinh viên trong lớp xem được bảng tin, người ngoài lớp bị chặn | Sinh viên | UI | Cao | Pass |
| `TC-STREAM-04` | Thành lập nhóm ⇒ tự sinh bài `group_created` trên bảng tin lớp | Hệ thống | DB | Cao | Pass |
| `TC-STREAM-05` | Thêm thành viên vào nhóm ⇒ bài `group_member_joined` | Hệ thống | DB | Trung bình | Pass |
| `TC-STREAM-06` | Duyệt đề tài cho nhóm ⇒ bài `group_topic` (đúng 1 bài, không lặp) | Hệ thống | DB | Cao | Pass |
| `TC-STREAM-07` | Ghim bài ⇒ bài ghim đứng đầu bảng tin; xoá bài ⇒ mất khỏi bảng tin | Giảng viên | API | Trung bình | Pass |
| `TC-STREAM-08` | Bình luận của sinh viên ⇒ giảng viên nhận thông báo; hỗ trợ trả lời và xoá đúng quyền | Sinh viên | API | Cao | Pass |
| `TC-STREAM-09` | Backfill: chạy lần đầu tạo bài, chạy lần hai KHÔNG nhân đôi (idempotent) | Admin | CLI | Trung bình | Pass |
| `TC-STREAM-10` | Lỗi khi ghi bảng tin KHÔNG làm hỏng việc tạo nhóm/thêm thành viên | Hệ thống | API | Cao | Pass |
| `TC-STREAM-11` | Mọi loại bài đều có nhãn + icon + màu; loại bài lạ vẫn có mặc định (không lỗi) | Hệ thống | API | Thấp | Pass |
| `TC-STREAM-12` | Phân biệt bài HỆ THỐNG (hoạt động nhóm) và bài do người đăng | Hệ thống | API | Trung bình | Pass |
| `TC-STREAM-13` | Hằng số giới hạn nội dung dùng chung với validate của controller | Hệ thống | API | Trung bình | Pass |
| `TC-STREAM-14` | Thẻ preview 3 bài mới nhất hiển thị ở trang lớp của sinh viên/giảng viên/admin | Sinh viên | UI | Thấp | Chưa chạy tay |
| `TC-STREAM-15` | Realtime Reverb: bài mới hiện banner, bình luận mới tự chèn vào bài đang mở | Sinh viên | Realtime | Trung bình | Chưa chạy tay |

## 3. Chi tiết test case

### TC-STREAM-01 — Giảng viên phụ trách đăng thông báo: 1 bài vào bảng tin + sinh viên trong lớp nhận thông báo

- **Chức năng**: Đăng thông báo (#19) · **Role**: Giảng viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập gv1@test.com (phụ trách lớp CNTT01-K1); lớp có ít nhất 1 sinh viên
- **Các bước thực hiện**:
  1. Mở /classes/{id}/stream
  2. Nhập nội dung thông báo
  3. Bấm đăng
  4. Đăng nhập một sinh viên và mở chuông thông báo
- **Dữ liệu đầu vào**: content=Tuần này nộp báo cáo chương 2 (kèm tiêu đề tuỳ chọn)
- **Kết quả mong đợi**: Bài `announcement` xuất hiện đầu bảng tin; sinh viên của lớp nhận 1 thông báo `class_announcement`
- **Kiểm tra thêm (DB / log / API)**: class_posts: 1 dòng (type=announcement, user_id=gv, class_id) · notifications cho từng SV trong lớp
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ClassStreamTest.php`

### TC-STREAM-02 — Sinh viên và giảng viên không phụ trách KHÔNG được đăng thông báo (case âm)

- **Chức năng**: Phân quyền bảng tin (#19) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập sinh viên thuộc lớp, và một giảng viên khác không phụ trách lớp
- **Các bước thực hiện**:
  1. Mở bảng tin lớp
  2. Thử POST /classes/{id}/stream với nội dung bất kỳ (2 vai trò trên)
- **Dữ liệu đầu vào**: POST /classes/{id}/stream
- **Kết quả mong đợi**: Bị chặn 403 (hoặc chuyển hướng kèm lỗi); không sinh bài mới trong class_posts
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ClassStreamTest.php`

### TC-STREAM-03 — Sinh viên trong lớp xem được bảng tin, người ngoài lớp bị chặn

- **Chức năng**: Xem bảng tin (#19) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Cao
- **Tiền điều kiện**: Sinh viên A thuộc lớp, sinh viên B không thuộc lớp
- **Các bước thực hiện**:
  1. SV A mở /classes/{id}/stream
  2. SV B mở cùng URL
  3. SV B gọi API bình luận của lớp đó
- **Dữ liệu đầu vào**: URL /classes/{classId}/stream
- **Kết quả mong đợi**: SV A thấy 200 + danh sách bài; SV B bị 403/chuyển hướng và không thấy nội dung
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ClassStreamTest.php`

### TC-STREAM-04 — Thành lập nhóm ⇒ tự sinh bài `group_created` trên bảng tin lớp

- **Chức năng**: Hoạt động nhóm tự sinh (#19) · **Role**: Hệ thống · **Loại**: DB · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv1 chưa có nhóm trong lớp CNTT01-K1
- **Các bước thực hiện**:
  1. sv1 tạo nhóm mới
  2. Mở /classes/{id}/stream
  3. Kiểm tra bài mới
- **Dữ liệu đầu vào**: Tạo nhóm qua /user/groups/store
- **Kết quả mong đợi**: Bảng tin có 1 bài `group_created` do HỆ THỐNG sinh (không có tên người đăng) nêu đúng tên nhóm
- **Kiểm tra thêm (DB / log / API)**: class_posts: user_id = NULL, type = group_created, group_id = nhóm mới
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ClassStreamTest.php`

### TC-STREAM-05 — Thêm thành viên vào nhóm ⇒ bài `group_member_joined`

- **Chức năng**: Hoạt động nhóm tự sinh (#19) · **Role**: Hệ thống · **Loại**: DB · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Nhóm Alpha đang có 1 trưởng nhóm
- **Các bước thực hiện**:
  1. Thêm sv3 vào nhóm (chấp nhận lời mời/yêu cầu)
  2. Kiểm tra bảng tin
- **Dữ liệu đầu vào**: Chấp nhận lời mời/Yêu cầu tham gia
- **Kết quả mong đợi**: Có thêm 1 bài `group_member_joined` nêu tên thành viên và tên nhóm
- **Kiểm tra thêm (DB / log / API)**: class_posts: type = group_member_joined, source_key duy nhất
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ClassStreamTest.php`

### TC-STREAM-06 — Duyệt đề tài cho nhóm ⇒ bài `group_topic` (đúng 1 bài, không lặp)

- **Chức năng**: Hoạt động nhóm tự sinh (#19) · **Role**: Hệ thống · **Loại**: DB · **Ưu tiên**: Cao
- **Tiền điều kiện**: Nhóm đã gửi yêu cầu đăng ký đề tài; giảng viên duyệt
- **Các bước thực hiện**:
  1. Giảng viên duyệt yêu cầu đăng ký
  2. Kiểm tra bảng tin lớp
  3. Duyệt lại/refresh trang
- **Dữ liệu đầu vào**: PATCH /topic-requests/{id}/approve
- **Kết quả mong đợi**: Có đúng 1 bài `group_topic` nêu tên đề tài; không bị nhân đôi khi thao tác lại
- **Kiểm tra thêm (DB / log / API)**: class_posts.source_key unique chặn trùng
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ClassStreamTest.php`

### TC-STREAM-07 — Ghim bài ⇒ bài ghim đứng đầu bảng tin; xoá bài ⇒ mất khỏi bảng tin

- **Chức năng**: Ghim / xoá bài (#19) · **Role**: Giảng viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Bảng tin lớp có ít nhất 3 bài
- **Các bước thực hiện**:
  1. Ghim bài cũ nhất
  2. Tải lại bảng tin kiểm tra vị trí
  3. Xoá một bài rồi kiểm tra
- **Dữ liệu đầu vào**: PATCH /classes/{id}/stream/{post}/pin · DELETE /classes/{id}/stream/{post}
- **Kết quả mong đợi**: Bài ghim luôn ở đầu danh sách; bài bị xoá biến mất và số bài giảm 1
- **Kiểm tra thêm (DB / log / API)**: class_posts.is_pinned = 1 · dòng bài bị xoá không còn
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ClassStreamTest.php`

### TC-STREAM-08 — Bình luận của sinh viên ⇒ giảng viên nhận thông báo; hỗ trợ trả lời và xoá đúng quyền

- **Chức năng**: Bình luận & trả lời (#19) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Lớp có bài của giảng viên; sv1 thuộc lớp; sv3 là tác giả 1 bình luận
- **Các bước thực hiện**:
  1. sv1 bình luận vào bài
  2. Kiểm tra thông báo của giảng viên
  3. Giảng viên trả lời bình luận đó
  4. Thử xoá bình luận của người khác (case âm)
- **Dữ liệu đầu vào**: POST/DELETE /classes/{id}/stream/{post}/comments
- **Kết quả mong đợi**: Bình luận + trả lời 1 cấp hiển thị đúng; chủ bài và tác giả bình luận gốc nhận thông báo `class_comment`; xoá bình luận người khác bị chặn
- **Kiểm tra thêm (DB / log / API)**: class_post_comments có parent_id cho reply · class_posts.comments_count cập nhật
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ClassStreamTest.php`

### TC-STREAM-09 — Backfill: chạy lần đầu tạo bài, chạy lần hai KHÔNG nhân đôi (idempotent)

- **Chức năng**: Backfill dữ liệu cũ (#19) · **Role**: Admin · **Loại**: CLI · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: DB có nhóm/đề tài cũ chưa có bài trên bảng tin
- **Các bước thực hiện**:
  1. Chạy `php artisan class-stream:backfill --dry-run` (chỉ đếm)
  2. Chạy thật lần 1
  3. Chạy lại lần 2
  4. So sánh số bài hệ thống
- **Dữ liệu đầu vào**: php artisan class-stream:backfill [--class=ID] [--dry-run] [--with-status]
- **Kết quả mong đợi**: Dry-run không ghi dữ liệu; lần 1 tạo bài; lần 2 thêm 0 bài (bỏ qua nhờ `source_key`); mốc `created_at` giữ theo thời điểm gốc
- **Kiểm tra thêm (DB / log / API)**: class_posts.user_id IS NULL — số dòng không đổi sau lần chạy thứ 2
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ClassStreamTest.php`

### TC-STREAM-10 — Lỗi khi ghi bảng tin KHÔNG làm hỏng việc tạo nhóm/thêm thành viên

- **Chức năng**: Fail-open bảng tin (#19) · **Role**: Hệ thống · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Giả lập lỗi ghi `class_posts` (mock/repo lỗi) khi tạo nhóm
- **Các bước thực hiện**:
  1. Tạo nhóm trong điều kiện ghi bảng tin lỗi
  2. Kiểm tra nhóm được tạo
- **Dữ liệu đầu vào**: Luồng tạo nhóm + observer ghi bảng tin
- **Kết quả mong đợi**: Nhóm vẫn được tạo thành công; chỉ ghi log cảnh báo, không ném exception ra người dùng
- **Kiểm tra thêm (DB / log / API)**: groups có dòng mới; log có warning “bỏ qua ghi hoạt động nhóm”
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ClassStreamTest.php`

### TC-STREAM-11 — Mọi loại bài đều có nhãn + icon + màu; loại bài lạ vẫn có mặc định (không lỗi)

- **Chức năng**: Nhãn & icon bài (#19) · **Role**: Hệ thống · **Loại**: API · **Ưu tiên**: Thấp
- **Tiền điều kiện**: Không cần DB (unit test)
- **Các bước thực hiện**:
  1. Gọi hàm lấy nhãn/icon/màu cho từng loại bài đã biết
  2. Gọi với loại bài không tồn tại trong danh sách
- **Dữ liệu đầu vào**: announcement, group_created, group_member_joined, group_member_left, group_leader_changed, group_status, group_topic, group_deleted, type-lạ
- **Kết quả mong đợi**: Mỗi loại trả nhãn + icon + màu hợp lệ; loại lạ nhận giá trị mặc định, không ném exception
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Unit/ClassStreamTest.php`

### TC-STREAM-12 — Phân biệt bài HỆ THỐNG (hoạt động nhóm) và bài do người đăng

- **Chức năng**: Phân loại bài (#19) · **Role**: Hệ thống · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Không cần DB (unit test)
- **Các bước thực hiện**:
  1. Kiểm tra bài có user_id = NULL và bài có user_id
  2. Xem cờ phân loại + mô tả hoạt động theo từng loại
- **Dữ liệu đầu vào**: class_posts.user_id = NULL / = {id}
- **Kết quả mong đợi**: Bài hệ thống không hiện tên người đăng; mô tả sinh đúng theo từng loại hoạt động (thành lập nhóm, tham gia, rời nhóm, đổi trưởng nhóm, được duyệt đề tài, giải tán)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Unit/ClassStreamTest.php`

### TC-STREAM-13 — Hằng số giới hạn nội dung dùng chung với validate của controller

- **Chức năng**: Giới hạn nội dung (#19) · **Role**: Hệ thống · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Không cần DB (unit test)
- **Các bước thực hiện**:
  1. So sánh hằng số trong ClassStreamService với rule validate dùng ở controller
  2. Gửi bài vượt 1000 ký tự và bình luận vượt 500 ký tự
- **Dữ liệu đầu vào**: MAX_POST_LENGTH = 1000 · MAX_COMMENT_LENGTH = 500 · PER_PAGE = 10 · PREVIEW_LIMIT = 3
- **Kết quả mong đợi**: Nội dung vượt giới hạn bị từ chối kèm lỗi validate; hằng số không bị lệch giữa service và controller
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Unit/ClassStreamTest.php + tests/Feature/ClassStreamTest.php`

### TC-STREAM-14 — Thẻ preview 3 bài mới nhất hiển thị ở trang lớp của sinh viên/giảng viên/admin

- **Chức năng**: Thẻ xem trước (#19) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Thấp
- **Tiền điều kiện**: Lớp có ít nhất 4 bài trên bảng tin
- **Các bước thực hiện**:
  1. Mở /user/classes/{id}
  2. Mở /lecturer/classes/{id}
  3. Mở /admin/classes/{id}
  4. Đếm số bài trong thẻ preview
- **Dữ liệu đầu vào**: 3 trang lớp
- **Kết quả mong đợi**: Mỗi trang có thẻ “Bảng tin” hiển thị đúng 3 bài MỚI NHẤT (bài ghim luôn đầu) + liên kết sang bảng tin đầy đủ
- **Kết quả thực tế**: Chưa chạy tay
- **Trạng thái**: **Chưa chạy tay**
- **Test tự động**: (thủ công — chạy trên trình duyệt)
- **Ghi chú**: Test tự động chỉ khẳng định một phần; nên kiểm tra tay trên cả 3 vai trò.

### TC-STREAM-15 — Realtime Reverb: bài mới hiện banner, bình luận mới tự chèn vào bài đang mở

- **Chức năng**: Realtime bảng tin (#19) · **Role**: Sinh viên · **Loại**: Realtime · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Reverb đang chạy (`php artisan reverb:start`); 2 trình duyệt: giảng viên + sinh viên
- **Các bước thực hiện**:
  1. Sinh viên mở bảng tin lớp
  2. Giảng viên đăng bài mới
  3. Sinh viên bình luận
  4. Giảng viên quan sát khung bình luận
- **Dữ liệu đầu vào**: Kênh private `class.{classId}`
- **Kết quả mong đợi**: Sinh viên thấy banner “có bài mới” không cần tải lại trang; bình luận mới tự chèn vào đúng bài đang mở
- **Kết quả thực tế**: Chưa chạy tay
- **Trạng thái**: **Chưa chạy tay**
- **Test tự động**: (thủ công — chạy trên trình duyệt)
- **Ghi chú**: BROADCAST_CONNECTION=null khi chạy Pest ⇒ phải kiểm tra tay với Reverb bật.

## 4. Cách chạy nhóm test này

```powershell
cd G:\MyApp\laragon\www\Team-Assign
php artisan test tests/Unit/ClassStreamTest.php tests/Feature/ClassStreamTest.php
```

## 5. Ghi chú & rủi ro

- Một bộ route dùng chung cho mọi vai trò (`/classes/{classId}/stream`); quyền được kiểm TẬP TRUNG trong `ClassStreamService::canManage()/canView()` nên không cần middleware riêng — GV phụ trách hoặc admin mới được đăng bài.
- Bài do HỆ THỐNG sinh (`user_id = NULL`) biểu diễn hoạt động nhóm; `source_key` (unique) + `created_at` giữ mốc gốc là cơ chế chống trùng cho lệnh backfill.
- Loại bài và mô tả: `announcement`, `group_created`, `group_member_joined`, `group_member_left`, `group_leader_changed`, `group_status`, `group_topic`, `group_deleted` (mọi loại có nhãn + icon + màu, kể cả loại lạ nhờ giá trị mặc định).
- Giới hạn nội dung dùng chung hằng số với controller: bài `MAX_POST_LENGTH = 1000`, bình luận `MAX_COMMENT_LENGTH = 500`, mỗi trang `PER_PAGE = 10`, thẻ xem trước `PREVIEW_LIMIT = 3`.
- Tất cả hành vi phụ (thông báo, realtime, ghi bảng tin) đều FAIL-OPEN: lỗi khi ghi bảng tin KHÔNG được làm hỏng việc tạo nhóm/thêm thành viên.
- Case có nhãn `Chưa chạy tay` cần tự chạy trên trình duyệt (2 tài khoản nếu cần realtime) rồi đổi trạng thái trong `data/09-bang-tin-lop-hoc.php` và export lại.

<sub>Sinh tự động bởi `php artisan testcases:export` · nguồn: `docs/test-cases/data/09-bang-tin-lop-hoc.php`</sub>
