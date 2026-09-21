 <?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        // Đặt flag trong session → login sẽ chuyển hướng tới trang lựa chọn
        $request->session()->put('just_verified_email', true);

        // Chuyển hướng tới trang lựa chọn: tiếp tục hoặc đổi mật khẩu ngay
        return redirect()->route('login.verify.done');
    }
}

