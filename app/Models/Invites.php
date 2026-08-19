<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $group_id
 * @property \App\Models\User $invitedBy
 * @property int $member_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Groups $group
 * @property-read \App\Models\User $member
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invites newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invites newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invites query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invites whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invites whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invites whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invites whereInvitedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invites whereMemberId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invites whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invites whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Invites extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'invitedBy',
        'member_id',
        'status',
        'created_at',
    ];

    public function group()
    {
        return $this->belongsTo(Groups::class, 'group_id', 'group_id');
    }

    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invitedBy', 'user_id');
    }
public function inviter()
    {
        return $this->belongsTo(User::class, 'invitedBy', 'user_id');
    }
    public function member()
    {
        return $this->belongsTo(User::class, 'member_id', 'user_id');
    }
}