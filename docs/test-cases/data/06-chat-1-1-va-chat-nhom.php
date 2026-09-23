<?php

/**
 * Test case — Nhóm 06: Chat 1-1 & chat nhóm (mã TC-CHAT)
 *
 * Trường mỗi case (giống mọi nhóm khác):
 *   role · feature · type (UI/API/DB/Realtime) · prio (Cao/Trung bình/Thấp)
 *   goal (mục tiêu) · pre (tiền điều kiện) · steps[] · input · expect
 *   db (kiểm tra thêm ở DB/log — tuỳ chọn) · auto (file test tự động, '' = thủ công)
 *   status (Pass | Fail | Chưa chạy tay) · actual (bỏ trống ⇒ tự sinh theo status) · note
 *
 * Sửa file này rồi chạy: php artisan testcases:export
 */

return [
    'code_prefix' => 'TC-CHAT',
    'sheet' => '06-CHAT',
    'title' => 'Nhóm 06 — Chat 1-1 & chat nhóm',
    'features' => 'FEATURE_STATUS #9 (chat 1-1 + ảnh + badge đã đọc + Reverb), #10 (chat nhóm), #11 (chặn người dùng 2 chiều)',
    'summary' => 'Kiểm thử gửi/nhận tin nhắn 1-1 và trong nhóm, đính kèm ảnh, badge chưa đọc theo từng hội thoại và badge tổng trên sidebar, sắp xếp danh sách hội thoại, chặn/bỏ chặn người dùng, và cơ chế polling dự phòng cho khung chat nhóm khi Reverb không chạy.',
    'env' => 'MySQL team_assign_test; cần 2 tài khoản (người gửi + người nhận) khi kiểm tra tay; BROADCAST_CONNECTION=null khi chạy test ⇒ nhánh realtime phải kiểm tra tay với `php artisan reverb:start`.',
    'run' => 'php artisan test tests/Feature/ChatUnreadBadgeTest.php tests/Feature/GroupChatPollingTest.php tests/Feature/ViewSmokeTest.php',
    'notes' => [
        'Badge 1-1 đếm theo NGƯỜI GỬI (`direct_messages.is_read`) và badge nhóm theo mốc `group_chat_reads.last_read_at` — hai loại badge cộng lại thành badge tổng.',
        'Mở khung chat = đã đọc (hướng B cho chat nhóm): mở `groups.chat.show` hoặc AJAX `groups.chat.read` ⇒ badge nhóm về 0; tương tự `chat.show` / `chat.read` cho chat 1-1.',
        'Danh sách hội thoại được sắp: hội thoại CÒN TIN CHƯA ĐỌC lên đầu → theo lịch sử trò chuyện → còn lại theo tên (test khẳng định bằng thứ tự DOM).',
        'Polling dự phòng `GET /groups/{groupId}/chat/messages?after={id}` trả JSON kèm `last_id`, dùng khi WebSocket lỗi; người ngoài nhóm bị 403, khách chưa đăng nhập bị đưa về /login.',
        'Chặn người dùng là quan hệ 2 CHIỀU khi nhắn tin (`blocked_users`): một trong hai bên chặn ⇒ cả hai không gửi được cho nhau; bỏ chặn thì gửi lại được.',
    ],
    'cases' => [
        [
            'role' => 'Sinh viên', 'feature' => 'Chat 1-1 (#9)', 'type' => 'API', 'prio' => 'Cao',
            'goal' => 'Gửi tin nhắn 1-1 thành công và lưu đúng người nhận',
            'pre' => 'Đăng nhập sv1@test.com; sv2@test.com là người nhận hợp lệ',
            'steps' => ['Mở /chat', 'Chọn hội thoại với sv2', 'Nhập nội dung', 'Bấm gửi'],
            'input' => 'POST /chat/{userId} content=Xin chào nhóm',
            'expect' => 'HTTP 200 + JSON tin nhắn vừa gửi; tin xuất hiện trong khung chat của cả hai bên',
            'db' => 'direct_messages có dòng mới (sender_id=sv1, recipient_id=sv2, is_read=0)',
            'auto' => 'tests/Feature/ChatUnreadBadgeTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Sinh viên', 'feature' => 'Badge chưa đọc (#9)', 'type' => 'DB', 'prio' => 'Cao',
            'goal' => 'Badge 1-1 hiện theo từng người gửi và badge tổng bằng tổng các hội thoại',
            'pre' => 'sv1 nhận 2 tin từ A và 1 tin từ B',
            'steps' => ['Đăng nhập sv1', 'Mở /chat', 'Đọc badge cạnh từng hội thoại và badge trên sidebar'],
            'input' => '3 tin chưa đọc từ 2 người gửi khác nhau',
            'expect' => 'Badge của A = 2, của B = 1; badge tổng trên sidebar = 3',
            'db' => 'users.unread_message_count = 3 · trang có `data-chat-badge-user-id="{id}"` cho từng người gửi',
            'auto' => 'tests/Feature/ChatUnreadBadgeTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Sinh viên', 'feature' => 'Badge chưa đọc (#9)', 'type' => 'UI', 'prio' => 'Trung bình',
            'goal' => 'Danh sách hội thoại: chưa đọc lên đầu, tiếp theo là lịch sử trò chuyện, cuối cùng theo tên',
            'pre' => 'sv1 có 1 hội thoại có lịch sử (không chưa đọc), 1 hội thoại chưa đọc, 2 người chưa từng chat',
            'steps' => ['Mở /chat', 'Quan sát thứ tự các hội thoại'],
            'input' => 'URL /chat',
            'expect' => 'Thứ tự hiển thị: người đang có tin chưa đọc → người có lịch sử trò chuyện → 2 người chưa từng chat theo tên A→B',
            'auto' => 'tests/Feature/ChatUnreadBadgeTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Sinh viên', 'feature' => 'Đánh dấu đã đọc (#9)', 'type' => 'API', 'prio' => 'Cao',
            'goal' => 'Mở hội thoại hoặc AJAX markRead: badge riêng về 0 và badge tổng cập nhật theo số còn lại',
            'pre' => 'sv1 đang có 3 tin chưa đọc: 1 từ A, 2 từ B',
            'steps' => ['Gọi POST /chat/{A}/read (AJAX)', 'Kiểm tra badge tổng còn 2', 'Mở /chat/{B}', 'Kiểm tra badge tổng = 0'],
            'input' => 'POST /chat/{userId}/read',
            'expect' => 'JSON trả `total` = 2 rồi 0; badge của A biến mất khỏi danh sách chưa đọc',
            'db' => 'direct_messages.is_read = 1 (và seen_at được ghi) cho các tin của A · users.unread_message_count giảm đúng',
            'auto' => 'tests/Feature/ChatUnreadBadgeTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Sinh viên', 'feature' => 'Chat nhóm (#10)', 'type' => 'API', 'prio' => 'Cao',
            'goal' => 'Gửi tin nhắn nhóm và badge đếm riêng theo từng nhóm',
            'pre' => 'sv3 là thành viên của 2 nhóm (Nhóm 1, Nhóm 2); sv2 là trưởng nhóm',
            'steps' => ['sv2 gửi 1 tin vào Nhóm 1 và 2 tin vào Nhóm 2', 'sv3 mở /chat?mode=group', 'Kiểm tra badge từng nhóm', 'Mở Nhóm 1 rồi kiểm tra badge nhóm 2'],
            'input' => 'POST /groups/{groupId}/chat/send',
            'expect' => 'Badge Nhóm 1 = 1, Nhóm 2 = 2, badge tổng = 3; mở Nhóm 1 ⇒ chỉ Nhóm 1 về 0 (tổng còn 2)',
            'db' => 'chat_messages có 3 dòng · group_chat_reads.last_read_at của Nhóm 1 được cập nhật',
            'auto' => 'tests/Feature/ChatUnreadBadgeTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Sinh viên', 'feature' => 'Polling dự phòng (#10)', 'type' => 'API', 'prio' => 'Trung bình',
            'goal' => 'Polling chỉ trả tin MỚI HƠN mốc after và kèm last_id; người ngoài nhóm bị chặn',
            'pre' => 'Nhóm có 2 tin; Reverb không chạy',
            'steps' => ['Gọi GET /groups/{id}/chat/messages (không after)', 'Gọi lại với ?after={id tin 1}', 'Gọi lại với ?after={id tin 2}', 'Thử với người ngoài nhóm và khách chưa đăng nhập'],
            'input' => 'GET /groups/{groupId}/chat/messages?after={id}',
            'expect' => 'Lần 1 trả 2 tin + last_id = id tin cuối; lần 2 chỉ trả tin thứ 2; lần 3 trả data rỗng giữ nguyên last_id; người ngoài ⇒ 403; khách ⇒ chuyển hướng /login',
            'auto' => 'tests/Feature/GroupChatPollingTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Sinh viên', 'feature' => 'Chặn người dùng (#11)', 'type' => 'API', 'prio' => 'Trung bình',
            'goal' => 'Chặn người dùng: hai bên không gửi được tin cho nhau; bỏ chặn thì gửi lại được',
            'pre' => 'Đăng nhập sv1; sv2 là người dùng bị chặn',
            'steps' => ['Gọi POST /block-user với id sv2', 'sv1 gửi tin cho sv2', 'sv2 gửi tin cho sv1', 'Gọi POST /unblock-user và gửi lại'],
            'input' => 'POST /block-user · POST /unblock-user · POST /block-user/check',
            'expect' => 'Sau khi chặn: cả hai chiều bị chặn gửi kèm thông báo; `check` trả trạng thái đã chặn; sau khi bỏ chặn tin gửi bình thường',
            'db' => 'blocked_users có 1 dòng (blocker_id, blocked_id) rồi bị xóa khi bỏ chặn',
            'status' => 'Chưa chạy tay',
            'note' => 'Chưa có Pest test riêng cho `BlockUserController` — cần chạy tay 2 tài khoản (đề xuất: bổ sung tests/Feature/BlockUserTest.php).',
        ],
        [
            'role' => 'Sinh viên', 'feature' => 'Gửi ảnh đính kèm (#9, #10)', 'type' => 'UI', 'prio' => 'Trung bình',
            'goal' => 'Gửi tin nhắn kèm ảnh trong chat 1-1 và chat nhóm',
            'pre' => 'Đăng nhập 2 tài khoản; chuẩn bị file ảnh .png < 5MB',
            'steps' => ['Mở hội thoại 1-1, bấm đính kèm và chọn ảnh', 'Gửi', 'Làm tương tự trong khung chat nhóm', 'Kiểm tra bên nhận xem được ảnh'],
            'input' => 'POST /chat/{user} · POST /groups/{id}/chat/send (kèm file)',
            'expect' => 'Ảnh được upload và hiển thị dạng xem trước ở cả hai bên; file lưu đúng thư mục storage',
            'db' => 'direct_messages/chat_messages có cột `attachment` chứa đường dẫn file',
            'status' => 'Chưa chạy tay',
            'note' => 'Chưa có Pest test cho nhánh upload ảnh (đã đánh dấu trong FEATURE_STATUS: chỉ kiểm tra bằng UI/AJAX).',
        ],
        [
            'role' => 'Sinh viên', 'feature' => 'Phân quyền chat nhóm (#10)', 'type' => 'API', 'prio' => 'Cao',
            'goal' => 'Người ngoài nhóm và khách không mở/gửi được khung chat nhóm (case âm)',
            'pre' => 'sv3 không thuộc nhóm Alpha; 1 phiên khách chưa đăng nhập',
            'steps' => ['sv3 mở /groups/{id}/chat', 'sv3 gọi POST /groups/{id}/chat/send', 'Khách mở cùng URL'],
            'input' => 'URL /groups/{groupId}/chat*',
            'expect' => 'sv3 bị 403; khách bị chuyển hướng /login; không đọc được nội dung tin nhắn nhóm',
            'status' => 'Chưa chạy tay',
            'note' => 'Polling đã có test (`GroupChatPollingTest`); nhánh showChat/send cần bổ sung test hoặc kiểm tra tay.',
        ],
        [
            'role' => 'Admin', 'feature' => 'Phân quyền chat (#9, #10)', 'type' => 'UI', 'prio' => 'Thấp',
            'goal' => 'Admin xem được mọi khung chat nhưng không bị cộng badge chưa đọc',
            'pre' => 'Đăng nhập admin; có nhiều hội thoại 1-1 và nhóm đang có tin mới',
            'steps' => ['Mở /chat', 'Mở /chat/{user} của người khác', 'Kiểm tra badge tổng trên sidebar'],
            'input' => 'URL /chat*',
            'expect' => 'Admin truy cập được (phục vụ giám sát) nhưng badge của admin không tăng; dữ liệu `is_read` của người dùng khác không bị đổi',
            'status' => 'Chưa chạy tay',
        ],
    ],
];
