<?php

namespace App\Http\Controllers;

use App\Models\ClassSection;
use App\Models\Group_Members;
use App\Models\Groups;
use App\Models\Topic_requests;
use App\Models\Topics;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Thống kê hệ thống (Admin).
 *
 * Hoàn thiện lại sau audit 2026-09-18 — schema hiện tại (sau migration
 * 2026_09_13_remove_leader_role_and_is_have_group_from_users_table):
 *  - users KHÔNG còn cột is_have_group, role enum = student|lecturer|admin.
 *  - "Trưởng nhóm" derive động từ groups.leader_id (User::is_leader), KHÔNG còn role 'leader'.
 *  - topic_requests.status = Pending|Accepted|Rejected (+Cancelled/Expired nếu enum còn).
 */
class StatisticsController extends Controller
{
    /** Trang chính: 5 thẻ tổng hợp + link tới 4 trang chi tiết. */
    public function index()
    {
        $stats = [
            'users' => User::count(),
            'topics' => Topics::count(),
            'groups' => Groups::count(),
            'requests' => Topic_requests::count(),
            'pendingRequests' => Topic_requests::where('status', 'Pending')->count(),
        ];

        return view('statistics.index', $stats);
    }

    /** Thống kê đề tài: còn trống / đã có nhóm / theo GV / theo lớp học phần. */
    public function topicStatistics()
    {
        $totalTopics = Topics::count();
        $freeTopics = Topics::whereNull('assigned_group_id')->count();
        $assignedTopics = Topics::whereNotNull('assigned_group_id')->count();

        $topicsByLecturer = Topics::select('lecturer', DB::raw('COUNT(*) as total'))
            ->groupBy('lecturer')
            ->orderByDesc('total')
            ->get();

        $topicsByClass = ClassSection::withCount('topics')
            ->orderByDesc('topics_count')
            ->get();

        return view('statistics.topics', compact(
            'totalTopics', 'freeTopics', 'assignedTopics', 'topicsByLecturer', 'topicsByClass'
        ));
    }

    /** Thống kê nhóm: có/không đề tài, số thành viên trung bình. */
    public function groupStatistics()
    {
        $totalGroups = Groups::count();
        $groupsWithTopic = Groups::whereNotNull('topic_id')->count();
        $groupsWithoutTopic = Groups::whereNull('topic_id')->count();

        // AVG đúng chuẩn SQL: COUNT(*) theo từng nhóm rồi lấy trung bình.
        $avgMembersPerGroup = round(
            (float) DB::table('group_members')
                ->selectRaw('COUNT(*) as members')
                ->groupBy('group_id')
                ->get()
                ->avg('members'),
            1
        );

        $groupsByClass = ClassSection::withCount('groups')
            ->orderByDesc('groups_count')
            ->get();

        return view('statistics.groups', compact(
            'totalGroups', 'groupsWithTopic', 'groupsWithoutTopic', 'avgMembersPerGroup', 'groupsByClass'
        ));
    }

    /** Thống kê yêu cầu đăng ký đề tài theo trạng thái thực tế của enum. */
    public function requestStatistics()
    {
        $totalRequests = Topic_requests::count();

        $byStatus = Topic_requests::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pendingRequests = (int) ($byStatus['Pending'] ?? 0);
        $acceptedRequests = (int) ($byStatus['Accepted'] ?? 0);
        $rejectedRequests = (int) ($byStatus['Rejected'] ?? 0);

        return view('statistics.requests', compact(
            'totalRequests', 'byStatus', 'pendingRequests', 'acceptedRequests', 'rejectedRequests'
        ));
    }

    /** Thống kê người dùng: theo role; trưởng nhóm & "chưa có nhóm" tính qua groups/group_members. */
    public function userStatistics()
    {
        $totalUsers = User::count();
        $students = User::where('role', 'student')->count();
        $lecturers = User::where('role', 'lecturer')->count();
        $admins = User::where('role', 'admin')->count();

        // Trưởng nhóm = số leader_id distinct trong groups (role 'leader' đã bị bỏ khỏi users).
        $leaders = Groups::distinct('leader_id')->count('leader_id');

        // "Chưa có nhóm" = không nằm trong group_members và không làm leader của nhóm nào.
        $inGroupIds = Group_Members::pluck('user_id')
            ->merge(Groups::pluck('leader_id'))
            ->unique()
            ->values();

        $usersWithoutGroup = User::whereNotIn('user_id', $inGroupIds->all())->count();

        return view('statistics.users', compact(
            'totalUsers', 'students', 'lecturers', 'admins', 'leaders', 'usersWithoutGroup'
        ));
    }
}
