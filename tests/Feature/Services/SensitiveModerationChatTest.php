<?php

use App\Models\ChatMessage;
use App\Models\DirectMessage;
use Illuminate\Support\Facades\Http;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_user;

/*
|--------------------------------------------------------------------------
| Sensitive moderation Phase 3 — luồng chat E2E (flag-only)
|--------------------------------------------------------------------------
| Tin nhắn xúc phạm/nhạy cảm VẪN gửi được, CHỈ bị gắn cờ
| (is_flagged + flag_reason 'sensitive:...' + moderation_score).
| Cần MySQL test theo phpunit.xml. Logic thuần: tests/Unit/SensitiveModerationTest.php
*/

it('chat nhóm: tin nhắn xúc phạm vẫn gửi được và chỉ bị gắn cờ sensitive', function () {
    // Mode rules để test không phụ thuộc server 8890.
    config()->set('services.content_moderation.mode', 'rules');

    $lecturer = make_user('lecturer', 'Giảng viên E');
    $class = make_class(make_subject($lecturer), $lecturer);
    $leader = make_user('student', 'Trưởng nhóm E');
    $group = make_group($leader, $class);

    $this->actingAs($leader)
        ->postJson(route('groups.chat.send', $group->group_id), [
            'content' => 'May ngu nhu cho, im mom di.',
        ])
        ->assertOk();

    $message = ChatMessage::where('group_id', $group->group_id)->first();

    expect($message)->not->toBeNull()
        ->and($message->is_flagged)->toBeTrue()
        ->and($message->flag_reason)->toContain('sensitive:')
        ->and($message->flag_reason)->toContain('insult')
        ->and($message->moderation_score)->toBeGreaterThanOrEqual(0.5)
        ->and($message->flagged_at)->not->toBeNull();
});

it('chat nhóm: tin nhắn sạch không bị gắn cờ', function () {
    config()->set('services.content_moderation.mode', 'rules');

    $lecturer = make_user('lecturer', 'Giảng viên F');
    $class = make_class(make_subject($lecturer), $lecturer);
    $leader = make_user('student', 'Trưởng nhóm F');
    $group = make_group($leader, $class);

    $this->actingAs($leader)
        ->postJson(route('groups.chat.send', $group->group_id), [
            'content' => 'Mai nộp báo cáo chương 2, mọi người nhớ kiểm tra phần mở đầu nhé.',
        ])
        ->assertOk();

    $message = ChatMessage::where('group_id', $group->group_id)->first();

    expect($message->is_flagged)->toBeFalse()
        ->and($message->flag_reason)->toBeNull();
});

it('chat nhóm mode=model: server 8890 trả multi-label thì dùng kết quả đó', function () {
    config()->set('services.content_moderation.url', 'http://127.0.0.1:8890');
    config()->set('services.content_moderation.mode', 'model');

    Http::fake([
        '*/predict' => Http::response([
            'is_violation' => true,
            'score' => 0.91,
            'reasons' => ['threat'],
            'needs_review' => false,
        ], 200),
    ]);

    $lecturer = make_user('lecturer', 'Giảng viên G');
    $class = make_class(make_subject($lecturer), $lecturer);
    $leader = make_user('student', 'Trưởng nhóm G');
    $group = make_group($leader, $class);

    $this->actingAs($leader)
        ->postJson(route('groups.chat.send', $group->group_id), [
            'content' => 'Câu gì cũng được, model tự quyết.',
        ])
        ->assertOk();

    $message = ChatMessage::where('group_id', $group->group_id)->first();

    expect($message->is_flagged)->toBeTrue()
        ->and($message->flag_reason)->toContain('sensitive:threat')
        ->and($message->moderation_score)->toBe(0.91);
});

it('chat cá nhân: tin nhắn nhạy cảm vẫn gửi được và chỉ bị gắn cờ', function () {
    config()->set('services.content_moderation.mode', 'rules');

    $sender = make_user('student', 'Người gửi E');
    $recipient = make_user('student', 'Người nhận E');

    $this->actingAs($sender)
        ->postJson(route('chat.send', $recipient->user_id), [
            'content' => 'Gui anh nong cho tao di.',
        ])
        ->assertOk();

    $message = DirectMessage::where('sender_id', $sender->user_id)->first();

    expect($message)->not->toBeNull()
        ->and($message->is_flagged)->toBeTrue()
        ->and($message->flag_reason)->toContain('sensitive:')
        ->and($message->flag_reason)->toContain('adult')
        ->and($recipient->fresh()->unread_message_count)->toBe(1);
});
