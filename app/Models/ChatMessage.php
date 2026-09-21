<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ChatMessage extends Model
{
    /** Tin nhắn thường của thành viên / trưởng nhóm. */
    public const TYPE_MEMBER = 'member';

    /** Thông báo do admin gửi thẳng vào khung chat nhóm. */
    public const TYPE_ANNOUNCEMENT = 'announcement';

    /** Cảnh báo do admin gửi thẳng vào khung chat nhóm. */
    public const TYPE_WARNING = 'warning';

    /** Các loại tin nhắn do admin gửi (không cần là thành viên nhóm). */
    public const ADMIN_TYPES = [self::TYPE_ANNOUNCEMENT, self::TYPE_WARNING];

    protected $table = 'chat_messages';
        protected $fillable = ['group_id', 'user_id', 'content', 'type', 'attachment', 'is_flagged', 'flag_reason', 'moderation_score', 'flagged_at'];

    /** Gửi kèm URL ảnh trong payload broadcast/AJAX để client render ngay không cần reload. */
    protected $appends = ['attachment_url'];

    protected $casts = [
        'is_flagged' => 'boolean',
        'moderation_score' => 'float',
        'flagged_at' => 'datetime',
    ];

    /**
     * Đường dẫn công khai tới file đính kèm (ảnh).
     */
    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment ? Storage::url($this->attachment) : null;
    }

    // Lấy thông tin nhóm
    public function group(): BelongsTo
    {
        return $this->belongsTo(Groups::class, 'group_id', 'group_id');
    }

    // Lấy thông tin người gửi
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /** Tin nhắn do admin gửi (thông báo / cảnh báo) -> render khác tin nhắn thường. */
    public function isAdminMessage(): bool
    {
        return in_array($this->type, self::ADMIN_TYPES, true);
    }
}