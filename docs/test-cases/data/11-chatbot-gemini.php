<?php

/**
 * Test case — Nhóm 11: Chatbot trợ lý đề tài (Gemini) (mã TC-BOT)
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
    'code_prefix' => 'TC-BOT',
    'sheet' => '11-BOT',
    'title' => 'Nhóm 11 — Chatbot trợ lý đề tài (Groq/Gemini)',
    'features' => 'FEATURE_STATUS #15: ChatbotService (đa provider) + ChatbotController + widget `components/chatbot.blade.php` + `config/services.php` (chatbot.provider/fallbacks + groq.* + gemini dự phòng)',
    'summary' => 'Kiểm thử widget chatbot trợ lý đề tài (đa provider): gọi Groq/Gemini và trả lời khi đã cấu hình key, KHÔNG gọi mạng + thông báo thân thiện khi thiếu toàn bộ key (widget tự ẩn), chặn tin nhắn rỗng, xử lý lỗi nhà cung cấp (503 + thông báo bận) thay vì crash, timeout/mất mạng không bao giờ thành 500, TỰ CHUYỂN provider dự phòng khi provider chính lỗi, retry cho lỗi tạm thời (5xx), và dòng miễn trừ “chỉ mang tính chất tham khảo — trao đổi với giảng viên phụ trách” hiện ở CUỐI mỗi câu trả lời của bot.',
    'env' => 'MySQL team_assign_test; test dùng Http::fake() nên không cần key thật. Muốn chạy thật: đặt `GROQ_API_KEY` (provider chính, https://console.groq.com/keys) và/hoặc `GEMINI_API_KEY` + `CHATBOT_FALLBACKS=gemini` trong .env.',
    'run' => 'php artisan test tests/Feature/ChatbotTest.php',
    'notes' => [
        'Service `ChatbotService` đọc cấu hình qua `config()`: provider chính ở `services.chatbot.provider` (groq | gemini), dự phòng ở `services.chatbot.fallbacks`, key từng provider ở `services.groq.*` / `services.gemini.*` ⇒ chạy `php artisan config:clear` sau khi sửa .env.',
        'Thiếu key (không provider nào có key) ⇒ HTTP 200 + `reply` chứa “chưa được cấu hình”; widget trong `components/chatbot.blade.php` tự ẩn ⇒ KHÔNG bao giờ lộ lỗi 500 ra giao diện.',
        'Groq dùng chuẩn OpenAI (`/chat/completions`, `Authorization: Bearer`), body `messages` ⇒ đọc `choices.0.message.content`. Free tier: gpt-oss-120b · gpt-oss-20b · qwen3.8-27b = 30 req/phút · 1.000 req/ngày · 8K token/phút.',
        'Gemini dùng body `contents/parts`, key gửi qua HEADER `x-goog-api-key` (không còn `?key=` trên URL ⇒ không lộ key trong log). Key dạng `AQ...` chỉ gọi được model mới (`gemini-3.6-flash`).',
        'Timeout 30s + connectTimeout 5s + retry (`CHATBOT_RETRY=2`) CHỈ áp cho lỗi tạm thời (timeout/429/5xx); hết tất cả provider ⇒ 503 + “Hệ thống đang bận, vui lòng thử lại sau.”',
        'Prompt hiện chỉ lấy 20 đề tài mới nhất (chưa lọc theo `class_id` của người hỏi) — đã ghi trong FEATURE_STATUS như hạng mục cải tiến.',
        'Dòng miễn trừ cuối câu trả lời: câu chữ lấy từ `CHATBOT_DISCLAIMER` (.env) → `services.chatbot.disclaimer` → widget `components/chatbot.blade.php` render vào khối ẩn `#chat-disclaimer`, JS đọc 1 lần rồi chèn `.msg-disclaimer` (chữ nghiêng, xám, có đường kẻ trên) vào CUỐI mỗi bong bóng bot. API `/chatbot/ask` KHÔNG đổi ⇒ các test API cũ giữ nguyên.',
        'LƯU Ý Dotenv: `CHATBOT_DISCLAIMER` có khoảng trắng nên BẮT BUỘC đặt trong ngoặc kép, thiếu ngoặc ⇒ `php artisan` báo “The environment file is invalid!”.',
        'Thông báo lỗi mạng ở widget (“⚠️ Lỗi kết nối…”) cố ý KHÔNG kèm dòng miễn trừ vì không phải nội dung tư vấn.',
    ],
    'cases' => [
        [
            'role' => 'Sinh viên', 'feature' => 'Hỏi chatbot (#15)', 'type' => 'API', 'prio' => 'Cao',
            'goal' => 'Chatbot gọi provider chính (Groq) và trả lời khi đã cấu hình key',
            'pre' => 'Đăng nhập sinh viên; `GROQ_API_KEY` có giá trị (`CHATBOT_PROVIDER=groq`); HTTP client được fake',
            'steps' => ['Mở trang có widget chatbot', 'Nhập câu hỏi', 'Bấm gửi'],
            'input' => 'POST /chatbot/ask {"message":"Có đề tài nào còn trống không?"}',
            'expect' => 'HTTP 200 + `reply` = nội dung Groq trả về; request gửi tới `/openai/v1/chat/completions` kèm header `Authorization: Bearer …` và câu hỏi của người dùng trong `messages`',
            'auto' => 'tests/Feature/ChatbotTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Sinh viên', 'feature' => 'Thiếu cấu hình (#15)', 'type' => 'API', 'prio' => 'Cao',
            'goal' => 'Thiếu GEMINI_API_KEY thì trả thông báo thân thiện và KHÔNG gọi mạng',
            'pre' => 'Đăng nhập sinh viên; `CHATBOT_PROVIDER=gemini`; `services.gemini.key` = rỗng',
            'steps' => ['Gọi POST /chatbot/ask', 'Kiểm tra phản hồi', 'Kiểm tra log HTTP (không có request ra ngoài)'],
            'input' => 'POST /chatbot/ask {"message":"Xin chào"}',
            'expect' => 'HTTP 200 + `reply` chứa “chưa được cấu hình”; KHÔNG có request nào được gửi đi (Http::assertNothingSent)',
            'auto' => 'tests/Feature/ChatbotTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Sinh viên', 'feature' => 'Validate đầu vào (#15)', 'type' => 'API', 'prio' => 'Trung bình',
            'goal' => 'Tin nhắn rỗng/toàn khoảng trắng không gọi Gemini (case âm)',
            'pre' => 'Đăng nhập sinh viên; key đã cấu hình',
            'steps' => ['Gửi tin nhắn chỉ có khoảng trắng', 'Kiểm tra phản hồi'],
            'input' => 'POST /chatbot/ask {"message":"   "}',
            'expect' => 'HTTP 200 + `reply` = “Bạn hãy nhập câu hỏi nhé!”; không gọi mạng',
            'auto' => 'tests/Feature/ChatbotTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Sinh viên', 'feature' => 'Lỗi phía Gemini (#15)', 'type' => 'API', 'prio' => 'Cao',
            'goal' => 'Provider lỗi 500 thì trả thông báo bận (503) thay vì crash',
            'pre' => 'Đăng nhập sinh viên; key đã cấu hình; Http::fake() trả HTTP 500 từ Groq',
            'steps' => ['Gửi câu hỏi', 'Kiểm tra mã HTTP và nội dung phản hồi'],
            'input' => 'POST /chatbot/ask {"message":"Gợi ý đề tài Laravel?"}',
            'expect' => 'HTTP 503 + `reply` = “Hệ thống đang bận, vui lòng thử lại sau.”; không có stack trace lộ ra giao diện',
            'auto' => 'tests/Feature/ChatbotTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Sinh viên', 'feature' => 'Widget tự ẩn (#15)', 'type' => 'UI', 'prio' => 'Trung bình',
            'goal' => 'Widget chatbot tự ẩn khi chưa cấu hình key cho bất kỳ provider nào',
            'pre' => '`GROQ_API_KEY` và `GEMINI_API_KEY` đều rỗng trong .env; đã chạy `php artisan config:clear`',
            'steps' => ['Đăng nhập sinh viên', 'Mở /user/dashboard và /user/topics', 'Tìm widget chatbot ở góc màn hình'],
            'input' => 'Trang có @include components.chatbot',
            'expect' => 'Không hiện nút/widget chatbot (trang vẫn tải bình thường, không có lỗi JS/500)',
            'status' => 'Chưa chạy tay',
            'note' => 'Test tự động chỉ phủ phần API; phần tự ẩn widget nên kiểm tra tay với key rỗng.',
        ],
        [
            'role' => 'Sinh viên', 'feature' => 'Tương thích model (#15)', 'type' => 'API', 'prio' => 'Trung bình',
            'goal' => 'Key mới dạng AQ... gọi model gemini-2.5-* trả 404 “no longer available to new users”',
            'pre' => 'GEMINI_API_KEY dạng AQ...; GEMINI_BASE_URL trỏ tới gemini-2.5-flash-lite',
            'steps' => ['Gửi câu hỏi qua chatbot', 'Đọc log/thông báo lỗi', 'Đổi GEMINI_BASE_URL sang gemini-3.6-flash và thử lại'],
            'input' => 'Cấu hình model 2.5 → 3.6',
            'expect' => 'Model 2.5 bị từ chối (404) ⇒ trả 503 + thông báo bận; chuyển sang gemini-3.6-flash ⇒ trả lời bình thường',
            'status' => 'Chưa chạy tay',
            'note' => 'Đã ghi trong FEATURE_STATUS #15 — nguyên nhân là phía Google, không phải code; mặc định config đã dùng model mới.',
        ],
        [
            'role' => 'Sinh viên', 'feature' => 'Groq timeout (#15)', 'type' => 'API', 'prio' => 'Cao',
            'goal' => 'Groq timeout/mất mạng ⇒ trả 503 thay vì crash 500',
            'pre' => 'Đăng nhập sinh viên; `GROQ_API_KEY` có giá trị; Http::fake() ném ConnectionException',
            'steps' => ['Gửi câu hỏi', 'Kiểm tra mã HTTP + nội dung thông báo'],
            'input' => 'POST /chatbot/ask {"message":"Gợi ý đề tài Laravel?"}',
            'expect' => 'HTTP 503 + `reply` = “Hệ thống đang bận, vui lòng thử lại sau.” (không lộ stack trace)',
            'auto' => 'tests/Feature/ChatbotTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Sinh viên', 'feature' => 'Fallback provider (#15)', 'type' => 'API', 'prio' => 'Cao',
            'goal' => 'Provider chính lỗi thì tự chuyển sang provider dự phòng (Groq ⇒ Gemini)',
            'pre' => '`CHATBOT_PROVIDER=groq` + `CHATBOT_FALLBACKS=gemini`; cả 2 key đều có; Http::fake() cho Groq 503 và Gemini 200',
            'steps' => ['Gửi câu hỏi', 'Đếm số request ra ngoài'],
            'input' => 'POST /chatbot/ask {"message":"Gợi ý đề tài Laravel?"}',
            'expect' => 'HTTP 200 + `reply` = nội dung từ Gemini; có ĐÚNG 2 request (Groq rồi Gemini)',
            'auto' => 'tests/Feature/ChatbotTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Sinh viên', 'feature' => 'Retry khi 5xx (#15)', 'type' => 'API', 'prio' => 'Trung bình',
            'goal' => '`CHATBOT_RETRY=2` ⇒ gọi lại lần 2 khi provider trả 5xx',
            'pre' => '`GROQ_API_KEY` có giá trị; `CHATBOT_RETRY=2`; Http::fake() trả 503',
            'steps' => ['Gửi câu hỏi', 'Đếm số request'],
            'input' => 'POST /chatbot/ask {"message":"Gợi ý đề tài Laravel?"}',
            'expect' => 'Có ĐÚNG 2 request tới Groq rồi trả 503 + thông báo bận',
            'auto' => 'tests/Feature/ChatbotTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Sinh viên', 'feature' => 'Gemini dự phòng (#15)', 'type' => 'API', 'prio' => 'Trung bình',
            'goal' => 'Gọi Gemini khi `CHATBOT_PROVIDER=gemini` — key ở header, KHÔNG trên URL',
            'pre' => '`CHATBOT_PROVIDER=gemini`; `services.gemini.key` có giá trị; Http::fake() trả 200',
            'steps' => ['Gửi câu hỏi', 'Kiểm tra URL + header của request'],
            'input' => 'POST /chatbot/ask {"message":"Gợi ý đề tài Laravel?"}',
            'expect' => 'HTTP 200 + `reply` = nội dung Gemini; URL chứa `:generateContent` và KHÔNG chứa `key=`; có header `x-goog-api-key`',
            'auto' => 'tests/Feature/ChatbotTest.php',
            'status' => 'Pass',
        ],
        [
            'role' => 'Sinh viên', 'feature' => 'Dòng miễn trừ (#15)', 'type' => 'UI', 'prio' => 'Trung bình',
            'goal' => 'Cuối mỗi câu trả lời của bot có dòng “Nội dung này chỉ mang tính chất tham khảo, … trao đổi với giảng viên phụ trách.” và đổi được từ .env',
            'pre' => 'Đăng nhập sinh viên (layout có `@include(\'components.chatbot\')`); có API key chatbot ⇒ widget không tự ẩn; câu chữ ở `CHATBOT_DISCLAIMER` (.env, đặt trong ngoặc kép)',
            'steps' => ['Mở trang có widget (vd /user/topics)', 'Kiểm tra HTML: khối ẩn #chat-disclaimer chứa câu miễn trừ', 'Hỏi 1 câu bất kỳ và xem bong bóng trả lời của bot', 'Xoá trắng CHATBOT_DISCLAIMER rồi tải lại trang'],
            'input' => 'GET /user/topics · POST /chatbot/ask {"message":"Gợi ý đề tài Laravel?"} · .env CHATBOT_DISCLAIMER',
            'expect' => 'HTML có `id="chat-disclaimer"` + câu chữ; dòng miễn trừ hiện ở CUỐI mỗi bong bóng bot (chữ nhỏ, nghiêng, xám, có đường kẻ trên) — kể cả câu chào; để trống CHATBOT_DISCLAIMER ⇒ widget KHÔNG render dòng đó nhưng KHÔNG lỗi',
            'db' => 'Không đụng DB; API `/chatbot/ask` trả `reply` y như trước (chỉ đổi phía giao diện)',
            'auto' => 'tests/Feature/ChatbotTest.php',
            'status' => 'Pass',
            'note' => 'Test tự động kiểm chứng khối #chat-disclaimer + câu chữ trong HTML render (có/không có theo config); phần chèn vào từng bong bóng là JavaScript nên nên liếc mắt kiểm tra 1 lần trong trình duyệt.',
        ],
    ],
];
