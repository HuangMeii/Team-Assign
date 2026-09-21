<?php

namespace App\Events;

use App\Models\DirectMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class DirectMessageSent implements ShouldBroadcastNow
{
    use SerializesModels;

    public $message;

    public function __construct(DirectMessage $message)
    {
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        // Gửi tới KÊNH RIÊNG CỦA NGƯỜI NHẬN
        return [
            new PrivateChannel('chat.' . $this->message->recipient_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'direct-message';
    }

    public function broadcastWith(): array
    {
        $this->message->loadMissing(['sender', 'recipient']);
        return [
            'message' => $this->message->toArray(),
        ];
    }
}
