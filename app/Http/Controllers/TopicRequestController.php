<?php

namespace App\Http\Controllers;

use App\Models\Topic_requests;
use App\Services\TopicRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class TopicRequestController extends Controller
{
    public function __construct(
        private readonly TopicRegistrationService $topicRegistration,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        // Chỉ giảng viên và admin được xem danh sách yêu cầu đăng ký đề tài
        if (!in_array($user->role, ['lecturer', 'admin'])) {
            abort(403, 'Bạn không có quyền truy cập trang này!');
        }

        $query = Topic_requests::with(['topic', 'group', 'user'])
            ->orderBy('created_at', 'desc');

        // Giảng viên chỉ xem các yêu cầu thuộc lớp mình phụ trách
        if ($user->role === 'lecturer') {
            $classIds = $user->classes->pluck('class_id');
            $query->whereHas('topic', function ($q) use ($classIds) {
                $q->whereIn('class_id', $classIds);
            });
        }

        $topicRequests = $query->get();

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

        $result = $this->topicRegistration->approve($topicRequest, Auth::user());

        return back()->with($result->status(), $result->message());
    }

    /**
     * Reject the specified topic request.
     */
    public function reject(Request $request, Topic_requests $topic_request)
    {
        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $result = $this->topicRegistration->reject(
            $topic_request,
            Auth::user(),
            $validated['rejection_reason'] ?? null
        );

        return back()->with($result->status(), $result->message());
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Topic_requests $topic_request)
    {
        $result = $this->topicRegistration->destroy($topic_request, Auth::user());

        return back()->with($result->status(), $result->message());
    }
}