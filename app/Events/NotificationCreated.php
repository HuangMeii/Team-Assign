<?php

namespace App\Events;

use App\Models\Notifications;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public Notifications $notification) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.' . $this->notification->user_id)];
    }

    public function broadcastAs(): string
    {
        return 'notification-created';
    }

    public function broadcastWith(): array
    {
        return ['notification' => [
            'notification_id' => $this->notification->notification_id,
            'title' => $this->notification->title,
            'message' => $this->notification->message,
            'type' => $this->notification->type,
            'url' => $this->notification->url,
        ]];
    }
}