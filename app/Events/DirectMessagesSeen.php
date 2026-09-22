<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Người nhận đã MỞ XEM tin nhắn 1-1 của người gửi ⇒ tick của người gửi chuyển
 * từ "đã gửi" (✓ xám) sang "đã xem" (✓✓ xanh) ngay lập tức.
 *
 * Broadcast tới kênh riêng của NGƯỜI GỬI (`chat.{senderId}`). Fail-open: Reverb tắt
 * thì tick vẫn đúng khi tải lại trang (đọc từ cột `direct_messages.seen_at`).
 */
class DirectMessagesSeen implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public int $senderId,
        public int $readerId,
        public int $count,
        public string $seenAt,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.' . $this->senderId)];
    }

    public function broadcastAs(): string
    {
        return 'direct-messages-seen';
    }

    public function broadcastWith(): array
    {
        return [
            'read' => [
                'sender_id' => $this->senderId,
                'reader_id' => $this->readerId,
                'count' => $this->count,
                'seen_at' => $this->seenAt,
            ],
        ];
    }
}
