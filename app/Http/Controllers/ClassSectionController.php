<?php

namespace App\Http\Controllers;

use App\Models\ClassSection;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
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
}