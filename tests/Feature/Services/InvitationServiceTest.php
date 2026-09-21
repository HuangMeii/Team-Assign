<?php

use App\Events\JoinRequestCreated;
use App\Events\JoinRequestResolved;
use App\Models\Invites;
use App\Models\Join_Requests;
use App\Services\GroupService;
use App\Services\InvitationService;
use Illuminate\Support\Facades\Event;

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
        ->and($member->fresh()->has_group)->toBeTrue();
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
        ->and($member->fresh()->has_group)->toBeTrue();
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

/*
|--------------------------------------------------------------------------
| Quy tắc "MỖI LỚP 1 NHÓM" (PA1): 1 sinh viên có thể ở nhiều lớp,
| nhưng trong cùng 1 lớp chỉ được thuộc 1 nhóm.
| Guard dùng GroupService::hasGroupInClass (cột isHaveGroup đã bị xoá).
|--------------------------------------------------------------------------
*/

it('sinh viên đã có nhóm CÙNG LỚP thì không gửi được yêu cầu vào nhóm khác cùng lớp', function () {
    $member = make_user('student', 'Thành viên X');
    $otherLeader = make_user('student', 'Trưởng nhóm X2');
    $otherGroup = make_group($otherLeader, $this->class); // cùng lớp
    $this->groups->addMember($otherGroup, $member);

    $result = $this->invitations->sendJoinRequest($this->group, $member);

    expect($result->succeeded())->toBeFalse()
        ->and(Join_Requests::where('group_id', $this->group->group_id)
            ->where('member_id', $member->user_id)->exists())->toBeFalse();
});

it('sinh viên đã có nhóm ở LỚP KHÁC vẫn gửi được yêu cầu (1 sinh viên nhiều lớp)', function () {
    $member = make_user('student', 'Thành viên Y');
    $otherClass = make_class($this->subject, $this->lecturer);
    $otherLeader = make_user('student', 'Trưởng nhóm Y2');
    $otherGroup = make_group($otherLeader, $otherClass); // lớp khác
    $this->groups->addMember($otherGroup, $member);

    $result = $this->invitations->sendJoinRequest($this->group, $member);

    expect($result->succeeded())->toBeTrue()
        ->and(Join_Requests::where('group_id', $this->group->group_id)
            ->where('member_id', $member->user_id)
            ->where('status', 'Pending')->exists())->toBeTrue();
});

it('không chấp nhận được lời mời vào nhóm thứ 2 trong CÙNG LỚP', function () {
    $member = make_user('student', 'Thành viên Z');
    $otherLeader = make_user('student', 'Trưởng nhóm Z2');
    $otherGroup = make_group($otherLeader, $this->class);
    $this->groups->addMember($otherGroup, $member);

    // Lời mời đang chờ (tạo trực tiếp để test đúng guard của acceptInvite)
    $invite = Invites::create([
        'group_id'  => $this->group->group_id,
        'member_id' => $member->user_id,
        'invitedBy' => $this->leader->user_id,
        'status'    => 'Pending',
    ]);

    $result = $this->invitations->acceptInvite($invite, $member);

    expect($result->succeeded())->toBeFalse()
        ->and($invite->fresh()->status)->toBe('Pending')
        ->and($this->groups->isInGroup($this->group, $member->user_id))->toBeFalse();
});

it('vẫn mời được sinh viên đã có nhóm ở LỚP KHÁC', function () {
    $member = make_user('student', 'Thành viên Z3');
    $otherClass = make_class($this->subject, $this->lecturer);
    $otherLeader = make_user('student', 'Trưởng nhóm Z4');
    $otherGroup = make_group($otherLeader, $otherClass);
    $this->groups->addMember($otherGroup, $member);

    $result = $this->invitations->sendInvite($this->group, $this->leader, $member->user_id);

    expect($result->succeeded())->toBeTrue();
});

it('gửi yêu cầu tham gia nhóm sẽ dispatch event JoinRequestCreated (badge realtime)', function () {
    Event::fake([JoinRequestCreated::class]);

    $member = make_user('student', 'Thành viên Z5');

    $result = $this->invitations->sendJoinRequest($this->group, $member);

    expect($result->succeeded())->toBeTrue();

    Event::assertDispatched(JoinRequestCreated::class, function ($event) use ($member) {
        return (int) $event->joinRequest->member_id === (int) $member->user_id
            && (int) $event->joinRequest->group_id === (int) $this->group->group_id;
    });
});

/*
|--------------------------------------------------------------------------
| Yêu cầu hết hiệu lực (Expired) + badge "Yêu cầu" của trưởng nhóm tự giảm
|--------------------------------------------------------------------------
| Hết hiệu lực khi: nhóm đủ thành viên, sinh viên đã có nhóm khác trong lớp,
| hoặc yêu cầu đã được chấp nhận / từ chối.
*/

it('duyệt yêu cầu khi sinh viên đã có nhóm cùng lớp -> yêu cầu hết hiệu lực (Expired)', function () {
    $member = make_user('student', 'Thành viên R1');

    // Sinh viên đã vào một nhóm khác trong CÙNG LỚP
    $otherLeader = make_user('student', 'Trưởng nhóm R2');
    $otherGroup = make_group($otherLeader, $this->class);
    $this->groups->addMember($otherGroup, $member);

    $joinRequest = Join_Requests::create([
        'group_id'  => $this->group->group_id,
        'member_id' => $member->user_id,
        'status'    => 'Pending',
    ]);

    expect($this->leader->fresh()->pending_join_requests_count)->toBe(1);

    $result = $this->invitations->approveJoinRequest($joinRequest, $this->leader);

    expect($result->succeeded())->toBeFalse()
        ->and($joinRequest->fresh()->status)->toBe('Expired')
        ->and($this->groups->isInGroup($this->group, $member->user_id))->toBeFalse()
        ->and($this->leader->fresh()->pending_join_requests_count)->toBe(0);
});

it('sinh viên vào nhóm -> yêu cầu Pending khác trong CÙNG LỚP hết hiệu lực', function () {
    $member = make_user('student', 'Thành viên R3');

    // Nhóm khác cùng lớp mà sinh viên này đã gửi yêu cầu
    $otherLeader = make_user('student', 'Trưởng nhóm R4');
    $otherGroup = make_group($otherLeader, $this->class);

    $pendingRequest = Join_Requests::create([
        'group_id'  => $otherGroup->group_id,
        'member_id' => $member->user_id,
        'status'    => 'Pending',
    ]);

    expect($otherLeader->fresh()->pending_join_requests_count)->toBe(1);

    // Sinh viên chấp nhận lời mời vào nhóm của trưởng nhóm chính
    $invite = Invites::create([
        'group_id'  => $this->group->group_id,
        'member_id' => $member->user_id,
        'invitedBy' => $this->leader->user_id,
        'status'    => 'Pending',
    ]);

    $result = $this->invitations->acceptInvite($invite, $member);

    expect($result->succeeded())->toBeTrue()
        ->and($this->groups->isInGroup($this->group, $member->user_id))->toBeTrue()
        ->and($pendingRequest->fresh()->status)->toBe('Expired')
        // badge "Yêu cầu" của trưởng nhóm bên kia tự về 0
        ->and($otherLeader->fresh()->pending_join_requests_count)->toBe(0);
});

it('nhóm đủ thành viên sau khi duyệt -> các yêu cầu Pending còn lại của nhóm hết hiệu lực', function () {
    // max = 3 (leader + 2) -> thêm 1 thành viên là còn đúng 1 chỗ
    $existing = make_user('student', 'Thành viên R5');
    $this->groups->addMember($this->group, $existing);

    $first = make_user('student', 'Thành viên R6');
    $second = make_user('student', 'Thành viên R7');

    $firstRequest = $this->invitations->sendJoinRequest($this->group, $first)->data();
    $secondRequest = $this->invitations->sendJoinRequest($this->group, $second)->data();

    expect($this->leader->fresh()->pending_join_requests_count)->toBe(2);

    $result = $this->invitations->approveJoinRequest($firstRequest, $this->leader);

    expect($result->succeeded())->toBeTrue()
        ->and($firstRequest->fresh()->status)->toBe('Accepted')
        ->and($secondRequest->fresh()->status)->toBe('Expired')
        ->and($this->leader->fresh()->pending_join_requests_count)->toBe(0);
});

it('xử lý yêu cầu sẽ dispatch event JoinRequestResolved (badge realtime giảm)', function () {
    Event::fake([JoinRequestResolved::class]);

    $member = make_user('student', 'Thành viên R9');
    $joinRequest = Join_Requests::create([
        'group_id'  => $this->group->group_id,
        'member_id' => $member->user_id,
        'status'    => 'Pending',
    ]);

    $this->invitations->rejectJoinRequest($joinRequest, $this->leader);

    Event::assertDispatched(JoinRequestResolved::class, function ($event) use ($joinRequest) {
        return (int) $event->joinRequest->id === (int) $joinRequest->id
            && $event->status === 'Rejected';
    });
});

