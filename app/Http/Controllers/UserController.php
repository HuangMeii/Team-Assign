<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\EmailChangeVerificationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\PasswordAuditService;

class UserController extends Controller
{
    public function index()
    {
        $users = User::paginate(15);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:student,lecturer,admin',
        ]);

        // Password sẽ tự động hash trong model boot()
        User::create($validated);
        
        return redirect()->route('users.index')
            ->with('success', 'Người dùng đã được tạo thành công');
    }

    public function show(User $user)
    {
        // Load relationships theo tên đúng trong model
        $user->load([
            'groupsLed',        // Nhóm quản lý
            'groupsJoined',     // Nhóm tham gia
            'joinRequests',     // Yêu cầu tham gia
            'invites',          // Lời mời nhận
            'sentInvites',      // Lời mời đã gửi
            'classes'           // Lớp học
        ]);
        
        $user->load(['passwordHistories.changer']);
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->user_id . ',user_id',
            'role' => 'required|in:student,lecturer,admin',
        ]);

        $user->update($validated);
        
        return redirect()->route('users.show', $user)
            ->with('success', 'Thông tin người dùng đã được cập nhật');
    }

    public function profile()
    {
        $user = Auth::user();
        
    
        
        if ($user->role == 'student') {
            return view('users.profile-info', ['user' => $user]);
        }
        
        return view('users.profile-admin', ['user' => $user]);
    }

public function editProfile()
    {
        $user = Auth::user();
          if ($user->role == 'student') {
            return view('users.profile-info', ['user' => $user]);
        }
        
        return view('users.profile-admin', ['user' => $user]);
        
    }

    /**
     * [PUT] Xử lý cập nhật thông tin.
     *
     * Quy tắc:
     * - Không thay đổi gì -> cảnh báo "Chưa có thay đổi nào để cập nhật."
     * - Chỉ đổi tên (email giữ nguyên) -> cập nhật tên, không cần xác thực.
     * - Đổi email -> email KHÔNG có hiệu lực ngay: lưu vào pending_email và gửi
     *   email xác thực tới địa chỉ mới; người dùng click liên kết (signed, 60 phút)
     *   thì email mới mới có hiệu lực. Email hiện tại vẫn dùng để đăng nhập.
     */
    public function updateProfile(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
        ]);

        $newName = $validated['name'];
        $newEmail = strtolower(trim($validated['email']));
        $nameChanged = $newName !== $user->name;
        $emailChanged = $newEmail !== strtolower($user->email);

        // 1. Không có thay đổi nào
        if (!$nameChanged && !$emailChanged) {
            return back()->with('warning', 'Chưa có thay đổi nào để cập nhật.');
        }

        // 2. Email không đổi -> chỉ cập nhật tên, không cần xác thực
        if (!$emailChanged) {
            $user->update(['name' => $newName]);

            return back()
                ->with('success', 'Đã cập nhật thông tin cá nhân.')
                ->with('info', 'Email không thay đổi nên không cần xác thực.');
        }

        // 3. Đổi email: email mới không được trùng email / pending_email của người khác
        $emailTaken = User::where(function ($q) use ($newEmail, $user) {
                $q->where('email', $newEmail)
                  ->orWhere('pending_email', $newEmail);
            })
            ->where('user_id', '!=', $user->user_id)
            ->exists();

        if ($emailTaken) {
            return back()->withInput()->withErrors([
                'email' => 'Email này đã được sử dụng bởi tài khoản khác.',
            ]);
        }

        // Tên cập nhật ngay; email chỉ có hiệu lực sau khi xác thực
        $user->update([
            'name' => $newName,
            'pending_email' => $newEmail,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'users.email.verify',
            now()->addMinutes(60),
            ['id' => $user->user_id, 'hash' => sha1($newEmail)]
        );

        try {
            Mail::to($newEmail)->send(new EmailChangeVerificationMail($user, $newEmail, $verificationUrl));
        } catch (\Throwable $exception) {
            Log::error('Email verification send failed: ' . $exception->getMessage());
            return back()->with('error', 'Không gửi được email xác thực. Kiểm tra cấu hình SMTP/Mailpit và thử lại.');
        }

        return back()
            ->with('success', 'Đã cập nhật thông tin cá nhân.')
            ->with('info', "Đã gửi email xác thực tới {$newEmail}. Email hiện tại ({$user->email}) vẫn dùng để đăng nhập cho đến khi bạn xác thực.");
    }

    /**
     * [GET] Xác thực email mới (người dùng click liên kết trong email).
     * Liên kết có chữ ký (signed) + hash = sha1(email mới).
     */
    public function verifyEmailChange(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (empty($user->pending_email)) {
            return redirect()->route('users.profile.info')
                ->with('warning', 'Không có yêu cầu thay đổi email nào đang chờ xác thực.');
        }

        if (!hash_equals(sha1($user->pending_email), (string) $hash)) {
            return redirect()->route('users.profile.info')
                ->with('error', 'Liên kết xác thực không hợp lệ.');
        }

        $newEmail = $user->pending_email;

        // Re-check: email mới có bị tài khoản khác chiếm không (phòng race)
        $emailTaken = User::where(function ($q) use ($newEmail, $user) {
                $q->where('email', $newEmail)
                  ->orWhere('pending_email', $newEmail);
            })
            ->where('user_id', '!=', $user->user_id)
            ->exists();

        if ($emailTaken) {
            return redirect()->route('users.profile.info')
                ->with('error', "Email {$newEmail} đã được sử dụng bởi tài khoản khác. Vui lòng chọn email khác trong Thiết lập tài khoản.");
        }

        $user->update([
            'email' => $newEmail,
            'email_verified_at' => now(),
            'pending_email' => null,
        ]);

        return redirect()->route('users.profile.info')
            ->with('success', "Email của bạn đã được đổi thành {$newEmail} và xác thực thành công!");
    }

    /**
     * [POST] Gửi lại email xác thực cho email đang chờ xác thực.
     */
    public function resendEmailVerification()
    {
        /** @var User $user */
        $user = Auth::user();

        if (empty($user->pending_email)) {
            return back()->with('warning', 'Không có yêu cầu thay đổi email nào để gửi lại.');
        }

        $newEmail = $user->pending_email;

        $verificationUrl = URL::temporarySignedRoute(
            'users.email.verify',
            now()->addMinutes(60),
            ['id' => $user->user_id, 'hash' => sha1($newEmail)]
        );

        try {
            Mail::to($newEmail)->send(new EmailChangeVerificationMail($user, $newEmail, $verificationUrl));
        } catch (\Throwable $exception) {
            Log::error('Email verification resend failed: ' . $exception->getMessage());
            return back()->with('error', 'Không gửi lại được email. Kiểm tra cấu hình SMTP/Mailpit và thử lại.');
        }

        return back()->with('success', "Đã gửi lại email xác thực tới {$newEmail}.");
    }

    // --- 2. QUẢN LÝ MẬT KHẨU ---

    /**
     * [GET] Hiển thị form đổi mật khẩu
     */
    public function changePasswordForm()
    {
        $user = Auth::user();
         if ($user->role == 'student') {
            return view('users.profile-password', ['user' => $user]);
        }
        
        return view('users.profile-admin-password', ['user' => $user]);
       
    }

    /**
     * [PUT] Xử lý đổi mật khẩu
     */
    public function changePassword(Request $request)
    {
        $passwordResetVerified = $request->session()->get('password_reset_verified', false)
            || ($request->filled('reset_token') && $request->filled('reset_email'));
        $rules = [
            'new_password' => 'required|string|min:6|same:new_password_confirmation',
        ];

        if (!$passwordResetVerified) {
            $rules['current_password'] = 'required';
        }

        $request->validate($rules, [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'new_password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'new_password.same' => 'Xác nhận mật khẩu mới không khớp.',
        ]);

        /** @var User $user */
        $user = Auth::user();

        if ($request->filled('reset_token') && $request->filled('reset_email')) {
            if (strtolower($request->reset_email) !== strtolower($user->email)) {
                return back()->withErrors(['reset_token' => 'Liên kết đặt lại mật khẩu không thuộc tài khoản đang đăng nhập.']);
            }

            $status = Password::reset([
                'email' => $request->reset_email,
                'password' => $request->new_password,
                'password_confirmation' => $request->new_password_confirmation,
                'token' => $request->reset_token,
            ], function (User $resetUser) use ($request) {
                $resetUser->forceFill([
                    'password' => Hash::make($request->new_password),
                    'remember_token' => Str::random(60),
                ])->save();

                PasswordAuditService::record($resetUser, null, 'password_reset');
            });

            if ($status !== Password::PASSWORD_RESET) {
                return back()->withInput()->withErrors(['reset_token' => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.']);
            }

            $request->session()->forget('password_reset_verified');

            return redirect()->route('users.profile.password')
                ->with('success', 'Đặt lại mật khẩu thành công.');
        }

        if (!$passwordResetVerified && !Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không chính xác']);
        }

        $user->update([
            'password' => $request->new_password // Model của bạn tự hash
        ]);

        $request->session()->forget('password_reset_verified');

        PasswordAuditService::record($user, $user, 'self');

        return redirect()->route('users.profile.password')
            ->with('success', 'Đổi mật khẩu thành công!');
    }

    /**
     * [POST] Gửi email đặt lại mật khẩu về email của tài khoản đang đăng nhập.
     * Dùng cho mục "Quên mật khẩu?" trong Thiết lập tài khoản: người dùng mở email
     * và click liên kết xác thực để đặt mật khẩu mới.
     */
    public function sendPasswordResetLink()
    {
        /** @var User $user */
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập lại!');
        }

        try {
            $status = Password::sendResetLink(['email' => $user->email]);
        } catch (\Throwable $exception) {
            Log::error('Password reset email failed: ' . $exception->getMessage());
            return back()->with('error', 'Không gửi được email đặt lại mật khẩu. Kiểm tra cấu hình SMTP/Mailpit.');
        }

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'Đã gửi email đặt lại mật khẩu tới ' . $user->email . '. Vui lòng kiểm tra hộp thư và click vào liên kết để xác thực.');
        }

        return back()->with('error', 'Không thể gửi email đặt lại mật khẩu: ' . __($status));
    }

}