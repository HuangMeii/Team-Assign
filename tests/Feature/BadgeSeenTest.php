<?php

use App\Models\Invites;
use App\Models\Join_Requests;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_user;

/*
|--------------------------------------------------------------------------
| Badge "đã xem": bấm vào mục Yêu cầu / Lời mời là badge về 0 NGAY và không
| hiện lại khi tải trang (chỉ hiện lại khi có bản ghi MỚI).
|--------------------------------------------------------------------------
| LƯU Ý: cần MySQL test theo phpunit.xml (DB_HOST/DB_PORT/DB_DATABASE).
*/

/** Nhóm có 1 trưởng nhóm; trả về [group, leader]. */
function bs_group(string $name = 'Nhóm Badge Seen'): array
{
    $lecturer = make_user('lecturer', 'Giảng viên BS');
    $class = make_class(make_subject($lecturer), $lecturer);
    $leader = make_user('student', 'Trưởng nhóm BS');
    $group = make_group($leader, $class, $name);

    return [$group, $leader];
}

it('bấm mục Yêu cầu: badge về 0 và chỉ hiện lại khi có yêu cầu mới', function () {
    [$group, $leader] = bs_group();
    $student = make_user('student', 'Sinh viên BS');

    Join_Requests::create([
        'group_id'  => $group->group_id,
        'member_id' => $student->user_id,
        'status'    => 'Pending',
    ]);

    expect($leader->fresh()->pending_join_requests_count)->toBe(1);

    // Trang dùng layouts.user phải có sẵn link "đã xem" cho JS
    $this->actingAs($leader)
        ->get(route('user.join-requests'))
        ->assertOk()
        ->assertSee('data-badge-clear="request"', false)
        ->assertSee('data-badge-seen-url="' . route('user.join-requests.seen') . '"', false);

    $this->actingAs($leader)
        ->postJson(route('user.join-requests.seen'))
        ->assertOk()
        ->assertJson(['success' => true, 'count' => 0]);

    expect($leader->fresh()->pending_join_requests_count)->toBe(0);

    // Có yêu cầu MỚI -> badge hiện lại
    $this->travel(2)->seconds();
    $student2 = make_user('student', 'Sinh viên BS 2');

    Join_Requests::create([
        'group_id'  => $group->group_id,
        'member_id' => $student2->user_id,
        'status'    => 'Pending',
    ]);

    expect($leader->fresh()->pending_join_requests_count)->toBe(1);
});

it('bấm mục Lời mời: badge về 0 và chỉ hiện lại khi có lời mời mới', function () {
    [$group, $leader] = bs_group();
    $invitee = make_user('student', 'Sinh viên mời BS');

    Invites::create([
        'group_id'  => $group->group_id,
        'invitedBy' => $leader->user_id,
        'member_id' => $invitee->user_id,
        'status'    => 'Pending',
    ]);

    expect($invitee->fresh()->pending_invites_count)->toBe(1);

    $this->actingAs($invitee)
        ->get(route('user.invites'))
        ->assertOk()
        ->assertSee('data-badge-clear="invite"', false)
        ->assertSee('data-badge-seen-url="' . route('user.invites.seen') . '"', false);

    $this->actingAs($invitee)
        ->postJson(route('user.invites.seen'))
        ->assertOk();

    expect($invitee->fresh()->pending_invites_count)->toBe(0);

    $this->travel(2)->seconds();

    [$group2] = bs_group('Nhóm Badge Seen 2');

    Invites::create([
        'group_id'  => $group2->group_id,
        'invitedBy' => $leader->user_id,
        'member_id' => $invitee->user_id,
        'status'    => 'Pending',
    ]);

    expect($invitee->fresh()->pending_invites_count)->toBe(1);
});

it('khách chưa đăng nhập không gọi được route đánh dấu đã xem', function () {
    $this->post(route('user.join-requests.seen'))->assertRedirect(route('login'));
    $this->post(route('user.invites.seen'))->assertRedirect(route('login'));
});
