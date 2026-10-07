<?php

/*
|--------------------------------------------------------------------------
| L05 — Trạng thái Đang học / Đã rời lớp (user_classes.status)
| Chốt 2a — Giữ trưởng nhóm: trưởng nhóm = người CUỐI CÙNG rời lớp
|--------------------------------------------------------------------------
| Bao phủ:
|  1. Rời lớp = xóa mềm (status='left' + left_at), Admin/GV thấy badge "Đã rời lớp".
|  2. Thêm lại = khôi phục 'studying', không sinh dòng pivot trùng.
|  3. Sinh viên đã rời lớp không thấy nhóm của lớp đó (lớp khác không ảnh hưởng).
|  4. Join bằng mã lớp khi đang 'left' ⇒ khôi phục 'studying' (không lỗi unique).
|  5. Thêm/xóa sai đối tượng chỉ cảnh báo, không đổi dữ liệu.
|  6. Trưởng nhóm rời lớp ⇒ chuyển quyền cho thành viên đang học.
|  7. Người cuối cùng rời lớp ⇒ GIỮ nhóm, leader_id = người cuối cùng rời.
|  8. Trưởng nhóm của nhóm rỗng quay lại lớp ⇒ nhóm hồi sinh, không tạo nhóm mới.
|  9. Trưởng nhóm đã rời lớp không truy cập được chat nhóm / chi tiết nhóm.
| 10. Nhóm rỗng hiển thị cho Admin/GV với nhãn "Đã rời hết" và 0 thành viên.
*/

use App\Models\Groups;
use App\Models\user_class;
use App\Services\GroupService;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_user;

/**
 * Cập nhật trạng thái (sinh viên, lớp) trong bảng user_classes
 * (dùng cho test cần dựng sẵn dữ liệu "đã rời lớp").
 */
function l05SetMembershipStatus(int $classId, int $userId, string $status): void
{
    user_class::where('class_id', $classId)
        ->where('user_id', $userId)
        ->update([
            'status' => $status,
            'left_at' => $status === user_class::STATUS_LEFT ? now() : null,
        ]);
}

function l05Pivot(int $classId, int $userId): ?user_class
{
    return user_class::where('class_id', $classId)->where('user_id', $userId)->first();
}

beforeEach(function () {
    $this->admin = make_user('admin', 'Admin hệ thống');
    $this->lecturer = make_user('lecturer', 'Giảng viên A');
    $this->subject = make_subject($this->lecturer);
    $this->class = make_class($this->subject, $this->lecturer);
    $this->classId = (int) $this->class->class_id;
});

it('rời lớp là xóa mềm: ghi status=left + left_at và Admin thấy badge Đã rời lớp', function () {
    $student = make_user('student', 'Sinh viên B');
    $this->class->users()->attach($student->user_id);

    $this->actingAs($this->admin)->post(
        route('admin.classes.students.remove', [$this->classId, $student->user_id])
    )->assertSessionHas('success');

    $pivot = l05Pivot($this->classId, $student->user_id);
    expect($pivot)->not->toBeNull()
        ->and($pivot->status)->toBe(user_class::STATUS_LEFT)
        ->and($pivot->left_at)->not->toBeNull();

    expect($this->class->students()->where('users.user_id', $student->user_id)->exists())->toBeFalse()
        ->and($this->class->allStudents()->where('users.user_id', $student->user_id)->exists())->toBeTrue();

    $this->actingAs($this->admin)->get(route('admin.classes.show', $this->classId))
        ->assertOk()
        ->assertSee('Đã rời lớp');
});

it('thêm lại sinh viên đã rời khôi phục studying và không sinh dòng pivot trùng', function () {
    $student = make_user('student', 'Sinh viên C');
    $this->class->users()->attach($student->user_id);

    $this->actingAs($this->admin)->post(
        route('admin.classes.students.remove', [$this->classId, $student->user_id])
    )->assertSessionHas('success');

    $pivotCount = user_class::where('class_id', $this->classId)->where('user_id', $student->user_id)->count();

    $this->actingAs($this->admin)->post(
        route('admin.classes.students.add', $this->classId),
        ['student_ids' => [$student->user_id]]
    )->assertSessionHas('success');

    $pivot = l05Pivot($this->classId, $student->user_id);
    expect($pivot->status)->toBe(user_class::STATUS_STUDYING)
        ->and($pivot->left_at)->toBeNull()
        ->and(user_class::where('class_id', $this->classId)->where('user_id', $student->user_id)->count())->toBe($pivotCount)
        ->and($this->class->students()->where('users.user_id', $student->user_id)->exists())->toBeTrue();
});


it('sinh viên đã rời lớp không thấy nhóm của lớp đó nhưng lớp khác không ảnh hưởng', function () {
    $student = make_user('student', 'Sinh viên D');
    $this->class->users()->attach($student->user_id);
    $groupLeft = make_group($student, $this->class, 'Nhóm lớp đã rời');

    $subject2 = make_subject($this->lecturer);
    $class2 = make_class($subject2, $this->lecturer);
    $class2->users()->attach($student->user_id);
    $groupOther = make_group($student, $class2, 'Nhóm lớp còn học');

    l05SetMembershipStatus($this->classId, $student->user_id, user_class::STATUS_LEFT);

    $this->actingAs($student)->get(route('user.my_groups'))
        ->assertOk()
        ->assertDontSee($groupLeft->group_name)
        ->assertSee($groupOther->group_name);

    // Truy cập trực tiếp nhóm của lớp đã rời -> bị chặn
    $this->actingAs($student)->get(route('user.group_detail', $groupLeft->group_id))
        ->assertForbidden();
});

it('sinh viên từng rời lớp join lại bằng mã thì khôi phục studying', function () {
    $student = make_user('student', 'Sinh viên E');
    $this->class->users()->attach($student->user_id);
    l05SetMembershipStatus($this->classId, $student->user_id, user_class::STATUS_LEFT);

    $this->actingAs($student)->post(route('user.join-class'), [
        'class_code' => $this->class->class_code,
    ])->assertSessionHas('success');

    $pivot = l05Pivot($this->classId, $student->user_id);
    expect($pivot->status)->toBe(user_class::STATUS_STUDYING)
        ->and($pivot->left_at)->toBeNull()
        ->and(user_class::where('class_id', $this->classId)->where('user_id', $student->user_id)->count())->toBe(1);
});

it('xóa sinh viên không thuộc lớp chỉ cảnh báo và không đổi dữ liệu', function () {
    $outsider = make_user('student', 'Sinh viên ngoài lớp');
    $subject2 = make_subject($this->lecturer);
    $class2 = make_class($subject2, $this->lecturer);
    $class2->users()->attach($outsider->user_id);

    $this->actingAs($this->admin)->post(
        route('admin.classes.students.remove', [$this->classId, $outsider->user_id])
    )->assertSessionHas('warning');

    expect(l05Pivot($class2->class_id, $outsider->user_id)->status)->toBe(user_class::STATUS_STUDYING);
});

it('thêm sinh viên đã ở trong lớp chỉ cảnh báo và không sinh dòng trùng', function () {
    $student = make_user('student', 'Sinh viên F');
    $this->class->users()->attach($student->user_id);

    $this->actingAs($this->admin)->post(
        route('admin.classes.students.add', $this->classId),
        ['student_ids' => [$student->user_id]]
    )->assertSessionHas('warning');

    expect(user_class::where('class_id', $this->classId)->where('user_id', $student->user_id)->count())->toBe(1);
});

it('giảng viên cho sinh viên rời lớp = xóa mềm và thêm lại được (không lỗi trùng unique)', function () {
    $student = make_user('student', 'Sinh viên G');
    $this->class->users()->attach($student->user_id);

    $this->actingAs($this->lecturer)->post(
        route('lecturer.classes.students.remove', [$this->classId, $student->user_id])
    )->assertSessionHas('success');

    expect(l05Pivot($this->classId, $student->user_id)->status)->toBe(user_class::STATUS_LEFT)
        ->and($this->class->students()->where('users.user_id', $student->user_id)->exists())->toBeFalse();

    // Thêm lại: phải khôi phục 'studying' thay vì insert trùng (unique user_id + class_id)
    $this->actingAs($this->lecturer)->post(
        route('lecturer.classes.students.add', $this->classId),
        ['student_ids' => [$student->user_id]]
    )->assertSessionHas('success');

    $pivot = l05Pivot($this->classId, $student->user_id);
    expect($pivot->status)->toBe(user_class::STATUS_STUDYING)
        ->and($pivot->left_at)->toBeNull()
        ->and(user_class::where('class_id', $this->classId)->where('user_id', $student->user_id)->count())->toBe(1);
});

it('trưởng nhóm rời lớp thì chuyển quyền cho thành viên đang học', function () {
    $leader = make_user('student', 'Trưởng nhóm H');
    $member = make_user('student', 'Thành viên I');
    $this->class->users()->attach([$leader->user_id, $member->user_id]);

    $group = make_group($leader, $this->class, 'Nhóm 01');
    $group->members()->attach($member->user_id);

    $this->actingAs($this->admin)->post(
        route('admin.classes.students.remove', [$this->classId, $leader->user_id])
    )->assertSessionHas('success');

    $group->refresh();
    expect($group->leader_id)->toBe($member->user_id)
        ->and($group->activeMemberCount())->toBe(1);
});

it('người cuối cùng rời lớp: giữ nhóm và leader_id = người cuối cùng rời', function () {
    $leader = make_user('student', 'Trưởng nhóm J');
    $member = make_user('student', 'Thành viên K');
    $this->class->users()->attach([$leader->user_id, $member->user_id]);

    $group = make_group($leader, $this->class, 'Nhóm 02');
    $group->members()->attach($member->user_id);

    // Thành viên rời trước
    $this->actingAs($this->admin)->post(
        route('admin.classes.students.remove', [$this->classId, $member->user_id])
    )->assertSessionHas('success');

    expect((new GroupService)->memberCount($group->fresh()))->toBe(1);

    // Trưởng nhóm rời sau (người cuối cùng)
    $this->actingAs($this->admin)->post(
        route('admin.classes.students.remove', [$this->classId, $leader->user_id])
    )->assertSessionHas('success');

    $group->refresh();
    expect(Groups::find($group->group_id))->not->toBeNull()
        ->and($group->leader_id)->toBe($leader->user_id)
        ->and((new GroupService)->memberCount($group))->toBe(0)
        ->and((new GroupService)->isGhost($group))->toBeTrue()
        ->and($group->status)->toBe('incomplete');
});

it('trưởng nhóm của nhóm rỗng quay lại lớp thì nhóm hồi sinh, không tạo nhóm mới', function () {
    $leader = make_user('student', 'Trưởng nhóm L');
    $this->class->users()->attach($leader->user_id);
    $group = make_group($leader, $this->class, 'Nhóm 03');

    $this->actingAs($this->admin)->post(
        route('admin.classes.students.remove', [$this->classId, $leader->user_id])
    )->assertSessionHas('success');

    expect((new GroupService)->memberCount($group->fresh()))->toBe(0);

    // Quay lại lớp bằng mã lớp
    $this->actingAs($leader)->post(route('user.join-class'), [
        'class_code' => $this->class->class_code,
    ])->assertSessionHas('success');

    $group->refresh();
    expect($group->leader_id)->toBe($leader->user_id)
        ->and($group->activeMemberCount())->toBe(1)
        // Không sinh nhóm mới trong lớp
        ->and(Groups::where('class_id', $this->classId)->count())->toBe(1)
        // Vẫn được coi là đã có nhóm trong lớp ⇒ không tạo nhóm mới được
        ->and(app(GroupService::class)->hasGroupInClass($leader->fresh(), $this->classId))->toBeTrue();
});

it('trưởng nhóm đã rời lớp không truy cập được chat nhóm và chi tiết nhóm', function () {
    $leader = make_user('student', 'Trưởng nhóm M');
    $this->class->users()->attach($leader->user_id);
    $group = make_group($leader, $this->class, 'Nhóm 04');

    $this->actingAs($this->admin)->post(
        route('admin.classes.students.remove', [$this->classId, $leader->user_id])
    )->assertSessionHas('success');

    $this->actingAs($leader)->get(route('groups.chat.show', $group->group_id))->assertForbidden();
    $this->actingAs($leader)->get(route('user.group_detail', $group->group_id))->assertForbidden();
});

it('Admin và giảng viên thấy nhóm rỗng với nhãn Đã rời hết', function () {
    $leader = make_user('student', 'Trưởng nhóm N');
    $this->class->users()->attach($leader->user_id);
    $group = make_group($leader, $this->class, 'Nhóm 05');

    $this->actingAs($this->admin)->post(
        route('admin.classes.students.remove', [$this->classId, $leader->user_id])
    )->assertSessionHas('success');

    $this->actingAs($this->admin)->get(route('admin.classes.show', $this->classId))
        ->assertOk()
        ->assertSee($group->group_name)
        ->assertSee('Đã rời hết');

    $this->actingAs($this->lecturer)->get(route('lecturer.classes.show', $this->classId))
        ->assertOk()
        ->assertSee('Nhóm không còn thành viên');
});

