<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $group_id
 * @property string $group_name
 * @property int $leader_id
 * @property int|null $topic_id
 * @property int|null $class_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ClassSection|null $class
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Invites> $invites
 * @property-read int|null $invites_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Join_Requests> $joinRequests
 * @property-read int|null $join_requests_count
 * @property-read \App\Models\User $leader
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $members
 * @property-read int|null $members_count
 * @property-read \App\Models\Topics|null $topic
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Topic_requests> $topicRequests
 * @property-read int|null $topic_requests_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Groups newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Groups newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Groups query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Groups whereClassId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Groups whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Groups whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Groups whereGroupName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Groups whereLeaderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Groups whereTopicId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Groups whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Groups extends Model
{
    use HasFactory;
    protected $primaryKey = 'group_id';

    protected $fillable = [
        'group_name',
        'leader_id',
        'topic_id',
        'class_id',
        'status'
    ];


    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id', 'user_id');
    }

    public function topic()
    {
        return $this->belongsTo(Topics::class, 'topic_id', 'topic_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'group_members', 'group_id', 'user_id');
    }

    public function class()
    {
        return $this->belongsTo(ClassSection::class, 'class_id', 'class_id');
    }

    public function invites()
    {
        return $this->hasMany(Invites::class, 'group_id', 'group_id');
    }

    public function joinRequests()
    {
        return $this->hasMany(Join_Requests::class, 'group_id', 'group_id');
    }

    public function topicRequests()
    {
        return $this->hasMany(Topic_Requests::class, 'group_id', 'group_id');
    }
}