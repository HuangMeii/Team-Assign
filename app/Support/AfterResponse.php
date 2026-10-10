<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Bó tối ưu D4 — Broadcast SAU khi response đã gửi.
 *
 * Các event chat/bảng tin/thông báo đều ShouldBroadcastNow ⇒ mặc định mỗi
 * request phải CHỜ HTTP publish tới Reverb trước khi trả HTML/JSON cho client.
 * Bọc qua đây, việc publish chạy trong phase terminating (giống
 * dispatch()->afterResponse() của L08) ⇒ client thấy response ngay, không cần
 * queue worker. Lỗi publish được nuốt + log warning (fail-open, chat không vỡ).
 */
final class AfterResponse
{
    public static function broadcast(object $event): void
    {
        app()->terminating(static function () use ($event): void {
            try {
                broadcast($event);
            } catch (\Throwable $e) {
                Log::warning(sprintf(
                    'Broadcast after-response failed (%s): %s',
                    $event::class,
                    $e->getMessage()
                ));
            }
        });
    }
}