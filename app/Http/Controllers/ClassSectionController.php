<?php

namespace App\Http\Controllers;

use App\Models\ClassSection;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ClassSectionController extends Controller
{
    public function index(Request $request)
    {
       
        $query = ClassSection::with(['subject', 'lecturers'])
                             ->withCount(['users', 'groups']);

        // Filter: Tìm kiếm
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('class_name', 'like', "%{$search}%")
                  ->orWhereHas('subject', function($subQ) use ($search) {
                      $subQ->where('subject_name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter: Môn học
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

       
        if ($request->filled('lecturer_id')) {
            $query->whereHas('lecturers', function($q) use ($request) {
                $q->where('users.user_id', $request->lecturer_id);
            });
        }

        // Filter: Trạng thái (Hoạt động / Đã khóa)
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active' ? 1 : 0);
        }

        $classes = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        $subjects = Subject::orderBy('subject_name')->get();
        $lecturers = User::where('role', 'lecturer')->orderBy('name')->get();

        return view('admin.classes.index', compact('classes', 'subjects', 'lecturers'));
    }

    public function create()
    {
        $subjects = Subject::orderBy('subject_name')->get();
        $lecturers = User::where('role', 'lecturer')->orderBy('name')->get();
        return view('admin.classes.create', compact('subjects', 'lecturers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_name'   => 'required|string|max:255|unique:class_sections,class_name',
            'subject_id'   => 'required|exists:subjects,subject_id',
           
            'lecturer_id'  => 'nullable|exists:users,user_id',
        ], [
            'class_name.required' => 'Vui lòng nhập tên lớp học phần.',
            'class_name.unique'   => 'Tên lớp học phần đã tồn tại, vui lòng chọn tên khác!',
            'subject_id.required' => 'Vui lòng chọn môn học cho lớp.',
        ]);

        try {
      
            $classData = collect($validated)->except('lecturer_id')->toArray();
            $class = ClassSection::create($classData);


            if ($request->filled('lecturer_id')) {
               
                $lecturer = User::find($request->lecturer_id);
                if ($lecturer && $lecturer->role === 'lecturer') {
                    $class->users()->attach($lecturer->user_id);
                }
            }

            return redirect()->route('admin.classes.index')->with('success', 'Tạo lớp thành công!');
        } catch (\Exception $e) {
            Log::error('Error creating class: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Có lỗi xảy ra.');
        }
    }

   /**
     * Form chỉnh sửa lớp
     */
    public function edit($id)
    {
        // Load quan hệ lecturers để view biết ai đang dạy
        $class = ClassSection::with('lecturers')->findOrFail($id);
        
        $subjects = Subject::orderBy('subject_name')->get();
        $lecturers = User::where('role', 'lecturer')->orderBy('name')->get();

        return view('admin.classes.edit', compact('class', 'subjects', 'lecturers'));
    }

    /**
     * Cập nhật lớp
     */
    public function update(Request $request, $id)
    {
        $class = ClassSection::findOrFail($id);

        $validated = $request->validate([
            'class_name'   => ['required', 'string', 'max:255', Rule::unique('class_sections', 'class_name')->ignore($id, 'class_id')],
            'subject_id'   => 'required|exists:subjects,subject_id',
           
            'lecturer_id'  => 'nullable|exists:users,user_id',
        ]);

        try {
            // 1. Cập nhật thông tin cơ bản (loại bỏ lecturer_id khỏi mảng update)
            $classData = collect($validated)->except('lecturer_id')->toArray();
            $class->update($classData);

            
            $currentLecturerIds = $class->lecturers()->pluck('users.user_id');
            if ($currentLecturerIds->isNotEmpty()) {
                $class->users()->detach($currentLecturerIds);
            }

          
            if ($request->filled('lecturer_id')) {
                $lecturer = User::find($request->lecturer_id);
                // Kiểm tra kỹ lại role để tránh gán nhầm sinh viên làm giảng viên
                if ($lecturer && $lecturer->role === 'lecturer') {
                    $class->users()->attach($lecturer->user_id);
                }
            }

            return redirect()->route('admin.classes.index')
                ->with('success', 'Cập nhật thông tin lớp thành công!');
                
        } catch (\Exception $e) {
            Log::error('Error updating class: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Có lỗi xảy ra khi cập nhật.');
        }
    }

    public function destroy($id)
    {
        $class = ClassSection::findOrFail($id);

        
        $totalUsers = $class->users()->count();
        $totalLecturers = $class->lecturers()->count();
        $studentCount = $totalUsers - $totalLecturers;

        if ($studentCount > 0) {
            return back()->with('error', 'Lớp đang có sinh viên tham gia, không thể xóa!');
        }

        if ($class->groups()->exists()) {
            return back()->with('error', 'Lớp đã có nhóm hoạt động, không thể xóa!');
        }

        try {
            // Detach tất cả user (bao gồm giảng viên) trước khi xóa lớp để sạch bảng pivot
            $class->users()->detach();
            $class->delete();
            
            return redirect()->route('admin.classes.index')->with('success', 'Xóa lớp thành công!');
        } catch (\Exception $e) {
            Log::error('Error deleting class: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra.');
        }
    }

    /**
     * Khóa / Mở khóa lớp học
     */
    public function toggleActive($id)
    {
        $class = ClassSection::findOrFail($id);

        $class->update(['is_active' => !$class->is_active]);

        $status = $class->is_active ? 'mở khóa' : 'khóa';
        return back()->with('success', "Đã {$status} lớp {$class->class_name}!");
    }

    /**
     * Form tạo lớp học phần dành cho GIẢNG VIÊN.
     * Một môn học có thể có nhiều lớp học phần.
     */
    public function lecturerCreate()
    {
        $user = Auth::user();

        // Ưu tiên chọn môn học giảng viên phụ trách
        $subjects = Subject::where('lecturer_id', $user->user_id)
            ->orderBy('subject_name')
            ->get();

        // Nếu chưa có môn nào được phân công -> cho chọn tất cả môn
        if ($subjects->isEmpty()) {
            $subjects = Subject::orderBy('subject_name')->get();
        }

        return view('lecturer.classes.create', compact('subjects'));
    }

    /**
     * Lưu lớp học phần mới dành cho GIẢNG VIÊN.
     */
    public function lecturerStore(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'lecturer') {
            abort(403, 'Chỉ giảng viên mới được tạo lớp học phần!');
        }

        $validated = $request->validate([
            'class_name' => 'required|string|max:255|unique:class_sections,class_name',
            'class_code' => 'required|string|max:50|unique:class_sections,class_code',
            'subject_id' => 'required|exists:subjects,subject_id',
        ], [
            'class_name.required' => 'Vui lòng nhập tên lớp học phần.',
            'class_name.unique'   => 'Tên lớp học phần đã tồn tại, vui lòng chọn tên khác!',
            'class_code.required' => 'Vui lòng nhập mã lớp.',
            'class_code.unique'   => 'Mã lớp đã tồn tại, vui lòng chọn mã khác!',
            'subject_id.required' => 'Vui lòng chọn môn học cho lớp.',
        ]);

        // Giảng viên không được tạo lớp cho môn của giảng viên khác
        $subject = Subject::find($validated['subject_id']);
        if ($subject->lecturer_id && $subject->lecturer_id !== $user->user_id) {
            return back()->withInput()->with('error', 'Bạn chỉ có thể tạo lớp cho môn học mình phụ trách!');
        }

        try {
            $class = ClassSection::create([
                'class_name' => $validated['class_name'],
                'class_code' => $validated['class_code'],
                'subject_id' => $validated['subject_id'],
                'is_active'  => true,
            ]);

            // Gán giảng viên tạo lớp làm giảng viên phụ trách lớp
            $class->users()->attach($user->user_id);

            return redirect()->route('lecturer.classes.create')
                ->with('success', 'Tạo lớp học phần "' . $class->class_name . '" thành công! Sinh viên tham gia bằng mã lớp: ' . $class->class_code);
        } catch (\Exception $e) {
            Log::error('Error creating class by lecturer: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Có lỗi xảy ra khi tạo lớp.');
        }
    }
}

