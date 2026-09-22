<?php

namespace App\Events;

use App\Models\ClassPost;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Có bài MỚI trên bảng tin lớp học (thông báo của giảng viên hoặc hoạt động nhóm).
 *
 * Đẩy realtime tới kênh private `class.{classId}` để mọi người đang mở bảng tin
 * của lớp thấy bài mới ngay mà không cần tải lại trang (nếu Reverb đang chạy).
 */
class ClassPostCreated implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(public ClassPost $post) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('class.' . $this->post->class_id)];
    }

    public function broadcastAs(): string
    {
        return 'class-post';
    }

    public function broadcastWith(): array
    {
        return [
            'post' => [
                'post_id' => $this->post->post_id,
                'class_id' => $this->post->class_id,
                'type' => $this->post->type,
                'label' => $this->post->label,
                'icon' => $this->post->icon,
                'color' => $this->post->color,
                'is_system' => $this->post->is_system,
                'title' => $this->post->title,
                'content' => $this->post->content,
                'group_id' => $this->post->group_id,
                'group_name' => $this->post->meta['group_name'] ?? null,
                'author_name' => $this->post->author?->name,
                'created_at_human' => $this->post->created_at?->diffForHumans(),
            ],
        ];
    }
}
