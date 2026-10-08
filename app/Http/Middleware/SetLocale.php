<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

/**
 * L09 (2.2) — Áp dụng tuỳ chọn NGÔN NGỮ (`users.locale`) + MÚI GIỜ (`users.timezone`)
 * của người dùng cho MỖI request.
 *
 * Bó-2 (fix BẤT BIẾN THỜI GIAN): TRƯỚC ĐÂY middleware gọi `date_default_timezone_set($tz)`
 * ⇒ 3 hệ quả xấu:
 *   1. `now()` (Laravel `Date::now()` → `Carbon::now()` dùng timezone mặc định của PHP)
 *      trả về giờ của user ⇒ MỌI `created_at/updated_at/last_seen_at/last_read_at/flagged_at`
 *      ghi trong request đó bị LỆCH GIỜ so với phần còn lại của hệ thống (DB UTC);
 *   2. PHP-FPM worker là process DÀI HẠN ⇒ timezone của user A "dính" sang request của user B
 *      (vì middleware chỉ set khi user B có timezone, không reset khi không có);
 *   3. Các phép so sánh thời gian GIỮA 2 NGƯỜI khác múi giờ sai ⇒ badge tin nhắn chưa đọc
 *      (`chat_messages.created_at > group_chat_reads.last_read_at`) và trạng thái online/offline
 *      (`PresenceService`) bị lệch.
 *
 * Cách làm đúng: luôn RESET mặc định về `app.timezone` (chuẩn hoá dữ liệu), rồi chỉ dùng
 * timezone của user cho phần HIỂN THỊ (`config('app.display_timezone')` + macro
 * `Carbon::displayTz()` — xem AppServiceProvider).
 *
 * Fail-open: giá trị không hợp lệ thì giữ mặc định, không bao giờ làm hỏng request.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1) Luôn đưa mặc định về timezone của ứng dụng (UTC) — chặn rò rỉ giữa các request
        //    trên cùng PHP-FPM worker.
        date_default_timezone_set(config('app.timezone', 'UTC'));

        $user = Auth::user();

        if ($user) {
            // 2) Ngôn ngữ giao diện
            $locale = $user->locale ?: config('app.locale');

            if (in_array($locale, User::LOCALES, true)) {
                App::setLocale($locale);
            }

            // 3) Múi giờ CHỈ để hiển thị (không đổi mặc định toàn cục)
            $timezone = $user->timezone;

            if (is_string($timezone) && $timezone !== '' && in_array($timezone, timezone_identifiers_list(), true)) {
                Config::set('app.display_timezone', $timezone);
            }
        }

        return $next($request);
    }
}
