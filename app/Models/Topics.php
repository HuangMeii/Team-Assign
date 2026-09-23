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
 * @property string $report_type Loại báo cáo: final (cuối kì) | midterm (giữa kì)
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
        'min_members',
        'max_members',
        'registration_deadline',
        'is_active',
        'assigned_group_id',
        'subject_id',
        'class_id',
        'report_type'   
    ];

    /**
     * Giá trị mặc định khi tạo mới: đề tài mới luôn là đồ án CUỐI KÌ (khớp default của cột report_type).
     * Cần khai báo để instance vừa tạo qua Topics::create() cũng có report_type (Eloquent không tự nạp default của DB).
     */
    protected $attributes = [
        'report_type' => self::REPORT_FINAL,
    ];

    protected $casts = [
        'registration_deadline' => 'datetime',
        'is_active' => 'boolean',
    ];


    /** Đề tài cuối kì (mặc định). */
    public const REPORT_FINAL = 'final';

    /** Đề tài giữa kì (chỉ có khi môn học có report_count = 2). */
    public const REPORT_MIDTERM = 'midterm';

    /**
     * Nhãn hiển thị của loại báo cáo.
     */
    public function reportLabel(): string
    {
        return $this->report_type === self::REPORT_MIDTERM ? 'Giữa kì' : 'Cuối kì';
    }

    /**
     * Các loại báo cáo hợp lệ.
     */
    public static function reportTypes(): array
    {
        return [self::REPORT_FINAL, self::REPORT_MIDTERM];
    }

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

    /**
     * Vector ngữ nghĩa đã lưu của đề tài (bảng `topic_embeddings`).
     * Dùng cho tính năng "gợi ý đề tài theo ngữ nghĩa" — xem App\Services\TopicEmbeddingService.
     */
    public function embedding()
    {
        return $this->hasOne(TopicEmbedding::class, 'topic_id', 'topic_id');
    }

    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

     public function class()
    {
        return $this->belongsTo(ClassSection::class, 'class_id', 'class_id');
    }

    /**
     * Alias của class() theo tài liệu thiết kế Subject – ClassSection – Topics
     * (dùng được cả `$topic->class_section` và `$topic->class`).
     */
    public function class_section()
    {
        return $this->belongsTo(ClassSection::class, 'class_id', 'class_id');
    }


}
