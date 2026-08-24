<?php

namespace App\Http\Controllers;

use App\Models\ClassSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassJoinController extends Controller
{
    /**
     * Sinh viên tham gia lớp học bằng mã lớp do giảng viên cung cấp.
     */
    public function joinByCode(Request $request)
    {
        $validated = $request->validate([
            'class_code' => 'required|string|max:50',
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

        // Kiểm tra user đã tham gia lớp này chưa
        if ($user->classes()->where('class_sections.class_id', $class->class_id)->exists()) {
            return back()->with('warning', 'Bạn đã tham gia lớp học này rồi!');
        }

        // Tham gia lớp
        $user->classes()->attach($class->class_id);

        return back()->with('success', 'Đã tham gia lớp "' . $class->class_name . '" thành công!');
    }
}
