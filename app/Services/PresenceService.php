<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Trạng thái ONLINE / OFFLINE của người dùng (chấm xanh / xám) + nhãn "hoạt động X trước".
 *
 * Cách hoạt động:
 *   - `users.last_seen_at` được cập nhật bởi middleware `UpdateLastSeen` (mỗi request web,
 *     tối đa 1 lần/60 giây) và bởi `POST /presence/ping` (JS gọi mỗi 60 giây khi tab mở).
 *   - ONLINE  = last_seen_at trong vòng 2 phút (rộng gấp 2 lần nhịp heartbeat để không nhấp nháy).
 *   - OFFLINE = hiện "Hoạt động X phút/giờ/ngày trước"; quá 7 ngày chỉ ghi "hơn 7 ngày trước".
 *
 * Mọi thao tác ghi đều nhẹ (1 câu UPDATE, không đụng updated_at) và fail-open:
 * lỗi ghi `last_seen_at` KHÔNG được làm hỏng request của người dùng.
 */
class PresenceService
{
    /** Còn coi là online trong bao nhiêu giây kể từ lần hoạt động cuối. */
    public const ONLINE_WINDOW_SECONDS = 120;

    /** Nhịp heartbeat của client (giây) — JS ping + middleware cũng guard theo mốc này. */
    public const HEARTBEAT_SECONDS = 60;

    /** Quá bao nhiêu ngày thì chỉ ghi "hơn N ngày trước". */
    public const MAX_DAYS_LABEL = 7;

    /**
     * Ghi nhận hoạt động của user.
     *
     * @param  bool  $force  true = ghi ngay (dùng cho /presence/ping), false = chỉ ghi khi đã quá nhịp
     * @return bool  true nếu có ghi
     */
    public function touch(User $user, bool $force = false): bool
    {
        if (! $force && ! $this->shouldTouch($user)) {
            return false;
        }

        $now = now();

        // UPDATE thẳng 1 câu: không kéo theo updated_at, không bắn model events.
        User::whereKey($user->getKey())->update(['last_seen_at' => $now]);

        // Đồng bộ instance đang dùng trong request này để view hiển thị đúng ngay.
        $user->setAttribute('last_seen_at', $now);

        return true;
    }

    /** Đã đến nhịp cần ghi lại last_seen_at chưa? (tránh 1 câu UPDATE cho MỖI request) */
    public function shouldTouch(User $user): bool
    {
        $lastSeen = $user->last_seen_at;

        return $lastSeen === null || $lastSeen->diffInSeconds(now()) >= self::HEARTBEAT_SECONDS;
    }

    /** User có đang online không (chưa từng hoạt động ⇒ offline). */
    public function isOnline(?User $user): bool
    {
        if (! $user || ! $user->last_seen_at) {
            return false;
        }

        return $user->last_seen_at->diffInSeconds(now()) <= self::ONLINE_WINDOW_SECONDS;
    }

    /** Nhãn hiển thị: "Đang hoạt động" / "Hoạt động 5 phút trước" / "Hoạt động hơn 7 ngày trước". */
    public function label(?User $user): string
    {
        if (! $user) {
            return 'Không xác định';
        }

        if ($this->isOnline($user)) {
            return 'Đang hoạt động';
        }

        if (! $user->last_seen_at) {
            return 'Chưa hoạt động';
        }

        return 'Hoạt động ' . $this->agoLabel($user->last_seen_at) . ' trước';
    }

    /** Khoảng thời gian dạng chữ: "vài giây" · "5 phút" · "3 giờ" · "2 ngày" · "hơn 7 ngày". */
    public function agoLabel(Carbon $at): string
    {
        $seconds = max(0, (int) $at->diffInSeconds(now()));

        if ($seconds < 60) {
            return 'vài giây';
        }

        $minutes = intdiv($seconds, 60);
        if ($minutes < 60) {
            return $minutes . ' phút';
        }

        $hours = intdiv($minutes, 60);
        if ($hours < 24) {
            return $hours . ' giờ';
        }

        $days = intdiv($hours, 24);
        if ($days <= self::MAX_DAYS_LABEL) {
            return $days . ' ngày';
        }

        return 'hơn ' . self::MAX_DAYS_LABEL . ' ngày';
    }

    /**
     * Trạng thái của 1 user: dùng để render chấm màu + nhãn.
     *
     * @return array{online: bool, label: string, color: string}
     */
    public function statusFor(?User $user): array
    {
        $online = $this->isOnline($user);

        return [
            'online' => $online,
            'label' => $this->label($user),
            'color' => $online ? 'success' : 'secondary',
        ];
    }

    /**
     * Trạng thái của nhiều user — dùng cho endpoint `/presence/status` (JS poll).
     *
     * @param  array<int|string>  $userIds
     * @return array<int, array{online: bool, label: string, color: string}>
     */
    public function statusesFor(array $userIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if ($ids === []) {
            return [];
        }

        $users = User::whereIn('user_id', $ids)->get(['user_id', 'last_seen_at']);

        $statuses = [];
        foreach ($users as $user) {
            $statuses[(int) $user->user_id] = $this->statusFor($user);
        }

        return $statuses;
    }
}
