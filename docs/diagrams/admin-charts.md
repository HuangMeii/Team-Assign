# Đề xuất biểu đồ cho Admin dashboard

> Hiện trạng: project **chưa dùng thư viện chart nào** (không có Chart.js trong layouts).
> Đề xuất dùng **Chart.js 4 (CDN)** — nhẹ, không build step, hợp với stack Vanilla JS hiện tại.
> Dữ liệu nguồn đã có sẵn trong DB, chỉ cần 1–2 endpoint JSON mới.

## Ưu tiên cao — Moderation & an toàn nội dung

| # | Biểu đồ | Loại | Dữ liệu nguồn | Giá trị |
|---|---------|------|---------------|---------|
| 1 | **Tin nhắn bị cờ theo ngày** (14–30 ngày) | Line/area | `direct_messages` + `chat_messages` where `is_flagged=1` group by `date(flagged_at)` | Nhìn xu hướng vi phạm, đánh giá hiệu quả ngưỡng |
| 2 | **Phân bố lý do cờ theo nhãn** | Donut | Parse `flag_reason`: `text:` (fraud) / `sensitive:profanity/insult/threat/dangerous/adult` / `image:` | Admin thấy loại vi phạm nào phổ biến nhất để tune rule/ngưỡng |
| 3 | **Histogram moderation_score** | Bar (bins 0.3–0.4–0.5–0.7–1.0) | `moderation_score` của tin bị cờ | Cân bằng giữa false positive (<0.5 nhiều) và bỏ sót |
| 4 | **Top 10 người gửi bị cờ nhiều nhất** | Horizontal bar | `ChatMessage/DirectMessage` join `users` group by `user_id` | Phát hiện tài khoản lạm dụng → khóa tài khoản |
| 5 | **Tỉ lệ xử lý cờ** (đã duyệt vs còn treo) | Stacked bar theo tuần | `flagged_at` vs `flagged_at = null` sau unflag | Đo tốc độ admin duyệt |

## Ưu tiên trung — Vận hành hệ thống

| # | Biểu đồ | Loại | Dữ liệu nguồn |
|---|---------|------|---------------|
| 6 | User hoạt động: **đăng nhập/tin nhắn theo ngày** | Line | `users.last_login` (nếu chưa có thì track), `messages.created_at` |
| 7 | **Tài liệu người dùng theo vai trò + trạng thái** | Donut | `users.role` + `is_active` (đã có sẵn logic trong AdminController) |
| 8 | **Tin nhắn gửi theo giờ trong ngày** (heat) | Bar 24 cột | `created_at` giờ — phân bố giờ cao điểm |
| 9 | **Số lần model fallback về rules** (server AI tắt) | Line theo ngày | log warning của `SensitiveModerationService` / `TextModerationService` — cần 1 bảng `moderation_events` nhỏ |

## Ưu tiên thấp — Học vụ (nếu hoàn thiện StatisticsController)

| # | Biểu đồ | Loại | Dữ liệu nguồn |
|---|---------|------|---------------|
| 10 | Đề tài theo trạng thái (trống/đã có nhóm/đã duyệt) | Donut | `topics` |
| 11 | Yêu cầu đăng ký đề tài: Pending/Approved/Rejected | Stacked bar | `topic_requests` |
| 12 | Số nhóm có/không đề tài theo lớp học phần | Bar | `groups` + `class_sections` |

## Bố cục đề xuất cho trang `admin/dashboard`

```
+--------------------------------------------------------------+
| 4 KPI card: Tin bị cờ hôm nay | Chờ duyệt | User mới | Đề tài |
+------------------------------+-------------------------------+
| (1) Line: cờ theo ngày       | (2) Donut: loại vi phạm       |
+------------------------------+-------------------------------+
| (4) Top 10 user bị cờ        | (5) Tỉ lệ xử lý cờ            |
+------------------------------+-------------------------------+
```

## Endpoint JSON đề xuất (1 controller mới)
```php
Route::get('admin/stats/moderation', [AdminStatsController::class, 'moderation']);   // #1 #2 #3
Route::get('admin/stats/top-offenders', [AdminStatsController::class, 'topOffenders']); // #4
Route::get('admin/stats/resolution', [AdminStatsController::class, 'resolution']);   // #5
```
- Nên cache 5–10 phút (`Cache::remember`) vì dữ liệu chỉ cần near-realtime.
- Parse `flag_reason` an toàn: prefix trước `:` là loại cờ (`text|image|sensitive`), nhãn là phần giữa hai dấu phẩy.
