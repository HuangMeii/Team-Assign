<?php

namespace App\Observers;

use App\Models\Groups;
use App\Services\ClassStreamService;

/**
 * Tự ghi HOẠT ĐỘNG NHÓM vào BẢNG TIN của lớp (kiểu Google Classroom).
 *
 * Nguyên tắc chống nhiễu: chỉ ghi khi thay đổi CÓ Ý NGHĨA với lớp —
 *   created  → "Nhóm X đã được thành lập"
 *   updated  → đổi tên nhóm / đổi trưởng nhóm / gán đề tài
 *   deleted  → "Nhóm X đã giải tán"
 * Các thay đổi chỉ mang tính hệ thống (`status`, `updated_at`) KHÔNG ghi bảng tin
 * (tránh 2 bài cho cùng 1 thao tác tạo nhóm: create + updateStatus).
 *
 * Mọi lỗi ghi bảng tin đều bị nuốt trong ClassStreamService (fail-open) nên observer
 * không bao giờ làm hỏng luồng tạo/sửa/xoá nhóm.
 */
class GroupObserver
{
    public function __construct(private readonly ClassStreamService $stream) {}

    public function created(Groups $group): void
    {
        if (! $group->class_id) {
            return;
        }

        $this->stream->logGroupActivity(
            $group,
            'group_created',
            [
                'actor_name' => $group->leader?->name,
                'member_count' => $group->members()->count() + 1,
            ],
            null,
            'group:' . $group->group_id . ':created'
        );
    }

    public function updated(Groups $group): void
    {
        if (! $group->class_id) {
            return;
        }

        $changed = array_keys($group->getChanges());
        $memberCount = $group->members()->count() + 1;

        // 1) Đổi tên nhóm
        if (in_array('group_name', $changed, true)) {
            $oldName = $group->getOriginal('group_name');

            $this->stream->logGroupActivity(
                $group,
                'group_status',
                ['member_count' => $memberCount],
                'Nhóm "' . ($oldName ?: '?') . '" đã đổi tên thành "' . $group->group_name . '".'
            );
        }

        // 2) Đổi trưởng nhóm
        if (in_array('leader_id', $changed, true)) {
            $this->stream->logGroupActivity($group, 'group_leader_changed', [
                'member_name' => $group->leader?->name,
                'member_count' => $memberCount,
            ]);
        }

        // 3) Nhóm được gán đề tài (trực tiếp hoặc qua duyệt yêu cầu)
        if (in_array('topic_id', $changed, true) && $group->topic_id) {
            $this->stream->logGroupActivity($group, 'group_topic', [
                'topic_id' => $group->topic_id,
                'topic_name' => $group->topic?->name,
            ]);
        }
    }

    public function deleted(Groups $group): void
    {
        if (! $group->class_id) {
            return;
        }

        $this->stream->logGroupActivity(
            $group,
            'group_deleted',
            ['member_count' => $group->members()->count() + 1],
            null,
            'group:' . $group->group_id . ':deleted'
        );
    }
}
