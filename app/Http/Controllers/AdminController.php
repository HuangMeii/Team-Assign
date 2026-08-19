<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ClassSection;
use App\Models\Groups;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
class AdminController extends Controller
{
    /**
     * Hiển thị danh sách người dùng (Quản lý Users)
     */
    public function index(Request $request)
    {
        // Khởi tạo query từ model User, tương tự cách ClassSectionController làm
        $query = User::query();

        // Filter theo Role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Search theo tên hoặc email, logic giống ClassSectionController
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Paginate kết quả, tham khảo từ NotificationController
        $users = $query->orderBy('created_at', 'desc')->paginate(15);
        
        // Giữ lại các tham số filter khi chuyển trang
        $users->appends($request->query());

        return view('admin.users.index', compact('users'));
    }

    /**
     * Form tạo người dùng mới
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Lưu người dùng mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => ['required', Rule::in(['student', 'lecturer', 'admin'])],
        ], [
            'email.unique' => 'Email này đã được sử dụng.',
            'role.in' => 'Vai trò không hợp lệ.',
        ]);

        try {
            User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']), // Hash password bảo mật
                'role' => $validated['role'],
                'is_have_group' => false, // Default value
            ]);

            return redirect()->route('admin.users.index')
                ->with('success', 'Tạo tài khoản thành công!');
        } catch (\Exception $e) {
            Log::error('Error creating user: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Có lỗi xảy ra khi tạo tài khoản.');
        }
    }

    /**
     * Form chỉnh sửa người dùng
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Cập nhật thông tin người dùng
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->user_id, 'user_id')], // Ignore ID hiện tại
            'role' => ['required', Rule::in(['student', 'lecturer', 'admin'])],
            'password' => 'nullable|string|min:6', // Password không bắt buộc nhập lại
        ]);

        try {
            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
            ];

            // Chỉ update password nếu có nhập mới
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $user->update($updateData);

            return redirect()->route('admin.users.index')
                ->with('success', 'Cập nhật tài khoản thành công!');
        } catch (\Exception $e) {
            Log::error('Error updating user: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Có lỗi xảy ra khi cập nhật.');
        }
    }

    /**
     * Xóa người dùng
     */
    public function destroy($id)
    {

        $id_Auth=Auth::user()->id;
        // Không cho phép tự xóa chính mình
        if ($id ==$id_Auth ) {
            return back()->with('error', 'Bạn không thể xóa chính tài khoản mình đang đăng nhập!');
        }

        $user = User::findOrFail($id);

        // Kiểm tra ràng buộc dữ liệu trước khi xóa (Validate logic tương tự ClassSectionController)
        if ($user->role === 'lecturer') {
            // Kiểm tra giảng viên có đang dạy lớp nào không
            $hasClasses = ClassSection::whereHas('subject', function($q) use ($user) {
                $q->where('lecturer_id', $user->user_id); // Giả sử subject có lecturer_id hoặc bảng liên kết
            })->exists();
            
            // Hoặc kiểm tra quan hệ classes trong model User (như trong DashboardController)
            if ($user->classes()->count() > 0) {
                 return back()->with('error', 'Không thể xóa giảng viên đang phụ trách lớp học!');
            }
        }

        if ($user->role === 'student') {
            // Kiểm tra sinh viên có đang trong nhóm không (Is Leader hoặc Member)
            $isLeader = Groups::where('leader_id', $user->user_id)->exists();
            if ($isLeader) {
                return back()->with('error', 'Sinh viên đang là trưởng nhóm, không thể xóa!');
            }
            
            // Logic User::is_have_group từ InviteController
            if ($user->is_have_group) {
                 return back()->with('error', 'Sinh viên đang tham gia nhóm, hãy xóa khỏi nhóm trước!');
            }
        }

        try {
            $user->delete();
            return back()->with('success', 'Xóa tài khoản thành công!');
        } catch (\Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi xóa tài khoản.');
        }
    }
}