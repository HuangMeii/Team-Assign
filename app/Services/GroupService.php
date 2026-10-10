<?php

namespace App\Services;

use App\Models\ClassSection;
use App\Models\Group_Members;
use App\Models\Groups;
use App\Models\Invites;
use App\Models\Join_Requests;
use App\Models\Topic_requests;
use App\Models\Topics;
use App\Models\User;
use App\Models\user_class;
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
     * Tổng số thành viên ĐANG HỌC của nhóm (ĐÃ BAO GỒM trưởng nhóm).
     *
     * L05 (Chốt 2a): đếm qua `Groups::activeMemberCount()` — loại sinh viên đã rời lớp
     * (`user_classes.status = 'left'`) và đếm HỢP (pivot ∪ trưởng nhóm) nên không đếm
     * trùng sau khi chuyển quyền trưởng nhóm. Nhóm mà tất cả đã rời lớp ⇒ 0.
     */
    public function memberCount(Groups $group): int
    {
        return $group->activeMemberCount();
    }

    /**
     * Nhóm "ghost": không còn thành viên nào đang học trong lớp.
     * Trưởng nhóm = người CUỐI CÙNG rời lớp vẫn được giữ lại (Chốt 2a) nên
     * `leader_id` không bao giờ NULL.
     */
    public function isGhost(Groups $group): bool
    {
        return ! $group->hasActiveMembers();
    }

    /**
     * Danh sách user_id đã RỜI LỚP của lớp chứa nhóm.
     *
     * @return int[]
     */
    public function leftUserIds(Groups $group): array
    {
        if (! $group->class_id) {
            return [];
        }

        return user_class::where('class_id', $group->class_id)
            ->where('status', user_class::STATUS_LEFT)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * L05 (Chốt 2a): sinh viên quay lại lớp — nếu đang là trưởng nhóm của nhóm cũ
     * trong lớp đó thì nhóm "hồi sinh" (KHÔNG tạo nhóm mới, KHÔNG thêm dòng pivot vì
     * trưởng nhóm không nằm trong `group_members`).
     *
     * @return Groups|null Nhóm được khôi phục (null nếu sinh viên không phải trưởng nhóm nhóm cũ)
     */
    public function restoreMembershipOnRejoin(User $user, int $classId): ?Groups
    {
        $group = Groups::where('class_id', $classId)
            ->where('leader_id', $user->user_id)
            ->first();

        if (! $group) {
            return null;
        }

        $this->updateStatus($group);

        return $group;
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
     * Nhóm đã có đề tài được duyệt cho LOẠI BÁO CÁO này hay chưa.
     *
     * @param  string|null  $reportType  final | midterm; null ⇒ bất kỳ loại nào (giữ hành vi cũ của môn 1 bài).
     */
    public function hasApprovedTopic(Groups $group, ?string $reportType = null): bool
    {
        $query = Topic_requests::where('group_id', $group->group_id)
            ->where('status', 'Accepted');

        if ($reportType === null) {
            return $query->exists();
        }

        return $query->whereHas('topic', fn ($q) => $q->where('report_type', $reportType))->exists();
    }

    /**
     * Các đề tài đã được duyệt của nhóm, gom theo LOẠI BÁO CÁO
     * (map: report_type => Topics). Nguồn sự thật là topic_requests.status = Accepted.
     */
    public function approvedTopics(Groups $group): Collection
    {
        $topicIds = Topic_requests::where('group_id', $group->group_id)
            ->where('status', 'Accepted')
            ->pluck('topic_id');

        return Topics::whereIn('topic_id', $topicIds)->get()->keyBy('report_type');
    }

    /**
     * Đề tài đã được duyệt theo một loại báo cáo cụ thể.
     */
    public function approvedTopicFor(Groups $group, string $reportType): ?Topics
    {
        return $this->approvedTopics($group)->get($reportType);
    }

    /**
     * Đồng bộ groups.topic_id = đề tài CUỐI KÌ đã được duyệt.
     * (Đề tài giữa kì chỉ nằm ở topic_requests — xem ghi chú 2026-09-23 lần 4.)
     */
    public function syncFinalTopic(Groups $group): void
    {
        $final = $this->approvedTopicFor($group, Topics::REPORT_FINAL);

        $group->update(['topic_id' => $final?->topic_id]);
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
     *
     * Dùng `Group_Members::firstOrCreate()` thay cho `attach()` vì attach() chỉ chèn
     * thẳng vào bảng pivot ⇒ KHÔNG bắn model events, khiến bảng tin lớp không ghi được
     * "thành viên mới". firstOrCreate còn tránh thêm trùng (unique group_id + user_id).
     */
    public function addMember(Groups $group, User $member): void
    {
        Group_Members::firstOrCreate(
            [
                'group_id' => $group->group_id,
                'user_id' => $member->user_id,
            ],
            ['role' => 'member']
        );
    }

    /**
     * Chuyển vai trò Sinh viên -> Nhóm trưởng sau khi tạo nhóm.
     */
    /**
     * Bugfix B2 [R71]: does this student already belong to a group in this class
     * (as leader or as pivot member)? Business rule: one group per class/subject.
     */
    public function hasGroupInClass(User $user, int $classId): bool
    {
        return Groups::where('class_id', $classId)
                ->where('leader_id', $user->user_id)
                ->exists()
            || Group_Members::where('user_id', $user->user_id)
                ->whereHas('group', function ($q) use ($classId) {
                    $q->where('class_id', $classId);
                })
                ->exists();
    }

    /**
     * Bugfix B2 [R71]: does this student belong to any group in any class?
     */
    public function hasGroupInAnyClass(User $user): bool
    {
        return $this->isLeaderOfAnyGroup($user) || $this->isMemberOfAnyGroup($user);
    }

    /**
     * Bugfix B3 [R13]: a student removed from a class must also be removed from
     * that class's groups:
     * - detach from group_members of groups in the class;
     * - if student is a group leader: transfer leadership to the first member,
     *   or delete the group when it has no members left;
     * - cancel pending invites / join requests of the student in that class.
     */
    public function removeUserFromClassGroups(User $student, int $classId): void
    {
        $groupIds = Groups::where('class_id', $classId)->pluck('group_id')->toArray();

        if (empty($groupIds)) {
            return;
        }

        DB::transaction(function () use ($student, $classId, $groupIds) {
            // 1. detach member from groups of this class
            //    (xoá TỪNG dòng để model events bắn ⇒ bảng tin lớp ghi được "thành viên rời nhóm")
            Group_Members::where('user_id', $student->user_id)
                ->whereIn('group_id', $groupIds)
                ->get()
                ->each(fn (Group_Members $row) => $row->delete());

            // 2. groups this student leads in this class
            $ledGroups = Groups::where('class_id', $classId)
                ->where('leader_id', $student->user_id)
                ->get();

            foreach ($ledGroups as $group) {
                $this->transferLeadershipOnLeave($group);
            }

            // 3. cancel pending invites / join requests of the student in this class
            Invites::where('member_id', $student->user_id)
                ->whereIn('group_id', $groupIds)
                ->where('status', 'Pending')
                ->delete();

            Join_Requests::where('member_id', $student->user_id)
                ->whereIn('group_id', $groupIds)
                ->where('status', 'Pending')
                ->delete();
        });
    }

    /**
     * Xử lý khi một sinh viên rời lớp đối với nhóm mà sinh viên đó đang là TRƯỞNG NHÓM.
     *
     * L05 (Chốt 2a): KHÔNG bỏ trưởng nhóm. Cụ thể:
     *  - Còn thành viên ĐANG HỌC trong lớp ⇒ chuyển quyền trưởng nhóm cho người vào nhóm sớm nhất.
     *  - Không còn ai đang học ⇒ GIỮ nhóm lại, `leader_id` = người CUỐI CÙNG rời lớp
     *    (nhóm "ghost" với 0 thành viên) để Admin/GV vẫn xem được lịch sử nhóm, đề tài,
     *    chat, bảng tin; chỉ đóng các lời mời / yêu cầu tham gia còn treo.
     *
     * Nhóm chỉ bị xóa CỨNG qua luồng có kiểm soát (GroupService::destroy()).
     */
    public function transferLeadershipOnLeave(Groups $group): void
    {
        $leftIds = $this->leftUserIds($group);

        $activeMembers = $group->members()
            ->orderBy('group_members.id')
            ->get()
            ->reject(fn (User $member) => in_array((int) $member->user_id, $leftIds, true));

        if ($activeMembers->isNotEmpty()) {
            $group->update(['leader_id' => $activeMembers->first()->user_id]);
            $this->updateStatus($group);

            return;
        }

        Invites::where('group_id', $group->group_id)
            ->where('status', 'Pending')
            ->update(['status' => 'Expired']);

        Join_Requests::where('group_id', $group->group_id)
            ->where('status', 'Pending')
            ->update(['status' => 'Expired']);

        $this->updateStatus($group);
    }

    /**
     * Sinh viên tự tạo nhóm mới (chỉ khi chưa thuộc nhóm nào).
     * Sau khi tạo thành công, hệ thống chuyển vai trò Sinh viên -> Nhóm trưởng.
     */
    public function createGroupByStudent(User $user, string $groupName, int $classId): ServiceResult
    {
        // 1. Mỗi sinh viên chỉ được thuộc một nhóm trong mỗi lớp học phần
        if ($this->hasGroupInClass($user, $classId)) {
            return ServiceResult::error('Bạn đã có nhóm trong lớp học phần này. Mỗi sinh viên chỉ được tham gia một nhóm trong một lớp!');
        }

        // 2. Sinh viên phải ĐANG tham gia lớp học phần này
        //    (chặn request giả mạo class_id của lớp mình không học)
        if (!$user->classes()->where('class_sections.class_id', $classId)->exists()) {
            return ServiceResult::error('Bạn không tham gia lớp học phần này nên không thể tạo nhóm!');
        }

        // 3. Lớp phải đang hoạt động
        $class = ClassSection::find($classId);
        if (!$class) {
            return ServiceResult::error('Không tìm thấy lớp học!');
        }
        if (isset($class->is_active) && !$class->is_active) {
            return ServiceResult::error('Lớp học này đã bị khóa, không thể tạo nhóm!');
        }

        // 4. Tạo nhóm trong transaction
        $group = DB::transaction(function () use ($user, $groupName, $classId) {
            $group = Groups::create([
                'group_name' => $groupName,
                'leader_id'  => $user->user_id,
                'class_id'   => $classId,
                'status'     => 'incomplete',
            ]);

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
        if (!$leader || $leader->role !== 'student') {
            return ServiceResult::error('Người được chỉ định làm trưởng nhóm phải là sinh viên!');
        }

        if ($this->hasGroupInClass($leader, $classId)) {
            return ServiceResult::error('Sinh viên được chỉ định đã thuộc một nhóm khác!');
        }

        $group = DB::transaction(function () use ($groupName, $classId, $leader) {
            $group = Groups::create([
                'group_name' => $groupName,
                'leader_id'  => $leader->user_id,
                'class_id'   => $classId,
                'status'     => 'incomplete',
            ]);


            return $group;
        });

        return ServiceResult::ok('Tạo nhóm thành công!', $group);
    }

    /**
     * L10 — XÓA MỀM nhóm (Admin + Giảng viên phụ trách lớp).
     *
     * Quyết định đã chốt: KHÔNG chặn nhóm đã có đề tài (khác bản cũ) — xóa nhóm sẽ
     * **nhả đề tài về trạng thái chưa đăng ký** để nhóm khác đăng ký lại bình thường.
     * Dữ liệu (thành viên, đề tài lịch sử, chat, bảng tin) được GIỮ LẠI để khôi phục/đối chiếu.
     */
    public function destroy(Groups $group, User $actor): ServiceResult
    {
        if ($deny = $this->denyGroupManagement($group, $actor)) {
            return $deny;
        }

        DB::transaction(function () use ($group) {
            $this->releaseTopic($group);

            // Hủy lời mời / yêu cầu còn treo nhưng GIỮ dòng làm lịch sử (không xóa cứng như trước).
            $group->invites()->where('status', 'Pending')->update(['status' => 'Expired']);
            $group->joinRequests()->where('status', 'Pending')->update(['status' => 'Expired']);

            $group->delete(); // L10: SoftDeletes ⇒ xóa mềm
        });

        return ServiceResult::ok(
            'Đã xóa nhóm. Đề tài của nhóm (nếu có) đã trở về trạng thái chưa đăng ký. Có thể khôi phục lại.'
        );
    }

    /**
     * L10 — Khôi phục nhóm đã xóa mềm. Nhóm KHÔNG tự lấy lại đề tài cũ
     * (tránh tranh chấp nếu đề tài đã được nhóm khác đăng ký).
     */
    public function restore(Groups $group, User $actor): ServiceResult
    {
        if ($deny = $this->denyGroupManagement($group, $actor)) {
            return $deny;
        }

        if (! $group->trashed()) {
            return ServiceResult::warning('Nhóm này chưa bị xóa.');
        }

        DB::transaction(function () use ($group) {
            $group->restore();
            // Khôi phục về trạng thái CHƯA CÓ ĐỀ TÀI (không tự lấy lại đề tài cũ).
            // `status` (incomplete/complete) được TÍNH LẠI theo số thành viên.
            $group->update(['topic_id' => null]);
            $this->updateStatus($group);
        });

        return ServiceResult::ok('Đã khôi phục nhóm. Nhóm đang ở trạng thái chưa có đề tài.');
    }

    /**
     * L10 — XÓA CỨNG (chỉ Admin): xóa nhóm cùng lời mời/yêu cầu/đăng ký và rút thành viên.
     * Chat + bảng tin của nhóm bị xóa theo do FK CASCADE.
     */
    public function forceDelete(Groups $group, User $actor): ServiceResult
    {
        if ($actor->role !== 'admin') {
            return ServiceResult::error('Chỉ quản trị viên mới được xóa vĩnh viễn nhóm!');
        }

        DB::transaction(function () use ($group) {
            $group->invites()->delete();
            $group->joinRequests()->delete();
            $group->topicRequests()->delete();
            $group->members()->detach();

            $group->forceDelete();
        });

        return ServiceResult::ok('Đã xóa vĩnh viễn nhóm và dữ liệu liên quan.');
    }

    /**
     * L10 — Nhả đề tài của nhóm (đề tài trở về "chưa đăng ký"):
     *  - `groups.topic_id` → NULL;
     *  - `topics.assigned_group_id` của nhóm → NULL (nhóm khác đăng ký lại được);
     *  - `topic_requests` đang chờ / đã duyệt → `Cancelled` + ghi lý do (GIỮ lịch sử).
     */
    private function releaseTopic(Groups $group): void
    {
        $group->update(['topic_id' => null]);

        Topics::where('assigned_group_id', $group->group_id)
            ->update(['assigned_group_id' => null]);

        $group->topicRequests()
            ->whereIn('status', ['Pending', 'Accepted'])
            ->update([
                'status' => 'Cancelled',
                'rejection_reason' => 'Nhóm đã bị xóa bởi quản trị viên/giảng viên',
            ]);
    }

    /**
     * L10 — Chỉ Admin hoặc Giảng viên phụ trách LỚP của nhóm mới được xóa/khôi phục.
     *
     * @return ServiceResult|null Thông báo lỗi, null = được phép
     */
    private function denyGroupManagement(Groups $group, User $actor): ?ServiceResult
    {
        if (! in_array($actor->role, ['admin', 'lecturer'], true)) {
            return ServiceResult::error('Bạn không có quyền xóa nhóm này!');
        }

        if ($actor->role === 'lecturer') {
            $ownsClass = $group->class_id
                && $group->class?->lecturers()->where('users.user_id', $actor->user_id)->exists();

            if (! $ownsClass) {
                return ServiceResult::error('Bạn không có quyền xóa nhóm này!');
            }
        }

        return null;
    }

    /**
     * Bó tối ưu D3 — Gộp số liệu thành viên + giới hạn của NHIỀU nhóm trong 3 query,
     * tránh N+1 khi render danh sách (`activeMemberCount()` + `maxMembers()` từng nhóm).
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, Groups> $groups
     * @return array<int, array{active:int, max:int, min:int}> key = group_id
     */
    public function memberStatsFor(Collection $groups): array
    {
        $groupIds = array_values(array_unique(array_map(
            fn ($group) => (int) $group->group_id,
            $groups->all()
        )));

        if ($groupIds === []) {
            return [];
        }

        // ① Thành viên pivot theo nhóm: [group_id => [user_id => true]]
        $pivotByGroup = [];
        foreach (Group_Members::whereIn('group_id', $groupIds)->get(['group_id', 'user_id']) as $row) {
            $pivotByGroup[(int) $row->group_id][(int) $row->user_id] = true;
        }

        // ② Người đã rời lớp theo lớp học: [class_id => [user_id => true]] (quy tắc 1 nhóm / 1 lớp của L05)
        $classIds = array_values(array_unique(array_filter(array_map(
            fn ($group) => $group->class_id ? (int) $group->class_id : null,
            $groups->all()
        ))));

        $leftByClass = [];
        if ($classIds !== []) {
            foreach (user_class::whereIn('class_id', $classIds)
                ->where('status', user_class::STATUS_LEFT)
                ->get(['class_id', 'user_id']) as $row) {
                $leftByClass[(int) $row->class_id][(int) $row->user_id] = true;
            }

            // Giới hạn thành viên: đề tài đầu tiên trong lớp (giống maxMembers()/minMembers()).
            $firstTopicByClass = Topics::whereIn('class_id', $classIds)
                ->whereNotNull('max_members')
                ->orderBy('topic_id')
                ->get(['class_id', 'min_members', 'max_members'])
                ->groupBy('class_id')
                ->map(fn ($rows) => $rows->first());
        } else {
            $firstTopicByClass = collect();
        }

        // ③ Đề tài riêng của từng nhóm (ưu tiên như maxMembers()/minMembers()).
        $topicIds = array_values(array_unique(array_filter(array_map(
            fn ($group) => $group->topic_id ? (int) $group->topic_id : null,
            $groups->all()
        ))));
        $topicsById = $topicIds !== []
            ? Topics::whereIn('topic_id', $topicIds)->get(['topic_id', 'min_members', 'max_members'])->keyBy('topic_id')
            : collect();

        // ④ Tính toán thuần trong RAM, không thêm query.
        $stats = [];
        foreach ($groups as $group) {
            $gid = (int) $group->group_id;
            $ids = array_keys($pivotByGroup[$gid] ?? []);

            if ($group->leader_id) {
                $ids[] = (int) $group->leader_id;
            }

            $ids = array_values(array_unique(array_map('intval', $ids)));

            $cid = $group->class_id ? (int) $group->class_id : null;
            if ($cid !== null && isset($leftByClass[$cid])) {
                $ids = array_values(array_diff($ids, array_keys($leftByClass[$cid])));
            }

            $ownTopic = $group->topic_id ? $topicsById->get((int) $group->topic_id) : null;
            $classTopic = $cid !== null ? $firstTopicByClass->get($cid) : null;

            $stats[$gid] = [
                'active' => count($ids),
                'max' => (int) ($ownTopic?->max_members ?? $classTopic?->max_members ?? 5),
                'min' => (int) ($ownTopic?->min_members ?? $classTopic?->min_members ?? 1),
            ];
        }

        return $stats;
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
