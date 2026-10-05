# Violation detection (flag-only) — phần PHP trong Team-Assign

Mọi vi phạm **chỉ gắn cờ, KHÔNG chặn gửi**: tin nhắn/ảnh vẫn được lưu và broadcast
bình thường, chỉ thêm `is_flagged = 1` để admin duyệt ở tab **Bị gắn cờ**.

> ⚠️ **Code serve 2 model AI (`:8889` gian lận, `:8890` nhạy cảm) đã chuyển sang repo riêng**
> [`AI-Services`](https://github.com/HuangMeii/AI-Services) — `services/fraud-8889/` và
> `services/sensitive-8890/`. Thư mục này chỉ giữ **phần PHP dùng trong app** + dataset + ngưỡng.
> Cách cài/chạy model: [`AI-Services/docs/GETTING-STARTED.md`](https://github.com/HuangMeii/AI-Services/blob/main/docs/GETTING-STARTED.md).

## Layout

- `src/` — **logic PHP dùng thật trong app**, autoload qua `composer.json`:
  `"App\\Services\\ViolationDetection\\": "violation-detection/src/"`.
  - `src/TextModerationService.php` — **rule-based** từ dataset, trả `{is_violation, score, reasons}`;
    khi `TEXT_MODERATION_MODE` = `model`/`hybrid` thì gọi thêm server `:8889` (PhoBERT gian lận).
  - `src/SensitiveModerationService.php` — rules tiếng Việt offline + gọi server `:8890`
    (PhoBERT 5 nhãn: profanity/insult/threat/dangerous/adult).
  - `src/FlagHelper.php` — gộp cờ text + ảnh + nhạy cảm thành
    `is_flagged / flag_reason / moderation_score / flagged_at`.
- `config/thresholds.json` — **nguồn ngưỡng duy nhất cho CẢ PHP và Python**:
  `text_threshold` (rule), `text_model_threshold` (model `:8889`), `moderation_threshold`
  (model `:8890`), `moderation_labels`, `moderation_positive_labels`…
  Hai server Python đọc file này qua env `THRESHOLDS_FILE` ⇒ tune **không cần restart** server Python.
- `datasets/chat_fraud_dataset.csv` — dataset text. Header thực tế `,text,label,,`
  (cột 0 trống, cột 1 = text, cột 2 = label; `1` = fraud). Thống kê hiện tại:
  **5151 dòng có nội dung / 2560 fraud / 2500 sạch / 91 không nhãn**.
- `tools/extract_keywords.php` — CLI trích top cụm fraud từ CSV (chỉ chạy tay, không runtime).

## Cách hoạt động

1. `TextModerationService::check($content)` → điểm 0..1 + `reasons`
   (rule-based; nếu mode = `model`/`hybrid` thì lấy thêm điểm từ `:8889`).
2. `SensitiveModerationService::check($content)` → điểm + `reasons` cho 5 nhãn (`:8890`).
3. `ImageModerationService::checkStoredImage($path)` (Cloud Vision `:8888`, fail-open khi server tắt).
4. `FlagHelper::merge(text, image, sensitive)` → 4 cột flag; Controller `array_merge` vào dữ liệu tin nhắn.
5. Controller **không bao giờ** xoá file ảnh hay trả 422 vì lý do moderation.

Áp dụng cho `DirectChatController::send` (chat 1-1) và `GroupsChatController::sendMessage` (chat nhóm).

## Quy ước flag

- `flag_reason` ví dụ: `text:bán slot,tiền + liên hệ riêng (telegram) (0.85) | image:adult VERY_LIKELY`.
- `moderation_score`: điểm cao nhất; ảnh bị Vision gắn cờ luôn >= 0.9.
- Admin: `admin/chat-monitor?tab=flagged` (+ filter `min_score`), nút **Bỏ cờ**
  (`admin.chat.direct.unflag` / `admin.chat.group.unflag`) hoặc **Xóa**.

## Trích lại luật từ dataset

```bash
php violation-detection/tools/extract_keywords.php
php violation-detection/tools/extract_keywords.php violation-detection/datasets/chat_fraud_dataset.csv 40
```

Output là các cụm fraud có tỉ lệ xuất hiện cao hơn hẳn nhóm sạch (top hiện tại:
`lam ho`, `dap an`, `slot`, `gia N`, `trieu ib`, `chuyen khoan`, `tai khoan`…)
→ dùng để cập nhật `STRONG_RULES` / `CONTACT_TOKENS` trong `src/TextModerationService.php`.

## Test

```bash
# Logic thuần (text + FlagHelper + SensitiveModeration) — KHÔNG cần DB, chạy được mọi lúc
php artisan test tests/Unit/ViolationDetectionTest.php
php artisan test tests/Unit/SensitiveModerationTest.php

# Luồng HTTP + admin — cần MySQL test (xem phpunit.xml: DB_HOST/DB_PORT/DB_DATABASE)
php artisan test tests/Feature/Services/ViolationDetectionTest.php
php artisan test tests/Feature/Services/SensitiveModerationChatTest.php
```

- `tests/Unit/ViolationDetectionTest.php`: text fraud/sạch, viết hoa không dấu, đe doạ tống tiền,
  ngưỡng từ config, `FlagHelper` merge + chuẩn hoá `violations`.
- Các test Phase 2 dùng `Http::fake()`: model báo vi phạm · model báo sạch · server chết → fallback rules ·
  hybrid lấy điểm cao nhất · `rules` không gọi mạng.
- Lưu ý môi trường: `.env` dev trỏ MySQL remote, còn `phpunit.xml` hardcode `127.0.0.1:3306`
  → toàn bộ suite `Feature` fail nếu chưa bật MySQL local + tạo DB `team_assign_test`.

## Bật model AI trong Laravel

```env
# Gian lận — PhoBERT 2 nhãn (repo AI-Services, server :8889)
TEXT_MODERATION_URL=http://127.0.0.1:8889
TEXT_MODERATION_MODE=hybrid          # rules | model | hybrid

# Xúc phạm / nhạy cảm — PhoBERT 5 nhãn (server :8890)
MODERATION_URL=http://127.0.0.1:8890
MODERATION_MODE=hybrid               # rules | model | hybrid

# Ngưỡng dùng chung PHP + Python
THRESHOLDS_FILE=G:\MyApp\laragon\www\Team-Assign\violation-detection\config\thresholds.json
```

- `rules` (mặc định khi chưa cấu hình): chỉ rule-based, **không gọi mạng**.
- `model`: chỉ dùng điểm PhoBERT. `hybrid`: rule OR model (điểm cao nhất, gộp lý do).
- Server tắt/lỗi/timeout 3 s ⇒ **tự fallback về rules** (chat không bao giờ bị chặn/treo).
- Đổi `.env` xong phải chạy `php artisan config:clear`.

### Model nhạy cảm (`:8890`) — 5 nhãn multi-label

| index | nhãn | ý nghĩa |
|---|---|---|
| 0 | `profanity` | chửi thề |
| 1 | `insult` | xúc phạm cá nhân |
| 2 | `threat` | đe doạ, uy hiếp |
| 3 | `dangerous` | nội dung nguy hiểm (bom/súng/ma tuý) |
| 4 | `adult` | nội dung 18+ |

- **Không có output "clean"**: sạch = không nhãn nào ≥ `moderation_threshold` (0.5).
- Một câu có thể ra **NHIỀU nhãn** cùng lúc (ví dụ `['insult','threat']`).
- `FlagHelper::merge($text, $image, $sensitive)` — tham số thứ 3 optional, backward compatible.
- `flag_reason` dạng: `sensitive:insult,threat (0.94)`.
- Tên nhãn đọc từ `thresholds.json` (`moderation_labels`) ⇒ đổi tên **không cần sửa code**.

> Chi tiết model (kiến trúc, bộ file, probe nhãn, FAQ): repo
> [AI-Services](https://github.com/HuangMeii/AI-Services) —
> [`MODEL_INFO.md`](https://github.com/HuangMeii/AI-Services/blob/main/MODEL_INFO.md) và
> [`docs/MODEL_NOTES-phobert.md`](https://github.com/HuangMeii/AI-Services/blob/main/docs/MODEL_NOTES-phobert.md).

