<?php

use function Tests\Support\make_class;
use function Tests\Support\make_subject;
use function Tests\Support\make_user;

/*
 * Sinh viên thấy mục Lớp học trên sidebar, thấy card Lớp học trên dashboard,
 * và vào được trang quản lý lớp để nhập mã lớp và tham gia lớp mới.
 */
it('sinh viên thấy menu lớp học và vào được trang quản lý lớp', function () {
    $student = make_user('student', 'Sinh viên B');

    $this->actingAs($student)->get(route('user.dashboard'))
        ->assertOk()
        ->assertSee('Lớp học')
        ->assertSee('Lớp học của tôi')
        ->assertSee('Vào lớp bằng mã')
        ->assertSee(route('user.classes'));

    $this->actingAs($student)->get(route('user.classes'))
        ->assertOk()
        ->assertSee('Vào lớp bằng mã lớp')
        ->assertSee(route('user.join-class'));
});

it('sinh viên xem chi tiết lớp của mình thấy mã lớp để chia sẻ', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer);
    $student = make_user('student', 'Sinh viên B');
    $class->users()->attach($student->user_id);

    $this->actingAs($student)->get(route('user.class_detail', $class->class_id))
        ->assertOk()
        ->assertSee('Mã lớp')
        ->assertSee($class->class_code);
});

it('sinh viên chỉ thấy các lớp mình đã tham gia trong danh sách lớp', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);
    $joinedClass = make_class($subject, $lecturer);
    $otherClass = make_class($subject, $lecturer);
    $student = make_user('student', 'Sinh viên B');
    $joinedClass->users()->attach($student->user_id);

    $this->actingAs($student)->get(route('user.classes'))
        ->assertOk()
        ->assertSee($joinedClass->class_name)
        ->assertDontSee($otherClass->class_name);
});

it('sinh viên không xem được chi tiết lớp mình chưa tham gia', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer);
    $student = make_user('student', 'Sinh viên B');

    $this->actingAs($student)->get(route('user.class_detail', $class->class_id))
        ->assertRedirect(route('user.classes'))
        ->assertSessionHas('error');
});