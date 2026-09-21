<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (\Throwable $exception) {
            Log::error('Password reset email failed: ' . $exception->getMessage());

            return back()->withInput()->with('error', 'Không gửi được email đặt lại mật khẩu. Vui lòng kiểm tra cấu hình email.');
        }

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'Đã gửi email đặt lại mật khẩu. Vui lòng kiểm tra hộp thư.');
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput($request->only('email'))
                ->with('error', 'Vui lòng chờ trước khi yêu cầu gửi lại email.');
        }

        return back()->withInput($request->only('email'))
            ->with('error', 'Không thể gửi email đặt lại mật khẩu. Email có thể chưa được đăng ký.');
    }
}
