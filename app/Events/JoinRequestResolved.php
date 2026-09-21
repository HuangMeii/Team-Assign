<?php

namespace App\Events;

use App\Models\Join_Requests;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Yêu cầu tham gia nhóm đã được xử lý hoặc hết hiệu lực (nhóm đủ người,
 * sinh viên đã vào nhóm khác...).
 *
 * Gửi tới kênh riêng của trưởng nhóm để:
 * - giảm badge "Yêu cầu" (data-request-badge) ngay lập tức;
 * - tắt nút Chấp nhận / Từ chối nếu trang đang mở đúng yêu cầu đó.
 */
class JoinRequestResolved implements ShouldBroadcastNow
{
    use SerializesModels;

    public $joinRequest;

    public $status;

    public $reason;

    public function __construct(Join_Requests $joinRequest, string $status, ?string $reason = null)
    {
        $this->joinRequest = $joinRequest;
        $this->status = $status;
        $this->reason = $reason;
    }

    public function broadcastOn(): array
    {
        $leaderId = $this->joinRequest->group?->leader_id;

        if (!$leaderId) {
            return [];
        }

        return [new PrivateChannel('chat.' . $leaderId)];
    }

    public function broadcastAs(): string
    {
        return 'join-request-resolved';
    }

    public function broadcastWith(): array
    {
        return [
            'join_request_id' => $this->joinRequest->id,
            'group_id'        => $this->joinRequest->group_id,
            'status'          => $this->status,
            'reason'          => $this->reason,
        ];
    }
}
