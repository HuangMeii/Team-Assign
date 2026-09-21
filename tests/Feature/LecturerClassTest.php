<?php

use App\Models\ClassSection;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_user;

it('giảng viên tạo lớp học phần thành công và mã lớp được tự sinh 5 ký tự', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);

    $response = $this->actingAs($lecturer)->post(route('lecturer.classes.store'), [
        'class_name' => 'Lớp sáng K1',
        'subject_id' => $subject->subject_id,
    ]);

    $response->assertSessionHas('success');

    $class = ClassSection::where('class_name', 'Lớp sáng K1')->first();
    expect($class)->not->toBeNull()
        ->and($class->class_code)->toMatch('/^[A-Z0-9]{5}$/');
});

it('giảng viên có thể tạo lớp cho bất kỳ môn học nào (phân công ở cấp lớp)', function () {
    $lecturer1 = make_user('lecturer', 'Giảng viên 1');
    $lecturer2 = make_user('lecturer', 'Giảng viên 2');
    $subject = make_subject($lecturer1); // môn học không còn gắn giảng viên cố định

    $response = $this->actingAs($lecturer2)->post(route('lecturer.classes.store'), [
        'class_name' => 'Lớp chung',
        'subject_id' => $subject->subject_id,
    ]);

    $response->assertSessionHas('success');
    expect(ClassSection::where('class_name', 'Lớp chung')->exists())->toBeTrue();
});

it('mã lớp tự sinh là duy nhất giữa các lớp khác nhau', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);

    $codes = [];
    for ($i = 1; $i <= 3; $i++) {
        $this->actingAs($lecturer)->post(route('lecturer.classes.store'), [
            'class_name' => 'Lớp tự sinh ' . $i,
            'subject_id' => $subject->subject_id,
        ])->assertSessionHas('success');

        $class = ClassSection::where('class_name', 'Lớp tự sinh ' . $i)->first();
        expect($class->class_code)->toMatch('/^[A-Z0-9]{5}$/');
        $codes[] = $class->class_code;
    }

    expect($codes)->toHaveCount(3)
        ->and(array_unique($codes))->toHaveCount(3);
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
        'subject_id' => $subject->subject_id,
    ]);

    $response->assertSessionHasErrors(['class_name' => 'Tên lớp học phần đã tồn tại, vui lòng chọn tên khác!']);
});

/*
|--------------------------------------------------------------------------
| Quản lý lớp học phần — GIẢNG VIÊN
|--------------------------------------------------------------------------
| Giảng viên chỉ thao tác trên lớp mình được phân công:
| danh sách lớp, chi tiết (mã lớp, sinh viên, nhóm), thêm/xóa sinh viên,
| khóa/mở khóa lớp. Mã lớp hiển thị để sinh viên tham gia bằng mã.
*/

it('giảng viên thấy danh sách lớp mình phụ trách kèm mã lớp', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $other = make_user('lecturer', 'Giảng viên B');
    $subject = make_subject($lecturer);
    $myClass = make_class($subject, $lecturer);
    $otherClass = make_class($subject, $other);

    $response = $this->actingAs($lecturer)->get(route('lecturer.classes.index'));

    $response->assertOk()
        ->assertSee($myClass->class_name)
        // Mã lớp hiển thị để chia sẻ cho sinh viên tham gia bằng mã
        ->assertSee($myClass->class_code)
        ->assertDontSee($otherClass->class_name);
});

it('giảng viên xem chi tiết lớp kèm mã lớp và danh sách sinh viên', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer);
    $student = make_user('student', 'Sinh viên B');
    $class->users()->attach($student->user_id);

    $response = $this->actingAs($lecturer)->get(route('lecturer.classes.show', $class->class_id));

    $response->assertOk()
        ->assertSee($class->class_code)
        ->assertSee('Sinh viên B')
        ->assertSee(route('lecturer.classes.students.add', $class->class_id), false)
        ->assertSee(route('lecturer.classes.students.remove', [$class->class_id, $student->user_id]), false);
});

it('giảng viên thêm sinh viên vào lớp và không thêm trùng', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer);
    $student1 = make_user('student', 'Sinh viên B');
    $student2 = make_user('student', 'Sinh viên C');

    $this->actingAs($lecturer)->post(route('lecturer.classes.students.add', $class->class_id), [
        'student_ids' => [$student1->user_id, $student2->user_id],
    ])->assertSessionHas('success');

    expect($class->students()->where('users.user_id', $student1->user_id)->exists())->toBeTrue()
        ->and($class->students()->where('users.user_id', $student2->user_id)->exists())->toBeTrue();

    // Thêm lại sinh viên đã có -> cảnh báo, không thêm trùng
    $this->actingAs($lecturer)->post(route('lecturer.classes.students.add', $class->class_id), [
        'student_ids' => [$student1->user_id],
    ])->assertSessionHas('warning');
});

it('giảng viên xóa sinh viên khỏi lớp thì dữ liệu nhóm được dọn dẹp', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer);

    $leader = make_user('student', 'Trưởng nhóm');
    $member = make_user('student', 'Thành viên');
    $class->users()->attach([$leader->user_id, $member->user_id]);

    $group = make_group($leader, $class, 'Nhóm 01');
    $group->members()->attach($member->user_id);

    $this->actingAs($lecturer)->post(
        route('lecturer.classes.students.remove', [$class->class_id, $leader->user_id])
    )->assertSessionHas('success');

    expect($class->students()->where('users.user_id', $leader->user_id)->exists())->toBeFalse();

    // Quyền trưởng nhóm được chuyển cho thành viên còn lại
    $group->refresh();
    expect($group->leader_id)->toBe($member->user_id)
        ->and($group->members()->where('users.user_id', $member->user_id)->exists())->toBeTrue();
});

it('giảng viên không quản lý được lớp không phụ trách', function () {
    $lecturer1 = make_user('lecturer', 'Giảng viên 1');
    $lecturer2 = make_user('lecturer', 'Giảng viên 2');
    $subject = make_subject($lecturer1);
    $class = make_class($subject, $lecturer1);
    $student = make_user('student', 'Sinh viên B');

    $indexUrl = route('lecturer.classes.index');

    $this->actingAs($lecturer2)->get(route('lecturer.classes.show', $class->class_id))
        ->assertRedirect($indexUrl)->assertSessionHas('error');

    $this->actingAs($lecturer2)->post(route('lecturer.classes.students.add', $class->class_id), [
        'student_ids' => [$student->user_id],
    ])->assertRedirect($indexUrl)->assertSessionHas('error');

    $this->actingAs($lecturer2)->post(
        route('lecturer.classes.students.remove', [$class->class_id, $student->user_id])
    )->assertRedirect($indexUrl)->assertSessionHas('error');

    $this->actingAs($lecturer2)->patch(route('lecturer.classes.toggle-active', $class->class_id))
        ->assertRedirect($indexUrl)->assertSessionHas('error');

    // Không có dữ liệu nào bị thay đổi
    expect($class->students()->count())->toBe(0)
        ->and($class->fresh()->is_active)->toBeTrue();
});

it('giảng viên khóa và mở khóa lớp', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer, true);

    $this->actingAs($lecturer)->patch(route('lecturer.classes.toggle-active', $class->class_id))
        ->assertSessionHas('success');
    expect($class->fresh()->is_active)->toBeFalse();

    $this->actingAs($lecturer)->patch(route('lecturer.classes.toggle-active', $class->class_id))
        ->assertSessionHas('success');
    expect($class->fresh()->is_active)->toBeTrue();
});

it('sinh viên không truy cập được trang quản lý lớp của giảng viên', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer);
    $student = make_user('student', 'Sinh viên B');

    $this->actingAs($student)->get(route('lecturer.classes.index'))
        ->assertRedirect(route('user.dashboard'));

    $this->actingAs($student)->get(route('lecturer.classes.show', $class->class_id))
        ->assertRedirect(route('user.dashboard'));
});
