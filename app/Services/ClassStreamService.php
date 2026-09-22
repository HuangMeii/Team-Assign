<?php

namespace App\Services;

use App\Events\ClassCommentCreated;
use App\Events\ClassPostCreated;
use App\Models\ClassPost;
use App\Models\ClassPostComment;
use App\Models\ClassSection;
use App\Models\Groups;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * BẢNG TIN LỚP HỌC (kiểu Google Classroom).
 *
 * 1) Thông báo: giảng viên phụ trách lớp (hoặc admin) đăng → sinh viên trong lớp
 *    nhận thông báo (bell + Reverb) và thấy bài trong bảng tin của lớp.
 * 2) Hoạt động nhóm: `logGroupActivity()` được observer gọi khi có nhóm mới,
 *    thêm/rời thành viên, đổi trưởng nhóm, nhóm được duyệt đề tài... ⇒ bài hệ thống
 *    (`user_id = NULL`) tự hiện trong bảng tin lớp đó.
 * 3) Bình luận: sinh viên + giảng viên + admin bình luận/trả lời dưới mỗi bài.
 *
 * NGUYÊN TẮC: mọi thao tác ghi vào bảng tin đều **fail-open** — lỗi (Reverb tắt,
 * DB lỗi...) KHÔNG được làm hỏng nghiệp vụ chính (tạo nhóm, duyệt đề tài...).
 */
class ClassStreamService
{
    /** Số bài mỗi trang bảng tin */
    public const PER_PAGE = 10;

    /** Số bài hiển thị ở thẻ "Bảng tin lớp" trong trang lớp */
    public const PREVIEW_LIMIT = 3;

    /** Giới hạn độ dài nội dung (khớp validate ở controller) */
    public const MAX_POST_LENGTH = 1000;

    public const MAX_COMMENT_LENGTH = 500;

    // =====================================================================
    // QUYỀN
    // =====================================================================

    /** Đăng thông báo / ghim / xoá bài: giảng viên PHỤ TRÁCH lớp hoặc admin. */
    public function canManage(?User $user, ClassSection $class): bool
    {
        if (! $user) {
            return false;
        }

        if (($user->role ?? null) === 'admin') {
            return true;
        }

        if (($user->role ?? null) !== 'lecturer') {
            return false;
        }

        return $class->lecturers()->where('users.user_id', $user->user_id)->exists();
    }

    /** Xem bảng tin / bình luận: admin, giảng viên phụ trách hoặc thành viên lớp. */
    public function canView(?User $user, ClassSection $class): bool
    {
        if (! $user) {
            return false;
        }

        if (($user->role ?? null) === 'admin') {
            return true;
        }

        return $class->users()->where('users.user_id', $user->user_id)->exists();
    }

    /** Xoá bài: tác giả bài, giảng viên phụ trách lớp, hoặc admin. */
    public function canDeletePost(?User $user, ClassPost $post): bool
    {
        if (! $user) {
            return false;
        }

        if ((int) $post->user_id === (int) $user->user_id) {
            return true;
        }

        return $post->class ? $this->canManage($user, $post->class) : false;
    }

    /** Xoá bình luận: tác giả bình luận, giảng viên phụ trách lớp, hoặc admin. */
    public function canDeleteComment(?User $user, ClassPostComment $comment): bool
    {
        if (! $user) {
            return false;
        }

        if ((int) $comment->user_id === (int) $user->user_id) {
            return true;
        }

        $class = $comment->post?->class;

        return $class ? $this->canManage($user, $class) : false;
    }

    // =====================================================================
    // ĐỌC
    // =====================================================================

    /** Bảng tin của lớp: bài GHIM trước, rồi mới nhất (kèm bình luận để render). */
    public function feed(ClassSection $class, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        return ClassPost::forClass($class->class_id)
            ->with([
                'author',
                'group',
                'topic',
                'orderedComments.author',
                'orderedComments.replies.author',
            ])
            ->orderedForFeed()
            ->paginate($perPage)
            ->withQueryString();
    }

    /** N bài mới nhất cho thẻ preview ở trang lớp. */
    public function preview(ClassSection $class, int $limit = self::PREVIEW_LIMIT)
    {
        return ClassPost::forClass($class->class_id)
            ->with(['author', 'group'])
            ->orderedForFeed()
            ->limit($limit)
            ->get();
    }

    /** Toàn bộ bình luận của 1 bài (cũ → mới, kèm reply) — dùng cho AJAX. */
    public function comments(ClassPost $post)
    {
        return $post->orderedComments()
            ->with(['author', 'replies.author'])
            ->get();
    }

    /**
     * Số bài + bài mới nhất của lớp (dùng cho thẻ preview, không cần query 2 lần).
     *
     * @return array{total: int, latest: \Illuminate\Database\Eloquent\Collection<int, ClassPost>}
     */
    public function summary(ClassSection $class, int $limit = self::PREVIEW_LIMIT): array
    {
        return [
            'total' => ClassPost::forClass($class->class_id)->count(),
            'latest' => $this->preview($class, $limit),
        ];
    }

    // =====================================================================
    // GHI — THÔNG BÁO CỦA GIẢNG VIÊN
    // =====================================================================

    /** Giảng viên đăng thông báo vào lớp + thông báo cho sinh viên trong lớp. */
    public function postAnnouncement(ClassSection $class, User $author, string $content, ?string $title = null, bool $pin = false): ClassPost
    {
        $post = ClassPost::create([
            'class_id' => $class->class_id,
            'user_id' => $author->user_id,
            'type' => 'announcement',
            'title' => $title ? trim($title) : null,
            'content' => trim($content),
            'is_pinned' => $pin,
        ]);

        $this->notifyStudents($class, $post);
        $this->broadcastPost($post);

        return $post;
    }

    /** Bình luận / trả lời một bình luận dưới bài viết (giữ reply 1 cấp). */
    public function addComment(ClassPost $post, User $author, string $content, ?int $parentId = null): ClassPostComment
    {
        $parentId = $parentId ?: null;

        if ($parentId) {
            $parent = ClassPostComment::where('post_id', $post->post_id)->find($parentId);
            // Reply của reply ⇒ gắn vào bình luận gốc để cây bình luận chỉ còn 1 cấp.
            $parentId = $parent ? ($parent->parent_id ?: $parent->comment_id) : null;
        }

        $comment = ClassPostComment::create([
            'post_id' => $post->post_id,
            'user_id' => $author->user_id,
            'parent_id' => $parentId,
            'content' => trim($content),
        ]);

        $post->increment('comments_count');

        $this->notifyComment($post, $comment);
        $this->broadcastComment($comment);

        return $comment;
    }

    /** Ghim / bỏ ghim bài (bài ghim luôn nằm đầu bảng tin). */
    public function togglePin(ClassPost $post): bool
    {
        $post->update(['is_pinned' => ! $post->is_pinned]);

        return (bool) $post->is_pinned;
    }

    public function deletePost(ClassPost $post): void
    {
        $post->delete();
    }

    public function deleteComment(ClassPostComment $comment): void
    {
        $post = $comment->post;
        $comment->delete();

        if ($post && $post->comments_count > 0) {
            $post->decrement('comments_count');
        }
    }

    // =====================================================================
    // GHI — HOẠT ĐỘNG NHÓM (hệ thống tự ghi, FAIL-OPEN)
    // =====================================================================

    /**
     * Ghi 1 bài hoạt động nhóm vào bảng tin của lớp mà nhóm thuộc về.
     *
     * @param  array<string, mixed>  $meta  dữ liệu phụ (tên nhóm, thành viên, đề tài...)
     * @param  string|null  $sourceKey  khoá chống trùng (backfill chạy lại không nhân đôi)
     * @param  \DateTimeInterface|null  $createdAt  giữ mốc thời gian gốc khi backfill
     */
    public function logGroupActivity(
        Groups $group,
        string $type,
        array $meta = [],
        ?string $content = null,
        ?string $sourceKey = null,
        ?\DateTimeInterface $createdAt = null
    ): ?ClassPost {
        try {
            if (! $group->class_id) {
                return null; // nhóm chưa thuộc lớp nào ⇒ không có bảng tin để ghi
            }

            if ($sourceKey && ClassPost::where('source_key', $sourceKey)->exists()) {
                return null; // đã ghi trước đó ⇒ bỏ qua (idempotent)
            }

            $post = ClassPost::create([
                'class_id' => $group->class_id,
                'user_id' => null, // bài hệ thống
                'type' => $type,
                'content' => $content ?? $this->describeGroupActivity($group, $type, $meta),
                'group_id' => $group->group_id,
                'topic_id' => $meta['topic_id'] ?? null,
                'meta' => array_merge(['group_name' => $group->group_name], $meta),
                'source_key' => $sourceKey,
            ]);

            if ($createdAt) {
                // Backfill: giữ đúng thời điểm gốc để bảng tin xếp theo lịch sử thật.
                $post->forceFill(['created_at' => $createdAt])->saveQuietly();
            }

            $this->broadcastPost($post);

            return $post;
        } catch (\Throwable $e) {
            Log::warning('Class stream: bỏ qua ghi hoạt động nhóm (' . $type . '): ' . $e->getMessage());

            return null; // fail-open: KHÔNG làm hỏng luồng tạo nhóm / duyệt đề tài
        }
    }

    /** Câu mô tả tiếng Việt cho từng loại hoạt động nhóm. */
    public function describeGroupActivity(Groups $group, string $type, array $meta = []): string
    {
        $name = $group->group_name;
        $actor = $meta['actor_name'] ?? null;
        $member = $meta['member_name'] ?? 'Một thành viên';

        return match ($type) {
            'group_created' => 'Nhóm ' . $name . ' đã được thành lập' . ($actor ? ' bởi ' . $actor : '') . '.',
            'group_member_joined' => $member . ' đã tham gia nhóm ' . $name . '.',
            'group_member_left' => $member . ' đã rời nhóm ' . $name . '.',
            'group_leader_changed' => $member . ' trở thành nhóm trưởng của nhóm ' . $name . '.',
            'group_status' => 'Nhóm ' . $name . ' hiện có ' . ($meta['member_count'] ?? '?') . ' thành viên.',
            'group_topic' => 'Nhóm ' . $name . ' được duyệt đề tài "' . ($meta['topic_name'] ?? '') . '".',
            'group_deleted' => 'Nhóm ' . $name . ' đã giải tán.',
            default => 'Cập nhật nhóm ' . $name . '.',
        };
    }

    // =====================================================================
    // THÔNG BÁO + REALTIME (tất cả đều fail-open)
    // =====================================================================

    /** Thông báo cho TOÀN BỘ sinh viên của lớp khi giảng viên đăng thông báo mới. */
    private function notifyStudents(ClassSection $class, ClassPost $post): void
    {
        try {
            $title = $post->title ?: 'Thông báo mới từ lớp ' . $class->class_name;
            $message = Str::limit((string) $post->content, 140);
            $url = route('class.stream', $class->class_id);

            foreach ($class->students()->get() as $student) {
                NotificationService::create($student->user_id, 'class_announcement', $title, $message, $url, [
                    'class_id' => $class->class_id,
                    'post_id' => $post->post_id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Class stream: không gửi được thông báo bài mới: ' . $e->getMessage());
        }
    }

    /** Thông báo cho chủ bài viết + tác giả bình luận gốc (không tự thông báo cho mình). */
    private function notifyComment(ClassPost $post, ClassPostComment $comment): void
    {
        try {
            $url = route('class.stream', $post->class_id) . '#post-' . $post->post_id;
            $message = ($comment->author->name ?? 'Ai đó') . ' đã bình luận: ' . Str::limit($comment->content, 100);

            $targets = [];
            if ($post->user_id) {
                $targets[] = (int) $post->user_id;
            }
            if ($comment->parent_id) {
                $parentAuthor = ClassPostComment::whereKey($comment->parent_id)->value('user_id');
                if ($parentAuthor) {
                    $targets[] = (int) $parentAuthor;
                }
            }

            $targets = array_values(array_unique(array_diff($targets, [(int) $comment->user_id])));

            foreach ($targets as $userId) {
                NotificationService::create($userId, 'class_comment', 'Bình luận mới trong lớp', $message, $url, [
                    'class_id' => $post->class_id,
                    'post_id' => $post->post_id,
                    'comment_id' => $comment->comment_id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Class stream: không gửi được thông báo bình luận: ' . $e->getMessage());
        }
    }

    private function broadcastPost(ClassPost $post): void
    {
        try {
            broadcast(new ClassPostCreated($post));
        } catch (\Throwable $e) {
            Log::warning('Class stream: broadcast bài mới thất bại (Reverb tắt?): ' . $e->getMessage());
        }
    }

    private function broadcastComment(ClassPostComment $comment): void
    {
        try {
            broadcast(new ClassCommentCreated($comment));
        } catch (\Throwable $e) {
            Log::warning('Class stream: broadcast bình luận thất bại (Reverb tắt?): ' . $e->getMessage());
        }
    }
}
