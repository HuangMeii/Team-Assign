<?php

use App\Models\Group_Members;
use App\Models\User;
use App\Services\ChatUnreadService;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_user;

/*
|--------------------------------------------------------------------------
| Badge "tin nhắn chưa đọc" theo TỪNG hội thoại (đúng người gửi / đúng nhóm)
|--------------------------------------------------------------------------
| - Chat 1-1: badge theo người gửi (direct_messages.is_read)
| - Chat nhóm: badge theo nhóm (group_chat_reads.last_read_at)
| - Badge tổng (users.unread_message_count) = tổng các hội thoại, KHÔNG bị xoá oan
| LƯU Ý: cần MySQL test theo phpunit.xml (DB_HOST/DB_PORT/DB_DATABASE).
*/

it('chat 1-1: badge hiện theo từng người gửi và badge tổng là tổng các hội thoại', function () {
    $senderA = make_user('student', 'Nguoi gui A');
    $senderB = make_user('student', 'Nguoi gui B');
    $me = make_user('student', 'Nguoi nhan 1');

    $this->actingAs($senderA)->postJson(route('chat.send', $me->user_id), ['content' => 'A1'])->assertOk();
    $this->actingAs($senderA)->postJson(route('chat.send', $me->user_id), ['content' => 'A2'])->assertOk();
    $this->actingAs($senderB)->postJson(route('chat.send', $me->user_id), ['content' => 'B1'])->assertOk();

    $counts = app(ChatUnreadService::class)->directCountsFor($me->user_id);

    expect($counts[$senderA->user_id])->toBe(2)
        ->and($counts[$senderB->user_id])->toBe(1)
        ->and($me->fresh()->unread_message_count)->toBe(3);

    $this->actingAs($me)->get(route('chat.index'))
        ->assertOk()
        ->assertSee('data-chat-badge-user-id="' . $senderA->user_id . '"', false)
        ->assertSee('data-chat-badge-user-id="' . $senderB->user_id . '"', false);
});

it('danh sách hội thoại: chưa đọc lên đầu, còn lại theo lịch sử trò chuyện, chưa chat thì theo tên', function () {
    $me = make_user('student', 'Nguoi nhan 2');
    $neverChattedA = make_user('student', 'AAA Chua tung chat');
    $withHistory = make_user('student', 'MMM Co lich su');
    $unreadSender = make_user('student', 'ZZZ Dang co tin chua doc');
    $neverChattedB = make_user('student', 'BBB Chua tung chat 2');

    // Có lịch sử trò chuyện nhưng KHÔNG còn tin chưa đọc (mình là người gửi)
    $this->actingAs($me)->postJson(route('chat.send', $withHistory->user_id), ['content' => 'Lich su'])->assertOk();
    // Người này gửi cho mình -> còn tin chưa đọc -> phải được đẩy lên đầu
    $this->actingAs($unreadSender)->postJson(route('chat.send', $me->user_id), ['content' => 'Chua doc'])->assertOk();

    $this->actingAs($me)->get(route('chat.index'))
        ->assertOk()
        ->assertSeeInOrder([
            $unreadSender->name,
            $withHistory->name,
            $neverChattedA->name,
            $neverChattedB->name,
        ], false);
});

it('bấm vào hội thoại: badge riêng về 0 và badge tổng cập nhật theo số còn lại', function () {
    $me = make_user('student', 'Nguoi nhan 3');
    $peerA = make_user('student', 'Nguoi gui A3');
    $peerB = make_user('student', 'Nguoi gui B3');

    $this->actingAs($peerA)->postJson(route('chat.send', $me->user_id), ['content' => 'A'])->assertOk();
    $this->actingAs($peerB)->postJson(route('chat.send', $me->user_id), ['content' => 'B1'])->assertOk();
    $this->actingAs($peerB)->postJson(route('chat.send', $me->user_id), ['content' => 'B2'])->assertOk();

    expect($me->fresh()->unread_message_count)->toBe(3);

    // API đánh dấu đã đọc khi bấm vào hội thoại với peerA
    $this->actingAs($me)->postJson(route('chat.read', $peerA->user_id))
        ->assertOk()
        ->assertJson(['total' => 2]);

    expect($me->fresh()->unread_message_count)->toBe(2)
        ->and(app(ChatUnreadService::class)->directCountsFor($me->user_id))->not->toHaveKey($peerA->user_id);

    // Mở trang chat với peerB -> chỉ hội thoại này về 0, badge tổng về 0
    $this->actingAs($me)->get(route('chat.show', $peerB->user_id))->assertOk();

    expect($me->fresh()->unread_message_count)->toBe(0);
});

it('chat nhóm: badge theo từng nhóm và mở một nhóm không xoá badge nhóm khác', function () {
    $lecturer = make_user('lecturer', 'Giang vien G');
    $class = make_class(make_subject($lecturer), $lecturer);
    $leader = make_user('student', 'Truong nhom G');
    $member = make_user('student', 'Thanh vien G');

    $group1 = make_group($leader, $class, 'Nhom 1');
    $group2 = make_group($leader, $class, 'Nhom 2');

    Group_Members::create(['group_id' => $group1->group_id, 'user_id' => $member->user_id, 'role' => 'member']);
    Group_Members::create(['group_id' => $group2->group_id, 'user_id' => $member->user_id, 'role' => 'member']);

    $this->actingAs($leader)->postJson(route('groups.chat.send', $group1->group_id), ['content' => 'N1'])->assertOk();
    $this->actingAs($leader)->postJson(route('groups.chat.send', $group2->group_id), ['content' => 'N2'])->assertOk();
    $this->actingAs($leader)->postJson(route('groups.chat.send', $group2->group_id), ['content' => 'N2b'])->assertOk();

    $counts = app(ChatUnreadService::class)->groupCountsFor($member->user_id, [$group1->group_id, $group2->group_id]);

    expect($counts[$group1->group_id])->toBe(1)
        ->and($counts[$group2->group_id])->toBe(2)
        ->and($member->fresh()->unread_message_count)->toBe(3);

    $this->actingAs($member)->get(route('chat.index', ['mode' => 'group']))
        ->assertOk()
        ->assertSee('data-chat-badge-group-id="' . $group1->group_id . '"', false)
        ->assertSee('data-chat-badge-group-id="' . $group2->group_id . '"', false);

    // Mở nhóm 1 -> chỉ nhóm 1 về 0, badge tổng còn 2 của nhóm 2
    $this->actingAs($member)->get(route('groups.chat.show', $group1->group_id))->assertOk();

    expect($member->fresh()->unread_message_count)->toBe(2)
        ->and(app(ChatUnreadService::class)->groupCountsFor($member->user_id, [$group1->group_id])[$group1->group_id])->toBe(0);

    // API đánh dấu đã đọc nhóm 2 -> badge tổng về 0
    $this->actingAs($member)->postJson(route('groups.chat.read', $group2->group_id))
        ->assertOk()
        ->assertJson(['total' => 0]);

    expect($member->fresh()->unread_message_count)->toBe(0);
});

it('badge Chat trên sidebar lấy theo dữ liệu thật, không phụ thuộc cột cache bị lệch', function () {
    $me = make_user('student', 'Nguoi nhan 4');
    $sender = make_user('student', 'Nguoi gui C4');

    $this->actingAs($sender)->postJson(route('chat.send', $me->user_id), ['content' => 'C1'])->assertOk();
    $this->actingAs($sender)->postJson(route('chat.send', $me->user_id), ['content' => 'C2'])->assertOk();

    // Giả lập cột cache users.unread_message_count bị lệch (ví dụ tin nhắn cũ trước khi có tính năng)
    User::where('user_id', $me->user_id)->update(['unread_message_count' => 0]);

    expect(app(ChatUnreadService::class)->totalFor($me->user_id))->toBe(2);

    $this->actingAs($me->fresh())
        ->get(route('user.dashboard'))
        ->assertOk()
        // Badge phải hiển thị số THẬT (2), không phải số trong cột cache (0)
        ->assertSee('data-chat-badge>2</span>', false);
});
