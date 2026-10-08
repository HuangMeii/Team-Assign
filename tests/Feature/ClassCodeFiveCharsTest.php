<?php

/*
|--------------------------------------------------------------------------
| L07 — Mã lớp thống nhất ĐÚNG 5 ký tự (admin + giảng viên) + join siết size:5
|--------------------------------------------------------------------------
| Bao phủ:
|  1. Admin tạo lớp → mã 5 ký tự, duy nhất giữa nhiều lớp.
|  2. Giảng viên tạo lớp → mã 5 ký tự (dùng chung quy tắc/bộ sinh mã với admin).
|  3. Sinh viên tham gia: mã 4 hoặc 6 ký tự bị từ chối kèm thông báo tiếng Việt.
|  4. Seeder dữ liệu mẫu + Fixtures của test đều tôn trọng quy tắc 5 ký tự.
|  5. Không còn method sinh mã cũ kiểu `{subject_code}-NN`.
*/

use App\Http\Controllers\ClassSectionController;
use App\Models\ClassSection;

use function Tests\Support\make_class;
use function Tests\Support\make_subject;
use function Tests\Support\make_user;

it('admin tạo nhiều lớp: mã lớp luôn 5 ký tự và không trùng nhau', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    $subject = make_subject();

    $codes = [];
    for ($i = 1; $i <= 5; $i++) {
        $this->actingAs($admin)->post(route('admin.classes.store'), [
            'class_name' => 'Lớp admin ' . $i,
            'subject_id' => $subject->subject_id,
        ])->assertSessionHas('success');

        $code = ClassSection::where('class_name', 'Lớp admin ' . $i)->value('class_code');
        expect($code)->toMatch('/^[A-Z0-9]{5}$/');
        $codes[] = $code;
    }

    expect(array_unique($codes))->toHaveCount(5);
});

it('giảng viên tạo lớp: mã lớp 5 ký tự theo đúng quy tắc của admin', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject();

    $this->actingAs($lecturer)->post(route('lecturer.classes.store'), [
        'class_name' => 'Lớp giảng viên 1',
        'subject_id' => $subject->subject_id,
    ])->assertSessionHas('success');

    expect(ClassSection::where('class_name', 'Lớp giảng viên 1')->value('class_code'))
        ->toMatch('/^[A-Z0-9]{5}$/');
});

it('sinh viên tham gia lớp: mã 4 hoặc 6 ký tự bị từ chối kèm thông báo tiếng Việt', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $class = make_class(make_subject(), $lecturer);
    $student = make_user('student', 'Sinh viên B');

    foreach (['ABCD', 'ABCDEF'] as $invalid) {
        $this->actingAs($student)->post(route('user.join-class'), ['class_code' => $invalid])
            ->assertSessionHasErrors([
                'class_code' => 'Mã lớp gồm đúng 5 ký tự. Vui lòng kiểm tra lại mã được giảng viên cung cấp!',
            ]);
    }

    // Mã 5 ký tự nhưng không tồn tại → tìm không thấy lớp (không phải lỗi validate).
    $this->actingAs($student)->post(route('user.join-class'), ['class_code' => 'ZZZZZ'])
        ->assertSessionHas('error');

    // Mã đúng của lớp → tham gia thành công.
    $this->actingAs($student)->post(route('user.join-class'), ['class_code' => $class->class_code])
        ->assertSessionHas('success');
});

it('seeder dữ liệu mẫu + Fixtures không sinh mã lớp lệch 5 ký tự', function () {
    $this->seed();

    $all = ClassSection::pluck('class_code')->all();
    expect($all)->not->toBeEmpty();

    foreach ($all as $code) {
        expect($code)->toMatch('/^[A-Z0-9]{5}$/');
    }

    // Fixtures dùng cho các test khác cũng phải theo quy tắc 5 ký tự.
    expect(make_class(make_subject(), make_user('lecturer', 'GV Fixture'))->class_code)
        ->toMatch('/^[A-Z0-9]{5}$/');

    // Bất biến toàn DB: không còn dòng nào có mã lệch 5 ký tự.
    expect(ClassSection::whereRaw('CHAR_LENGTH(class_code) <> 5')->count())->toBe(0);
});

it('không còn method sinh mã lớp cũ kiểu {subject_code}-NN', function () {
    $reflection = new ReflectionClass(ClassSectionController::class);

    expect($reflection->hasMethod('generateClassCode'))->toBeFalse()
        ->and($reflection->hasMethod('generateUniqueClassCode'))->toBeTrue();
});
