<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $class_id
 * @property int $subject_id
 * @property string $class_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Groups> $groups
 * @property-read int|null $groups_count
 * @property-read \App\Models\Subject $subject
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Topics> $topics
 * @property-read int|null $topics_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClassSection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClassSection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClassSection query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClassSection whereClassId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClassSection whereClassName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClassSection whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClassSection whereSubjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClassSection whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ClassSection extends Model
{
    use HasFactory;

    protected $primaryKey = 'class_id';
    protected $fillable = ['subject_id', 'class_name', 'class_code', 'is_active'];


    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function groups()
    {
        return $this->hasMany(Groups::class, 'class_id');
    }

    // User tham gia lớp học - many to many qua bảng user_classes
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'user_classes',      
            'class_id',         
            'user_id'           
        );
    }
public function getLecturerAttribute()
    {
        // Phải có ->first() để lấy object đầu tiên ra khỏi danh sách
        return $this->lecturers->first(); 
    }
    public function lecturers()
    {
        return $this->belongsToMany(User::class, 'user_classes', 'class_id', 'user_id')
                    ->where('role', 'lecturer');
    }
      public function topics()
    {
        return $this->hasMany(Topics::class, 'class_id');
    }
}