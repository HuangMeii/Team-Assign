<?php

namespace App\Jobs;

use App\Models\ChatMessage;
use App\Services\ChatModerationService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

/**
 * L08 (c) — Gắn cờ tin nhắn chat NHÓM SAU khi đã trả response.
 * Chỉ áp dụng cho tin nhắn thường của thành viên/trưởng nhóm — tin thông báo /
 * cảnh báo của admin là nội dung hệ thống nên KHÔNG kiểm duyệt.
 *
 * KHÔNG implement ShouldQueue (xem ModerateDirectMessage).
 */
class ModerateGroupMessage
{
    use Dispatchable;

    public function __construct(public int $messageId)
    {
    }

    public function handle(ChatModerationService $moderation): void
    {
        $message = ChatMessage::find($this->messageId);

        if (! $message || $message->isAdminMessage()) {
            return;
        }

        try {
            $message->update($moderation->analyze($message->content, $message->attachment));
        } catch (\Throwable $e) {
            Log::warning('ModerateGroupMessage failed (fail-open): ' . $e->getMessage());
        }
    }
}
