<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view("auth.login");
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            "email" => ["required", "email"],
            "password" => ["required"],
        ]);

        $remember = $request->boolean("remember");

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Chặn tài khoản soft-deleted (is_deleted)
            if (isset($user->is_deleted) && $user->is_deleted) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withInput()->withErrors([
                    "email" => "Tai khoan cua ban da bi xoa. Vui long lien he quan tri vien.",
                ]);
            }

            // Kiem tra tai khoan co bi khoa khong
            if (isset($user->is_active) && !$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withInput()->withErrors([
                    "email" => "Tai khoan cua ban da bi khoa. Vui long lien he quan tri vien.",
                ]);
            }

            // L06 (quyet dinh 5b): tai khoan vua bi Admin reset mat khau
            // (users.must_change_password = true) phai doi mat khau truoc khi vao dashboard.
            if (!empty($user->must_change_password)) {
                return redirect()->route("users.profile.password")
                    ->with("warning", "Mat khau cua ban da duoc dat lai. Vui long doi mat khau truoc khi tiep tuc.");
            }

            // Role redirect
            if (in_array($user->role, ["student", "leader"])) {
                $response = redirect()->route("user.dashboard");
            } elseif ($user->role === "lecturer") {
                $response = redirect()->route("dashboard");
            } elseif ($user->role === "admin") {
                $response = redirect()->route("admin.users.index");
            } else {
                $response = redirect()->intended("/");
            }

            return $response;
        }

        return back()->withInput()->withErrors([
            "email" => "Email hoac mat khau khong dung.",
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route("login");
    }

    /**
     * Đat danau session de chuyen huong toi trang doi mat khau
     * voi phan "mat khau hien tai" an di.
     */
    public function prepareChangePassword(Request $request)
    {
        $request->session()->put("password_reset_verified", true);

        if (Auth::user()->role === "student") {
            return redirect()->route("users.profile.password");
        }

        return redirect()->route("users.profile-admin.password");
    }
}
