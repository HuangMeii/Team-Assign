<?php

use function Tests\Support\make_class;
use function Tests\Support\make_subject;
use function Tests\Support\make_user;

beforeEach(function () {
    $this->lecturer = make_user('lecturer', 'Giảng viên A');
    $this->subject = make_subject($this->lecturer);
    $this->class = make_class($this->subject, $this->lecturer);
});

it('sinh viên tham gia lớp thành công bằng mã lớp', function () {
    $student = make_user('student', 'Sinh viên B');

    $response = $this->actingAs($student)->post(route('user.join-class'), [
        'class_code' => $this->class->class_code,
    ]);

    $response->assertSessionHas('success');
    expect($student->classes()->where('class_sections.class_id', $this->class->class_id)->exists())->toBeTrue();
});

it('không tìm thấy lớp khi nhập sai mã', function () {
    $student = make_user('student', 'Sinh viên C');

    $response = $this->actingAs($student)->post(route('user.join-class'), [
        'class_code' => 'MA-KHONG-TON-TAI',
    ]);

    $response->assertSessionHas('error');
});

it('không thể tham gia lớp đã bị khóa', function () {
    $lockedClass = make_class($this->subject, $this->lecturer, false);
    $student = make_user('student', 'Sinh viên D');

    $response = $this->actingAs($student)->post(route('user.join-class'), [
        'class_code' => $lockedClass->class_code,
    ]);

    $response->assertSessionHas('error');
});

it('không thể tham gia lớp đã tham gia rồi', function () {
    $student = make_user('student', 'Sinh viên E');
    $student->classes()->attach($this->class->class_id);

    $response = $this->actingAs($student)->post(route('user.join-class'), [
        'class_code' => $this->class->class_code,
    ]);

    $response->assertSessionHas('warning');
});
