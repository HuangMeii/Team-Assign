<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * L09 (2.2) — Áp dụng tuỳ chọn NGÔN NGỮ (`users.locale`) + MÚI GIỜ (`users.timezone`)
 * của người dùng cho MỖI request.
 *
 * Mặc định `vi` (khớp UI hiện tại). Fail-open: giá trị không hợp lệ thì giữ mặc định,
 * không bao giờ làm hỏng request.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user) {
            $locale = $user->locale ?: config('app.locale');

            if (in_array($locale, User::LOCALES, true)) {
                App::setLocale($locale);
            }

            $timezone = $user->timezone;

            if (is_string($timezone) && $timezone !== '' && in_array($timezone, timezone_identifiers_list(), true)) {
                date_default_timezone_set($timezone);
            }
        }

        return $next($request);
    }
}
