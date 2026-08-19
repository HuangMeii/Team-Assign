<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

 public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    $remember = $request->boolean('remember');
    Log::info('Remember input from checkbox: ' . ($remember ? 'true' : 'false'));

    if (Auth::attempt($credentials, $remember)) {
        $request->session()->regenerate();  // Regenerate ngay sau attempt, trước redirect

        $user = Auth::user();
        Log::info('Remember token in DB after login: ' . $user->getRememberToken());  
        Log::info('Config remember minutes: ' . config('auth.guards.web.remember'));  

        // Role redirect với response thật
        if ($user->role === 'student') {
            $response = redirect()->route('user.dashboard');
        } elseif ($user->role === 'lecturer') {
            $response = redirect()->route('dashboard');
        } elseif ($user->role === 'admin') {
            $response = redirect()->route('admin.users.index');
        } else {
            $response = redirect()->intended('/');  // Default
        }


       

        return $response; 
    }

    return back()->withErrors([
        'email' => 'Email hoặc mật khẩu không đúng.',
    ]);
}

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}