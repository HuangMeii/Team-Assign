<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $group_id
 * @property int $user_id
 * @property string $role
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Groups $group
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group_Members newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group_Members newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group_Members query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group_Members whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group_Members whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group_Members whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group_Members whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group_Members whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group_Members whereUserId($value)
 * @mixin \Eloquent
 */
class Group_Members extends Model
{
    use HasFactory;

    protected $table = 'group_members';
    protected $primaryKey = 'id';

    protected $fillable = [
        'group_id',
        'user_id',
        'role'
    ];

    public function group()
    {
        return $this->belongsTo(Groups::class, 'group_id', 'group_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}