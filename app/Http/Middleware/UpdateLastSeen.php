<?php

namespace App\Http\Middleware;

use App\Services\PresenceService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ghi nhận hoạt động của người dùng đã đăng nhập ⇒ phục vụ trạng thái online/offline.
 *
 * Nhẹ: chỉ ghi khi đã quá nhịp heartbeat (mặc định 60s), mỗi lần 1 câu UPDATE và
 * KHÔNG đụng `updated_at` (xem PresenceService::touch()).
 *
 * Fail-open: lỗi ghi `last_seen_at` không được làm hỏng request của người dùng.
 */
class UpdateLastSeen
{
    public function __construct(private readonly PresenceService $presence) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            try {
                $this->presence->touch($user);
            } catch (\Throwable $e) {
                Log::warning('Không ghi được users.last_seen_at: ' . $e->getMessage());
            }
        }

        return $next($request);
    }
}
