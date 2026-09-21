<?php

use App\Models\Subject;

use function Tests\Support\make_user;

it('admin thêm môn học mới (không còn phân công giảng viên ở cấp môn)', function () {
    $admin = make_user('admin', 'Admin hệ thống');

    $response = $this->actingAs($admin)->post(route('admin.subjects.store'), [
        'subject_code' => 'CS' . uniqid(),
        'subject_name' => 'Môn học mới',
        'credits' => 3,
    ]);

    $response->assertSessionHas('success');
    expect(Subject::where('subject_name', 'Môn học mới')->exists())->toBeTrue();
});

it('admin có thể thêm môn học mà không nhập mã (mã tự sinh)', function () {
    $admin = make_user('admin', 'Admin hệ thống');

    $response = $this->actingAs($admin)->post(route('admin.subjects.store'), [
        'subject_name' => 'Lập trình Web',
        'credits' => 4,
    ]);

    $response->assertSessionHas('success');
    $subject = Subject::where('subject_name', 'Lập trình Web')->first();

    expect($subject)->not->toBeNull()
        ->and($subject->subject_code)->not->toBe('')
        ->and($subject->credits)->toBe(4);
});

it('admin không thể thêm môn học khi thiếu số tín chỉ', function () {
    $admin = make_user('admin', 'Admin hệ thống');

    $response = $this->actingAs($admin)->post(route('admin.subjects.store'), [
        'subject_name' => 'Môn học thiếu tín chỉ',
    ]);

    $response->assertSessionHasErrors('credits');
    expect(Subject::where('subject_name', 'Môn học thiếu tín chỉ')->exists())->toBeFalse();
});

it('admin không thể sửa mã môn học (subject_code bị giữ nguyên)', function () {
    $admin = make_user('admin', 'Admin hệ thống');

    $subject = Subject::create([
        'subject_code' => 'LOCK001',
        'subject_name' => 'Môn học gốc',
        'credits' => 3,
    ]);

    $response = $this->actingAs($admin)->put(route('admin.subjects.update', $subject->subject_id), [
        'subject_code' => 'CHANGED01',
        'subject_name' => 'Tên đã sửa',
        'credits' => 2,
    ]);

    $response->assertSessionHas('success');

    $subject->refresh();
    expect($subject->subject_code)->toBe('LOCK001')
        ->and($subject->subject_name)->toBe('Tên đã sửa')
        ->and($subject->credits)->toBe(2);
});

it('môn học không còn gắn giảng viên phụ trách (phân công ở cấp lớp học phần)', function () {
    $subject = Subject::create(['subject_code' => 'SUB1', 'subject_name' => 'Môn 1']);

    expect($subject->refresh()->toArray())->not->toHaveKey('lecturer_id');
});

it('giảng viên thấy tất cả môn học trong form tạo lớp (phân công ở cấp lớp)', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');
    Subject::create(['subject_code' => 'SUB1', 'subject_name' => 'Môn 1']);
    Subject::create(['subject_code' => 'SUB2', 'subject_name' => 'Môn 2']);

    $response = $this->actingAs($lecturer)->get(route('lecturer.classes.create'));

    $response->assertOk();
    $response->assertSee('Môn 1');
    $response->assertSee('Môn 2');
});

it('trang quản lý môn học không còn cột giảng viên phụ trách', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    Subject::create(['subject_code' => 'SUB1', 'subject_name' => 'Môn 1']);

    $response = $this->actingAs($admin)->get(route('admin.subjects.index'));

    $response->assertOk();
    $response->assertDontSee('Giảng viên phụ trách');
});

it('mã môn học luôn tự sinh, bỏ qua mã do client gửi lên', function () {
    $admin = make_user('admin', 'Admin hệ thống');

    $this->actingAs($admin)->post(route('admin.subjects.store'), [
        'subject_code' => 'HACKER99',
        'subject_name' => 'Mạng máy tính',
        'credits' => 3,
    ])->assertSessionHas('success');

    $subject = Subject::where('subject_name', 'Mạng máy tính')->first();

    expect($subject)->not->toBeNull()
        ->and($subject->subject_code)->not->toBe('HACKER99')
        ->and($subject->subject_code)->toMatch('/^[A-Z]{1,5}\d{3}$/');
});

it('form thêm môn học không còn ô nhập mã môn', function () {
    $admin = make_user('admin', 'Admin hệ thống');

    $this->actingAs($admin)->get(route('admin.subjects.create'))
        ->assertOk()
        ->assertDontSee('name="subject_code"', false)
        ->assertSee('Mã môn học sẽ được hệ thống tự sinh');
});
