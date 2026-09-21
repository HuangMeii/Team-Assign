<?php

namespace App\Services;

use App\Events\JoinRequestCreated;
use App\Events\JoinRequestResolved;
use App\Models\Groups;
use App\Models\Invites;
use App\Models\Join_Requests;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service tập trung các quy tắc nghiệp vụ LỜI MỜI & YÊU CẦU THAM GIA NHÓM:
 *
 * - Chỉ trưởng nhóm được mời thành viên
 * - Không được gửi lời mời vượt quá số lượng thành viên còn thiếu của nhóm
 * - Chỉ gửi thêm lời mời khi lời mời cũ bị từ chối / hết hiệu lực
 * - Cảnh báo khi sinh viên được mời đã tham gia nhóm khác
 * - 1 sinh viên chỉ thuộc 1 nhóm tại một thời điểm
 * - Nhóm đã đủ thành viên -> lời mời / yêu cầu trở thành Expired
 * - Trạng thái yêu cầu: Pending / Accepted / Rejected / Expired
 */
class InvitationService
{
    public function __construct(
        private readonly GroupService $groups,
    ) {}

    /**
     * Trưởng nhóm gửi lời mời tham gia nhóm.
     */
    public function sendInvite(Groups $group, User $actor, int $memberId): ServiceResult
    {
        // 1. Chỉ trưởng nhóm được mời
        if (!$this->groups->isLeader($group, $actor)) {
            return ServiceResult::error('Chỉ trưởng nhóm mới có thể mời thành viên!');
        }

        // 2. Nhóm chưa được đầy
        if ($this->groups->isFull($group)) {
            $max = $this->groups->maxMembers($group);
            return ServiceResult::error("Nhóm đã đủ {$max} thành viên, không thể mời thêm!");
        }

        // 3. Số lời mời đang chờ không được vượt quá số chỗ còn thiếu
        $pendingInviteCount = Invites::where('group_id', $group->group_id)
            ->where('status', 'Pending')
            ->count();
        $remainingSlots = $this->groups->maxMembers($group) - $this->groups->memberCount($group);

        if ($pendingInviteCount >= $remainingSlots) {
            return ServiceResult::error("Số lời mời đang chờ đã đạt tối đa ({$remainingSlots} chỗ còn thiếu). Hãy chờ phản hồi hoặc hủy lời mời cũ!");
        }

        // 4. Người được mời chưa phải là thành viên của nhóm
        if ($this->groups->isInGroup($group, $memberId)) {
            return ServiceResult::error('Người này đã là thành viên của nhóm!');
        }

        $invitedUser = User::find($memberId);
        if (!$invitedUser) {
            return ServiceResult::error('Không tìm thấy sinh viên!');
        }

        // 5. Cảnh báo: sinh viên được mời đã có nhóm trong LỚP NÀY.
        // Bugfix B1+B2: 1 sinh viên có thể ở nhiều lớp, nhưng MỖI LỚP chỉ 1 nhóm
        // → lọc động bằng hasGroupInClass (cột isHaveGroup đã bị xoá ở migration 2026_09_13_000001).
        if ($this->groups->hasGroupInClass($invitedUser, $group->class_id)) {
            return ServiceResult::error('Sinh viên này đã có nhóm trong lớp này, không thể mời!');
        }

        // 6. Không gửi trùng lời mời đang chờ
        $existingInvite = Invites::where([
            'group_id' => $group->group_id,
            'member_id' => $memberId,
            'status' => 'Pending',
        ])->first();

        if ($existingInvite) {
            return ServiceResult::warning('Đã gửi lời mời cho người này rồi!');
        }

        // 7. Tạo lời mời
        $invite = Invites::create([
            'group_id' => $group->group_id,
            'member_id' => $memberId,
            'invitedBy' => $actor->user_id,
            'status' => 'Pending',
        ]);

        $invite->load(['group', 'inviter']);

        // 8. Thông báo cho người được mời
        NotificationService::groupInviteCreated($invite);

        return ServiceResult::ok('Đã gửi lời mời thành công!', $invite);
    }

    /**
     * Trưởng nhóm hủy lời mời (chỉ khi còn đang chờ).
     */
    public function cancelInvite(Invites $invite, User $actor): ServiceResult
    {
        if (!$this->groups->isLeader($invite->group, $actor)) {
            return ServiceResult::error('Chỉ trưởng nhóm mới có thể hủy lời mời!');
        }

        if ($invite->status !== 'Pending') {
            return ServiceResult::error('Chỉ có thể hủy lời mời chưa được xử lý!');
        }

        $invite->delete();

        return ServiceResult::ok('Đã hủy lời mời!');
    }

    /**
     * Sinh viên chấp nhận lời mời tham gia nhóm.
     * Nếu nhóm đã đủ thành viên -> lời mời hết hiệu lực (Expired).
     */
    public function acceptInvite(Invites $invite, User $member): ServiceResult
    {
        // 1. Chỉ người được mời mới được xử lý
        if ((int) $invite->member_id !== (int) $member->user_id) {
            return ServiceResult::error('Bạn không có quyền thực hiện hành động này!');
        }

        // 2. Lời mời còn hiệu lực
        if ($invite->status !== 'Pending') {
            return ServiceResult::warning('Lời mời này đã được xử lý!');
        }

        // 3. Mỗi lớp chỉ 1 nhóm (1 sinh viên vẫn có thể ở nhiều lớp khác nhau)
        if ($this->groups->hasGroupInClass($member, $invite->group->class_id)) {
            return ServiceResult::error('Bạn đã có nhóm trong lớp này, không thể chấp nhận lời mời vào nhóm khác cùng lớp!');
        }

        // 4. Nhóm đã đủ thành viên -> lời mời hết hiệu lực
        if ($this->groups->isFull($invite->group)) {
            $invite->update(['status' => 'Expired']);
            return ServiceResult::error('Nhóm đã đủ thành viên, lời mời không còn hiệu lực!');
        }

        // 5. Thêm vào nhóm trong transaction
        DB::transaction(function () use ($invite, $member) {
            if (!$this->groups->isInGroup($invite->group, $member->user_id)) {
                $this->groups->addMember($invite->group, $member);
            }

            $invite->update(['status' => 'Accepted']);
            $this->groups->updateStatus($invite->group);
        });

        // 6. Sinh viên đã có nhóm trong lớp -> các yêu cầu Pending khác cùng lớp hết hiệu lực
        $this->expireMemberPendingRequestsInClass(
            (int) $member->user_id,
            (int) $invite->group->class_id,
            (int) $invite->group->group_id
        );

        // 7. Nhóm đã đủ thành viên -> các yêu cầu Pending còn lại của nhóm hết hiệu lực
        $this->expirePendingRequestsOfFullGroup($invite->group, null);

        return ServiceResult::ok('Đã chấp nhận lời mời tham gia nhóm!');
    }

    /**
     * Sinh viên từ chối lời mời tham gia nhóm.
     */
    public function rejectInvite(Invites $invite, User $member): ServiceResult
    {
        if ((int) $invite->member_id !== (int) $member->user_id) {
            return ServiceResult::error('Bạn không có quyền thực hiện hành động này!');
        }

        if ($invite->status !== 'Pending') {
            return ServiceResult::warning('Lời mời này đã được xử lý!');
        }

        $invite->update(['status' => 'Rejected']);

        return ServiceResult::ok('Đã từ chối lời mời!');
    }

    /**
     * Sinh viên gửi yêu cầu tham gia nhóm.
     */
    public function sendJoinRequest(Groups $group, User $member): ServiceResult
    {
        // 1. Mỗi lớp chỉ 1 nhóm (1 sinh viên vẫn có thể ở nhiều lớp khác nhau)
        if ($this->groups->hasGroupInClass($member, $group->class_id)) {
            return ServiceResult::error('Bạn đã có nhóm trong lớp này, không thể gửi yêu cầu tham gia nhóm khác cùng lớp!');
        }

        // 2. Chưa là thành viên của nhóm này
        if ($this->groups->isInGroup($group, $member->user_id)) {
            return ServiceResult::error('Bạn đã là thành viên của nhóm này!');
        }

        // 3. Nhóm chưa đầy
        if ($this->groups->isFull($group)) {
            $max = $this->groups->maxMembers($group);
            return ServiceResult::error("Nhóm đã đủ {$max} thành viên, không thể gửi yêu cầu!");
        }

        // 4. Không gửi trùng yêu cầu đang chờ
        $existingRequest = Join_Requests::where([
            'group_id' => $group->group_id,
            'member_id' => $member->user_id,
            'status' => 'Pending',
        ])->first();

        if ($existingRequest) {
            return ServiceResult::warning('Bạn đã gửi yêu cầu tham gia nhóm này rồi!');
        }

        // 5. Tạo yêu cầu
        $joinRequest = Join_Requests::create([
            'group_id' => $group->group_id,
            'member_id' => $member->user_id,
            'status' => 'Pending',
        ]);

        // 6. Thông báo cho trưởng nhóm
        NotificationService::joinRequestCreated($joinRequest);

        // 7. Realtime: badge "Yêu cầu" của trưởng nhóm qua kênh private chat.{leader_id}.
        // Badge "Yêu cầu" đi qua DUY NHẤT event này (realtime_badges.js đã bỏ nhánh
        // notification.type === 'join_request' để không tăng 2 lần mỗi yêu cầu).
        $joinRequest->loadMissing(['group', 'member']);

        // Fail-open: Reverb/WebSocket chết thì chỉ log, KHÔNG làm hỏng request
        // gửi yêu cầu tham gia nhóm (ShouldBroadcastNow ném lỗi khi không kết nối được).
        try {
            event(new JoinRequestCreated($joinRequest));
        } catch (\Throwable $e) {
            Log::warning('Broadcast join request failed (Reverb offline?): ' . $e->getMessage());
        }

        return ServiceResult::ok('Đã gửi yêu cầu tham gia nhóm!', $joinRequest);
    }

    /**
     * Sinh viên hủy yêu cầu tham gia của chính mình.
     */
    public function cancelJoinRequest(Join_Requests $request, User $member): ServiceResult
    {
        if ((int) $request->member_id !== (int) $member->user_id) {
            return ServiceResult::error('Bạn không có quyền thực hiện hành động này!');
        }

        if ($request->status !== 'Pending') {
            return ServiceResult::error('Chỉ có thể hủy yêu cầu chưa được xử lý!');
        }

        $request->delete();

        return ServiceResult::ok('Đã hủy yêu cầu!');
    }

    /**
     * Trưởng nhóm chấp nhận yêu cầu tham gia nhóm.
     * Nếu nhóm đã đủ thành viên -> yêu cầu hết hiệu lực (Expired).
     */
    public function approveJoinRequest(Join_Requests $request, User $leader): ServiceResult
    {
        // 1. Chỉ trưởng nhóm được duyệt
        if (!$this->groups->isLeader($request->group, $leader)) {
            return ServiceResult::error('Chỉ trưởng nhóm mới có thể chấp nhận yêu cầu!');
        }

        // 2. Yêu cầu còn hiệu lực
        if ($request->status !== 'Pending') {
            return ServiceResult::warning('Yêu cầu này đã được xử lý!');
        }

        // 3. Sinh viên đã có nhóm trong lớp này -> yêu cầu hết hiệu lực
        $member = User::find($request->member_id);

        if ($member && $this->groups->hasGroupInClass($member, (int) $request->group->class_id)) {
            $this->expireJoinRequest($request, 'Sinh viên đã có nhóm trong lớp này');

            return ServiceResult::error('Sinh viên đã có nhóm trong lớp này, yêu cầu không còn hiệu lực!');
        }

        // 4. Nhóm đã đủ thành viên -> yêu cầu hết hiệu lực
        if ($this->groups->isFull($request->group)) {
            $this->expireJoinRequest($request, 'Nhóm đã đủ thành viên');

            return ServiceResult::error('Nhóm đã đủ thành viên, yêu cầu không còn hiệu lực!');
        }

        // 5. Thêm vào nhóm trong transaction
        DB::transaction(function () use ($request, $member) {
            if ($member && !$this->groups->isInGroup($request->group, $member->user_id)) {
                $this->groups->addMember($request->group, $member);
            }

            $request->update(['status' => 'Accepted']);
            $this->groups->updateStatus($request->group);

            // Thông báo cho sinh viên được chấp nhận
            NotificationService::joinRequestApproved($request);
        });

        // 6. Sinh viên đã có nhóm -> các yêu cầu Pending khác cùng lớp hết hiệu lực
        if ($member) {
            $this->expireMemberPendingRequestsInClass(
                (int) $member->user_id,
                (int) $request->group->class_id,
                (int) $request->group->group_id
            );
        }

        // 7. Nhóm đã đủ thành viên -> các yêu cầu Pending còn lại của nhóm hết hiệu lực
        $this->expirePendingRequestsOfFullGroup($request->group, (int) $request->id);

        return ServiceResult::ok('Đã chấp nhận yêu cầu tham gia!');
    }

    /**
     * Trưởng nhóm từ chối yêu cầu tham gia nhóm.
     */
    public function rejectJoinRequest(Join_Requests $request, User $leader): ServiceResult
    {
        if (!$this->groups->isLeader($request->group, $leader)) {
            return ServiceResult::error('Chỉ trưởng nhóm mới có thể từ chối yêu cầu!');
        }

        if ($request->status !== 'Pending') {
            return ServiceResult::warning('Yêu cầu này đã được xử lý!');
        }

        $request->update(['status' => 'Rejected']);

        // Realtime: badge "Yêu cầu" của trưởng nhóm giảm ngay, nút Chấp nhận/Từ chối tự tắt
        $this->broadcastResolved($request, 'Rejected', 'Yêu cầu đã bị từ chối');

        return ServiceResult::ok('Đã từ chối yêu cầu tham gia!');
    }

    /**
     * Yêu cầu tham gia nhóm còn xử lý được hay không (dùng cho trang Yêu cầu và trang Thông báo).
     * Yêu cầu hết hiệu lực khi: đã xử lý, nhóm đủ thành viên, hoặc sinh viên đã có nhóm trong lớp.
     *
     * @return array{can: bool, reason: ?string, status: string}
     */
    public function canHandleJoinRequest(Join_Requests $joinRequest): array
    {
        if ($joinRequest->status !== 'Pending') {
            return [
                'can'    => false,
                'reason' => 'Yêu cầu đã được xử lý',
                'status' => (string) $joinRequest->status,
            ];
        }

        $group = $joinRequest->group;
        $member = $joinRequest->member;

        if (!$group || !$member) {
            return [
                'can'    => false,
                'reason' => 'Yêu cầu không còn tồn tại',
                'status' => (string) $joinRequest->status,
            ];
        }

        // Sinh viên đã có nhóm trong lớp này (hoặc đã ở trong chính nhóm này)
        if ($this->groups->hasGroupInClass($member, (int) $group->class_id)) {
            return [
                'can'    => false,
                'reason' => 'Sinh viên đã có nhóm trong lớp này',
                'status' => 'Expired',
            ];
        }

        // Nhóm đã đủ thành viên
        if ($this->groups->isFull($group)) {
            return [
                'can'    => false,
                'reason' => 'Nhóm đã đủ thành viên',
                'status' => 'Expired',
            ];
        }

        return ['can' => true, 'reason' => null, 'status' => 'Pending'];
    }

    /**
     * Đánh dấu một yêu cầu là hết hiệu lực (Expired) và báo realtime cho trưởng nhóm.
     */
    private function expireJoinRequest(Join_Requests $joinRequest, string $reason): void
    {
        $joinRequest->update(['status' => 'Expired']);

        $this->broadcastResolved($joinRequest, 'Expired', $reason);
    }

    /**
     * Sinh viên đã vào một nhóm trong lớp -> mọi yêu cầu Pending KHÁC của sinh viên
     * trong cùng lớp hết hiệu lực (mỗi lớp chỉ 1 nhóm).
     *
     * @return int Số yêu cầu đã chuyển sang Expired
     */
    public function expireMemberPendingRequestsInClass(int $memberId, int $classId, ?int $exceptGroupId = null): int
    {
        $requests = Join_Requests::where('member_id', $memberId)
            ->where('status', 'Pending')
            ->when($exceptGroupId, fn ($query) => $query->where('group_id', '!=', $exceptGroupId))
            ->whereIn('group_id', Groups::where('class_id', $classId)->select('group_id'))
            ->with('group')
            ->get();

        foreach ($requests as $request) {
            $this->expireJoinRequest($request, 'Sinh viên đã tham gia nhóm khác trong lớp này');
        }

        return $requests->count();
    }

    /**
     * Nhóm đã đủ thành viên -> mọi yêu cầu Pending còn lại của nhóm hết hiệu lực.
     *
     * @return int Số yêu cầu đã chuyển sang Expired
     */
    public function expirePendingRequestsOfFullGroup(Groups $group, ?int $exceptRequestId = null): int
    {
        if (!$this->groups->isFull($group)) {
            return 0;
        }

        $requests = Join_Requests::where('group_id', $group->group_id)
            ->where('status', 'Pending')
            ->when($exceptRequestId, fn ($query) => $query->where('id', '!=', $exceptRequestId))
            ->with('group')
            ->get();

        foreach ($requests as $request) {
            $this->expireJoinRequest($request, 'Nhóm đã đủ thành viên');
        }

        return $requests->count();
    }

    /**
     * Fail-open: Reverb/WebSocket chết thì chỉ log, KHÔNG làm hỏng request.
     */
    private function broadcastResolved(Join_Requests $joinRequest, string $status, ?string $reason = null): void
    {
        $joinRequest->loadMissing('group');

        if (!$joinRequest->group) {
            return;
        }

        try {
            event(new JoinRequestResolved($joinRequest, $status, $reason));
        } catch (\Throwable $e) {
            Log::warning('Broadcast join request resolved failed (Reverb offline?): ' . $e->getMessage());
        }
    }
}

