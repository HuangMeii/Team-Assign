<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $group_id
 * @property int $member_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Groups $group
 * @property-read \App\Models\User $member
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Join_Requests newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Join_Requests newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Join_Requests query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Join_Requests whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Join_Requests whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Join_Requests whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Join_Requests whereMemberId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Join_Requests whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Join_Requests whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Join_Requests extends Model
{
    use HasFactory;

    protected $table = 'join_requests';
    protected $fillable = [
        'group_id',
        'member_id',
        'status',
    ];

    public function group()
    {
        return $this->belongsTo(Groups::class, 'group_id', 'group_id');
    }

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id', 'user_id');
    }
}