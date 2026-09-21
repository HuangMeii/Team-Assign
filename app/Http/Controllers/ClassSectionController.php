<?php

namespace App\Http\Controllers;

use App\Models\ClassSection;
use App\Models\Groups;
use App\Models\Subject;
use App\Models\User;
use App\Services\GroupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ClassSectionController extends Controller
{
    protected $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    public function index(Request $request)
    {
       
        $query = ClassSection::with(['subject', 'lecturers'])
                             ->withCount(['students', 'groups']);

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

        // Bugfix C6 [R24]: Bộ lọc multi-select (môn học / giảng viên / trạng thái).
        // Vẫn chấp nhận tham số ĐƠN (subject_id, lecturer_id, status) để link cũ không hỏng.
        $subjectIds = array_values(array_filter((array) $request->input('subject_ids', []), fn ($v) => $v !== '' && $v !== null));
        if (empty($subjectIds) && $request->filled('subject_id')) {
            $subjectIds = [$request->subject_id];
        }
        if (! empty($subjectIds)) {
            $query->whereIn('subject_id', $subjectIds);
        }

        $lecturerIds = array_values(array_filter((array) $request->input('lecturer_ids', []), fn ($v) => $v !== '' && $v !== null));
        if (empty($lecturerIds) && $request->filled('lecturer_id')) {
            $lecturerIds = [$request->lecturer_id];
        }
        if (! empty($lecturerIds)) {
            $query->whereHas('lecturers', function ($q) use ($lecturerIds) {
                $q->whereIn('users.user_id', $lecturerIds);
            });
        }

        // Trạng thái: nhiều lựa chọn -> is_active thuộc {1, 0}
        $statuses = array_values(array_filter((array) $request->input('statuses', []), fn ($v) => $v !== '' && $v !== null));
        if (empty($statuses) && $request->filled('status')) {
            $statuses = [$request->status];
        }
        if (! empty($statuses)) {
            $activeValues = [];
            foreach ($statuses as $status) {
                if ($status === 'active') {
                    $activeValues[] = 1;
                } elseif ($status === 'locked') {
                    $activeValues[] = 0;
                }
            }
            if (! empty($activeValues)) {
                $query->whereIn('is_active', array_unique($activeValues));
            }
        }

        $classes = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        $subjects = Subject::orderBy('subject_name')->get();
        $lecturers = User::where('role', 'lecturer')->orderBy('name')->get();

        // Bugfix C6 [R24]: Multi-select filter (được xử lý trong view)
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
            // Bugfix B5 [R28]: Tự sinh class_code cho lớp tạo bởi admin
            $subject = Subject::find($validated['subject_id']);
            $classCode = $this->generateClassCode($subject);

            $classData = array_merge(
                collect($validated)->except('lecturer_id')->toArray(),
                [
                    'class_code' => $classCode,
                    'is_active'  => true,
                ]
            );
            $class = ClassSection::create($classData);

            if ($request->filled('lecturer_id')) {
                $lecturer = User::find($request->lecturer_id);
                if ($lecturer && $lecturer->role === 'lecturer') {
                    $class->users()->attach($lecturer->user_id);
                }
            }

        return redirect()->route('admin.classes.index')
                ->with('success', 'Tạo lớp thành công! Mã lớp: ' . $classCode . ' — Gửi mã này cho sinh viên để tham gia.');
        } catch (\Exception $e) {
            Log::error('Error creating class: ' . $e->getMessage());
        return back()->withInput()->with('error', 'Có lỗi xảy ra.');
        }
    }

    /**
     * Bugfix B5 [R28]: Tự sinh mã lớp duy nhất từ mã môn học + số thứ tự.
     */
    private function generateClassCode(Subject $subject): string
    {
        $prefix = $subject->subject_code;
        $lastClass = ClassSection::where('class_code', 'like', $prefix . '-%')
            ->orderByRaw('CAST(SUBSTRING(class_code, -2) AS UNSIGNED) DESC')
            ->first();

        $nextNumber = 1;
        if ($lastClass) {
            $parts = explode('-', $lastClass->class_code);
            $lastPart = end($parts);
            if (is_numeric($lastPart)) {
                $nextNumber = (int) $lastPart + 1;
            }
        }

        return $prefix . '-' . str_pad((string) $nextNumber, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Chi tiet lop hoc phan: danh sach sinh vien, nhom, thay doi giang vien.
     */
    public function show($id)
    {
        $class = ClassSection::with(['subject', 'lecturers'])
            ->withCount(['students', 'groups'])
            ->findOrFail($id);

        // Eager-load nhóm của sinh viên (cả nhóm tham gia lẫn nhóm lãnh đạo) để cột
        // "Nhóm" ở tab danh sách sinh viên hiển thị đúng và tránh truy vấn N+1.
        $students = $class->students()
            ->with(['groupsJoined', 'groupsLed'])
            ->orderBy('name')
            ->get();

        $groups = Groups::where('class_id', $id)
            ->with(['leader', 'members'])
            ->get();

        $availableStudents = User::where('role', 'student')
            ->whereDoesntHave('classes', function ($q) use ($id) {
                $q->where('class_sections.class_id', $id);
            })
            ->orderBy('name')
            ->get();

        $lecturers = User::where('role', 'lecturer')
            ->orderBy('name')
            ->get();

        return view('admin.classes.show', compact(
            'class', 'students', 'groups', 'availableStudents', 'lecturers'
        ));
    }

    /**
     * Thêm sinh viên vào lớp học phần.
     */
    public function addStudents(Request $request, $classId)
    {
        $class = ClassSection::findOrFail($classId);

        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:users,user_id',
        ], [
            'student_ids.required' => 'Vui lòng chọn ít nhất một sinh viên.',
            'student_ids.min' => 'Vui lòng chọn ít nhất một sinh viên.',
        ]);

        try {
            // Chỉ nhận tài khoản có vai trò sinh viên (bỏ qua giảng viên/admin nếu bị gửi lên)
            $studentIds = User::where('role', 'student')
                ->whereIn('user_id', $validated['student_ids'])
                ->pluck('user_id')
                ->all();

            // Sinh viên đã có trong lớp -> không thêm trùng
            $existingIds = $class->students()->pluck('users.user_id')->all();
            $newIds = array_values(array_diff($studentIds, $existingIds));

            if (empty($newIds)) {
                return back()->with('warning', 'Sinh viên được chọn đã có trong lớp học phần này.');
            }

            // syncWithoutDetaching: thêm mới, không xóa sinh viên/giảng viên hiện có
            $attachedCount = count($class->students()->syncWithoutDetaching($newIds)['attached']);

            $skippedCount = count($validated['student_ids']) - $attachedCount;
            $message = "Đã thêm {$attachedCount} sinh viên vào lớp \"{$class->class_name}\" thành công!";
            if ($skippedCount > 0) {
                $message .= " ({$skippedCount} sinh viên đã có trong lớp, bỏ qua)";
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Error adding students to class: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi thêm sinh viên.');
        }
    }

    /**
     * Xóa sinh viên khỏi lớp học phần (kèm cleanup dữ liệu nhóm - Bugfix B3 [R13]).
     */
    public function removeStudent($classId, $studentId)
    {
        $class = ClassSection::findOrFail($classId);
        $student = User::where('role', 'student')->findOrFail($studentId);

        if ($class->lecturers->contains('user_id', (int) $studentId)) {
            return back()->with('error', 'Không thể xóa giảng viên khỏi lớp. Vui lòng dùng chức năng đổi giảng viên!');
        }

        if (! $class->students()->where('users.user_id', $studentId)->exists()) {
            return back()->with('warning', 'Sinh viên này không thuộc lớp học phần.');
        }

        try {
            $this->groupService->removeUserFromClassGroups($student, $classId);
            $class->students()->detach($studentId);

            return back()->with('success', 'Đã xóa sinh viên "' . $student->name . '" khỏi lớp học phần thành công!');
        } catch (\Exception $e) {
            Log::error('Error removing student from class: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi xóa sinh viên.');
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

        
        // Chỉ chặn xóa khi lớp còn sinh viên (giảng viên phụ trách không tính là sinh viên)
        $studentCount = $class->students()->count();

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
     * Một môn học có thể có nhiều lớp học phần; phân công giảng viên ở cấp lớp.
     */
    public function lecturerCreate()
    {
        $subjects = Subject::orderBy('subject_name')->get();

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
            'subject_id' => 'required|exists:subjects,subject_id',
        ], [
            'class_name.required' => 'Vui lòng nhập tên lớp học phần.',
            'class_name.unique'   => 'Tên lớp học phần đã tồn tại, vui lòng chọn tên khác!',
            'subject_id.required' => 'Vui lòng chọn môn học cho lớp.',
        ]);

        // Giảng viên tạo lớp cho bất kỳ môn học nào; sau khi tạo,
        // giảng viên đó trở thành giảng viên quản lý duy nhất của lớp.
        $subject = Subject::find($validated['subject_id']);
        if (!$subject) {
            return back()->withInput()->with('error', 'Môn học không tồn tại!');
        }

        try {
            $class = ClassSection::create([
                'class_name' => $validated['class_name'],
                'class_code' => $this->generateUniqueClassCode(),
                'subject_id' => $validated['subject_id'],
                'is_active'  => true,
            ]);

            // Gán giảng viên tạo lớp làm giảng viên phụ trách lớp
            $class->users()->attach($user->user_id);

        return redirect()->route('lecturer.classes.index')
                ->with('success', 'Tạo lớp học phần "' . $class->class_name . '" thành công! Sinh viên tham gia bằng mã lớp: ' . $class->class_code);
        } catch (\Exception $e) {
            Log::error('Error creating class by lecturer: ' . $e->getMessage());
        return back()->withInput()->with('error', 'Có lỗi xảy ra khi tạo lớp.');
        }
    }

    /**
     * Sinh mã lớp ngẫu nhiên gồm 5 ký tự (chữ hoa và số), đảm bảo duy nhất.
     * Bỏ các ký tự dễ nhầm lẫn: I, O, 0, 1.
     */
    private function generateUniqueClassCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = '';
            for ($i = 0; $i < 5; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (ClassSection::where('class_code', $code)->exists());

        return $code;
    }

    /* ================================================================
     |  QUẢN LÝ LỚP HỌC PHẦN — GIẢNG VIÊN
     |  Giảng viên chỉ thao tác trên các lớp mình được phân công phụ trách.
     ================================================================ */

    /**
     * Kiểm tra giảng viên hiện tại có được phân công phụ trách lớp này không.
     * Trả về redirect response nếu KHÔNG hợp lệ, null nếu hợp lệ.
     */
    private function denyIfNotOwnClass(ClassSection $class)
    {
        $user = Auth::user();

        if ($user->role !== 'lecturer' || !$user->classes->contains('class_id', $class->class_id)) {
            return redirect()->route('lecturer.classes.index')
                ->with('error', 'Bạn không phụ trách lớp học phần này!');
        }

        return null;
    }

    /**
     * Danh sách lớp học phần giảng viên đang phụ trách.
     */
    public function lecturerClassesIndex(Request $request)
    {
        $user = Auth::user();

        $query = ClassSection::whereIn('class_id', $user->classes->pluck('class_id'))
            ->with('subject')
            ->withCount(['students', 'groups']);

        // Tìm kiếm theo tên lớp / mã lớp / tên môn học
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('class_name', 'like', "%{$search}%")
                  ->orWhere('class_code', 'like', "%{$search}%")
                  ->orWhereHas('subject', function ($s) use ($search) {
                      $s->where('subject_name', 'like', "%{$search}%");
                  });
            });
        }

        // Lọc trạng thái lớp
        if ($request->filled('status') && in_array($request->status, ['active', 'locked'])) {
            $query->where('is_active', $request->status === 'active' ? 1 : 0);
        }

        $classes = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return view('lecturer.classes.index', compact('classes'));
    }

    /**
     * Chi tiết lớp học phần của giảng viên: thông tin lớp, mã lớp tham gia,
     * danh sách sinh viên (thêm/xóa) và danh sách nhóm.
     */
    public function lecturerClassesShow($id)
    {
        $class = ClassSection::with(['subject', 'lecturers'])
            ->withCount(['students', 'groups'])
            ->findOrFail($id);

        if ($deny = $this->denyIfNotOwnClass($class)) {
            return $deny;
        }

        // Eager-load nhóm của sinh viên (cả nhóm tham gia lẫn nhóm lãnh đạo)
        $students = $class->students()
            ->with(['groupsJoined', 'groupsLed'])
            ->orderBy('name')
            ->get();

        // Sinh viên chưa thuộc lớp này (danh sách để thêm nhanh)
        $availableStudents = User::where('role', 'student')
            ->whereDoesntHave('classes', function ($q) use ($id) {
                $q->where('class_sections.class_id', $id);
            })
            ->orderBy('name')
            ->get();

        $groups = Groups::where('class_id', $id)
            ->with(['leader', 'members'])
            ->orderBy('created_at')
            ->get();

        return view('lecturer.classes.show', compact('class', 'students', 'availableStudents', 'groups'));
    }

    /**
     * Giảng viên thêm sinh viên vào lớp mình phụ trách.
     */
    public function lecturerClassesAddStudents(Request $request, $classId)
    {
        $class = ClassSection::findOrFail($classId);

        if ($deny = $this->denyIfNotOwnClass($class)) {
            return $deny;
        }

        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:users,user_id',
        ], [
            'student_ids.required' => 'Vui lòng chọn ít nhất một sinh viên.',
            'student_ids.min' => 'Vui lòng chọn ít nhất một sinh viên.',
        ]);

        try {
            // Chỉ nhận tài khoản có vai trò sinh viên (bỏ qua giảng viên/admin nếu bị gửi lên)
            $studentIds = User::where('role', 'student')
                ->whereIn('user_id', $validated['student_ids'])
                ->pluck('user_id')
                ->all();

            // Sinh viên đã có trong lớp -> không thêm trùng
            $existingIds = $class->students()->pluck('users.user_id')->all();
            $newIds = array_values(array_diff($studentIds, $existingIds));

            if (empty($newIds)) {
                return back()->with('warning', 'Sinh viên được chọn đã có trong lớp học phần này.');
            }

            // syncWithoutDetaching: thêm mới, không xóa dữ liệu hiện có
            $attachedCount = count($class->students()->syncWithoutDetaching($newIds)['attached']);

            $skippedCount = count($validated['student_ids']) - $attachedCount;
            $message = "Đã thêm {$attachedCount} sinh viên vào lớp \"{$class->class_name}\" thành công!";
            if ($skippedCount > 0) {
                $message .= " ({$skippedCount} sinh viên đã có trong lớp, bỏ qua)";
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Lecturer adding students to class: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi thêm sinh viên.');
        }
    }

    /**
     * Giảng viên xóa sinh viên khỏi lớp mình phụ trách
     * (kèm cleanup dữ liệu nhóm — giống admin, Bugfix B3 [R13]).
     */
    public function lecturerClassesRemoveStudent($classId, $studentId)
    {
        $class = ClassSection::findOrFail($classId);

        if ($deny = $this->denyIfNotOwnClass($class)) {
            return $deny;
        }

        $student = User::where('role', 'student')->findOrFail($studentId);

        if (! $class->students()->where('users.user_id', $studentId)->exists()) {
            return back()->with('warning', 'Sinh viên này không thuộc lớp học phần.');
        }

        try {
            // Dọn nhóm trong lớp: rút khỏi nhóm, chuyển quyền trưởng nhóm, hủy lời mời/đăng ký chờ
            $this->groupService->removeUserFromClassGroups($student, $classId);
            $class->students()->detach($studentId);

            return back()->with('success', 'Đã xóa sinh viên "' . $student->name . '" khỏi lớp học phần thành công!');
        } catch (\Exception $e) {
            Log::error('Lecturer removing student from class: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi xóa sinh viên.');
        }
    }

    /**
     * Giảng viên khóa / mở khóa lớp mình phụ trách
     * (lớp bị khóa thì sinh viên không thể tham gia bằng mã lớp).
     */
    public function lecturerClassesToggleActive($id)
    {
        $class = ClassSection::findOrFail($id);

        if ($deny = $this->denyIfNotOwnClass($class)) {
            return $deny;
        }

        $class->update(['is_active' => ! $class->is_active]);

        $status = $class->is_active ? 'mở khóa' : 'khóa';
        return back()->with('success', "Đã {$status} lớp {$class->class_name}!");
    }
}

