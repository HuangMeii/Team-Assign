<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bình luận dưới một bài của bảng tin lớp học.
 *
 * `parent_id` != null ⇒ đây là TRẢ LỜI một bình luận khác (reply 1 cấp).
 *
 * @property int $comment_id
 * @property int $post_id
 * @property int $user_id
 * @property int|null $parent_id
 * @property string $content
 * @property-read \App\Models\User|null $author
 * @property-read \App\Models\ClassPost|null $post
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ClassPostComment> $replies
 */
class ClassPostComment extends Model
{
    protected $table = 'class_post_comments';

    protected $primaryKey = 'comment_id';

    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'content',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(ClassPost::class, 'post_id', 'post_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'comment_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'comment_id')->orderBy('created_at');
    }

    /** Bình luận gốc (không phải reply). */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }
}
