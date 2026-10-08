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
     * Hiển thị danh sách nhóm.
     *
     * L10: Admin/Giảng viên được XÓA MỀM nhóm (`groups.destroy`) — danh sách có thêm
     * tab "Đã xóa" (`?trashed=1`) để khôi phục / xóa vĩnh viễn. Sinh viên không thấy nút xóa.
     *
     * Ghi chú: "Không tự ý thay đổi thông tin hoặc thành viên của nhóm" -> không có chức năng sửa nhóm.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // L10: chỉ Admin/Giảng viên xem được danh sách nhóm đã xóa (khôi phục/xóa vĩnh viễn).
        $showTrashed = $request->boolean('trashed') && $user->role !== 'student';

        $query = $showTrashed
            ? Groups::onlyTrashed()->with(['leader', 'topic', 'members', 'class.subject'])
            : Groups::with(['leader', 'topic', 'members', 'class.subject']);

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

        return view('groups.index', compact('groups', 'classes', 'maxMembersByGroup', 'showTrashed'));
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

    /**
     * L10 — XÓA MỀM nhóm (Admin hoặc Giảng viên phụ trách lớp).
     * Nhóm biến mất khỏi danh sách nhưng dữ liệu vẫn còn và có thể khôi phục.
     */
    public function destroy($id)
    {
        $group = Groups::findOrFail($id);

        $result = $this->groups->destroy($group, Auth::user());

        return back()->with($result->status(), $result->message());
    }

    /** L10 — Khôi phục nhóm đã xóa mềm (về trạng thái chưa có đề tài). */
    public function restore($id)
    {
        $group = Groups::onlyTrashed()->findOrFail($id);

        $result = $this->groups->restore($group, Auth::user());

        return back()->with($result->status(), $result->message());
    }

    /** L10 — Xóa vĩnh viễn nhóm (chỉ Admin; kèm chat/bảng tin theo FK CASCADE). */
    public function forceDestroy($id)
    {
        $group = Groups::onlyTrashed()->findOrFail($id);

        $result = $this->groups->forceDelete($group, Auth::user());

        return back()->with($result->status(), $result->message());
    }
}
