<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\ClassSection;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    /**
     * Danh sách môn học
     */
    public function index(Request $request)
    {
        $query = Subject::withCount('classes'); // Đếm xem môn này có bao nhiêu lớp

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('subject_name', 'like', "%{$search}%")
                  ->orWhere('subject_code', 'like', "%{$search}%");
            });
        }

        $subjects = $query->paginate(10)->withQueryString();

        return view('admin.subjects.index', compact('subjects'));
    }

    /**
     * Form thêm môn học
     */
    public function create()
    {
        return view('admin.subjects.create');
    }

    /**
     * Lưu môn học
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_code' => 'required|string|max:50|unique:subjects,subject_code',
            'subject_name' => 'required|string|max:255',
            
           
        ], [
            'subject_code.unique' => 'Mã môn học này đã tồn tại.',
        ]);

        Subject::create($validated);

        return redirect()->route('admin.subjects.index')->with('success', 'Thêm môn học thành công!');
    }

    /**
     * Form sửa môn học
     */
    public function edit($id)
    {
        $subject = Subject::findOrFail($id);
        return view('admin.subjects.edit', compact('subject'));
    }

    /**
     * Cập nhật môn học
     */
    public function update(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        $validated = $request->validate([
            'subject_code' => ['required', 'string', 'max:50', Rule::unique('subjects')->ignore($id, 'subject_id')],
            'subject_name' => 'required|string|max:255',
          
        ]);

        $subject->update($validated);

        return redirect()->route('admin.subjects.index')->with('success', 'Cập nhật môn học thành công!');
    }

    /**
     * Xóa môn học
     */
    public function destroy($id)
    {
        $subject = Subject::findOrFail($id);

        // Kiểm tra xem môn này đã có lớp nào mở chưa
        if ($subject->classes()->exists()) {
            return back()->with('error', 'Không thể xóa môn học đang có lớp học phần hoạt động!');
        }

        $subject->delete();

        return back()->with('success', 'Xóa môn học thành công!');
    }
}