<?php
namespace App\Http\Controllers;

use App\Models\Topics;
use App\Models\Groups;
use App\Models\Group_Members;
use App\Models\Topic_requests;
use App\Models\Users;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    public function index()
    {
        return view('statistics.index');
    }

    public function topicStatistics()
    {
        $totalTopics = Topics::count();
        $registeredTopics = Topics::whereNotNull('topic_id')->distinct('topic_id')->count();
        $topicsByLecturer = Topics::groupBy('lecturer')
            ->selectRaw('lecturer, COUNT(*) as count')
            ->get();

        return view('statistics.topics', compact('totalTopics', 'registeredTopics', 'topicsByLecturer'));
    }

    public function groupStatistics()
    {
        $totalGroups = Groups::count();
        $groupsWithTopic = Groups::whereNotNull('topic_id')->count();
        $groupsWithoutTopic = Groups::whereNull('topic_id')->count();
        $avgMembersPerGroup = Group_Members::groupBy('group_id')
            ->selectRaw('COUNT(*) as count')
            ->avg('count');

        return view('statistics.groups', compact('totalGroups', 'groupsWithTopic', 'groupsWithoutTopic', 'avgMembersPerGroup'));
    }

    public function requestStatistics()
    {
        $totalRequests = Topic_requests::count();
        $pendingRequests = Topic_requests::where('status', 'Pending')->count();
        $approvedRequests = Topic_requests::where('status', 'Approved')->count();
        $rejectedRequests = Topic_requests::where('status', 'Rejected')->count();

        $requestsByStatus = Topic_requests::groupBy('status')
            ->selectRaw('status, COUNT(*) as count')
            ->get();

        return view('statistics.requests', compact('totalRequests', 'pendingRequests', 'approvedRequests', 'rejectedRequests', 'requestsByStatus'));
    }

    public function userStatistics()
    {
        $totalUsers = Users::count();
        $students = Users::where('role', 'student')->count();
        $leaders = Users::where('role', 'leader')->count();
        $lecturers = Users::where('role', 'lecturer')->count();
        $admins = Users::where('role', 'admin')->count();

        $usersWithoutGroup = Users::where('is_have_group', false)->count();

        return view('statistics.users', compact('totalUsers', 'students', 'leaders', 'lecturers', 'admins', 'usersWithoutGroup'));
    }
}