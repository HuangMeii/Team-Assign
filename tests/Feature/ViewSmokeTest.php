<?php

use App\Services\GroupService;

use function Tests\Support\make_class;
use function Tests\Support\make_subject;
use function Tests\Support\make_topic;
use function Tests\Support\make_user;

beforeEach(function () {
    $this->groups = app(GroupService::class);
    $this->lecturer = make_user('lecturer', 'Giảng viên A');
    $this->subject = make_subject($this->lecturer);
    $this->class = make_class($this->subject, $this->lecturer);
    $this->topic = make_topic($this->class, $this->subject, 2, 4);
    $this->leader = make_user('student', 'Trưởng nhóm B');
    $this->group = $this->groups->createGroupByStudent($this->leader, 'Nhóm Alpha', $this->class->class_id)->data();
    $this->groups->addMember($this->group, make_user('student', 'Thành viên C'));
    $this->student = make_user('student', 'Sinh viên D');
    $this->student->classes()->attach($this->class->class_id);
});

it('dashboard hiển thị cho sinh viên', function () {
    $this->actingAs($this->student)->get(route('user.dashboard'))->assertOk();
});

it('danh sách đề tài hiển thị', function () {
    $this->actingAs($this->student)->get(route('user.topics'))->assertOk();
});

it('chi tiết đề tài hiển thị', function () {
    $this->actingAs($this->student)->get(route('user.topic_detail', $this->topic->topic_id))->assertOk();
});

it('danh sách nhóm còn thiếu người hiển thị', function () {
    $this->actingAs($this->student)->get(route('user.available_groups'))->assertOk();
});

it('chi tiết nhóm hiển thị cho trưởng nhóm', function () {
    $this->actingAs($this->leader)->get(route('user.group_detail', $this->group->group_id))->assertOk();
});

it('nhóm của tôi hiển thị cho trưởng nhóm', function () {
    $this->actingAs($this->leader)->get(route('user.my_groups'))->assertOk();
});

it('tìm đề tài cho nhóm hiển thị', function () {
    $this->actingAs($this->leader)->get(route('user.group_topics', $this->group->group_id))->assertOk();
});

it('trang mời thành viên hiển thị', function () {
    $this->actingAs($this->leader)->get(route('user.invite-member', $this->group->group_id))->assertOk();
});

it('đề tài của tôi hiển thị', function () {
    $this->actingAs($this->leader)->get(route('user.my_topics'))->assertOk();
});

it('danh sách lớp học hiển thị', function () {
    $this->actingAs($this->student)->get(route('user.classes'))->assertOk();
});

it('form gửi thông báo admin hiển thị', function () {
    $admin = make_user('admin', 'Admin hệ thống');

    $this->actingAs($admin)->get(route('admin.notifications.create'))->assertOk();
});
