<p align="center">
  <img src="public/logo.png" width="400" alt="team_assign Logo">
</p>

<h1 align="center">team_assign - Hệ thống Quản lý Đề tài Nhóm</h1>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11.x-red.svg" alt="Laravel Version">
  <img src="https://img.shields.io/badge/PHP-8.2+-blue.svg" alt="PHP Version">
  <img src="https://img.shields.io/badge/License-MIT-green.svg" alt="License">
</p>

> 📋 **Trạng thái chức năng:** Bảng audit chức năng (hoàn thành / dở dang) xem tại [`docs/FEATURE_STATUS.md`](./docs/FEATURE_STATUS.md) · Sơ đồ use case, ERD, luồng kiểm duyệt xem tại [`docs/diagrams/`](./docs/diagrams/).

## 📋 Giới thiệu

**team_assign** là hệ thống quản lý đề tài và nhóm sinh viên được xây dựng trên nền tảng Laravel. Hệ thống giúp tổ chức, quản lý và theo dõi quá trình đăng ký, phân công đề tài cho các nhóm sinh viên một cách hiệu quả.

### ✨ Tính năng chính

#### 👥 Quản lý Nhóm
- Tạo và quản lý nhóm sinh viên
- Mời thành viên vào nhóm
- Gửi/nhận yêu cầu tham gia nhóm
- Phân quyền trưởng nhóm và thành viên
- Theo dõi trạng thái nhóm theo lớp học

#### 📚 Quản lý Đề tài
- Duyệt danh sách đề tài theo môn học/lớp học
- Lọc đề tài theo trạng thái (còn trống/đã có nhóm)
- Đăng ký đề tài cho nhóm
- Theo dõi trạng thái duyệt đề tài
- Quản lý đề tài đã đăng ký

#### 🏫 Quản lý Lớp học & Môn học
- Quản lý danh sách lớp học
- Phân công môn học cho lớp
- Import/Export danh sách sinh viên
- Thống kê nhóm và đề tài theo lớp

#### 🔔 Hệ thống Thông báo
- Thông báo real-time
- Thông báo lời mời tham gia nhóm
- Thông báo yêu cầu tham gia nhóm
- Thông báo trạng thái đề tài (duyệt/từ chối)
- Đánh dấu đã đọc/chưa đọc

#### 👤 Phân quyền Người dùng
- **Admin**: Quản lý toàn bộ hệ thống
- **Giảng viên**: Quản lý đề tài, duyệt đăng ký
- **Sinh viên**: Tạo nhóm, đăng ký đề tài

#### 💬 Chat & Kiểm duyệt nội dung (flag-only)
- Chat 1-1 và chat nhóm, gửi ảnh đính kèm, chặn/bỏ chặn người dùng
- **Trạng thái online/offline**: chấm **xanh** = đang hoạt động, chấm **xám** = offline kèm nhãn
  "Hoạt động X phút/giờ/ngày trước" (quá 7 ngày chỉ ghi "hơn 7 ngày trước"); tự cập nhật mỗi 60s
- **Trạng thái tin nhắn 1-1**: `✓` xám = **Đã gửi** (người nhận chưa mở xem) → `✓✓` xanh = **Đã xem**
  (người nhận đang mở trang hội thoại — chỉ cần mở trang, không cần click ô nhập); cập nhật realtime
- **Lịch sử trò chuyện**: tách nhãn ngày (Hôm nay / Hôm qua / dd/mm/yyyy) + nút *Tải thêm tin nhắn cũ*
- Badge số tin nhắn chưa đọc (cá nhân + theo nhóm), real-time qua Laravel Reverb
- Kiểm duyệt **chỉ gắn cờ, không chặn gửi**: gian lận (rules + PhoBERT 2 nhãn),
  xúc phạm/nội dung nhạy cảm (PhoBERT 5 nhãn multi-label: profanity/insult/threat/dangerous/adult),
  ảnh nhạy cảm (Cloud Vision) — chi tiết tại [`violation-detection/README.md`](./violation-detection/README.md)
  · model & cách bật 3 server AI: [`AI-Services/README.md`](../AI-Services/README.md)
- Admin giám sát chat: tab **Bị gắn cờ**, bỏ cờ/xóa tin, broadcast thông báo

#### 🤖 Chatbot trợ lý đề tài
- Trả lời câu hỏi về đề tài qua Gemini API (component góc màn hình)
- Cần cấu hình `GEMINI_API_KEY` + `GEMINI_BASE_URL` trong `.env` (xem phần Cấu hình nâng cao)

#### 🏫 Bảng tin lớp học (kiểu Google Classroom)
- Giảng viên **phụ trách lớp** (hoặc admin) đăng thông báo cho lớp → sinh viên trong lớp nhận
  thông báo (chuông + realtime)
- **Hoạt động nhóm tự động hiện trong lớp**: thành lập nhóm, thêm/rời thành viên, đổi nhóm trưởng,
  nhóm được duyệt/gán đề tài, nhóm giải tán (bài hệ thống, không cần GV nhập tay)
- **Bình luận + trả lời** (1 cấp) dưới mỗi bài; **ghim** thông báo quan trọng; xoá bài/bình luận theo quyền
- Trang bảng tin `/classes/{id}/stream` + thẻ tóm tắt 3 bài mới nhất ngay trong **trang lớp**
  (sinh viên / giảng viên / admin); cập nhật realtime qua Reverb
- Đưa **dữ liệu cũ** vào bảng tin: `php artisan class-stream:backfill [--class=] [--dry-run]` (idempotent)

- Sinh viên nhập mô tả điều nhóm muốn làm → **Top 5 đề tài gần nghĩa nhất** trong lớp học phần
  (embedding `vietnamese-sbert` 768 chiều + cosine similarity; service `AI-Services/topic-recommender`, port 8891)
- Khác tìm kiếm từ khoá: hiểu *ý định* — "làm web quản lý sách cho trường" vẫn khớp
  "Xây dựng hệ thống quản lý thư viện" dù không trùng từ nào
- Vector đề tài lưu **1 lần** ở bảng `topic_embeddings` (`php artisan topics:embed`) ⇒
  mỗi lần gợi ý chỉ embedding câu truy vấn, **không embedding lại kho đề tài**
- Có ở 2 trang: **Danh sách đề tài** (`user/topics`) và **Tìm đề tài cho nhóm** (`user/group_topics`)
- Service AI tắt ⇒ panel báo "tạm thời không khả dụng", đăng ký đề tài vẫn chạy bình thường (fail-open)

## 🚀 Công nghệ sử dụng

- **Backend**: Laravel 11.x
- **Frontend**: Bootstrap 5, Blade Templates
- **Database**: MySQL/MariaDB
- **Icons**: Font Awesome 6.4
- **JS**: Vanilla JavaScript (ES6+)

## 📦 Yêu cầu hệ thống

- PHP >= 8.2
- Composer >= 2.0
- MySQL >= 5.7 hoặc MariaDB >= 10.3
- Node.js >= 18.x (optional, cho build assets)
- Git

## 🛠️ Cài đặt

### 1. Clone project
```bash
git clone https://github.com/Huangmeii/team_assign.git
cd team_assign
```

### 2. Cài đặt dependencies
```bash
composer install
```

### 3. Cấu hình môi trường
```bash
cp .env.example .env
php artisan key:generate
```

Chỉnh sửa file `.env` với thông tin database:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=team_assign
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Tạo database và chạy migrations
```bash
# Tạo database
mysql -u root -p -e "CREATE DATABASE team_assign CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Chạy migrations
php artisan migrate

# Seed dữ liệu mẫu (optional)
php artisan db:seed
```

### 5. Tạo symbolic link cho storage
```bash
php artisan storage:link
```

### 6. Khởi chạy server
```bash
php artisan serve
```

Truy cập: `http://localhost:8000`



## 📂 Cấu trúc Project
```
team_assign/                  # repo: chỉ chứa code — weights model KHÔNG nằm trong repo
├── app/
│   ├── Http/Controllers/     # 23 controller (admin/, chat, groups...)
│   ├── Models/               # User, Groups, Topics, ChatMessage, DirectMessage...
│   ├── Events/               # DirectMessageSent, NewChatMessage (Reverb)
│   └── Services/             # ImageModerationService, ChatUnreadService...
├── violation-detection/      # Module kiểm duyệt nội dung (code PHP + 2 server FastAPI)
│   ├── src/                  # TextModerationService, SensitiveModerationService, FlagHelper
│   ├── config/thresholds.json# Ngưỡng + tên nhãn (PHP + Python đọc chung)
│   ├── datasets/             # chat_fraud_dataset.csv (5151 dòng)
│   └── python/               # app.py (fraud :8889), app_moderation.py (:8890)
├── docs/
│   ├── FEATURE_STATUS.md     # Audit trạng thái chức năng
│   └── diagrams/             # Use case, activity, sequence, ERD, architecture
├── database/migrations/
├── resources/views/          # Blade (admin/, user/, chat/, layouts/...)
├── routes/web.php
└── public/logo.png
```

Weights model + app Vision nằm **ngoài repo**, ở `G:\MyApp\laragon\www\AI-Services\`
(lý do: ~1 GB weights sẽ làm repo phình to; đây cũng là thành phần độc lập,
không cần version chung với Team-Assign):

```
AI-Services/                          # NGOÀI repo Team-Assign
├── README.md                         # bật/dừng 3 service, health check, troubleshooting
├── MODEL_INFO.md                     # thông tin model: kiến trúc, nhãn, tokenizer, FAQ
├── start-servers.ps1                 # bật cả 3 (chạy nền, idempotent)
├── stop-servers.ps1                  # dừng cả 3 (dò process theo port)
├── logs/                             # log runtime 3 server
├── phobert-negative-classifier/      # weights PhoBERT GIAN LẬN (2 nhãn, :8889)
├── chat_moderation_model/            # weights PhoBERT NHẠY CẢM (5 nhãn, :8890)
└── ImageCommentClassification/       # Node app Cloud Vision (:8888)
```

## 🔧 Cấu hình nâng cao

### Import sinh viên từ Excel

1. Tải template Excel:
```
GET /students-template/download
```

2. Upload file Excel:
```
POST /students-import
```

Format file Excel:
| Email | Họ và tên |
|-------|-----------|
| student1@example.com | Nguyễn Văn A |
| student2@example.com | Trần Thị B |

### Cấu hình Notification

File: `config/app.php`
```php
'notification_cleanup_days' => env('NOTIFICATION_CLEANUP_DAYS', 30),
```

Dọn dẹp notifications cũ:
```bash
php artisan schedule:work
```

### Cấu hình Chatbot (Gemini)
```env
GEMINI_API_KEY=your_api_key        # key mới dạng AQ... tạo tại aistudio.google.com
GEMINI_BASE_URL=https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent
```
> ⚠️ Key mới (dạng `AQ...`) **không gọi được model cũ** (`gemini-2.5-*` → 404 "no longer
> available to new users") — dùng `gemini-3.6-flash` / `gemini-3.5-flash` / `gemini-flash-latest`.
> Thiếu key ⇒ widget chatbot tự ẩn (không còn lỗi 500). Chi tiết: [`docs/FEATURE_STATUS.md`](./docs/FEATURE_STATUS.md).

### Cấu hình kiểm duyệt nội dung (moderation)
```env
# Gian lận (PhoBERT 2 nhãn, port 8889)
TEXT_MODERATION_URL=http://127.0.0.1:8889
TEXT_MODERATION_MODE=rules          # rules | model | hybrid

# Xúc phạm / nhạy cảm (PhoBERT 5 nhãn multi-label, port 8890)
MODERATION_URL=http://127.0.0.1:8890
MODERATION_MODE=hybrid              # rules | model | hybrid

# Ảnh nhạy cảm (Cloud Vision node server, port 8888)
VISION_MODERATION_URL=http://127.0.0.1:8888

# Đường dẫn weights model — server Python đọc; env thắng giá trị mặc định trong app*.py
TEXT_MODEL_DIR=G:\MyApp\laragon\www\AI-Services\phobert-negative-classifier
MODERATION_MODEL_DIR=G:\MyApp\laragon\www\AI-Services\chat_moderation_model

# Gợi ý đề tài theo ngữ nghĩa (embedding + cosine, port 8891)
TOPIC_RECOMMENDER_ENABLED=true
TOPIC_RECOMMENDER_URL=http://127.0.0.1:8891
TOPIC_RECOMMENDER_TOP_K=5
RECOMMENDER_MODEL_DIR=G:\MyApp\laragon\www\AI-Services\topic-recommender\model
```
- Bật/dừng cả 4 AI service bằng 1 lệnh:
  ```powershell
  powershell -ExecutionPolicy Bypass -File G:\MyApp\laragon\www\AI-Services\start-servers.ps1
  powershell -ExecutionPolicy Bypass -File G:\MyApp\laragon\www\AI-Services\stop-servers.ps1
  ```
  Hướng dẫn đầy đủ (health check, xử lý sự cố, chạy từng service): [`AI-Services/README.md`](../AI-Services/README.md).
- Thông tin model (kiến trúc, nhãn, bộ file, tokenizer, tài nguyên, FAQ): [`AI-Services/MODEL_INFO.md`](../AI-Services/MODEL_INFO.md).
- Contract API + cách chạy thủ công từng server: [`violation-detection/README.md`](./violation-detection/README.md).
- Ngưỡng gắn cờ chỉnh trong `violation-detection/config/thresholds.json` (PHP + Python đọc chung,
  **không cần restart** server Python).

## 🗺️ Sơ đồ & tài liệu thiết kế

| Tài liệu | Nội dung |
|----------|----------|
| [`AI-Services/README.md`](../AI-Services/README.md) | Bật/dừng 4 AI service (8888/8889/8890/8891), health check, troubleshooting |
| [`AI-Services/MODEL_INFO.md`](../AI-Services/MODEL_INFO.md) | Thông tin model: kiến trúc, nhãn, tokenizer, tài nguyên, FAQ (gồm model embedding ở mục 10) |
| [`AI-Services/topic-recommender/README.md`](../AI-Services/topic-recommender/README.md) | Gợi ý đề tài theo ngữ nghĩa (:8891): contract API, cấu hình, backfill vector, troubleshooting |
| [`docs/FEATURE_STATUS.md`](./docs/FEATURE_STATUS.md) | Bảng audit trạng thái từng chức năng + việc cần làm |
| [`docs/diagrams/use-case.md`](./docs/diagrams/use-case.md) | Use case theo 4 vai trò |
| [`docs/diagrams/activity-moderation.md`](./docs/diagrams/activity-moderation.md) | Luồng gửi tin nhắn qua 3 tầng kiểm duyệt (flag-only) |
| [`docs/diagrams/sequence-chat.md`](./docs/diagrams/sequence-chat.md) | Sequence: gửi tin nhóm + admin bỏ cờ |
| [`docs/diagrams/erd.md`](./docs/diagrams/erd.md) | ERD các bảng chính |
| [`docs/diagrams/architecture.md`](./docs/diagrams/architecture.md) | Kiến trúc triển khai (Laravel + Reverb + AI servers) |
| [`docs/diagrams/admin-charts.md`](./docs/diagrams/admin-charts.md) | Đề xuất biểu đồ cho admin dashboard (Chart.js) |
| [`docs/diagrams/sequence-topic-recommendation.md`](./docs/diagrams/sequence-topic-recommendation.md) | Sequence: sinh viên nhập mô tả → embedding → cosine → Top 5 đề tài |
| [`docs/diagrams/sequence-class-stream.md`](./docs/diagrams/sequence-class-stream.md) | Sequence: đăng thông báo lớp, bình luận, hoạt động nhóm tự vào bảng tin (realtime) |

## 🎨 Tùy chỉnh giao diện

### Màu sắc chính

File: `resources/views/layouts/user.blade.php`
```css
--primary-color: #667eea;
--secondary-color: #764ba2;
```

### Logo

Thay thế file `public/logo.png` bằng logo của bạn.

## 🧪 Testing
```bash
# Chạy tests
php artisan test

# Với coverage
php artisan test --coverage
```

> Trạng thái hiện tại: **184 passed / 4 failed** — 4 fail đều ở `ForgotPasswordTest` /
> `ChangePasswordTest` do môi trường mail (test cần SMTP thật). Ép `MAIL_MAILER=log` trong
> `phpunit.xml` để chạy xanh. Chi tiết: [`docs/FEATURE_STATUS.md`](./docs/FEATURE_STATUS.md).
>
> Chức năng gợi ý đề tài theo ngữ nghĩa (mục 18) có thêm **22 test**:
> `php artisan test tests/Unit/TopicRecommendationTest.php` (12 — không cần DB/service AI, dùng `Http::fake()`)
> và `php artisan test tests/Feature/TopicRecommendationTest.php` (10 — cần MySQL test `team_assign_test`).
>
> Chức năng bảng tin lớp học (mục 19) có thêm **15 test**:
> `php artisan test tests/Unit/ClassStreamTest.php` (5) và `php artisan test tests/Feature/ClassStreamTest.php` (10
> — ACL, notify sinh viên, hoạt động nhóm tự sinh bài, ghim/xoá, bình luận/reply, backfill idempotent, fail-open).
>
> Chức năng online/offline + trạng thái tin nhắn (mục 20) có thêm **19 test**:
> `tests/Unit/PresenceTest.php` (7 — nhãn "Đang hoạt động"/"Hoạt động X trước", cap 7 ngày),
> `tests/Feature/PresenceTest.php` (6 — middleware + guard 60s, `/presence/ping`, `/presence/status`),
> `tests/Feature/ChatMessageStatusTest.php` (6 — Đã gửi → Đã xem, broadcast `DirectMessagesSeen`, tick, lịch sử).

## 📊 Database Schema

### Bảng chính

- **users** - Quản lý người dùng
- **groups** - Quản lý nhóm sinh viên
- **topics** - Quản lý đề tài
- **topic_embeddings** - Vector ngữ nghĩa của đề tài (gợi ý đề tài, chỉ sinh 1 lần)
- **class_sections** - Quản lý lớp học
- **subjects** - Quản lý môn học
- **class_posts** - Bảng tin lớp (thông báo của GV + hoạt động nhóm)
- **class_post_comments** - Bình luận / trả lời dưới bài viết của lớp
- **notifications** - Quản lý thông báo
- **topic_requests** - Yêu cầu đăng ký đề tài
- **join_requests** - Yêu cầu tham gia nhóm
- **invites** - Lời mời tham gia nhóm

## 🤝 Đóng góp

Mọi đóng góp đều được hoan nghênh! Vui lòng:

1. Fork project
2. Tạo branch cho tính năng (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Mở Pull Request

## 📞 Liên hệ

- **Developer**: Huỳnh Mai
- **Email**: huynhmai2755@gmail.com
- **GitHub**: [@huangmeii](https://github.com/huangmeii)

## 📄 License

Project này được phân phối dưới giấy phép MIT. Xem file `LICENSE` để biết thêm chi tiết.

## 🙏 Acknowledgments

- [Laravel](https://laravel.com) - Framework PHP tuyệt vời
- [Bootstrap](https://getbootstrap.com) - CSS Framework
- [Font Awesome](https://fontawesome.com) - Icon library
- Cảm ơn tất cả contributors đã đóng góp cho project

---

<p align="center">Made with ❤️ by Ngân</p>