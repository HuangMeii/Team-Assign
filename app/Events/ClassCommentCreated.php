<?php

namespace App\Events;

use App\Models\ClassPostComment;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Có BÌNH LUẬN mới dưới một bài của bảng tin lớp học.
 *
 * Đẩy realtime tới kênh private `class.{classId}` — trang bảng tin chèn bình luận
 * vào đúng bài (`post_id`) mà không cần tải lại.
 */
class ClassCommentCreated implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(public ClassPostComment $comment) {}

    public function broadcastOn(): array
    {
        $classId = $this->comment->post?->class_id;

        // Bài đã bị xoá ⇒ không còn gì để đẩy.
        return $classId ? [new PrivateChannel('class.' . $classId)] : [];
    }

    public function broadcastAs(): string
    {
        return 'class-comment';
    }

    public function broadcastWith(): array
    {
        return [
            'comment' => [
                'comment_id' => $this->comment->comment_id,
                'post_id' => $this->comment->post_id,
                'parent_id' => $this->comment->parent_id,
                'content' => $this->comment->content,
                'author_name' => $this->comment->author?->name,
                'author_role' => $this->comment->author?->role,
                'created_at_human' => $this->comment->created_at?->diffForHumans(),
            ],
        ];
    }
}
