<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm vai trò 'leader' (Nhóm trưởng) vào enum role của bảng users.
     *
     * MySQL không hỗ trợ sửa enum trực tiếp bằng change(), nên ta dùng
     * DB::statement để drop cột role và tạo lại với danh sách giá trị mới.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('student', 'lecturer', 'admin', 'leader') NOT NULL DEFAULT 'student'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('student', 'lecturer', 'admin') NOT NULL DEFAULT 'student'");
    }
};
