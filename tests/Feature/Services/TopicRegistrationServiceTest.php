<?php

use App\Models\Groups;
use App\Models\Topic_requests;
use App\Services\GroupService;
use App\Services\TopicRegistrationService;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_topic;
use function Tests\Support\make_user;

beforeEach(function () {
    $this->groups = app(GroupService::class);
    $this->service = app(TopicRegistrationService::class);
    $this->lecturer = make_user('lecturer', 'Giảng viên A');
    $this->subject = make_subject($this->lecturer);
    $this->class = make_class($this->subject, $this->lecturer);
    $this->topic = make_topic($this->class, $this->subject, 2, 4);
    $this->leader = make_user('student', 'Trưởng nhóm B');
    $this->group = make_group($this->leader, $this->class);
    $this->groups->addMember($this->group, make_user('student', 'Thành viên C')); // đủ 2 thành viên
});

it('trưởng nhóm đăng ký đề tài thành công khi đủ thành viên', function () {
    $result = $this->service->register($this->group, $this->topic, $this->leader);

    expect($result->succeeded())->toBeTrue()
        ->and(Topic_requests::where('group_id', $this->group->group_id)
            ->where('topic_id', $this->topic->topic_id)
            ->where('status', 'Pending')->exists())->toBeTrue();
});

it('thành viên thường không được đăng ký đề tài', function () {
    $member = make_user('student', 'Thành viên D');
    $this->groups->addMember($this->group, $member);

    $result = $this->service->register($this->group, $this->topic, $member);

    expect($result->succeeded())->toBeFalse();
});

it('nhóm chưa đủ thành viên tối thiểu thì không được đăng ký', function () {
    $soloLeader = make_user('student', 'Trưởng nhóm E');
    $soloGroup = make_group($soloLeader, $this->class); // chỉ có 1 thành viên

    $result = $this->service->register($soloGroup, $this->topic, $soloLeader);

    expect($result->succeeded())->toBeFalse();
});

it('không thể đăng ký đề tài của lớp khác', function () {
    $otherClass = make_class($this->subject, $this->lecturer);
    $otherTopic = make_topic($otherClass, $this->subject, 1, 3);

    $result = $this->service->register($this->group, $otherTopic, $this->leader);

    expect($result->succeeded())->toBeFalse();
});

it('không thể đăng ký khi đã quá hạn đăng ký đề tài', function () {
    $expiredTopic = make_topic($this->class, $this->subject, 2, 4, now()->subDay());

    $result = $this->service->register($this->group, $expiredTopic, $this->leader);

    expect($result->succeeded())->toBeFalse();
});

it('không thể đăng ký thêm đề tài khi nhóm đã có đề tài được duyệt', function () {
    Topic_requests::create([
        'topic_id' => $this->topic->topic_id,
        'group_id' => $this->group->group_id,
        'created_by' => $this->leader->user_id,
        'status' => 'Accepted',
    ]);
    $topic2 = make_topic($this->class, $this->subject, 2, 4);

    $result = $this->service->register($this->group, $topic2, $this->leader);

    expect($result->succeeded())->toBeFalse();
});

it('không thể đăng ký đề tài đã được gán cho nhóm khác', function () {
    $otherLeader = make_user('student', 'Trưởng nhóm F');
    $otherGroup = make_group($otherLeader, $this->class);
    $this->topic->update(['assigned_group_id' => $otherGroup->group_id]);

    $result = $this->service->register($this->group, $this->topic, $this->leader);

    expect($result->succeeded())->toBeFalse();
});

it('yêu cầu bị từ chối trước đó thì có thể gửi lại', function () {
    $request = Topic_requests::create([
        'topic_id' => $this->topic->topic_id,
        'group_id' => $this->group->group_id,
        'created_by' => $this->leader->user_id,
        'status' => 'Rejected',
        'rejection_reason' => 'Lý do test',
    ]);

    $result = $this->service->register($this->group, $this->topic, $this->leader);

    expect($result->succeeded())->toBeTrue()
        ->and($request->fresh()->status)->toBe('Pending');
});

it('không tạo yêu cầu trùng khi đã có yêu cầu đang chờ', function () {
    $this->service->register($this->group, $this->topic, $this->leader);

    $second = $this->service->register($this->group, $this->topic, $this->leader);

    expect($second->succeeded())->toBeFalse()
        ->and(Topic_requests::where('group_id', $this->group->group_id)
            ->where('status', 'Pending')->count())->toBe(1);
});

it('giảng viên duyệt yêu cầu: gán đề tài và tự động từ chối yêu cầu khác', function () {
    // Nhóm 1 đăng ký đề tài A
    $this->service->register($this->group, $this->topic, $this->leader);
    $request1 = Topic_requests::where('group_id', $this->group->group_id)->first();

    // Nhóm 2 cũng đăng ký đề tài A
    $leader2 = make_user('student', 'Trưởng nhóm G');
    $group2 = make_group($leader2, $this->class);
    $this->groups->addMember($group2, make_user('student', 'Thành viên H'));
    $this->service->register($group2, $this->topic, $leader2);
    $request2 = Topic_requests::where('group_id', $group2->group_id)->first();

    // Giảng viên duyệt nhóm 1
    $result = $this->service->approve($request1, $this->lecturer);

    expect($result->succeeded())->toBeTrue()
        ->and($request1->fresh()->status)->toBe('Accepted')
        ->and($this->group->fresh()->topic_id)->toBe($this->topic->topic_id)
        ->and($this->topic->fresh()->assigned_group_id)->toBe($this->group->group_id)
        ->and($request2->fresh()->status)->toBe('Rejected');
});

it('giảng viên từ chối yêu cầu kèm lý do', function () {
    $this->service->register($this->group, $this->topic, $this->leader);
    $request = Topic_requests::where('group_id', $this->group->group_id)->first();

    $result = $this->service->reject($request, $this->lecturer, 'Nhóm chưa đủ năng lực');

    expect($result->succeeded())->toBeTrue()
        ->and($request->fresh()->status)->toBe('Rejected')
        ->and($request->fresh()->rejection_reason)->toBe('Nhóm chưa đủ năng lực');
});

it('thành viên thường không được duyệt/từ chối yêu cầu', function () {
    $member = make_user('student', 'Thành viên I');
    $this->groups->addMember($this->group, $member);
    $this->service->register($this->group, $this->topic, $this->leader);
    $request = Topic_requests::where('group_id', $this->group->group_id)->first();

    expect($this->service->approve($request, $member)->succeeded())->toBeFalse();
    expect($this->service->reject($request, $member)->succeeded())->toBeFalse();
});

it('không thể duyệt/từ chối yêu cầu đã được xử lý', function () {
    $this->service->register($this->group, $this->topic, $this->leader);
    $request = Topic_requests::where('group_id', $this->group->group_id)->first();
    $this->service->reject($request, $this->lecturer, 'Lý do');

    expect($this->service->approve($request, $this->lecturer)->succeeded())->toBeFalse();
    expect($this->service->reject($request, $this->lecturer)->succeeded())->toBeFalse();
});

it('trưởng nhóm hủy yêu cầu khi chưa được duyệt', function () {
    $this->service->register($this->group, $this->topic, $this->leader);
    $request = Topic_requests::where('group_id', $this->group->group_id)->first();

    $result = $this->service->cancel($request, $this->leader);

    expect($result->succeeded())->toBeTrue()
        ->and($request->fresh()->status)->toBe('Cancelled');
});

it('giảng viên trực tiếp gán đề tài cho nhóm', function () {
    $result = $this->service->assignDirectly($this->group, $this->topic, $this->lecturer);

    expect($result->succeeded())->toBeTrue()
        ->and($this->group->fresh()->topic_id)->toBe($this->topic->topic_id)
        ->and($this->topic->fresh()->assigned_group_id)->toBe($this->group->group_id);
});

it('không gán đề tài đã được gán cho nhóm khác', function () {
    $otherLeader = make_user('student', 'Trưởng nhóm J');
    $otherGroup = make_group($otherLeader, $this->class);
    $this->topic->update(['assigned_group_id' => $otherGroup->group_id]);

    $result = $this->service->assignDirectly($this->group, $this->topic, $this->lecturer);

    expect($result->succeeded())->toBeFalse();
});

