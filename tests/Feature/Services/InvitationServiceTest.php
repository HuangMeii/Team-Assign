<?php

use App\Models\Invites;
use App\Models\Join_Requests;
use App\Services\GroupService;
use App\Services\InvitationService;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_topic;
use function Tests\Support\make_user;

beforeEach(function () {
    $this->groups = app(GroupService::class);
    $this->invitations = app(InvitationService::class);
    $this->lecturer = make_user('lecturer', 'Giảng viên A');
    $this->subject = make_subject($this->lecturer);
    $this->class = make_class($this->subject, $this->lecturer);
    $this->topic = make_topic($this->class, $this->subject, 2, 3);
    $this->leader = make_user('student', 'Trưởng nhóm B');
    $this->group = make_group($this->leader, $this->class);
});

it('trưởng nhóm gửi lời mời thành công', function () {
    $member = make_user('student', 'Thành viên C');

    $result = $this->invitations->sendInvite($this->group, $this->leader, $member->user_id);

    expect($result->succeeded())->toBeTrue()
        ->and(Invites::where('group_id', $this->group->group_id)
            ->where('member_id', $member->user_id)
            ->where('status', 'Pending')->exists())->toBeTrue();
});

it('thành viên thường không được gửi lời mời', function () {
    $member = make_user('student', 'Thành viên D');
    $this->groups->addMember($this->group, $member);
    $target = make_user('student', 'Thành viên E');

    $result = $this->invitations->sendInvite($this->group, $member, $target->user_id);

    expect($result->succeeded())->toBeFalse();
});

it('không thể mời khi nhóm đã đủ thành viên tối đa', function () {
    $member1 = make_user('student', 'Thành viên F');
    $member2 = make_user('student', 'Thành viên G');
    $this->groups->addMember($this->group, $member1);
    $this->groups->addMember($this->group, $member2); // max = 3, nhóm đã đủ (leader + 2)
    $target = make_user('student', 'Thành viên H');

    $result = $this->invitations->sendInvite($this->group, $this->leader, $target->user_id);

    expect($result->succeeded())->toBeFalse();
});

it('không gửi lời mời vượt quá số chỗ còn thiếu', function () {
    $member1 = make_user('student', 'Thành viên I');
    $this->groups->addMember($this->group, $member1); // còn 1 chỗ (max=3, hiện 2 người)

    $target1 = make_user('student', 'Thành viên J');
    $target2 = make_user('student', 'Thành viên K');

    $first = $this->invitations->sendInvite($this->group, $this->leader, $target1->user_id);
    expect($first->succeeded())->toBeTrue();

    $second = $this->invitations->sendInvite($this->group, $this->leader, $target2->user_id);
    expect($second->succeeded())->toBeFalse();
});

it('không thể mời sinh viên đã tham gia nhóm khác', function () {
    $otherLeader = make_user('student', 'Trưởng nhóm L');
    $otherGroup = make_group($otherLeader, $this->class);
    $this->groups->addMember($otherGroup, $otherLeader);

    $result = $this->invitations->sendInvite($this->group, $this->leader, $otherLeader->user_id);

    expect($result->succeeded())->toBeFalse();
});

it('không gửi trùng lời mời đang chờ cho cùng một sinh viên', function () {
    $member = make_user('student', 'Thành viên M');

    $this->invitations->sendInvite($this->group, $this->leader, $member->user_id);
    $second = $this->invitations->sendInvite($this->group, $this->leader, $member->user_id);

    expect($second->succeeded())->toBeFalse();
});

it('sinh viên chấp nhận lời mời thì gia nhập nhóm và đánh dấu đã có nhóm', function () {
    $member = make_user('student', 'Thành viên N');
    $invite = $this->invitations->sendInvite($this->group, $this->leader, $member->user_id)->data();

    $result = $this->invitations->acceptInvite($invite, $member);

    expect($result->succeeded())->toBeTrue()
        ->and($invite->fresh()->status)->toBe('Accepted')
        ->and($this->groups->isInGroup($this->group, $member->user_id))->toBeTrue()
        ->and($member->fresh()->is_have_group)->toBeTrue();
});

it('chấp nhận lời mời khi nhóm đã đủ -> lời mời hết hiệu lực (Expired)', function () {
    $member1 = make_user('student', 'Thành viên O');
    $member2 = make_user('student', 'Thành viên P');
    $this->groups->addMember($this->group, $member1); // nhóm có 2 người

    // Gửi lời mời khi còn 1 chỗ
    $member3 = make_user('student', 'Thành viên Q');
    $this->invitations->sendInvite($this->group, $this->leader, $member3->user_id);

    // Lấp đầy nhóm
    $this->groups->addMember($this->group, $member2); // đủ max = 3

    $invite = Invites::where('group_id', $this->group->group_id)
        ->where('member_id', $member3->user_id)->first();

    $result = $this->invitations->acceptInvite($invite, $member3);

    expect($result->succeeded())->toBeFalse()
        ->and($invite->fresh()->status)->toBe('Expired');
});

it('sinh viên gửi yêu cầu tham gia nhóm', function () {
    $member = make_user('student', 'Thành viên R');

    $result = $this->invitations->sendJoinRequest($this->group, $member);

    expect($result->succeeded())->toBeTrue()
        ->and(Join_Requests::where('group_id', $this->group->group_id)
            ->where('member_id', $member->user_id)
            ->where('status', 'Pending')->exists())->toBeTrue();
});

it('trưởng nhóm chấp nhận yêu cầu tham gia', function () {
    $member = make_user('student', 'Thành viên S');
    $this->invitations->sendJoinRequest($this->group, $member);
    $joinRequest = Join_Requests::where('group_id', $this->group->group_id)
        ->where('member_id', $member->user_id)->first();

    $result = $this->invitations->approveJoinRequest($joinRequest, $this->leader);

    expect($result->succeeded())->toBeTrue()
        ->and($joinRequest->fresh()->status)->toBe('Accepted')
        ->and($this->groups->isInGroup($this->group, $member->user_id))->toBeTrue()
        ->and($member->fresh()->is_have_group)->toBeTrue();
});

it('chấp nhận yêu cầu khi nhóm đã đủ -> yêu cầu hết hiệu lực (Expired)', function () {
    $member1 = make_user('student', 'Thành viên T');
    $member2 = make_user('student', 'Thành viên U');
    $this->groups->addMember($this->group, $member1);
    $this->groups->addMember($this->group, $member2); // đầy max=3

    $member3 = make_user('student', 'Thành viên V');
    $joinRequest = Join_Requests::create([
        'group_id' => $this->group->group_id,
        'member_id' => $member3->user_id,
        'status' => 'Pending',
    ]);

    $result = $this->invitations->approveJoinRequest($joinRequest, $this->leader);

    expect($result->succeeded())->toBeFalse()
        ->and($joinRequest->fresh()->status)->toBe('Expired');
});

it('trưởng nhóm từ chối yêu cầu tham gia', function () {
    $member = make_user('student', 'Thành viên W');
    $this->invitations->sendJoinRequest($this->group, $member);
    $joinRequest = Join_Requests::where('group_id', $this->group->group_id)
        ->where('member_id', $member->user_id)->first();

    $result = $this->invitations->rejectJoinRequest($joinRequest, $this->leader);

    expect($result->succeeded())->toBeTrue()
        ->and($joinRequest->fresh()->status)->toBe('Rejected');
});

