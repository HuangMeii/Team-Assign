<?php

namespace App\Services;

use App\Events\DirectMessagesSeen;
use App\Models\ChatMessage;
use App\Models\DirectMessage;
use App\Models\Groups;
use App\Models\GroupChatRead;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service tập trung việc đếm / đánh dấu "tin nhắn chưa đọc" cho CHAT:
 *
 * - Chat 1-1: dựa trên direct_messages.is_read  -> badge theo ĐÚNG người gửi.
 * - Chat nhóm: dựa trên group_chat_reads.last_read_at -> badge theo ĐÚNG nhóm.
 *
 * users.unread_message_count vẫn là badge TỔNG (nav sidebar) nhưng được tính lại
 * từ dữ liệu thật (syncTotal) thay vì reset mù về 0 — nhờ vậy mở một hội thoại
 * không còn xoá oan badge của các hội thoại khác.
 */
class ChatUnreadService
{
    /** Mốc thời gian gốc cho nhóm chưa từng được mở (coi như mọi tin đều chưa đọc). */
    private const EPOCH = '1970-01-01 00:00:00';

    /**
     * Số tin chưa đọc theo từng NGƯỜI GỬI: [sender_id => count].
     *
     * @return array<int, int>
     */
    public function directCountsFor(int $userId): array
    {
        $query = DirectMessage::query()
            ->where('recipient_id', $userId)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false);

        $blockedIds = $this->blockedUserIds($userId);
        if ($blockedIds !== []) {
            $query->whereNotIn('sender_id', $blockedIds);
        }

        return $query
            ->selectRaw('sender_id, COUNT(*) as aggregate')
            ->groupBy('sender_id')
            ->pluck('aggregate', 'sender_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /**
     * Số tin chưa đọc theo từng NHÓM: [group_id => count].
     *
     * @param  array<int, int|string>  $groupIds
     * @return array<int, int>
     */
    public function groupCountsFor(int $userId, array $groupIds): array
    {
        $groupIds = $this->normalizeIds($groupIds);
        if ($groupIds === []) {
            return [];
        }

        $counts = ChatMessage::query()
            ->leftJoin('group_chat_reads as gcr', function ($join) use ($userId) {
                $join->on('gcr.group_id', '=', 'chat_messages.group_id')
                    ->where('gcr.user_id', '=', $userId);
            })
            ->whereIn('chat_messages.group_id', $groupIds)
            ->where('chat_messages.user_id', '!=', $userId)
            ->whereRaw('chat_messages.created_at > COALESCE(gcr.last_read_at, ?)', [self::EPOCH])
            ->groupBy('chat_messages.group_id')
            ->selectRaw('chat_messages.group_id as group_id, COUNT(*) as aggregate')
            ->pluck('aggregate', 'group_id')
            ->all();

        $result = [];
        foreach ($groupIds as $groupId) {
            $result[$groupId] = (int) ($counts[$groupId] ?? 0);
        }

        return $result;
    }

    /**
     * Thời điểm tin nhắn nhóm gần nhất: [group_id => 'Y-m-d H:i:s'].
     * Dùng để sắp xếp danh sách nhóm theo lịch sử trò chuyện.
     *
     * @param  array<int, int|string>  $groupIds
     * @return array<int, string>
     */
    public function groupLastMessageTimes(array $groupIds): array
    {
        $groupIds = $this->normalizeIds($groupIds);
        if ($groupIds === []) {
            return [];
        }

        return ChatMessage::query()
            ->whereIn('group_id', $groupIds)
            ->groupBy('group_id')
            ->selectRaw('group_id, MAX(created_at) as last_at')
            ->pluck('last_at', 'group_id')
            ->map(fn ($value) => (string) $value)
            ->all();
    }

    /**
     * Danh sách group_id mà user tham gia (thành viên hoặc trưởng nhóm).
     *
     * @return array<int, int>
     */
    public function groupIdsFor(int $userId): array
    {
        return Groups::query()
            ->where('leader_id', $userId)
            ->orWhereHas('members', function ($query) use ($userId) {
                $query->where('group_members.user_id', $userId);
            })
            ->pluck('group_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Tổng số tin chưa đọc (chat 1-1 + chat nhóm) — dùng cho badge tổng trên sidebar.
     */
    public function totalFor(int $userId): int
    {
        return array_sum($this->directCountsFor($userId))
            + array_sum($this->groupCountsFor($userId, $this->groupIdsFor($userId)));
    }

    /**
     * Tính lại users.unread_message_count theo dữ liệu thật và trả về giá trị mới.
     */
    public function syncTotal(int $userId): int
    {
        $total = $this->totalFor($userId);

        // Nếu là user đang đăng nhập thì phải cập nhật luôn instance trong bộ nhớ,
        // nếu không badge tổng trên sidebar (Auth::user()->unread_message_count)
        // vẫn hiển thị số cũ trong chính request hiện tại.
        $user = Auth::check() && (int) Auth::id() === $userId
            ? Auth::user()
            : User::find($userId);

        if ($user) {
            $user->unread_message_count = $total;
            $user->save();
        }

        return $total;
    }

    /**
     * Đánh dấu đã đọc toàn bộ tin nhắn 1-1 nhận từ $peerId, trả về badge tổng mới.
     *
     * Đồng thời ghi `seen_at` = thời điểm XEM ⇒ người gửi thấy tick chuyển
     * "đã gửi" (✓ xám) → "đã xem" (✓✓ xanh), và broadcast cho người gửi (fail-open).
     */
    public function markDirectRead(int $userId, int $peerId): int
    {
        $now = now();

        $updated = DirectMessage::query()
            ->where('sender_id', $peerId)
            ->where('recipient_id', $userId)
            ->where(function ($query) {
                // Tin chưa đọc HOẶC tin chưa có mốc xem (dữ liệu cũ trước khi có seen_at).
                $query->where('is_read', false)->orWhereNull('seen_at');
            })
            ->update([
                'is_read' => true,
                'seen_at' => $now,
            ]);

        if ($updated > 0) {
            try {
                broadcast(new DirectMessagesSeen((int) $peerId, $userId, (int) $updated, $now->toDateTimeString()));
            } catch (\Throwable $e) {
                Log::warning('Broadcast DirectMessagesSeen failed (Reverb offline?): ' . $e->getMessage());
            }
        }

        return $this->syncTotal($userId);
    }

    /**
     * Đánh dấu đã đọc tới thời điểm hiện tại cho một nhóm, trả về badge tổng mới.
     */
    public function markGroupRead(int $userId, int $groupId): int
    {
        GroupChatRead::updateOrCreate(
            ['user_id' => $userId, 'group_id' => $groupId],
            ['last_read_at' => now()]
        );

        return $this->syncTotal($userId);
    }

    /**
     * Danh sách user_id đã chặn HOẶC bị chặn (2 chiều) — loại khỏi số đếm badge.
     *
     * @return array<int, int>
     */
    public function blockedUserIds(int $userId): array
    {
        $rows = DB::table('blocked_users')
            ->where('blocker_id', $userId)
            ->orWhere('blocked_id', $userId)
            ->get(['blocker_id', 'blocked_id']);

        $ids = [];
        foreach ($rows as $row) {
            $other = (int) $row->blocker_id === $userId ? (int) $row->blocked_id : (int) $row->blocker_id;
            if ($other !== $userId) {
                $ids[] = $other;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return array<int, int>
     */
    private function normalizeIds(array $ids): array
    {
        $normalized = array_map('intval', array_filter($ids, fn ($id) => $id !== null && $id !== ''));

        return array_values(array_unique($normalized));
    }
}
