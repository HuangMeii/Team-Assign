<?php

namespace App\Services;

use App\Models\ClassSection;
use App\Models\Group_Members;
use App\Models\Groups;
use App\Models\Topic_requests;
use App\Models\Topics;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service tập trung các quy tắc nghiệp vụ liên quan đến NHÓM:
 *
 * - Tạo nhóm (sinh viên tự tạo / giảng viên - phân quyền bổ sung)
 * - Chuyển vai trò Sinh viên -> Nhóm trưởng sau khi tạo nhóm
 * - Mỗi sinh viên chỉ thuộc một nhóm tại một thời điểm
 * - Số lượng thành viên nằm trong khoảng [min, max] do giảng viên thiết lập
 * - Cập nhật trạng thái nhóm (incomplete / complete)
 *
 * QUY ƯỚC ĐẾM THÀNH VIÊN:
 * Trưởng nhóm KHÔNG nằm trong bảng pivot group_members.
 * Tổng số thành viên của nhóm = số dòng pivot + 1 (trưởng nhóm).
 */
class GroupService
{
    /**
     * Tổng số thành viên của nhóm (ĐÃ BAO GỒM trưởng nhóm).
     */
    public function memberCount(Groups $group): int
    {
        return $group->members()->count() + 1;
    }

    /**
     * Số thành viên tối đa của nhóm (lấy từ đề tài của lớp/nhóm, mặc định 5).
     */
    public function maxMembers(Groups $group): int
    {
        // Ưu tiên đề tài mà nhóm đã đăng ký
        if ($group->topic_id) {
            $topic = Topics::find($group->topic_id);
            if ($topic && $topic->max_members) {
                return (int) $topic->max_members;
            }
        }

        // Nếu không, lấy từ đề tài đầu tiên có max_members trong lớp
        $topic = Topics::where('class_id', $group->class_id)
            ->whereNotNull('max_members')
            ->first();

        return $topic && $topic->max_members ? (int) $topic->max_members : 5;
    }

    /**
     * Số thành viên tối thiểu của nhóm (mặc định 1).
     */
    public function minMembers(Groups $group): int
    {
        if ($group->topic_id) {
            $topic = Topics::find($group->topic_id);
            if ($topic && $topic->min_members) {
                return (int) $topic->min_members;
            }
        }

        $topic = Topics::where('class_id', $group->class_id)
            ->whereNotNull('min_members')
            ->first();

        return $topic && $topic->min_members ? (int) $topic->min_members : 1;
    }

    /**
     * Nhóm đã đạt số lượng thành viên tối đa (không nhận thêm người).
     */
    public function isFull(Groups $group): bool
    {
        return $this->memberCount($group) >= $this->maxMembers($group);
    }

    /**
     * Nhóm đã đạt số lượng thành viên tối thiểu (có thể đăng ký đề tài).
     */
    public function isEligibleForRegistration(Groups $group): bool
    {
        return $this->memberCount($group) >= $this->minMembers($group);
    }

    /**
     * Nhóm đã có đề tài được duyệt hay chưa.
     */
    public function hasApprovedTopic(Groups $group): bool
    {
        return Topic_requests::where('group_id', $group->group_id)
            ->where('status', 'Accepted')
            ->exists();
    }

    /**
     * Cập nhật trạng thái nhóm dựa trên số lượng thành viên.
     * complete: đã đủ thành viên tối thiểu (có thể đăng ký đề tài)
     * incomplete: chưa đủ thành viên.
     */
    public function updateStatus(Groups $group): void
    {
        $status = $this->isEligibleForRegistration($group) ? 'complete' : 'incomplete';

        if ($group->status !== $status) {
            $group->update(['status' => $status]);
        }

        // Thông báo cho giảng viên khi số lượng thành viên thay đổi
        NotificationService::groupMemberCountChanged($group);
    }

    /**
     * Kiểm tra user có phải trưởng nhóm không.
     */
    public function isLeader(Groups $group, User $user): bool
    {
        return (int) $group->leader_id === (int) $user->user_id;
    }

    /**
     * Kiểm tra user đã thuộc nhóm (trưởng nhóm hoặc thành viên) hay chưa.
     */
    public function isInGroup(Groups $group, int $userId): bool
    {
        return (int) $group->leader_id === $userId
            || $group->members()->where('group_members.user_id', $userId)->exists();
    }

    /**
     * Kiểm tra user đang là trưởng nhóm của bất kỳ nhóm nào.
     */
    public function isLeaderOfAnyGroup(User $user): bool
    {
        return Groups::where('leader_id', $user->user_id)->exists();
    }

    /**
     * Kiểm tra user đang là thành viên (pivot) của bất kỳ nhóm nào.
     */
    public function isMemberOfAnyGroup(User $user): bool
    {
        return Group_Members::where('user_id', $user->user_id)->exists();
    }

    /**
     * Thêm thành viên vào nhóm và đánh dấu sinh viên đã có nhóm.
     */
    public function addMember(Groups $group, User $member): void
    {
        $group->members()->attach($member->user_id, ['role' => 'member']);
        $member->update(['is_have_group' => true]);
    }

    /**
     * Chuyển vai trò Sinh viên -> Nhóm trưởng sau khi tạo nhóm.
     */
    public function promoteToLeader(User $user): void
    {
        $user->update([
            'role' => 'leader',
            'is_have_group' => true,
        ]);
    }

    /**
     * Sinh viên tự tạo nhóm mới (chỉ khi chưa thuộc nhóm nào).
     * Sau khi tạo thành công, hệ thống chuyển vai trò Sinh viên -> Nhóm trưởng.
     */
    public function createGroupByStudent(User $user, string $groupName, int $classId): ServiceResult
    {
        // 1. Mỗi sinh viên chỉ được thuộc một nhóm tại một thời điểm
        if ($this->isLeaderOfAnyGroup($user) || $this->isMemberOfAnyGroup($user)) {
            return ServiceResult::error('Bạn đã thuộc một nhóm khác. Mỗi sinh viên chỉ được tham gia một nhóm!');
        }

        // 2. Lớp phải đang hoạt động
        $class = ClassSection::find($classId);
        if (!$class) {
            return ServiceResult::error('Không tìm thấy lớp học!');
        }
        if (isset($class->is_active) && !$class->is_active) {
            return ServiceResult::error('Lớp học này đã bị khóa, không thể tạo nhóm!');
        }

        // 3. Tạo nhóm trong transaction
        $group = DB::transaction(function () use ($user, $groupName, $classId) {
            $group = Groups::create([
                'group_name' => $groupName,
                'leader_id'  => $user->user_id,
                'class_id'   => $classId,
                'status'     => 'incomplete',
            ]);

            // Chuyển vai trò Sinh viên -> Nhóm trưởng
            $this->promoteToLeader($user);

            return $group;
        });

        return ServiceResult::ok('Tạo nhóm thành công! Bạn có thể mời thêm thành viên.', $group);
    }

    /**
     * Giảng viên/Admin tạo nhóm cho lớp (phân quyền bổ sung).
     * Chỉ định trưởng nhóm là một sinh viên chưa thuộc nhóm nào.
     */
    public function createGroupByLecturer(User $actor, string $groupName, int $classId, int $leaderId): ServiceResult
    {
        if (!in_array($actor->role, ['lecturer', 'admin'])) {
            return ServiceResult::error('Bạn không có quyền tạo nhóm cho người khác!');
        }

        $class = ClassSection::find($classId);
        if (!$class) {
            return ServiceResult::error('Không tìm thấy lớp học!');
        }

        // Giảng viên chỉ được tạo nhóm trong lớp mình phụ trách
        if ($actor->role === 'lecturer'
            && !$class->lecturers()->where('users.user_id', $actor->user_id)->exists()) {
            return ServiceResult::error('Bạn không có quyền tạo nhóm trong lớp này!');
        }

        $leader = User::find($leaderId);
        if (!$leader || !in_array($leader->role, ['student', 'leader'])) {
            return ServiceResult::error('Người được chỉ định làm trưởng nhóm phải là sinh viên!');
        }

        if ($this->isLeaderOfAnyGroup($leader) || $this->isMemberOfAnyGroup($leader)) {
            return ServiceResult::error('Sinh viên được chỉ định đã thuộc một nhóm khác!');
        }

        $group = DB::transaction(function () use ($groupName, $classId, $leader) {
            $group = Groups::create([
                'group_name' => $groupName,
                'leader_id'  => $leader->user_id,
                'class_id'   => $classId,
                'status'     => 'incomplete',
            ]);

            $this->promoteToLeader($leader);

            return $group;
        });

        return ServiceResult::ok('Tạo nhóm thành công!', $group);
    }

    /**
     * Xóa nhóm (chỉ admin/giảng viên; không xóa nhóm đã gán đề tài).
     */
    public function destroy(Groups $group, User $actor): ServiceResult
    {
        if (!in_array($actor->role, ['admin', 'lecturer'])) {
            return ServiceResult::error('Bạn không có quyền xóa nhóm này!');
        }

        if ($group->topic_id) {
            return ServiceResult::error('Không thể xóa nhóm đã được gán đề tài!');
        }

        DB::transaction(function () use ($group) {
            // Xóa các lời mời / yêu cầu tham gia còn liên quan
            $group->invites()->delete();
            $group->joinRequests()->delete();
            $group->topicRequests()->delete();
            $group->members()->detach();

            $group->delete();
        });

        return ServiceResult::ok('Xóa nhóm thành công!');
    }

    /**
     * Danh sách sinh viên có thể mời vào nhóm (cùng lớp, chưa thuộc nhóm nào).
     */
    public function availableUsersForGroup(Groups $group): Collection
    {
        if (!$group->class_id) {
            return collect([]);
        }

        // Những user đã nằm trong nhóm nào đó của lớp (pivot hoặc làm trưởng nhóm)
        $usedUserIds = Group_Members::whereHas('group', function ($q) use ($group) {
            $q->where('class_id', $group->class_id);
        })->pluck('user_id')
            ->merge(
                Groups::where('class_id', $group->class_id)->pluck('leader_id')
            )
            ->unique();

        return User::where('role', 'student')
            ->whereHas('classes', function ($query) use ($group) {
                $query->where('class_sections.class_id', $group->class_id);
            })
            ->whereNotIn('user_id', $usedUserIds)
            ->orderBy('name')
            ->get();
    }

    /**
     * Danh sách lời mời đang chờ của nhóm.
     */
    public function pendingInvites(Groups $group): Collection
    {
        return $group->invites()
            ->where('status', 'Pending')
            ->with('member')
            ->latest()
            ->get();
    }
}
