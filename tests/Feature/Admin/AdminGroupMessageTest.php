<?php

use App\Events\NewChatMessage;
use App\Models\ChatMessage;
use App\Models\Group_Members;
use App\Services\ChatUnreadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_user;

/*
|--------------------------------------------------------------------------
| Admin gửi thông báo / cảnh báo vào khung chat nhóm (không cần tham gia)
|--------------------------------------------------------------------------
| - Admin thấy TẤT CẢ nhóm ở /chat?mode=group, không có badge chưa đọc.
| - Admin gửi tin qua admin.chat.message.group -> chat_messages.type =
|   announcement | warning, KHÔNG tạo dòng group_members nhưng vẫn cộng badge
|   chưa đọc cho trưởng nhóm + thành viên và broadcast realtime.
| - Tin của admin là nội dung hệ thống nên KHÔNG bị gắn cờ.
| LƯU Ý: cần MySQL test theo phpunit.xml (DB_HOST/DB_PORT/DB_DATABASE).
*/

/** Tạo nhóm có 1 trưởng nhóm + 1 thành viên; trả về [group, leader, member]. */
function agm_group(string $name = 'Nhóm AGM'): array
{
    $lecturer = make_user('lecturer', 'Giảng viên AGM');
    $class = make_class(make_subject($lecturer), $lecturer);
    $leader = make_user('student', 'Trưởng nhóm AGM');
    $member = make_user('student', 'Thành viên AGM');
    $group = make_group($leader, $class, $name);

    Group_Members::create([
        'group_id' => $group->group_id,
        'user_id'  => $member->user_id,
        'role'     => 'member',
    ]);

    return [$group, $leader, $member];
}

it('admin gửi thông báo vào nhóm: đúng type, không tham gia nhóm, cộng badge cho thành viên', function () {
    Event::fake([NewChatMessage::class]);

    $admin = make_user('admin', 'Admin AGM 1');
    [$group, $leader, $member] = agm_group();

    $this->actingAs($admin)
        ->post(route('admin.chat.message.group'), [
            'group_id' => $group->group_id,
            'type'     => 'announcement',
            'content'  => 'Lớp nghỉ tuần này, các nhóm nộp báo cáo trước thứ 6.',
        ])
        ->assertRedirect();

    $message = ChatMessage::where('group_id', $group->group_id)->first();

    expect($message)->not->toBeNull()
        ->and((int) $message->user_id)->toBe($admin->user_id)
        ->and($message->type)->toBe('announcement')
        ->and($message->is_flagged)->toBeFalse();

    // Admin KHÔNG được thêm vào group_members => không bị tính là thành viên nhóm
    expect($group->members()->where('group_members.user_id', $admin->user_id)->exists())->toBeFalse()
        ->and(app(ChatUnreadService::class)->groupIdsFor($admin->user_id))->not->toContain($group->group_id);

    // Trưởng nhóm + thành viên được cộng badge chưa đọc, admin thì không
    expect($leader->fresh()->unread_message_count)->toBe(1)
        ->and($member->fresh()->unread_message_count)->toBe(1)
        ->and($admin->fresh()->unread_message_count)->toBe(0);

    // Vẫn broadcast realtime tới kênh của nhóm như tin nhắn thường
    Event::assertDispatched(NewChatMessage::class, function (NewChatMessage $event) use ($group) {
        return (int) $event->message->group_id === (int) $group->group_id
            && $event->message->type === 'announcement';
    });
});

it('cảnh báo của admin hiện rõ trong khung chat nhóm và không bị gắn cờ', function () {
    // Mode rules để test không phụ thuộc server 8890.
    config()->set('services.content_moderation.mode', 'rules');

    $admin = make_user('admin', 'Admin AGM 2');
    [$group, , $member] = agm_group();

    $this->actingAs($admin)
        ->post(route('admin.chat.message.group'), [
            'group_id' => $group->group_id,
            'type'     => 'warning',
            'content'  => 'May ngu nhu cho, im mom di.',
        ])
        ->assertRedirect();

    $message = ChatMessage::where('group_id', $group->group_id)->first();

    // Nội dung nhạy cảm nhưng là tin hệ thống của admin -> KHÔNG gắn cờ
    expect($message->type)->toBe('warning')
        ->and($message->is_flagged)->toBeFalse()
        ->and($message->flag_reason)->toBeNull();

    // Thành viên mở khung chat nhóm -> thấy cảnh báo của admin ngay giữa khung chat
    $this->actingAs($member)
        ->get(route('groups.chat.show', $group->group_id))
        ->assertOk()
        ->assertSee('Cảnh báo từ Admin')
        ->assertSee('May ngu nhu cho, im mom di.');
});

it('admin xem mọi khung chat nhóm, không badge chưa đọc và tìm được theo tên', function () {
    $admin = make_user('admin', 'Admin AGM 3');
    $lecturer = make_user('lecturer', 'Giảng viên AGM 3');
    $class = make_class(make_subject($lecturer), $lecturer);
    $leader = make_user('student', 'Trưởng nhóm AGM 3');

    $groupA = make_group($leader, $class, 'Nhóm Alpha');
    $groupB = make_group($leader, $class, 'Nhóm Beta');

    // Nhóm Beta có 1 tin bị gắn cờ -> admin thấy badge "cờ" của đúng nhóm đó
    ChatMessage::create([
        'group_id'         => $groupB->group_id,
        'user_id'          => $leader->user_id,
        'content'          => 'tin nhắn bị gắn cờ',
        'type'             => 'member',
        'is_flagged'       => true,
        'flag_reason'      => 'sensitive:insult',
        'moderation_score' => 0.8,
        'flagged_at'       => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('chat.index', ['mode' => 'group']))
        ->assertOk()
        ->assertSee('Tất cả nhóm')
        ->assertSee($groupA->group_name)
        ->assertSee($groupB->group_name)
        // Admin không tham gia nhóm nên KHÔNG có badge chưa đọc theo nhóm
        ->assertDontSee('data-chat-badge-group-id', false)
        // Nhưng thấy số tin bị gắn cờ để mở thẳng tab Giám sát Chat
        ->assertSee('1 cờ');

    // Tìm kiếm theo tên nhóm
    $this->actingAs($admin)
        ->get(route('chat.index', ['mode' => 'group', 'gsearch' => 'Alpha']))
        ->assertOk()
        ->assertSee($groupA->group_name)
        ->assertDontSee($groupB->group_name);
});

it('admin mở khung chat nhóm ở chế độ giám sát: có ô soạn thông báo/cảnh báo, không gửi tin thường', function () {
    $admin = make_user('admin', 'Admin AGM 4');
    [$group] = agm_group();

    $this->actingAs($admin)
        ->get(route('groups.chat.show', $group->group_id))
        ->assertOk()
        ->assertSee('Đang xem với quyền Admin')
        ->assertSee('action="' . route('admin.chat.message.group') . '"', false)
        ->assertSee('value="announcement"', false)
        ->assertSee('value="warning"', false);

    // Admin không gửi được tin nhắn thường (chỉ gửi thông báo / cảnh báo)
    $this->actingAs($admin)
        ->postJson(route('groups.chat.send', $group->group_id), ['content' => 'tin thường'])
        ->assertStatus(403);

    // Mở khung chat giám sát KHÔNG tạo dòng "đã đọc" cho admin
    expect(DB::table('group_chat_reads')->where('user_id', $admin->user_id)->exists())->toBeFalse()
        ->and(ChatMessage::where('group_id', $group->group_id)->count())->toBe(0);
});

it('sinh viên và khách không dùng được route gửi thông báo của admin', function () {
    $student = make_user('student', 'Sinh viên AGM');
    [$group] = agm_group();

    $response = $this->actingAs($student)->post(route('admin.chat.message.group'), [
        'group_id' => $group->group_id,
        'type'     => 'announcement',
        'content'  => 'Thử gửi trộm',
    ]);

    expect(in_array($response->status(), [302, 403]))->toBeTrue()
        ->and(ChatMessage::where('content', 'Thử gửi trộm')->exists())->toBeFalse();

    $guest = $this->post(route('admin.chat.message.group'), [
        'group_id' => $group->group_id,
        'type'     => 'warning',
        'content'  => 'Khách gửi trộm',
    ]);

    expect(in_array($guest->status(), [302, 403]))->toBeTrue()
        ->and(ChatMessage::where('content', 'Khách gửi trộm')->exists())->toBeFalse();
});

it('kiểm tra dữ liệu: thiếu nội dung, sai loại tin, nhóm không tồn tại đều bị từ chối', function () {
    $admin = make_user('admin', 'Admin AGM 5');
    [$group] = agm_group();

    $this->actingAs($admin)
        ->post(route('admin.chat.message.group'), [
            'group_id' => $group->group_id,
            'type'     => 'announcement',
        ])
        ->assertSessionHasErrors('content');

    $this->actingAs($admin)
        ->post(route('admin.chat.message.group'), [
            'group_id' => $group->group_id,
            'type'     => 'member',
            'content'  => 'loại tin không hợp lệ',
        ])
        ->assertSessionHasErrors('type');

    $this->actingAs($admin)
        ->post(route('admin.chat.message.group'), [
            'group_id' => 99999999,
            'type'     => 'warning',
            'content'  => 'nhóm không tồn tại',
        ])
        ->assertSessionHasErrors('group_id');

    expect(ChatMessage::count())->toBe(0);
});
