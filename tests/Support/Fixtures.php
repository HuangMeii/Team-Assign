<?php

namespace Tests\Support;

use App\Models\ClassSection;
use App\Models\Groups;
use App\Models\Subject;
use App\Models\Topics;
use App\Models\User;

function make_user(string $role, string $name = 'Người dùng test'): User
{
    return User::create([
        'name' => $name,
        'email' => strtolower($role . '_' . uniqid()) . '@test.com',
        'password' => 'password',
        'role' => $role,
        'is_have_group' => false,
        'is_active' => true,
    ]);
}

function make_subject(User $lecturer): Subject
{
    return Subject::create([
        'subject_code' => 'SUB' . uniqid(),
        'subject_name' => 'Môn học ' . uniqid(),
        'lecturer_id' => $lecturer->user_id,
    ]);
}

function make_class(Subject $subject, ?User $lecturer = null, bool $active = true): ClassSection
{
    $class = ClassSection::create([
        'subject_id' => $subject->subject_id,
        'class_name' => 'Lớp học ' . uniqid(),
        'class_code' => 'CLASS' . uniqid(),
        'is_active' => $active,
    ]);

    if ($lecturer) {
        $class->users()->attach($lecturer->user_id);
    }

    return $class;
}

function make_topic(
    ClassSection $class,
    Subject $subject,
    int $min = 2,
    int $max = 4,
    ?string $deadline = null
): Topics {
    return Topics::create([
        'name' => 'Đề tài ' . uniqid(),
        'description' => 'Mô tả đề tài phục vụ kiểm thử nghiệp vụ.',
        'lecturer' => 'Giảng viên test',
        'min_members' => $min,
        'max_members' => $max,
        'registration_deadline' => $deadline ?? now()->addDays(30),
        'is_active' => true,
        'subject_id' => $subject->subject_id,
        'class_id' => $class->class_id,
    ]);
}

/**
 * Tạo nhóm cho sinh viên (trưởng nhóm), không gắn thành viên khác.
 */
function make_group(User $leader, ClassSection $class, string $name = 'Nhóm test'): Groups
{
    return Groups::create([
        'group_name' => $name . ' ' . uniqid(),
        'leader_id' => $leader->user_id,
        'class_id' => $class->class_id,
        'status' => 'incomplete',
    ]);
}
