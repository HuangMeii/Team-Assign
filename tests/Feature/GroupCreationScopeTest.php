<?php

use App\Models\Groups;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_user;

/*
|--------------------------------------------------------------------------
| Phạm vi tạo nhóm & tìm nhóm THEO TỪNG LỚP HỌC PHẦN
|--------------------------------------------------------------------------
| Nghiệp vụ: mỗi sinh viên chỉ có 1 nhóm trong 1 lớp học phần.
|   - Lớp đã có nhóm KHÔNG hiện trong combo box khi tạo nhóm;
|   - Vào form từ thẻ lớp (?class_id=) -> lớp hiện dạng TEXT, không bắt chọn lại;
|   - Đã có nhóm ở mọi lớp -> không tạo thêm được (cảnh báo + nút bị khóa);
|   - "Tìm nhóm" ở từng lớp chỉ liệt kê nhóm THUỘC LỚP ĐÓ;
|   - POST class_id của lớp mình không tham gia -> bị chặn.
| Cần MySQL test theo phpunit.xml.
*/

/** Sinh viên tham gia 2 lớp học phần (A, B), chưa có nhóm. */
function gcs_student_in_two_classes(): array
{
    $lecturer = make_user('lecturer', 'GV GCS');
    $subject = make_subject($lecturer);

    $classA = make_class($subject, $lecturer);
    $classB = make_class($subject, $lecturer);

    $student = make_user('student', 'SV GCS');
    $student->classes()->attach([$classA->class_id, $classB->class_id]);

    return [$student, $classA, $classB];
}

it('combo box tạo nhóm KHÔNG hiện lớp học phần đã có nhóm', function () {
    [$student, $classA, $classB] = gcs_student_in_two_classes();

    // Sinh viên đã có nhóm trong lớp A (là trưởng nhóm)
    make_group($student, $classA, 'Nhóm lớp A');

    $this->actingAs($student)
        ->get(route('user.create_group'))
        ->assertOk()
        ->assertDontSee($classA->class_name)   // lớp đã có nhóm -> không còn trong combo box
        ->assertSee($classB->class_name);      // lớp chưa có nhóm -> vẫn chọn được
});

it('vào form tạo nhóm từ thẻ lớp: lớp hiện dạng TEXT và KHÔNG có combo box', function () {
    [$student, , $classB] = gcs_student_in_two_classes();

    $response = $this->actingAs($student)->get(route('user.create_group', ['class_id' => $classB->class_id]));

    $response->assertOk()
        ->assertSee($classB->class_name)
        ->assertSee('name="class_id"', false)
        ->assertSee('value="' . $classB->class_id . '"', false)
        ->assertDontSee('<select class="form-select', false);   // không bắt chọn lại lớp
});

it('đã có nhóm ở TẤT CẢ lớp học phần: hiện cảnh báo và không cho tạo thêm', function () {
    [$student, $classA, $classB] = gcs_student_in_two_classes();

    make_group($student, $classA, 'Nhóm A');
    make_group($student, $classB, 'Nhóm B');

    $this->actingAs($student)
        ->get(route('user.create_group'))
        ->assertOk()
        ->assertSee('Bạn đã có nhóm ở tất cả lớp học phần')
        ->assertSee('disabled', false);
});

it('POST tạo nhóm với lớp mình KHÔNG tham gia bị chặn', function () {
    $lecturer = make_user('lecturer', 'GV GCS 2');
    $otherClass = make_class(make_subject($lecturer), $lecturer);   // lớp sinh viên không học

    $student = make_user('student', 'SV GCS 2');

    $this->actingAs($student)
        ->post(route('user.store_group'), [
            'group_name' => 'Nhóm giả mạo',
            'class_id'   => $otherClass->class_id,
        ])
        ->assertSessionHas('error');

    expect(Groups::where('group_name', 'Nhóm giả mạo')->exists())->toBeFalse();
});

it('trang Nhóm của tôi: nút Tạo nhóm mới của thẻ lớp có kèm class_id', function () {
    [$student, , $classB] = gcs_student_in_two_classes();

    $this->actingAs($student)
        ->get(route('user.my_groups'))
        ->assertOk()
        ->assertSee(route('user.create_group', ['class_id' => $classB->class_id]), false);
});

it('modal Tìm nhóm của mỗi lớp CHỈ chứa nhóm thuộc lớp đó', function () {
    [$student, $classA, $classB] = gcs_student_in_two_classes();

    $leaderA = make_user('student', 'Trưởng nhóm lớp A');
    $leaderB = make_user('student', 'Trưởng nhóm lớp B');
    $groupA = make_group($leaderA, $classA, 'Nhóm còn chỗ A');
    $groupB = make_group($leaderB, $classB, 'Nhóm còn chỗ B');

    $html = $this->actingAs($student)->get(route('user.my_groups'))->assertOk()->getContent();

    // Tách riêng khối modal của lớp A
    preg_match('/id="findGroupModal-' . $classA->class_id . '".*?(?=<div class="modal fade" id="findGroupModal-|<\/body>)/s', $html, $matches);

    expect($matches)->not->toBeEmpty();

    $modalA = $matches[0];

    expect($modalA)->toContain($groupA->group_name)
        ->and($modalA)->not->toContain($groupB->group_name);

    // Cả 2 modal đều tồn tại (mỗi lớp 1 modal riêng)
    expect($html)->toContain('findGroupModal-' . $classB->class_id);
});
