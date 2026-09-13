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
     * Bugfix C5 [R16]: Thêm lọc/tìm kiếm + load quan hệ để hiển thị Môn/Lớp.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Chỉ giảng viên và admin được xem danh sách yêu cầu đăng ký đề tài
        if (!in_array($user->role, ['lecturer', 'admin'])) {
            abort(403, 'Bạn không có quyền truy cập trang này!');
        }

        // Bugfix C5 [R16]: Load thêm quan hệ để hiển thị Môn học/Lớp
        $query = Topic_requests::with(['topic.class.subject', 'topic.class', 'group', 'user'])
            ->orderBy('created_at', 'desc');

        // Giảng viên chỉ xem các yêu cầu thuộc lớp mình phụ trách
        if ($user->role === 'lecturer') {
            $classIds = $user->classes->pluck('class_id');
            $query->whereHas('topic', function ($q) use ($classIds) {
                $q->whereIn('class_id', $classIds);
            });
        }

        // Bugfix C5 [R16]: Lọc theo từ khóa tìm kiếm (tên đề tài, tên nhóm, người gửi)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('topic', function ($tq) use ($search) {
                    $tq->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('group', function ($gq) use ($search) {
                    $gq->where('group_name', 'like', "%{$search}%");
                })
                ->orWhereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Bugfix C5 [R16]: Lọc theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Bugfix C5 [R16]: Lọc theo lớp học phần
        if ($request->filled('class_id')) {
            $query->whereHas('topic', function ($q) use ($request) {
                $q->where('class_id', $request->class_id);
            });
        }

        $topicRequests = $query->paginate(15)->withQueryString();

        // Lấy danh sách lớp cho filter (chỉ admin hoặc GV)
        $classes = collect();
        if ($user->role === 'admin') {
            $classes = \App\Models\ClassSection::with('subject')->orderBy('class_name')->get();
        } elseif ($user->role === 'lecturer') {
            $classes = $user->classes()->with('subject')->orderBy('class_name')->get();
        }

        return view('topic_requests.index', compact('topicRequests', 'classes'));
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