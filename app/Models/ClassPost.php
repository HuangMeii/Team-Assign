<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Một bài trên BẢNG TIN LỚP HỌC (kiểu Google Classroom).
 *
 * Hai nhóm bài:
 *   - `announcement`  : thông báo do giảng viên phụ trách lớp (hoặc admin) đăng  ⇒ `user_id` có giá trị.
 *   - các `group_*`   : hoạt động nhóm do hệ thống tự ghi                        ⇒ `user_id = NULL`.
 *
 * `meta` giữ dữ liệu phụ để render (tên nhóm, số thành viên, tên đề tài...) nên
 * bảng tin không cần query thêm khi hiển thị.
 *
 * @property int $post_id
 * @property int $class_id
 * @property int|null $user_id
 * @property string $type
 * @property string|null $title
 * @property string|null $content
 * @property int|null $group_id
 * @property int|null $topic_id
 * @property array|null $meta
 * @property bool $is_pinned
 * @property int $comments_count
 * @property string|null $source_key
 * @property-read \App\Models\ClassSection|null $class
 * @property-read \App\Models\User|null $author
 * @property-read \App\Models\Groups|null $group
 * @property-read \App\Models\Topics|null $topic
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ClassPostComment> $comments
 */
class ClassPost extends Model
{
    protected $table = 'class_posts';

    protected $primaryKey = 'post_id';

    /** Nhãn hiển thị theo loại bài (dùng ở UI + thông báo). */
    public const LABELS = [
        'announcement' => 'Thông báo',
        'group_created' => 'Nhóm mới thành lập',
        'group_member_joined' => 'Thành viên mới',
        'group_member_left' => 'Thành viên rời nhóm',
        'group_leader_changed' => 'Đổi nhóm trưởng',
        'group_status' => 'Cập nhật nhóm',
        'group_topic' => 'Đề tài của nhóm',
        'group_deleted' => 'Nhóm đã giải tán',
    ];

    /** Icon Font Awesome theo loại bài. */
    public const ICONS = [
        'announcement' => 'fa-bullhorn',
        'group_created' => 'fa-users',
        'group_member_joined' => 'fa-user-plus',
        'group_member_left' => 'fa-user-minus',
        'group_leader_changed' => 'fa-crown',
        'group_status' => 'fa-circle-info',
        'group_topic' => 'fa-clipboard-check',
        'group_deleted' => 'fa-user-slash',
    ];

    /** Màu Bootstrap theo loại bài. */
    public const COLORS = [
        'announcement' => 'primary',
        'group_created' => 'success',
        'group_member_joined' => 'info',
        'group_member_left' => 'warning',
        'group_leader_changed' => 'secondary',
        'group_status' => 'secondary',
        'group_topic' => 'success',
        'group_deleted' => 'danger',
    ];

    protected $fillable = [
        'class_id',
        'user_id',
        'type',
        'title',
        'content',
        'group_id',
        'topic_id',
        'meta',
        'is_pinned',
        'comments_count',
        'source_key',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_pinned' => 'boolean',
        'comments_count' => 'integer',
    ];

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class, 'class_id', 'class_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Groups::class, 'group_id', 'group_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topics::class, 'topic_id', 'topic_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ClassPostComment::class, 'post_id', 'post_id');
    }

    /** Bình luận cũ nhất trước (đọc như một cuộc hội thoại). */
    public function orderedComments(): HasMany
    {
        return $this->comments()->orderBy('created_at')->orderBy('comment_id');
    }

    /** Bảng tin: bài GHIM lên đầu, sau đó mới nhất trước. */
    public function scopeOrderedForFeed(Builder $query): Builder
    {
        return $query->orderByDesc('is_pinned')->orderByDesc('created_at')->orderByDesc('post_id');
    }

    public function scopeForClass(Builder $query, int $classId): Builder
    {
        return $query->where('class_id', $classId);
    }

    /** Bài do hệ thống sinh (hoạt động nhóm) — không có người đăng. */
    public function getIsSystemAttribute(): bool
    {
        return $this->user_id === null;
    }

    public function getLabelAttribute(): string
    {
        return self::LABELS[$this->type] ?? 'Hoạt động';
    }

    public function getIconAttribute(): string
    {
        return self::ICONS[$this->type] ?? 'fa-circle-info';
    }

    public function getColorAttribute(): string
    {
        return self::COLORS[$this->type] ?? 'secondary';
    }
}
