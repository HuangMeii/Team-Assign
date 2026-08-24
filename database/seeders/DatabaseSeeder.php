<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Subject;
use App\Models\ClassSection;
use App\Models\Topics;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ============================================================
        // 1. Tạo tài khoản Admin mặc định
        // ============================================================
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'isFirstLogin' => false,
            'isHaveGroup' => false,
            'is_active' => true,
        ]);

        // ============================================================
        // 2. Tạo tài khoản Giảng viên mặc định
        // ============================================================
        $lecturer = User::factory()->create([
            'name' => 'Giảng viên A',
            'email' => 'lecturer@example.com',
            'password' => Hash::make('password'),
            'role' => 'lecturer',
            'isFirstLogin' => false,
            'isHaveGroup' => false,
            'is_active' => true,
        ]);

        // ============================================================
        // 3. Tạo tài khoản Sinh viên mặc định
        // ============================================================
        $student = User::factory()->create([
            'name' => 'Sinh viên A',
            'email' => 'student@example.com',
            'password' => Hash::make('password'),
            'role' => 'student',
            'isFirstLogin' => false,
            'isHaveGroup' => false,
            'is_active' => true,
        ]);

        // ============================================================
        // 4. Tạo môn học mẫu
        // ============================================================
        $subject = Subject::create([
            'subject_code' => 'CS101',
            'subject_name' => 'Lập trình Web',
            'lecturer_id' => $lecturer->user_id,
        ]);

        // ============================================================
        // 5. Tạo lớp học mẫu (có mã lớp để sinh viên tự tham gia)
        // ============================================================
        $class = ClassSection::create([
            'subject_id' => $subject->subject_id,
            'class_name' => 'Lớp Lập trình Web - K1',
            'class_code' => 'WEB-K1-2026',
            'is_active' => true,
        ]);

        // Gán giảng viên vào lớp
        $class->users()->attach($lecturer->user_id);

        // Gán sinh viên vào lớp
        $class->users()->attach($student->user_id);

        // ============================================================
        // 6. Tạo đề tài mẫu (có min/max thành viên, deadline)
        // ============================================================
        Topics::create([
            'name' => 'Xây dựng website quản lý thư viện',
            'description' => 'Xây dựng hệ thống quản lý thư viện trực tuyến với các chức năng mượn/trả sách.',
            'lecturer' => $lecturer->name,
            'goal' => 'Giúp sinh viên nắm vững quy trình phát triển web.',
            'requirements' => 'Sử dụng Laravel, MySQL.',
            'min_members' => 2,
            'max_members' => 4,
            'registration_deadline' => now()->addDays(30),
            'is_active' => true,
            'subject_id' => $subject->subject_id,
            'class_id' => $class->class_id,
        ]);

        Topics::create([
            'name' => 'Ứng dụng quản lý chi tiêu cá nhân',
            'description' => 'Xây dựng ứng dụng quản lý chi tiêu cá nhân trên nền web.',
            'lecturer' => $lecturer->name,
            'goal' => 'Rèn luyện kỹ năng phân tích và thiết kế hệ thống.',
            'requirements' => 'Sử dụng Laravel, Bootstrap.',
            'min_members' => 3,
            'max_members' => 5,
            'registration_deadline' => now()->addDays(30),
            'is_active' => true,
            'subject_id' => $subject->subject_id,
            'class_id' => $class->class_id,
        ]);
    }
}
