<?php

use App\Models\Topics;
use Illuminate\Support\Facades\Http;

use function Tests\Support\make_class;
use function Tests\Support\make_subject;
use function Tests\Support\make_user;

/*
|--------------------------------------------------------------------------
| Chatbot trợ lý đề tài (Gemini) — /chatbot/ask
|--------------------------------------------------------------------------
| Đọc cấu hình qua config('services.gemini.*'). Thiếu key => trả thông báo
| thân thiện (200), KHÔNG gọi mạng, KHÔNG lỗi 500.
*/

it('chatbot gọi Gemini và trả lời khi đã cấu hình key', function () {
    make_class(make_subject(make_user('lecturer', 'GV Bot')), null);

    config()->set('services.gemini.key', 'test-key');
    config()->set('services.gemini.url', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent');

    Http::fake([
        '*generativelanguage.googleapis.com*' => Http::response([
            'candidates' => [
                ['content' => ['parts' => [['text' => 'Chào bạn! Mình có thể giúp gì về đề tài hôm nay?']]]],
            ],
        ], 200),
    ]);

    $response = $this->actingAs(make_user('student', 'SV Bot'))
        ->postJson(route('chatbot.ask'), ['message' => 'Có đề tài nào còn trống không?']);

    $response->assertOk()
        ->assertJsonPath('reply', 'Chào bạn! Mình có thể giúp gì về đề tài hôm nay?');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'generateContent')
        && str_contains($request->data()['contents'][0]['parts'][0]['text'] ?? '', 'Có đề tài nào còn trống không?'));
});

it('thiếu GEMINI_API_KEY thì trả thông báo thân thiện và KHÔNG gọi mạng', function () {
    config()->set('services.gemini.key', '');

    Http::fake();

    $response = $this->actingAs(make_user('student', 'SV Bot 2'))
        ->postJson(route('chatbot.ask'), ['message' => 'Xin chào']);

    $response->assertOk()
        ->assertJsonPath('reply', fn ($reply) => str_contains($reply, 'chưa được cấu hình'));

    Http::assertNothingSent();
});

it('tin nhắn rỗng không gọi Gemini', function () {
    config()->set('services.gemini.key', 'test-key');

    Http::fake();

    $response = $this->actingAs(make_user('student', 'SV Bot 3'))
        ->postJson(route('chatbot.ask'), ['message' => '   ']);

    $response->assertOk()
        ->assertJsonPath('reply', 'Bạn hãy nhập câu hỏi nhé!');

    Http::assertNothingSent();
});

it('Gemini lỗi 500 thì trả thông báo bận (503) thay vì crash', function () {
    config()->set('services.gemini.key', 'test-key');

    Http::fake([
        '*generativelanguage.googleapis.com*' => Http::response('upstream error', 500),
    ]);

    $response = $this->actingAs(make_user('student', 'SV Bot 4'))
        ->postJson(route('chatbot.ask'), ['message' => 'Gợi ý đề tài Laravel?']);

    $response->assertStatus(503)
        ->assertJsonPath('reply', 'Hệ thống đang bận, vui lòng thử lại sau.');
});
