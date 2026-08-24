<?php

namespace App\Services;

use App\Models\Groups;
use App\Models\Topic_requests;
use App\Models\Topics;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Service tập trung các quy tắc nghiệp vụ ĐĂNG KÝ ĐỀ TÀI:
 *
 * - Chỉ trưởng nhóm được đăng ký đề tài
 * - Nhóm phải đạt số lượng thành viên tối thiểu mới được đăng ký
 * - Nhóm và đề tài phải cùng một lớp
 * - Không đăng ký khi quá hạn (registration_deadline)
 * - Mỗi nhóm chỉ có MỘT đề tài được duyệt tại một thời điểm
 * - Bị từ chối thì được đăng ký lại đề tài khác
 * - Sau khi được duyệt, không được đổi đề tài
 * - Khi duyệt: tự động từ chối các yêu cầu khác (cùng đề tài / cùng nhóm)
 */
class TopicRegistrationService
{
    public function __construct(
        private readonly GroupService $groups,
    ) {}

    /**
     * Trưởng nhóm đăng ký đề tài (hoặc gửi lại sau khi bị từ chối).
     */
    public function register(Groups $group, Topics $topic, User $actor): ServiceResult
    {
        // 1. Chỉ trưởng nhóm được đăng ký
        if (!$this->groups->isLeader($group, $actor)) {
            return ServiceResult::error('Chỉ trưởng nhóm mới có thể đăng ký đề tài!');
        }

        // 2. Nhóm và đề tài phải cùng lớp
        if ($group->class_id !== $topic->class_id) {
            return ServiceResult::error('Nhóm và đề tài phải thuộc cùng một lớp mới được đăng ký!');
        }

        // 3. Nhóm phải đủ số lượng thành viên tối thiểu
        $memberCount = $this->groups->memberCount($group);
        $minMembers = $topic->min_members ?? $this->groups->minMembers($group);
        if ($memberCount < $minMembers) {
            return ServiceResult::error("Nhóm cần ít nhất {$minMembers} thành viên mới được đăng ký đề tài. Hiện nhóm có {$memberCount} thành viên!");
        }

        // 4. Kiểm tra hạn đăng ký
        if ($topic->registration_deadline && now()->greaterThan($topic->registration_deadline)) {
            return ServiceResult::error('Đã quá hạn đăng ký đề tài này!');
        }

        // 5. Nhóm đã có đề tài được duyệt thì không đăng ký thêm
        if ($this->groups->hasApprovedTopic($group)) {
            return ServiceResult::error('Nhóm đã có đề tài được duyệt, không thể đăng ký thêm đề tài khác!');
        }

        // 6. Đề tài không được gán cho nhóm khác
        if ($topic->assigned_group_id && (int) $topic->assigned_group_id !== (int) $group->group_id) {
            return ServiceResult::error('Đề tài này đã được gán cho nhóm khác!');
        }

        // 7. Kiểm tra yêu cầu hiện có của nhóm cho đề tài này
        $existing = Topic_requests::where('topic_id', $topic->topic_id)
            ->where('group_id', $group->group_id)
            ->first();

        if ($existing && $existing->status === 'Accepted') {
            return ServiceResult::warning('Nhóm đã đăng ký đề tài này rồi!');
        }

        if ($existing && $existing->status === 'Pending') {
            return ServiceResult::warning('Nhóm đã gửi yêu cầu cho đề tài này và đang chờ duyệt.');
        }

        // 8. Nếu từng bị từ chối -> được gửi lại yêu cầu
        if ($existing && $existing->status === 'Rejected') {
            $existing->update([
                'status' => 'Pending',
                'created_by' => $actor->user_id,
            ]);

            NotificationService::topicRequestCreated($existing);

            return ServiceResult::ok('Đã gửi lại yêu cầu đăng ký đề tài!');
        }

        // 9. Tạo yêu cầu mới
        $topicRequest = Topic_requests::create([
            'topic_id' => $topic->topic_id,
            'group_id' => $group->group_id,
            'created_by' => $actor->user_id,
            'status' => 'Pending',
        ]);

        NotificationService::topicRequestCreated($topicRequest);

        return ServiceResult::ok('Đã gửi yêu cầu đăng ký đề tài thành công!', $topicRequest);
    }

    /**
     * Trưởng nhóm hủy yêu cầu đăng ký (chỉ khi chưa được duyệt).
     */
    public function cancel(Topic_requests $request, User $actor): ServiceResult
    {
        if ($request->status !== 'Pending') {
            return ServiceResult::error('Chỉ có thể hủy đăng ký khi chưa được duyệt!');
        }

        if ((int) $request->created_by !== (int) $actor->user_id) {
            return ServiceResult::error('Bạn không có quyền hủy đăng ký này!');
        }

        $request->update(['status' => 'Cancelled']);

        return ServiceResult::ok('Đã hủy đăng ký đề tài!');
    }

    /**
     * Giảng viên duyệt yêu cầu đăng ký đề tài.
     * Sau khi duyệt: gán đề tài cho nhóm và tự động từ chối các yêu cầu còn lại.
     */
    public function approve(Topic_requests $request, User $lecturer): ServiceResult
    {
        if ($lecturer->role !== 'lecturer') {
            return ServiceResult::error('Bạn không có quyền thực hiện hành động này!');
        }

        if ($request->status !== 'Pending') {
            return ServiceResult::error('Yêu cầu này đã được xử lý!');
        }

        DB::transaction(function () use ($request) {
            // 1. Duyệt yêu cầu hiện tại
            $request->update(['status' => 'Accepted']);

            // 2. Gán đề tài cho nhóm
            if ($request->group) {
                $request->group->update(['topic_id' => $request->topic_id]);
            }

            // 3. Khóa đề tài (không cho nhóm khác chọn)
            if ($request->topic) {
                $request->topic->update(['assigned_group_id' => $request->group_id]);
            }

            // 4. Tự động từ chối các yêu cầu đang chờ khác cho CÙNG ĐỀ TÀI
            Topic_requests::where('topic_id', $request->topic_id)
                ->where('request_id', '!=', $request->request_id)
                ->where('status', 'Pending')
                ->update(['status' => 'Rejected']);

            // 5. Tự động từ chối các yêu cầu khác của CÙNG NHÓM
            Topic_requests::where('group_id', $request->group_id)
                ->where('request_id', '!=', $request->request_id)
                ->where('status', 'Pending')
                ->update(['status' => 'Rejected']);

            // 6. Thông báo cho trưởng nhóm và các thành viên
            NotificationService::topicRequestApproved($request);
        });

        return ServiceResult::ok('Đã duyệt yêu cầu! Nhóm đã được gán đề tài chính thức.');
    }

    /**
     * Giảng viên từ chối yêu cầu đăng ký đề tài (kèm lý do).
     */
    public function reject(Topic_requests $request, User $lecturer, ?string $reason = null): ServiceResult
    {
        if ($lecturer->role !== 'lecturer') {
            return ServiceResult::error('Bạn không có quyền thực hiện hành động này!');
        }

        if ($request->status !== 'Pending') {
            return ServiceResult::error('Yêu cầu này đã được xử lý!');
        }

        $request->update([
            'status' => 'Rejected',
            'rejection_reason' => $reason,
        ]);

        // Thông báo cho trưởng nhóm kèm lý do từ chối
        NotificationService::topicRequestRejected($request, $reason);

        return ServiceResult::ok('Yêu cầu đã bị từ chối');
    }

    /**
     * Xóa yêu cầu đăng ký (người tạo hoặc admin).
     */
    public function destroy(Topic_requests $request, User $actor): ServiceResult
    {
        if ((int) $actor->user_id !== (int) $request->created_by && $actor->role !== 'admin') {
            return ServiceResult::error('Bạn không có quyền xóa yêu cầu này!');
        }

        $request->delete();

        return ServiceResult::ok('Yêu cầu đã được xóa');
    }

    /**
     * Giảng viên/Admin trực tiếp gán đề tài cho nhóm (không qua luồng đăng ký).
     */
    public function assignDirectly(Groups $group, Topics $topic, User $actor): ServiceResult
    {
        if (!in_array($actor->role, ['lecturer', 'admin'])) {
            return ServiceResult::error('Bạn không có quyền gán đề tài!');
        }

        if ($topic->class_id !== $group->class_id) {
            return ServiceResult::error('Đề tài không thuộc lớp của nhóm này!');
        }

        if ($topic->assigned_group_id) {
            return ServiceResult::error('Đề tài này đã được gán cho nhóm khác!');
        }

        DB::transaction(function () use ($group, $topic) {
            $group->update(['topic_id' => $topic->topic_id]);
            $topic->update(['assigned_group_id' => $group->group_id]);
        });

        return ServiceResult::ok('Gán đề tài thành công!');
    }
}

