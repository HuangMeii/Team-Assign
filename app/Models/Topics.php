<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $topic_id
 * @property string $name
 * @property string|null $description
 * @property string|null $lecturer
 * @property string|null $goal
 * @property string|null $requirements
 * @property int|null $assigned_group_id
 * @property int|null $subject_id
 * @property int|null $class_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Groups|null $assignedGroup
 * @property-read \App\Models\ClassSection|null $class
 * @property-read \App\Models\Subject|null $subject
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Topic_requests> $topic_requests
 * @property-read int|null $topic_requests_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics byClass($classId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics whereAssignedGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics whereClassId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics whereGoal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics whereLecturer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics whereRequirements($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics whereSubjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics whereTopicId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Topics extends Model
{
    use HasFactory;
    protected $primaryKey = 'topic_id';
    protected $fillable = [
        'name',
        'description',
        'lecturer',
        'goal',
        'requirements',
        'assigned_group_id',
        'subject_id',
        'class_id'   
    ];

    public function assignedGroup()
    {
        return $this->hasOne(Groups::class, 'topic_id');
    }
    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function topic_requests()
{
    return $this->hasMany(Topic_requests::class, 'topic_id', 'topic_id');
}
    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

     public function class()
    {
        return $this->belongsTo(ClassSection::class, 'class_id', 'class_id');
    }


}
