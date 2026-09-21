<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DirectMessage extends Model
{
    protected $fillable = ['sender_id', 'recipient_id', 'content', 'attachment', 'is_read', 'is_flagged', 'flag_reason', 'moderation_score', 'flagged_at'];
    protected $appends = ['attachment_url'];

    protected $casts = [
        'is_read'    => 'boolean',
        'is_flagged' => 'boolean',
        'moderation_score' => 'float',
        'flagged_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Đường dẫn công khai tới file đính kèm (ảnh).
     */
    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment ? Storage::url($this->attachment) : null;
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id', 'user_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id', 'user_id');
    }
}