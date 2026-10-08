<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * L09 (2.1) — Một lần đăng nhập (IP + user_agent).
 *
 * Hiện ở tab "Bảo mật" của Thiết lập tài khoản; AuthController cũng dùng để
 * cảnh báo khi đăng nhập từ IP lạ.
 */
class LoginHistory extends Model
{
    protected $fillable = ['user_id', 'ip_address', 'user_agent'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /** Tên hệ điều hành rút gọn từ user_agent (chỉ để hiển thị). */
    public function deviceLabel(): string
    {
        $ua = (string) $this->user_agent;

        foreach (['Windows', 'Android', 'iPhone', 'iPad', 'Macintosh', 'Linux'] as $os) {
            if ($ua !== '' && stripos($ua, $os) !== false) {
                return $os;
            }
        }

        return 'Không xác định';
    }

    /** Tên trình duyệt rút gọn từ user_agent (chỉ để hiển thị). */
    public function browserLabel(): string
    {
        $ua = (string) $this->user_agent;

        foreach (['Edg' => 'Edge', 'Chrome' => 'Chrome', 'Firefox' => 'Firefox', 'Safari' => 'Safari'] as $needle => $name) {
            if ($ua !== '' && stripos($ua, $needle) !== false) {
                return $name;
            }
        }

        return 'Khác';
    }
}
