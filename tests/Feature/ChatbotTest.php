<?php

use App\Models\Topics;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

use function Tests\Support\make_class;
use function Tests\Support\make_subject;
use function Tests\Support\make_user;

/*
|--------------------------------------------------------------------------
| Chatbot trợ lý đề tài (Groq / Gemini) — /chatbot/ask
|--------------------------------------------------------------------------
| Provider chọn qua config('services.chatbot.provider'):
|   groq   → POST api.groq.com/openai/v1/chat/completions (chuẩn OpenAI, Authorization: Bearer)
|   gemini → POST generativelanguage.googleapis.com/...:generateContent (contents/parts, key ở header)
| Không provider nào có key ⇒ thông báo thân thiện (200), KHÔNG gọi mạng, KHÔNG lỗi 500.
| Provider chính lỗi ⇒ tự chuyển provider trong config('services.chatbot.fallbacks').
*/

beforeEach(function () {
    config()->set('services.chatbot.provider', 'groq');
    config()->set('services.chatbot.fallbacks', []);
    config()->set('services.chatbot.retry', 0);        // test nhanh + tất định
    config()->set('services.chatbot.timeout', 5);
    config()->set('services.chatbot.connect_timeout', 2);
    config()->set('services.groq.key', '');
    config()->set('services.groq.url', 'https://api.groq.com/openai/v1/chat/completions');
    config()->set('services.groq.model', 'openai/gpt-oss-120b');
    config()->set('services.gemini.key', '');
});

it('chatbot gọi Groq (chuẩn OpenAI) và trả lời khi đã cấu hình key', function () {
    make_class(make_subject(make_user('lecturer', 'GV Bot')), null);

    config()->set('services.groq.key', 'test-groq-key');

    Http::fake([
        'api.groq.com/*' => Http::response([
            'choices' => [['message' => ['content' => 'Chào bạn! Mình có thể giúp gì về đề tài hôm nay?']]],
        ], 200),
    ]);

    $response = $this->actingAs(make_user('student', 'SV Bot'))
        ->postJson(route('chatbot.ask'), ['message' => 'Có đề tài nào còn trống không?']);

    $response->assertOk()
        ->assertJsonPath('reply', 'Chào bạn! Mình có thể giúp gì về đề tài hôm nay?');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
        && $request->hasHeader('Authorization', 'Bearer test-groq-key')
        && ($request->data()['model'] ?? '') === 'openai/gpt-oss-120b'
        && str_contains($request->data()['messages'][0]['content'] ?? '', 'Có đề tài nào còn trống không?'));
});

it('chatbot gọi Gemini khi provider là gemini — key ở header, KHÔNG trên URL', function () {
    config()->set('services.chatbot.provider', 'gemini');
    config()->set('services.gemini.key', 'test-key');
    config()->set('services.gemini.url', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent');

    Http::fake([
        '*generativelanguage.googleapis.com*' => Http::response([
            'candidates' => [
                ['content' => ['parts' => [['text' => 'Chào bạn! Mình có thể giúp gì về đề tài hôm nay?']]]],
            ],
        ], 200),
    ]);

    $response = $this->actingAs(make_user('student', 'SV Bot Gemini'))
        ->postJson(route('chatbot.ask'), ['message' => 'Gợi ý đề tài Laravel?']);

    $response->assertOk()->assertJsonPath('reply', 'Chào bạn! Mình có thể giúp gì về đề tài hôm nay?');

    Http::assertSent(fn ($request) => str_contains($request->url(), ':generateContent')
        && ! str_contains($request->url(), 'key=')
        && $request->hasHeader('x-goog-api-key', 'test-key')
        && str_contains($request->data()['contents'][0]['parts'][0]['text'] ?? '', 'Gợi ý đề tài Laravel?'));
});

it('thiếu API key thì trả thông báo thân thiện và KHÔNG gọi mạng', function () {
    Http::fake();

    $response = $this->actingAs(make_user('student', 'SV Bot 2'))
        ->postJson(route('chatbot.ask'), ['message' => 'Xin chào']);

    $response->assertOk()
        ->assertJsonPath('reply', fn ($reply) => str_contains($reply, 'chưa được cấu hình'));

    Http::assertNothingSent();
});

it('tin nhắn rỗng không gọi LLM', function () {
    config()->set('services.groq.key', 'test-groq-key');

    Http::fake();

    $response = $this->actingAs(make_user('student', 'SV Bot 3'))
        ->postJson(route('chatbot.ask'), ['message' => '   ']);

    $response->assertOk()->assertJsonPath('reply', 'Bạn hãy nhập câu hỏi nhé!');

    Http::assertNothingSent();
});

it('Groq lỗi 500 thì trả thông báo bận (503) thay vì crash', function () {
    config()->set('services.groq.key', 'test-groq-key');

    Http::fake([
        'api.groq.com/*' => Http::response('upstream error', 500),
    ]);

    $response = $this->actingAs(make_user('student', 'SV Bot 4'))
        ->postJson(route('chatbot.ask'), ['message' => 'Gợi ý đề tài Laravel?']);

    $response->assertStatus(503)
        ->assertJsonPath('reply', 'Hệ thống đang bận, vui lòng thử lại sau.');
});

it('Groq timeout/mất mạng thì trả 503, KHÔNG bao giờ 500', function () {
    config()->set('services.groq.key', 'test-groq-key');

    Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

    $response = $this->actingAs(make_user('student', 'SV Bot 5'))
        ->postJson(route('chatbot.ask'), ['message' => 'Gợi ý đề tài Laravel?']);

    $response->assertStatus(503)
        ->assertJsonPath('reply', 'Hệ thống đang bận, vui lòng thử lại sau.');
});

it('Groq lỗi thì tự chuyển sang provider dự phòng (fallback gemini)', function () {
    config()->set('services.groq.key', 'test-groq-key');
    config()->set('services.chatbot.fallbacks', ['gemini']);
    config()->set('services.gemini.key', 'test-key');
    config()->set('services.gemini.url', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent');

    Http::fake([
        'api.groq.com/*' => Http::response('upstream error', 503),
        '*generativelanguage.googleapis.com*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'Trả lời từ Gemini dự phòng.']]]]],
        ], 200),
    ]);

    $response = $this->actingAs(make_user('student', 'SV Bot 6'))
        ->postJson(route('chatbot.ask'), ['message' => 'Gợi ý đề tài Laravel?']);

    $response->assertOk()->assertJsonPath('reply', 'Trả lời từ Gemini dự phòng.');

    // 1 request Groq (503) + 1 request Gemini (200)
    Http::assertSentCount(2);
});

it('widget chatbot hiện dòng miễn trừ (từ CHATBOT_DISCLAIMER) ở cuối mỗi câu trả lời', function () {
    config()->set('services.groq.key', 'test-groq-key');   // có key ⇒ widget không tự ẩn
    make_class(make_subject(make_user('lecturer', 'GV Disclaimer')), null);

    $this->actingAs(make_user('student', 'SV Disclaimer'))
        ->get(route('user.topics'))
        ->assertOk()
        // Khối #chat-disclaimer = nguồn câu chữ cho JS (.env CHATBOT_DISCLAIMER → config → widget).
        ->assertSee('id="chat-disclaimer"', false)
        ->assertSee('chỉ mang tính chất tham khảo')
        ->assertSee('giảng viên phụ trách');
});

it('để trống CHATBOT_DISCLAIMER thì widget KHÔNG hiện dòng miễn trừ', function () {
    config()->set('services.groq.key', 'test-groq-key');
    config()->set('services.chatbot.disclaimer', '');
    make_class(make_subject(make_user('lecturer', 'GV Disclaimer 2')), null);

    $this->actingAs(make_user('student', 'SV Disclaimer 2'))
        ->get(route('user.topics'))
        ->assertOk()
        ->assertDontSee('id="chat-disclaimer"', false)
        ->assertDontSee('chỉ mang tính chất tham khảo');
});

it('CHATBOT_RETRY=2 thì gọi lại lần 2 khi provider trả 5xx', function () {
    config()->set('services.groq.key', 'test-groq-key');
    config()->set('services.chatbot.retry', 2);

    Http::fake([
        'api.groq.com/*' => Http::response('upstream error', 503),
    ]);

    $this->actingAs(make_user('student', 'SV Bot 7'))
        ->postJson(route('chatbot.ask'), ['message' => 'Gợi ý đề tài Laravel?'])
        ->assertStatus(503);

    Http::assertSentCount(2);
});
