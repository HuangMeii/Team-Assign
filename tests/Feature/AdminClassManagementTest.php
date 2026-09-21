<?php

use App\Models\ClassSection;
use App\Models\Groups;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_user;

/*
|--------------------------------------------------------------------------
| Admin — Quản lý Lớp học phần
|--------------------------------------------------------------------------
| Bao phủ 3 lỗi người dùng báo:
|  1. Danh sách lớp học phần không hiển thị.
|  2. Không thêm/xóa được sinh viên trong lớp học phần.
|  3. Không thay đổi được giảng viên phụ trách.
*/

it('admin thấy danh sách lớp học phần', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer);

    $response = $this->actingAs($admin)->get(route('admin.classes.index'));

    $response->assertOk();
    $response->assertSee($class->class_name);
    // Mã lớp hiển thị để admin chia sẻ cho sinh viên tham gia bằng mã
    $response->assertSee($class->class_code);
    $response->assertSee($subject->subject_name);
    // Cột "Giảng viên" phải hiển thị tên giảng viên phụ trách (accessor lecturer)
    $response->assertSee('Giảng viên A');
    // Có nút mở trang chi tiết (quản lý sinh viên / đổi giảng viên)
    $response->assertSee(route('admin.classes.show', $class->class_id), false);
    // Bộ lọc multi-select (Bugfix C6)
    $response->assertSee('subject_ids[]', false);
    $response->assertSee('lecturer_ids[]', false);
    $response->assertSee('statuses[]', false);
});

it('admin xem chi tiết lớp học phần kèm danh sách sinh viên', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer);
    $student = make_user('student', 'Sinh viên B');
    $class->users()->attach($student->user_id);

    $response = $this->actingAs($admin)->get(route('admin.classes.show', $class->class_id));

    $response->assertOk();
    $response->assertSee($class->class_name);
    $response->assertSee($class->class_code);
    $response->assertSee('Sinh viên B');
    $response->assertSee('Giảng viên A');
    // Form thêm/xóa sinh viên phải render được URL (trước đây thiếu route -> 500)
    $response->assertSee(route('admin.classes.students.add', $class->class_id), false);
    $response->assertSee(route('admin.classes.students.remove', [$class->class_id, $student->user_id]), false);
    // Ô tìm kiếm sinh viên để thêm vào lớp
    $response->assertSee('studentSearch', false);
    $response->assertSee('studentSelect', false);
});

it('admin thêm sinh viên vào lớp học phần', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer);
    $student1 = make_user('student', 'Sinh viên B');
    $student2 = make_user('student', 'Sinh viên C');

    $response = $this->actingAs($admin)->post(route('admin.classes.students.add', $class->class_id), [
        'student_ids' => [$student1->user_id, $student2->user_id],
    ]);

    $response->assertSessionHas('success');
    expect($class->users()->where('users.user_id', $student1->user_id)->exists())->toBeTrue()
        ->and($class->users()->where('users.user_id', $student2->user_id)->exists())->toBeTrue();
});

it('admin không thêm trùng sinh viên đã có trong lớp', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer);
    $student = make_user('student', 'Sinh viên B');
    $class->users()->attach($student->user_id);

    $this->actingAs($admin)->post(route('admin.classes.students.add', $class->class_id), [
        'student_ids' => [$student->user_id],
    ]);

    expect($class->users()->where('users.user_id', $student->user_id)->count())->toBe(1);
});

it('admin xóa sinh viên khỏi lớp học phần', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer);
    $student = make_user('student', 'Sinh viên B');
    $class->users()->attach($student->user_id);

    $response = $this->actingAs($admin)->post(
        route('admin.classes.students.remove', [$class->class_id, $student->user_id])
    );

    $response->assertSessionHas('success');
    expect($class->users()->where('users.user_id', $student->user_id)->exists())->toBeFalse();
});

it('admin thay đổi giảng viên phụ trách lớp học phần', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    $oldLecturer = make_user('lecturer', 'Giảng viên cũ');
    $newLecturer = make_user('lecturer', 'Giảng viên mới');
    $subject = make_subject($oldLecturer);
    $class = make_class($subject, $oldLecturer);

    $response = $this->actingAs($admin)->put(route('admin.classes.update', $class->class_id), [
        'class_name' => $class->class_name,
        'subject_id' => $subject->subject_id,
        'lecturer_id' => $newLecturer->user_id,
    ]);

    $response->assertSessionHas('success');
    $class->refresh();
    expect($class->lecturers->pluck('user_id')->all())->toBe([$newLecturer->user_id]);
});

it('admin thay đổi giảng viên phụ trách không xóa sinh viên khỏi lớp', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    $oldLecturer = make_user('lecturer', 'Giảng viên cũ');
    $newLecturer = make_user('lecturer', 'Giảng viên mới');
    $subject = make_subject($oldLecturer);
    $class = make_class($subject, $oldLecturer);
    $student = make_user('student', 'Sinh viên B');
    $class->users()->attach($student->user_id);

    $this->actingAs($admin)->put(route('admin.classes.update', $class->class_id), [
        'class_name' => $class->class_name,
        'subject_id' => $subject->subject_id,
        'lecturer_id' => $newLecturer->user_id,
    ]);

    expect($class->users()->where('users.user_id', $student->user_id)->exists())->toBeTrue()
        ->and($class->lecturers()->count())->toBe(1);
});

it('admin bỏ phân công giảng viên phụ trách lớp', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer);

    $this->actingAs($admin)->put(route('admin.classes.update', $class->class_id), [
        'class_name' => $class->class_name,
        'subject_id' => $subject->subject_id,
        'lecturer_id' => '',
    ]);

    expect($class->lecturers()->count())->toBe(0);
});

it('admin tạo lớp học phần sinh mã lớp tự động', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);

    $this->actingAs($admin)->post(route('admin.classes.store'), [
        'class_name' => 'Lớp mới ' . uniqid(),
        'subject_id' => $subject->subject_id,
        'lecturer_id' => $lecturer->user_id,
    ])->assertSessionHas('success');

    $class = ClassSection::latest('class_id')->first();
    expect($class->class_code)->not->toBeNull()
        ->and($class->lecturers->pluck('user_id')->all())->toBe([$lecturer->user_id]);
});

it('giảng viên không truy cập được trang quản lý lớp của admin', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');

    $this->actingAs($lecturer)
        ->get(route('admin.classes.index'))
        ->assertRedirect(route('dashboard.lecturer'));
});

it('sinh viên không truy cập được trang quản lý lớp của admin', function () {
    $student = make_user('student', 'Sinh viên B');

    $this->actingAs($student)
        ->get(route('admin.classes.index'))
        ->assertRedirect(route('user.dashboard'));
});

it('admin lọc danh sách lớp theo nhiều môn học và nhiều trạng thái', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject1 = make_subject($lecturer);
    $subject2 = make_subject($lecturer);
    $subject3 = make_subject($lecturer);

    $class1 = make_class($subject1, $lecturer);
    $class2 = make_class($subject2, $lecturer);
    $class3 = make_class($subject3, $lecturer, false);

    // Lọc theo 2 môn học
    $response = $this->actingAs($admin)->get(route('admin.classes.index', [
        'subject_ids' => [$subject1->subject_id, $subject2->subject_id],
    ]));

    $response->assertOk()
        ->assertSee($class1->class_name)
        ->assertSee($class2->class_name)
        ->assertDontSee($class3->class_name);

    // Lọc theo trạng thái (nhiều lựa chọn)
    $response = $this->actingAs($admin)->get(route('admin.classes.index', [
        'statuses' => ['locked'],
    ]));

    $response->assertOk()
        ->assertSee($class3->class_name)
        ->assertDontSee($class1->class_name);
});

it('admin vẫn lọc được theo tham số đơn (tương thích link cũ)', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject1 = make_subject($lecturer);
    $subject2 = make_subject($lecturer);

    $class1 = make_class($subject1, $lecturer);
    $class2 = make_class($subject2, $lecturer);

    $response = $this->actingAs($admin)->get(route('admin.classes.index', [
        'subject_id' => $subject1->subject_id,
    ]));

    $response->assertOk()
        ->assertSee($class1->class_name)
        ->assertDontSee($class2->class_name);
});

it('URL lớp học cũ /classes chuyển hướng admin về trang quản lý lớp', function () {
    $admin = make_user('admin', 'Admin hệ thống');

    $this->actingAs($admin)->get('/classes')
        ->assertRedirect(route('admin.classes.index'));
});

/*
|--------------------------------------------------------------------------
| Bổ sung: hiển thị nhóm của sinh viên & dọn dữ liệu khi xóa sinh viên
|--------------------------------------------------------------------------
| Trưởng nhóm KHÔNG nằm trong bảng group_members (chỉ có groups.leader_id),
| nên danh sách sinh viên phải xét cả groupsJoined lẫn groupsLed.
*/

it('admin xem chi tiết lớp hiển thị nhóm của cả trưởng nhóm và thành viên', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer);

    $leader = make_user('student', 'Sinh viên trưởng nhóm');
    $member = make_user('student', 'Sinh viên thành viên');
    $class->users()->attach([$leader->user_id, $member->user_id]);

    $group = make_group($leader, $class, 'Nhóm 01');
    $group->members()->attach($member->user_id);

    $response = $this->actingAs($admin)->get(route('admin.classes.show', $class->class_id));
    $response->assertOk();

    // Chỉ xét tab "Danh sách sinh viên" để không lẫn tên nhóm ở tab "Nhóm"
    $html = $response->getContent();
    $start = strpos($html, 'id="students"');
    $end = strpos($html, 'id="groups"');
    expect($start)->not->toBeFalse()->and($end)->not->toBeFalse();
    $studentsTab = substr($html, (int) $start, (int) $end - (int) $start);

    // Cả trưởng nhóm và thành viên đều phải thấy tên nhóm của mình
    expect(substr_count($studentsTab, $group->group_name))->toBe(2)
        ->and(substr_count($studentsTab, 'Trưởng nhóm'))->toBe(1);
});

it('admin xóa trưởng nhóm khỏi lớp thì chuyển quyền trưởng nhóm cho thành viên còn lại', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer);

    $leader = make_user('student', 'Sinh viên trưởng nhóm');
    $member = make_user('student', 'Sinh viên thành viên');
    $class->users()->attach([$leader->user_id, $member->user_id]);

    $group = make_group($leader, $class, 'Nhóm 01');
    $group->members()->attach($member->user_id);

    $response = $this->actingAs($admin)->post(
        route('admin.classes.students.remove', [$class->class_id, $leader->user_id])
    );

    $response->assertSessionHas('success');
    expect($class->students()->where('users.user_id', $leader->user_id)->exists())->toBeFalse();

    $group->refresh();
    expect($group->leader_id)->toBe($member->user_id)
        ->and($group->members()->where('users.user_id', $member->user_id)->exists())->toBeTrue();
});

it('admin xóa trưởng nhóm duy nhất khỏi lớp thì nhóm rỗng bị giải tán', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer);

    $leader = make_user('student', 'Sinh viên trưởng nhóm');
    $class->users()->attach($leader->user_id);
    $group = make_group($leader, $class, 'Nhóm 01');
    $groupId = $group->group_id;

    $response = $this->actingAs($admin)->post(
        route('admin.classes.students.remove', [$class->class_id, $leader->user_id])
    );

    $response->assertSessionHas('success');
    expect(Groups::find($groupId))->toBeNull()
        ->and($class->students()->where('users.user_id', $leader->user_id)->exists())->toBeFalse();
});

it('admin xóa sinh viên khỏi lớp này không ảnh hưởng lớp khác', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject1 = make_subject($lecturer);
    $subject2 = make_subject($lecturer);
    $class1 = make_class($subject1, $lecturer);
    $class2 = make_class($subject2, $lecturer);

    $student = make_user('student', 'Sinh viên B');
    $class1->users()->attach($student->user_id);
    $class2->users()->attach($student->user_id);

    $this->actingAs($admin)->post(
        route('admin.classes.students.remove', [$class1->class_id, $student->user_id])
    )->assertSessionHas('success');

    expect($class1->students()->where('users.user_id', $student->user_id)->exists())->toBeFalse()
        ->and($class2->students()->where('users.user_id', $student->user_id)->exists())->toBeTrue();
});

it('giảng viên không xóa được sinh viên khỏi lớp học phần', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer);
    $student = make_user('student', 'Sinh viên B');
    $class->users()->attach($student->user_id);

    $this->actingAs($lecturer)->post(
        route('admin.classes.students.remove', [$class->class_id, $student->user_id])
    )->assertRedirect(route('dashboard.lecturer'));

    expect($class->students()->where('users.user_id', $student->user_id)->exists())->toBeTrue();
});

it('admin xóa sinh viên không thuộc lớp thì chỉ cảnh báo, không đổi dữ liệu', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $subject1 = make_subject($lecturer);
    $subject2 = make_subject($lecturer);
    $class1 = make_class($subject1, $lecturer);
    $class2 = make_class($subject2, $lecturer);

    $student = make_user('student', 'Sinh viên ngoài lớp');
    $class2->users()->attach($student->user_id);

    $this->actingAs($admin)->post(
        route('admin.classes.students.remove', [$class1->class_id, $student->user_id])
    )->assertSessionHas('warning');

    expect($class2->students()->where('users.user_id', $student->user_id)->exists())->toBeTrue();
});