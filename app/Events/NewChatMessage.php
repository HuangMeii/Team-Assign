<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewChatMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(ChatMessage $message)
    {
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('chat.group.' . $this->message->group_id),
        ];

        // Gửi thêm tới KÊNH CÁ NHÂN của từng thành viên (trừ người gửi) để họ
        // nhận badge + toast "tin nhắn nhóm mới" ngay cả khi không mở trang chat nhóm.
        $group = $this->message->group;

        if ($group) {
            $memberIds = $group->members()
                ->pluck('users.user_id')
                ->push($group->leader_id)
                ->unique()
                ->reject(fn ($id) => (int) $id === (int) $this->message->user_id);

            foreach ($memberIds as $memberId) {
                $channels[] = new PrivateChannel('chat.' . $memberId);
            }
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'new-message';
    }

    public function broadcastWith(): array
    {
        // BƯỚC SỬA LỖI 1: Tải quan hệ 'user' nếu nó chưa được tải.
        $this->message->loadMissing('user'); 

        return [
           
            'message' => $this->message->toArray(), 
        ];
    }
}