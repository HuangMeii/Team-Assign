<?php

namespace App\Http\Controllers;

use App\Services\PresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Trạng thái ONLINE/OFFLINE của người dùng (chấm xanh/xám).
 *
 *  - `POST /presence/ping`   : JS gọi mỗi 60 giây khi tab đang mở ⇒ giữ "Đang hoạt động"
 *                              kể cả khi người dùng không chuyển trang.
 *  - `GET  /presence/status` : trạng thái của danh sách user (danh sách chat + header hội thoại).
 *
 * Cả 2 endpoint đều fail-open: lỗi mạng ở client không ảnh hưởng chat.
 */
class PresenceController extends Controller
{
    public function __construct(
        private readonly PresenceService $presence,
    ) {}

    public function ping(): JsonResponse
    {
        $user = Auth::user();
        $written = false;

        if ($user) {
            try {
                $written = $this->presence->touch($user, true);
            } catch (\Throwable $e) {
                Log::warning('Presence ping không ghi được last_seen_at: ' . $e->getMessage());
            }
        }

        return response()->json([
            'ok' => true,
            'online' => true,
            'written' => $written,
            'next_ping_in' => PresenceService::HEARTBEAT_SECONDS,
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $ids = array_values(array_filter(
            (array) $request->input('user_ids', []),
            fn ($id) => $id !== '' && $id !== null
        ));

        return response()->json([
            'ok' => true,
            'statuses' => $this->presence->statusesFor($ids),
        ]);
    }
}
