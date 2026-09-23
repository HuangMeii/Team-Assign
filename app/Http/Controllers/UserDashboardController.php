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
        $myClassesCount = $userClasses->count();
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
            'myClassesCount',
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

        // Lấy danh sách môn học (cho filter dropdown trong view)
        $subjectIds = $userClasses->pluck('subject_id')->unique()->filter();
        $subjects = Subject::whereIn('subject_id', $subjectIds)->get();

        // Bugfix A4 [R57]: sinh viên chỉ thấy đề tài của lớp học phần mình tham gia.
        // Lọc theo class_id (không subject_id): cùng 1 môn có nhiều lớp → trước đây hiến đề tài các lớp khác cùng môn.
        $classIds = $userClasses->pluck('class_id')->unique()->filter();

        // Build query với filters
        $query = Topics::with(['subject', 'assignedGroup'])
            ->whereIn('class_id', $classIds);

        $query = $this->applyTopicFilters($query, $request);

        $topics = $query->orderBy('created_at', 'desc')->paginate(15);
        $topics->appends($request->query());

        // CẢNH BÁO (không chặn): nhóm của sinh viên đã có đề tài được duyệt -> gợi ý chỉ để tham khảo
        $hasApprovedTopic = Groups::where(function ($q) use ($user) {
                $q->where('leader_id', $user->user_id)
                    ->orWhereHas('members', fn ($members) => $members->where('group_members.user_id', $user->user_id));
            })
            ->whereNotNull('topic_id')
            ->exists();

        $recommenderWarning = $hasApprovedTopic
            ? 'Nhóm của bạn đã có đề tài được duyệt — kết quả gợi ý dưới đây chỉ mang tính tham khảo.'
            : null;

        return view('user.topics', compact('topics', 'userClasses', 'subjects', 'recommenderWarning'));
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

        // Dọn dẹp an toàn (idempotent): yêu cầu Pending không còn hiệu lực được chuyển Expired
        // ngay khi mở trang -> sinh viên không còn thấy "Đang chờ duyệt" ở nhóm mình không
        // thể vào nữa (đã có nhóm trong lớp / nhóm đã đầy).
        $this->invitations->expireStalePendingRequestsFor($user);

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

        // Nhóm CÒN CHỖ theo từng lớp -> nút "Tìm nhóm" của mỗi lớp chỉ hiện nhóm thuộc lớp đó.
        $availableGroupsByClass = $this->availableGroupsForClasses($userClasses->pluck('class_id')->all());

        // Bản đồ max_members theo từng nhóm (cho hiển thị "đã đầy")
        $maxMembersByGroup = $groups->mapWithKeys(function ($group) {
            return [$group->group_id => $this->groups->maxMembers($group)];
        });

        // Bổ sung max_members cho các nhóm hiện trong modal "Tìm nhóm" (nhóm của lớp khác chưa có trong $groups)
        foreach ($availableGroupsByClass as $classGroups) {
            foreach ($classGroups as $availableGroup) {
                $maxMembersByGroup[$availableGroup->group_id] = $this->groups->maxMembers($availableGroup);
            }
        }

        return view('user.my_groups', compact(
            'groups',
            'userClasses',
            'joinedClassIds',
            'maxMembersByGroup',
            'availableGroupsByClass'
        ));
    }

    /**
     * Nhóm còn chỗ trống, GOM THEO class_id.
     *
     * Dùng cho nút "Tìm nhóm" của từng lớp ở trang "Nhóm của tôi" (mỗi lớp 1 danh sách riêng).
     * Cùng quy tắc lọc với availableGroups(): tổng thành viên (members + trưởng nhóm) < max_members.
     *
     * @param  int[]  $classIds
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, Groups>>
     */
    private function availableGroupsForClasses(array $classIds)
    {
        if (empty($classIds)) {
            return collect();
        }

        $groups = Groups::with(['leader', 'class.subject', 'members'])
            ->whereIn('class_id', $classIds)
            ->withCount('members')
            ->get();

        return $groups
            ->filter(fn ($group) => $this->groups->memberCount($group) < $this->groups->maxMembers($group))
            ->groupBy('class_id');
    }
    /**
     * Form tạo nhóm mới
     */
    public function createGroupForm(Request $request)
    {
        $user = Auth::user();

        // Sinh viên chỉ được tạo nhóm trong lớp mình ĐANG tham gia và CHƯA có nhóm.
        // Lớp đã có nhóm bị loại khỏi danh sách chọn (nghiệp vụ: 1 sinh viên chỉ 1 nhóm / 1 lớp học phần).
        $userClasses = $user->classes
            ->reject(fn ($class) => $this->groups->hasGroupInClass($user, (int) $class->class_id))
            ->values();

        // Lớp được chọn sẵn từ URL (khi bấm "Tạo nhóm mới" ở thẻ lớp trong trang Nhóm của tôi)
        // -> form hiển thị lớp dạng TEXT (không bắt chọn lại bằng combo box).
        $selectedClassId = (int) $request->input('class_id') ?: null;
        $selectedClass = $selectedClassId
            ? $userClasses->firstWhere('class_id', $selectedClassId)
            : null;

        // URL truyền class_id không hợp lệ (không thuộc lớp đang học / đã có nhóm) -> bỏ chọn, dùng combo box
        if ($selectedClassId && ! $selectedClass) {
            $selectedClassId = null;
        }

        // Đã có nhóm ở TẤT CẢ lớp học phần đang tham gia -> không thể tạo nhóm mới
        $hasGroupInEveryClass = $user->classes->isNotEmpty() && $userClasses->isEmpty();

        return view('user.create_group', compact(
            'userClasses',
            'selectedClass',
            'selectedClassId',
            'hasGroupInEveryClass'
        ));
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

            // Sinh viên đã có nhóm riêng -> các yêu cầu xin vào nhóm khác trong cùng lớp hết hiệu lực
            $this->invitations->expireMemberPendingRequestsInClass(
                (int) Auth::id(),
                (int) $group->class_id,
                (int) $group->group_id
            );

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

        // Dọn dẹp an toàn (idempotent): yêu cầu Pending KHÔNG còn hiệu lực
        // (nhóm đã đầy / sinh viên đã có nhóm trong lớp) được chuyển Expired ngay khi mở
        // trang -> cả dữ liệu cũ (trước khi có tính năng) cũng tự sạch.
        $this->invitations->expireStalePendingRequestsFor($user);

        // ---------- Yêu cầu NHẬN ĐƯỢC (các nhóm user làm trưởng nhóm) ----------
        // Đây chính là số mà badge "Yêu cầu" trên sidebar đếm
        // (Auth::user()->pending_join_requests_count) -> badge và nội dung trang luôn khớp.
        $incomingRequests = Join_Requests::whereIn('group_id', function ($q) use ($user) {
                $q->select('group_id')->from('groups')->where('leader_id', $user->user_id);
            })
            ->where('status', 'Pending')
            ->with(['member.classes', 'group.class', 'group.leader'])
            ->latest()
            ->get();

        // Cờ quyết định: yêu cầu hết hiệu lực -> view ẩn nút Chấp nhận / Từ chối.
        $incomingDecisions = [];
        foreach ($incomingRequests as $incomingRequest) {
            $incomingDecisions[$incomingRequest->id] = $this->joinRequestDecision($incomingRequest);
        }
        $incomingCount = $incomingRequests->count();

        // ---------- Yêu cầu ĐÃ GỬI ----------
        // Mặc định CHỈ hiện yêu cầu còn hiệu lực (Pending). Yêu cầu đã hết hiệu lực
        // (Expired) chỉ hiện ở tab "Hết hiệu lực"; tab "Tất cả" (?status=all) xem toàn bộ lịch sử.
        $query = Join_Requests::where('member_id', $user->user_id)
            ->with([
                'group.class.subject',
                'group.topic',
                'group.leader',
                'group.members'
            ]);

        $statusFilter = $request->input('status');

        if ($statusFilter === 'all') {
            // Không lọc: xem toàn bộ lịch sử (Pending + Accepted + Rejected + Expired)
        } elseif (in_array($statusFilter, ['Pending', 'Accepted', 'Rejected', 'Expired'], true)) {
            $query->where('status', $statusFilter);
        } else {
            // Không truyền ?status= -> mặc định chỉ hiện yêu cầu ĐANG CHỜ
            $query->where('status', 'Pending');
        }

        $requests = $query->latest()->paginate(10)->withQueryString();

        $sentPendingCount = Join_Requests::where('member_id', $user->user_id)
            ->where('status', 'Pending')
            ->count();

        $sentExpiredCount = Join_Requests::where('member_id', $user->user_id)
            ->where('status', 'Expired')
            ->count();

        return view('user.join_requests', compact(
            'requests',
            'incomingRequests',
            'incomingDecisions',
            'incomingCount',
            'sentPendingCount',
            'sentExpiredCount'
        ));
    }

    /**
     * Yêu cầu tham gia nhóm còn xử lý được hay không (ủy quyền cho InvitationService
     * để trang Yêu cầu và trang Thông báo dùng chung một logic).
     *
     * @return array{can: bool, reason: ?string, status: string}
     */
    private function joinRequestDecision(Join_Requests $joinRequest): array
    {
        return $this->invitations->canHandleJoinRequest($joinRequest);
    }

    /**
     * Hủy yêu cầu tham gia
     */
    /**
     * AJAX: bấm vào mục "Lời mời" trên sidebar -> đánh dấu đã xem.
     * Badge "Lời mời" về 0 và chỉ hiện lại khi có lời mời MỚI.
     */
    public function markInvitesSeen()
    {
        Auth::user()->markInvitesSeen();

        return response()->json(['success' => true, 'count' => 0]);
    }

    /**
     * AJAX: bấm vào mục "Yêu cầu" trên sidebar -> đánh dấu đã xem.
     * Badge "Yêu cầu" về 0 và chỉ hiện lại khi có yêu cầu tham gia MỚI.
     */
    public function markJoinRequestsSeen()
    {
        Auth::user()->markJoinRequestsSeen();

        return response()->json(['success' => true, 'count' => 0]);
    }

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
        $user = Auth::user();

        // Sinh viên chỉ được xem các lớp mình đã tham gia.
        $query = ClassSection::with(['subject', 'lecturers'])
            ->withCount('groups')
            ->whereHas('students', function ($q) use ($user) {
                $q->where('users.user_id', $user->user_id);
            });

        // Lọc theo môn học (trong các lớp đã tham gia)
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
        $user = Auth::user();

        $class = ClassSection::with([
            'subject.topics',
            'lecturers',
            'groups.leader',
            'groups.topic',
            'groups.members'
        ])
            ->withCount('groups')
            ->findOrFail($id);

        // Sinh viên chỉ được xem chi tiết lớp mình đã tham gia.
        $isMember = $class->students()->where('users.user_id', $user->user_id)->exists();
        if (!$isMember) {
            return redirect()->route('user.classes')->with('error', 'Bạn chưa tham gia lớp học này!');
        }

        return view('user.class_detail', compact('class'));
    }

    /**
     * Danh sách môn học
     */
    public function subjects(Request $request)
    {
        $query = Subject::with(['classes.lecturers', 'topics'])
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
            'classes.groups.leader',
            'classes.lecturers',
            'topics.assignedGroup'
        ])
            ->withCount(['classes', 'topics'])
            ->findOrFail($id);

        return view('user.subject_detail', compact('subject'));
    }

    /**
     * Danh sách nhóm còn thiếu thành viên (cho sinh viên chưa có nhóm)
     */
    /**
     * Hiển thị danh sách nhóm còn thiếu thành viên để sinh viên join
     * Bugfix D2 [R70]: Hỗ trợ filter theo class_id khi gọi từ trang lớp cụ thể.
     */
    public function availableGroups(Request $request)
    {
        $user = Auth::user();

        // Lấy các lớp mà user tham gia
        $userClassIds = $user->classes->pluck('class_id');

        // Bugfix D2 [R70]: Nếu có class_id trong query string, chỉ hiện nhóm của lớp đó
        $filterClassId = $request->input('class_id');
        if ($filterClassId && $userClassIds->contains($filterClassId)) {
            $userClassIds = [$filterClassId];
        }

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

        return view('user.available_groups', compact('availableGroups', 'requestedGroupIds', 'maxMembersByGroup', 'filterClassId'));
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

    // Panel gợi ý: môn học của lớp nhóm (chọn sẵn) + cảnh báo nếu nhóm đã có đề tài được duyệt
    $recommenderSubjects = collect([$group->class?->subject])->filter();
    $recommenderWarning = $group->topic_id
        ? 'Nhóm của bạn đã có đề tài được duyệt — kết quả gợi ý dưới đây chỉ mang tính tham khảo.'
        : null;

    return view('user.group_topics', compact(
        'group',
        'topics',
        'groupsRegistered',
        'recommenderSubjects',
        'recommenderWarning'
    ));
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


