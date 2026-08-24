<?php

use App\Models\Groups;
use App\Services\GroupService;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_topic;
use function Tests\Support\make_user;

beforeEach(function () {
    $this->groupService = app(GroupService::class);
    $this->lecturer = make_user('lecturer', 'Giảng viên A');
    $this->subject = make_subject($this->lecturer);
    $this->class = make_class($this->subject, $this->lecturer);
    $this->topic = make_topic($this->class, $this->subject, 2, 4);
});

it('sinh viên tạo nhóm thành công và được chuyển vai trò thành trưởng nhóm', function () {
    $student = make_user('student', 'Sinh viên B');

    $result = $this->groupService->createGroupByStudent($student, 'Nhóm Alpha', $this->class->class_id);

    expect($result->succeeded())->toBeTrue()
        ->and(Groups::where('leader_id', $student->user_id)->exists())->toBeTrue()
        ->and($student->fresh()->role)->toBe('leader')
        ->and($student->fresh()->is_have_group)->toBeTrue()
        ->and($this->groupService->memberCount($result->data()))->toBe(1);
});

it('sinh viên đã thuộc nhóm khác thì không thể tạo nhóm mới', function () {
    $student = make_user('student', 'Sinh viên C');
    $this->groupService->createGroupByStudent($student, 'Nhóm cũ', $this->class->class_id);

    $result = $this->groupService->createGroupByStudent($student, 'Nhóm mới', $this->class->class_id);

    expect($result->succeeded())->toBeFalse();
});

it('không thể tạo nhóm trong lớp đã bị khóa', function () {
    $lockedClass = make_class($this->subject, $this->lecturer, false);
    $student = make_user('student', 'Sinh viên D');

    $result = $this->groupService->createGroupByStudent($student, 'Nhóm trong lớp khóa', $lockedClass->class_id);

    expect($result->succeeded())->toBeFalse();
});

it('giảng viên tạo nhóm và chỉ định trưởng nhóm là sinh viên (phân quyền bổ sung)', function () {
    $student = make_user('student', 'Sinh viên E');

    $result = $this->groupService->createGroupByLecturer(
        $this->lecturer,
        'Nhóm do GV tạo',
        $this->class->class_id,
        $student->user_id
    );

    expect($result->succeeded())->toBeTrue()
        ->and($student->fresh()->role)->toBe('leader');
});

it('giảng viên không thể chỉ định giảng viên khác làm trưởng nhóm', function () {
    $otherLecturer = make_user('lecturer', 'Giảng viên F');

    $result = $this->groupService->createGroupByLecturer(
        $this->lecturer,
        'Nhóm sai',
        $this->class->class_id,
        $otherLecturer->user_id
    );

    expect($result->succeeded())->toBeFalse();
});

it('không thể xóa nhóm đã được gán đề tài', function () {
    $student = make_user('student', 'Sinh viên G');
    $group = $this->groupService->createGroupByStudent($student, 'Nhóm có đề tài', $this->class->class_id)->data();
    $group->update(['topic_id' => $this->topic->topic_id]);

    $result = $this->groupService->destroy($group, $this->lecturer);

    expect($result->succeeded())->toBeFalse();
});

it('đếm thành viên nhóm bao gồm cả trưởng nhóm', function () {
    $leader = make_user('student', 'Trưởng nhóm H');
    $member1 = make_user('student', 'Thành viên I');
    $member2 = make_user('student', 'Thành viên J');
    $group = make_group($leader, $this->class);

    $this->groupService->addMember($group, $member1);
    $this->groupService->addMember($group, $member2);

    expect($this->groupService->memberCount($group))->toBe(3)
        ->and($this->groupService->maxMembers($group))->toBe(4)
        ->and($this->groupService->minMembers($group))->toBe(2)
        ->and($this->groupService->isEligibleForRegistration($group))->toBeTrue()
        ->and($this->groupService->isFull($group))->toBeFalse();
});

it('cập nhật trạng thái nhóm: complete khi đạt tối thiểu, incomplete khi chưa đạt', function () {
    $leader = make_user('student', 'Trưởng nhóm K');
    $group = make_group($leader, $this->class);

    $this->groupService->updateStatus($group);
    expect($group->fresh()->status)->toBe('incomplete');

    $this->groupService->addMember($group, make_user('student', 'Thành viên L'));
    $this->groupService->updateStatus($group);
    expect($group->fresh()->status)->toBe('complete');
});
