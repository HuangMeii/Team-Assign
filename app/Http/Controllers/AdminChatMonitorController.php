<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\DirectMessage;
use App\Models\Groups;
use App\Models\Notifications;
use App\Models\User;
use App\Services\GroupChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Admin giám sát nội dung chat (cá nhân + nhóm), duyệt tin nhắn bị gắn cờ
 * (bỏ cờ / xóa) và gửi thông báo hệ thống tới toàn bộ thành viên nhóm.
 *
 * Moderation theo hướng FLAG-ONLY: tin nhắn/ảnh nhạy cảm vẫn được gửi bình
 * thường, chỉ được đánh dấu is_flagged = 1 để admin xem ở tab "Bị gắn cờ".
 */
class AdminChatMonitorController extends Controller
{
    public function flaggedCount()
    {
        return response()->json(['count' => $this->unseenFlaggedCount()]);
    }

    /**
     * Số tin nhắn/ảnh bị gắn cờ mà admin CHƯA mở trang Giám sát Chat để xem
     * (flagged_at mới hơn mốc users.flagged_seen_at của chính admin đó).
     *
     * Mở trang/tab "Bị gắn cờ" sẽ set lại mốc này (markFlaggedSeen) nên badge
     * "Giám sát Chat" reset về 0 ngay; khi có tin bị gắn cờ mới thì đếm lại từ 1.
     */
    private function unseenFlaggedCount(): int
    {
        $seenAt = DB::table('users')->where('user_id', Auth::id())->value('flagged_seen_at')
            ?: '1970-01-01 00:00:00';

        return DirectMessage::where('is_flagged', true)
                ->whereRaw('COALESCE(flagged_at, created_at) > ?', [$seenAt])->count()
            + ChatMessage::where('is_flagged', true)
                ->whereRaw('COALESCE(flagged_at, created_at) > ?', [$seenAt])->count();
    }

    /**
     * Đánh dấu admin đã mở tab "Bị gắn cờ" -> badge "Giám sát Chat" về 0.
     */
    private function markFlaggedSeen(): void
    {
        DB::table('users')->where('user_id', Auth::id())->update(['flagged_seen_at' => now()]);
    }

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'direct');

        $directMessages = collect();
        $groupMessages = collect();
        $flaggedDirect = collect();
        $flaggedGroup = collect();

        if ($tab === 'direct') {
            $directMessages = DirectMessage::with(['sender', 'recipient'])
                ->when($request->filled('search'), function ($q) use ($request) {
                    $q->where('content', 'like', '%' . $request->search . '%');
                })
                ->latest()
                ->paginate(20)
                ->withQueryString();
        } elseif ($tab === 'group') {
            $groupMessages = ChatMessage::with(['user', 'group'])
                ->when($request->filled('search'), function ($q) use ($request) {
                    $q->where('content', 'like', '%' . $request->search . '%');
                })
                ->when($request->filled('group_id'), function ($q) use ($request) {
                    $q->where('group_id', $request->group_id);
                })
                ->latest()
                ->paginate(20)
                ->withQueryString();
        } else {
            // Tab "Bị gắn cờ": gộp tin nhắn cá nhân + nhóm có is_flagged = 1.
            $tab = 'flagged';

            // Đang mở tab này = admin đã xem -> reset badge "Giám sát Chat" về 0.
            $this->markFlaggedSeen();

            $flaggedDirect = DirectMessage::with(['sender', 'recipient'])
                ->where('is_flagged', true)
                ->when($request->filled('search'), function ($q) use ($request) {
                    $q->where(function ($sub) use ($request) {
                        $sub->where('content', 'like', '%' . $request->search . '%')
                            ->orWhere('flag_reason', 'like', '%' . $request->search . '%');
                    });
                })
                ->when($request->filled('min_score'), function ($q) use ($request) {
                    $q->where('moderation_score', '>=', (float) $request->min_score);
                })
                ->orderByDesc('flagged_at')
                ->orderByDesc('id')
                ->paginate(20, ['*'], 'dpage')
                ->withQueryString();

            $flaggedGroup = ChatMessage::with(['user', 'group'])
                ->where('is_flagged', true)
                ->when($request->filled('search'), function ($q) use ($request) {
                    $q->where(function ($sub) use ($request) {
                        $sub->where('content', 'like', '%' . $request->search . '%')
                            ->orWhere('flag_reason', 'like', '%' . $request->search . '%');
                    });
                })
                ->when($request->filled('group_id'), function ($q) use ($request) {
                    $q->where('group_id', $request->group_id);
                })
                ->when($request->filled('min_score'), function ($q) use ($request) {
                    $q->where('moderation_score', '>=', (float) $request->min_score);
                })
                ->orderByDesc('flagged_at')
                ->orderByDesc('id')
                ->paginate(20, ['*'], 'gpage')
                ->withQueryString();
        }

        $groups = Groups::orderBy('group_name')->get(['group_id', 'group_name']);

        $flaggedCount = $this->unseenFlaggedCount();

        return view('admin.chat-monitoring', compact(
            'tab',
            'directMessages',
            'groupMessages',
            'flaggedDirect',
            'flaggedGroup',
            'groups',
            'flaggedCount'
        ));
    }

    public function destroyDirect($id)
    {
        DirectMessage::findOrFail($id)->delete();

        $successMessage = 'Đã xóa tin nhắn cá nhân vi phạm.';

        return back()->with('success', $successMessage);
    }

    public function destroyGroup($id)
    {
        ChatMessage::findOrFail($id)->delete();

        $successMessage = 'Đã xóa tin nhắn nhóm vi phạm.';

        return back()->with('success', $successMessage);
    }

    /**
     * Admin xác nhận tin nhắn cá nhân KHÔNG vi phạm -> bỏ cờ (giữ lại tin nhắn).
     */
    public function unflagDirect($id)
    {
        $message = DirectMessage::findOrFail($id);

        $message->update([
            'is_flagged'       => false,
            'flag_reason'      => null,
            'moderation_score' => null,
            'flagged_at'       => null,
        ]);

        $successMessage = 'Đã bỏ cờ tin nhắn cá nhân.';

        return back()->with('success', $successMessage);
    }

    /**
     * Admin xác nhận tin nhắn nhóm KHÔNG vi phạm -> bỏ cờ (giữ lại tin nhắn).
     */
    public function unflagGroup($id)
    {
        $message = ChatMessage::findOrFail($id);

        $message->update([
            'is_flagged'       => false,
            'flag_reason'      => null,
            'moderation_score' => null,
            'flagged_at'       => null,
        ]);

        $successMessage = 'Đã bỏ cờ tin nhắn nhóm.';

        return back()->with('success', $successMessage);
    }

    /**
     * Gửi thông báo hệ thống tới toàn bộ thành viên của 1 nhóm
     * (cả leader + members).
     */
    public function broadcastToGroup(Request $request)
    {
        $validated = $request->validate([
            'group_id' => ['required', 'exists:groups,group_id'],
            'title'    => ['required', 'string', 'max:255'],
            'message'  => ['required', 'string', 'max:2000'],
        ]);

        $group = Groups::with('members')->findOrFail($validated['group_id']);

        $userIds = $group->members->pluck('user_id')->push($group->leader_id)->unique();

        $now = now();
        $rows = $userIds->map(fn ($userId) => [
            'user_id'    => $userId,
            'type'       => 'admin_broadcast',
            'title'      => $validated['title'],
            'message'    => '[Nhóm ' . $group->group_name . '] ' . $validated['message'],
            'url'        => route('groups.chat.show', $group->group_id),
            'is_read'    => false,
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        DB::table('notifications')->insert($rows);

        // Giữ bộ đếm badge đồng bộ (insert thẳng bảng notifications, không qua NotificationService).
        DB::table('users')->whereIn('user_id', $userIds->all())->increment('unread_notifications');

        return back()->with('success', 'Đã gửi thông báo tới ' . count($rows) . ' thành viên nhóm ' . $group->group_name . '.');
    }

    /**
     * Admin gửi tin nhắn THÔNG BÁO / CẢNH BÁO thẳng vào KHUNG CHAT của nhóm.
     *
     * Khác với broadcastToGroup (chỉ tạo thông báo chuông), tin nhắn này nằm ngay
     * trong chat_messages nên hiện giữa khung chat nhóm, có badge chưa đọc cho
     * thành viên và broadcast realtime. Admin KHÔNG cần là thành viên nhóm và
     * KHÔNG tạo dòng group_members.
     */
    public function messageToGroup(Request $request)
    {
        $validated = $request->validate([
            'group_id' => ['required', 'exists:groups,group_id'],
            'type'     => ['required', 'in:' . ChatMessage::TYPE_ANNOUNCEMENT . ',' . ChatMessage::TYPE_WARNING],
            'content'  => ['required', 'string', 'max:1000'],
        ]);

        $group = Groups::findOrFail($validated['group_id']);

        app(GroupChatService::class)->send(
            $group,
            Auth::user(),
            $validated['content'],
            null,
            $validated['type']
        );

        $label = $validated['type'] === ChatMessage::TYPE_WARNING ? 'cảnh báo' : 'thông báo';

        return back()->with('success', 'Đã gửi ' . $label . ' tới nhóm ' . $group->group_name . '.');
    }

    /**
     * Gửi thông báo hệ thống tới TOÀN BỘ người dùng.
     */
    public function broadcastToAll(Request $request)
    {
        $validated = $request->validate([
            'title'   => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $actor = Auth::user();
        $count = 0;

        User::query()->select('user_id')->chunkById(500, function ($users) use ($validated, $actor, &$count) {
            foreach ($users as $u) {
                Notifications::create([
                    'user_id' => $u->user_id,
                    'type'    => 'admin_broadcast',
                    'title'   => $validated['title'],
                    'message' => $validated['message'],
                    'url'     => null,
                ]);
                $count++;
            }

            // Giữ bộ đếm badge đồng bộ với số thông báo vừa tạo.
            DB::table('users')->whereIn('user_id', $users->pluck('user_id')->all())->increment('unread_notifications');
        });

        return back()->with('success', "Đã gửi thông báo tới toàn bộ {$count} người dùng.");
    }
}
