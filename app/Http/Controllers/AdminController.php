<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Services\PasswordAuditService;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AccountsImport;
class AdminController extends Controller
{
    /**
     * Hiển thị danh sách người dùng (Quản lý Users)
     */
    public function index(Request $request)
    {
        // Khởi tạo query từ model User, tương tự cách ClassSectionController làm
        // SoftDeletes: User::query() tự loại bản ghi đã xóa mềm (deleted_at IS NULL)
        $query = User::query();

        // Filter theo Role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter theo trạng thái is_deleted (mặc định chỉ hiện chưa xóa)
        if ($request->filled('is_deleted')) {
            if ($request->is_deleted === 'deleted') {
                $query->onlyTrashed();
            } elseif ($request->is_deleted === 'all') {
                $query->withTrashed();
            }
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

    public function show($id)
    {
        $user = User::with(['passwordHistories.changer'])->findOrFail($id);
        return view('admin.users.show', compact('user'));
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
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
        ]);

        try {
            User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']), // Hash password bảo mật
                'role' => $validated['role'],
                'is_active' => true, // Mặc định tài khoản hoạt động
                'email_verified_at' => now(), // Tài khoản do admin tạo & bàn giao trực tiếp -> coi như email đã xác thực
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

        ], [
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
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

            if ($request->filled('password')) {
                PasswordAuditService::record($user, Auth::user(), 'admin');
            }

            return redirect()->route('admin.users.index')
                ->with('success', 'Cập nhật tài khoản thành công!');
        } catch (\Exception $e) {
            Log::error('Error updating user: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Có lỗi xảy ra khi cập nhật.');
        }
    }

    /**
     * Khóa / Mở khóa tài khoản người dùng
     * Bugfix C1 [R19]: Không cho Admin khóa Admin khác.
     */
    public function toggleActive($id)
    {
        $user = User::findOrFail($id);

        // Không cho phép tự khóa chính mình
        if ($user->user_id === Auth::id()) {
            return back()->with('error', 'Bạn không thể khóa tài khoản của chính mình!');
        }

        // Bugfix C1 [R19]: Không cho phép khóa Admin khác
        if ($user->role === 'admin') {
            return back()->with('error', 'Không thể khóa tài khoản Admin khác!');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'mở khóa' : 'khóa';
        return back()->with('success', "Đã {$status} tài khoản {$user->name}!");
    }

    public function importForm()
    {
        return view('admin.users.import');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv']);

        try {
            Excel::import(new AccountsImport(Auth::user()), $request->file('file'));
            return redirect()->route('admin.users.index')->with('success', 'Import tài khoản thành công.');
        } catch (\Throwable $exception) {
            Log::error('Account import failed: ' . $exception->getMessage());
            return back()->withInput()->with('error', 'Import không thành công: ' . $exception->getMessage());
        }
    }
}

