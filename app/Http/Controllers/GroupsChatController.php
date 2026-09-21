<?php
namespace App\Http\Controllers;

use App\Models\Groups;
use App\Models\ChatMessage;
use App\Services\ChatUnreadService;
use App\Services\GroupChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupsChatController extends Controller
{
    /**
     * Admin được XEM mọi khung chat nhóm (chế độ giám sát, không cần là thành viên)
     * nhưng KHÔNG gửi tin nhắn thường — admin chỉ gửi thông báo/cảnh báo qua
     * route admin.chat.message.group.
     */
    private function isAdminViewer(): bool
    {
        return Auth::user()->role === 'admin';
    }

    /**
     * Quyền xem khung chat nhóm: thành viên, trưởng nhóm hoặc admin (giám sát).
     */
    private function authorizeGroupAccess(Groups $group): void
    {
        if ($this->isAdminViewer()) {
            return;
        }

        if (!$group->members->contains(Auth::id()) && $group->leader_id !== Auth::id()) {
            abort(403, 'Bạn không phải là thành viên của nhóm này.');
        }
    }

    public function showChat($groupId)
    {
        $group = Groups::with(['members', 'topic'])->where('group_id', $groupId)->firstOrFail();

        $isAdminViewer = $this->isAdminViewer();
        $this->authorizeGroupAccess($group);

        // Tải 50 tin nhắn gần nhất
        $messages = ChatMessage::where('group_id', $groupId)
            ->with('user')
            ->latest()
            ->take(50)
            ->get()
            ->reverse();

        // Mở trang chat nhóm = đã đọc các tin nhắn của NHÓM NÀY (group_chat_reads).
        // Mở đúng nhóm -> chỉ đánh dấu đã đọc riêng cho NHÓM NÀY (group_chat_reads),
        // sau đó tính lại badge tổng theo dữ liệu thật thay vì reset mù về 0
        // (reset mù sẽ xoá oan badge của các hội thoại/nhóm khác).
        //
        // Admin chỉ giám sát: KHÔNG ghi group_chat_reads (admin không là thành viên
        // nhóm nên badge "chưa đọc" theo nhóm không áp dụng cho admin).
        if (!$isAdminViewer) {
            app(ChatUnreadService::class)->markGroupRead((int) Auth::id(), (int) $group->group_id);
        }

        return view('groups.chat', [
            'group' => $group,
            'messages' => $messages,
            'isAdminViewer' => $isAdminViewer,
        ]);
    }

    /**
     * AJAX: đánh dấu đã đọc nhóm (gọi khi cửa sổ chat nhóm đang mở).
     * Trả về badge tổng mới để client cập nhật badge tổng khớp với server.
     */
    public function markRead(Request $request, $groupId)
    {
        $group = Groups::with('members')->where('group_id', $groupId)->firstOrFail();

        $this->authorizeGroupAccess($group);

        // Admin giám sát: không có badge "chưa đọc" theo nhóm -> trả về badge tổng hiện tại.
        if ($this->isAdminViewer()) {
            return response()->json([
                'message' => 'Admin chỉ giám sát nhóm nên không có badge chưa đọc.',
                'total'   => (int) Auth::user()->unread_message_count,
            ]);
        }

        $total = app(ChatUnreadService::class)->markGroupRead((int) Auth::id(), (int) $group->group_id);

        return response()->json([
            'message' => 'Đã đánh dấu đã đọc nhóm.',
            'total'   => $total,
        ]);
    }

    /**
     * Polling fallback cho khung chat nhóm: trả về các tin nhắn MỚI HƠN $after.
     *
     * WebSocket (Reverb/Echo) có thể không khả dụng (Reverb tắt, /broadcasting/auth
     * lỗi, mạng chập) nên khung chat còn hỏi lại server định kỳ để không bỏ sót
     * thông báo / cảnh báo của admin. Trả kèm last_id để client tiến mốc lần sau.
     */
    public function messages(Request $request, $groupId)
    {
        $group = Groups::with('members')->where('group_id', $groupId)->firstOrFail();

        $this->authorizeGroupAccess($group);

        $after = max((int) $request->get('after', 0), 0);
        $limit = min(max((int) $request->get('limit', 50), 1), 100);

        $messages = ChatMessage::where('group_id', $group->group_id)
            ->when($after > 0, fn ($query) => $query->where('id', '>', $after))
            ->with('user')
            ->orderBy('id')
            ->take($limit)
            ->get();

        return response()->json([
            'data'    => $messages,
            'last_id' => (int) ($messages->last()->id ?? $after),
        ]);
    }

    public function sendMessage(Request $request, $groupId)
    {
        $request->validate([
            'content'    => ['nullable', 'string', 'max:1000'],
            'attachment' => ['nullable', 'image', 'max:5120'],
        ]);

        $group = Groups::findOrFail($groupId);
        $user = Auth::user();
        $service = app(GroupChatService::class);

        // Chỉ thành viên / trưởng nhóm mới gửi được tin nhắn thường (type = member).
        // Admin không tham gia nhóm nên chỉ gửi được thông báo / cảnh báo qua
        // route admin.chat.message.group.
        if (!$service->isMember($group, $user)) {
            $denied = $this->isAdminViewer()
                ? 'Admin chỉ gửi được thông báo / cảnh báo vào nhóm.'
                : 'Bạn không thể gửi tin nhắn vào nhóm này.';

            return response()->json(['message' => $denied], 403);
        }

        $message = $service->send(
            $group,
            $user,
            $request->input('content'),
            $request->file('attachment')
        );

        return response()->json([
            'message' => 'Tin nhắn đã được gửi',
            'data' => $message,
        ]);
    }
}
