<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\ClassSection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    /**
     * Danh sách môn học
     */
    public function index(Request $request)
    {
        $query = Subject::with('lecturer')->withCount('classes'); // Đếm xem môn này có bao nhiêu lớp

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
        $lecturers = User::where('role', 'lecturer')->orderBy('name')->get();

        return view('admin.subjects.create', compact('lecturers'));
    }

    /**
     * Lưu môn học (một giảng viên có thể phụ trách nhiều môn)
     * Bugfix B4 [R30]: Tự sinh subject_code nếu không nhập; thêm validation credits.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_code' => 'nullable|string|max:50|unique:subjects,subject_code',
            'subject_name' => 'required|string|max:255',
            'credits'      => 'required|integer|min:1|max:10',
            'lecturer_id'  => 'nullable|exists:users,user_id',
        ], [
            'subject_code.unique'   => 'Mã môn học này đã tồn tại.',
            'subject_name.required' => 'Vui lòng nhập tên môn học.',
            'credits.required'      => 'Vui lòng nhập số tín chỉ.',
            'credits.integer'       => 'Số tín chỉ phải là số nguyên.',
            'credits.min'           => 'Số tín chỉ tối thiểu là 1.',
            'credits.max'           => 'Số tối đa là 10.',
        ]);

        // Tự sinh mã môn học nếu không nhập (B4)
        if (empty($validated['subject_code'])) {
            $validated['subject_code'] = $this->generateSubjectCode($validated['subject_name']);
        }

        Subject::create($validated);

        return redirect()->route('admin.subjects.index')->with('success', 'Thêm môn học thành công! Mã môn: ' . $validated['subject_code']);
    }

    /**
     * Tự sinh mã môn học duy nhất từ tên môn học.
     * Format: Viết tắt tên môn + số thứ tự (ví dụ: "Lập trình Web" → LTW001)
     */
    private function generateSubjectCode(string $subjectName): string
    {
        // Tạo viết tắt từ chữ cái đầu mỗi từ
        $words = preg_split('/\s+/', trim($subjectName));
        $prefix = '';
        foreach ($words as $word) {
            if (strlen($word) > 0) {
                $prefix .= mb_strtoupper(mb_substr($word, 0, 1), 'UTF-8');
            }
        }
        // Giới hạn prefix tối đa 5 ký tự
        $prefix = substr($prefix, 0, 5);
        if (empty($prefix)) {
            $prefix = 'SUB';
        }

        // Tìm số thứ tự tiếp theo
        $lastSubject = Subject::where('subject_code', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(subject_code, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
            ->first();

        $nextNumber = 1;
        if ($lastSubject) {
            $existingNumber = (int) substr($lastSubject->subject_code, strlen($prefix));
            $nextNumber = $existingNumber + 1;
        }

        return $prefix . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Form sửa môn học
     */
    public function edit($id)
    {
        $subject = Subject::findOrFail($id);
        $lecturers = User::where('role', 'lecturer')->orderBy('name')->get();

        return view('admin.subjects.edit', compact('subject', 'lecturers'));
    }

    /**
     * Cập nhật môn học
     * Bugfix B6 [R31]: Không cho sửa mã môn học.
     */
    public function update(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        $validated = $request->validate([
            'subject_name' => 'required|string|max:255',
            'credits'      => 'required|integer|min:1|max:10',
            'lecturer_id'  => 'nullable|exists:users,user_id',
        ], [
            'subject_name.required' => 'Vui lòng nhập tên môn học.',
            'credits.required'      => 'Vui lòng nhập số tín chỉ.',
            'credits.integer'       => 'Số tín chỉ phải là số nguyên.',
            'credits.min'           => 'Số tín chỉ tối thiểu là 1.',
            'credits.max'           => 'Số tối đa là 10.',
        ]);

        $subject->update([
            'subject_name' => $validated['subject_name'],
            'credits'      => $validated['credits'],
            'lecturer_id'  => $validated['lecturer_id'],
        ]);

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