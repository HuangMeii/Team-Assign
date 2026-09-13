<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Bugfix B1 [R71] + B2 [R71]:
 * - B1: Vai trò 'leader' (Nhóm trưởng) không thu thuộc hệ thống vai trò.
 *   Nhóm trưởng derive động từ groups.leader_id → đổi 'leader' -> 'student'
 *   và tạo lại ENUM role chỉ còn ('student','lecturer','admin').
 * - B2: Cờ 'isHaveGroup' chặn sinh viên vào 1 nhóm duy nhất, trong khi nghiệp vụ
 *   cho phép mỗi lớp/môn 1 nhóm → loại bó hoàn toan și lọc động theo
 *   group_members + groups.class_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        // B1: sinh vién làm trưởng nhóm quà lại vai trò hệ thống 'student'
        DB::statement("UPDATE users SET role = 'student' WHERE role = 'leader'");

        // B1: enum role chỉ còn 3 vai trò hệ thống
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('student', 'lecturer', 'admin') NOT NULL DEFAULT 'student'");

        // B2: loại bó cờ đa có nhóm duy nhất (lọc động theo nhóm)
        DB::statement("ALTER TABLE users DROP COLUMN isHaveGroup");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users ADD COLUMN isHaveGroup BOOLEAN NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('student', 'lecturer', 'admin', 'leader') NOT NULL DEFAULT 'student'");
    }
};