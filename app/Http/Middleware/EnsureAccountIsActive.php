<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chặn tài khoản bị khóa (is_active = 0) hoặc đã xóa mềm (deleted_at) truy cập
 * khi đã có phiên — KỂ CẢ phiên được khôi phục bằng cookie "Ghi nhớ đăng nhập".
 *
 * Lý do: AuthController::login() chỉ check 2 cờ này lúc submit form; request sau
 * được SessionGuard::userFromRecaller() xác thực lại mà KHÔNG check gì ⇒ tài khoản
 * bị admin khóa vẫn giữ được cookie remember tối đa 5 năm.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof \App\Models\User) {
            $blocked = (isset($user->is_active) && ! $user->is_active)
                || $user->trashed()
                || (isset($user->is_deleted) && $user->is_deleted);

            if ($blocked) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Tai khoan cua ban da bi khoa hoac xoa. Vui long lien he quan tri vien.',
                ]);
            }
        }

        return $next($request);
    }
}
