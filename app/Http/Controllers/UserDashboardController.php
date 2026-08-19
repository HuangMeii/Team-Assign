<?php

namespace App\Http\Controllers;

use App\Models\Groups;
use App\Models\Topics;
use App\Models\Invites;
use App\Models\Join_Requests;
use App\Models\Topic_requests;
use App\Models\ClassSection;
use App\Models\Group_Members;
use App\Models\Subject;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserDashboardController extends Controller
{
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
        $user = Auth::user();
        $group = Groups::findOrFail($groupId);

        // 1. Kiểm tra xem user có phải là thành viên không
        $isMember = \App\Models\Group_Members::where('group_id', $groupId)
            ->where('user_id', $user->user_id)
            ->exists();

        if (!$isMember) {
            return back()->with('error', 'Bạn không phải là thành viên của nhóm này!');
        }

        // 2. Không cho trưởng nhóm rời (phải chuyển quyền hoặc xóa nhóm)
        if ($group->leader_id == $user->user_id) {
            return back()->with('error', 'Trưởng nhóm không thể rời nhóm. Hãy xóa nhóm hoặc chuyển quyền trưởng nhóm!');
        }

        // 3. Thực hiện xóa user khỏi bảng group_members
        \App\Models\Group_Members::where('group_id', $groupId)
            ->where('user_id', $user->user_id)
            ->delete();

        return redirect()->route('user.my_groups')
            ->with('success', 'Bạn đã rời nhóm thành công!');
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
        if ($group->class_id !== $topic->class_id) {
            return back()->with('error', 'Nhóm và đề tài phải thuộc cùng một lớp mới được đăng ký!');
        }
        // Chỉ trưởng nhóm mới được gửi request
        if (!$this->isGroupLeader($group)) {
            return back()->with('error', 'Chỉ trưởng nhóm mới có thể đăng ký đề tài!');
        }


        $existing = Topic_requests::where('topic_id', $validated['topic_id'])
            ->where('group_id', $validated['group_id'])
            ->first();

        if ($existing && $existing->status === 'Accepted') {
            return back()->with('warning', 'Nhóm đã đăng ký đề tài này rồi!');
        }


        if ($existing && $existing->status === 'Pending') {
            return back()->with('warning', 'Nhóm đã gửi yêu cầu cho đề tài này và đang chờ duyệt.');
        }


        if ($existing && $existing->status === 'Rejected') {
            $existing->update([
                'status' => 'Pending',
                'created_by' => Auth::id(),
            ]);

            NotificationService::topicRequestCreated($existing);

            return back()->with('success', 'Đã gửi lại yêu cầu đăng ký đề tài!');
        }

        $topicRequest = Topic_requests::create([
            'topic_id' => $validated['topic_id'],
            'group_id' => $validated['group_id'],
            'created_by' => Auth::id(),
            'status' => 'Pending',
        ]);

        NotificationService::topicRequestCreated($topicRequest);

        return back()->with('success', 'Đã gửi yêu cầu đăng ký đề tài thành công!');
    }

    /**
     * Hủy đăng ký đề tài
     */
    public function cancelTopicRequest($requestId)
    {
        $topicRequest = Topic_requests::findOrFail($requestId);

        if ($topicRequest->status !== 'Pending') {
            return back()->with('error', 'Chỉ có thể hủy đăng ký khi chưa được duyệt!');
        }

        if ($topicRequest->created_by !== Auth::id()) {
            return back()->with('error', 'Bạn không có quyền hủy đăng ký này!');
        }

        // Cập nhật trạng thái thay vì xóa
        $topicRequest->status = 'Cancelled'; // hoặc 'Đã hủy'
        $topicRequest->save();

        return back()->with('success', 'Đã hủy đăng ký đề tài!');
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

        return view('user.my_groups', compact('groups', 'userClasses', 'joinedClassIds'));
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

        $user = Auth::user();

        // Kiểm tra user đã là leader của nhóm nào chưa
        $existingLeaderGroup = Groups::where('leader_id', $user->user_id)
            ->where('class_id', $validated['class_id'])
            ->first();

        if ($existingLeaderGroup) {
            return back()->with('error', 'Bạn đã là trưởng nhóm của nhóm "' . $existingLeaderGroup->group_name . '" trong lớp này!');
        }


        // Kiểm tra user đã tham gia nhóm nào chưa trong cùng lớp
        $existingMemberGroup = Groups::where('class_id', $validated['class_id'])
            ->whereHas('members', function ($query) use ($user) {
                $query->where('group_members.user_id', $user->user_id);
            })
            ->first();

        if ($existingMemberGroup) {
            return back()->with('error', 'Bạn đã là thành viên của nhóm "' . $existingMemberGroup->group_name . '" trong lớp này!');
        }

        // Tạo nhóm mới
        $group = Groups::create([
            'group_name' => $validated['group_name'],
            'leader_id' => $user->user_id,
            'class_id' => $validated['class_id'],
        ]);

        Group_Members::create([
            'group_id' => $group->group_id,
            'user_id' => $user->user_id,
            'role' => 'leader'
        ]);

        return redirect()->route('user.group_detail', $group->group_id)
            ->with('success', 'Tạo nhóm thành công! Bạn có thể mời thêm thành viên.');
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
        $memberCount = $members->count();
        $isLeader = $this->isGroupLeader($group);
        $isMember = $this->isGroupMember($group);

        return view('user.group_detail', compact(
            'group',
            'members',
            'memberCount',
            'isLeader',
            'isMember'
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
        if ($group->members->count() >= 4) {
            return back()->with('error', 'Nhóm đã đủ 5 thành viên, không thể mời thêm!');
        }
        $availableUsers = $this->getAvailableUsersForGroup($group);
        $pendingInvites = $this->getPendingInvites($groupId);

        return view('user.invite_member', compact(
            'group',
            'availableUsers',
            'pendingInvites'
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

        if (!$this->isGroupLeader($group)) {
            return back()->with('error', 'Chỉ trưởng nhóm mới có thể mời thành viên!');
        }
        if ($group->members()->count() >= 4) {
            return back()->with('error', 'Nhóm đã đủ 5 thành viên, không thể mời thêm!');
        }
        // Kiểm tra user đã là thành viên chưa
        if ($this->isUserInGroup($group, $validated['member_id'])) {
            return back()->with('error', 'Người này đã là thành viên của nhóm!');
        }

        // Kiểm tra lời mời đang pending
        $existingInvite = Invites::where([
            'group_id' => $validated['group_id'],
            'member_id' => $validated['member_id'],
            'status' => 'Pending'
        ])->first();

        if ($existingInvite) {
            return back()->with('warning', 'Đã gửi lời mời cho người này rồi!');
        }

        $invite = Invites::create([
            'group_id' => $validated['group_id'],
            'member_id' => $validated['member_id'],
            'invitedBy' => Auth::id(),
            'status' => 'Pending',
        ]);

        // Load relationships trước khi gửi notification
        $invite->load(['group', 'inviter']);

        // Gửi thông báo
        NotificationService::groupInviteCreated($invite);

        return back()->with('success', 'Đã gửi lời mời thành công!');
    }

    /**
     * Hủy lời mời
     */
    public function cancelInvite($inviteId)
    {
        $invite = Invites::findOrFail($inviteId);

        if (!$this->isGroupLeader($invite->group)) {
            return back()->with('error', 'Chỉ trưởng nhóm mới có thể hủy lời mời!');
        }

        if ($invite->status !== 'Pending') {
            return back()->with('error', 'Chỉ có thể hủy lời mời chưa được xử lý!');
        }

        $invite->delete();

        return back()->with('success', 'Đã hủy lời mời!');
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

        if ($invite->member_id !== Auth::id()) {
            return back()->with('error', 'Bạn không có quyền thực hiện hành động này!');
        }

        if ($invite->status !== 'Pending') {
            return back()->with('warning', 'Lời mời này đã được xử lý!');
        }

        DB::transaction(function () use ($invite) {
            // Thêm vào nhóm nếu chưa có
            if (!$this->isUserInGroup($invite->group, Auth::id())) {
                $invite->group->members()->attach(Auth::id());
            }

            $invite->update(['status' => 'Accepted']);
        });

        return back()->with('success', 'Đã chấp nhận lời mời tham gia nhóm!');
    }

    /**
     * Từ chối lời mời
     */
    public function rejectInvite($id)
    {
        $invite = Invites::findOrFail($id);

        if ($invite->member_id !== Auth::id()) {
            return back()->with('error', 'Bạn không có quyền thực hiện hành động này!');
        }

        if ($invite->status !== 'Pending') {
            return back()->with('warning', 'Lời mời này đã được xử lý!');
        }

        $invite->update(['status' => 'Rejected']);

        return back()->with('success', 'Đã từ chối lời mời!');
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

        // Kiểm tra đã là thành viên chưa
        if ($this->isUserInGroup($group, $user->user_id)) {
            return back()->with('error', 'Bạn đã là thành viên của nhóm này!');
        }

        // Kiểm tra yêu cầu đang pending
        $existingRequest = Join_Requests::where([
            'group_id' => $validated['group_id'],
            'member_id' => $user->user_id,
            'status' => 'Pending'
        ])->first();

        if ($existingRequest) {
            return back()->with('warning', 'Bạn đã gửi yêu cầu tham gia nhóm này rồi!');
        }

        $joinRequest = Join_Requests::create([
            'group_id' => $validated['group_id'],
            'member_id' => $user->user_id,
            'status' => 'Pending',
        ]);

        // Gửi thông báo cho trưởng nhóm
        NotificationService::joinRequestCreated($joinRequest);

        return back()->with('success', 'Đã gửi yêu cầu tham gia nhóm!');
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

        if ($request->member_id !== Auth::id()) {
            return back()->with('error', 'Bạn không có quyền thực hiện hành động này!');
        }

        if ($request->status !== 'Pending') {
            return back()->with('error', 'Chỉ có thể hủy yêu cầu chưa được xử lý!');
        }

        $request->delete();

        return back()->with('success', 'Đã hủy yêu cầu!');
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

        if (!$this->isGroupLeader($joinRequest->group)) {
            return back()->with('error', 'Chỉ trưởng nhóm mới có thể chấp nhận yêu cầu!');
        }

        if ($joinRequest->status !== 'Pending') {
            return back()->with('warning', 'Yêu cầu này đã được xử lý!');
        }

        DB::transaction(function () use ($joinRequest) {
            // Thêm vào nhóm nếu chưa có
            if (!$this->isUserInGroup($joinRequest->group, $joinRequest->member_id)) {
                $joinRequest->group->members()->attach($joinRequest->member_id);
            }

            $joinRequest->update(['status' => 'Approved']);

            // Gửi thông báo
            NotificationService::joinRequestApproved($joinRequest);
        });

        return back()->with('success', 'Đã chấp nhận yêu cầu tham gia!');
    }

    /**
     * Từ chối yêu cầu tham gia
     */
    public function rejectJoinRequest($requestId)
    {
        $joinRequest = Join_Requests::findOrFail($requestId);

        if (!$this->isGroupLeader($joinRequest->group)) {
            return back()->with('error', 'Chỉ trưởng nhóm mới có thể từ chối yêu cầu!');
        }

        if ($joinRequest->status !== 'Pending') {
            return back()->with('warning', 'Yêu cầu này đã được xử lý!');
        }

        $joinRequest->update(['status' => 'Rejected']);

        return back()->with('success', 'Đã từ chối yêu cầu tham gia!');
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

    /**
     * Kiểm tra user đã ở trong nhóm chưa
     */
    private function isUserInGroup($group, $userId)
    {
        return $group->leader_id == $userId ||
            $group->members()->where('group_members.user_id', $userId)->exists();
    }

    /**
     * Lấy danh sách user có thể mời vào nhóm
     */
    private function getAvailableUsersForGroup($group)
    {
        if (!$group->class_id) {
            return collect([]);
        }

        $usedUserIds = \App\Models\Group_Members::whereHas('group', function ($q) use ($group) {
            $q->where('class_id', $group->class_id);
        })->pluck('user_id');

        return \App\Models\User::where('role', 'student')
            ->whereHas('classes', function ($query) use ($group) {
                $query->where('class_sections.class_id', $group->class_id);
            })
            ->whereNotIn('user_id', $usedUserIds)
            ->orderBy('name')
            ->get();
    }

    /**
     * Lấy danh sách lời mời đang pending
     */
    private function getPendingInvites($groupId)
    {
        return Invites::where('group_id', $groupId)
            ->where('status', 'Pending')
            ->with('member')
            ->latest()
            ->get();
    }
}