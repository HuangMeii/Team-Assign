<?php

namespace App\Console\Commands;

use App\Models\ClassPost;
use App\Models\Group_Members;
use App\Models\Groups;
use App\Models\Topic_requests;
use App\Services\ClassStreamService;
use Illuminate\Console\Command;

/**
 * Đưa DỮ LIỆU CŨ vào BẢNG TIN LỚP (kiểu Google Classroom) — chạy 1 lần sau khi bật tính năng.
 *
 *   php artisan class-stream:backfill --dry-run      # chỉ in kế hoạch, KHÔNG ghi
 *   php artisan class-stream:backfill               # ghi thật
 *   php artisan class-stream:backfill --class=3     # giới hạn 1 lớp học phần
 *   php artisan class-stream:backfill --with-status # thêm bài "cập nhật nhóm" cho nhóm đã đủ TV
 *
 * Sinh bài từ dữ liệu đang có:
 *   groups                    → `group_created`       (mốc: groups.created_at)
 *   group_members             → `group_member_joined` (mốc: created_at của bảng pivot)
 *   topic_requests (Accepted) → `group_topic`         (mốc: topic_requests.created_at —
 *                               bảng này KHÔNG có updated_at nên mốc là lúc GỬI yêu cầu)
 *   groups (status=complete)  → `group_status`        (chỉ khi bật --with-status)
 *
 * IDEMPOTENT: mỗi bài có `source_key` riêng (vd `backfill:group:12:created`, unique index)
 * nên chạy lại nhiều lần cũng KHÔNG nhân đôi, và không đụng các bài do observer sinh ra
 * trong lúc chạy thật (observer không set source_key).
 *
 * KHÔNG thể dựng lại (không có lịch sử): đổi tên nhóm, đổi trưởng nhóm, nhóm đã bị xoá.
 */
class ClassStreamBackfillCommand extends Command
{
    protected $signature = 'class-stream:backfill
        {--class= : Chỉ xử lý đúng 1 lớp học phần (class_id)}
        {--with-status : Ghi thêm bài "cập nhật nhóm" cho nhóm đã đủ thành viên}
        {--dry-run : Chỉ in kế hoạch, KHÔNG ghi vào DB}';

    protected $description = 'Đưa hoạt động nhóm + đề tài cũ vào bảng tin của lớp (idempotent)';

    public function handle(ClassStreamService $stream): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $withStatus = (bool) $this->option('with-status');
        $classId = $this->option('class') !== null ? (int) $this->option('class') : null;

        $this->line('=== Bảng tin lớp — backfill dữ liệu cũ ' . ($dryRun ? '(DRY RUN)' : '') . ' ===');

        $groups = Groups::query()
            ->whereNotNull('class_id')
            ->when($classId, fn ($query) => $query->where('class_id', $classId))
            ->orderBy('class_id')
            ->orderBy('group_id')
            ->get();

        if ($groups->isEmpty()) {
            $this->warn('Không có nhóm nào (thuộc lớp) để dựng lịch sử.');

            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;
        $byClass = [];

        foreach ($groups as $group) {
            // 1) Nhóm được thành lập
            $this->write($dryRun, $stream, $group, 'group_created', [
                'actor_name' => $group->leader?->name,
                'member_count' => $group->members()->count() + 1,
            ], 'backfill:group:' . $group->group_id . ':created', $group->created_at, $created, $skipped, $byClass);

            // 2) Từng thành viên tham gia nhóm
            //    Mốc thời gian: created_at của pivot; dữ liệu CŨ có thể NULL (do attach()
            //    trước đây không ghi timestamp) ⇒ fallback về thời điểm nhóm được thành lập
            //    (+1 giây mỗi thành viên để bảng tin vẫn xếp đúng thứ tự).
            $members = Group_Members::where('group_id', $group->group_id)->orderBy('id')->get();
            $memberCount = $group->members()->count() + 1;

            foreach ($members as $index => $member) {
                $joinedAt = $member->created_at ?? ($group->created_at ? $group->created_at->copy()->addSeconds($index + 1) : null);

                $this->write($dryRun, $stream, $group, 'group_member_joined', [
                    'member_id' => $member->user_id,
                    'member_name' => $member->user?->name ?? ('Thành viên #' . $member->user_id),
                    'member_count' => $memberCount,
                ], 'backfill:group:' . $group->group_id . ':member:' . $member->user_id, $joinedAt, $created, $skipped, $byClass);
            }

            // 3) Nhóm được duyệt đề tài
            $accepted = Topic_requests::where('group_id', $group->group_id)
                ->where('status', 'Accepted')
                ->get();

            foreach ($accepted as $request) {
                $this->write($dryRun, $stream, $group, 'group_topic', [
                    'topic_id' => $request->topic_id,
                    'topic_name' => $request->topic?->name,
                    'member_count' => $group->members()->count() + 1,
                ], 'backfill:topic_request:' . $request->request_id . ':accepted', $request->created_at, $created, $skipped, $byClass);
            }

            // 4) (tuỳ chọn) bài cập nhật số thành viên cho nhóm đã đủ người
            if ($withStatus && $group->status === 'complete') {
                $this->write($dryRun, $stream, $group, 'group_status', [
                    'member_count' => $group->members()->count() + 1,
                ], 'backfill:group:' . $group->group_id . ':status:complete', $group->updated_at, $created, $skipped, $byClass);
            }
        }

        $this->report($dryRun, $groups, $created, $skipped, $byClass);

        return self::SUCCESS;
    }

    /**
     * Ghi 1 bài backfill + cập nhật thống kê.
     *
     * @param  array<string, mixed>  $meta
     * @param  array<int, array{created: int, skipped: int}>  $byClass
     */
    private function write(
        bool $dryRun,
        ClassStreamService $stream,
        Groups $group,
        string $type,
        array $meta,
        string $sourceKey,
        mixed $createdAt,
        int &$created,
        int &$skipped,
        array &$byClass
    ): void {
        $key = (int) $group->class_id;
        $byClass[$key] = $byClass[$key] ?? ['created' => 0, 'skipped' => 0];

        if ($dryRun) {
            $byClass[$key]['created']++;

            return;
        }

        $post = $stream->logGroupActivity($group, $type, $meta, null, $sourceKey, $createdAt);

        if ($post) {
            $created++;
            $byClass[$key]['created']++;
        } else {
            $skipped++;
            $byClass[$key]['skipped']++;
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Groups>  $groups
     * @param  array<int, array{created: int, skipped: int}>  $byClass
     */
    private function report(bool $dryRun, $groups, int $created, int $skipped, array $byClass): void
    {
        $groupIds = $groups->pluck('group_id');

        $members = Group_Members::whereIn('group_id', $groupIds)->count();
        $topics = Topic_requests::whereIn('group_id', $groupIds)->where('status', 'Accepted')->count();

        $this->newLine();

        if ($dryRun) {
            $this->info('DRY RUN: dự kiến tối đa ' . ($groups->count() + $members + $topics) . ' bài (chưa ghi gì).');
        } else {
            $this->info("Đã tạo {$created} bài, bỏ qua {$skipped} bài (đã tồn tại từ trước).");
        }

        foreach ($byClass as $classKey => $stats) {
            $this->line("  · lớp #{$classKey}: +{$stats['created']} bài, bỏ qua {$stats['skipped']}");
        }

        $this->line('Tổng số bài HỆ THỐNG hiện có: ' . ClassPost::whereNull('user_id')->count());
    }
}
