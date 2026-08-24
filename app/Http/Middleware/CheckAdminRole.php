<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập!');
        }

        $user = Auth::user();
        
        if (!$user || !isset($user->role)) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Thông tin người dùng không hợp lệ!');
        }

        if ($user->role === 'admin') {
            return $next($request);
        }

        // Redirect theo role
        if (in_array($user->role, ['user', 'student', 'leader'])) {
            return redirect()->route('user.dashboard')->with('warning', 'Bạn không có quyền truy cập trang này!');
        }


        if ($user->role === 'lecturer') {
            return redirect()->route('dashboard.lecturer')->with('warning', 'Bạn không có quyền truy cập trang này!');
        }

        Auth::logout();
        return redirect()->route('login')->with('error', 'Bạn không có quyền truy cập!');
    }
}