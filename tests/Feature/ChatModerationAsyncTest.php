<?php

/*
|--------------------------------------------------------------------------
| L08 — Chat song song + hien truoc gan co sau (giu du 3 loc)
|--------------------------------------------------------------------------
| Chot: (a) song song hoa 3 check bang Http::pool() + (c) hien tin truoc,
|       gan co SAU response bang job afterResponse().  BO (b) short-circuit.
|
| Bao phu:
|  1. Gui tin -> hien NGAY, kiem duyet duoc hen SAU response (Bus::fake).
|  2. Job ModerateDirectMessage::handle() gan du 4 cot co.
|  3. Job ModerateGroupMessage::handle() gan co tin nhom, BO QUA tin admin.
|  4. 1 lan gui co anh -> pool goi du 3 bo loc (8889 + 8890 + 8888).
|  5. Server AI chet -> fail-open: tin van gui, khong gan co.
*/

use App\Jobs\ModerateDirectMessage;
use App\Jobs\ModerateGroupMessage;
use App\Models\ChatMessage;
use App\Models\DirectMessage;
use App\Services\ChatModerationService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

use function Tests\Support\make_class;
use function Tests\Support\make_group;
use function Tests\Support\make_subject;
use function Tests\Support\make_user;

it('gui tin 1-1: hien ngay va kiem duyet duoc hen SAU response (khong chan gui)', function () {
    Bus::fake();

    $sender = make_user('student', 'Nguoi gui L08');
    $recipient = make_user('student', 'Nguoi nhan L08');

    $this->actingAs($sender)
        ->postJson(route('chat.send', $recipient->user_id), [
            'content' => 'Ban de tai gia 200k, ib telegram @abc.',
        ])
        ->assertOk();

    $message = DirectMessage::first();

    // Tin nhan da hien ngay (chua kip kiem duyet) va khong bi chan.
    expect($message)->not->toBeNull()
        ->and($message->is_flagged)->toBeFalse()
        ->and($recipient->fresh()->unread_message_count)->toBe(1);

    // Job kiem duyet duoc len lich chay SAU khi response da gui.
    Bus::assertDispatchedAfterResponse(
        ModerateDirectMessage::class,
        fn ($job) => $job->messageId === $message->id
    );
});

it('job ModerateDirectMessage::handle() gan du 4 cot co cho tin nhan fraud', function () {
    config()->set('services.content_moderation.mode', 'rules');
    config()->set('services.text_moderation.mode', 'rules');

    $sender = make_user('student', 'Nguoi gui L08b');
    $recipient = make_user('student', 'Nguoi nhan L08b');

    $message = DirectMessage::create([
        'sender_id'    => $sender->user_id,
        'recipient_id' => $recipient->user_id,
        'content'      => 'Ban slot de tai gia 200k, ib telegram @abc.',
    ]);

    app()->call([new ModerateDirectMessage($message->id), 'handle']);

    $fresh = $message->fresh();
    expect($fresh->is_flagged)->toBeTrue()
        ->and($fresh->flag_reason)->toContain('text:')
        ->and($fresh->moderation_score)->toBeGreaterThanOrEqual(0.55)
        ->and($fresh->flagged_at)->not->toBeNull();
});

it('job ModerateGroupMessage::handle() gan co tin nhom nhung BO QUA tin cua admin', function () {
    config()->set('services.content_moderation.mode', 'rules');
    config()->set('services.text_moderation.mode', 'rules');

    $lecturer = make_user('lecturer', 'Giang vien L08');
    $class = make_class(make_subject($lecturer), $lecturer);
    $leader = make_user('student', 'Truong nhom L08');
    $group = make_group($leader, $class);

    $memberMessage = ChatMessage::create([
        'group_id' => $group->group_id,
        'user_id'  => $leader->user_id,
        'content'  => 'May ngu nhu cho, im mom di.',
        'type'     => ChatMessage::TYPE_MEMBER,
    ]);

    $adminMessage = ChatMessage::create([
        'group_id' => $group->group_id,
        'user_id'  => $lecturer->user_id,
        'content'  => 'Thong bao: lop nghi hoc tuan sau.',
        'type'     => ChatMessage::TYPE_ANNOUNCEMENT,
    ]);

    app()->call([new ModerateGroupMessage($memberMessage->id), 'handle']);
    app()->call([new ModerateGroupMessage($adminMessage->id), 'handle']);

    expect($memberMessage->fresh()->is_flagged)->toBeTrue()
        ->and($memberMessage->fresh()->flag_reason)->toContain('sensitive:')
        // Tin thong bao cua admin la noi dung he thong => khong bao gio bi gan co.
        ->and($adminMessage->fresh()->is_flagged)->toBeFalse()
        ->and($adminMessage->fresh()->flag_reason)->toBeNull();
});

it('mot lan gui co anh: pool goi du 3 bo loc (text 8889 + sensitive 8890 + vision 8888)', function () {
    config()->set('services.text_moderation.url', 'http://127.0.0.1:8889');
    config()->set('services.text_moderation.mode', 'hybrid');
    config()->set('services.content_moderation.url', 'http://127.0.0.1:8890');
    config()->set('services.content_moderation.mode', 'hybrid');

    Http::fake(['*' => Http::response([
        'is_violation' => false,
        'score' => 0.1,
        'reasons' => [],
        'passed' => true,
        'flagged' => [],
    ], 200)]);

    $flag = app(ChatModerationService::class)->analyze('Noi dung binh thuong', 'chat-attachments/anh.jpg');

    expect($flag['is_flagged'])->toBeFalse();

    Http::assertSent(fn ($request) => str_contains($request->url(), ':8889') && str_ends_with($request->url(), '/predict'));
    Http::assertSent(fn ($request) => str_contains($request->url(), ':8890') && str_ends_with($request->url(), '/predict'));
    Http::assertSent(fn ($request) => str_contains($request->url(), ':8888') && str_ends_with($request->url(), '/check-review-images'));
});

it('server AI chet: tin nhan van gui duoc va khong bi gan co (fail-open)', function () {
    config()->set('services.text_moderation.url', 'http://127.0.0.1:8889');
    config()->set('services.text_moderation.mode', 'model');

    // Mo phong moi request deu loi ket noi => pool nem ConnectionException.
    Http::fake([
        '*' => function () {
            throw new ConnectionException('AI server offline');
        },
    ]);

    $sender = make_user('student', 'Nguoi gui L08c');
    $recipient = make_user('student', 'Nguoi nhan L08c');

    $this->actingAs($sender)
        ->postJson(route('chat.send', $recipient->user_id), [
            'content' => 'Noi dung sach binh thuong, khong vi pham gi.',
        ])
        ->assertOk();

    $message = DirectMessage::first();

    expect($message)->not->toBeNull()
        ->and($message->is_flagged)->toBeFalse()
        ->and($message->flag_reason)->toBeNull();
});
