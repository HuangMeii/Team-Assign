# 2.2.2 — Mô hình hóa quy trình nghiệp vụ (Team-Assign)

> Thư mục này chứa **21 quy trình nghiệp vụ** của hệ thống Team-Assign, mỗi quy trình **1 file**, trình bày theo mẫu báo cáo:
> **a. Bằng văn bản** (Use case nghiệp vụ · Các dòng cơ bản · Các dòng thay thế) và **b. Sơ đồ hoạt động**.

## 1. Mục lục

| Mục | Tên quy trình nghiệp vụ | Tác nhân chính | File | Tài liệu kỹ thuật | Test case |
|---|---|---|---|---|---|
| 2.2.2.1 | Đăng nhập hệ thống | Khách, Sinh viên, Giảng viên, Admin | [2.2.2.01](2.2.2.01-dang-nhap-he-thong.md) | [#1](../01-dang-nhap-dang-ky-quen-mat-khau.md) | `TC-AUTH` |
| 2.2.2.2 | Quên và đặt lại mật khẩu | Khách | [2.2.2.02](2.2.2.02-quen-va-dat-lai-mat-khau.md) | [#1](../01-dang-nhap-dang-ky-quen-mat-khau.md) | `TC-AUTH` |
| 2.2.2.3 | Quản lý tài khoản người dùng | Admin | [2.2.2.03](2.2.2.03-quan-ly-tai-khoan-nguoi-dung.md) | [#2](../02-quan-ly-nguoi-dung.md) | `TC-ADMIN` |
| 2.2.2.4 | Cập nhật hồ sơ và đổi email | Sinh viên, Giảng viên, Admin | [2.2.2.04](2.2.2.04-cap-nhat-ho-so-va-doi-email.md) | [#1](../01-dang-nhap-dang-ky-quen-mat-khau.md) | `TC-AUTH` |
| 2.2.2.5 | Quản lý môn học | Admin | [2.2.2.05](2.2.2.05-quan-ly-mon-hoc.md) | [#3](../03-quan-ly-mon-hoc.md) | `TC-ADMIN` |
| 2.2.2.6 | Quản lý lớp học phần | Admin, Giảng viên | [2.2.2.06](2.2.2.06-quan-ly-lop-hoc-phan.md) | [#4](../04-quan-ly-lop-hoc-phan.md) | `TC-ADMIN`, `TC-LECT` |
| 2.2.2.7 | Tham gia lớp học phần | Sinh viên | [2.2.2.07](2.2.2.07-tham-gia-lop-hoc-phan.md) | [#4](../04-quan-ly-lop-hoc-phan.md) | `TC-LECT` |
| 2.2.2.8 | Quản lý sinh viên trong lớp học phần | Admin, Giảng viên | [2.2.2.08](2.2.2.08-quan-ly-sinh-vien-trong-lop.md) | [#5](../05-quan-ly-sinh-vien-trong-lop.md) | `TC-ADMIN`, `TC-LECT` |
| 2.2.2.9 | **Quản lý đồ án môn học** | Giảng viên, Admin | [2.2.2.09](2.2.2.09-quan-ly-do-an-mon-hoc.md) | [#6](../06-quan-ly-de-tai-va-import.md) | `TC-STU` |
| 2.2.2.10 | Đăng ký đồ án cho nhóm | Trưởng nhóm | [2.2.2.10](2.2.2.10-dang-ky-do-an-cho-nhom.md) | [#7](../07-dang-ky-de-tai-duyet-tu-choi.md) | `TC-STU` |
| 2.2.2.11 | Duyệt và từ chối đăng ký đồ án | Giảng viên, Admin | [2.2.2.11](2.2.2.11-duyet-va-tu-choi-dang-ky-do-an.md) | [#7](../07-dang-ky-de-tai-duyet-tu-choi.md) | `TC-STU` |
| 2.2.2.12 | Thành lập nhóm | Sinh viên (trưởng nhóm) | [2.2.2.12](2.2.2.12-thanh-lap-nhom.md) | [#8](../08-nhom-loi-moi-yeu-cau-tham-gia.md) | `TC-STU` |
| 2.2.2.13 | Mời thành viên và phản hồi lời mời | Trưởng nhóm, Sinh viên | [2.2.2.13](2.2.2.13-moi-thanh-vien-va-phan-hoi-loi-moi.md) | [#8](../08-nhom-loi-moi-yeu-cau-tham-gia.md) | `TC-STU` |
| 2.2.2.14 | Yêu cầu tham gia nhóm | Sinh viên, Trưởng nhóm | [2.2.2.14](2.2.2.14-yeu-cau-tham-gia-nhom.md) | [#8](../08-nhom-loi-moi-yeu-cau-tham-gia.md) | `TC-STU` |
| 2.2.2.15 | Chat 1-1 | Mọi vai trò | [2.2.2.15](2.2.2.15-chat-1-1.md) | [#9](../09-chat-1-1.md), [#11](../11-chan-nguoi-dung.md) | `TC-CHAT` |
| 2.2.2.16 | Chat nhóm | Thành viên nhóm, Admin | [2.2.2.16](2.2.2.16-chat-nhom.md) | [#10](../10-chat-nhom.md) | `TC-CHAT` |
| 2.2.2.17 | Kiểm duyệt nội dung chat | Hệ thống, Admin | [2.2.2.17](2.2.2.17-kiem-duyet-noi-dung-chat.md) | [#13](../13-kiem-duyet-noi-dung.md), [#14](../14-admin-giam-sat-chat.md) | `TC-MOD` |
| 2.2.2.18 | Gợi ý đồ án theo ngữ nghĩa | Sinh viên, Giảng viên, Admin | [2.2.2.18](2.2.2.18-goi-y-do-an-theo-ngu-nghia.md) | [#18](../18-goi-y-de-tai-ngu-nghia.md) | `TC-REC` |
| 2.2.2.19 | Bảng tin lớp học | Giảng viên, Sinh viên, Admin | [2.2.2.19](2.2.2.19-bang-tin-lop-hoc.md) | [#19](../19-bang-tin-lop-hoc.md) | `TC-STREAM` |
| 2.2.2.20 | Thống kê hệ thống | Admin | [2.2.2.20](2.2.2.20-thong-ke-he-thong.md) | [#16](../16-thong-ke-he-thong.md), [#17](../17-bieu-do-dashboard-admin.md) | `TC-STAT` |
| 2.2.2.21 | Quản lý nhóm (dành cho giảng viên) | Giảng viên | [2.2.2.21](2.2.2.21-quan-ly-nhom-danh-cho-giang-vien.md) | [#8](../08-nhom-loi-moi-yeu-cau-tham-gia.md) | `TC-LECT` |

## 2. Quy ước trình bày

### a. Bằng văn bản

- Mở đầu bằng **một câu** theo mẫu: *"Use case bắt đầu khi \<tác nhân\> cần \<thêm mới / cập nhật / kiểm tra\> \<đối tượng nghiệp vụ\> để \<mục đích\>."*
- **Các dòng cơ bản**: 7–9 bước theo **luồng thao tác trên màn hình**: mở danh sách → chọn chức năng → chọn đối tượng → xem chi tiết → chọn sửa → sửa → lưu → kiểm tra lại.
- **Các dòng thay thế**: mỗi nhánh bắt đầu bằng *"Tại bước \<k\>: Nếu … thì …"* và dùng **gạch đầu dòng con** cho các thao tác; luôn nêu rõ điểm quay lại (*quay lại bước …*, *bỏ qua bước …*, *tiếp tục bước …*, *kết thúc use case*).
- Với chức năng có thêm/xoá/import, nhánh thay thế được tách riêng tại bước "chọn chức năng" hoặc bước "xem chi tiết" (giống mẫu "Quản lý phòng chiếu").

### b. Sơ đồ hoạt động

- Sơ đồ UML activity dạng **Mermaid** (`flowchart TD`): nút tròn đặc `((●))` = bắt đầu, nút bo tròn `[ ]` = hành động, hình thoi `{ }` = quyết định, nút tròn đôi `((◉))` = kết thúc.
- Sơ đồ **khớp 1-1 với "Các dòng cơ bản"** và có đủ các nhánh của "Các dòng thay thế".
- Xem trước bằng **Markdown Preview** của VS Code; muốn chèn vào Word thì chụp ảnh sơ đồ (hoặc cài `@mermaid-js/mermaid-cli` để xuất PNG/SVG).
- **Số mục** theo mục lục báo cáo; đổi số chỉ cần sửa tên file + tiêu đề (nội dung không phụ thuộc số).

### Ghi chú

- Mỗi file kết thúc bằng dòng `Đối chiếu code` (route name · controller/service · bảng dữ liệu) để tra ngược về mã nguồn.
- Các **bảng quy tắc nghiệp vụ (`BR-x`), bảng dữ liệu phát sinh, danh sách câu thông báo** không nằm trong file báo cáo — xem tài liệu kỹ thuật [`docs/business-flows/0x-*.md`](../README.md) tương ứng ở cột "Tài liệu kỹ thuật".

## 3. Ghi chú nghiệp vụ quan trọng (áp dụng cho nhiều quy trình)

- Hệ thống có **4 vai trò**: `student`, `lecturer`, `admin`; "trưởng nhóm" suy ra từ `groups.leader_id`.
- **Không có đăng ký tài khoản công khai** — tài khoản do Admin tạo/import (xem 2.2.2.3).
- **1 sinh viên / 1 lớp học phần = 1 nhóm duy nhất**; nhóm làm cả 2 bài (giữa kì + cuối kì) nếu môn có 2 bài.
- **Kiểm duyệt nội dung chỉ gắn cờ**, không chặn gửi và không xóa tin.
- Mọi tác vụ phụ trợ (broadcast realtime, service AI, ghi bảng tin) đều **fail-open**: lỗi thì bỏ qua, nghiệp vụ chính vẫn thành công.

<sub>Tài liệu viết tay · tạo: 2026-09-24 · viết lại theo mẫu "luồng thao tác nghiệp vụ": 2026-09-24 · đối chiếu `routes/web.php`, controller/service trong `app/`, và `php artisan route:list`</sub>

