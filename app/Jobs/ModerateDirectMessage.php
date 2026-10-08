<?php

namespace App\Jobs;

use App\Models\DirectMessage;
use App\Services\ChatModerationService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

/**
 * L08 (c) — Gắn cờ tin nhắn chat 1-1 SAU khi đã trả response
 * (`ModerateDirectMessage::dispatch($id)->afterResponse()`).
 *
 * Nhờ vậy: tin nhắn hiện NGAY cho 2 bên (không chờ AI), còn cờ chỉ xuất hiện ở
 * tab "Bị gắn cờ" của admin sau ~1-2s (chấp nhận được).
 *
 * KHÔNG implement ShouldQueue: `dispatchAfterResponse()` chạy đồng bộ sau khi
 * response đã gửi đi ⇒ KHÔNG cần `queue:work` cũng có tác dụng (traffic lớn mới
 * cần worker riêng).
 */
class ModerateDirectMessage
{
    use Dispatchable;

    public function __construct(public int $messageId)
    {
    }

    public function handle(ChatModerationService $moderation): void
    {
        $message = DirectMessage::find($this->messageId);

        if (! $message) {
            return;
        }

        try {
            $message->update($moderation->analyze($message->content, $message->attachment));
        } catch (\Throwable $e) {
            // Fail-open: lỗi kiểm duyệt KHÔNG được làm hỏng bất cứ thứ gì.
            Log::warning('ModerateDirectMessage failed (fail-open): ' . $e->getMessage());
        }
    }
}
