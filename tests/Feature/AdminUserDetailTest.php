<?php

/*
|--------------------------------------------------------------------------
| Admin xem chi tiết user (/admin/users/{id}) — Lớp & nhóm theo vai trò
|--------------------------------------------------------------------------
|  1. Giảng viên -> danh sách lớp đang phụ trách (KHÔNG có card Nhóm).
|  2. Sinh viên  -> danh sách lớp (kèm trạng thái) + danh sách nhóm.
|  3. Sinh viên đã rời lớp vẫn hiện trong danh sách với badge "Đã rời lớp".
|  4. Không phải admin gọi URL trực tiếp -> 403.
*/

use Illuminate\Support\Facades\DB;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_user;

beforeEach(function () {
    $this->admin = make_user('admin', 'Admin hệ thống');
    $this->lecturer = make_user('lecturer', 'Giảng viên A');
    $this->student = make_user('student', 'Sinh viên A');

    $this->subject = make_subject($this->lecturer);
    // make_class tự attach giảng viên vào user_classes (status mặc định 'studying').
    $this->class = make_class($this->subject, $this->lecturer);
    $this->class->users()->attach($this->student->user_id);

    // Sinh viên là TRƯỞNG nhóm: groupsLed vẫn thấy nhóm kể cả khi thiếu dòng group_members.
    $this->group = make_group($this->student, $this->class, 'Nhóm của SV');
});

it('Admin xem chi tiết giảng viên thấy danh sách lớp phụ trách, không thấy nhóm', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.users.show', $this->lecturer->user_id))
        ->assertOk()
        ->assertSee($this->class->class_name)
        ->assertSee($this->subject->subject_name)
        ->assertSee('Giảng viên phụ trách')
        ->assertDontSee('Nhóm (');
});

it('Admin xem chi tiết sinh viên thấy danh sách lớp và danh sách nhóm', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.users.show', $this->student->user_id))
        ->assertOk()
        ->assertSee($this->class->class_name)
        ->assertSee('Đang học')
        ->assertSee('Lớp học phần (')
        ->assertSee($this->group->group_name)
        ->assertSee('Trưởng nhóm')
        ->assertSee('Nhóm (');
});

it('Sinh viên đã rời lớp vẫn hiện trong danh sách với badge Đã rời lớp', function () {
    DB::table('user_classes')
        ->where('user_id', $this->student->user_id)
        ->where('class_id', $this->class->class_id)
        ->update(['status' => 'left', 'left_at' => now()]);

    $this->actingAs($this->admin)
        ->get(route('admin.users.show', $this->student->user_id))
        ->assertOk()
        ->assertSee($this->class->class_name)
        ->assertSee('Đã rời lớp')
        ->assertDontSee('Đang học');
});

it('Không phải admin thì không xem được chi tiết user của admin', function () {
    // Middleware CheckAdminRole redirect theo vai trò (không phải 403).
    $this->actingAs($this->lecturer)
        ->get(route('admin.users.show', $this->lecturer->user_id))
        ->assertRedirect(route('dashboard.lecturer'));

    $this->actingAs($this->student)
        ->get(route('admin.users.show', $this->student->user_id))
        ->assertRedirect(route('user.dashboard'));
});