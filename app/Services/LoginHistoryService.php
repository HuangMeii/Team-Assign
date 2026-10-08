<?php

namespace App\Services;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * L09 (2.1) — Ghi & đọc LỊCH SỬ ĐĂNG NHẬP.
 *
 * Mọi thao tác đều fail-open: lỗi ghi lịch sử KHÔNG được chặn việc đăng nhập.
 */
class LoginHistoryService
{
    /** Số bản ghi tối đa giữ lại cho mỗi người dùng (lịch sử gọn). */
    public const MAX_PER_USER = 30;

    /**
     * Ghi 1 lần đăng nhập.
     *
     * @return bool true nếu đây là IP LẠ (chưa từng xuất hiện) — để controller
     *              hiện cảnh báo "đăng nhập từ IP mới".
     */
    public function record(User $user, Request $request): bool
    {
        $ip = $request->ip();
        $userAgent = mb_substr((string) $request->userAgent(), 0, 1000);

        try {
            $wasNewIp = $ip !== null && ! LoginHistory::where('user_id', $user->user_id)
                ->where('ip_address', $ip)
                ->exists();

            LoginHistory::create([
                'user_id' => $user->user_id,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);

            // Chỉ giữ MAX_PER_USER bản ghi gần nhất.
            $keepIds = LoginHistory::where('user_id', $user->user_id)
                ->orderByDesc('id')
                ->limit(self::MAX_PER_USER)
                ->pluck('id');

            LoginHistory::where('user_id', $user->user_id)
                ->whereNotIn('id', $keepIds)
                ->delete();

            return $wasNewIp;
        } catch (\Throwable $e) {
            Log::warning('Login history record failed (fail-open): ' . $e->getMessage());

            return false;
        }
    }

    /**
     * N bản ghi gần nhất (mới nhất trước) — dùng cho tab "Bảo mật".
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, LoginHistory>
     */
    public function latestFor(User $user, int $limit = 10)
    {
        return LoginHistory::where('user_id', $user->user_id)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /** Số lần đăng nhập đã ghi nhận. */
    public function countFor(User $user): int
    {
        return LoginHistory::where('user_id', $user->user_id)->count();
    }
}
