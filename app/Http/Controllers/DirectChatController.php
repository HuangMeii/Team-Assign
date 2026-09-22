<?php

namespace App\Http\Controllers;

use App\Events\DirectMessageSent;
use App\Models\BlockedUser;
use App\Models\DirectMessage;
use App\Models\Groups;
use App\Models\User;
use App\Services\ChatUnreadService;
use App\Services\ImageModerationService;
use App\Services\ViolationDetection\FlagHelper;
use App\Services\ViolationDetection\SensitiveModerationService;
use App\Services\ViolationDetection\TextModerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DirectChatController extends Controller
{
    public function index(Request $request)
    {
        $users = $this->sidebarUsers($request);

        // Các nhóm của user hiện tại (tab chat nhóm) + số tin chưa đọc theo từng nhóm
        [$myGroups, $groupUnread] = $this->sidebarGroups($request);

        $mode = $request->get('mode', 'direct');

        // Lưu ý: KHÔNG reset unread ở đây. Badge chỉ được xoá khi người dùng mở
        // đúng hội thoại (chat.show) hoặc mở đúng nhóm (groups.chat.show).
        // Nếu reset ở trang danh sách, badge dùng chung sẽ bị xoá oan.

        return view('chat.index', compact('users', 'myGroups', 'groupUnread', 'mode'));
    }

    public function show(Request $request, User $user)
    {
        abort_if($user->user_id === Auth::id(), 404);

        // Không cho chat với người đã bị chặn (cả hai chiều)
        if (Auth::user()->hasBlocked($user->user_id) || $user->hasBlocked(Auth::id())) {
            return redirect()->route('chat.index')
                ->with('error', 'Bạn đã chặn người dùng này hoặc bị người dùng này chặn.');
        }

        // Mở đúng hội thoại => chỉ đánh dấu đã đọc tin nhắn nhận từ ĐÚNG người này.
        // Badge tổng (users.unread_message_count) được tính lại theo dữ liệu thật,
        // nên badge của các hội thoại khác KHÔNG bị xoá oan.
        app(ChatUnreadService::class)->markDirectRead((int) Auth::id(), (int) $user->user_id);

        $users = $this->sidebarUsers($request);
        [$myGroups, $groupUnread] = $this->sidebarGroups($request);

        $mode = $request->get('mode', 'direct');
        $messages = $this->conversationQuery($user)
            ->with(['sender', 'recipient'])
            ->orderByDesc('id')
            ->take(self::HISTORY_LIMIT)
            ->get()
            ->reverse()
            ->values();

        return view('chat.index', compact('users', 'user', 'messages', 'myGroups', 'groupUnread', 'mode'));
    }

    /** Số tin nhắn tải lần đầu; nút "Tải thêm tin nhắn cũ" sẽ lấy tiếp theo từng khối. */
    public const HISTORY_LIMIT = 100;

    /**
     * AJAX: đánh dấu đã đọc hội thoại với $user (gọi khi cửa sổ chat đang mở).
     * Trả về badge tổng mới để client cập nhật badge tổng khớp với server.
     */
    public function markRead(Request $request, User $user)
    {
        abort_if($user->user_id === Auth::id(), 404);

        $total = app(ChatUnreadService::class)->markDirectRead((int) Auth::id(), (int) $user->user_id);

        return response()->json([
            'message' => 'Đã đánh dấu đã đọc.',
            'total'   => $total,
        ]);
    }
    /**
     * Cột trái "Người dùng": kèm
     *  - unread_count: số tin chưa đọc ĐÚNG người gửi đó (badge theo từng hội thoại)
     *  - last_message_at: thời điểm tin nhắn gần nhất của cặp (lịch sử trò chuyện)
     *
     * Thứ tự: hội thoại có tin chưa đọc lên đầu -> tin nhắn gần nhất trước ->
     * chưa từng trò chuyện thì xếp theo tên.
     */
    private function sidebarUsers(Request $request)
    {
        $me = (int) Auth::id();

        $unreadSub = DirectMessage::query()
            ->selectRaw('COUNT(*)')
            ->whereColumn('sender_id', 'users.user_id')
            ->where('recipient_id', $me)
            ->where('is_read', false);

        $lastMessageSub = DirectMessage::query()
            ->selectRaw('MAX(created_at)')
            ->where(function ($query) use ($me) {
                $query->where('sender_id', $me)
                    ->whereColumn('recipient_id', 'users.user_id');
            })
            ->orWhere(function ($query) use ($me) {
                $query->where('recipient_id', $me)
                    ->whereColumn('sender_id', 'users.user_id');
            });

        return User::query()
            ->where('user_id', '!=', $me)
            // Không hiển thị người đã chặn / bị chặn (2 chiều): không thể chat nên
            // badge của họ sẽ không bao giờ xoá được.
            ->whereRaw(
                'NOT EXISTS (SELECT 1 FROM blocked_users AS bu'
                . ' WHERE (bu.blocker_id = ? AND bu.blocked_id = users.user_id)'
                . ' OR (bu.blocked_id = ? AND bu.blocker_id = users.user_id))',
                [$me, $me]
            )
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            })
            ->when($request->filled('role') && $request->role !== 'all', function ($query) use ($request) {
                $query->where('role', $request->role);
            })
            ->select('users.*')
            ->selectSub($unreadSub, 'unread_count')
            ->selectSub($lastMessageSub, 'last_message_at')
            // 1) Hội thoại còn tin chưa đọc lên đầu (ORDER BY dùng lại biểu thức
            //    tổng hợp vì MySQL không đảm bảo dùng alias trong biểu thức).
            ->orderByRaw(
                '(SELECT COUNT(*) FROM direct_messages AS dm_unread'
                . ' WHERE dm_unread.sender_id = users.user_id'
                . ' AND dm_unread.recipient_id = ?'
                . ' AND dm_unread.is_read = 0) > 0 DESC',
                [$me]
            )
            // 2) Tin nhắn gần nhất trước (chưa từng chat -> NULL -> xuống cuối)
            ->orderByDesc('last_message_at')
            // 3) Chưa có lịch sử trò chuyện -> theo tên
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * Tab "Chat nhóm": danh sách nhóm của user + số tin chưa đọc theo từng nhóm.
     *
     * Thứ tự: nhóm có tin chưa đọc trước -> tin nhắn nhóm gần nhất -> tên nhóm.
     *
     * Admin (không là thành viên nhóm nào) -> thấy TẤT CẢ nhóm trong hệ thống để
     * giám sát và gửi thông báo/cảnh báo; badge chưa đọc trả về mảng RỖNG vì badge
     * theo nhóm chỉ có nghĩa với thành viên/trưởng nhóm.
     *
     * @return array{0: \Illuminate\Support\Collection<int, \App\Models\Groups>, 1: array<int, int>}
     */
    private function sidebarGroups(Request $request): array
    {
        // Admin không là thành viên/trưởng nhóm nào -> cho thấy TẤT CẢ nhóm (kèm tìm
        // kiếm theo tên) để giám sát nội dung và gửi thông báo / cảnh báo vào nhóm.
        // Badge "chưa đọc" theo nhóm không áp dụng cho admin nên trả về mảng rỗng.
        if (Auth::user()->role === 'admin') {
            return [$this->adminGroups($request), []];
        }

        $service = app(ChatUnreadService::class);
        $me = (int) Auth::id();

        $myGroups = Auth::user()->groupsJoined()->orderBy('group_name')->get()
            ->merge(Auth::user()->groupsLed()->orderBy('group_name')->get())
            ->unique('group_id')
            ->values();

        $groupIds = $myGroups->pluck('group_id')->all();

        $groupUnread  = $service->groupCountsFor($me, $groupIds);
        $groupLastMsg = $service->groupLastMessageTimes($groupIds);

        $myGroups = $myGroups
            ->sortByDesc(fn ($group) => [
                (int) (($groupUnread[$group->group_id] ?? 0) > 0),
                $groupLastMsg[$group->group_id] ?? '1970-01-01 00:00:00',
                (string) $group->group_name,
            ])
            ->values();

        return [$myGroups, $groupUnread];
    }

    /**
     * Tab "Chat nhóm" của admin: TẤT CẢ nhóm (lọc theo tên nếu có ?gsearch=...).
     *
     * Kèm số thành viên, thời điểm tin nhắn cuối và số tin bị gắn cờ để admin biết
     * nhóm nào đang cần chú ý trước khi mở khung chat / gửi thông báo.
     */
    private function adminGroups(Request $request): \Illuminate\Database\Eloquent\Collection
    {
        $search = trim((string) $request->get('gsearch', ''));

        return Groups::query()
            ->withCount('members')
            ->withCount(['chatMessages as flagged_count' => fn ($query) => $query->where('is_flagged', true)])
            ->withMax('chatMessages', 'created_at')
            ->when($search !== '', fn ($query) => $query->where('group_name', 'like', '%' . $search . '%'))
            ->orderBy('group_name')
            ->get();
    }

    /**
     * AJAX: tải thêm tin nhắn CŨ HƠN (phân trang ngược) cho hội thoại với $user.
     *
     * Trả về HTML đã render (dùng chung partial với lần tải đầu) để client chèn lên đầu
     * khung chat mà không phải dựng lại markup — trạng thái tick + tách ngày vẫn nhất quán.
     */
    public function history(Request $request, User $user)
    {
        abort_if($user->user_id === Auth::id(), 404);

        $before = max((int) $request->get('before', 0), 0);
        $limit = min(max((int) $request->get('limit', 50), 1), 100);
        // Ngày của tin đang hiển thị trên cùng (client gửi lên) — tránh lặp nhãn ngày.
        $previousDate = $request->get('previous_date');

        $messages = $this->conversationQuery($user)
            ->when($before > 0, fn ($query) => $query->where('id', '<', $before))
            ->with(['sender', 'recipient'])
            ->orderByDesc('id')
            ->take($limit)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'ok' => true,
            'html' => view('chat.partials.messages', [
                'messages' => $messages,
                'previousDate' => $previousDate,
            ])->render(),
            'first_id' => (int) ($messages->first()->id ?? 0),
            'has_more' => $messages->count() === $limit,
        ]);
    }

    /**
     * Truy vấn tin nhắn giữa user đang đăng nhập và $user (cả 2 chiều).
     *
     * LƯU Ý: phải BỌC NGOẶC toàn bộ điều kiện 2 chiều — nếu không, khi ghép thêm
     * `->where('id', '<', ...)` (phân trang lịch sử) thì SQL thành
     * `(A) OR (B AND id < x)` do AND ưu tiên hơn OR ⇒ mất tác dụng lọc.
     */
    private function conversationQuery(User $user)
    {
        $authId = Auth::id();

        return DirectMessage::query()->where(function ($outer) use ($user, $authId) {
            $outer->where(function ($inner) use ($user, $authId) {
                $inner->where('sender_id', $authId)->where('recipient_id', $user->user_id);
            })->orWhere(function ($inner) use ($user, $authId) {
                $inner->where('sender_id', $user->user_id)->where('recipient_id', $authId);
            });
        });
    }

    public function send(Request $request, User $user)
    {
        abort_if($user->user_id === Auth::id(), 404);

        // Kiểm tra chặn (hai chiều)
        if (Auth::user()->hasBlocked($user->user_id) || $user->hasBlocked(Auth::id())) {
            return response()->json(['message' => 'Bạn không thể gửi tin nhắn tới người dùng đã bị chặn.'], 403);
        }

        $validated = $request->validate([
            'content'     => ['nullable', 'string', 'max:2000'],
            'attachment'  => ['nullable', 'image', 'max:5120'],
        ]);

        $messageData = [
            'sender_id'    => Auth::id(),
            'recipient_id'   => $user->user_id,
            'content'        => $validated['content'] ?? '',
        ];

        // Flag-only moderation: text (CSV rule-based, later PhoBERT) + image (Vision)
        // both only set is_flagged, never block sending.
        $textCheck = TextModerationService::check($validated['content'] ?? '');
        $sensitiveCheck = SensitiveModerationService::check($validated['content'] ?? '');
        $imageCheck = null;

        // Xử lý upload ảnh đính kèm (chỉ gắn cờ ảnh nhạy cảm, không chặn)
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('chat-attachments', 'public');

            $imageCheck = ImageModerationService::checkStoredImage($path);
            if (!$imageCheck['passed']) {
                Log::warning('Direct chat image flagged by Vision: ' . ($imageCheck['violations'] ?? 'unknown'));
            }

            $messageData['attachment'] = $path;
        }

        $messageData = array_merge($messageData, FlagHelper::merge($textCheck, $imageCheck, $sensitiveCheck));

        $message = DirectMessage::create($messageData);
        $message->loadMissing(['sender', 'recipient']);

        // Tăng unread count cho người nhận
        $user->increment('unread_message_count');

        // Broadcast real-time tới kênh private của người nhận (fail-open: Reverb chết thì bỏ qua)
        try {
            broadcast(new DirectMessageSent($message));
        } catch (\Throwable $e) {
            Log::warning('Broadcast direct message failed (Reverb offline?): ' . $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Đã gửi tin nhắn.',
                'data'    => $message,
            ]);
        }

        return back()->with('success', 'Đã gửi tin nhắn.');
    }
}
