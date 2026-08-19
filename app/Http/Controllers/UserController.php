<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

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
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:student,leader,lecturer,admin',
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
            'role' => 'required|in:student,leader,lecturer,admin',
        ]);

        $user->update($validated);
        
        return redirect()->route('users.show', $user)
            ->with('success', 'Thông tin người dùng đã được cập nhật');
    }

    public function destroy(User $user)
    {
        $user->delete();
        
        return redirect()->route('users.index')
            ->with('success', 'Người dùng đã được xóa');
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
     * [PUT] Xử lý cập nhật thông tin
     */
    public function updateProfile(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->user_id . ',user_id',
        ]);

        $user->update($validated);

        return redirect()->route('users.profile.info')
            ->with('success', 'Thông tin cá nhân đã được cập nhật.');
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
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không chính xác']);
        }

        $user->update([
            'password' => $request->new_password // Model của bạn tự hash
        ]);

        return redirect()->route('users.profile.password')
            ->with('success', 'Đổi mật khẩu thành công!');
    }

}