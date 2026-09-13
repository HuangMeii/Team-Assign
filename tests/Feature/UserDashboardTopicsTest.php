<?php

use function Tests\Support\make_class;
use function Tests\Support\make_subject;
use function Tests\Support\make_topic;
use function Tests\Support\make_user;

/*
 * Kiểm thử lọc đề tài sinh viên (bug A4 - R57):
 * - Sinh viên chỉ thấy đề tài của lớp học phần mình tham gia
 * - Cùng 1 môn có nhiều lớp → không hiển đề tài của lớp khác cùng môn
 */

beforeEach(function () {
    $this->student = make_user('student', 'Sinh viên Test');
    $this->lecturer = make_user('lecturer', 'Giảng viên A');
    $this->subject = make_subject($this->lecturer);

    // Hai lớp học phần cùng môn
    $this->myClass = make_class($this->subject, $this->lecturer);
    $this->otherClass = make_class($this->subject, $this->lecturer);

    // Sinh viên tham gia chỉ lớp đầu
    $this->myClass->users()->attach($this->student->user_id);
});

it('sinh viên thấy đề tài của lớp học phần mình tham gia', function () {
    $myTopic = make_topic($this->myClass, $this->subject, 2, 4);

    $response = $this->actingAs($this->student)->get(route('user.topics'));

    $response->assertStatus(200)
        ->assertSee($myTopic->name);
});

it('sinh viên không thấy đề tài của lớp khác cùng môn', function () {
    $myTopic = make_topic($this->myClass, $this->subject, 2, 4);
    $otherTopic = make_topic($this->otherClass, $this->subject, 2, 4);

    $response = $this->actingAs($this->student)->get(route('user.topics'));

    $response->assertStatus(200)
        ->assertSee($myTopic->name)
        ->assertDontSee($otherTopic->name);
});