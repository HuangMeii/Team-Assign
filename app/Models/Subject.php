<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * @property int $subject_id
 * @property string $subject_code
 * @property string $subject_name
 * @property int|null $lecturer_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ClassSection> $classes
 * @property-read int|null $classes_count
 * @property-read \App\Models\User|null $lecturer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Topics> $topics
 * @property-read int|null $topics_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subject newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subject newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subject query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subject whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subject whereLecturerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subject whereSubjectCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subject whereSubjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subject whereSubjectName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subject whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Subject extends Model
{
    protected $primaryKey = 'subject_id';
    protected $fillable = ['subject_code', 'subject_name', 'lecturer_id'];

    public function lecturer() {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    public function classes() {
        return $this->hasMany(ClassSection::class, 'subject_id');
    }

    public function topics() {
        return $this->hasMany(Topics::class, 'subject_id');
    }
}
