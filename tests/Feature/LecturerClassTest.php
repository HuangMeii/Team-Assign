<?php

use App\Models\ClassSection;

use function Tests\Support\make_subject;
use function Tests\Support\make_user;

it('giảng viên tạo lớp học phần thành công', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);

    $response = $this->actingAs($lecturer)->post(route('lecturer.classes.store'), [
        'class_name' => 'Lớp sáng K1',
        'class_code' => 'WEB-SANG-' . uniqid(),
        'subject_id' => $subject->subject_id,
    ]);

    $response->assertSessionHas('success');
    expect(ClassSection::where('class_name', 'Lớp sáng K1')->exists())->toBeTrue();
});

it('giảng viên không thể tạo lớp cho môn của giảng viên khác', function () {
    $lecturer1 = make_user('lecturer', 'Giảng viên 1');
    $lecturer2 = make_user('lecturer', 'Giảng viên 2');
    $subject = make_subject($lecturer1); // môn của giảng viên 1

    $response = $this->actingAs($lecturer2)->post(route('lecturer.classes.store'), [
        'class_name' => 'Lớp sai',
        'class_code' => 'WEB-SAI-' . uniqid(),
        'subject_id' => $subject->subject_id,
    ]);

    $response->assertSessionHas('error');
    expect(ClassSection::where('class_name', 'Lớp sai')->exists())->toBeFalse();
});

it('mã lớp phải là duy nhất', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);
    $code = 'MA-DUY-NHAT';

    ClassSection::create([
        'class_name' => 'Lớp trùng mã',
        'class_code' => $code,
        'subject_id' => $subject->subject_id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($lecturer)->post(route('lecturer.classes.store'), [
        'class_name' => 'Lớp mới',
        'class_code' => $code,
        'subject_id' => $subject->subject_id,
    ]);

    $response->assertSessionHasErrors('class_code');
});

it('trang tạo lớp của giảng viên hiển thị', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');

    $this->actingAs($lecturer)->get(route('lecturer.classes.create'))->assertOk();
});

it('sinh viên không truy cập được trang tạo lớp của giảng viên', function () {
    $student = make_user('student', 'Sinh viên B');

    $response = $this->actingAs($student)->get(route('lecturer.classes.create'));

    $response->assertRedirect(route('user.dashboard'));
});

it('thông báo tên lớp trùng bằng tiếng Việt', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);

    ClassSection::create([
        'class_name' => 'Lớp trùng tên',
        'class_code' => 'MA1-' . uniqid(),
        'subject_id' => $subject->subject_id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($lecturer)->post(route('lecturer.classes.store'), [
        'class_name' => 'Lớp trùng tên',
        'class_code' => 'MA2-' . uniqid(),
        'subject_id' => $subject->subject_id,
    ]);

    $response->assertSessionHasErrors(['class_name' => 'Tên lớp học phần đã tồn tại, vui lòng chọn tên khác!']);
});
