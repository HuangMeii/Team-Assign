<?php

namespace App\Observers;

use App\Models\Group_Members;
use App\Services\ClassStreamService;

/**
 * Ghi bảng tin lớp khi có người THAM GIA hoặc RỜI nhóm (bảng pivot `group_members`).
 *
 * Lưu ý: trưởng nhóm KHÔNG nằm trong pivot này (theo quy ước của GroupService),
 * nên "thành lập nhóm" do GroupObserver lo, còn ở đây là các thành viên thêm/bớt.
 */
class GroupMemberObserver
{
    public function __construct(private readonly ClassStreamService $stream) {}

    public function created(Group_Members $member): void
    {
        $group = $member->group;

        if (! $group || ! $group->class_id) {
            return;
        }

        $this->stream->logGroupActivity($group, 'group_member_joined', [
            'member_id' => $member->user_id,
            'member_name' => $member->user?->name ?? 'Một thành viên',
            'member_count' => $group->members()->count() + 1,
        ]);
    }

    public function deleted(Group_Members $member): void
    {
        $group = $member->group;

        if (! $group || ! $group->class_id) {
            return;
        }

        $this->stream->logGroupActivity($group, 'group_member_left', [
            'member_id' => $member->user_id,
            'member_name' => $member->user?->name ?? 'Một thành viên',
            'member_count' => max(1, $group->members()->count() + 1),
        ]);
    }
}
