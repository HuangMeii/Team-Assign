<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Gọi LLM cho chatbot "Trợ lý Đề tài" — hỗ trợ 2 driver:
 *
 *   gemini : POST {url} (key ở header x-goog-api-key — KHÔNG đưa key lên URL để khỏi lộ trong log)
 *            body  { contents: [ { parts: [ { text } ] } ] }
 *            đọc   candidates.0.content.parts.0.text
 *
 *   openai : POST {url} (Authorization: Bearer <key> — chuẩn OpenAI)
 *            body  { model, messages: [ { role, content } ] }
 *            đọc   choices.0.message.content
 *            (Groq / OpenRouter / Ollama / Mistral… dùng driver này — chỉ đổi services.<name>.*)
 *
 * Provider chính lỗi (timeout / 429 / 5xx) ⇒ tự thử tiếp provider trong CHATBOT_FALLBACKS.
 * Hết tất cả ⇒ trả ['ok' => false] để controller hiện thông báo thân thiện (KHÔNG bao giờ 500).
 */
class ChatbotService
{
    /** provider => driver */
    private const DRIVERS = [
        'gemini' => 'gemini',
        'groq' => 'openai',
    ];

    /** Provider đang bật (CHATBOT_PROVIDER). */
    public function provider(): string
    {
        $provider = strtolower(trim((string) config('services.chatbot.provider', 'groq')));

        return $provider !== '' ? $provider : 'groq';
    }

    /**
     * Danh sách provider sẽ thử lần lượt: [chính, ...CHATBOT_FALLBACKS] — chỉ giữ provider CÓ key.
     *
     * @return string[]
     */
    public function chain(): array
    {
        $names = array_merge([$this->provider()], (array) config('services.chatbot.fallbacks', []));
        $chain = [];

        foreach ($names as $name) {
            $name = strtolower(trim((string) $name));

            if ($name === '' || in_array($name, $chain, true) || ! isset(self::DRIVERS[$name])) {
                continue;
            }

            if ($this->apiKey($name) === '') {
                continue;
            }

            $chain[] = $name;
        }

        return $chain;
    }

    /** Widget chỉ hiện khi có ÍT NHẤT 1 provider đủ key (thay cho check services.gemini.key). */
    public function configured(): bool
    {
        return $this->chain() !== [];
    }

    /**
     * Gửi prompt tới LLM, tự chuyển provider khi provider trước lỗi.
     *
     * @return array{ok: bool, reply: ?string, provider: ?string, message: ?string}
     */
    public function reply(string $prompt): array
    {
        foreach ($this->chain() as $name) {
            try {
                $reply = $this->call($name, $prompt);
            } catch (ConnectionException $e) {
                Log::warning("Chatbot {$name}: không kết nối được — " . $e->getMessage());

                continue;
            } catch (\Throwable $e) {
                Log::error("Chatbot {$name} lỗi: " . $e->getMessage());

                continue;
            }

            if (is_string($reply) && trim($reply) !== '') {
                return ['ok' => true, 'reply' => trim($reply), 'provider' => $name, 'message' => null];
            }

            Log::warning("Chatbot {$name}: phản hồi rỗng / HTTP lỗi (xem log ngay trên).");
        }

        return [
            'ok' => false,
            'reply' => null,
            'provider' => null,
            'message' => 'Hệ thống đang bận, vui lòng thử lại sau.',
        ];
    }

    private function apiKey(string $name): string
    {
        return trim((string) config("services.{$name}.key"));
    }

    /**
     * HTTP client dùng chung: timeout + retry cho lỗi TẠM THỜI (timeout / 429 / 5xx).
     *
     * LƯU Ý cơ chế Laravel: lỗi kết nối LUÔN ném ConnectionException ra ngoài
     * (PendingRequest::marshalConnectionException) ⇒ reply() bắt exception đó.
     * `throw: false` để khi hết lượt retry, response lỗi được TRẢ VỀ thay vì ném ra ngoài.
     */
    private function client(): PendingRequest
    {
        return Http::connectTimeout(max(1, (int) config('services.chatbot.connect_timeout', 5)))
            ->timeout(max(5, (int) config('services.chatbot.timeout', 30)))
            ->retry(
                max(0, (int) config('services.chatbot.retry', 2)),
                700,
                function (Throwable $e) {
                    if ($e instanceof ConnectionException) {
                        return true;                              // timeout / mất mạng
                    }

                    $status = $e instanceof RequestException ? (int) $e->response->status() : 0;

                    return $status === 429 || $status >= 500;     // quá tải / lỗi phía provider
                },
                throw: false,
            );
    }

    /** Driver Gemini (API gốc Google) — key gửi qua header, KHÔNG để trên URL (tránh lộ trong log). */
    private function callGemini(string $name, string $prompt): ?string
    {
        $url = rtrim((string) config("services.{$name}.url"), '?');

        $response = $this->client()
            ->withHeaders(['x-goog-api-key' => $this->apiKey($name)])
            ->post($url, [
                'contents' => [['parts' => [['text' => $prompt]]]],
            ]);

        if (! $response->successful()) {
            Log::error("Chatbot {$name} HTTP {$response->status()}: " . Str::limit($response->body(), 300));

            return null;
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        return is_string($text) ? $text : null;
    }

    /** Driver chuẩn OpenAI (Groq, OpenRouter, Ollama, Mistral…). */
    private function callOpenAi(string $name, string $prompt): ?string
    {
        $response = $this->client()
            ->withToken($this->apiKey($name))
            ->acceptJson()
            ->post((string) config("services.{$name}.url"), [
                'model' => (string) config("services.{$name}.model"),
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'temperature' => 0.4,
            ]);

        if (! $response->successful()) {
            Log::error("Chatbot {$name} HTTP {$response->status()}: " . Str::limit($response->body(), 300));

            return null;
        }

        $text = data_get($response->json(), 'choices.0.message.content');

        return is_string($text) ? $text : null;
    }

    private function call(string $name, string $prompt): ?string
    {
        return self::DRIVERS[$name] === 'gemini'
            ? $this->callGemini($name, $prompt)
            : $this->callOpenAi($name, $prompt);
    }
}
