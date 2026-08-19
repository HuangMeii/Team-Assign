<?php

namespace App\Http\Controllers;

use App\Models\Groups;
use App\Models\User;
use App\Models\ClassSection;
use App\Models\Topics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{
    /**
     * Hiển thị danh sách nhóm (có filter theo lớp)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = Groups::with(['leader', 'topic', 'members', 'class.subject']);
        
        // Nếu là lecturer, chỉ hiển thị nhóm trong các lớp mình dạy
        if ($user->role === 'lecturer') {
            $lecturerClassIds = $user->classes->pluck('class_id');
            $query->whereIn('class_id', $lecturerClassIds);
            
            // Lấy danh sách lớp của lecturer để filter
            $classes = $user->classes;
        } else {
            // Admin hoặc student thì lấy tất cả lớp
            $classes = ClassSection::with('subject')->get();
        }
        
        // Filter theo lớp nếu có
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        
        // Search theo tên nhóm
        if ($request->filled('search')) {
            $query->where('group_name', 'like', '%' . $request->search . '%');
        }
        
        $groups = $query->paginate(9)->withQueryString();

        return view('groups.index', compact('groups', 'classes'));
    }

    /**
     * Form tạo nhóm
     */
    public function create()
    {
        $user = Auth::user();
        
        // Nếu là lecturer, chỉ cho tạo nhóm trong lớp mình dạy
        if ($user->role === 'lecturer' or $user->role === 'admin') {
            $classes = $user->classes;
        } else {
            $classes = ClassSection::with('subject')->get();
        }
        
        return view('groups.create', compact('classes'));
    }

    /**
     * Lưu nhóm mới
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'group_name' => 'required|string|max:255',
            'class_id' => 'required|exists:class_sections,class_id',
            'leader_id' => 'required|exists:users,user_id',
        ]);

        // Kiểm tra nếu là lecturer, class_id phải thuộc về họ
        if ($user->role === 'lecturer') {
            $classIds = $user->classes->pluck('class_id');
            if (!$classIds->contains($request->class_id)) {
                return redirect()->back()
                               ->withInput()
                               ->with('error', 'Bạn không có quyền tạo nhóm trong lớp này!');
            }
        }

        Groups::create($validated);
        return redirect()->route('groups.index')->with('success', 'Tạo nhóm thành công!');
    }

    /**
     * Xem chi tiết nhóm
     */
    public function show($id)
    {
        $user = Auth::user();
        
        $group = Groups::with(['leader', 'members', 'topic', 'class.subject'])->findOrFail($id);
        
        // Kiểm tra quyền xem (nếu là lecturer thì phải thuộc lớp mình dạy)
        if ($user->role === 'lecturer') {
            $classIds = $user->classes->pluck('class_id');
            if (!$classIds->contains($group->class_id)) {
                abort(403, 'Bạn không có quyền xem nhóm này.');
            }
        }
        
        // Lấy danh sách topics của lớp này để assign
        $availableTopics = Topics::where('class_id', $group->class_id)
                                 ->whereNull('assigned_group_id')
                                 ->get();
        
        return view('groups.show', compact('group', 'availableTopics'));
    }

    /**
     * Form chỉnh sửa nhóm
     */
    public function edit($id)
    {
        $user = Auth::user();
        $group = Groups::findOrFail($id);
        
        // Kiểm tra quyền sửa
        if ($user->role === 'lecturer') {
            $classIds = $user->classes->pluck('class_id');
            if (!$classIds->contains($group->class_id)) {
                abort(403, 'Bạn không có quyền chỉnh sửa nhóm này.');
            }
        }
        
        // Lấy danh sách lớp
        if ($user->role === 'lecturer') {
            $classes = $user->classes;
        } else {
            $classes = ClassSection::with('subject')->get();
        }
        
        // Lấy danh sách sinh viên trong lớp
        $students = User::whereHas('classes', function($q) use ($group) {
            $q->where('class_id', $group->class_id);
        })->where('role', 'student')->get();
        
        return view('groups.edit', compact('group', 'classes', 'students'));
    }

    /**
     * Cập nhật nhóm
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $group = Groups::findOrFail($id);
        
        // Kiểm tra quyền sửa
        if ($user->role === 'lecturer') {
            $classIds = $user->classes->pluck('class_id');
            if (!$classIds->contains($group->class_id)) {
                abort(403, 'Bạn không có quyền chỉnh sửa nhóm này.');
            }
        }
        
        $validated = $request->validate([
            'group_name' => 'required|string|max:255',
            'class_id' => 'required|exists:class_sections,class_id',
            'leader_id' => 'required|exists:users,user_id',
        ]);

        $group->update($validated);
        return redirect()->route('groups.show', $group->group_id)
                       ->with('success', 'Cập nhật nhóm thành công!');
    }

    /**
     * Xóa nhóm
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $group = Groups::findOrFail($id);
        
        // Kiểm tra quyền xóa
        if ($user->role === 'lecturer') {
            $classIds = $user->classes->pluck('class_id');
            if (!$classIds->contains($group->class_id)) {
                abort(403, 'Bạn không có quyền xóa nhóm này.');
            }
        }
        
        // Kiểm tra xem nhóm đã có topic chưa
        if ($group->topic_id) {
            return redirect()->route('groups.index')
                           ->with('error', 'Không thể xóa nhóm đã được gán đề tài!');
        }
        
        $group->delete();
        return redirect()->route('groups.index')->with('success', 'Xóa nhóm thành công!');
    }

    /**
     * Gán topic cho nhóm
     */
    public function assignTopic(Request $request, $id)
    {
        $group = Groups::findOrFail($id);
        
        $validated = $request->validate([
            'topic_id' => 'required|exists:topics,topic_id',
        ]);
        
        // Check xem topic có thuộc cùng lớp không
        $topic = Topics::findOrFail($request->topic_id);
        if ($topic->class_id !== $group->class_id) {
            return redirect()->back()->with('error', 'Đề tài không thuộc lớp của nhóm này!');
        }
        
        // Check xem topic đã được assign chưa
        if ($topic->assigned_group_id) {
            return redirect()->back()->with('error', 'Đề tài này đã được gán cho nhóm khác!');
        }
        
        // Assign topic
        $group->update(['topic_id' => $request->topic_id]);
        $topic->update(['assigned_group_id' => $group->group_id]);
        
        return redirect()->route('groups.show', $group->group_id)
                       ->with('success', 'Gán đề tài thành công!');
    }
}