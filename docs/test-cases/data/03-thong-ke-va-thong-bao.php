<?php

/**
 * Test case — Nhóm 03: Thống kê & thông báo (mã TC-STAT)
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
    'code_prefix' => 'TC-STAT',
    'sheet' => '03-STAT',
    'title' => 'Nhóm 03 — Thống kê & thông báo',
    'features' => 'FEATURE_STATUS #16 (thống kê hệ thống 5 trang), #17 (biểu đồ dashboard admin), #12 (thông báo realtime + badge tổng + badge đã xem)',
    'summary' => 'Kiểm thử 5 trang thống kê của Admin (tổng quan, đề tài, nhóm, yêu cầu, người dùng) và toàn bộ hệ thống thông báo: admin gửi thông báo hệ thống cho giảng viên, chuông thông báo của người dùng, badge chưa đọc, đánh dấu đã đọc/đã xem, xóa thông báo.',
    'env' => 'MySQL team_assign_test; đăng nhập admin@test.com cho phần thống kê; BROADCAST_CONNECTION=null khi chạy test (Realtime phải kiểm tra tay bằng reverb:start).',
    'run' => 'php artisan test tests/Feature/Admin/StatisticsTest.php tests/Feature/AdminNotificationTest.php tests/Feature/JoinRequestNotificationTest.php tests/Feature/BadgeSeenTest.php',
    'notes' => [
        'Thống kê dùng ĐÚNG schema hiện tại: trưởng nhóm suy ra từ `groups.leader_id` (không dùng `users.role = leader` — đã xóa ở migration 2026_09_13), không dùng `users.is_have_group`, và status yêu cầu là `Pending / Accepted / Rejected` (KHÔNG có `Approved`).',
        'Badge "Chat" trên sidebar lấy từ `ChatUnreadService::totalFor()` (tính thật), KHÔNG tin cột cache `users.unread_message_count`.',
        'Badge "Yêu cầu"/"Lời mời" đếm trực tiếp từ bảng `join_requests`/`invites`: bấm vào mục ⇒ ghi mốc `*_seen_at` ⇒ badge về 0 tới khi có bản ghi MỚI hơn mốc.',
        'Badge "Thông báo" (`users.unread_notifications`) tăng khi có thông báo mới và về 0 sau `POST /notifications/mark-all-read`.',
        'FEATURE_STATUS #17 ghi rõ: trang Thống kê hiện là bảng + progress bar, CHƯA có Chart.js (đề xuất nâng cấp ở `docs/diagrams/admin-charts.md`) ⇒ các case biểu đồ đánh dấu Chưa chạy tay.',
    ],
    'cases' => [
        [
            'role' => 'Admin', 'feature' => 'Thống kê tổng quan (#16)', 'type' => 'UI', 'prio' => 'Cao',
            'goal' => 'Admin xem trang tổng quan thống kê với các số liệu tổng hợp',
            'pre' => 'Đăng nhập admin; DB có giảng viên, lớp, nhóm, đề tài mẫu',
            'steps' => ['Mở /admin/statistics', 'Đối chiếu số liệu với DB'],
            'input' => 'URL /admin/statistics',
            'expect' => 'Trang render 200; hiện tiêu đề “Thống kê hệ thống”, khối “Người dùng” và “Yêu cầu đang chờ duyệt”',
            'auto' => 'tests/Feature/Admin/StatisticsTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Admin', 'feature' => 'Thống kê đề tài (#16)', 'type' => 'UI', 'prio' => 'Cao',
            'goal' => 'Trang thống kê đề tài hiển thị đúng số còn trống và số đã có nhóm đăng ký',
            'pre' => 'Có 2 đề tài: 1 chưa gán nhóm, 1 đã gán (groups.topic_id + topics.assigned_group_id)',
            'steps' => ['Mở /admin/statistics/topics', 'Đối chiếu “Tổng số đề tài” / “Còn trống” / “Đã có nhóm đăng ký”', 'Xem bảng “Theo giảng viên”'],
            'input' => 'URL /admin/statistics/topics',
            'expect' => 'Tổng = 2 · Còn trống = 1 · Đã có nhóm = 1; có bảng thống kê theo giảng viên',
            'db' => 'Đếm theo `topics.assigned_group_id` (KHÔNG tự so `topics.topic_id`)',
            'auto' => 'tests/Feature/Admin/StatisticsTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Admin', 'feature' => 'Thống kê nhóm (#16)', 'type' => 'UI', 'prio' => 'Trung bình',
            'goal' => 'Trang thống kê nhóm tính đúng số thành viên trung bình mỗi nhóm',
            'pre' => 'Có 1 nhóm với 1 trưởng nhóm; các nhóm khác chưa gán đề tài',
            'steps' => ['Mở /admin/statistics/groups', 'Kiểm tra các chỉ số'],
            'input' => 'URL /admin/statistics/groups',
            'expect' => 'Hiện “Thống kê nhóm”, “Chưa có đề tài” và “Số thành viên trung bình mỗi nhóm”',
            'db' => 'Số thành viên đếm qua bảng `group_members` (đã gồm trưởng nhóm)',
            'auto' => 'tests/Feature/Admin/StatisticsTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Admin', 'feature' => 'Thông báo hệ thống (#12)', 'type' => 'UI', 'prio' => 'Cao',
            'goal' => 'Admin gửi thông báo hệ thống đến TẤT CẢ giảng viên',
            'pre' => 'Đăng nhập admin; có ít nhất 2 tài khoản giảng viên',
            'steps' => ['Mở /admin/notifications/create', 'Chọn phạm vi “tất cả giảng viên”, nhập tiêu đề + nội dung', 'Bấm gửi'],
            'input' => 'title=Kế hoạch học kỳ, content=..., scope=all',
            'expect' => 'Gửi thành công; mọi giảng viên nhận 1 thông báo và badge chưa đọc tăng',
            'db' => 'notifications có 1 dòng/giảng viên · users.unread_notifications tăng tương ứng',
            'auto' => 'tests/Feature/AdminNotificationTest.php + tests/Feature/ViewSmokeTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Admin', 'feature' => 'Thông báo hệ thống (#12)', 'type' => 'UI', 'prio' => 'Trung bình',
            'goal' => 'Gửi thông báo tới danh sách giảng viên được chọn',
            'pre' => 'Đăng nhập admin; có 2 giảng viên, chọn đúng 1 người',
            'steps' => ['Mở form gửi thông báo', 'Tick chọn 1 giảng viên', 'Bấm gửi'],
            'input' => 'recipients=[gv1]',
            'expect' => 'Chỉ giảng viên được chọn nhận thông báo; giảng viên khác không thấy',
            'db' => 'notifications chỉ có dòng cho user_id của gv1',
            'auto' => 'tests/Feature/AdminNotificationTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Giảng viên', 'feature' => 'Chuông thông báo (#12)', 'type' => 'UI', 'prio' => 'Cao',
            'goal' => 'Nav-bar hiển thị badge thông báo và badge yêu cầu tính sẵn từ server',
            'pre' => 'Giảng viên đang có 1 thông báo chưa đọc + 1 yêu cầu đăng ký đang chờ',
            'steps' => ['Mở bất kỳ trang có nav-bar', 'Quan sát badge trên nav-bar'],
            'input' => 'GET /dashboard/lecturer',
            'expect' => 'Badge thông báo và badge yêu cầu hiển thị đúng số tính từ DB (không phải JS tự đoán)',
            'auto' => 'tests/Feature/JoinRequestNotificationTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Giảng viên', 'feature' => 'Đánh dấu đã đọc (#12)', 'type' => 'UI', 'prio' => 'Trung bình',
            'goal' => 'Đánh dấu tất cả đã đọc đưa badge thông báo về 0',
            'pre' => 'Đang có 2 thông báo chưa đọc',
            'steps' => ['Mở /notifications', 'Bấm “Đánh dấu tất cả đã đọc”', 'Quan sát badge'],
            'input' => 'POST /notifications/mark-all-read',
            'expect' => 'Danh sách chuyển trạng thái đã đọc; badge về 0',
            'db' => 'users.unread_notifications = 0',
            'auto' => 'tests/Feature/JoinRequestNotificationTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Sinh viên', 'feature' => 'Badge đã xem (#12)', 'type' => 'DB', 'prio' => 'Trung bình',
            'goal' => 'Bấm mục Yêu cầu / Lời mời: badge về 0 và chỉ hiện lại khi có bản ghi mới',
            'pre' => 'Sinh viên đang có yêu cầu/lời mời chưa xem',
            'steps' => ['Mở mục “Yêu cầu” (POST /user/join_requests/seen)', 'Kiểm tra badge = 0', 'Tạo thêm 1 yêu cầu mới', 'Kiểm tra badge = 1'],
            'input' => 'POST /user/join_requests/seen · POST /user/invites/seen',
            'expect' => 'Badge về 0 sau khi xem; chỉ tăng lại với bản ghi có thời điểm MỚI HƠN mốc `*_seen_at`',
            'db' => 'users.join_requests_seen_at / users.invites_seen_at được cập nhật',
            'auto' => 'tests/Feature/BadgeSeenTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Khách', 'feature' => 'Badge đã xem (#12)', 'type' => 'API', 'prio' => 'Cao',
            'goal' => 'Khách chưa đăng nhập không gọi được route đánh dấu đã xem (case âm)',
            'pre' => 'Không đăng nhập',
            'steps' => ['Gọi POST /user/join_requests/seen', 'Gọi POST /user/invites/seen'],
            'input' => '2 request không session',
            'expect' => 'Chuyển hướng /login (302); không thay đổi mốc `*_seen_at`',
            'auto' => 'tests/Feature/BadgeSeenTest.php',
            'status' => 'Pass',
        ],
    ],
];
