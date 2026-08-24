<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    /**
     * Hiển thị form gửi thông báo hệ thống đến giảng viên.
     */
    public function create()
    {
        $lecturers = User::where('role', 'lecturer')->orderBy('name')->get();

        return view('admin.notifications.create', compact('lecturers'));
    }

    /**
     * Gửi thông báo hệ thống đến giảng viên (tất cả hoặc chọn cụ thể).
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'lecturer_ids' => 'nullable|array',
            'lecturer_ids.*' => 'exists:users,user_id',
        ]);

        $lecturerIds = $validated['lecturer_ids'] ?? null;

        $count = NotificationService::broadcastToLecturers(
            $validated['title'],
            $validated['message'],
            route('dashboard.lecturer'),
            $lecturerIds
        );

        if ($count === 0) {
            return redirect()->route('admin.notifications.create')
                ->with('error', 'Không có giảng viên nào để gửi thông báo!');
        }

        $message = is_array($lecturerIds)
            ? "Đã gửi thông báo đến {$count} giảng viên!"
            : "Đã gửi thông báo đến tất cả {$count} giảng viên!";

        return redirect()->route('admin.notifications.create')->with('success', $message);
    }
}
