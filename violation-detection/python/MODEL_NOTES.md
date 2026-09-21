# Model PhoBERT phÃ¢n loáº¡i gian láº­n â€” ghi chÃº tÃ­ch há»£p

> 📚 Bản thông tin model đầy đủ (kiến trúc, bộ file, tokenizer, tài nguyên, FAQ) nằm ở
> [`AI-Services/MODEL_INFO.md`](../../../AI-Services/MODEL_INFO.md). File này chỉ giữ
> **contract tích hợp + cách vận hành**.

## Model
| Má»¥c | GiÃ¡ trá»‹ |
|---|---|
| ThÆ° má»¥c | `G:\MyApp\laragon\www\AI-Services\phobert-negative-classifier` (náº¡p qua env `TEXT_MODEL_DIR`) |
| Kiáº¿n trÃºc | `RobertaForSequenceClassification` (PhoBERT base: 12 layer, hidden 768) |
| Sá»‘ nhÃ£n | 2 (`classifier.out_proj.weight` shape `[2, 768]`) |
| NhÃ£n | **0 = bÃ¬nh thÆ°á»ng**, **1 = gian láº­n** (do ngÆ°á»i huáº¥n luyá»‡n xÃ¡c nháº­n) |
| Tokenizer | `PhobertTokenizer` (`vocab.txt` 64001 + `bpe.codes`) |
| `max_position_embeddings` | 258 (dÃ¹ng `max_len = 256`) |
| Huáº¥n luyá»‡n | TrÃªn Colab, `output_dir=./results` trong `training_args.bin`; model lÆ°u báº±ng transformers 5.16.1 |
| KÃ¨m theo | KhÃ´ng cÃ³ `id2label`/`label2id`, khÃ´ng cÃ³ metrics.json â‡’ index nhÃ£n láº¥y theo xÃ¡c nháº­n cá»§a ngÆ°á»i train (`TEXT_MODEL_POSITIVE_INDEX=1`) |

## Server
- File: `violation-detection/python/app.py` (FastAPI), port **8889**.
- CÃ i Ä‘áº·t: xem `violation-detection/python/requirements.txt`.
- Cháº¡y:
  ```powershell
  cd g:\MyApp\laragon\www\Team-Assign\violation-detection\python
  conda run -n phobert uvicorn app:app --host 127.0.0.1 --port 8889
  ```
- Kiá»ƒm tra: `GET /health` (bÃ¡o model_dir, positive_index, threshold, lá»—i náº¿u cÃ³) Â· `POST /predict {"text": "..."}`.

## Contract `/predict` (giá»¯ nguyÃªn nhÆ° báº£n PHP rule-based)
```json
{
  "is_violation": true,
  "score": 0.93,
  "reasons": ["phobert-fraud@phobert-negative-classifier (0.93)"],
  "needs_review": false,
  "skipped": false,
  "model": "phobert-fraud@phobert-negative-classifier",
  "latency_ms": 42.5
}
```

## Ná»‘i vÃ o Laravel
`.env`:
```env
TEXT_MODERATION_URL=http://127.0.0.1:8889
TEXT_MODERATION_MODE=rules      # rules | model | hybrid
```
- `rules` (máº·c Ä‘á»‹nh): chá»‰ dÃ¹ng rule-based, **khÃ´ng gá»i máº¡ng**.
- `model`: chá»‰ dÃ¹ng Ä‘iá»ƒm PhoBERT.
- `hybrid`: rule OR model (láº¥y Ä‘iá»ƒm cao nháº¥t, gá»™p lÃ½ do) â€” giá»¯ recall cá»§a rules.
- Server táº¯t/lá»—i/timeout 3s â‡’ **tá»± fallback vá» rules**, chat khÃ´ng bá»‹ cháº·n/treo.
- Sau khi Ä‘á»•i `.env` cáº§n `php artisan config:clear`.

## NgÆ°á»¡ng & van an toÃ n (Ä‘á»•i khÃ´ng cáº§n sá»­a code)
| Cáº§n gÃ¬ | Sá»­a á»Ÿ Ä‘Ã¢u |
|---|---|
| Model gáº¯n cá» quÃ¡ nhiá»u / quÃ¡ Ã­t | `violation-detection/config/thresholds.json` â†’ `text_model_threshold` (máº·c Ä‘á»‹nh 0.5) |
| ÄÃ¡nh dáº¥u "cáº§n xem láº¡i" | cÃ¹ng file â†’ `text_review_threshold` (0.35) |
| Nghi ngá» Ä‘áº£o nhÃ£n (cÃ¢u sáº¡ch bá»‹ Ä‘iá»ƒm cao) | env `TEXT_MODEL_POSITIVE_INDEX=0` khi cháº¡y server |
| Cháº¥t lÆ°á»£ng tÃ¡ch tá»« | env `TEXT_MODEL_SEGMENT=pyvi` (PhoBERT vá»‘n train trÃªn vÄƒn báº£n Ä‘Ã£ tÃ¡ch tá»«) |
| Äá»•i sang model khÃ¡c | env `TEXT_MODEL_DIR=<thÆ° má»¥c model má»›i>` + restart server |

## NguyÃªn táº¯c
Chá»‰ **gáº¯n cá»** (`is_flagged`, `flag_reason`, `moderation_score`, `flagged_at`) Ä‘á»ƒ admin

---

# Model PhoBERT v2 phÃ¢n loáº¡i XÃšC PHáº M / Ná»˜I DUNG NHáº Y Cáº¢M (Phase 3)

## Model
| Má»¥c | GiÃ¡ trá»‹ |
|---|---|
| ThÆ° má»¥c | `G:\MyApp\laragon\www\AI-Services\chat_moderation_model` (náº¡p qua env `MODERATION_MODEL_DIR`) |
| Kiáº¿n trÃºc | `RobertaForSequenceClassification` (PhoBERT) |
| Sá»‘ nhÃ£n | 5, **multi_label_classification** â‡’ dÃ¹ng **sigmoid tá»«ng nhÃ£n** (KHÃ”NG softmax) |
| NhÃ£n | **0 = profanity**, **1 = insult**, **2 = threat**, **3 = dangerous**, **4 = adult** |
| "clean" | Model KHÃ”NG cÃ³ output "clean" â€” clean = khÃ´ng nhÃ£n nÃ o â‰¥ `moderation_threshold` |
| Multi-hit | Má»™t cÃ¢u cÃ³ thá»ƒ ra NHIá»€U nhÃ£n cÃ¹ng lÃºc (vd `['insult','threat']`) |
| Kiá»ƒm chá»©ng | Thá»© tá»± index Ä‘Ã£ probe thá»±c táº¿ trÃªn chÃ­nh model nÃ y (insult 0.994, threat 0.993, dangerous 0.993, profanity 0.993, clean â‰ˆ0.001) |

## Server
- File: `violation-detection/python/app_moderation.py` (FastAPI), port **8890** (tÃ¡ch khá»i server fraud 8889).
- Cháº¡y:
  ```powershell
  cd g:\MyApp\laragon\www\Team-Assign\violation-detection\python
  G:\MyApp\miniconda3\envs\phobert\python.exe -m uvicorn app_moderation:app --host 127.0.0.1 --port 8890
  ```
- `GET /health` (bÃ¡o labels, threshold, lá»—i náº¿u cÃ³) Â· `POST /predict {"text": "..."}`.

## Ná»‘i vÃ o Laravel
`.env`:
```env
MODERATION_URL=http://127.0.0.1:8890
MODERATION_MODE=hybrid           # rules | model | hybrid
```
- Service: `violation-detection/src/SensitiveModerationService.php` (`SensitiveModerationService::check()`),
  Ä‘Æ°á»£c gá»i trong `DirectChatController::send()` + `GroupsChatController::sendMessage()`.
- `FlagHelper::merge()` nháº­n thÃªm tham sá»‘ thá»© 3 `$sensitiveCheck` (backward compatible);
  flag_reason dáº¡ng `sensitive:insult,threat (0.94)`.

## NgÆ°á»¡ng (violation-detection/config/thresholds.json â€” Ä‘á»c chung PHP + Python)
| KhoÃ¡ | Ã nghÄ©a |
|---|---|
| `moderation_threshold` (0.5) | max Ä‘iá»ƒm nhÃ£n â‰¥ ngÆ°á»¡ng â‡’ gáº¯n cá» |
| `moderation_review_threshold` (0.35) | dÆ°á»›i ngÆ°á»¡ng cá» nhÆ°ng â‰¥ ngÆ°á»¡ng nÃ y â‡’ needs_review |
| `moderation_labels` | index â‡’ tÃªn nhÃ£n (Ä‘áº£o/tune khÃ´ng cáº§n sá»­a code) |
| `moderation_positive_labels` | nhÃ£n nÃ o Ä‘Æ°á»£c tÃ­nh lÃ  vi pháº¡m |

LÆ°u Ã½: nhÃ£n `adult` cá»§a model hiá»‡n cÃ²n yáº¿u (cÃ¢u 18+ rÃµ rÃ ng chá»‰ ~0.09â€“0.3) â€” náº¿u cáº§n recall
cao hÆ¡n cho 18+ thÃ¬ giáº£m `moderation_threshold` hoáº·c bá»• sung rule `adult` trong
`SensitiveModerationService::RULES`.

## NguyÃªn táº¯c
Giá»‘ng Phase 2: fail-open vá» rules khi server táº¯t/lá»—i, flag-only, khÃ´ng cháº·n gá»­i.

duyá»‡t á»Ÿ `admin/chat-monitor?tab=flagged`. KhÃ´ng cháº·n gá»­i, khÃ´ng xoÃ¡ áº£nh, khÃ´ng tráº£ 422.
