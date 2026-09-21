<?php

use App\Models\ChatMessage;
use App\Models\DirectMessage;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_user;

/*
|--------------------------------------------------------------------------
| Giám sát Chat (admin/chat-monitor) — badge reset khi đã xem + ẩn cột nội bộ
|--------------------------------------------------------------------------
| Badge "Giám sát Chat" = số tin nhắn/ảnh bị gắn cờ CHƯA XEM, nghĩa là
| COALESCE(flagged_at, created_at) > users.flagged_seen_at của chính admin đó.
|
| Mở trang/tab "Bị gắn cờ" (admin.chat.monitor?tab=flagged) -> server set
| flagged_seen_at = now() -> badge về 0; khi có tin bị gắn cờ mới thì đếm lại.
| Hai tab còn lại (direct/group) KHÔNG reset badge.
|
| Cột "Lý do / Điểm" trong 2 bảng ở tab "Bị gắn cờ" đã bị ẩn (thông tin nội bộ);
| bộ lọc theo điểm (min_score) và theo lý do cờ (search) vẫn giữ nguyên.
*/

/**
 * Tạo 1 tin nhắn cá nhân bị gắn cờ.
 * $withFlaggedAt = false mô phỏng tin được gắn cờ trước khi có cột flagged_at (giá trị NULL).
 */
function cm_flagged_direct(string $content = 'nội dung bị gắn cờ cá nhân', bool $withFlaggedAt = true): DirectMessage
{
    $sender = make_user('student', 'Người gửi CM');
    $recipient = make_user('student', 'Người nhận CM');

    return DirectMessage::create([
        'sender_id'        => $sender->user_id,
        'recipient_id'     => $recipient->user_id,
        'content'          => $content,
        'is_read'          => false,
        'is_flagged'       => true,
        'flag_reason'      => 'sensitive:insult',
        'moderation_score' => 0.86,
        'flagged_at'       => $withFlaggedAt ? now() : null,
    ]);
}

/** Tạo 1 tin nhắn nhóm bị gắn cờ (mốc flagged_at = now()). */
function cm_flagged_group(string $content = 'nội dung bị gắn cờ nhóm'): ChatMessage
{
    $lecturer = make_user('lecturer', 'Giảng viên CM');
    $class = make_class(make_subject($lecturer), $lecturer);
    $leader = make_user('student', 'Trưởng nhóm CM');
    $group = make_group($leader, $class);

    return ChatMessage::create([
        'group_id'         => $group->group_id,
        'user_id'          => $leader->user_id,
        'content'          => $content,
        'is_flagged'       => true,
        'flag_reason'      => 'sensitive:threat',
        'moderation_score' => 0.91,
        'flagged_at'       => now(),
    ]);
}

it('badge Giám sát Chat cộng cả tin nhắn cá nhân lẫn nhóm bị gắn cờ khi admin chưa xem', function () {
    $admin = make_user('admin', 'Admin CM 1');

    cm_flagged_direct();
    cm_flagged_group();

    $this->actingAs($admin)
        ->get(route('admin.chat.flagged-count'))
        ->assertOk()
        ->assertJson(['count' => 2]);
});

it('mở tab Bị gắn cờ: set flagged_seen_at, badge về 0 và không còn cột Lý do / Điểm', function () {
    $admin = make_user('admin', 'Admin CM 2');
    cm_flagged_direct();
    cm_flagged_group();

    $response = $this->actingAs($admin)->get(route('admin.chat.monitor', ['tab' => 'flagged']));

    $response->assertOk()
        // Trang vẫn render đủ 2 bảng tin nhắn bị gắn cờ + nút xử lý
        ->assertSee('nội dung bị gắn cờ cá nhân')
        ->assertSee('nội dung bị gắn cờ nhóm')
        ->assertSee('Bỏ cờ')
        // ...nhưng cột "Lý do / Điểm" (flag_reason + moderation_score) đã bị ẩn
        ->assertDontSee('Lý do / Điểm')
        ->assertDontSee('sensitive:insult')
        ->assertDontSee('sensitive:threat')
        ->assertDontSee('điểm 0.86')
        ->assertDontSee('điểm 0.91')
        // Badge ngay trên tab đang mở = 0 -> ẩn
        ->assertSee('data-moderation-badge style="display: none;"', false);

    expect($admin->fresh()->flagged_seen_at)->not->toBeNull();

    $this->actingAs($admin)
        ->get(route('admin.chat.flagged-count'))
        ->assertOk()
        ->assertJson(['count' => 0]);
});

it('sau khi đã xem, tin nhắn bị gắn cờ mới làm badge đếm lại từ 1', function () {
    $admin = make_user('admin', 'Admin CM 3');
    cm_flagged_direct();

    $this->actingAs($admin)->get(route('admin.chat.monitor', ['tab' => 'flagged']))->assertOk();
    $this->actingAs($admin)->get(route('admin.chat.flagged-count'))->assertJson(['count' => 0]);

    // Tin bị gắn cờ mới xuất hiện SAU mốc đã xem -> badge phải nhích lên 1
    $this->travel(5)->seconds();
    cm_flagged_group();

    $this->actingAs($admin)
        ->get(route('admin.chat.flagged-count'))
        ->assertJson(['count' => 1]);
});

it('mở tab Chat cá nhân hoặc Chat nhóm thì không reset badge', function () {
    $admin = make_user('admin', 'Admin CM 4');
    cm_flagged_direct();

    $this->actingAs($admin)->get(route('admin.chat.monitor', ['tab' => 'direct']))->assertOk();
    $this->actingAs($admin)->get(route('admin.chat.monitor', ['tab' => 'group']))->assertOk();

    expect($admin->fresh()->flagged_seen_at)->toBeNull();

    $this->actingAs($admin)
        ->get(route('admin.chat.flagged-count'))
        ->assertJson(['count' => 1]);
});

it('sinh viên không vào được trang Giám sát Chat và không đọc được badge', function () {
    $student = make_user('student', 'Sinh viên CM');

    $page = $this->actingAs($student)->get(route('admin.chat.monitor', ['tab' => 'flagged']));
    $badge = $this->actingAs($student)->get(route('admin.chat.flagged-count'));

    expect(in_array($page->status(), [302, 403]))->toBeTrue()
        ->and(in_array($badge->status(), [302, 403]))->toBeTrue()
        ->and($student->fresh()->flagged_seen_at)->toBeNull();
});

it('layout admin: link Giám sát Chat mở thẳng tab Bị gắn cờ và badge có URL đếm', function () {
    $admin = make_user('admin', 'Admin CM 5');

    $this->actingAs($admin)
        ->get(route('admin.notifications.create'))
        ->assertOk()
        ->assertSee('data-flagged-count-url', false)
        ->assertSee('tab=flagged', false)
        ->assertSee('data-moderation-badge', false);
});

it('bỏ cờ / xóa tin nhắn bị gắn cờ vẫn hoạt động như trước (không phụ thuộc AJAX)', function () {
    $admin = make_user('admin', 'Admin CM 6');
    $direct = cm_flagged_direct();
    $group = cm_flagged_group();

    $this->actingAs($admin)->patch(route('admin.chat.direct.unflag', $direct->id))->assertRedirect();
    expect($direct->fresh()->is_flagged)->toBeFalse()
        ->and($direct->fresh()->flagged_at)->toBeNull();

    $this->actingAs($admin)->patch(route('admin.chat.group.unflag', $group->id))->assertRedirect();
    expect($group->fresh()->is_flagged)->toBeFalse()
        ->and($group->fresh()->flagged_at)->toBeNull();

    $this->actingAs($admin)->delete(route('admin.chat.direct.destroy', $direct->id))->assertRedirect();
    $this->actingAs($admin)->delete(route('admin.chat.group.destroy', $group->id))->assertRedirect();

    expect(DirectMessage::find($direct->id))->toBeNull()
        ->and(ChatMessage::find($group->id))->toBeNull();
});

it('tin bị gắn cờ thiếu mốc flagged_at vẫn được đếm theo created_at', function () {
    $admin = make_user('admin', 'Admin CM 7');

    // Tin được gắn cờ trước khi có cột flagged_at -> flagged_at = NULL
    cm_flagged_direct('tin cũ chưa có flagged_at', false);

    $this->actingAs($admin)
        ->get(route('admin.chat.flagged-count'))
        ->assertOk()
        ->assertJson(['count' => 1]);
});
