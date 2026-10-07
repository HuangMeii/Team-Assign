# L08 — Chat song song + hien truoc gan co sau (giu du 3 loc)

> Thu muc: `docs/Final-Bug-Fixes/` — ke hoach sua sau (chua trien khai code).
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
