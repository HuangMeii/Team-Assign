<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ClassSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StudentsExport;
use App\Imports\StudentsImport;

class StudentController extends Controller
{
    /**
     * Hiển thị danh sách sinh viên
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = User::where('role', 'student')
            ->with('classes.subject');

        // Nếu là lecturer, chỉ hiển thị sinh viên trong lớp mình dạy
        if ($user->role === 'lecturer') {
            $lecturerClassIds = $user->classes->pluck('class_id');
            $query->whereHas('classes', function ($q) use ($lecturerClassIds) {

                $q->whereIn('class_sections.class_id', $lecturerClassIds);
            });

            $classes = $user->classes;
        } else {
            $classes = ClassSection::with('subject')->get();
        }

        // Filter theo lớp
        if ($request->filled('class_id')) {
            $query->whereHas('classes', function ($q) use ($request) {
                // FIX: Chỉ rõ tên bảng cho class_id
                $q->where('class_sections.class_id', $request->class_id);
            });
        }

        // Search theo tên hoặc email
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $students = $query->paginate(15)->withQueryString();

        return view('students.index', compact('students', 'classes'));
    }
    /**
     * Form tạo sinh viên mới
     */
    public function create()
    {
        $user = Auth::user();

        if ($user->role === 'lecturer') {
            $classes = $user->classes;
        } else {
            $classes = ClassSection::with('subject')->get();
        }

        return view('students.create', compact('classes'));
    }

    /**
     * Lưu sinh viên mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'class_ids' => 'required|array|min:1',
            'class_ids.*' => 'exists:class_sections,class_id',
        ], [
            'class_ids.required' => 'Vui lòng chọn ít nhất một lớp học',
            'class_ids.min' => 'Vui lòng chọn ít nhất một lớp học',
        ]);

        // Kiểm tra email đã tồn tại chưa
        $existingUser = User::where('email', $validated['email'])->first();

        if ($existingUser) {
            // Nếu user đã tồn tại

            if ($existingUser->role !== 'student') {
                return back()
                    ->withInput()
                    ->with('error', 'Email này đã được sử dụng bởi ' . ($existingUser->role === 'lecturer' ? 'giảng viên' : 'quản trị viên') . '!');
            }

            // Nếu là student, cập nhật thông tin
            $existingUser->update([
                'name' => $validated['name'],
                'password' => Hash::make($validated['password']),
            ]);

            // Lấy danh sách lớp hiện tại
            $currentClassIds = $existingUser->classes()->pluck('class_sections.class_id')->toArray();

            // Tìm các lớp mới cần thêm
            $newClassIds = array_diff($validated['class_ids'], $currentClassIds);

            // Tìm các lớp đã có
            $existingClassIds = array_intersect($validated['class_ids'], $currentClassIds);

            if (!empty($newClassIds)) {
                $existingUser->classes()->attach($newClassIds);
            }

            // Tạo thông báo chi tiết
            $messages = [];
            $messages[] = 'Đã cập nhật thông tin sinh viên';

            if (!empty($newClassIds)) {
                $messages[] = 'Thêm vào ' . count($newClassIds) . ' lớp mới';
            }

            if (!empty($existingClassIds)) {
                $messages[] = 'Đã có trong ' . count($existingClassIds) . ' lớp';
            }

            $message = implode('. ', $messages) . '!';

            return redirect()->route('students.show', $existingUser->user_id)
                ->with('success', $message);
        }

        // Tạo sinh viên mới nếu chưa tồn tại
        $student = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'student',
            'isFirstLogin' => true,
            'isHaveGroup' => false,
        ]);

        // Gán vào các lớp
        $student->classes()->attach($validated['class_ids']);

        return redirect()->route('students.show', $student->user_id)
            ->with('success', 'Thêm sinh viên mới thành công vào ' . count($validated['class_ids']) . ' lớp!');
    }
    public function checkEmail(Request $request)
    {
        $email = $request->get('email');

        if (!$email) {
            return response()->json([
                'exists' => false,
                'message' => 'Email không được để trống'
            ]);
        }

        $student = User::where('email', $email)->first();

        if (!$student) {
            return response()->json([
                'exists' => false,
                'message' => 'Email chưa tồn tại'
            ]);
        }

        // Kiểm tra role
        if ($student->role !== 'student') {
            return response()->json([
                'exists' => true,
                'is_student' => false,
                'message' => 'Email này thuộc về ' . ($student->role === 'lecturer' ? 'giảng viên' : 'quản trị viên'),
                'student' => null
            ]);
        }

        // Load classes
        $student->load('classes.subject');

        $classNames = $student->classes->map(function ($class) {
            return $class->class_name;
        })->implode(', ');

        return response()->json([
            'exists' => true,
            'is_student' => true,
            'student' => [
                'user_id' => $student->user_id,
                'name' => $student->name,
                'email' => $student->email,
                'classes' => $classNames ?: 'Chưa có lớp',
                'class_ids' => $student->classes->pluck('class_id')->toArray()
            ]
        ]);
    }
    /**
     * Xem chi tiết sinh viên
     */
    public function show($id)
    {
        $student = User::where('role', 'student')
            ->with(['classes.subject', 'groupsJoined', 'groupsLed'])
            ->findOrFail($id);

        $user = Auth::user();

        // Check quyền xem
        if ($user->role === 'lecturer') {
            $lecturerClassIds = $user->classes->pluck('class_id');
            $studentClassIds = $student->classes->pluck('class_id');

            if ($studentClassIds->intersect($lecturerClassIds)->isEmpty()) {
                abort(403, 'Bạn không có quyền xem sinh viên này.');
            }
        }

        return view('students.show', compact('student'));
    }

    /**
     * Form chỉnh sửa sinh viên
     */
    public function edit($id)
    {
        $student = User::where('role', 'student')->findOrFail($id);

        $user = Auth::user();

        if ($user->role === 'lecturer') {
            $classes = $user->classes;
        } else {
            $classes = ClassSection::with('subject')->get();
        }

        return view('students.edit', compact('student', 'classes'));
    }

    /**
     * Cập nhật sinh viên
     */
    public function update(Request $request, $id)
    {
        $student = User::where('role', 'student')->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id . ',user_id',
            'password' => 'nullable|string|min:6',
            'class_ids' => 'required|array',
            'class_ids.*' => 'exists:class_sections,class_id',
        ]);

        // Cập nhật thông tin
        $student->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        // Cập nhật password nếu có
        if ($request->filled('password')) {
            $student->update(['password' => Hash::make($validated['password'])]);
        }

        // Cập nhật lớp
        $student->classes()->sync($validated['class_ids']);

        return redirect()->route('students.show', $student->user_id)
            ->with('success', 'Cập nhật sinh viên thành công!');
    }

    /**
     * Xóa sinh viên
     */
    public function destroy($id)
    {
        $student = User::where('role', 'student')->findOrFail($id);

        // Kiểm tra xem sinh viên có đang trong nhóm không
        if ($student->groupsJoined->count() > 0 || $student->groupsLed->count() > 0) {
            return redirect()->route('students.index')
                ->with('error', 'Không thể xóa sinh viên đang trong nhóm!');
        }

        $student->delete();

        return redirect()->route('students.index')
            ->with('success', 'Xóa sinh viên thành công!');
    }

    /**
     * Export danh sách sinh viên ra Excel
     */
    public function export(Request $request)
    {
        $classId = $request->get('class_id');

        return Excel::download(new StudentsExport($classId), 'students_' . date('Y-m-d_H-i-s') . '.xlsx');
    }

    /**
     * Hiển thị form import
     */
    public function importForm()
    {
        $user = Auth::user();

        if ($user->role === 'lecturer') {
            $classes = $user->classes;
        } else {
            $classes = ClassSection::with('subject')->get();
        }

        return view('students.import', compact('classes'));
    }

    /**
     * Import sinh viên từ Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'class_id' => 'required|exists:class_sections,class_id',
        ]);

        try {
            Excel::import(new StudentsImport($request->class_id), $request->file('file'));

            return redirect()->route('students.index')
                ->with('success', 'Import sinh viên thành công!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Có lỗi khi import: ' . $e->getMessage());
        }
    }

    /**
     * Download template Excel
     */
    public function downloadTemplate()
    {
        $filePath = public_path('templates/students_template.xlsx');

        if (file_exists($filePath)) {
            return response()->download($filePath);
        }

        // Nếu không có file template, tạo một file mẫu đơn giản
        return Excel::download(new \App\Exports\StudentTemplateExport(), 'students_template.xlsx');
    }
}