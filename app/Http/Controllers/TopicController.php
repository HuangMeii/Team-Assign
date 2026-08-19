<?php

namespace App\Http\Controllers;

use App\Models\Topics;
use App\Models\ClassSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TopicController extends Controller
{
    /**
     * Display a listing of the topics.
     */
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
        
        // Search theo tên đề tài
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        // 5. Thực hiện truy vấn và phân trang
        $topics = $query->orderBy('created_at', 'desc') 
                        ->paginate(10)
                        ->withQueryString();
        
        return view('topics.index', compact('topics', 'classes'));
    }
    /**
     * Show the form for creating a new topic.
     */
    public function create()
    {
        $user = Auth::user();
        
        
        $classes = $user->classes; // <-- SỬA Ở ĐÂY
        
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
        ]);

        // Kiểm tra xem class_id có thuộc về lecturer này không
        $classIds = $user->classes->pluck('class_id'); 
        if (!$classIds->contains($request->class_id)) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Bạn không có quyền tạo đề tài cho lớp này!');
        }

        // Thêm lecturer tự động từ user đăng nhập
        $validated['lecturer'] = $user->name;
        
        // Lấy subject_id từ class
        $class = ClassSection::find($request->class_id);
        $validated['subject_id'] = $class->subject_id;

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
        
        // CHỈ lấy các lớp mà lecturer đang dạy
        $classes = $user->classes; // <-- SỬA Ở ĐÂY
        
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
        
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:topics,name,' . $topic->topic_id . ',topic_id',
            'description' => 'required|string|min:10',
            'goal' => 'nullable|string',
            'requirements' => 'nullable|string',
            'class_id' => 'required|exists:class_sections,class_id',
        ]);

        // Kiểm tra xem class_id có thuộc về lecturer này không
        $classIds = $user->classes->pluck('class_id'); // <-- SỬA Ở ĐÂY
        if (!$classIds->contains($request->class_id)) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Bạn không có quyền chuyển đề tài sang lớp này!');
        }

        // Lecturer không thay đổi
        $validated['lecturer'] = $topic->lecturer;
        
        // Cập nhật subject_id từ class mới
        $class = ClassSection::find($request->class_id);
        $validated['subject_id'] = $class->subject_id;

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
     * Get topics by class (API endpoint for AJAX)
     */
    public function getByClass($classId)
    {
        $user = Auth::user();
        
        // Kiểm tra xem class có thuộc về lecturer này không
        $classIds = $user->classes->pluck('class_id'); // <-- SỬA Ở ĐÂY
        if (!$classIds->contains($classId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $topics = Topics::where('class_id', $classId)
                       ->where('lecturer', $user->name)
                       ->with(['subject', 'assignedGroup'])
                       ->get();
        
        return response()->json($topics);
    }
}