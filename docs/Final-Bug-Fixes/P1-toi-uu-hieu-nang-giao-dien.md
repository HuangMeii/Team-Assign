# P1 — Tối ưu hiệu năng giao diện (D1 → D5)

> Trạng thái: **ĐÃ TRIỂN KHAI** (2026-10-09) · Test: `tests/Feature/PerformanceBatchTest.php` (4 case)
> · Test-case QA thủ công: nhóm **12 · TC-PERF** (`docs/test-cases/`)
> Nguồn: kế hoạch trả lời "cách nào để giao diện load nhanh nhất" (D1 → D5), phát hiện từ rà soát L01–L11 + hardening.

## 1. Triển khai theo từng mục

### D1 — Asset tĩnh: CDN → local (Vite) ✅
**Trước**: trang tải render-blocking từ domain thứ ba (phải chờ DNS+TLS):
- `layouts/user|app|admin` → Bootstrap 5.3 + Font Awesome 6.4 (cdnjs) + Bootstrap JS (cdnjs);
- 4 trang auth (`login`, `forgot-password`, `reset-password`, `verify-done`) → Bootstrap 5.3.3 (jsdelivr) + FA (cdnjs);
- `layouts/guest` → FA (cdnjs); `components/chatbot` → `marked.min.js` (jsdelivr).

**Sau**: `npm i bootstrap@5.3.0 @fortawesome/fontawesome-free@6.4.0 marked` + 4 entry Vite:

| Entry | Nội dung | Nơi dùng |
|---|---|---|
| `resources/css/vendor.css` | Bootstrap CSS + FA + biến màu brand | `<head>` 3 layout chính + 4 trang auth |
| `resources/js/vendor.js` | Bootstrap **bundle JS** | cuối trang 3 layout chính |
| `resources/css/fa.css` | **Chỉ FA** (không Bootstrap) | `layouts/guest` (Tailwind — không trộn Bootstrap) |
| `resources/js/marked.js` | `window.marked` (ESM) | `components/chatbot` |

**Chống tái phát**: test `không view nào còn tải asset từ CDN` quét **toàn bộ** `resources/views`
(không còn `cdnjs.cloudflare.com` / `cdn.jsdelivr.net`). Font guest (`fonts.bunny.net`) giữ nguyên (chỉ font, không block UI).

### D2 — Server caches ✅
- `php artisan route:cache` + `view:cache` + `event:cache` — route:cache đòi hỏi **không còn closure ở action route**
  ⇒ chuyển 2 closure (`/` redirect theo vai trò, `/classes` redirect) sang **`HomeController`** (`index`, `classesRedirect`).
- **OPcache**: bật `zend_extension=opcache` + `opcache.enable=1` trong `php.ini` của Laragon
  (file này **ngoài repo** — mỗi máy tự bật và **restart web server** là có hiệu lực).
- `.env`: `CACHE_STORE=database` → **`file`** (đọc cache = 1 lần đọc file thay vì 1–2 query DB).
- **KHÔNG bật `config:cache`**: phpunit set `APP_ENV/DB_*/MAIL_*` ở runtime — config cache sẽ nuốt biến môi trường test
  ⇒ full suite sập. Đây là lựa chọn có chủ đích (ghi ở đây để không ai bật lại).

### D3 — Chống N+1 ở trang danh sách ✅ (phần badge: KHÔNG cache — có chủ đích)
- `students.index`: controller `withCount('groupsLed')` + `with('groupsJoined')` (trước: +2 query × mỗi dòng).
- `groups.index`: `GroupService::memberStatsFor()` gộp số thành viên/tỉ lệ cho **tất cả** nhóm trong 1–2 query
  (trước: `activeMemberCount()` + `maxMembers()` chạy per-group ≈ N+1 kép).
- **Badge tin nhắn (`ChatUnreadService::totalFor`)**: giữ **tính tươi** 4 query indexed/trang thay vì cache 60–120s —
  vì mọi thay đổi chưa đọc (gửi/đọc/chặn/nhóm) đều phải invalidate ở **nhiều điểm** (send, markRead, block, join…);
  cache sai lệch giữa 2 thiết bị tệ hơn là mất 2–3 ms. (Đề xuất "cache" trong kế hoạch bị **từ chối có chủ đích**.)

### D4 — Broadcast + mail: không chặn response ✅
- 6 điểm `broadcast(...)` (chat 1-1, chat nhóm, bài/bình luận bảng tin, tick "đã xem", thông báo) bọc
  **`App\Support\AfterResponse::broadcast()`** ⇒ publish tới Reverb chạy ở phase `terminating`
  (sau khi response đã gửi, cùng cơ chế `dispatch()->afterResponse()` của L08; **không cần queue worker**;
  fail-open: lỗi publish chỉ log warning).
- `config/mail.php`: smtp `'timeout' => env('MAIL_TIMEOUT', 10)` (trước `null` = không timeout ⇒ Gmail chậm treo request);
  `.env` có `MAIL_TIMEOUT=10`.

### D5 — Đo (chưa cài — để máy dev làm khi cần) ⏸
- Chưa thêm Debugbar/Telescope/Pulse (tránh phình dev); khi cần đo: cài Debugbar dev-only, Lighthouse trên DevTools,
  `slow_query_log` + `EXPLAIN`, Mục tiêu tham chiếu: LCP < 2.5s · TTFB < 500ms · CSS chặn render ≤ 1 file.

## 2. File thay đổi chính
`vite.config.js` · `package.json` (+`bootstrap`, `fontawesome-free`, `marked`) · `resources/css/{vendor,fa}.css` ·
`resources/js/{vendor,marked}.js` · `resources/views/layouts/{user,app,admin,guest}` · 4 trang `auth/*` ·
`components/chatbot` · `routes/web.php` + `HomeController` (mới) · `config/mail.php` · `app/Support/AfterResponse.php` (mới) ·
6 file broadcast · `.env` (CACHE_STORE, MAIL_TIMEOUT — không tracked) · `php.ini` Laragon (ngoài repo).

## 3. Rủi ro / ghi chú vận hành
1. **`public/build` nằm trong `.gitignore`** ⇒ mỗi môi trường chạy `npm ci && npm run build` sau khi pull.
2. OPcache bật ở php.ini máy dev — cần **restart Apache/Laragon**; `opcache.enable_cli` giữ tắt (test không đổi).
3. Cache đã tạo bằng `route:cache/view:cache/event:cache` ⇒ **chạy lại** khi sửa `routes/web.php`/view;
   `config:cache` KHÔNG được bật (xem D2).
4. Gzip/brotli do Apache đảm nhiệm (`mod_deflate`) — không thuộc repo.
5. Badge giữ tươi (D3) — chấp nhận 4 query indexed/trang.

## 4. Xác minh
- `php artisan test tests/Feature/PerformanceBatchTest.php` → **4 passed** (broadcast sau response, redirect `/` theo vai trò,
  smtp timeout = 10, quét toàn views không còn CDN).
- AdminGroupMessageTest (assert broadcast trong cùng request) + RememberLoginTest (redirect `/`) vẫn xanh.
- Full suite: xem dòng xác minh cuối `docs/Final-Bug-Fixes/README.md`.