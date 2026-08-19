<?php

namespace App\Http\Controllers;

use App\Models\Topic_requests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class TopicRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $topicRequests = Topic_requests::with(['topic', 'group', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('topic_requests.index', compact('topicRequests'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'topic_id' => 'required|exists:topics,topic_id',
            'group_id' => 'required|exists:groups,group_id',
        ]);

        Topic_requests::create([
            'topic_id' => $validated['topic_id'],
            'group_id' => $validated['group_id'],
            'created_by' => Auth::id(),
            'status' => 'Pending',
        ]);

        return redirect()->back()->with('success', 'Yêu cầu đã được gửi');
    }

    /**
     * Approve the specified topic request.
     */
public function approve($id)
    {
        $topicRequest = Topic_requests::with(['group', 'topic'])->findOrFail($id);
        $user = Auth::user();

        // 1. Kiểm tra quyền: chỉ lecturer (và phải là GV hướng dẫn của đề tài đó) mới được duyệt
        if ($user->role !== 'lecturer') {
            abort(403, 'Bạn không có quyền thực hiện hành động này.');
        }

        // (Tùy chọn) Kiểm tra xem GV này có phải chủ đề tài không
        // if ($topicRequest->topic->lecturer !== $user->name) { ... }

        DB::transaction(function () use ($topicRequest) {
            // 2. Cập nhật trạng thái request hiện tại
            $topicRequest->update(['status' => 'Accepted']);

            // 3. Cập nhật topic_id cho Nhóm (Quan trọng: như bạn yêu cầu)
            if ($topicRequest->group) {
                $topicRequest->group->update(['topic_id' => $topicRequest->topic_id]);
            }

            // 4. Cập nhật assigned_group_id cho Đề tài (Để khóa đề tài, không cho nhóm khác chọn)
            if ($topicRequest->topic) {
                $topicRequest->topic->update(['assigned_group_id' => $topicRequest->group_id]);
            }

            // 5. Dọn dẹp các request còn lại (Logic tự động từ chối)
            
            // -> Từ chối các request khác đang chờ duyệt cho CÙNG ĐỀ TÀI này (vì đã có chủ)
            Topic_requests::where('topic_id', $topicRequest->topic_id)
                ->where('request_id', '!=', $topicRequest->request_id)
                ->where('status', 'Pending')
                ->update(['status' => 'Rejected']);

            // -> Từ chối các request khác của CÙNG NHÓM này cho các đề tài khác (vì nhóm đã có đề tài)
            Topic_requests::where('group_id', $topicRequest->group_id)
                ->where('request_id', '!=', $topicRequest->request_id)
                ->where('status', 'Pending')
                ->update(['status' => 'Rejected']);
        });
        
        return back()->with('success', 'Đã duyệt yêu cầu! Nhóm đã được gán đề tài chính thức.');
    }

    /**
     * Reject the specified topic request.
     */
    public function reject(Topic_requests $topic_request)
    {
         $userRole = Auth::user()->role;
        
        if ($userRole!='lecturer') {
            abort(403, 'Unauthorized action.');
        }

        $topic_request->update(['status' => 'Rejected']);
        return back()->with('success', 'Yêu cầu đã bị từ chối');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Topic_requests $topic_request)
    {
        // Kiểm tra quyền: chỉ người tạo hoặc admin mới được xóa
        $userRole=Auth::user()->role;
        if (Auth::id() !== $topic_request->created_by &&  $userRole=='admin') {
            abort(403, 'Unauthorized action.');
        }

        $topic_request->delete();
        return back()->with('success', 'Yêu cầu đã được xóa');
    }
}