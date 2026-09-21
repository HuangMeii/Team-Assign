<?php

use App\Models\Join_Requests;
use App\Models\Notifications;
use App\Services\GroupService;
use App\Services\InvitationService;
use App\Services\NotificationService;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_user;

/*
|--------------------------------------------------------------------------
| Thông báo yêu cầu tham gia nhóm + badge chưa đọc (users.unread_notifications)
|--------------------------------------------------------------------------
| LƯU Ý: cần MySQL test theo phpunit.xml (DB_HOST/DB_PORT/DB_DATABASE).
*/

it('gửi yêu cầu tham gia nhóm sẽ thông báo cho trưởng nhóm và tăng badge chưa đọc', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $class = make_class(make_subject($lecturer), $lecturer);
    $leader = make_user('student', 'Trưởng nhóm A');
    $member = make_user('student', 'Sinh viên B');
    $group = make_group($leader, $class);

    $result = app(InvitationService::class)->sendJoinRequest($group, $member);

    expect($result->succeeded())->toBeTrue()
        ->and(Join_Requests::where('group_id', $group->group_id)
            ->where('member_id', $member->user_id)
            ->where('status', 'Pending')
            ->exists())->toBeTrue()
        // Badge chuông (thông báo chưa đọc) của trưởng nhóm tăng 1
        ->and($leader->fresh()->unread_notifications)->toBe(1)
        // Badge "Yêu cầu" đếm số yêu cầu đang chờ
        ->and($leader->fresh()->pending_join_requests_count)->toBe(1)
        ->and(Notifications::where('user_id', $leader->user_id)
            ->where('type', 'join_request')
            ->exists())->toBeTrue();
});

it('đánh dấu tất cả đã đọc sẽ đưa badge thông báo về 0', function () {
    $user = make_user('student', 'Người dùng A');

    NotificationService::create($user->user_id, 'system', 'Tiêu đề 1', 'Nội dung 1');
    NotificationService::create($user->user_id, 'system', 'Tiêu đề 2', 'Nội dung 2');

    expect($user->fresh()->unread_notifications)->toBe(2);

    NotificationService::markAllAsRead($user->user_id);

    expect($user->fresh()->unread_notifications)->toBe(0);
});

it('nav-bar render sẵn badge thông báo và badge yêu cầu từ server', function () {
    $user = make_user('student', 'Người dùng B');
    NotificationService::create($user->user_id, 'system', 'Tiêu đề', 'Nội dung');

    $this->actingAs($user)
        ->get(route('user.dashboard'))
        ->assertOk()
        // Badge phải tồn tại trong DOM ngay từ server (không chờ AJAX/WebSocket)
        ->assertSee('data-notification-badge', false)
        ->assertSee('data-request-badge', false);
});

/*
|--------------------------------------------------------------------------
| Trang "Yêu cầu" phải hiển thị đúng những yêu cầu mà badge đang đếm
|--------------------------------------------------------------------------
| Badge "Yêu cầu" đếm yêu cầu Pending GỬI ĐẾN nhóm mình làm trưởng nhóm;
| trước đây trang chỉ liệt kê yêu cầu mình ĐÃ GỬI nên badge có số mà trang rỗng.
*/

it('trang Yêu cầu hiển thị yêu cầu nhận được và cho xử lý khi còn hiệu lực', function () {
    $lecturer = make_user('lecturer', 'Giảng viên C');
    $class = make_class(make_subject($lecturer), $lecturer);
    $leader = make_user('student', 'Trưởng nhóm C');
    $member = make_user('student', 'Sinh viên C');
    $group = make_group($leader, $class);

    app(InvitationService::class)->sendJoinRequest($group, $member);

    $joinRequest = Join_Requests::where('group_id', $group->group_id)
        ->where('member_id', $member->user_id)
        ->first();

    expect($joinRequest)->not->toBeNull()
        ->and($leader->fresh()->pending_join_requests_count)->toBe(1);

    $this->actingAs($leader)->get(route('user.join-requests'))
        ->assertOk()
        ->assertSee('data-request-badge', false)
        ->assertSee($member->name)
        ->assertSee($group->group_name)
        ->assertSee(route('user.approve-join-request', $joinRequest->id), false)
        ->assertSee(route('user.reject-join-request', $joinRequest->id), false);

    // Xử lý xong -> badge "Yêu cầu" giảm về 0 và nút biến mất
    $this->actingAs($leader)
        ->post(route('user.approve-join-request', $joinRequest->id))
        ->assertRedirect();

    expect($joinRequest->fresh()->status)->toBe('Accepted')
        ->and($leader->fresh()->pending_join_requests_count)->toBe(0);

    $this->actingAs($leader)->get(route('user.join-requests'))
        ->assertOk()
        ->assertDontSee(route('user.approve-join-request', $joinRequest->id), false)
        ->assertDontSee(route('user.reject-join-request', $joinRequest->id), false);
});

it('yêu cầu hết hiệu lực (sinh viên đã có nhóm khác) thì ẩn nút Chấp nhận/Từ chối', function () {
    $lecturer = make_user('lecturer', 'Giảng viên D');
    $class = make_class(make_subject($lecturer), $lecturer);
    $leader = make_user('student', 'Trưởng nhóm D');
    $group = make_group($leader, $class);

    $member = make_user('student', 'Sinh viên D');
    $joinRequest = Join_Requests::create([
        'group_id'  => $group->group_id,
        'member_id' => $member->user_id,
        'status'    => 'Pending',
    ]);

    // Sinh viên đã vào một nhóm khác trong CÙNG LỚP
    $otherLeader = make_user('student', 'Trưởng nhóm D2');
    $otherGroup = make_group($otherLeader, $class);
    app(GroupService::class)->addMember($otherGroup, $member);

    $this->actingAs($leader)->get(route('user.join-requests'))
        ->assertOk()
        ->assertSee('Sinh viên đã có nhóm trong lớp này')
        ->assertDontSee(route('user.approve-join-request', $joinRequest->id), false)
        ->assertDontSee(route('user.reject-join-request', $joinRequest->id), false);

    // Dù bấm duyệt (ví dụ request cũ) thì service cũng chặn và đánh dấu hết hiệu lực
    $result = app(InvitationService::class)->approveJoinRequest($joinRequest, $leader);

    expect($result->succeeded())->toBeFalse()
        ->and($joinRequest->fresh()->status)->toBe('Expired')
        ->and($leader->fresh()->pending_join_requests_count)->toBe(0);
});

it('trang Thông báo cũng ẩn nút Chấp nhận/Từ chối khi yêu cầu hết hiệu lực', function () {
    $lecturer = make_user('lecturer', 'Giảng viên E');
    $class = make_class(make_subject($lecturer), $lecturer);
    $leader = make_user('student', 'Trưởng nhóm E');
    $group = make_group($leader, $class);
    $member = make_user('student', 'Sinh viên E');

    app(InvitationService::class)->sendJoinRequest($group, $member);

    $joinRequest = Join_Requests::where('group_id', $group->group_id)
        ->where('member_id', $member->user_id)
        ->first();

    // Còn hiệu lực -> có nút xử lý ngay trên thông báo
    $this->actingAs($leader)->get(route('notifications.index'))
        ->assertOk()
        ->assertSee(route('user.approve-join-request', $joinRequest->id), false);

    // Sinh viên đã vào nhóm khác cùng lớp -> hết hiệu lực -> nút phải biến mất
    $otherLeader = make_user('student', 'Trưởng nhóm E2');
    $otherGroup = make_group($otherLeader, $class);
    app(GroupService::class)->addMember($otherGroup, $member);

    $this->actingAs($leader)->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Sinh viên đã có nhóm trong lớp này')
        ->assertDontSee(route('user.approve-join-request', $joinRequest->id), false)
        ->assertDontSee(route('user.reject-join-request', $joinRequest->id), false);
});
