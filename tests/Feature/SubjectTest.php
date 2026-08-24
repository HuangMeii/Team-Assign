<?php

use App\Models\Subject;

use function Tests\Support\make_user;

it('admin thêm môn học và phân công giảng viên phụ trách', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    $lecturer = make_user('lecturer', 'Giảng viên A');

    $response = $this->actingAs($admin)->post(route('admin.subjects.store'), [
        'subject_code' => 'CS' . uniqid(),
        'subject_name' => 'Môn học mới',
        'lecturer_id' => $lecturer->user_id,
    ]);

    $response->assertSessionHas('success');
    expect(Subject::where('subject_name', 'Môn học mới')
        ->where('lecturer_id', $lecturer->user_id)->exists())->toBeTrue();
});

it('một giảng viên có thể phụ trách nhiều môn học', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');

    Subject::create(['subject_code' => 'SUB1', 'subject_name' => 'Môn 1', 'lecturer_id' => $lecturer->user_id]);
    Subject::create(['subject_code' => 'SUB2', 'subject_name' => 'Môn 2', 'lecturer_id' => $lecturer->user_id]);

    expect(Subject::where('lecturer_id', $lecturer->user_id)->count())->toBe(2);
});

it('giảng viên thấy tất cả môn mình phụ trách trong form tạo lớp', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');
    Subject::create(['subject_code' => 'SUB1', 'subject_name' => 'Môn 1', 'lecturer_id' => $lecturer->user_id]);
    Subject::create(['subject_code' => 'SUB2', 'subject_name' => 'Môn 2', 'lecturer_id' => $lecturer->user_id]);

    $response = $this->actingAs($lecturer)->get(route('lecturer.classes.create'));

    $response->assertOk();
    $response->assertSee('Môn 1');
    $response->assertSee('Môn 2');
});

it('trang quản lý môn học hiển thị giảng viên phụ trách', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    $lecturer = make_user('lecturer', 'Giảng viên A');
    Subject::create(['subject_code' => 'SUB1', 'subject_name' => 'Môn 1', 'lecturer_id' => $lecturer->user_id]);

    $response = $this->actingAs($admin)->get(route('admin.subjects.index'));

    $response->assertOk();
    $response->assertSee('Giảng viên A');
});
