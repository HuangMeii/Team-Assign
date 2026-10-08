<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $group_id
 * @property string $group_name
 * @property int $leader_id
 * @property int|null $topic_id
 * @property int|null $class_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChatMessage> $chatMessages
 * @property-read int|null $chat_messages_count
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
    use HasFactory, SoftDeletes;

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
        // withTimestamps(): ghi created_at/updated_at cho bảng pivot `group_members`
        // ⇒ biết được thời điểm sinh viên tham gia nhóm (dùng cho bảng tin lớp + backfill).
        return $this->belongsToMany(User::class, 'group_members', 'group_id', 'user_id')
            ->withPivot('role', 'id')
            ->withTimestamps();
    }

    /**
     * L05 (Chốt 2a): danh sách user_id ĐANG CÒN HỌC trong lớp của nhóm.
     *
     * Quy ước:
     *  - Trưởng nhóm KHÔNG bắt buộc vắng mặt trong bảng pivot `group_members`
     *   (khi chuyển quyền trưởng nhóm, dòng pivot cũ được giữ lại) ⇒ phải đếm HỢP
     *   (union distinct) giữa pivot và trưởng nhóm để không đếm trùng.
     *  - Loại bỏ những user đã rời lớp (`user_classes.status = 'left'`) ⇒ trưởng nhóm
     *   "người cuối cùng rời lớp" của nhóm rỗng không còn bị tính là thành viên.
     *
     * @return int[]
     */
    public function activeMemberIds(): array
    {
        // Truy vấn MỚI (không dùng relation đã cache) để số đếm luôn đúng cả trong
        // model events (GroupMemberObserver khi thêm/xóa thành viên).
        $ids = $this->members()->pluck('users.user_id')->all();

        if ($this->leader_id) {
            $ids[] = (int) $this->leader_id;
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));

        if (! $this->class_id) {
            return $ids;
        }

        $leftIds = user_class::where('class_id', $this->class_id)
            ->where('status', user_class::STATUS_LEFT)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_diff($ids, $leftIds));
    }

    /**
     * Tổng số thành viên ĐANG HỌC của nhóm (đã bao gồm trưởng nhóm).
     */
    public function activeMemberCount(): int
    {
        return count($this->activeMemberIds());
    }

    /**
     * Nhóm còn ít nhất 1 thành viên đang học (nhóm "ghost" ⇒ false).
     */
    public function hasActiveMembers(): bool
    {
        return $this->activeMemberCount() > 0;
    }

    /**
     * Tin nhắn trong khung chat của nhóm.
     * Dùng cho danh sách "Tất cả nhóm" của admin (withCount / withMax).
     */
    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class, 'group_id', 'group_id');
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