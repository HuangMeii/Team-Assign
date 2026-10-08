<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bó-4 (L06 hardening) — ÉP đổi mật khẩu khi `users.must_change_password = true`.
 *
 * Trước đây chỉ có "gate mềm" ở bước ĐĂNG NHẬP: sau khi login hệ thống chuyển người dùng
 * tới trang Đổi mật khẩu, nhưng họ chỉ cần gõ URL khác là dùng hệ thống bình thường
 * (cờ `must_change_password` không được kiểm tra ở request nào khác).
 *
 * Middleware này chặn MỌI route khác (trừ trang đổi mật khẩu / đăng xuất / gửi link reset)
 * cho tới khi đổi xong. Chỉ áp dụng cho người ĐÃ đăng nhập nên không ảnh hưởng khách.
 */
class EnsurePasswordIsChanged
{
    /** Các route ĐƯỢC PHÉP truy cập khi còn "nợ" đổi mật khẩu. */
    private const ALLOWED_ROUTES = [
        'users.profile.password',           // GET  trang Đổi mật khẩu
        'users.password.update',            // PUT  xử lý đổi mật khẩu
        'users.password.send-reset-link',   // POST gửi link đặt lại qua email
        'logout',                           // POST đăng xuất
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->must_change_password) {
            $routeName = $request->route()?->getName();

            if (! in_array($routeName, self::ALLOWED_ROUTES, true)) {
                return redirect()->route('users.profile.password')
                    ->with('warning', 'Bạn cần đổi mật khẩu trước khi tiếp tục sử dụng hệ thống.');
            }
        }

        return $next($request);
    }
}
