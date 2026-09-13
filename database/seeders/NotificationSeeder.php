<?php

namespace Database\Seeders;

use App\Models\Notifications;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Bugfix D5 [R35]: Tạo dữ liệu test cho thông báo.
     */
    public function run(): void
    {
        $student = User::where('role', 'student')->first();
        $lecturer = User::where('role', 'lecturer')->first();

        if (!$student) {
            return;
        }

        // Thông báo lời mời tham gia nhóm
        Notifications::create([
            'user_id' => $student->user_id,
            'type' => 'invite',
            'title' => 'Lời mời tham gia nhóm',
            'message' => 'Bạn nhận được lời mời tham gia nhóm "Nhóm Alpha" từ Giảng viên A.',
            'is_read' => false,
            'url' => route('invites.index'),
        ]);

        // Thông báo yêu cầu tham gia được chấp nhận
        Notifications::create([
            'user_id' => $student->user_id,
            'type' => 'join_request',
            'title' => 'Yêu cầu tham gia được chấp nhận',
            'message' => 'Yêu cầu tham gia nhóm "Nhóm Beta" của bạn đã được chấp nhận.',
            'is_read' => false,
            'url' => route('user.my_groups'),
        ]);

        // Thông báo đăng ký đề tài được duyệt
        Notifications::create([
            'user_id' => $student->user_id,
            'type' => 'topic_approved',
            'title' => 'Đăng ký đề tài được duyệt',
            'message' => 'Đề tài "Xây dựng website quản lý thư viện" của nhóm bạn đã được duyệt.',
            'is_read' => true,
            'url' => route('user.my_topics'),
        ]);

        // Thông báo đăng ký đề tài bị từ chối
        Notifications::create([
            'user_id' => $student->user_id,
            'type' => 'topic_rejected',
            'title' => 'Đăng ký đề tài bị từ chối',
            'message' => 'Đề tài "Ứng dụng quản lý chi tiêu cá nhân" của nhóm bạn đã bị từ chối. Lý do: Đề tài đã đủ số lượng nhóm.',
            'is_read' => true,
            'url' => route('user.my_topics'),
        ]);

        // Thông báo từ admin (broadcast)
        Notifications::create([
            'user_id' => $student->user_id,
            'type' => 'system',
            'title' => 'Thông báo từ hệ thống',
            'message' => 'Hệ thống sẽ bảo trì vào ngày 20/09/2026. Vui lòng lưu công việc trước thời gian này.',
            'is_read' => false,
            'url' => null,
        ]);

        // Thông báo cho giảng viên
        if ($lecturer) {
            Notifications::create([
                'user_id' => $lecturer->user_id,
                'type' => 'join_request',
                'title' => 'Yêu cầu tham gia nhóm mới',
                'message' => 'Sinh viên A đã gửi yêu cầu tham gia nhóm "Nhóm Alpha".',
                'is_read' => false,
                'url' => route('user.my_groups'),
            ]);

            Notifications::create([
                'user_id' => $lecturer->user_id,
                'type' => 'topic_request',
                'title' => 'Đăng ký đề tài mới',
                'message' => 'Nhóm Alpha đã đăng ký đề tài "Xây dựng website quản lý thư viện".',
                'is_read' => true,
                'url' => route('topic_requests.index'),
            ]);
        }
    }
}
