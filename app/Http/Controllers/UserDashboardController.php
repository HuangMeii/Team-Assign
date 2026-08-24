<?php

namespace App\Http\Controllers;

use App\Models\Groups;
use App\Models\Topics;
use App\Models\Invites;
use App\Models\Join_Requests;
use App\Models\Topic_requests;
use App\Models\ClassSection;
use App\Models\Subject;
use App\Services\GroupService;
use App\Services\InvitationService;
use App\Services\TopicRegistrationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserDashboardController extends Controller
{
    public function __construct(
        private readonly GroupService $groups,
        private readonly InvitationService $invitations,
        private readonly TopicRegistrationService $topicRegistration,
    ) {}

    /**
     * Dashboard chính của user
     */
    public function index()
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
            ->whereNull('assigned_group_id')
            ->inRandomOrder()
            ->limit(6)
            ->get();

        // Bản đồ max_members cho từng nhóm (hiển thị trạng thái đủ/thiếu)
        $maxMembersByGroup = $myGroups->mapWithKeys(fn ($g) => [$g->group_id => $this->groups->maxMembers($g)]);

        return view('user.dashboard', compact(
            'myGroups',
            'pendingInvites',
            'pendingRequests',
            'myTopics',
            'userClasses',
            'userSubjects',
            'suggestedTopics',
            'maxMembersByGroup'
        ));
    }

    /**
     * Danh sách đề tài với filter
     */
    public function topics(Request $request)
    {
        $user = Auth::user();
        $userClasses = $user->classes;

        if ($userClasses->isEmpty()) {
            return view('user.topics', [
                'topics' => collect([]),
                'userClasses' => $userClasses,
                'subjects' => collect([])
            ]);
        }

        // Lấy danh sách môn học
        $subjectIds = $userClasses->pluck('subject_id')->unique()->filter();
        $subjects = Subject::whereIn('subject_id', $subjectIds)->get();

        // Build query với filters
        $query = Topics::with(['subject', 'assignedGroup'])
            ->whereIn('subject_id', $subjectIds);

        $query = $this->applyTopicFilters($query, $request);

        $topics = $query->orderBy('created_at', 'desc')->paginate(15);
        $topics->appends($request->query());

        return view('user.topics', compact('topics', 'userClasses', 'subjects'));
    }

    /**
     * Chi tiết đề tài
     */
    public function topicDetail($id)
    {
        $topic = Topics::with([
            'subject',
            'class',
            'assignedGroup',
            'topic_requests.group'
        ])->findOrFail($id);

        $user = Auth::user();

        // Lấy TẤT CẢ nhóm của user (để kiểm tra)
        $myGroups = $this->getUserGroups($user);

        // Kiểm tra nhóm nào đã đăng ký đề tài này
        $groupsRegistered = $topic->topic_requests()
            ->whereIn('status', ['Pending', 'Accepted'])
            ->pluck('group_id')
            ->toArray();

        return view('user.topic_detail', compact(
            'topic',
            'myGroups',
            'groupsRegistered'
        ));
    }

    public function leaveGroup($groupId)
    {
        // Theo yêu cầu đề tài: "Sau khi tham gia nhóm, không được tự ý rời nhóm."
        return back()->with('error', 'Bạn không được tự ý rời nhóm sau khi đã tham gia. Vui lòng liên hệ giảng viên để được hỗ trợ!');
    }


    /**
     * Đăng ký đề tài cho nhóm
     */
    public function registerTopic(Request $request)
    {
        $validated = $request->validate([
            'topic_id' => 'required|exists:topics,topic_id',
            'group_id' => 'required|exists:groups,group_id',
        ]);

        $group = Groups::findOrFail($validated['group_id']);
        $topic = Topics::findOrFail($validated['topic_id']);

        $result = $this->topicRegistration->register($group, $topic, Auth::user());

        return back()->with($result->status(), $result->message());
    }

    /**
     * Hủy đăng ký đề tài
     */
    public function cancelTopicRequest($requestId)
    {
        $topicRequest = Topic_requests::findOrFail($requestId);

        $result = $this->topicRegistration->cancel($topicRequest, Auth::user());

        return back()->with($result->status(), $result->message());
    }

    /**
     * Danh sách đề tài của tôi
     */
    public function myTopics()
    {
        $user = Auth::user();

        $groups = Groups::where('leader_id', $user->user_id)
            ->orWhereHas('members', function ($query) use ($user) {
                $query->where('group_members.user_id', $user->user_id);
            })
            ->with(['topic.subject', 'class', 'leader'])
            ->get();

        $topics = $groups->filter(fn($group) => $group->topic)
            ->map(function ($group) {
                $topic = $group->topic;
                $topic->group = $group;
                return $topic;
            });

        return view('user.my_topics', compact('topics', 'groups'));
    }

    /**
     * Danh sách nhóm của tôi
     */
    public function myGroups()
    {
        $user = Auth::user();

        // Lấy các nhóm mà user đã tham gia
        $groups = Groups::where('leader_id', $user->user_id)
            ->orWhereHas('members', function ($query) use ($user) {
                $query->where('group_members.user_id', $user->user_id);
            })
            ->with(['leader', 'topic', 'members', 'class.subject'])
            ->withCount('members')
            ->paginate(9);

        // Lấy danh sách các lớp mà user đã tham gia nhóm
        $joinedClassIds = Groups::query()
            ->select('class_id')
            ->where('leader_id', $user->user_id)
            ->orWhereHas('members', function ($q) use ($user) {
                $q->where('group_members.user_id', $user->user_id);
            })
            ->pluck('class_id')
            ->unique()
            ->toArray();

        // Lấy TẤT CẢ các lớp mà user tham gia (dùng quan hệ user->classes)
        $userClasses = $user->classes;

        // Bản đồ max_members theo từng nhóm (cho hiển thị "đã đầy")
        $maxMembersByGroup = $groups->mapWithKeys(function ($group) {
            return [$group->group_id => $this->groups->maxMembers($group)];
        });

        return view('user.my_groups', compact('groups', 'userClasses', 'joinedClassIds', 'maxMembersByGroup'));
    }
    /**
     * Form tạo nhóm mới
     */
    public function createGroupForm()
    {
        $user = Auth::user();
        $userClasses = $user->classes;

        return view('user.create_group', compact('userClasses'));
    }

    /**
     * Lưu nhóm mới
     */
    public function storeGroup(Request $request)
    {
        $validated = $request->validate([
            'group_name' => 'required|string|max:255',
            'class_id' => 'required|exists:class_sections,class_id',
        ]);

        $result = $this->groups->createGroupByStudent(Auth::user(), $validated['group_name'], $validated['class_id']);

        if ($result->succeeded()) {
            $group = $result->data();

            return redirect()->route('user.group_detail', $group->group_id)
                ->with('success', $result->message());
        }

        return back()->with($result->status(), $result->message());
    }


    /**
     * Chi tiết nhóm
     */
    public function groupDetail($id)
    {
        $group = Groups::with([
            'class.subject',
            'topic',
            'leader',
            'members',
            'topicRequests.topic'
        ])->findOrFail($id);

        $members = $group->members;
        $memberCount = $members->count() + 1; // +1 cho trưởng nhóm
        $isLeader = $this->isGroupLeader($group);
        $isMember = $this->isGroupMember($group);
        $maxMembers = $this->groups->maxMembers($group);

        return view('user.group_detail', compact(
            'group',
            'members',
            'memberCount',
            'isLeader',
            'isMember',
            'maxMembers'
        ));
    }

    /**
     * Form mời thành viên
     */
    public function inviteMemberForm($groupId)
    {
        $group = Groups::with(['members', 'leader', 'class'])->findOrFail($groupId);

        if (!$this->isGroupLeader($group)) {
            return back()->with('error', 'Chỉ trưởng nhóm mới có thể mời thành viên!');
        }

        // Lấy số lượng thành viên tối đa từ đề tài của lớp (nếu có), mặc định 5
        $maxMembers = $this->groups->maxMembers($group);

        if ($this->groups->memberCount($group) >= $maxMembers) {
            return back()->with('error', "Nhóm đã đủ {$maxMembers} thành viên, không thể mời thêm!");
        }

        $availableUsers = $this->groups->availableUsersForGroup($group);
        $pendingInvites = $this->groups->pendingInvites($group);

        return view('user.invite_member', compact(
            'group',
            'availableUsers',
            'pendingInvites',
            'maxMembers'
        ));
    }


    /**
     * Gửi lời mời thành viên
     */
    public function sendInvite(Request $request)
    {
        $validated = $request->validate([
            'group_id' => 'required|exists:groups,group_id',
            'member_id' => 'required|exists:users,user_id',
        ]);

        $group = Groups::findOrFail($validated['group_id']);

        $result = $this->invitations->sendInvite($group, Auth::user(), $validated['member_id']);

        return back()->with($result->status(), $result->message());
    }

    /**
     * Hủy lời mời
     */
    public function cancelInvite($inviteId)
    {
        $invite = Invites::findOrFail($inviteId);

        $result = $this->invitations->cancelInvite($invite, Auth::user());

        return back()->with($result->status(), $result->message());
    }

    /**
     * Danh sách lời mời nhận được
     */
    public function invites(Request $request)
    {
        $user = Auth::user();

        $query = Invites::where('member_id', $user->user_id)
            ->with([
                'group.class.subject',
                'group.topic',
                'group.leader',
                'group.members',
                'invitedBy'
            ]);

        // Filter theo status nếu có
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invites = $query->latest()->paginate(10);

        return view('user.invites', compact('invites'));
    }

    /**
     * Chấp nhận lời mời
     */
    public function acceptInvite($id)
    {
        $invite = Invites::findOrFail($id);

        $result = $this->invitations->acceptInvite($invite, Auth::user());

        return back()->with($result->status(), $result->message());
    }


    /**
     * Từ chối lời mời
     */
    public function rejectInvite($id)
    {
        $invite = Invites::findOrFail($id);

        $result = $this->invitations->rejectInvite($invite, Auth::user());

        return back()->with($result->status(), $result->message());
    }

    /**
     * Gửi yêu cầu tham gia nhóm
     */
    public function sendJoinRequest(Request $request)
    {
        $validated = $request->validate([
            'group_id' => 'required|exists:groups,group_id',
        ]);

        $user = Auth::user();
        $group = Groups::findOrFail($validated['group_id']);

        $result = $this->invitations->sendJoinRequest($group, $user);

        return back()->with($result->status(), $result->message());
    }

    /**
     * Danh sách yêu cầu tham gia của tôi
     */
    public function joinRequests(Request $request)
    {
        $user = Auth::user();

        $query = Join_Requests::where('member_id', $user->user_id)
            ->with([
                'group.class.subject',
                'group.topic',
                'group.leader',
                'group.members'
            ]);

        // Filter theo status nếu có
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->latest()->paginate(10);

        return view('user.join_requests', compact('requests'));
    }

    /**
     * Hủy yêu cầu tham gia
     */
    public function cancelRequest($id)
    {
        $request = Join_Requests::findOrFail($id);

        $result = $this->invitations->cancelJoinRequest($request, Auth::user());

        return back()->with($result->status(), $result->message());
    }

    /**
     * Danh sách yêu cầu tham gia nhóm (cho leader)
     */
    public function groupJoinRequests($groupId)
    {
        $group = Groups::with(['members', 'leader'])->findOrFail($groupId);

        if (!$this->isGroupLeader($group)) {
            return back()->with('error', 'Chỉ trưởng nhóm mới có thể xem yêu cầu tham gia!');
        }

        $requests = Join_Requests::where('group_id', $groupId)
            ->where('status', 'Pending')
            ->with('member')
            ->latest()
            ->paginate(10);

        return view('user.group_join_requests', compact('group', 'requests'));
    }

    /**
     * Chấp nhận yêu cầu tham gia
     */
    public function approveJoinRequest($requestId)
    {
        $joinRequest = Join_Requests::findOrFail($requestId);

        $result = $this->invitations->approveJoinRequest($joinRequest, Auth::user());

        return back()->with($result->status(), $result->message());
    }


    /**
     * Từ chối yêu cầu tham gia
     */
    public function rejectJoinRequest($requestId)
    {
        $joinRequest = Join_Requests::findOrFail($requestId);

        $result = $this->invitations->rejectJoinRequest($joinRequest, Auth::user());

        return back()->with($result->status(), $result->message());
    }

    /**
     * Danh sách lớp học
     */
    public function classes(Request $request)
    {
        $query = ClassSection::with(['subject.lecturer', 'groups'])
            ->withCount('groups');

        // Lọc theo môn học
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        // Tìm kiếm theo tên lớp
        if ($request->filled('search')) {
            $query->where('class_name', 'like', '%' . $request->search . '%');
        }

        // Phân trang
        $classes = $query->paginate(12);
        $classes->appends($request->query());

        $user = Auth::user();

        // Lấy danh sách class_id mà user đã tham gia
        $userClasses = DB::table('user_classes')
            ->where('user_id', $user->user_id)
            ->pluck('class_id')
            ->toArray();

        $subjects = Subject::all();

        return view('user.classes', compact('classes', 'userClasses', 'subjects'));
    }

    /**
     * Chi tiết lớp học
     */
    public function classDetail($id)
    {
        $class = ClassSection::with([
            'subject.lecturer',
            'subject.topics',
            'groups.leader',
            'groups.topic',
            'groups.members'
        ])
            ->withCount('groups')
            ->findOrFail($id);

        return view('user.class_detail', compact('class'));
    }

    /**
     * Danh sách môn học
     */
    public function subjects(Request $request)
    {
        $query = Subject::with(['lecturer', 'classes', 'topics'])
            ->withCount(['classes', 'topics']);

        // Search theo tên môn hoặc mã môn
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject_name', 'like', "%{$search}%")
                    ->orWhere('subject_code', 'like', "%{$search}%");
            });
        }

        $subjects = $query->paginate(12);
        $subjects->appends($request->query());

        return view('user.subjects', compact('subjects'));
    }

    /**
     * Chi tiết môn học
     */
    public function subjectDetail($id)
    {
        $subject = Subject::with([
            'lecturer',
            'classes.groups.leader',
            'topics.assignedGroup'
        ])
            ->withCount(['classes', 'topics'])
            ->findOrFail($id);

        return view('user.subject_detail', compact('subject'));
    }

    /**
     * Danh sách nhóm còn thiếu thành viên (cho sinh viên chưa có nhóm)
     */
    public function availableGroups(Request $request)
    {
        $user = Auth::user();

        // Lấy các lớp mà user tham gia
        $userClassIds = $user->classes->pluck('class_id');

        // Lấy danh sách nhóm trong các lớp của user
        $groups = Groups::with(['leader', 'class.subject', 'members'])
            ->whereIn('class_id', $userClassIds)
            ->withCount('members')
            ->get();

        // Lọc nhóm còn chỗ trống: tổng thành viên (members + 1 leader) < max_members
        $availableGroups = $groups->filter(function ($group) {
            return $this->groups->memberCount($group) < $this->groups->maxMembers($group);
        });

        // Bản đồ max_members theo từng nhóm (cho hiển thị)
        $maxMembersByGroup = $availableGroups->mapWithKeys(function ($group) {
            return [$group->group_id => $this->groups->maxMembers($group)];
        });

        // Lấy danh sách nhóm mà user đã gửi yêu cầu
        $requestedGroupIds = Join_Requests::where('member_id', $user->user_id)
            ->where('status', 'Pending')
            ->pluck('group_id')
            ->toArray();

        return view('user.available_groups', compact('availableGroups', 'requestedGroupIds', 'maxMembersByGroup'));
    }

    // ==================== HELPER METHODS ====================

    /**
     * Lấy danh sách nhóm của user
     */
    private function getUserGroups($user)

    {
        return Groups::where('leader_id', $user->user_id)
            ->orWhereHas('members', function ($query) use ($user) {
                $query->where('group_members.user_id', $user->user_id);
            })
            ->with(['leader', 'topic.subject', 'class.subject', 'members'])
            ->get();
    }

    /**
     * Đếm số lời mời chưa xử lý
     */
    private function countPendingInvites($user)
    {
        return Invites::where('member_id', $user->user_id)
            ->where('status', 'Pending')
            ->count();
    }

    /**
     * Đếm số yêu cầu tham gia chưa xử lý
     */
    private function countPendingJoinRequests($user)
    {
        return Join_Requests::where('member_id', $user->user_id)
            ->where('status', 'Pending')
            ->count();
    }

    /**
     * Lấy đề tài của các nhóm
     */
    private function getGroupTopics($groups)
    {
        $topicIds = $groups->pluck('topic_id')->filter();

        return Topics::whereIn('topic_id', $topicIds)
            ->with('subject')
            ->get();
    }
    public function groupTopics(Request $request, $groupId)
{
    $group = Groups::with(['class.subject', 'leader', 'topic'])->findOrFail($groupId);

    // Kiểm tra user có phải thành viên nhóm không
    if (!$this->isGroupLeader($group) && !$this->isGroupMember($group)) {
        return redirect()->route('user.my_groups')
            ->with('error', 'Bạn không phải thành viên của nhóm này!');
    }

    // Nếu nhóm không thuộc lớp nào thì không thể tìm đề tài
    if (!$group->class_id) {
        return back()->with('error', 'Nhóm chưa thuộc lớp học nào!');
    }

    // Lấy đề tài CHỈ TRONG LỚP CỦA NHÓM
    $query = Topics::with(['subject', 'assignedGroup', 'topic_requests'])
        ->where('class_id', $group->class_id);

    // Apply filters
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('lecturer', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // Filter theo trạng thái
    if ($request->filled('status')) {
        if ($request->status === 'available') {
            $query->whereNull('assigned_group_id');
        } elseif ($request->status === 'assigned') {
            $query->whereNotNull('assigned_group_id');
        }
    }

    $topics = $query->orderBy('created_at', 'desc')->paginate(10);

    // Lấy danh sách topic_id mà nhóm đã gửi request (Pending hoặc Accepted)
    $groupsRegistered = Topic_requests::where('group_id', $groupId)
        ->whereIn('status', ['Pending', 'Accepted'])
        ->pluck('topic_id')
        ->toArray();

    return view('user.group_topics', compact('group', 'topics', 'groupsRegistered'));
}
    /**
     * Lấy môn học của user
     */
    private function getUserSubjects($userClasses)
    {
        $subjectIds = $userClasses->pluck('subject_id')->unique()->filter();

        return Subject::whereIn('subject_id', $subjectIds)->get();
    }

    /**
     * Apply filters cho danh sách đề tài
     */
    private function applyTopicFilters($query, $request)
    {
        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('lecturer', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter theo lớp học
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        // Filter theo môn học
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        // Filter theo trạng thái
        if ($request->filled('status')) {
            if ($request->status === 'available') {
                $query->whereNull('assigned_group_id');
            } elseif ($request->status === 'assigned') {
                $query->whereNotNull('assigned_group_id');
            }
        }

        return $query;
    }

    /**
     * Kiểm tra user có phải leader của nhóm không
     */
    private function isGroupLeader($group)
    {
        return $group->leader_id === Auth::id();
    }

    /**
     * Kiểm tra user có phải thành viên của nhóm không
     */
    private function isGroupMember($group)
    {
        return $group->members()->where('group_members.user_id', Auth::id())->exists();
    }
}


