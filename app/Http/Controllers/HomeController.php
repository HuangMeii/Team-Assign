<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

/**
 * D2 — Route "entry" của app (trước đây là closure trong routes/web.php
 * khiến `php artisan route:cache` không chạy được ⇒ mỗi request phải
 * parse lại toàn bộ routes/web.php).
 */
class HomeController extends Controller
{
    /** Trang chủ `/`: đã đăng nhập thì vào dashboard theo vai trò, chưa thì về login. */
    public function index()
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $role = Auth::user()->role;

        return match ($role) {
            'student' => redirect()->route('user.dashboard'),
            'lecturer' => redirect()->route('dashboard'),
            'admin' => redirect()->route('admin.users.index'),
            default => redirect('/dashboard'),
        };
    }

    /** Giữ URL cũ `/classes` (bookmark không 404) → trang quản lý lớp của Admin. */
    public function classesRedirect()
    {
        return redirect()->route('admin.classes.index');
    }
}