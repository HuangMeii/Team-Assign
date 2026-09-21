<?php

namespace App\Events;

use App\Models\Join_Requests;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class JoinRequestCreated implements ShouldBroadcastNow
{
    use SerializesModels;

    public $joinRequest;

    public function __construct(Join_Requests $joinRequest)
    {
        $this->joinRequest = $joinRequest;
    }

    public function broadcastOn(): array
    {
        // Gửi tới leader của nhóm (qua kênh riêng của leader)
        return [
            new PrivateChannel('chat.' . $this->joinRequest->group->leader_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'join-request';
    }

    public function broadcastWith(): array
    {
        $this->joinRequest->loadMissing(['group', 'member']);
        return [
            'join_request' => $this->joinRequest->toArray(),
        ];
    }
}
