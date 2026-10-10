<?php

namespace App\Http\Controllers;

use App\Models\Topics;
use App\Models\ClassSection;
use App\Models\Subject;
use App\Imports\TopicsHeadingRowImport;
use App\Imports\TopicsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class TopicController extends Controller
{
    /**
     * Display a listing of the topics.
     */
    /**
     * Loại báo cáo hiệu lực của đề tài:
     * môn 1 bài (subjects.report_count = 1) CHỈ có đồ án cuối kì ⇒ luôn trả về final.
     */
    private function resolveReportType(?int $subjectId, ?string $requested): string
    {
        $reportCount = (int) (Subject::find($subjectId)?->report_count ?? 1);

        return ($reportCount === 2 && $requested === Topics::REPORT_MIDTERM)
            ? Topics::REPORT_MIDTERM
            : Topics::REPORT_FINAL;
    }

  public function index(Request $request)
    {
        $user = Auth::user();


        if ($user->role == 'student') {
            return abort(403, 'Bạn không có quyền truy cập quản lý đề tài.');
        }

     
        $query = Topics::with(['class.subject', 'topic_requests']);

    
        if ($user->role === 'lecturer') {
            $classIds = $user->classes->pluck('class_id');
          
        $query->whereIn('class_id', $classIds);
            
         
            $classes = $user->classes;

        } elseif ($user->role === 'admin') {
           
            $classes = ClassSection::with('subject')->get();
        }

        
        
        // Filter theo lớp
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        
        // Filter theo trạng thái đăng ký
        if ($request->filled('status')) {
            if ($request->status === 'available') {
                $query->whereNull('assigned_group_id');
            } elseif ($request->status === 'assigned') {
                $query->whereNotNull('assigned_group_id');
            }
        }
        
        // Search theo tên đề tài
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        // 5. Thực hiện truy vấn và phân trang
        $topics = $query->orderBy('created_at', 'desc') 
                        ->paginate(10)
                        ->withQueryString();

        // 6. Panel gợi ý đề tài: môn học của các lớp người dùng quản lý
        //    (giảng viên: lớp mình phụ trách — admin: toàn bộ môn đang có lớp học phần)
        $subjects = Subject::whereIn('subject_id', $classes->pluck('subject_id')->unique()->filter())
            ->orderBy('subject_name')
            ->get();

        return view('topics.index', compact('topics', 'classes', 'subjects'));
    }
    /**
     * Show the form for creating a new topic.
     */
    public function create()
    {
        $user = Auth::user();
        
        // Lấy lớp học phần theo vai trò:
        // - Admin: toàn bộ lớp học phần
        // - Lecturer: chỉ các lớp mình đang phụ trách
        if ($user->role === 'admin') {
            $classes = ClassSection::with('subject')->get();
        } else {
            $classes = $user->classes->load('subject');
        }
        
        return view('topics.create', compact('classes'));
    }

    /**
     * Store a newly created topic in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role=='student') return  abort(403, 'Bạn không có quyền ');
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:topics',
            'description' => 'required|string|min:10',
            'goal' => 'nullable|string',
            'requirements' => 'nullable|string',
            'class_id' => 'required|exists:class_sections,class_id',
            'min_members' => 'nullable|integer|min:1',
            'max_members' => 'nullable|integer|min:1',
            'registration_deadline' => 'nullable|date',
            'report_type' => 'nullable|in:final,midterm',
        ]);

        // Việc 3 (2026-10-10): `registration_deadline` là GIỜ NGƯỜI NHẬP ⇒ hiểu theo múi giờ
        // người nhập rồi lưu UTC (hiển thị/prefill đổi ngược bằng displayTz()).
        if (! empty($validated['registration_deadline'])) {
            $validated['registration_deadline'] = \App\Support\DisplayTime::toUtc($validated['registration_deadline']);
        }


        // Kiểm tra quyền theo vai trò:
        // - Lecturer: chỉ được tạo đề tài cho lớp mình phụ trách
        // - Admin: được tạo đề tài cho mọi lớp
        if ($user->role !== 'admin' && !$user->classes->pluck('class_id')->contains($request->class_id)) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Bạn không có quyền tạo đề tài cho lớp này!');
        }

        // Lấy thông tin lớp (kèm danh sách giảng viên phụ trách)
        $class = ClassSection::with('lecturers')->find($request->class_id);

        // Lecturer: lấy tên giảng viên đang đăng nhập
        // Admin: lấy giảng viên phụ trách lớp (tránh ghi nhầm tên admin làm giảng viên)
        $validated['lecturer'] = $user->role === 'admin'
            ? ($class->lecturer?->name ?? $user->name)
            : $user->name;
        
        // Lấy subject_id từ class
        $validated['subject_id'] = $class->subject_id;

        // Loại báo cáo: môn 1 bài (report_count = 1) chỉ có đồ án cuối kì ⇒ ép về final
        $validated['report_type'] = $this->resolveReportType((int) $class->subject_id, $validated['report_type'] ?? null);

        Topics::create($validated);
        return redirect()->route('topics.index')->with('success', 'Thêm đề tài thành công!');
    }

    /**
     * Display the specified topic.
     */
    public function show(Topics $topic)
    {
        $user = Auth::user();
        
        // Kiểm tra quyền xem
        if ($user->role === 'lecturer' && $topic->lecturer !== $user->name) {
            abort(403, 'Bạn không có quyền xem đề tài này.');
        }
        
        $topic->load(['class.subject', 'topic_requests.group', 'assignedGroup']);
        return view('topics.show', compact('topic'));
    }

    /**
     * Show the form for editing the specified topic.
     */
    public function edit(Topics $topic)
    {
        $user = Auth::user();
        
        // Kiểm tra quyền sửa
        if ($user->role === 'lecturer' && $topic->lecturer !== $user->name) {
            abort(403, 'Bạn không có quyền chỉnh sửa đề tài này.');
        }
        
        // Sau khi có sinh viên/nhóm đăng ký đề tài (đang chờ duyệt hoặc đã được duyệt) thì không cho chỉnh sửa nữa
        if ($topic->topic_requests()->whereIn('status', ['Pending', 'Accepted'])->exists()) {
            return redirect()->route('topics.show', $topic)
                           ->with('error', 'Đề tài đã có sinh viên đăng ký nên không thể chỉnh sửa!');
        }

        // Lấy lớp học phần theo vai trò:
        // - Admin: toàn bộ lớp học phần
        // - Lecturer: chỉ các lớp mình đang phụ trách
        if ($user->role === 'admin') {
            $classes = ClassSection::with('subject')->get();
        } else {
            $classes = $user->classes->load('subject');
        }
        
        return view('topics.edit', compact('topic', 'classes'));
    }

    /**
     * Update the specified topic in storage.
     */
    public function update(Request $request, Topics $topic)
    {
        $user = Auth::user();
        
        // Kiểm tra quyền sửa
        if ($user->role === 'lecturer' && $topic->lecturer !== $user->name) {
            abort(403, 'Bạn không có quyền chỉnh sửa đề tài này.');
        }
        
        // Sau khi có sinh viên/nhóm đăng ký đề tài (đang chờ duyệt hoặc đã được duyệt) thì không cho chỉnh sửa nữa
        if ($topic->topic_requests()->whereIn('status', ['Pending', 'Accepted'])->exists()) {
            return redirect()->route('topics.show', $topic)
                           ->with('error', 'Đề tài đã có sinh viên đăng ký nên không thể chỉnh sửa!');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:topics,name,' . $topic->topic_id . ',topic_id',
            'description' => 'required|string|min:10',
            'goal' => 'nullable|string',
            'requirements' => 'nullable|string',
            'min_members' => 'nullable|integer|min:1',
            'max_members' => 'nullable|integer|min:1',
            'registration_deadline' => 'nullable|date',
            'report_type' => 'nullable|in:final,midterm',
        ]);


        // Việc 3 (2026-10-10): hạn đăng ký là giờ NGƯỜI NHẬP ⇒ lưu UTC.
        if (! empty($validated['registration_deadline'])) {
            $validated['registration_deadline'] = \App\Support\DisplayTime::toUtc($validated['registration_deadline']);
        }


        // Không cho phép thay đổi lớp học phần: luôn giữ nguyên lớp học phần và môn học gốc của đề tài
        $validated['class_id'] = $topic->class_id;
        $validated['subject_id'] = $topic->subject_id;

        // Loại báo cáo: môn 1 bài chỉ có đồ án cuối kì ⇒ ép về final
        $validated['report_type'] = $this->resolveReportType((int) $topic->subject_id, $validated['report_type'] ?? null);

        // Lecturer không thay đổi
        $validated['lecturer'] = $topic->lecturer;
        $topic->update($validated);
        return redirect()->route('topics.index')->with('success', 'Cập nhật đề tài thành công!');
    }

    /**
     * Remove the specified topic from storage.
     */
    public function destroy(Topics $topic)
    {
        $user = Auth::user();
        
        // Kiểm tra quyền xóa
        if ($user->role === 'lecturer' && $topic->lecturer !== $user->name) {
            abort(403, 'Bạn không có quyền xóa đề tài này.');
        }
        
        // Check xem có group nào đang đăng ký không
        if ($topic->topic_requests()->where('status', 'pending')->exists()) {
            return redirect()->route('topics.index')
                           ->with('error', 'Không thể xóa đề tài đang có yêu cầu đăng ký!');
        }
        
        if ($topic->assigned_group_id) {
            return redirect()->route('topics.index')
                           ->with('error', 'Không thể xóa đề tài đã được gán cho nhóm!');
        }
        
        $topic->delete();
        return redirect()->route('topics.index')->with('success', 'Xóa đề tài thành công!');
    }
    
    /**
     * Form import đề tài từ Excel/CSV.
     * Sinh viên không có quyền; giảng viên chỉ thấy lớp mình phụ trách.
     */
    public function importForm()
    {
        $user = Auth::user();

        if ($user->role === 'student') {
            abort(403, 'Bạn không có quyền import đề tài.');
        }

        $classes = $user->role === 'admin'
            ? ClassSection::with('subject')->get()
            : $user->classes->load('subject');

        return view('topics.import', compact('classes'));
    }

    /**
     * Xử lý import đề tài từ file Excel/CSV.
     *
     * File gồm các cột: ten_de_tai, mo_ta, muc_tieu, yeu_cau, ma_lop, so_tv_min, so_tv_max, han_dang_ky
     * (xem App\Imports\TopicsImport). Tên đề tài trùng sẽ được BỎ QUA, không ghi đè.
     */
    public function import(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'student') {
            abort(403, 'Bạn không có quyền import đề tài.');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file.required' => 'Vui lòng chọn file để import.',
            'file.mimes'    => 'Chỉ chấp nhận file Excel (.xlsx, .xls) hoặc CSV.',
            'file.max'      => 'File tối đa 5MB.',
        ]);

        $file = $request->file('file');

        // Dấu phân cách CSV: Maatwebsite mặc định KHOÁ CỨNG dấu phẩy (dự án không có config/excel.php) nên
        // file CSV phân cách TAB (rất hay gặp khi sao chép từ Excel) bị đọc thành MỘT cột ⇒ mọi cột bắt buộc
        // rỗng. Tự dò dấu phân cách trước khi import.
        $delimiter = strtolower((string) $file->getClientOriginalExtension()) === 'csv'
            ? TopicsImport::detectCsvDelimiter((string) $file->getRealPath())
            : null;

        // Chẩn đoán SỚM dòng tiêu đề: file thiếu cột bắt buộc ⇒ báo MỘT lỗi dễ hiểu thay vì lặp lại
        // "Tên đề tài không được để trống" cho từng dòng (file 9 dòng ⇒ 27 dòng lỗi).
        $headings = $this->headingRowColumns($file, $delimiter);

        if ($headings !== null) {
            $missing = array_values(array_diff(TopicsImport::REQUIRED_HEADINGS, $headings));

            if ($missing !== []) {
                return redirect()->route('topics.import.form')
                    ->with('error', $this->headingRowErrorMessage($headings, $missing));
            }
        }

        $import = new TopicsImport($user, $delimiter);

        try {
            Excel::import($import, $file);
        } catch (ValidationException $e) {
            $messages = [];
            foreach ($e->failures() as $failure) {
                $messages[] = 'Dòng ' . $failure->row() . ': ' . implode(', ', $failure->errors());
            }

            return redirect()->route('topics.import.form')
                ->with('error', 'Import thất bại (' . count($messages) . ' dòng lỗi): ' . implode(' | ', array_slice($messages, 0, 5)));
        } catch (\Exception $e) {
            return redirect()->route('topics.import.form')
                ->with('error', 'Import thất bại: ' . $e->getMessage());
        }

        $stats = $import->getStats();
        $failures = $import->failures();
        $failed = count($failures);

        $message = "Import đề tài: thêm mới {$stats['created']}, bỏ qua {$stats['skipped']}"
            . ($failed > 0 ? ", dòng lỗi {$failed}" : '') . '.';

        // Liệt kê tối đa 5 dòng lỗi đầu tiên để người dùng sửa file
        if ($failed > 0) {
            $details = [];
            foreach ($failures as $failure) {
                $details[] = 'Dòng ' . $failure->row() . ': ' . implode(', ', $failure->errors());
            }

            $message .= ' ' . implode(' | ', array_slice($details, 0, 5));
        }

        // Không thêm được dòng nào mà lại có dòng lỗi -> quay lại form kèm lỗi chi tiết
        if ($failed > 0 && $stats['created'] === 0) {
            return redirect()->route('topics.import.form')->with('error', $message);
        }

        return redirect()->route('topics.index')->with('success', $message);
    }

    /**
     * Đọc dòng tiêu đề của file import (đã slug hoá) để CHẨN ĐOÁN SỚM.
     *
     * Trả về null khi không đọc được file ⇒ để TopicsImport báo lỗi chi tiết như trước.
     *
     * @return string[]|null
     */
    private function headingRowColumns($file, ?string $delimiter): ?array
    {
        try {
            $sheets = Excel::toArray(new TopicsHeadingRowImport(1, $delimiter), $file);
        } catch (\Throwable $e) {
            return null;
        }

        $headings = array_map('strval', array_values($sheets[0][0] ?? []));

        return array_values(array_filter($headings, fn ($heading) => $heading !== '' && ! ctype_digit($heading)));
    }

    /**
     * Thông báo lỗi thân thiện khi file không có dòng tiêu đề đúng (thiếu cột bắt buộc).
     *
     * @param  string[]  $headings  Các cột đọc được từ dòng 1.
     * @param  string[]  $missing  Các cột bắt buộc bị thiếu.
     */
    private function headingRowErrorMessage(array $headings, array $missing): string
    {
        $found = $headings === [] ? 'không đọc được cột nào' : implode(', ', array_slice($headings, 0, 8));

        return 'Không đọc được dòng tiêu đề của file. '
            . 'Cần dòng 1 (không có dòng trống phía trên) gồm các cột: ' . implode(', ', TopicsImport::HEADINGS) . '. '
            . 'Đã đọc được: ' . $found . '. '
            . 'Thiếu: ' . implode(', ', $missing) . '. '
            . 'Nếu file đang phân cách bằng TAB (sao chép từ Excel) hoặc dấu chấm phẩy, hãy lưu lại thành .xlsx rồi import lại.';
    }

    /**
     * Tải file mẫu CSV cho import đề tài.
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="mau_de_tai.csv"',
        ];

        $content = "ten_de_tai,mo_ta,muc_tieu,yeu_cau,ma_lop,so_tv_min,so_tv_max,han_dang_ky\n";
        $content .= "Xây dựng website quản lý thư viện,Đề tài làm website quản lý sách và độc giả cho thư viện trường,Hoàn thiện kỹ năng Laravel và MySQL,Biết PHP cơ bản,CNTT01-K1,2,4,2026-12-31\n";
        $content .= "Ứng dụng điểm danh bằng QR code,Đề tài xây dựng ứng dụng điểm danh sinh viên bằng mã QR,Hiểu quy trình điểm danh của lớp học,Biết lập trình web,CNTT01-K1,3,4,\n";

        return response($content, 200, $headers);
    }

    /**
     * Get topics by class (API endpoint for AJAX)
     */
    public function getByClass($classId)
    {
        $user = Auth::user();

        // Quyền truy cập:
        // - Lecturer: chỉ xem đề tài của lớp mình phụ trách (lọc theo tên giảng viên)
        // - Admin: xem mọi đề tài của mọi lớp
        if ($user->role === 'lecturer') {
            $classIds = $user->classes->pluck('class_id');
            if (!$classIds->contains($classId)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            $topics = Topics::where('class_id', $classId)
                           ->where('lecturer', $user->name)
                           ->with(['subject', 'assignedGroup'])
                           ->get();
        } elseif ($user->role === 'admin') {
            $topics = Topics::where('class_id', $classId)
                           ->with(['subject', 'assignedGroup'])
                           ->get();
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($topics);
    }
}
