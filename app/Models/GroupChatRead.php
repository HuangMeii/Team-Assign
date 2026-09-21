<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mốc thời gian đọc cuối cùng của một user trong một nhóm (chat nhóm).
 *
 * @property int $id
 * @property int $user_id
 * @property int $group_id
 * @property \Illuminate\Support\Carbon|null $last_read_at
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\Groups|null $group
 */
class GroupChatRead extends Model
{
    protected $table = 'group_chat_reads';

    protected $fillable = [
        'user_id',
        'group_id',
        'last_read_at',
    ];

    protected $casts = [
        'last_read_at' => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Groups::class, 'group_id', 'group_id');
    }
}
