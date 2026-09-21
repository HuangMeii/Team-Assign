<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        Log::info("Remember input from checkbox: " . ($remember ? "true" : "false"));

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Chặn tài khoản soft-deleted (is_deleted)
            if (isset($user->is_deleted) && $user->is_deleted) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withErrors([
                    "email" => "Tai khoan cua ban da bi xoa. Vui long lien he quan tri vien.",
                ]);
            }

            // Kiem tra tai khoan co bi khoa khong
            if (isset($user->is_active) && !$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withErrors([
                    "email" => "Tai khoan cua ban da bi khoa. Vui long lien he quan tri vien.",
                ]);
            }

            Log::info("Remember token in DB after login: " . $user->getRememberToken());
            Log::info("Config remember minutes: " . config("auth.guards.web.remember"));

            // Neu user chua xac minh email -> yeu cau xac minh
            if (!$user->hasVerifiedEmail()) {
                return redirect()->route("verification.notice");
            }

            // Neu user vua moi xac nhan email -> trang lua chon
            if (session("just_verified_email")) {
                $request->session()->forget("just_verified_email");
                return redirect()->route("login.verify.done");
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

        return back()->withErrors([
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
     * Trang lua chon sau khi xac thuc email thanh cong.
     */
    public function verifyDone(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route("login");
        }

        $user = Auth::user();
        if (!$user->hasVerifiedEmail()) {
            return redirect()->route("verification.notice");
        }

        return view("auth.verify-done");
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
