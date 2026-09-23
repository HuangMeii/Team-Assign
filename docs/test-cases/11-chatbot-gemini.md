# Nhóm 11 — Chatbot trợ lý đề tài (Groq/Gemini)

> **Mã nhóm**: `TC-BOT` · **Chức năng**: FEATURE_STATUS #15: ChatbotService (đa provider) + ChatbotController + widget `components/chatbot.blade.php` + `config/services.php` (chatbot.provider/fallbacks + groq.* + gemini dự phòng)
> **Số test case**: 11 — Pass: **9** · Fail: **0** · Chưa chạy tay: **2**
> **Môi trường**: MySQL team_assign_test; test dùng Http::fake() nên không cần key thật. Muốn chạy thật: đặt `GROQ_API_KEY` (provider chính, https://console.groq.com/keys) và/hoặc `GEMINI_API_KEY` + `CHATBOT_FALLBACKS=gemini` trong .env.
> ↻ File này **sinh tự động** từ `docs/test-cases/data/11-chatbot-gemini.php` — sửa dữ liệu ở đó rồi chạy `php artisan testcases:export` (đừng sửa file .md này).

## 1. Mục tiêu & phạm vi

Kiểm thử widget chatbot trợ lý đề tài (đa provider): gọi Groq/Gemini và trả lời khi đã cấu hình key, KHÔNG gọi mạng + thông báo thân thiện khi thiếu toàn bộ key (widget tự ẩn), chặn tin nhắn rỗng, xử lý lỗi nhà cung cấp (503 + thông báo bận) thay vì crash, timeout/mất mạng không bao giờ thành 500, TỰ CHUYỂN provider dự phòng khi provider chính lỗi, retry cho lỗi tạm thời (5xx), và dòng miễn trừ “chỉ mang tính chất tham khảo — trao đổi với giảng viên phụ trách” hiện ở CUỐI mỗi câu trả lời của bot.

## 2. Bảng tóm tắt test case

| Mã TC | Tên / Mục tiêu | Role | Loại | Ưu tiên | Trạng thái |
|---|---|---|---|---|---|
| `TC-BOT-01` | Chatbot gọi provider chính (Groq) và trả lời khi đã cấu hình key | Sinh viên | API | Cao | Pass |
| `TC-BOT-02` | Thiếu GEMINI_API_KEY thì trả thông báo thân thiện và KHÔNG gọi mạng | Sinh viên | API | Cao | Pass |
| `TC-BOT-03` | Tin nhắn rỗng/toàn khoảng trắng không gọi Gemini (case âm) | Sinh viên | API | Trung bình | Pass |
| `TC-BOT-04` | Provider lỗi 500 thì trả thông báo bận (503) thay vì crash | Sinh viên | API | Cao | Pass |
| `TC-BOT-05` | Widget chatbot tự ẩn khi chưa cấu hình key cho bất kỳ provider nào | Sinh viên | UI | Trung bình | Chưa chạy tay |
| `TC-BOT-06` | Key mới dạng AQ... gọi model gemini-2.5-* trả 404 “no longer available to new users” | Sinh viên | API | Trung bình | Chưa chạy tay |
| `TC-BOT-07` | Groq timeout/mất mạng ⇒ trả 503 thay vì crash 500 | Sinh viên | API | Cao | Pass |
| `TC-BOT-08` | Provider chính lỗi thì tự chuyển sang provider dự phòng (Groq ⇒ Gemini) | Sinh viên | API | Cao | Pass |
| `TC-BOT-09` | `CHATBOT_RETRY=2` ⇒ gọi lại lần 2 khi provider trả 5xx | Sinh viên | API | Trung bình | Pass |
| `TC-BOT-10` | Gọi Gemini khi `CHATBOT_PROVIDER=gemini` — key ở header, KHÔNG trên URL | Sinh viên | API | Trung bình | Pass |
| `TC-BOT-11` | Cuối mỗi câu trả lời của bot có dòng “Nội dung này chỉ mang tính chất tham khảo, … trao đổi với giảng viên phụ trách.” và đổi được từ .env | Sinh viên | UI | Trung bình | Pass |

## 3. Chi tiết test case

### TC-BOT-01 — Chatbot gọi provider chính (Groq) và trả lời khi đã cấu hình key

- **Chức năng**: Hỏi chatbot (#15) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập sinh viên; `GROQ_API_KEY` có giá trị (`CHATBOT_PROVIDER=groq`); HTTP client được fake
- **Các bước thực hiện**:
  1. Mở trang có widget chatbot
  2. Nhập câu hỏi
  3. Bấm gửi
- **Dữ liệu đầu vào**: POST /chatbot/ask {"message":"Có đề tài nào còn trống không?"}
- **Kết quả mong đợi**: HTTP 200 + `reply` = nội dung Groq trả về; request gửi tới `/openai/v1/chat/completions` kèm header `Authorization: Bearer …` và câu hỏi của người dùng trong `messages`
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ChatbotTest.php`

### TC-BOT-02 — Thiếu GEMINI_API_KEY thì trả thông báo thân thiện và KHÔNG gọi mạng

- **Chức năng**: Thiếu cấu hình (#15) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập sinh viên; `CHATBOT_PROVIDER=gemini`; `services.gemini.key` = rỗng
- **Các bước thực hiện**:
  1. Gọi POST /chatbot/ask
  2. Kiểm tra phản hồi
  3. Kiểm tra log HTTP (không có request ra ngoài)
- **Dữ liệu đầu vào**: POST /chatbot/ask {"message":"Xin chào"}
- **Kết quả mong đợi**: HTTP 200 + `reply` chứa “chưa được cấu hình”; KHÔNG có request nào được gửi đi (Http::assertNothingSent)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ChatbotTest.php`

### TC-BOT-03 — Tin nhắn rỗng/toàn khoảng trắng không gọi Gemini (case âm)

- **Chức năng**: Validate đầu vào (#15) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập sinh viên; key đã cấu hình
- **Các bước thực hiện**:
  1. Gửi tin nhắn chỉ có khoảng trắng
  2. Kiểm tra phản hồi
- **Dữ liệu đầu vào**: POST /chatbot/ask {"message":"   "}
- **Kết quả mong đợi**: HTTP 200 + `reply` = “Bạn hãy nhập câu hỏi nhé!”; không gọi mạng
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ChatbotTest.php`

### TC-BOT-04 — Provider lỗi 500 thì trả thông báo bận (503) thay vì crash

- **Chức năng**: Lỗi phía Gemini (#15) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập sinh viên; key đã cấu hình; Http::fake() trả HTTP 500 từ Groq
- **Các bước thực hiện**:
  1. Gửi câu hỏi
  2. Kiểm tra mã HTTP và nội dung phản hồi
- **Dữ liệu đầu vào**: POST /chatbot/ask {"message":"Gợi ý đề tài Laravel?"}
- **Kết quả mong đợi**: HTTP 503 + `reply` = “Hệ thống đang bận, vui lòng thử lại sau.”; không có stack trace lộ ra giao diện
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ChatbotTest.php`

### TC-BOT-05 — Widget chatbot tự ẩn khi chưa cấu hình key cho bất kỳ provider nào

- **Chức năng**: Widget tự ẩn (#15) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: `GROQ_API_KEY` và `GEMINI_API_KEY` đều rỗng trong .env; đã chạy `php artisan config:clear`
- **Các bước thực hiện**:
  1. Đăng nhập sinh viên
  2. Mở /user/dashboard và /user/topics
  3. Tìm widget chatbot ở góc màn hình
- **Dữ liệu đầu vào**: Trang có @include components.chatbot
- **Kết quả mong đợi**: Không hiện nút/widget chatbot (trang vẫn tải bình thường, không có lỗi JS/500)
- **Kết quả thực tế**: Chưa chạy tay
- **Trạng thái**: **Chưa chạy tay**
- **Test tự động**: (thủ công — chạy trên trình duyệt)
- **Ghi chú**: Test tự động chỉ phủ phần API; phần tự ẩn widget nên kiểm tra tay với key rỗng.

### TC-BOT-06 — Key mới dạng AQ... gọi model gemini-2.5-* trả 404 “no longer available to new users”

- **Chức năng**: Tương thích model (#15) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: GEMINI_API_KEY dạng AQ...; GEMINI_BASE_URL trỏ tới gemini-2.5-flash-lite
- **Các bước thực hiện**:
  1. Gửi câu hỏi qua chatbot
  2. Đọc log/thông báo lỗi
  3. Đổi GEMINI_BASE_URL sang gemini-3.6-flash và thử lại
- **Dữ liệu đầu vào**: Cấu hình model 2.5 → 3.6
- **Kết quả mong đợi**: Model 2.5 bị từ chối (404) ⇒ trả 503 + thông báo bận; chuyển sang gemini-3.6-flash ⇒ trả lời bình thường
- **Kết quả thực tế**: Chưa chạy tay
- **Trạng thái**: **Chưa chạy tay**
- **Test tự động**: (thủ công — chạy trên trình duyệt)
- **Ghi chú**: Đã ghi trong FEATURE_STATUS #15 — nguyên nhân là phía Google, không phải code; mặc định config đã dùng model mới.

### TC-BOT-07 — Groq timeout/mất mạng ⇒ trả 503 thay vì crash 500

- **Chức năng**: Groq timeout (#15) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: Đăng nhập sinh viên; `GROQ_API_KEY` có giá trị; Http::fake() ném ConnectionException
- **Các bước thực hiện**:
  1. Gửi câu hỏi
  2. Kiểm tra mã HTTP + nội dung thông báo
- **Dữ liệu đầu vào**: POST /chatbot/ask {"message":"Gợi ý đề tài Laravel?"}
- **Kết quả mong đợi**: HTTP 503 + `reply` = “Hệ thống đang bận, vui lòng thử lại sau.” (không lộ stack trace)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ChatbotTest.php`

### TC-BOT-08 — Provider chính lỗi thì tự chuyển sang provider dự phòng (Groq ⇒ Gemini)

- **Chức năng**: Fallback provider (#15) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Cao
- **Tiền điều kiện**: `CHATBOT_PROVIDER=groq` + `CHATBOT_FALLBACKS=gemini`; cả 2 key đều có; Http::fake() cho Groq 503 và Gemini 200
- **Các bước thực hiện**:
  1. Gửi câu hỏi
  2. Đếm số request ra ngoài
- **Dữ liệu đầu vào**: POST /chatbot/ask {"message":"Gợi ý đề tài Laravel?"}
- **Kết quả mong đợi**: HTTP 200 + `reply` = nội dung từ Gemini; có ĐÚNG 2 request (Groq rồi Gemini)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ChatbotTest.php`

### TC-BOT-09 — `CHATBOT_RETRY=2` ⇒ gọi lại lần 2 khi provider trả 5xx

- **Chức năng**: Retry khi 5xx (#15) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: `GROQ_API_KEY` có giá trị; `CHATBOT_RETRY=2`; Http::fake() trả 503
- **Các bước thực hiện**:
  1. Gửi câu hỏi
  2. Đếm số request
- **Dữ liệu đầu vào**: POST /chatbot/ask {"message":"Gợi ý đề tài Laravel?"}
- **Kết quả mong đợi**: Có ĐÚNG 2 request tới Groq rồi trả 503 + thông báo bận
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ChatbotTest.php`

### TC-BOT-10 — Gọi Gemini khi `CHATBOT_PROVIDER=gemini` — key ở header, KHÔNG trên URL

- **Chức năng**: Gemini dự phòng (#15) · **Role**: Sinh viên · **Loại**: API · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: `CHATBOT_PROVIDER=gemini`; `services.gemini.key` có giá trị; Http::fake() trả 200
- **Các bước thực hiện**:
  1. Gửi câu hỏi
  2. Kiểm tra URL + header của request
- **Dữ liệu đầu vào**: POST /chatbot/ask {"message":"Gợi ý đề tài Laravel?"}
- **Kết quả mong đợi**: HTTP 200 + `reply` = nội dung Gemini; URL chứa `:generateContent` và KHÔNG chứa `key=`; có header `x-goog-api-key`
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ChatbotTest.php`

### TC-BOT-11 — Cuối mỗi câu trả lời của bot có dòng “Nội dung này chỉ mang tính chất tham khảo, … trao đổi với giảng viên phụ trách.” và đổi được từ .env

- **Chức năng**: Dòng miễn trừ (#15) · **Role**: Sinh viên · **Loại**: UI · **Ưu tiên**: Trung bình
- **Tiền điều kiện**: Đăng nhập sinh viên (layout có `@include('components.chatbot')`); có API key chatbot ⇒ widget không tự ẩn; câu chữ ở `CHATBOT_DISCLAIMER` (.env, đặt trong ngoặc kép)
- **Các bước thực hiện**:
  1. Mở trang có widget (vd /user/topics)
  2. Kiểm tra HTML: khối ẩn #chat-disclaimer chứa câu miễn trừ
  3. Hỏi 1 câu bất kỳ và xem bong bóng trả lời của bot
  4. Xoá trắng CHATBOT_DISCLAIMER rồi tải lại trang
- **Dữ liệu đầu vào**: GET /user/topics · POST /chatbot/ask {"message":"Gợi ý đề tài Laravel?"} · .env CHATBOT_DISCLAIMER
- **Kết quả mong đợi**: HTML có `id="chat-disclaimer"` + câu chữ; dòng miễn trừ hiện ở CUỐI mỗi bong bóng bot (chữ nhỏ, nghiêng, xám, có đường kẻ trên) — kể cả câu chào; để trống CHATBOT_DISCLAIMER ⇒ widget KHÔNG render dòng đó nhưng KHÔNG lỗi
- **Kiểm tra thêm (DB / log / API)**: Không đụng DB; API `/chatbot/ask` trả `reply` y như trước (chỉ đổi phía giao diện)
- **Kết quả thực tế**: Đúng như mong đợi (kiểm chứng bằng test tự động)
- **Trạng thái**: **Pass**
- **Test tự động**: `tests/Feature/ChatbotTest.php`
- **Ghi chú**: Test tự động kiểm chứng khối #chat-disclaimer + câu chữ trong HTML render (có/không có theo config); phần chèn vào từng bong bóng là JavaScript nên nên liếc mắt kiểm tra 1 lần trong trình duyệt.

## 4. Cách chạy nhóm test này

```powershell
cd G:\MyApp\laragon\www\Team-Assign
php artisan test tests/Feature/ChatbotTest.php
```

## 5. Ghi chú & rủi ro

- Service `ChatbotService` đọc cấu hình qua `config()`: provider chính ở `services.chatbot.provider` (groq | gemini), dự phòng ở `services.chatbot.fallbacks`, key từng provider ở `services.groq.*` / `services.gemini.*` ⇒ chạy `php artisan config:clear` sau khi sửa .env.
- Thiếu key (không provider nào có key) ⇒ HTTP 200 + `reply` chứa “chưa được cấu hình”; widget trong `components/chatbot.blade.php` tự ẩn ⇒ KHÔNG bao giờ lộ lỗi 500 ra giao diện.
- Groq dùng chuẩn OpenAI (`/chat/completions`, `Authorization: Bearer`), body `messages` ⇒ đọc `choices.0.message.content`. Free tier: gpt-oss-120b · gpt-oss-20b · qwen3.8-27b = 30 req/phút · 1.000 req/ngày · 8K token/phút.
- Gemini dùng body `contents/parts`, key gửi qua HEADER `x-goog-api-key` (không còn `?key=` trên URL ⇒ không lộ key trong log). Key dạng `AQ...` chỉ gọi được model mới (`gemini-3.6-flash`).
- Timeout 30s + connectTimeout 5s + retry (`CHATBOT_RETRY=2`) CHỈ áp cho lỗi tạm thời (timeout/429/5xx); hết tất cả provider ⇒ 503 + “Hệ thống đang bận, vui lòng thử lại sau.”
- Prompt hiện chỉ lấy 20 đề tài mới nhất (chưa lọc theo `class_id` của người hỏi) — đã ghi trong FEATURE_STATUS như hạng mục cải tiến.
- Dòng miễn trừ cuối câu trả lời: câu chữ lấy từ `CHATBOT_DISCLAIMER` (.env) → `services.chatbot.disclaimer` → widget `components/chatbot.blade.php` render vào khối ẩn `#chat-disclaimer`, JS đọc 1 lần rồi chèn `.msg-disclaimer` (chữ nghiêng, xám, có đường kẻ trên) vào CUỐI mỗi bong bóng bot. API `/chatbot/ask` KHÔNG đổi ⇒ các test API cũ giữ nguyên.
- LƯU Ý Dotenv: `CHATBOT_DISCLAIMER` có khoảng trắng nên BẮT BUỘC đặt trong ngoặc kép, thiếu ngoặc ⇒ `php artisan` báo “The environment file is invalid!”.
- Thông báo lỗi mạng ở widget (“⚠️ Lỗi kết nối…”) cố ý KHÔNG kèm dòng miễn trừ vì không phải nội dung tư vấn.
- Case có nhãn `Chưa chạy tay` cần tự chạy trên trình duyệt (2 tài khoản nếu cần realtime) rồi đổi trạng thái trong `data/11-chatbot-gemini.php` và export lại.

<sub>Sinh tự động bởi `php artisan testcases:export` · nguồn: `docs/test-cases/data/11-chatbot-gemini.php`</sub>
