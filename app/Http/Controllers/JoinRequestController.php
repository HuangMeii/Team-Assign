<?php
namespace App\Http\Controllers;

use App\Models\Join_requests;
use App\Models\Group_Members;
use App\Models\Groups;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; 

class JoinRequestController extends Controller
{
    use AuthorizesRequests; 

    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_id' => 'required|exists:groups,group_id',
        ]);

        $groupId = $validated['group_id'];
        $userId = Auth::id();

        $existing = Join_requests::where('group_id', $groupId)
            ->where('member_id', $userId)
            ->where('status', 'Pending')
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'Bạn đã gửi yêu cầu gia nhập nhóm này');
        }

        Join_requests::create([
            'group_id' => $groupId,
            'member_id' => $userId,
            'status' => 'Pending',
        ]);

        return redirect()->back()->with('success', 'Yêu cầu gia nhập đã được gửi');
    }

    public function pending(Groups $group)
    {
        $this->authorize('update', $group);

        $requests = Join_requests::where('group_id', $group->group_id)
            ->where('status', 'Pending')
            ->with('member')
            ->paginate(10);

        return view('join-requests.pending', compact('group', 'requests'));
    }

    public function approve(Join_requests $joinRequest)
    {
        $this->authorize('update', $joinRequest->group);

        $joinRequest->update(['status' => 'Accepted']);

        Group_Members::create([
            'group_id' => $joinRequest->group_id,
            'user_id' => $joinRequest->member_id,
        ]);

        return redirect()->back()->with('success', 'Yêu cầu đã được chấp nhận');
    }

    public function reject(Join_requests $joinRequest)
    {
        $this->authorize('update', $joinRequest->group);

        $joinRequest->update(['status' => 'Rejected']);
        return redirect()->back()->with('success', 'Yêu cầu đã bị từ chối');
    }

    public function destroy(Join_requests $joinRequest)
    {
        if (Auth::id() !== $joinRequest->member_id) {
            $this->authorize('update', $joinRequest->group);
        }

        $joinRequest->delete();
        return redirect()->back()->with('success', 'Yêu cầu đã được hủy');
    }
}