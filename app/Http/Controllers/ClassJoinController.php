<?php

namespace App\Http\Controllers;

use App\Models\ClassSection;
use App\Models\user_class;
use App\Services\GroupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClassJoinController extends Controller
{
    public function __construct(private readonly GroupService $groupService)
    {
    }

    /**
     * Sinh viên tham gia lớp học bằng mã lớp do giảng viên cung cấp.
     *
     * L05: nếu sinh viên từng học lớp này rồi rời (`user_classes.status = 'left'`)
     * thì KHÔI PHỤC thành 'studying' (không thêm dòng trùng, không vi phạm unique).
     * Chốt 2a: nếu sinh viên là TRƯỞNG NHÓM của nhóm cũ trong lớp ⇒ nhóm "hồi sinh".
     */
    public function joinByCode(Request $request)
    {
        $validated = $request->validate([
            'class_code' => 'required|string|size:5',
        ], [
            'class_code.size' => 'Mã lớp gồm đúng 5 ký tự. Vui lòng kiểm tra lại mã được giảng viên cung cấp!',
        ]);

        $user = Auth::user();

        // Tìm lớp theo mã lớp
        $class = ClassSection::where('class_code', $validated['class_code'])->first();

        if (!$class) {
            return back()->with('error', 'Không tìm thấy lớp học với mã này!');
        }

        // Kiểm tra lớp có bị khóa không
        if (isset($class->is_active) && !$class->is_active) {
            return back()->with('error', 'Lớp học này đã bị khóa, không thể tham gia!');
        }

        // Kiểm tra user đã tham gia lớp này chưa (chỉ tính dòng ĐANG HỌC)
        $existing = user_class::where('user_id', $user->user_id)
            ->where('class_id', $class->class_id)
            ->first();

        if ($existing && $existing->status === user_class::STATUS_STUDYING) {
            return back()->with('warning', 'Bạn đã tham gia lớp học này rồi!');
        }

        // Tham gia lớp (hoặc khôi phục nếu từng rời lớp)
        DB::transaction(function () use ($existing, $user, $class) {
            if ($existing) {
                $existing->update([
                    'status' => user_class::STATUS_STUDYING,
                    'left_at' => null,
                ]);

                return;
            }

            user_class::create([
                'user_id' => $user->user_id,
                'class_id' => $class->class_id,
                'status' => user_class::STATUS_STUDYING,
            ]);
        });

        // Chốt 2a: trưởng nhóm nhóm cũ quay lại lớp ⇒ nhóm hồi sinh (không tạo nhóm mới)
        $rejoinedGroup = $this->groupService->restoreMembershipOnRejoin($user, (int) $class->class_id);

        if ($existing) {
            $message = 'Chào mừng bạn quay lại lớp "' . $class->class_name . '"!';
            if ($rejoinedGroup) {
                $message .= ' Bạn đã trở lại nhóm "' . $rejoinedGroup->group_name . '" với vai trò trưởng nhóm.';
            }

            return back()->with('success', $message);
        }

        return back()->with('success', 'Đã tham gia lớp "' . $class->class_name . '" thành công!');
    }
}
