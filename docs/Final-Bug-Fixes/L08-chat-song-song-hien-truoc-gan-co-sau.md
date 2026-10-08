# L08 — Chat song song + hien truoc gan co sau (giu du 3 loc)

> Thu muc: `docs/Final-Bug-Fixes/` — **TRANG THAI: DA TRIEN KHAI (2026-10-08).**
> Quyet dinh da chot: lam **(a) song song + (c) hien truoc gan co sau**, **BO (b)** (khong short-circuit rules — moi tin deu qua du 3 check).

## 1. Hien trang (da khao sat code)

- 2 luong gui (`DirectChatController::send` dong 291-320, `GroupChatService::send` dong 92-101) goi **noi tiep**: `TextModeration (8889) → SensitiveModeration (8890) → Vision (8888, neu co anh)` roi moi `FlagHelper::merge → INSERT → tang unread → broadcast → tra JSON`.
- Timeout cung: text/sensitive `Http::timeout(3)`, Vision `timeout(15)` / `timeout(20)`.
- `.env` dang `TEXT_MODERATION_MODE=hybrid`, `MODERATION_MODE=hybrid` → luc nao cung goi model PhoBERT CPU (cham) thay vi chi rules offline.
- Chinh sach flag-only (`FlagHelper.php:8` — NEVER block): kiem duyet chi de dien `is_flagged / flag_reason / moderation_score / flagged_at` cho admin xem tab "Bi gan co".
- Frontend (`groups/chat.blade.php:224-258` + chat 1-1) `fetch()` **cho response moi coi la gui xong** (khong co optimistic UI).
- DB tro len Aiven cloud (latency quoc te) + broadcast `ShouldBroadcastNow` dong bo trong request.

## 2. Muc tieu

- Giu du 3 loc: gian lan text (8889), nhay cam (8890), anh (8888). Khong mat chuc nang nao; tab "Bi gan co" cua admin van day du (chi muon vai giay).
- Nhanh hon bang (a) + (c); khong lam (b) theo yeu cau user.

## 3. (a) Song song hoa 3 check bang Http::pool()

- Tin chu: goi dong thoi fraud + sensitive; tin co anh: goi them vision cung luc. Thoi gian = `max` thay vi `tong` (~-50%).
- Giam timeout text `3s → 1-2s`, Vision `15/20s → 3-5s`; fail-open giu nguyen (loi/timeout → cho qua + log).
- File sua: `violation-detection/src/TextModerationService.php`, `SensitiveModerationService.php` (tach ham goi HTTP de dung trong pool), `app/Services/ImageModerationService.php` (timeout).

## 4. (c) Hien truoc gan co sau (async, sau response)

- Request chinh chi lam: `validate → store anh (neu co, local disk, nhanh) → INSERT tin sach (is_flagged=false) → bump unread (1 query batch) → broadcast tin → dispatch job ->afterResponse() → tra JSON` (target <200ms).
- 2 job moi: `ModerateDirectMessage` + `ModerateGroupMessage` (nhan `message_id`): chay pool AI (a) → `FlagHelper::merge()` → `UPDATE` 4 cot co (`is_flagged, flag_reason, moderation_score, flagged_at`). Job fail → bo qua + log (fail-open).
- Dung `dispatch(...)->afterResponse()` → khong can dung worker van co tac dung (job chay sau khi da tra response); traffic lon moi can `queue:work` rieng.
- Broadcast tin nhan giu nguyen (van hien ngay cho 2 ben); admin thay co muon vai giay o tab "Bi gan co" (chap nhan duoc). Tuy chon: them event nhe `MessageFlagUpdated` cho realtime tab admin (khong bat buoc).
- File sua/them: `app/Jobs/ModerateDirectMessage.php`, `app/Jobs/ModerateGroupMessage.php`, `DirectChatController::send`, `GroupChatService::send` (+ `bumpUnreadCounters` gop 1 query).

## 5. Test + Docs + Verify (khi trien khai)

- Test lai: `*Moderation*`, `ChatUnreadBadgeTest`, `GroupChatPollingTest`, `ChatMessageStatusTest`; them case: tin hien ngay chua co → job chay xong co co (`Queue::fake()` assert dispatched + 1 case `perform()` that assert DB co co); response khong doi chu ky nen frontend cu van chay.
- Docs: cap nhat `docs/diagrams/sequence-chat.md`, `activity-moderation.md` (ve nhanh async), `FEATURE_STATUS #13`, test-case `07-kiem-duyet-noi-dung.md`.
- Verify: do latency truoc/sau (target: tin chu ~0.2s hien tin; co gan sau ~1-2s; tin kem anh ~0.2s hien anh), `php artisan test` nhom chat + moderation.

## 6. Rui ro / ghi chu

- Khong lam (b) nen moi tin van ton chi phi goi AI (du da song song + async) — doi lai dam bao du 3 loc nhu yeu cau.
- Can kiem tra `QUEUE_CONNECTION` hien `database` + bang `jobs` ton tai neu muon chay worker that; tam thoi `afterResponse()` la du.
- Event `MessageFlagUpdated` (neu lam) phai phan quyen kenh admin de khong lo flag cho user thuong.

## 7. Ket qua trien khai (2026-10-08)

### 7.1 (a) Song song hoa 3 check

| File | Thay doi |
|------|----------|
| `violation-detection/src/TextModerationService.php` | Tach `needsModel()`, `parseModelResponse()`, `combineWithModel()`, `timeout()` (3s → 2s, cấu hình `services.text_moderation.timeout`); `check()` chỉ còn là đường tuần tự dùng chung logic |
| `violation-detection/src/SensitiveModerationService.php` | Tương tự (+ `mode()` chuyển sang public, timeout 3s → 2s) |
| `app/Services/ImageModerationService.php` | Thêm `requestSpec()` (url + payload), `parseResponse()`, `timeout()` (15s → 5s) |
| `app/Services/ChatModerationService.php` **(mới)** | `analyze($content, $imagePath)`: rules offline + `Http::pool()` cho phần cần gọi mạng (8889 + 8890 + 8888 **đồng thời**) → gộp bằng `FlagHelper::merge()` |
| `config/services.php` | Thêm `timeout` cho `vision` / `text_moderation` / `content_moderation` |

Bắt buộc giữ **fail-open**: `Http::pool()` gọi `wait()` từng promise nên 1 server chết sẽ ném
`ConnectionException` ra khỏi pool ⇒ `runPool()` bắt lại, coi như "không có kết quả model" và rơi về rules.

### 7.2 (c) Hien truoc — gan co sau

| File | Thay doi |
|------|----------|
| `app/Jobs/ModerateDirectMessage.php` **(mới)** | `handle()`: đọc lại tin → `ChatModerationService::analyze()` → `update()` 4 cột cờ; fail-open |
| `app/Jobs/ModerateGroupMessage.php` **(mới)** | Như trên cho tin nhắn nhóm; **bỏ qua** tin `announcement/warning` của admin |
| `app/Http/Controllers/DirectChatController.php` | `send()`: bỏ 3 check đồng bộ; INSERT tin **sạch** (kèm lưu ảnh local) → bump unread → broadcast → `ModerateDirectMessage::dispatch($id)->afterResponse()` |
| `app/Services/GroupChatService.php` | `send()`: tương tự; chỉ dispatch job khi `!$isAdminMessage` |

- Job **KHÔNG** implement `ShouldQueue`: `dispatchAfterResponse()` chạy đồng bộ SAU khi response đã gửi
  (`PendingDispatch::__destruct` → `Bus\Dispatcher::dispatchAfterResponse()` → `container->terminating()` → `dispatchSync`)
  ⇒ **không cần `queue:work`** là vẫn có tác dụng, đúng như kế hoạch.
- Nhờ vậy tin nhắn hiện NGAY cho 2 bên; cờ xuất hiện ở tab "Bị gắn cờ" sau ~1–2s.

### 7.3 Kiem chung

```powershell
php artisan test tests/Feature/ChatModerationAsyncTest.php   # 5 passed
php artisan test                                             # 392 passed / 0 failed
```

- Test mới `tests/Feature/ChatModerationAsyncTest.php`: tin hiện ngay + job được hẹn SAU response
  (`Bus::fake()` + `assertDispatchedAfterResponse`); `handle()` gắn đủ 4 cột cờ (1-1 và nhóm); tin admin không bị gắn cờ;
  1 lần gửi ảnh gọi đủ 3 endpoint (8889/8890/8888); server AI chết ⇒ tin vẫn gửi + không gắn cờ.
- **Tương thích ngược**: 48 test chat/kiểm duyệt cũ vẫn xanh vì môi trường test có `QUEUE_CONNECTION=sync`
  nên job `afterResponse()` chạy trong `$kernel->terminate()` **trước khi** test khẳng định cờ.

## 8. Rủi ro CHẤP NHẬN (đã cân nhắc, giữ nguyên — ghi để minh bạch)

Đây là các hạn chế CÒN LẠI của L08 sau khi rà soát hardening (Bó-4):

1. **`Http::pool()` là all-or-nothing**: nếu 1 trong 3 server AI không kết nối được thì `wait()` ném
   `ConnectionException` ra khỏi pool ⇒ `runPool()` bắt và coi như **mất kết quả model của TẤT CẢ** server
   (chỉ còn rules). Muốn cô lập từng request thì dùng `Http::batch()` / Guzzle `EachPromise` + catch riêng
   từng promise — chưa làm vì bộ rules đã chặn được các mẫu fraud phổ biến.
2. **Cờ gắn sau response phụ thuộc `container->terminating()`**: nếu PHP process kết thúc trước khi callback
   chạy (client ngắt kết nối + `ignore_user_abort=off`, hoặc `php artisan serve` 1 worker) thì tin đó
   **không bao giờ được gắn cờ** (im lặng, chỉ thấy thiếu trong tab "Bị gắn cờ"). Production nên dùng
   PHP-FPM/Octane; lượng chat lớn thì cân nhắc `queue:work`.
3. **Fail-open nuốt MỌI `Throwable`** (kể cả bug lập trình của chính mình) — chỉ `Log::warning`, dễ bỏ sót.
   Khi cần siết lại: chỉ catch `ConnectionException`/`RequestException`.
