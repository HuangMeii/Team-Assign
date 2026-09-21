<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Services\SubjectCodeService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use App\Imports\SubjectsImport;

class SubjectController extends Controller
{
        /**
     * Form import môn học từ Excel/CSV
     */
    public function importForm()
    {
        return view('admin.subjects.import');
    }

    /**
     * Xử lý import môn học từ file Excel/CSV.
     * File chỉ có 2 cột: ten_mon, so_tc. Mã môn LUÔN tự sinh.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file.required' => 'Vui lòng chọn file để import.',
            'file.mimes'    => 'Chỉ chấp nhận file Excel (.xlsx, .xls) hoặc CSV.',
        ]);

        $import = new SubjectsImport;

        try {
            Excel::import($import, $request->file('file'));
        } catch (ValidationException $e) {
            $messages = [];
            foreach ($e->failures() as $failure) {
                $messages[] = 'Dòng ' . $failure->row() . ': ' . implode(', ', $failure->errors());
            }

            return redirect()->route('admin.subjects.import.form')
                ->with('error', 'Import thất bại (' . count($messages) . ' dòng lỗi): ' . implode(' | ', array_slice($messages, 0, 5)));
        } catch (\Exception $e) {
            return redirect()->route('admin.subjects.import.form')
                ->with('error', 'Import thất bại: ' . $e->getMessage());
        }

        $stats = $import->getStats();
        $failed = count($import->failures());

        return redirect()->route('admin.subjects.index')
            ->with('success', "Import môn học thành công! Thêm mới: {$stats['created']}, Cập nhật: {$stats['updated']}" . ($failed > 0 ? ", Lỗi: {$failed}" : ''));
    }

    /**
     * Tải file mẫu Excel
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="mau_mon_hoc.csv"',
        ];

        $content = "ten_mon,so_tc\n";
        $content .= "Lập trình Web,3\n";
        $content .= "Cơ sở dữ liệu,3\n";

        return response($content, 200, $headers);
    }

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
     * Lưu môn học.
     * Mã môn học LUÔN được hệ thống tự sinh (admin không nhập tay).
     * Phân công giảng viên thực hiện ở cấp lớp học phần (admin/lớp học).
     * Bugfix B4 [R30]: thêm validation credits.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_name' => 'required|string|max:255',
            'credits'      => 'required|integer|min:1|max:10',
        ], [
            'subject_name.required' => 'Vui lòng nhập tên môn học.',
            'credits.required'      => 'Vui lòng nhập số tín chỉ.',
            'credits.integer'       => 'Số tín chỉ phải là số nguyên.',
            'credits.min'           => 'Số tín chỉ tối thiểu là 1.',
            'credits.max'           => 'Số tối đa là 10.',
        ]);

        // Mã môn học luôn tự sinh (bỏ qua mọi giá trị client gửi lên)
        $validated['subject_code'] = SubjectCodeService::generate($validated['subject_name']);

        Subject::create($validated);

        return redirect()->route('admin.subjects.index')->with('success', 'Thêm môn học thành công! Mã môn: ' . $validated['subject_code']);
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
     * Bugfix B6 [R31]: Không cho sửa mã môn học.
     */
    public function update(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        $validated = $request->validate([
            'subject_name' => 'required|string|max:255',
            'credits'      => 'required|integer|min:1|max:10',
        ], [
            'subject_name.required' => 'Vui lòng nhập tên môn học.',
            'credits.required'      => 'Vui lòng nhập số tín chỉ.',
            'credits.integer'       => 'Số tín chỉ phải là số nguyên.',
            'credits.min'           => 'Số tín chỉ tối thiểu là 1.',
            'credits.max'           => 'Số tối đa là 10.',
        ]);

        $subject->update([
            'subject_name' => $validated['subject_name'],
            'credits' => $validated['credits'],
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