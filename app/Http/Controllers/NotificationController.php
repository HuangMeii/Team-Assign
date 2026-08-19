<?php

namespace App\Http\Controllers;

use App\Models\Notifications; // Có chữ s
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Get user's notifications
     */
    public function index()
    {
        $notifications = Notifications::forUser(Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Get unread notifications count (for AJAX)
     */
    public function unreadCount()
    {
        try {
            $count = Notifications::forUser(Auth::id())
                ->unread()
                ->count();

            return response()->json(['count' => $count]);
        } catch (\Exception $e) {
            Log::error('Error getting unread count: ' . $e->getMessage());
            return response()->json(['count' => 0], 500);
        }
    }

    /**
     * Get recent notifications (for dropdown)
     */
    public function recent()
    {
        try {
            $notifications = Notifications::forUser(Auth::id())
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $unreadCount = Notifications::forUser(Auth::id())
                ->unread()
                ->count();

            // Format notifications for response
            $formattedNotifications = $notifications->map(function($notification) {
                return [
                    'notification_id' => $notification->notification_id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'icon' => $notification->icon,
                    'color' => $notification->color,
                    'is_read' => $notification->is_read,
                    'created_at' => $notification->created_at->toISOString(),
                    'url' => $notification->url,
                ];
            });

            return response()->json([
                'notifications' => $formattedNotifications,
                'unread_count' => $unreadCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading notifications: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'notifications' => [],
                'unread_count' => 0,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        try {
            $notification = Notifications::forUser(Auth::id())->findOrFail($id);
            $notification->markAsRead();

            if ($notification->url) {
                return redirect($notification->url);
            }

            return back()->with('success', 'Đã đánh dấu là đã đọc');
        } catch (\Exception $e) {
            Log::error('Error marking notification as read: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra');
        }
    }

    /**
     * Mark all as read
     */
    public function markAllAsRead()
    {
        try {
            NotificationService::markAllAsRead(Auth::id());
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error marking all as read: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete notification
     */
    public function destroy($id)
    {
        try {
            $notification = Notifications::forUser(Auth::id())->findOrFail($id);
            $notification->delete();

            return back()->with('success', 'Đã xóa thông báo');
        } catch (\Exception $e) {
            Log::error('Error deleting notification: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra');
        }
    }
}