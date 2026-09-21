<?php

namespace App\Http\Controllers;

use App\Models\Notifications; // Có chữ s
use App\Services\NotificationService;
use App\Services\InvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function __construct(
        private readonly InvitationService $invitations,
    ) {}

    /**
     * Get user's notifications
     */
    public function index()
    {
        $notifications = Notifications::forUser(Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Thông báo "yêu cầu tham gia nhóm": chỉ hiện nút Chấp nhận/Từ chối
        // khi yêu cầu còn hiệu lực (nhóm chưa đủ, sinh viên chưa có nhóm khác...).
        $joinRequestDecisions = $this->joinRequestDecisions($notifications);

        return view('notifications.index', compact('notifications', 'joinRequestDecisions'));
    }

    /**
     * Cờ quyết định cho các thông báo loại join_request trên trang hiện tại.
     *
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection  $notifications
     * @return array<int, array{can: bool, reason: ?string}>
     */
    private function joinRequestDecisions($notifications): array
    {
        $items = $notifications instanceof \Illuminate\Support\Collection
            ? $notifications
            : collect($notifications->items());

        $requestIds = $items
            ->filter(fn ($notification) => $notification->type === 'join_request')
            ->map(fn ($notification) => data_get($notification->data, 'join_request_id'))
            ->filter()
            ->unique()
            ->values();

        if ($requestIds->isEmpty()) {
            return [];
        }

        $decisions = [];

        $requests = \App\Models\Join_Requests::with(['group', 'member'])
            ->whereIn('id', $requestIds->all())
            ->get();

        foreach ($requests as $request) {
            $decision = $this->invitations->canHandleJoinRequest($request);
            $decisions[$request->id] = [
                'can'    => $decision['can'],
                'reason' => $decision['reason'],
            ];
        }

        // Thông báo còn nhưng yêu cầu đã bị xóa -> coi như đã xử lý
        foreach ($requestIds as $requestId) {
            if (!isset($decisions[$requestId])) {
                $decisions[$requestId] = ['can' => false, 'reason' => 'Yêu cầu không còn tồn tại'];
            }
        }

        return $decisions;
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
            $wasUnread = ! $notification->is_read;
            $notification->markAsRead();

            if ($wasUnread) {
                // Giảm badge "thông báo chưa đọc" của user (không xuống dưới 0).
                DB::table('users')
                    ->where('user_id', Auth::id())
                    ->where('unread_notifications', '>', 0)
                    ->decrement('unread_notifications');
            }

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