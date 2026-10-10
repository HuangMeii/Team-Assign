<?php

/**
 * Test case — Nhóm 12: Hiệu năng & tải trang (mã TC-PERF)
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
    'code_prefix' => 'TC-PERF',
    'sheet' => '12-PERF',
    'title' => 'Nhóm 12 — Hiệu năng & tải trang (P1: D1→D5)',
    'features' => 'docs/Final-Bug-Fixes/P1-toi-uu-hieu-nang-giao-dien.md (không gắn số FEATURE_STATUS): D1 asset local thay CDN, D2 route/view/event cache + OPcache + CACHE_STORE=file, D3 chống N+1 danh sách, D4 broadcast sau response + MAIL_TIMEOUT',
    'summary' => 'Kiểm tra giao diện không còn phụ thuộc mạng ngoài (CDN), trang không bị chặn bởi broadcast tới Reverb hay SMTP chậm, route cache hoạt động đúng, và N+1 ở trang danh sách đã được gộp.',
    'env' => 'MySQL team_assign_test; npm đã chạy `npm ci && npm run build` (public/build là gitignored); máy dev đã bật OPcache ở php.ini Laragon và restart web server.',
    'run' => 'php artisan test tests/Feature/PerformanceBatchTest.php tests/Feature/Admin/AdminGroupMessageTest.php tests/Feature/Auth/RememberLoginTest.php',
    'notes' => [
        'D1: `vendor.css` (Bootstrap + FA) nạp trong <head> qua @vite; trang guest dùng `fa.css` RIÊNG vì dựng bằng Tailwind.',
        'Chống tái phát: test quét toàn bộ resources/views — thêm lại 1 link cdnjs/jsdelivr là test đỏ.',
        'D2: sửa routes/views phải chạy lại route:cache/view:cache; TUYỆT ĐỐI không bật config:cache (nuốt env phpunit).',
        'D4: broadcast bọc AfterResponse::broadcast() chạy ở phase terminating — assert được trong cùng request.',
        'Badge tin nhắn cố ý KHÔNG cache (giữ tươi 4 query indexed) — xem P1 mục D3.',
    ],
    'cases' => [
        [
            'role' => 'Hệ thống', 'feature' => 'D1 — asset local, không CDN', 'type' => 'UI', 'prio' => 'Cao',
            'goal' => 'Không trang nào còn request CSS/JS tới CDN bên thứ ba (cdnjs/jsdelivr)',
            'pre' => 'Đã npm run build',
            'steps' => ['Mở DevTools tab Network', 'Vào /login, /, /students', 'Lọc theo domain ngoài localhost'],
            'input' => 'Trang đăng nhập + trang sau đăng nhập',
            'expect' => 'Không request tới cdnjs.cloudflare.com / cdn.jsdelivr.net; CSS vendor tải từ chính origin',
            'auto' => 'tests/Feature/PerformanceBatchTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Hệ thống', 'feature' => 'D4 — broadcast sau response', 'type' => 'Realtime', 'prio' => 'Cao',
            'goal' => 'Gửi tin/bài/thông báo không phải chờ publish tới Reverb trước khi trả response',
            'pre' => 'Reverb bật hoặc tắt đều được (fail-open)',
            'steps' => ['Gửi tin chat 1-1', 'Gửi tin chat nhóm', 'Gửi bài bảng tin', 'Xác nhận client nhận được broadcast'],
            'input' => 'Bất kỳ nội dung chat/thông báo nào',
            'expect' => 'Response trả ngay; event vẫn dispatch trong phase terminating của cùng request; lỗi publish chỉ log warning',
            'auto' => 'tests/Feature/PerformanceBatchTest.php + tests/Feature/Admin/AdminGroupMessageTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Hệ thống', 'feature' => 'D2 — route cache + HomeController', 'type' => 'API', 'prio' => 'Trung bình',
            'goal' => '`/` redirect theo vai trò sau khi closure chuyển sang HomeController; routes đóng gói cache được',
            'pre' => 'Đã php artisan route:cache',
            'steps' => ['Khách bấm /', 'SV đăng nhập bấm /', 'GV bấm /', 'Admin bấm /'],
            'input' => '4 trường hợp vai trò',
            'expect' => 'Khách → /login; SV → user.dashboard; GV → dashboard; Admin → admin.users.index; không lỗi route closure',
            'auto' => 'tests/Feature/PerformanceBatchTest.php + tests/Feature/Auth/RememberLoginTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Hệ thống', 'feature' => 'D4 — SMTP timeout', 'type' => 'API', 'prio' => 'Trung bình',
            'goal' => 'Gửi mail không treo vô hạn khi Gmail chậm/throttle',
            'pre' => 'config/mail.php có MAIL_TIMEOUT (mặc định 10s)',
            'steps' => ['Đọc config mailers.smtp.timeout', 'Gửi email L06 với SMTP chậm (mô phỏng)'],
            'input' => 'MAIL_TIMEOUT=10',
            'expect' => 'timeout = 10 (không null); request gửi mail chờ tối đa 10s rồi trả lỗi',
            'auto' => 'tests/Feature/PerformanceBatchTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Tester', 'feature' => 'D1 — first paint không phụ thuộc mạng ngoài', 'type' => 'UI', 'prio' => 'Trung bình',
            'goal' => 'Trang đăng nhập load nhanh ngay cả khi mạng ngoài chập chờn',
            'pre' => 'Đã build asset; DevTools đang mở',
            'steps' => ['Hard reload /login (disable cache)', 'Xem tab Performance tới FCP', 'Tắt mạng ngoài reload lại'],
            'input' => 'Trang /login',
            'expect' => 'FCP không còn do chờ DNS/TLS cdnjs; reload offline vẫn hiện nền + form + icon',
            'auto' => '',
            'status' => 'Chưa chạy tay',
        ],
        [
            'role' => 'Tester', 'feature' => 'D3 — không N+1 ở danh sách', 'type' => 'DB', 'prio' => 'Trung bình',
            'goal' => 'Số query mở trang danh sách sinh viên/nhóm không tăng theo số dòng',
            'pre' => 'Mở Debugbar (nếu cài) hoặc bật MySQL general log',
            'steps' => ['Vào /students với ~15 SV', 'Vào /groups với ~10 nhóm', 'Đếm số query'],
            'input' => 'Danh sách có dữ liệu thật',
            'expect' => 'Số query gần như không đổi khi tăng số dòng (không còn +2 query/dòng; không còn per-group member count)',
            'auto' => '',
            'status' => 'Chưa chạy tay',
        ],
    ],
];
