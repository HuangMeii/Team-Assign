<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRole
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
        // Kiểm tra user đã login chưa
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập!');
        }

        // Lấy thông tin user
        $user = Auth::user();
        
        // Kiểm tra user có tồn tại và có role không
        if (!$user || !isset($user->role)) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Thông tin người dùng không hợp lệ!');
        }

        // Kiểm tra role có phải là user hoặc student không
        if (in_array($user->role, ['user', 'student'])) {
            return $next($request);
        }

        // Redirect theo role khác
        switch ($user->role) {
            case 'admin':
                return redirect()->route('admin.dashboard')->with('warning', 'Bạn không có quyền truy cập trang này!');
                
            case 'lecturer':
                return redirect()->route('lecturer.dashboard')->with('warning', 'Bạn không có quyền truy cập trang này!');
                
            default:
                Auth::logout();
                return redirect()->route('login')->with('error', 'Role không hợp lệ!');
        }
    }
}