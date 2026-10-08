<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L10 — Xóa MỀM nhóm (`groups.deleted_at`) để Admin/Giảng viên có thể
 * xóa nhóm mà KHÔNG mất dữ liệu lịch sử (thành viên, chat, bảng tin, lịch sử đăng ký đề tài).
 *
 * - Xóa mềm: ẩn khỏi danh sách mặc định + có thể khôi phục (`groups.restore`).
 * - Xóa cứng (`forceDelete`) chỉ dành cho Admin — khi đó các FK CASCADE mới kéo theo chat/bảng tin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
