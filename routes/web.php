<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\GroupController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ClassSectionController;
use App\Http\Controllers\ClassJoinController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupsChatController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\DirectChatController;
use App\Http\Controllers\TopicRecommendationController;
use App\Http\Controllers\ClassStreamController;
use App\Http\Controllers\BlockUserController;



Route::get('/', function () {
    if (Illuminate\Support\Facades\Auth::check()) {

        $user = Illuminate\Support\Facades\Auth::user();
        if ($user->role === 'student') {
            return redirect()->route('user.dashboard');
        } elseif ($user->role === 'lecturer') {
            return redirect()->route('dashboard');
        } elseif ($user->role === 'admin') {
            return redirect()->route('admin.users.index');
        }
        return redirect('/dashboard');  // Default

    }
    // Chưa login → Về login
    return redirect('/login');
})->name('home');

Route::resource('topics', TopicController::class);
Route::get('/groups/{groupId}/chat', [GroupsChatController::class, 'showChat'])
    ->name('groups.chat.show');
// Lời mời
Route::get('invites', [InviteController::class, 'index'])->name('invites.index');
Route::get('invites/{id}/approve', [InviteController::class, 'approve'])->name('invites.approve');
Route::get('invites/{id}/reject', [InviteController::class, 'reject'])->name('invites.reject');






Route::prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/admin', [DashboardController::class, 'adminDashboard'])->name('dashboard.admin');
    Route::get('/lecturer', [DashboardController::class, 'lecturerDashboard'])->name('dashboard.lecturer');
    Route::get('/student', [DashboardController::class, 'studentDashboard'])->name('dashboard.student');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/class/{classId}', [DashboardController::class, 'classDetail'])->name('dashboard.class.detail');
});

// Giảng viên: quản lý lớp học phần (một môn học có thể có nhiều lớp)
Route::middleware(['auth', 'lecturer'])->prefix('lecturer')->name('lecturer.')->group(function () {
    // Danh sách lớp mình phụ trách
    Route::get('/classes', [ClassSectionController::class, 'lecturerClassesIndex'])
        ->name('classes.index');
    // Tạo lớp học phần
    Route::get('/classes/create', [ClassSectionController::class, 'lecturerCreate'])
        ->name('classes.create');
    Route::post('/classes', [ClassSectionController::class, 'lecturerStore'])
        ->name('classes.store');
    // Chi tiết & quản lý sinh viên trong lớp (đặt sau /classes/create để không bị wildcard che)
    Route::get('/classes/{id}', [ClassSectionController::class, 'lecturerClassesShow'])
        ->name('classes.show');
    Route::post('/classes/{id}/students', [ClassSectionController::class, 'lecturerClassesAddStudents'])
        ->name('classes.students.add');
    Route::post('/classes/{id}/students/{studentId}/remove', [ClassSectionController::class, 'lecturerClassesRemoveStudent'])
        ->name('classes.students.remove');
    Route::patch('/classes/{id}/toggle-active', [ClassSectionController::class, 'lecturerClassesToggleActive'])
        ->name('classes.toggle-active');
});

Route::get('/requests', function () {
    return view('requests'); // hoặc view nào m muốn
})->name('requests');




//  Auth routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Quên mật khẩu: gửi email chứa liên kết, người dùng click vào để xác thực và đặt mật khẩu mới
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');
});

// Link reset có token phải mở được cả khi người dùng vẫn đang đăng nhập.
// Nếu đặt trong middleware guest, Laravel sẽ chuyển người dùng thẳng về dashboard.
Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
    ->name('password.reset');
Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->name('password.store');

// Xác thực email mới khi đổi email: người dùng click liên kết TRONG EMAIL
// (thường khi chưa đăng nhập ở trình duyệt đó) — liên kết signed tự là bằng chứng.
Route::get('/email/verify/{id}/{hash}', [UserController::class, 'verifyEmailChange'])
    ->middleware('signed')->name('users.email.verify');


Route::middleware(['auth'])->group(function () {
    // 1. Route cho trang Thông tin
    Route::get('/profile/info', [UserController::class, 'editProfile'])->name('users.profile.info');
    Route::put('/profile/info', [UserController::class, 'updateProfile'])->name('users.profile.update');
    Route::get('/profile/info-admin', [UserController::class, 'editProfile'])->name('users.profile-info-admin');
    // 2. Route cho trang Mật khẩu
    Route::get('/profile/password', [UserController::class, 'changePasswordForm'])->name('users.profile.password');
    Route::put('/profile/password', [UserController::class, 'changePassword'])->name('users.password.update');
    // Gửi email đặt lại mật khẩu về email của tài khoản đang đăng nhập (Thiết lập tài khoản)
    Route::post('/profile/password/send-reset-link', [UserController::class, 'sendPasswordResetLink'])
        ->name('users.password.send-reset-link');
    // Gửi lại email xác thực (khi có pending_email)
    Route::post('/email/resend-verification', [UserController::class, 'resendEmailVerification'])
        ->name('users.email.resend');
    Route::get('/check-student-email', [StudentController::class, 'checkEmail'])
        ->name('students.check-email');

    Route::get('/chat', [DirectChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/{user}', [DirectChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{user}', [DirectChatController::class, 'send'])->name('chat.send');
    // Đánh dấu đã đọc hội thoại (AJAX) -> cập nhật badge riêng + badge tổng
    Route::post('/chat/{user}/read', [DirectChatController::class, 'markRead'])->name('chat.read');

    // Chặn / bỏ chặn / kiểm tra trạng thái chặn (có hộp thoại xác nhận ở client)
    Route::post('/block-user', [BlockUserController::class, 'block'])->name('block-user');
    Route::post('/unblock-user', [BlockUserController::class, 'unblock'])->name('unblock-user');
    Route::post('/block-user/check', [BlockUserController::class, 'check'])->name('block-user.check');
});
use Illuminate\Support\Facades\Auth;

Route::post('/logout', function () {
    Auth::logout();
    return redirect('/login');
})->name('logout');
use App\Http\Controllers\TopicRequestController;
use App\Http\Controllers\AdminChatMonitorController;

Route::controller(TopicRequestController::class)->middleware('auth')->group(function () {
    Route::get('/topic-requests', 'index')->name('topic_requests.index');


    Route::patch('/topic-requests/{topic_request}/approve', 'approve')->name('topic_requests.approve');
    Route::patch('/topic-requests/{topic_request}/reject', 'reject')->name('topic_requests.reject');
    Route::delete('/topic-requests/{topic_request}', 'destroy')->name('topic_requests.destroy');
});


Route::prefix('groups')->name('groups.')->group(function () {

    // Nhóm CHỈ ĐỌC: theo yêu cầu không được tạo/sửa/xóa nhóm qua trang quản lý,
    // không gán đề tài trực tiếp. Sinh viên tự tạo nhóm qua luồng user.create_group.
    Route::get('/', [GroupController::class, 'index'])->name('index');

    Route::get('/{id}', [GroupController::class, 'show'])->name('show');
});

// DEPRECATED: nhóm route lớp học cũ (/classes, /classes/create...) chỉ yêu cầu 'auth'
// → mọi tài khoản đã đăng nhập (kể cả sinh viên) đều gọi được CRUD lớp học.
// Toàn bộ nghiệp vụ lớp học phần của Admin nay nằm ở nhóm 'admin/classes' (middleware admin).
// Giữ lại đúng URL cũ /classes dưới dạng chuyển hướng để bookmark cũ không bị 404.
Route::middleware(['auth', 'admin'])->get('/classes', function () {
    return redirect()->route('admin.classes.index');
})->name('classes.index');
// Notification routes
Route::middleware(['auth'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
});
Route::middleware(['auth'])->group(function () {
    // Student routes
    Route::resource('students', StudentController::class);

    // Import/Export routes
    Route::get('students-import/form', [StudentController::class, 'importForm'])->name('students.import.form');
    Route::post('students-import', [StudentController::class, 'import'])->name('students.import');
    Route::get('students-export', [StudentController::class, 'export'])->name('students.export');
    Route::post('students/{id}/send-email', [StudentController::class, 'sendEmail'])->name('students.send-email');
    Route::post('students/{id}/reset-password', [StudentController::class, 'resetPassword'])->name('students.reset-password');
    Route::get('students-template/download', [StudentController::class, 'downloadTemplate'])->name('students.download-template');
});
Route::middleware(['auth'])->group(function () {
    // Chat routes - RA NGOÀI prefix "user"
    Route::get('/groups/{groupId}/chat', [GroupsChatController::class, 'showChat'])
        ->name('groups.chat.show');

    Route::post('/groups/{groupId}/chat/send', [GroupsChatController::class, 'sendMessage'])
        ->name('groups.chat.send');

    // Đánh dấu đã đọc nhóm (AJAX) -> cập nhật badge riêng + badge tổng
    Route::post('/groups/{groupId}/chat/read', [GroupsChatController::class, 'markRead'])
        ->name('groups.chat.read');

    // Polling fallback cho khung chat nhóm: trả các tin nhắn MỚI HƠN id cuối cùng.
    // Dùng khi WebSocket (Reverb/Echo) không khả dụng -> thành viên vẫn thấy được
    // thông báo / cảnh báo của admin mà không cần tải lại trang (chat_listener.js).
    Route::get('/groups/{groupId}/chat/messages', [GroupsChatController::class, 'messages'])
        ->name('groups.chat.messages');
});

use App\Http\Controllers\UserDashboardController;

Route::middleware(['auth'])->prefix('user')->name('user.')->group(function () {


    // ============================================================
    // DASHBOARD
    // ============================================================
    Route::get('/dashboard', [UserDashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/groups/create', [UserDashboardController::class, 'createGroupForm'])
        ->name('create_group');
    Route::post('/groups/store', [UserDashboardController::class, 'storeGroup'])
        ->name('store_group');

    // ============================================================
    // TOPICS (Đề tài)
    // ============================================================

    // Danh sách đề tài với filter
    Route::get('/topics', [UserDashboardController::class, 'topics'])
        ->name('topics');

    // Chi tiết đề tài
    Route::get('/topics/{id}', [UserDashboardController::class, 'topicDetail'])
        ->name('topic_detail');

    // Đăng ký đề tài cho nhóm
    Route::post('/topics/register', [UserDashboardController::class, 'registerTopic'])
        ->name('register_topic');

    // Hủy đăng ký đề tài
    Route::delete('/topics/cancel/{requestId}', [UserDashboardController::class, 'cancelTopicRequest'])
        ->name('cancel-topic-request');

    // Đề tài của tôi
    Route::get('/my-topics', [UserDashboardController::class, 'myTopics'])
        ->name('my_topics');

    Route::get('/groups/{groupId}/topics', [UserDashboardController::class, 'groupTopics'])
        ->name('group_topics');
    // ============================================================
    // GROUPS (Nhóm)
    // ============================================================

    // Danh sách nhóm của tôi
    Route::get('/groups', [UserDashboardController::class, 'myGroups'])
        ->name('my_groups');

    // Chi tiết nhóm
    Route::get('/groups/{id}', [UserDashboardController::class, 'groupDetail'])
        ->name('group_detail');


    // ============================================================
    // INVITATIONS (Lời mời)
    // ============================================================

    // Form mời thành viên vào nhóm (chỉ leader)
    Route::get('/groups/{groupId}/invite', [UserDashboardController::class, 'inviteMemberForm'])
        ->name('invite-member');

    // Gửi lời mời thành viên
    Route::post('/invites/send', [UserDashboardController::class, 'sendInvite'])
        ->name('send-invite');

    // Hủy lời mời (chỉ leader)
    Route::delete('/invites/{inviteId}', [UserDashboardController::class, 'cancelInvite'])
        ->name('cancel-invite');

    // Danh sách lời mời nhận được
    Route::get('/invites', [UserDashboardController::class, 'invites'])
        ->name('invites');

    // Bấm vào mục "Lời mời" -> đánh dấu đã xem: badge về 0 tới khi có lời mời mới
    Route::post('/invites/seen', [UserDashboardController::class, 'markInvitesSeen'])
        ->name('invites.seen');

    // Chấp nhận lời mời
    Route::post('/invites/{id}/accept', [UserDashboardController::class, 'acceptInvite'])
        ->name('accept-invite');

    // Từ chối lời mời
    Route::post('/invites/{id}/reject', [UserDashboardController::class, 'rejectInvite'])
        ->name('reject-invite');


    // ============================================================
    // JOIN REQUESTS (Yêu cầu tham gia)
    // ============================================================

    // Gửi yêu cầu tham gia nhóm
    Route::post('/join_requests/send', [UserDashboardController::class, 'sendJoinRequest'])
        ->name('send-join-request');

    // Danh sách yêu cầu tham gia đã gửi
    Route::get('/join_requests', [UserDashboardController::class, 'joinRequests'])
        ->name('join-requests');

    // Bấm vào mục "Yêu cầu" -> đánh dấu đã xem: badge về 0 tới khi có yêu cầu mới
    Route::post('/join_requests/seen', [UserDashboardController::class, 'markJoinRequestsSeen'])
        ->name('join-requests.seen');

    // Hủy yêu cầu tham gia
    Route::delete('/join_requests/{id}', [UserDashboardController::class, 'cancelRequest'])
        ->name('cancel-request');

    // Danh sách yêu cầu tham gia nhóm (cho leader xem)
    Route::get('/groups/{groupId}/join-requests', [UserDashboardController::class, 'groupJoinRequests'])
        ->name('group-join-requests');

    // Chấp nhận yêu cầu tham gia (chỉ leader)
    Route::post('/join-requests/{requestId}/approve', [UserDashboardController::class, 'approveJoinRequest'])
        ->name('approve-join-request');

    // Từ chối yêu cầu tham gia (chỉ leader)
    Route::post('/join-requests/{requestId}/reject', [UserDashboardController::class, 'rejectJoinRequest'])
        ->name('reject-join-request');

    Route::delete('/groups/{id}/leave', [UserDashboardController::class, 'leaveGroup'])->name('leave_group');
    // ============================================================
    // CLASSES (Lớp học)
    // ============================================================

    // Danh sách lớp học
    Route::get('/classes', [UserDashboardController::class, 'classes'])
        ->name('classes');

    // Tham gia lớp bằng mã lớp
    Route::post('/classes/join', [ClassJoinController::class, 'joinByCode'])
        ->name('join-class');

    // Danh sách nhóm còn thiếu thành viên
    Route::get('/available-groups', [UserDashboardController::class, 'availableGroups'])
        ->name('available_groups');

    // Chi tiết lớp học
    Route::get('/classes/{id}', [UserDashboardController::class, 'classDetail'])
        ->name('class_detail');



    // ============================================================
    // SUBJECTS (Môn học)
    // ============================================================

    // Danh sách môn học
    Route::get('/subjects', [UserDashboardController::class, 'subjects'])
        ->name('subjects');

    // Chi tiết môn học
    Route::get('/subjects/{id}', [UserDashboardController::class, 'subjectDetail'])
        ->name('subject_detail');
});

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\SubjectController;

// Đổi 'role:admin' thành 'admin'
Route::middleware(['auth', 'admin'])->group(function () {
    // Thống kê hệ thống (audit 2026-09-18: hoàn thiện StatisticsController)
    Route::get('admin/statistics', [StatisticsController::class, 'index'])
        ->name('admin.statistics.index');
    Route::get('admin/statistics/topics', [StatisticsController::class, 'topicStatistics'])
        ->name('admin.statistics.topics');
    Route::get('admin/statistics/groups', [StatisticsController::class, 'groupStatistics'])
        ->name('admin.statistics.groups');
    Route::get('admin/statistics/requests', [StatisticsController::class, 'requestStatistics'])
        ->name('admin.statistics.requests');
    Route::get('admin/statistics/users', [StatisticsController::class, 'userStatistics'])
        ->name('admin.statistics.users');

        Route::resource('admin/users', AdminController::class, ['as' => 'admin'])->except(['destroy']);
    // Import môn học từ Excel/CSV (phải đặt TRƯỚC resource để tránh route {subject} bắt sai)
    Route::get('admin/subjects/import/form', [SubjectController::class, 'importForm'])
        ->name('admin.subjects.import.form');
    Route::post('admin/subjects/import', [SubjectController::class, 'import'])
        ->name('admin.subjects.import');
    Route::get('admin/subjects/template', [SubjectController::class, 'downloadTemplate'])
        ->name('admin.subjects.download-template');
        Route::resource('admin/subjects', SubjectController::class, ['as' => 'admin']);
    Route::resource('admin/classes', ClassSectionController::class, ['as' => 'admin']);
    Route::resource('admin/classes', ClassSectionController::class, ['as' => 'admin']);

    // Khóa / Mở khóa tài khoản người dùng
    Route::patch('admin/users/{id}/toggle-active', [AdminController::class, 'toggleActive'])
        ->name('admin.users.toggle-active');
    Route::get('admin/users-import', [AdminController::class, 'importForm'])->name('admin.users.import.form');
    Route::post('admin/users-import', [AdminController::class, 'import'])->name('admin.users.import');

    // Khóa / Mở khóa lớp học
    Route::patch('admin/classes/{id}/toggle-active', [ClassSectionController::class, 'toggleActive'])
        ->name('admin.classes.toggle-active');

    // Quản lý sinh viên trong lớp học phần (Admin)
    Route::post('admin/classes/{id}/students', [ClassSectionController::class, 'addStudents'])
        ->name('admin.classes.students.add');
    Route::post('admin/classes/{id}/students/{studentId}/remove', [ClassSectionController::class, 'removeStudent'])
        ->name('admin.classes.students.remove');

    // Gửi thông báo hệ thống đến giảng viên
    Route::get('admin/notifications/create', [AdminNotificationController::class, 'create'])
        ->name('admin.notifications.create');
    Route::post('admin/notifications/send', [AdminNotificationController::class, 'send'])
        ->name('admin.notifications.send');

    // Admin giám sát chat (cá nhân + nhóm), xóa tin nhắn vi phạm, broadcast nhóm/toàn hệ thống
    Route::get('admin/chat-monitor/flagged-count', [AdminChatMonitorController::class, 'flaggedCount'])
        ->name('admin.chat.flagged-count');
    Route::get('admin/chat-monitor', [AdminChatMonitorController::class, 'index'])
        ->name('admin.chat.monitor');
    Route::delete('admin/chat-monitor/direct/{id}', [AdminChatMonitorController::class, 'destroyDirect'])
        ->name('admin.chat.direct.destroy');
    Route::delete('admin/chat-monitor/group/{id}', [AdminChatMonitorController::class, 'destroyGroup'])
        ->name('admin.chat.group.destroy');
    // Duyệt tin nhắn bị gắn cờ: admin xác nhận không vi phạm -> bỏ cờ (giữ tin nhắn)
    Route::patch('admin/chat-monitor/direct/{id}/unflag', [AdminChatMonitorController::class, 'unflagDirect'])
        ->name('admin.chat.direct.unflag');
    Route::patch('admin/chat-monitor/group/{id}/unflag', [AdminChatMonitorController::class, 'unflagGroup'])
        ->name('admin.chat.group.unflag');
    Route::post('admin/chat-broadcast/group', [AdminChatMonitorController::class, 'broadcastToGroup'])
        ->name('admin.chat.broadcast.group');
    Route::post('admin/chat-broadcast/all', [AdminChatMonitorController::class, 'broadcastToAll'])
        ->name('admin.chat.broadcast.all');
    // Admin gửi tin nhắn THÔNG BÁO / CẢNH BÁO thẳng vào khung chat nhóm
    // (không cần tham gia nhóm, không tạo group_members).
    Route::post('admin/chat-monitor/message-to-group', [AdminChatMonitorController::class, 'messageToGroup'])
        ->name('admin.chat.message.group');
});



Route::post('/chatbot/ask', [ChatbotController::class, 'ask'])->name('chatbot.ask')->middleware('auth');

// Gợi ý đề tài theo NGỮ NGHĨA: sinh viên nhập mô tả → Top K đề tài gần nghĩa nhất (cosine similarity).
// Khai báo trong web.php để dùng session + CSRF như toàn bộ app; JS gửi kèm header X-CSRF-TOKEN
// (xem resources/views/components/topic-recommender.blade.php). Service AI: port 8891.
Route::post('/api/recommend', [TopicRecommendationController::class, 'recommend'])
    ->name('api.recommend')
    ->middleware('auth');

// =====================================================================================
// BẢNG TIN LỚP HỌC (kiểu Google Classroom): thông báo của giảng viên + hoạt động nhóm
// + bình luận. Một bộ route dùng chung cho mọi vai trò — quyền được kiểm tra tập trung
// trong ClassStreamService (GV phụ trách/admin mới được đăng), nên KHÔNG cần middleware
// riêng cho từng vai trò.
// =====================================================================================
Route::middleware(['auth'])->group(function () {
    Route::get('classes/{classId}/stream', [ClassStreamController::class, 'index'])
        ->name('class.stream');
    Route::post('classes/{classId}/stream', [ClassStreamController::class, 'store'])
        ->name('class.stream.store');
    Route::patch('classes/{classId}/stream/{post}/pin', [ClassStreamController::class, 'pin'])
        ->name('class.stream.pin');
    Route::get('classes/{classId}/stream/{post}/comments', [ClassStreamController::class, 'comments'])
        ->name('class.stream.comments');
    Route::post('classes/{classId}/stream/{post}/comments', [ClassStreamController::class, 'storeComment'])
        ->name('class.stream.comment');
    Route::delete('classes/{classId}/stream/comments/{comment}', [ClassStreamController::class, 'destroyComment'])
        ->name('class.stream.comment.destroy');
    Route::delete('classes/{classId}/stream/{post}', [ClassStreamController::class, 'destroy'])
        ->name('class.stream.destroy');
});
