<?php
namespace App\Http\Controllers;

use App\Models\Groups;
use App\Models\ChatMessage;
use App\Events\NewChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupsChatController extends Controller
{
    public function showChat($groupId)
    {
        $group = Groups::with(['members', 'topic'])->where('group_id', $groupId)->firstOrFail();

        // Kiểm tra quyền (chỉ thành viên/leader mới được xem)
        if (!$group->members->contains(Auth::id()) && $group->leader_id !== Auth::id()) {
            abort(403, 'Bạn không phải là thành viên của nhóm này.');
        }

        // Tải 50 tin nhắn gần nhất
        $messages = ChatMessage::where('group_id', $groupId)
            ->with('user')
            ->latest()
            ->take(50)
            ->get()
            ->reverse();

        return view('groups.chat', [
            'group' => $group,
            'messages' => $messages,
        ]);
    }

    public function sendMessage(Request $request, $groupId)
    {
        $request->validate(['content' => 'required|string|max:1000']);

        $group = Groups::findOrFail($groupId);
        $user = Auth::user();

        $isMember = $group->members()
                          ->where('group_members.user_id', $user->user_id) 
                          ->exists();

        if (!$isMember && $group->leader_id !== $user->user_id) {
            return response()->json(['message' => 'Bạn không thể gửi tin nhắn vào nhóm này.'], 403);
        }

        $message = ChatMessage::create([
            'group_id' => $groupId,
            'user_id' => $user->user_id,
            'content' => $request->content,
        ]);

        
        broadcast(new NewChatMessage($message)); 

       
        $message->load('user'); 

        return response()->json([
            'message' => 'Tin nhắn đã được gửi',
            'data' => $message,
        ]);
    }
}