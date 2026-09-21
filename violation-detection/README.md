# Violation detection (flag-only)

Má»i vi pháº¡m **chá»‰ gáº¯n cá», khÃ´ng cháº·n gá»­i**: tin nháº¯n/áº£nh váº«n Ä‘Æ°á»£c lÆ°u vÃ  broadcast
bÃ¬nh thÆ°á»ng, chá»‰ thÃªm `is_flagged = 1` Ä‘á»ƒ admin duyá»‡t á»Ÿ tab **Bá»‹ gáº¯n cá»**.

## Layout

- `src/` â€” **logic PHP dÃ¹ng tháº­t trong app**, autoload qua `composer.json`:
  `"App\\Services\\ViolationDetection\\": "violation-detection/src/"`.
  - `src/TextModerationService.php` â€” rule-based tá»« dataset, tráº£ `{is_violation, score, reasons}`.
  - `src/FlagHelper.php` â€” gá»™p cá» text + áº£nh thÃ nh `is_flagged / flag_reason / moderation_score / flagged_at`.
- `config/thresholds.json` â€” `text_threshold` (flag), `text_review_threshold`.
- `datasets/chat_fraud_dataset.csv` â€” dataset text. Header thá»±c táº¿ `,text,label,,`
  (cá»™t 0 trá»‘ng, cá»™t 1 = text, cá»™t 2 = label; `1` = fraud). Thá»‘ng kÃª hiá»‡n táº¡i:
  **5151 dÃ²ng cÃ³ ná»™i dung / 2560 fraud / 2500 sáº¡ch / 91 khÃ´ng nhÃ£n**.
- `tools/extract_keywords.php` â€” CLI trÃ­ch top cá»¥m fraud tá»« CSV (chá»‰ cháº¡y tay, khÃ´ng runtime).
- `python/app.py` â€” FastAPI serve model PhoBERT Ä‘Ã£ fine-tune (port 8889, `POST /predict {text}`).
  Chi tiáº¿t nhÃ£n/ngÆ°á»¡ng/van an toÃ n: `python/MODEL_NOTES.md`.

## CÃ¡ch hoáº¡t Ä‘á»™ng

1. `TextModerationService::check($content)` â†’ Ä‘iá»ƒm 0..1 + `reasons`.
2. `ImageModerationService::checkStoredImage($path)` (Cloud Vision, fail-open khi server táº¯t).
3. `FlagHelper::merge(text, image)` â†’ 4 cá»™t flag; Controller `array_merge` vÃ o dá»¯ liá»‡u tin nháº¯n.
4. Controller **khÃ´ng bao giá»** xoÃ¡ file áº£nh hay tráº£ 422 vÃ¬ lÃ½ do moderation.

Ãp dá»¥ng cho `DirectChatController::send` (chat 1-1) vÃ  `GroupsChatController::sendMessage` (chat nhÃ³m).

## Quy Æ°á»›c flag

- `flag_reason` vÃ­ dá»¥: `text:bÃ¡n slot,tiá»n + liÃªn há»‡ riÃªng (telegram) (0.85) | image:adult VERY_LIKELY`.
- `moderation_score`: Ä‘iá»ƒm cao nháº¥t; áº£nh bá»‹ Vision gáº¯n cá» luÃ´n >= 0.9.
- Admin: `admin/chat-monitor?tab=flagged` (+ filter `min_score`), nÃºt **Bá» cá»**
  (`admin.chat.direct.unflag` / `admin.chat.group.unflag`) hoáº·c **XÃ³a**.

## TrÃ­ch láº¡i luáº­t tá»« dataset

```bash
php violation-detection/tools/extract_keywords.php
php violation-detection/tools/extract_keywords.php violation-detection/datasets/chat_fraud_dataset.csv 40
```

Output lÃ  cÃ¡c cá»¥m fraud cÃ³ tá»‰ lá»‡ xuáº¥t hiá»‡n cao hÆ¡n háº³n nhÃ³m sáº¡ch (top hiá»‡n táº¡i:
`lam ho`, `dap an`, `slot`, `gia N`, `trieu ib`, `chuyen khoan`, `tai khoan`...)
â†’ dÃ¹ng Ä‘á»ƒ cáº­p nháº­t `STRONG_RULES` / `CONTACT_TOKENS` trong `src/TextModerationService.php`.

## Test

```bash
# Logic thuáº§n (text + FlagHelper) â€” KHÃ”NG cáº§n DB, cháº¡y Ä‘Æ°á»£c má»i lÃºc
php artisan test tests/Unit/ViolationDetectionTest.php

# Luá»“ng HTTP + admin â€” cáº§n MySQL test (xem phpunit.xml: DB_HOST/DB_PORT/DB_DATABASE)
php artisan test tests/Feature/Services/ViolationDetectionTest.php
```

- `tests/Unit/ViolationDetectionTest.php`: text fraud/sáº¡ch, viáº¿t hoa khÃ´ng dáº¥u, Ä‘e doáº¡ tá»‘ng tiá»n,
  ngÆ°á»¡ng tá»« config, `FlagHelper` merge + chuáº©n hoÃ¡ `violations` (8 test / 35 assertion â€” Ä‘Ã£ pass).
- `tests/Feature/Services/ViolationDetectionTest.php`: chat nhÃ³m/1-1 pháº£i **váº«n gá»­i Ä‘Æ°á»£c** vÃ  chá»‰ gáº¯n cá»,
  áº£nh Vision fail **khÃ´ng bá»‹ xoÃ¡**, admin xem tab **Bá»‹ gáº¯n cá»** + bá» cá».
- LÆ°u Ã½ mÃ´i trÆ°á»ng: `.env` dev trá» MySQL remote, cÃ²n `phpunit.xml` hardcode `127.0.0.1:3306`
  â†’ toÃ n bá»™ suite `Feature` fail náº¿u chÆ°a báº­t MySQL local + táº¡o DB `team_assign_test`.


## Phase 2 (PhoBERT) â€” ÄÃƒ CHáº Y

Model fine-tune sáºµn (Colab): `G:\MyApp\laragon\www\AI-Services\phobert-negative-classifier`
(RobertaForSequenceClassification, 2 nhÃ£n â€” **0 = bÃ¬nh thÆ°á»ng, 1 = gian láº­n**).

Chi tiáº¿t nhÃ£n/ngÆ°á»¡ng/van an toÃ n: xem [`python/MODEL_NOTES.md`](./python/MODEL_NOTES.md).

### CÃ i + cháº¡y server
```powershell
conda create -n phobert --override-channels -c conda-forge python=3.11 -y
G:\MyApp\miniconda3\envs\phobert\python.exe -m pip install torch --index-url https://download.pytorch.org/whl/cpu
G:\MyApp\miniconda3\envs\phobert\python.exe -m pip install -r violation-detection/python/requirements.txt

cd violation-detection/python
G:\MyApp\miniconda3\envs\phobert\python.exe -m uvicorn app:app --host 127.0.0.1 --port 8889
```

Kiá»ƒm tra:
```powershell
curl.exe -s http://127.0.0.1:8889/health
# PowerShell: dùng Invoke-RestMethod — dạng curl -d "{\"text\":...}" sẽ lỗi JSON trong PowerShell
Invoke-RestMethod -Uri http://127.0.0.1:8889/predict -Method Post -ContentType 'application/json' -Body '{"text":"Ban slot de tai gia 200k ib telegram"}'
```

### Báº­t model trong Laravel
```env
TEXT_MODERATION_URL=http://127.0.0.1:8889
TEXT_MODERATION_MODE=hybrid      # rules | model | hybrid
```
- `rules` (máº·c Ä‘á»‹nh khi chÆ°a cáº¥u hÃ¬nh): chá»‰ rule-based, khÃ´ng gá»i máº¡ng.
- `model`: chá»‰ dÃ¹ng Ä‘iá»ƒm PhoBERT.
- `hybrid`: rule OR model (Ä‘iá»ƒm cao nháº¥t, gá»™p lÃ½ do).
- Server táº¯t/lá»—i/timeout 3s â‡’ **tá»± fallback vá» rules** (chat khÃ´ng bao giá» bá»‹ cháº·n/treo).
- Äá»•i `.env` xong pháº£i cháº¡y `php artisan config:clear`.

### Test
```powershell
php artisan test tests/Unit/ViolationDetectionTest.php
```
Bao gá»“m 5 test Phase 2 dÃ¹ng `Http::fake()`: model bÃ¡o vi pháº¡m Â· model bÃ¡o sáº¡ch Â·
server cháº¿t â†’ fallback rules Â· hybrid láº¥y Ä‘iá»ƒm cao nháº¥t Â· `rules` khÃ´ng gá»i máº¡ng.

NguyÃªn táº¯c Phase 1 váº«n giá»¯ nguyÃªn: `check()` náº¿u cÃ³ URL thÃ¬ gá»i HTTP, `phase 1`
rule-based váº«n lÃ  fallback vÃ  váº«n dÃ¹ng Ä‘Æ°á»£c Ä‘á»™c láº­p. Shape `{is_violation, score,
reasons}` khÃ´ng Ä‘á»•i nÃªn Controller/Admin khÃ´ng pháº£i sá»­a láº¡i.

---

## Phase 3 (PhoBERT v2 â€” XÃšC PHáº M / Ná»˜I DUNG NHáº Y Cáº¢M) â€” ÄÃƒ CHáº Y

Model fine-tune riÃªng: `G:\MyApp\laragon\www\AI-Services\chat_moderation_model`
(RobertaForSequenceClassification, **5 nhÃ£n multi-label** â€” sigmoid tá»«ng nhÃ£n):

| index | nhÃ£n |
|---|---|
| 0 | `profanity` (chá»­i thá») |
| 1 | `insult` (xÃºc pháº¡m) |
| 2 | `threat` (Ä‘e doáº¡) |
| 3 | `dangerous` (ná»™i dung nguy hiá»ƒm) |
| 4 | `adult` (ná»™i dung 18+) |

Model **khÃ´ng cÃ³ output "clean"**: clean = khÃ´ng nhÃ£n nÃ o â‰¥ `moderation_threshold`.
Má»™t cÃ¢u cÃ³ thá»ƒ ra **NHIá»€U nhÃ£n** cÃ¹ng lÃºc (vd `['insult','threat']`).
Chi tiáº¿t: [`python/MODEL_NOTES.md`](./python/MODEL_NOTES.md).

### Cháº¡y server (port 8890, tÃ¡ch khá»i server fraud 8889)
```powershell
cd violation-detection/python
G:\MyApp\miniconda3\envs\phobert\python.exe -m uvicorn app_moderation:app --host 127.0.0.1 --port 8890
```

### Báº­t trong Laravel
```env
MODERATION_URL=http://127.0.0.1:8890
MODERATION_MODE=hybrid      # rules | model | hybrid
```
- Service: `src/SensitiveModerationService.php` â€” rules tá»« Ä‘iá»ƒn tiáº¿ng Viá»‡t offline +
  gá»i model (timeout 3s, fail-open vá» rules khi server táº¯t).
- `FlagHelper::merge($text, $image, $sensitive)` â€” tham sá»‘ thá»© 3 optional, backward compatible.
- `flag_reason` dáº¡ng: `sensitive:insult,threat (0.94)`.
- NgÆ°á»¡ng + tÃªn nhÃ£n: `config/thresholds.json` (`moderation_threshold`,
  `moderation_review_threshold`, `moderation_labels`, `moderation_positive_labels`)
  â€” PHP vÃ  Python Ä‘á»c chung má»™t file nÃªn tune khÃ´ng cáº§n sá»­a code.

### Test
```powershell
php artisan test tests/Unit/SensitiveModerationTest.php          # logic thuáº§n, khÃ´ng cáº§n DB
php artisan test tests/Feature/Services/SensitiveModerationChatTest.php  # cáº§n MySQL test
```
14 test (10 unit + 4 feature) â€” Ä‘Ã£ pass.



