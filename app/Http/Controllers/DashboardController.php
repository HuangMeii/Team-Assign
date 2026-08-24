<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Topics;
use App\Models\Groups;
use App\Models\Topic_requests;
use App\Models\Join_requests;
use App\Models\Invites;
use App\Models\ClassSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return $this->adminDashboard();
        } else
        if ($user->role === 'lecturer') {
            return $this->lecturerDashboard($request);
        } else {
            return $this->studentDashboard();
        }
        
    }

   public function adminDashboard()
    {
        // 1. Thống kê tổng quan
        $stats = [
            'total_users'    => User::count(),
            'total_classes'  => ClassSection::count(),
            'total_topics'   => Topics::count(),
            'total_groups'   => Groups::count(),
            'pending_reqs'   => Topic_requests::where('status', 'Pending')->count(),
        ];

        // 2. Người dùng mới nhất
        $recentUsers = User::latest()->take(5)->get();

        // 3. Lớp học mới nhất
        $recentClasses = ClassSection::with(['lecturers', 'subject'])
            ->latest()
            ->take(5)
            ->get();

        // 4. Yêu cầu đăng ký đề tài đang chờ duyệt (trên toàn hệ thống)
        $pendingTopicRequests = Topic_requests::where('status', 'Pending')
            ->with(['topic', 'group.leader', 'topic.class'])
            ->latest()
            ->take(8)
            ->get();

        return view('dashboard.admin', compact(
            'stats', 
            'recentUsers', 
            'recentClasses', 
            'pendingTopicRequests'
        ));
    }

    public function lecturerDashboard(Request $request)
    {
        $user = Auth::user();

        // Lấy các lớp mà lecturer đang dạy
        $lecturerClasses = $user->classes;

        // Tính toán statistics cho từng lớp
        $classStats = [];
        foreach ($lecturerClasses as $class) {
            $topicsCount = Topics::where('class_id', $class->class_id)->count();

            $pendingCount = Topic_requests::whereHas('topic', function ($q) use ($class) {
                $q->where('class_id', $class->class_id);
            })->where('status', 'Pending')->count();

            $approvedCount = Topic_requests::whereHas('topic', function ($q) use ($class) {
                $q->where('class_id', $class->class_id);
            })->where('status', 'Accepted')->count();

            $groupsCount = Groups::where('class_id', $class->class_id)->count();

            $studentsCount = $class->users->where('role', 'student')->count();

            $classStats[$class->class_id] = [
                'class_name' => $class->class_name,
                'subject_name' => $class->subject->subject_name ?? 'N/A',
                'topics' => $topicsCount,
                'pending' => $pendingCount,
                'approved' => $approvedCount,
                'students' => $studentsCount,
                'groups' => $groupsCount,
            ];
        }

        // Overall statistics
        $lecturerClassIds = $lecturerClasses->pluck('class_id');

        $stats = [
            'total_classes' => $lecturerClasses->count(),
            'assigned_topics' => Topics::whereIn('class_id', $lecturerClassIds)->count(),
            'pending_requests' => Topic_requests::whereHas('topic', function ($query) use ($lecturerClassIds) {
                $query->whereIn('class_id', $lecturerClassIds);
            })->where('status', 'Pending')->count(),
            'approved_topics' => Topic_requests::whereHas('topic', function ($query) use ($lecturerClassIds) {
                $query->whereIn('class_id', $lecturerClassIds);
            })->where('status', 'Accepted')->count(),
        ];

        // Recent requests
        $recentRequests = Topic_requests::whereHas('topic', function ($query) use ($lecturerClassIds) {
            $query->whereIn('class_id', $lecturerClassIds);
        })->with(['topic.class', 'group.leader'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        $lecturerClasses = $user->classes;
        $lecturerClassIds = $lecturerClasses->pluck('class_id');
        $requestsByStatus = [
            'Pending' => Topic_requests::whereHas('topic', function ($q) use ($lecturerClassIds) {
                $q->whereIn('class_id', $lecturerClassIds);
            })->where('status', 'Pending')->count(),

            'Accepted' => Topic_requests::whereHas('topic', function ($q) use ($lecturerClassIds) {
                $q->whereIn('class_id', $lecturerClassIds);
            })->where('status', 'Accepted')->count(),

            'Rejected' => Topic_requests::whereHas('topic', function ($q) use ($lecturerClassIds) {
                $q->whereIn('class_id', $lecturerClassIds);
            })->where('status', 'Rejected')->count(),
        ];

        // Chart Data: Topics & Groups by Class
        $chartDataByClass = [];
        foreach ($lecturerClasses as $class) {
            $chartDataByClass['classes'][] = $class->class_name;
            $chartDataByClass['topics'][] = Topics::where('class_id', $class->class_id)->count();
            $chartDataByClass['groups'][] = Groups::where('class_id', $class->class_id)->count();
            $chartDataByClass['students'][] = $class->users->where('role', 'student')->count();
        }

        // Chart Data: Requests Timeline (last 7 days)
        $requestsTimeline = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $requestsTimeline['dates'][] = now()->subDays($i)->format('M d');
            $requestsTimeline['counts'][] = Topic_requests::whereHas('topic', function ($q) use ($lecturerClassIds) {
                $q->whereIn('class_id', $lecturerClassIds);
            })->whereDate('created_at', $date)->count();
        }

        // Chart Data: Topic Distribution
        $topicDistribution = [
            'with_requests' => Topic_requests::whereHas('topic', function ($q) use ($lecturerClassIds) {
                $q->whereIn('class_id', $lecturerClassIds);
            })->distinct('topic_id')->count(),

            'without_requests' => Topics::whereIn('class_id', $lecturerClassIds)
                ->whereNotIn('topic_id', function ($query) {
                    $query->select('topic_id')->from('topic_requests');
                })->count(),
        ];

        return view('dashboard.lecturer', compact(
            'stats',
            'lecturerClasses',
            'classStats',
            'recentRequests',
            'requestsByStatus',
            'chartDataByClass',
            'requestsTimeline',
            'topicDistribution'
        ));

    }
    // Thêm method để xem chi tiết lớp
public function classDetail($classId)
    {
        $user = Auth::user();
        $class = ClassSection::findOrFail($classId);

        // Kiểm tra quyền truy cập (nếu là giảng viên)
        if ($user->role === 'lecturer') {
           
            $isAssigned = $class->lecturers->contains('user_id', $user->user_id);
            
            // Hoặc kiểm tra xem có topic nào của giảng viên này trong lớp đó không (logic cũ của bạn)
            $hasTopicAccess = Topics::where('lecturer', $user->name)
                ->where('class_id', $classId)
                ->exists();

            if (!$isAssigned && !$hasTopicAccess) {
                abort(403, 'Bạn không có quyền truy cập lớp học phần này.');
            }
        }

        // Lấy danh sách đề tài
        $topics = Topics::where('class_id', $classId)
            ->with(['topic_requests.group'])
            ->get();

        // Lấy danh sách nhóm
        $groups = Groups::where('class_id', $classId)
            ->with(['leader', 'members'])
            ->get();

      
        $students = $class->users()
                        ->where('role', 'student')
                        ->orderBy('name')
                        ->get();
      
        $topicRequests = Topic_requests::whereHas('topic', function ($q) use ($classId) {
            $q->where('class_id', $classId);
        })->with(['topic', 'group'])->latest()->get();

        return view('dashboard.class-detail', compact('class', 'topics', 'groups', 'students', 'topicRequests'));
    }

    public function studentDashboard()
    {
        $user = Auth::user();

        // Lấy các nhóm mà user tham gia
        $myGroups = $this->getUserGroups($user);

        // Đếm số lượng thông báo chưa xử lý
        $pendingInvites = $this->countPendingInvites($user);
        $pendingRequests = $this->countPendingJoinRequests($user);

        // Lấy các đề tài của nhóm
        $myTopics = $this->getGroupTopics($myGroups);

        // Thông tin lớp và môn học
        $userClasses = $user->classes;
        $userSubjects = $this->getUserSubjects($userClasses);

        // Đề tài gợi ý
        $suggestedTopics = Topics::with('subject')
            ->inRandomOrder()
            ->limit(6)
            ->get();

        return view('user.dashboard', compact(
            'myGroups',
            'pendingInvites',
            'pendingRequests',
            'myTopics',
            'userClasses',
            'userSubjects',
            'suggestedTopics'
        ));
    }
}