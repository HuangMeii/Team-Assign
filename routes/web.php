<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\GroupController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ClassSectionController;
use App\Http\Controllers\ClassJoinController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupsChatController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StudentController;



Route::get('/', function () {
    if (Illuminate\Support\Facades\Auth::check()) {

        $user = Illuminate\Support\Facades\Auth::user();
        if (in_array($user->role, ['student', 'leader'])) {
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

Route::get('/requests', function () {
    return view('requests'); // hoặc view nào m muốn
})->name('requests');




//  Auth routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


Route::middleware(['auth'])->group(function () {
    // 1. Route cho trang Thông tin
    Route::get('/profile/info', [UserController::class, 'editProfile'])->name('users.profile.info');
    Route::put('/profile/info', [UserController::class, 'updateProfile'])->name('users.profile.update');
    Route::get('/profile/info-admin', [UserController::class, 'editProfile'])->name('users.profile-info-admin');
    // 2. Route cho trang Mật khẩu
    Route::get('/profile/password', [UserController::class, 'changePasswordForm'])->name('users.profile.password');
    Route::put('/profile/password', [UserController::class, 'changePassword'])->name('users.password.update');
     Route::get('/check-student-email', [StudentController::class, 'checkEmail'])
        ->name('students.check-email');
});
use Illuminate\Support\Facades\Auth;

Route::post('/logout', function () {
    Auth::logout();
    return redirect('/login');
})->name('logout');
use App\Http\Controllers\TopicRequestController;

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

Route::middleware(['auth'])->group(function () {




    // Class Management Routes
    Route::get('/classes', [ClassSectionController::class, 'index'])->name('classes.index');
    Route::get('/classes/create', [ClassSectionController::class, 'create'])->name('classes.create');
    Route::post('/classes', [ClassSectionController::class, 'store'])->name('classes.store');
    Route::get('/classes/{id}', [ClassSectionController::class, 'show'])->name('classes.show');
    Route::get('/classes/{id}/edit', [ClassSectionController::class, 'edit'])->name('classes.edit');
    Route::put('/classes/{id}', [ClassSectionController::class, 'update'])->name('classes.update');
    Route::delete('/classes/{id}', [ClassSectionController::class, 'destroy'])->name('classes.destroy');


});
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
    Route::get('students-template/download', [StudentController::class, 'downloadTemplate'])->name('students.download-template');
});
Route::middleware(['auth'])->group(function () {
    // Chat routes - RA NGOÀI prefix "user"
    Route::get('/groups/{groupId}/chat', [GroupsChatController::class, 'showChat'])
        ->name('groups.chat.show');

    Route::post('/groups/{groupId}/chat/send', [GroupsChatController::class, 'sendMessage'])
        ->name('groups.chat.send');
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
use App\Http\Controllers\SubjectController;

// Đổi 'role:admin' thành 'admin'
Route::middleware(['auth', 'admin'])->group(function () {
    Route::resource('admin/users', AdminController::class, ['as' => 'admin']);
    Route::resource('admin/subjects', SubjectController::class, ['as' => 'admin']);
    Route::resource('admin/classes', ClassSectionController::class, ['as' => 'admin']);

    // Khóa / Mở khóa tài khoản người dùng
    Route::patch('admin/users/{id}/toggle-active', [AdminController::class, 'toggleActive'])
        ->name('admin.users.toggle-active');

    // Khóa / Mở khóa lớp học
    Route::patch('admin/classes/{id}/toggle-active', [ClassSectionController::class, 'toggleActive'])
        ->name('admin.classes.toggle-active');

    // Gửi thông báo hệ thống đến giảng viên
    Route::get('admin/notifications/create', [AdminNotificationController::class, 'create'])
        ->name('admin.notifications.create');
    Route::post('admin/notifications/send', [AdminNotificationController::class, 'send'])
        ->name('admin.notifications.send');
});



Route::post('/chatbot/ask', [ChatbotController::class, 'ask'])->name('chatbot.ask')->middleware('auth');