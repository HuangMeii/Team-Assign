<?php
namespace App\Http\Controllers;

use App\Models\Group_Members;
use App\Models\Invites;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InviteController extends Controller
{
    public function index()
    {
        $invites = Invites::where('member_id', Auth::id())
            ->with('group.leader', 'leader')
            ->where('status', 'Pending')
            ->paginate(10);
        return view('invites.index', compact('invites'));
    }

    /**
     * @param \App\Models\Invites $invite
     */
    public function accept(\App\Models\Invites $invite) // fully-qualified trong signature
    {
        if ($invite->member_id !== Auth::id()) {
            return redirect()->back()->with('error', 'Không có quyền thực hiện hành động này');
        }

        /** @var \App\Models\Invites $invite */ // ép kiểu rõ ràng cho Intelephense
        $invite->update(['status' => 'Accepted']);

        Group_Members::create([
            'group_id' => $invite->group_id,
            'user_id'  => $invite->member_id,
        ]);
        $user = Auth::user();
        /** @var \App\Models\User $user */
        $user->update(['is_have_group' => true]);

        return redirect()->route('groups.show', $invite->group_id)->with('success', 'Bạn đã tham gia nhóm');
    }

    public function reject(\App\Models\Invites $invite)
    {
        if ($invite->member_id !== Auth::id()) {
            return redirect()->back()->with('error', 'Không có quyền thực hiện hành động này');
        }

        /** @var \App\Models\Invites $invite */
        $invite->update(['status' => 'Rejected']);
        return redirect()->back()->with('success', 'Lời mời đã bị từ chối');
    }

    public function destroy(\App\Models\Invites $invite)
    {
        if (Auth::id() !== $invite->leader_id && Auth::id() !== $invite->member_id) {
            return redirect()->back()->with('error', 'Không có quyền xóa');
        }

        $invite->delete();
        return redirect()->back()->with('success', 'Lời mời đã được hủy');
    }
}
