# Sơ đồ hoạt động — Luồng gửi tin nhắn & kiểm duyệt (flag-only)

> Áp dụng cho cả chat 1-1 (`DirectChatController::send`) và chat nhóm (`GroupsChatController::sendMessage`).
> Nguyên tắc: **KHÔNG bao giờ chặn gửi** — mọi vi phạm chỉ gắn cờ cho admin duyệt.

```mermaid
flowchart TD
    A([Người dùng soạn tin nhắn]) --> B{Có nội dung / ảnh?}
    B -- trống cả hai --> Z([Báo lỗi validate])
    B -- có --> C[Validate: content max 2000/1000,<br/>attachment image max 5MB]

    C --> D[TextModerationService::check<br/>Gian lận: rules CSV]
    C --> E[SensitiveModerationService::check<br/>Nhạy cảm: rules + PhoBERT 5 nhãn]
    C --> F{Có ảnh đính kèm?}

    E --> E1{Mode?}
    E1 -- rules --> E2[Chỉ rules từ điển]
    E1 -- model --> E3[Gọi FastAPI :8890<br/>timeout 3s]
    E1 -- hybrid --> E2 --> E3
    E3 -- lỗi/tắt/timeout --> E2

    F -- có --> G[Lưu ảnh storage<br/>ImageModerationService<br/>Cloud Vision :8888]
    G -- Vision lỗi --> H[fail-open: bỏ qua, không cờ ảnh]
    G -- Vision OK --> I[passed / violations]

    D --> M[FlagHelper::merge text, image, sensitive]
    E2 --> M
    E3 --> M
    H --> M
    I --> M

    M --> N{Có vi phạm nào?}
    N -- không --> O[is_flagged = null<br/>Lưu + broadcast bình thường]
    N -- có --> P[flag_reason =<br/>text:... \| image:... \| sensitive:...<br/>moderation_score = max<br/>flagged_at = now]

    P --> Q[Lưu DB + tăng unread<br/>Broadcast Reverb<br/>fail-open: Reverb chết vẫn gửi]
    O --> Q
    Q --> R([Admin xem tab Bị gắn cờ<br/>→ Bỏ cờ hoặc Xóa])
```

## L08 — nhánh BẤT ĐỒNG BỘ (hiện tin trước, gắn cờ sau)

```mermaid
activityDiagram
    start
    :validate + lưu ảnh (local disk)
    :INSERT tin SẠCH (is_flagged = false)
    :tăng unread + broadcast Reverb
    :TRẢ RESPONSE cho client (tin hiện ngay)
    :container->terminating() → job afterResponse()
    :ChatModerationService::analyze()
    if (Http::pool: 8889 + 8890 [+ 8888 nếu có ảnh]) then (đồng thời)
        :parse từng response (fail-open nếu lỗi/timeout)
    endif
    :FlagHelper::merge()
    :UPDATE 4 cột cờ (is_flagged, flag_reason, moderation_score, flagged_at)
    stop
```

- 3 bộ lọc chạy **song song** (`Http::pool()`); timeout 2s/2s/5s; server AI chết ⇒ rơi về rules (fail-open).
- Job **không** `ShouldQueue` ⇒ `dispatch()->afterResponse()` chạy sau khi response đã gửi — không cần `queue:work`.
- Tin nhắn của admin (`announcement` / `warning`) **không** bị kiểm duyệt/gắn cờ.

## Nhãn của model moderation (PhoBERT 5 nhãn multi-label)
| index | nhãn | ví dụ |
|---|---|---|
| 0 | `profanity` | chửi thề |
| 1 | `insult` | "ngu như chó, im mồm" |
| 2 | `threat` | "tao sẽ giết mày" |
| 3 | `dangerous` | bom/súng/ma túy |
| 4 | `adult` | nội dung 18+ |

- Model **không có output "clean"** — sạch = không nhãn nào ≥ `moderation_threshold` (0.5).
- Một câu có thể ra **nhiều nhãn** → `flag_reason`: `sensitive:insult,threat (0.94)`.
- Ngưỡng chỉnh trong `violation-detection/config/thresholds.json` (PHP + Python đọc chung).
