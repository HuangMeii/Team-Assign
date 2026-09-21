<?php

use App\Models\ChatMessage;
use App\Models\DirectMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_user;

/*
|--------------------------------------------------------------------------
| Violation detection (flag-only) — violation-detection/
|--------------------------------------------------------------------------
| Nguyên tắc: mọi vi phạm CHỈ gắn cờ (is_flagged = 1), KHÔNG chặn gửi,
| KHÔNG xoá file ảnh. Dataset: violation-detection/datasets/chat_fraud_dataset.csv
|
| LƯU Ý: file này cần MySQL test theo phpunit.xml (DB_HOST/DB_PORT/DB_DATABASE).
| Test logic thuần, không cần DB: tests/Unit/ViolationDetectionTest.php
*/

it('chat nhóm: tin nhắn fraud vẫn được gửi bình thường và chỉ bị gắn cờ', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');
    $class = make_class(make_subject($lecturer), $lecturer);
    $leader = make_user('student', 'Trưởng nhóm A');
    $group = make_group($leader, $class);

    $this->actingAs($leader)
        ->postJson(route('groups.chat.send', $group->group_id), [
            'content' => 'Bán slot đề tài giá 200k, ib telegram @abc.',
        ])
        ->assertOk();

    $message = ChatMessage::where('group_id', $group->group_id)->first();

    expect($message)->not->toBeNull()
        ->and($message->is_flagged)->toBeTrue()
        ->and($message->flag_reason)->toContain('text:')
        ->and($message->moderation_score)->toBeGreaterThanOrEqual(0.55)
        ->and($message->flagged_at)->not->toBeNull();
});

it('chat nhóm: tin nhắn sạch không bị gắn cờ', function () {
    $lecturer = make_user('lecturer', 'Giảng viên B');
    $class = make_class(make_subject($lecturer), $lecturer);
    $leader = make_user('student', 'Trưởng nhóm B');
    $group = make_group($leader, $class);

    $this->actingAs($leader)
        ->postJson(route('groups.chat.send', $group->group_id), [
            'content' => 'Mai nộp báo cáo, mọi người nhớ kiểm tra phần mở đầu nhé.',
        ])
        ->assertOk();

    $message = ChatMessage::where('group_id', $group->group_id)->first();

    expect($message->is_flagged)->toBeFalse()
        ->and($message->flag_reason)->toBeNull();
});

it('chat nhóm: ảnh nhạy cảm VẪN gửi được, KHÔNG bị xoá, chỉ gắn cờ (flag-only)', function () {
    Storage::fake('public', ['url' => 'http://localhost/storage']);
    Http::fake(['*' => Http::response([
        'passed'  => false,
        'flagged' => [['violations' => 'adult VERY_LIKELY']],
    ], 200)]);

    $lecturer = make_user('lecturer', 'Giảng viên C');
    $class = make_class(make_subject($lecturer), $lecturer);
    $leader = make_user('student', 'Trưởng nhóm C');
    $group = make_group($leader, $class);

    $this->actingAs($leader)
        ->post(route('groups.chat.send', $group->group_id), [
            'content'    => 'Ảnh này hay nè',
            'attachment' => UploadedFile::fake()->create('nhay-cam.jpg', 50, 'image/jpeg'),
        ], ['Accept' => 'application/json'])
        ->assertOk();

    $message = ChatMessage::where('group_id', $group->group_id)->first();

    expect($message)->not->toBeNull()
        ->and($message->attachment)->not->toBeNull()
        ->and($message->is_flagged)->toBeTrue()
        ->and($message->flag_reason)->toContain('image:adult VERY_LIKELY')
        ->and($message->moderation_score)->toBeGreaterThanOrEqual(0.9)
        ->and(Storage::disk('public')->exists($message->attachment))->toBeTrue();
});

it('chat cá nhân: tin nhắn fraud vẫn được gửi và chỉ bị gắn cờ', function () {
    $sender = make_user('student', 'Người gửi A');
    $recipient = make_user('student', 'Người nhận B');

    $this->actingAs($sender)
        ->postJson(route('chat.send', $recipient->user_id), [
            'content' => 'Bán đáp án môn CNPM giá 1 triệu, ai cần ib zalo.',
        ])
        ->assertOk();

    $message = DirectMessage::where('sender_id', $sender->user_id)->first();

    expect($message)->not->toBeNull()
        ->and($message->is_flagged)->toBeTrue()
        ->and($message->flag_reason)->toContain('text:')
        ->and($recipient->fresh()->unread_message_count)->toBe(1);
});

it('admin xem tab "Bị gắn cờ" và bỏ cờ được tin nhắn cá nhân', function () {
    $admin = make_user('admin', 'Quản trị viên');
    $sender = make_user('student', 'Người gửi C');
    $recipient = make_user('student', 'Người nhận D');

    $message = DirectMessage::create([
        'sender_id'        => $sender->user_id,
        'recipient_id'     => $recipient->user_id,
        'content'          => 'Bán đề tài giá 200k',
        'is_flagged'       => true,
        'flag_reason'      => 'text:bán đề tài (0.55)',
        'moderation_score' => 0.55,
        'flagged_at'       => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.chat.monitor', ['tab' => 'flagged']))
        ->assertOk()
        ->assertSee('Bị gắn cờ')
        ->assertSee('Bán đề tài giá 200k')
        // Cột "Lý do / Điểm" đã bị ẩn: badge lý do + điểm chỉ dùng nội bộ, không render ra UI
        ->assertDontSee('text:bán đề tài (0.55)')
        ->assertDontSee('Lý do / Điểm');

    $this->actingAs($admin)
        ->patch(route('admin.chat.direct.unflag', $message->id))
        ->assertRedirect();

    expect($message->fresh()->is_flagged)->toBeFalse()
        ->and($message->fresh()->flag_reason)->toBeNull()
        ->and($message->fresh()->moderation_score)->toBeNull();
});

it('admin bỏ cờ được tin nhắn nhóm bị gắn cờ', function () {
    $admin = make_user('admin', 'Quản trị viên 2');
    $lecturer = make_user('lecturer', 'Giảng viên D');
    $class = make_class(make_subject($lecturer), $lecturer);
    $leader = make_user('student', 'Trưởng nhóm D');
    $group = make_group($leader, $class);

    $message = ChatMessage::create([
        'group_id'         => $group->group_id,
        'user_id'          => $leader->user_id,
        'content'          => 'Chia sẻ acc học online giá 2 triệu',
        'is_flagged'       => true,
        'flag_reason'      => 'text:chia sẻ acc (0.85)',
        'moderation_score' => 0.85,
        'flagged_at'       => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.chat.monitor', ['tab' => 'flagged', 'min_score' => 0.5]))
        ->assertOk()
        ->assertSee('Chia sẻ acc học online giá 2 triệu');

    $this->actingAs($admin)
        ->patch(route('admin.chat.group.unflag', $message->id))
        ->assertRedirect();

    expect($message->fresh()->is_flagged)->toBeFalse()
        ->and($message->fresh()->flag_reason)->toBeNull();
});
