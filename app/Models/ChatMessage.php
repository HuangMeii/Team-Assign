<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $table = 'chat_messages';
    protected $fillable = ['group_id', 'user_id', 'content'];

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
}