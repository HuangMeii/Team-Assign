<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DirectMessage extends Model
{
    protected $fillable = ['sender_id', 'recipient_id', 'content', 'attachment', 'is_read', 'seen_at', 'is_flagged', 'flag_reason', 'moderation_score', 'flagged_at'];
    protected $appends = ['attachment_url'];

    protected $casts = [
        'is_read'    => 'boolean',
        'is_flagged' => 'boolean',
        'moderation_score' => 'float',
        'flagged_at' => 'datetime',
        'seen_at'    => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Trạng thái tin nhắn hiển thị cho NGƯỜI GỬI (2 mốc):
     *   - `sent` = ĐÃ GỬI  (✓ xám)  — người nhận chưa mở xem
     *   - `seen` = ĐÃ XEM  (✓✓ xanh) — người nhận đã mở hội thoại xem nội dung
     *
     * `is_read` được coi tương đương `seen_at` để tương thích dữ liệu cũ
     * (tin đã đọc trước khi có cột `seen_at`).
     */
    public function deliveryStatus(): string
    {
        return ($this->seen_at || $this->is_read) ? 'seen' : 'sent';
    }

    /** Nhãn tiếng Việt của trạng thái (dùng cho tooltip/aria-label). */
    public function deliveryStatusLabel(): string
    {
        return $this->deliveryStatus() === 'seen' ? 'Đã xem' : 'Đã gửi';
    }

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