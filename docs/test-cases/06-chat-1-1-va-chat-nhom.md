# Nhóm 06 — Chat 1-1 & chat nhóm

> **Mã nhóm**: `TC-CHAT` · **Chức năng**: FEATURE_STATUS #9 (chat 1-1 + ảnh + badge đã đọc + Reverb), #10 (chat nhóm), #11 (chặn người dùng 2 chiều)
> **Số test case**: 10 — Pass: **6** · Fail: **0** · Chưa chạy tay: **4**
> **Môi trường**: MySQL team_assign_test; cần 2 tài khoản (người gửi + người nhận) khi kiểm tra tay; BROADCAST_CONNECTION=null khi chạy test ⇒ nhánh realtime phải kiểm tra tay với `php artisan reverb:start`.
> ↻ File này **sinh tự động** từ `docs/test-cases/data/06-chat-1-1-va-chat-nhom.php` — sửa dữ liệu ở đó rồi chạy `php artisan testcases:export` (đừng sửa file .md này).

## 1. Mục tiêu & phạm vi

Kiểm thử gửi/nhận tin nhắn 1-1 và trong nhóm, đính kèm ảnh, badge chưa đọc theo từng hội thoại và badge tổng trên sidebar, sắp xếp danh sách hội thoại, chặn/bỏ chặn người dùng, và cơ chế polling dự phòng cho khung chat nhóm khi Reverb không chạy.

## 2. Bảng tóm tắt test case

| Mã TC | Tên / Mục tiêu | Role | Loại | Ưu tiên | Trạng thái |
|---|---|---|---|---|---|
| `TC-CHAT-01` | Gửi tin nhắn 1-1 thành công và lưu đúng người nhận | Sinh viên | API | Cao | Pass |
| `TC-CHAT-02` | Badge 1-1 hiện theo từng người gửi và badge tổng bằng tổng các hội thoại | Sinh viên | DB | Cao | Pass |
| `TC-CHAT-03` | Danh sách hội thoại: chưa đọc lên đầu, tiếp theo là lịch sử trò chuyện, cuối cùng theo tên | Sinh viên | UI | Trung bình | Pass |
| `TC-CHAT-04` | Mở hội thoại hoặc AJAX markRead: badge riêng về 0 và badge tổng cập nhật theo số còn lại | Sinh viên | API | Cao | Pass |
| `TC-CHAT-05` | Gửi tin nhắn nhóm và badge đếm riêng theo từng nhóm | Sinh viên | API | Cao | Pass |
| `TC-CHAT-06` | Polling chỉ trả tin MỚI HƠN mốc after và kèm last_id; người ngoài nhóm bị chặn | Sinh viên | API | Trung bình | Pass |
| `TC-CHAT-07` | Chặn người dùng: hai bên không gửi được tin cho nhau; bỏ chặn thì gửi lại được | Sinh viên | API | Trung bình | Chưa chạy tay |
| `TC-CHAT-08` | Gửi tin nhắn kèm ảnh trong chat 1-1 và chat nhóm | Sinh viên | UI | Trung bình | Chưa chạy tay |
| `TC-CHAT-09` | Người ngoài nhóm và khách không mở/gửi được khung chat nhóm (case âm) | Sinh viên | API | Cao | Chưa chạy tay |
| `TC-CHAT-10` | Admin xem được mọi khung chat nhưng không bị cộng badge chưa đọc | Admin | UI | Thấp | Chưa chạy tay |

## 3. Chi tiết test case

### TC-CHAT-01 — Gửi tin nhắn 1-1 thành công và lưu đúng người nhận

- **Chức năng**: Chat 1-1 (#9) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập sv1@test.com; sv2@test.com là người nhận hợp lệ
- **Các bước thực hiện**:
  1. Mở /chat
  2. Chọn hội thoại với sv2
  3. Nhập nội dung
  4. Bấm gửi
- **Dữ liệu đầu vào**: POST /chat/{userId} content=Xin chào nhóm
- **Kết quả mong đợi**: HTTP 200 + JSON tin nhắn vừa gửi; tin xuất hiện trong khung chat của cả hai bên
- **Kiểm tra thêm (DB / log / API)**: direct_messages có dòng mới (sender_id=sv1, recipient_id=sv2, is_read=0)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ChatUnreadBadgeTest.php`

### TC-CHAT-02 — Badge 1-1 hiện theo từng người gửi và badge tổng bằng tổng các hội thoại

- **Chức năng**: Badge chưa đọc (#9) · **Role**: Sinh viên · **Loại**: DB · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv1 nhận 2 tin từ A và 1 tin từ B
- **Các bước thực hiện**:
  1. Đăng nhập sv1
  2. Mở /chat
  3. Đọc badge cạnh từng hội thoại và badge trên sidebar
- **Dữ liệu đầu vào**: 3 tin chưa đọc từ 2 người gửi khác nhau
- **Kết quả mong đợi**: Badge của A = 2, của B = 1; badge tổng trên sidebar = 3
- **Kiểm tra thêm (DB / log / API)**: users.unread_message_count = 3 · trang có `data-chat-badge-user-id="{id}"` cho từng người gửi
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ChatUnreadBadgeTest.php`

### TC-CHAT-03 — Danh sách hội thoại: chưa đọc lên đầu, tiếp theo là lịch sử trò chuyện, cuối cùng theo tên

- **Chức năng**: Badge chưa đọc (#9) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: sv1 có 1 hội thoại có lịch sử (không chưa đọc), 1 hội thoại chưa đọc, 2 người chưa từng chat
- **Các bước thực hiện**:
  1. Mở /chat
  2. Quan sát thứ tự các hội thoại
- **Dữ liệu đầu vào**: URL /chat
- **Kết quả mong đợi**: Thứ tự hiển thị: người đang có tin chưa đọc → người có lịch sử trò chuyện → 2 người chưa từng chat theo tên A→B
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ChatUnreadBadgeTest.php`

### TC-CHAT-04 — Mở hội thoại hoặc AJAX markRead: badge riêng về 0 và badge tổng cập nhật theo số còn lại

- **Chức năng**: Đánh dấu đã đọc (#9) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv1 đang có 3 tin chưa đọc: 1 từ A, 2 từ B
- **Các bước thực hiện**:
  1. Gọi POST /chat/{A}/read (AJAX)
  2. Kiểm tra badge tổng còn 2
  3. Mở /chat/{B}
  4. Kiểm tra badge tổng = 0
- **Dữ liệu đầu vào**: POST /chat/{userId}/read
- **Kết quả mong đợi**: JSON trả `total` = 2 rồi 0; badge của A biến mất khỏi danh sách chưa đọc
- **Kiểm tra thêm (DB / log / API)**: direct_messages.is_read = 1 (và seen_at được ghi) cho các tin của A · users.unread_message_count giảm đúng
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ChatUnreadBadgeTest.php`

### TC-CHAT-05 — Gửi tin nhắn nhóm và badge đếm riêng theo từng nhóm

- **Chức năng**: Chat nhóm (#10) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv3 là thành viên của 2 nhóm (Nhóm 1, Nhóm 2); sv2 là trưởng nhóm
- **Các bước thực hiện**:
  1. sv2 gửi 1 tin vào Nhóm 1 và 2 tin vào Nhóm 2
  2. sv3 mở /chat?mode=group
  3. Kiểm tra badge từng nhóm
  4. Mở Nhóm 1 rồi kiểm tra badge nhóm 2
- **Dữ liệu đầu vào**: POST /groups/{groupId}/chat/send
- **Kết quả mong đợi**: Badge Nhóm 1 = 1, Nhóm 2 = 2, badge tổng = 3; mở Nhóm 1 ⇒ chỉ Nhóm 1 về 0 (tổng còn 2)
- **Kiểm tra thêm (DB / log / API)**: chat_messages có 3 dòng · group_chat_reads.last_read_at của Nhóm 1 được cập nhật
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ChatUnreadBadgeTest.php`

### TC-CHAT-06 — Polling chỉ trả tin MỚI HƠN mốc after và kèm last_id; người ngoài nhóm bị chặn

- **Chức năng**: Polling dự phòng (#10) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Nhóm có 2 tin; Reverb không chạy
- **Các bước thực hiện**:
  1. Gọi GET /groups/{id}/chat/messages (không after)
  2. Gọi lại với ?after={id tin 1}
  3. Gọi lại với ?after={id tin 2}
  4. Thử với người ngoài nhóm và khách chưa đăng nhập
- **Dữ liệu đầu vào**: GET /groups/{groupId}/chat/messages?after={id}
- **Kết quả mong đợi**: Lần 1 trả 2 tin + last_id = id tin cuối; lần 2 chỉ trả tin thứ 2; lần 3 trả data rỗng giữ nguyên last_id; người ngoài ⇒ 403; khách ⇒ chuyển hướng /login
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/GroupChatPollingTest.php`

### TC-CHAT-07 — Chặn người dùng: hai bên không gửi được tin cho nhau; bỏ chặn thì gửi lại được

- **Chức năng**: Chặn người dùng (#11) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập sv1; sv2 là người dùng bị chặn
- **Các bước thực hiện**:
  1. Gọi POST /block-user với id sv2
  2. sv1 gửi tin cho sv2
  3. sv2 gửi tin cho sv1
  4. Gọi POST /unblock-user và gửi lại
- **Dữ liệu đầu vào**: POST /block-user · POST /unblock-user · POST /block-user/check
- **Kết quả mong đợi**: Sau khi chặn: cả hai chiều bị chặn gửi kèm thông báo; `check` trả trạng thái đã chặn; sau khi bỏ chặn tin gửi bình thường
- **Kiểm tra thêm (DB / log / API)**: blocked_users có 1 dòng (blocker_id, blocked_id) rồi bị xóa khi bỏ chặn
- **Kết quả thực tế**: Chưa chạy tay
- **Trạng thái**: **Chưa chạy tay**
- **Test tự động**: (thủ công — chạy trên trình duyệt)
- **Ghi chú**: Chưa có Pest test riêng cho `BlockUserController` — cần chạy tay 2 tài khoản (đề xuất: bổ sung tests/Feature/BlockUserTest.php).

### TC-CHAT-08 — Gửi tin nhắn kèm ảnh trong chat 1-1 và chat nhóm

- **Chức năng**: Gửi ảnh đính kèm (#9, #10) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập 2 tài khoản; chuẩn bị file ảnh .png < 5MB
- **Các bước thực hiện**:
  1. Mở hội thoại 1-1, bấm đính kèm và chọn ảnh
  2. Gửi
  3. Làm tương tự trong khung chat nhóm
  4. Kiểm tra bên nhận xem được ảnh
- **Dữ liệu đầu vào**: POST /chat/{user} · POST /groups/{id}/chat/send (kèm file)
- **Kết quả mong đợi**: Ảnh được upload và hiển thị dạng xem trước ở cả hai bên; file lưu đúng thư mục storage
- **Kiểm tra thêm (DB / log / API)**: direct_messages/chat_messages có cột `attachment` chứa đường dẫn file
- **Kết quả thực tế**: Chưa chạy tay
- **Trạng thái**: **Chưa chạy tay**
- **Test tự động**: (thủ công — chạy trên trình duyệt)
- **Ghi chú**: Chưa có Pest test cho nhánh upload ảnh (đã đánh dấu trong FEATURE_STATUS: chỉ kiểm tra bằng UI/AJAX).

### TC-CHAT-09 — Người ngoài nhóm và khách không mở/gửi được khung chat nhóm (case âm)

- **Chức năng**: Phân quyền chat nhóm (#10) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: sv3 không thuộc nhóm Alpha; 1 phiên khách chưa đăng nhập
- **Các bước thực hiện**:
  1. sv3 mở /groups/{id}/chat
  2. sv3 gọi POST /groups/{id}/chat/send
  3. Khách mở cùng URL
- **Dữ liệu đầu vào**: URL /groups/{groupId}/chat*
- **Kết quả mong đợi**: sv3 bị 403; khách bị chuyển hướng /login; không đọc được nội dung tin nhắn nhóm
- **Kết quả thực tế**: Chưa chạy tay
- **Trạng thái**: **Chưa chạy tay**
- **Test tự động**: (thủ công — chạy trên trình duyệt)
- **Ghi chú**: Polling đã có test (`GroupChatPollingTest`); nhánh showChat/send cần bổ sung test hoặc kiểm tra tay.

### TC-CHAT-10 — Admin xem được mọi khung chat nhưng không bị cộng badge chưa đọc

- **Chức năng**: Phân quyền chat (#9, #10) · **Role**: Admin · **Loại**: UI · **Ưu tiên**: Thấp
- **Tiền điều kiện**: Đăng nhập admin; có nhiều hội thoại 1-1 và nhóm đang có tin mới
- **Các bước thực hiện**:
  1. Mở /chat
  2. Mở /chat/{user} của người khác
  3. Kiểm tra badge tổng trên sidebar
- **Dữ liệu đầu vào**: URL /chat*
- **Kết quả mong đợi**: Admin truy cập được (phục vụ giám sát) nhưng badge của admin không tăng; dữ liệu `is_read` của người dùng khác không bị đổi
- **Kết quả thực tế**: Chưa chạy tay
- **Trạng thái**: **Chưa chạy tay**
- **Test tự động**: (thủ công — chạy trên trình duyệt)

## 4. Cách chạy nhóm test này

```powershell
cd G:\MyApp\laragon\www\Team-Assign
php artisan test tests/Feature/ChatUnreadBadgeTest.php tests/Feature/GroupChatPollingTest.php tests/Feature/ViewSmokeTest.php
```

## 5. Ghi chú & rủi ro

- Badge 1-1 đếm theo NGƯỜI GỬI (`direct_messages.is_read`) và badge nhóm theo mốc `group_chat_reads.last_read_at` — hai loại badge cộng lại thành badge tổng.
- Mở khung chat = đã đọc (hướng B cho chat nhóm): mở `groups.chat.show` hoặc AJAX `groups.chat.read` ⇒ badge nhóm về 0; tương tự `chat.show` / `chat.read` cho chat 1-1.
- Danh sách hội thoại được sắp: hội thoại CÒN TIN CHƯA ĐỌC lên đầu → theo lịch sử trò chuyện → còn lại theo tên (test khẳng định bằng thứ tự DOM).
- Polling dự phòng `GET /groups/{groupId}/chat/messages?after={id}` trả JSON kèm `last_id`, dùng khi WebSocket lỗi; người ngoài nhóm bị 403, khách chưa đăng nhập bị đưa về /login.
- Chặn người dùng là quan hệ 2 CHIỀU khi nhắn tin (`blocked_users`): một trong hai bên chặn ⇒ cả hai không gửi được cho nhau; bỏ chặn thì gửi lại được.
- Case có nhãn `Chưa chạy tay` cần tự chạy trên trình duyệt (2 tài khoản nếu cần realtime) rồi đổi trạng thái trong `data/06-chat-1-1-va-chat-nhom.php` và export lại.

<sub>Sinh tự động bởi `php artisan testcases:export` · nguồn: `docs/test-cases/data/06-chat-1-1-va-chat-nhom.php`</sub>
