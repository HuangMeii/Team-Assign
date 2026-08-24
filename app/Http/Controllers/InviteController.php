<?php

namespace App\Http\Controllers;

use App\Models\Invites;
use App\Services\InvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InviteController extends Controller
{
    public function __construct(
        private readonly InvitationService $invitations,
    ) {}

    /**
     * Danh sách lời mời nhận được (đang chờ).
     */
    public function index()
    {
        $invites = Invites::where('member_id', Auth::id())
            ->with(['group.leader', 'invitedBy'])
            ->where('status', 'Pending')
            ->paginate(10);

        return view('invites.index', compact('invites'));
    }

    /**
     * Chấp nhận lời mời tham gia nhóm.
     */
    public function accept(Invites $invite)
    {
        $result = $this->invitations->acceptInvite($invite, Auth::user());

        if ($result->succeeded()) {
            return redirect()->route('groups.show', $invite->group_id)
                ->with('success', $result->message());
        }

        return redirect()->back()->with($result->status(), $result->message());
    }

    /**
     * Từ chối lời mời tham gia nhóm.
     */
    public function reject(Invites $invite)
    {
        $result = $this->invitations->rejectInvite($invite, Auth::user());

        return redirect()->back()->with($result->status(), $result->message());
    }

    /**
     * Hủy lời mời (trưởng nhóm của nhóm gửi lời mời hoặc người nhận).
     */
    public function destroy(Invites $invite)
    {
        $user = Auth::user();
        $isLeader = $invite->group && (int) $invite->group->leader_id === (int) $user->user_id;
        $isMember = (int) $invite->member_id === (int) $user->user_id;

        if (!$isLeader && !$isMember) {
            return redirect()->back()->with('error', 'Không có quyền xóa');
        }

        $invite->delete();

        return redirect()->back()->with('success', 'Lời mời đã được hủy');
    }
}
