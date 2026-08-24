<?php

namespace App\Http\Controllers;

use App\Models\Groups;
use App\Models\ClassSection;
use App\Services\GroupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{
    public function __construct(
        private readonly GroupService $groups,
    ) {}
    /**
     * Hiển thị danh sách nhóm (chỉ đọc).
     *
     * Theo yêu cầu đề tài:
     * - "Không thể hủy nhóm sau khi tạo" -> không có chức năng xóa nhóm.
     * - "Không tự ý thay đổi thông tin hoặc thành viên của nhóm" -> không có chức năng sửa nhóm.
     * - Admin/Giảng viên không trực tiếp tạo nhóm hoặc gán đề tài thay nhóm trưởng.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Groups::with(['leader', 'topic', 'members', 'class.subject']);

        // Nếu là lecturer, chỉ hiển thị nhóm trong các lớp mình dạy
        if ($user->role === 'lecturer') {
            $lecturerClassIds = $user->classes->pluck('class_id');
            $query->whereIn('class_id', $lecturerClassIds);
            $classes = $user->classes;
        } else {
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

        // Bản đồ max_members cho từng nhóm (hiển thị trạng thái đủ/thiếu thành viên)
        $maxMembersByGroup = $groups->getCollection()->mapWithKeys(function ($group) {
            return [$group->group_id => $this->groups->maxMembers($group)];
        });

        return view('groups.index', compact('groups', 'classes', 'maxMembersByGroup'));
    }

    /**
     * Chi tiết nhóm (chỉ đọc).
     */
    public function show($id)
    {
        $user = Auth::user();
        $group = Groups::with(['leader', 'members', 'topic', 'class.subject'])->findOrFail($id);

        // Kiểm tra quyền xem
        if ($user->role === 'lecturer') {
            $classIds = $user->classes->pluck('class_id');
            if (!$classIds->contains($group->class_id)) {
                abort(403, 'Bạn không có quyền xem nhóm này.');
            }
        }

        $maxMembers = $this->groups->maxMembers($group);

        return view('groups.show', compact('group', 'maxMembers'));
    }
}
