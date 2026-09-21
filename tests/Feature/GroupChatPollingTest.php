<?php

use App\Models\ChatMessage;
use App\Models\Group_Members;
use App\Services\ChatUnreadService;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_user;

/*
|--------------------------------------------------------------------------
| Polling fallback cho khung chat nhóm (khi WebSocket/Reverb không khả dụng)
|--------------------------------------------------------------------------
| - GET /groups/{groupId}/chat/messages?after=... chỉ trả các tin MỚI HƠN mốc,
|   kèm last_id để client tiến mốc cho lần hỏi sau (resources/js/chat_listener.js).
| - Thành viên / trưởng nhóm / admin (giám sát) gọi được; người ngoài nhóm 403.
| - Thông báo / cảnh báo của admin cũng trả qua polling nên thành viên vẫn thấy
|   tin ngay cả khi Reverb không chạy.
| - Badge nhóm giữ nguyên Hướng B: thành viên CHƯA mở khung nhóm thấy badge,
|   mở khung nhóm thì badge về 0.
| LƯU Ý: cần MySQL test theo phpunit.xml (DB_HOST/DB_PORT/DB_DATABASE).
*/

/** Nhóm có 1 trưởng nhóm + 1 thành viên; trả về [group, leader, member]. */
function gcp_group(string $name = 'Nhóm Polling'): array
{
    $lecturer = make_user('lecturer', 'Giảng viên GP');
    $class = make_class(make_subject($lecturer), $lecturer);
    $leader = make_user('student', 'Trưởng nhóm GP');
    $member = make_user('student', 'Thành viên GP');
    $group = make_group($leader, $class, $name);

    Group_Members::create([
        'group_id' => $group->group_id,
        'user_id'  => $member->user_id,
        'role'     => 'member',
    ]);

    return [$group, $leader, $member];
}

it('polling chỉ trả tin mới hơn mốc after và kèm last_id', function () {
    [$group, $leader, $member] = gcp_group();

    $this->actingAs($leader)->postJson(route('groups.chat.send', $group->group_id), ['content' => 'Tin 1'])->assertOk();
    $this->actingAs($leader)->postJson(route('groups.chat.send', $group->group_id), ['content' => 'Tin 2'])->assertOk();

    $ids = ChatMessage::where('group_id', $group->group_id)->orderBy('id')->pluck('id')->all();

    expect($ids)->toHaveCount(2);

    // Không truyền after -> trả toàn bộ tin đang có trong khung chat
    $all = $this->actingAs($member)
        ->getJson(route('groups.chat.messages', $group->group_id))
        ->assertOk()
        ->assertJsonPath('last_id', (int) $ids[1]);

    expect(collect($all->json('data'))->pluck('content')->all())->toBe(['Tin 1', 'Tin 2']);

    // after = id tin đầu -> chỉ còn tin thứ 2
    $newer = $this->actingAs($member)
        ->getJson(route('groups.chat.messages', $group->group_id) . '?after=' . $ids[0])
        ->assertOk()
        ->assertJsonPath('last_id', (int) $ids[1]);

    expect(collect($newer->json('data'))->pluck('id')->all())->toBe([(int) $ids[1]]);

    // Không có tin mới -> data rỗng, last_id giữ nguyên mốc cũ
    $this->actingAs($member)
        ->getJson(route('groups.chat.messages', $group->group_id) . '?after=' . $ids[1])
        ->assertOk()
        ->assertJsonPath('last_id', (int) $ids[1])
        ->assertJsonCount(0, 'data');
});

it('thông báo của admin được trả qua polling cho thành viên', function () {
    $admin = make_user('admin', 'Admin GP');
    [$group, , $member] = gcp_group();

    $this->actingAs($admin)
        ->post(route('admin.chat.message.group'), [
            'group_id' => $group->group_id,
            'type'     => 'announcement',
            'content'  => 'Cả lớp nộp báo cáo trước thứ 6.',
        ])
        ->assertRedirect();

    $response = $this->actingAs($member)
        ->getJson(route('groups.chat.messages', $group->group_id))
        ->assertOk();

    $message = collect($response->json('data'))->first();

    expect($message['type'])->toBe('announcement')
        ->and($message['content'])->toBe('Cả lớp nộp báo cáo trước thứ 6.')
        ->and((int) $message['user_id'])->toBe($admin->user_id)
        ->and((int) $response->json('last_id'))->toBe((int) $message['id']);
});

it('người ngoài nhóm và khách không gọi được polling', function () {
    [$group, , $member] = gcp_group();
    $outsider = make_user('student', 'Người ngoài GP');

    // Khách chưa đăng nhập -> bị đưa về trang đăng nhập
    $this->get(route('groups.chat.messages', $group->group_id))
        ->assertRedirect(route('login'));

    // Đã đăng nhập nhưng không thuộc nhóm -> 403
    $this->actingAs($outsider)
        ->getJson(route('groups.chat.messages', $group->group_id))
        ->assertForbidden();

    // Thành viên của nhóm vẫn gọi được bình thường
    $this->actingAs($member)
        ->getJson(route('groups.chat.messages', $group->group_id))
        ->assertOk();
});

it('badge nhóm hiện cho thành viên chưa mở nhóm, mở nhóm thì về 0 (Hướng B)', function () {
    $admin = make_user('admin', 'Admin GP 2');
    [$group, $leader, $member] = gcp_group();

    $this->actingAs($admin)
        ->post(route('admin.chat.message.group'), [
            'group_id' => $group->group_id,
            'type'     => 'warning',
            'content'  => 'Cảnh báo: nộp bài muộn sẽ bị trừ điểm.',
        ])
        ->assertRedirect();

    // Chưa mở nhóm -> badge riêng của nhóm = 1 cho cả trưởng nhóm và thành viên
    expect(app(ChatUnreadService::class)->groupCountsFor($member->user_id, [$group->group_id])[$group->group_id])->toBe(1)
        ->and(app(ChatUnreadService::class)->groupCountsFor($leader->user_id, [$group->group_id])[$group->group_id])->toBe(1);

    $this->actingAs($member)
        ->get(route('chat.index', ['mode' => 'group']))
        ->assertOk()
        ->assertSee('data-chat-badge-group-id="' . $group->group_id . '"', false);

    // Mở khung chat nhóm = đã đọc (Hướng B) -> badge nhóm về 0, đồng thời trang có
    // sẵn URL polling + mốc id để chat_listener.js hỏi tin mới khi Reverb lỗi.
    $this->actingAs($member)
        ->get(route('groups.chat.show', $group->group_id))
        ->assertOk()
        ->assertSee('Cảnh báo từ Admin')
        ->assertSee('data-messages-url="' . route('groups.chat.messages', $group->group_id) . '"', false)
        ->assertSee('data-message-id=', false);

    expect(app(ChatUnreadService::class)->groupCountsFor($member->user_id, [$group->group_id])[$group->group_id])->toBe(0);
});
