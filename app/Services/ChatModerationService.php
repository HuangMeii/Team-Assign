<?php

namespace App\Services;

use App\Services\ViolationDetection\FlagHelper;
use App\Services\ViolationDetection\SensitiveModerationService;
use App\Services\ViolationDetection\TextModerationService;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * L08 (a) — Kiểm duyệt chat chạy SONG SONG 3 bộ lọc bằng `Http::pool()`:
 *
 *   1. fraud text  (TEXT_MODERATION_URL  / 8889) — rules + model PhoBERT
 *   2. sensitive   (MODERATION_URL       / 8890) — rules + model PhoBERT v2 (5 nhãn)
 *   3. vision      (VISION_MODERATION_URL/ 8888) — CHỈ khi tin nhắn có ảnh
 *
 * Trước L08: 3 check chạy NỐI TIẾP (tổng thời gian = t1 + t2 + t3).
 * Sau L08  : chạy ĐỒNG THỜI (thời gian = max) ⇒ nhanh hơn ~50% khi có model.
 *
 * Nguyên tắc KHÔNG đổi: flag-only (chỉ gắn cờ cho admin review, KHÔNG chặn gửi)
 * và fail-open (server tắt/lỗi/timeout ⇒ bỏ qua check đó + log, chat không treo).
 */
class ChatModerationService
{
    /**
     * Phân tích 1 tin nhắn (text + ảnh tuỳ chọn) và trả về ĐÚNG 4 cột cờ
     * (`is_flagged`, `flag_reason`, `moderation_score`, `flagged_at`) để ghi vào DB.
     *
     * @return array{is_flagged:bool,flag_reason:?string,moderation_score:?float,flagged_at:?\Illuminate\Support\Carbon}
     */
    public function analyze(?string $content, ?string $imageRelativePath = null): array
    {
        $content = (string) $content;

        // (1) Phần RULES chạy offline, không tốn thời gian mạng → luôn có kết quả.
        $textRules = TextModerationService::checkRules($content);
        $sensitiveRules = SensitiveModerationService::checkRules($content);

        // (2) Chỉ gọi mạng cho những bộ lọc thực sự cần (mode khác `rules` + có URL).
        $needText = $content !== '' && TextModerationService::needsModel();
        $needSensitive = $content !== '' && SensitiveModerationService::needsModel();
        $needVision = is_string($imageRelativePath) && $imageRelativePath !== '';

        $textModel = null;
        $sensitiveModel = null;
        $imageCheck = null;

        if ($needText || $needSensitive || $needVision) {
            $responses = $this->runPool($needText, $needSensitive, $needVision, $content, $imageRelativePath);

            $textModel = $this->parseResponse(
                $responses['text'] ?? null,
                'Text',
                [TextModerationService::class, 'parseModelResponse']
            );
            $sensitiveModel = $this->parseResponse(
                $responses['sensitive'] ?? null,
                'Sensitive',
                [SensitiveModerationService::class, 'parseModelResponse']
            );

            if ($needVision) {
                $vision = $responses['vision'] ?? null;
                // Fail-open: Vision tắt/lỗi ⇒ coi như "cho qua" (passed = true).
                $imageCheck = $vision instanceof Response && $vision->successful()
                    ? ImageModerationService::parseResponse($vision->json())
                    : ['passed' => true, 'violations' => null, 'skipped' => true];

                if (($imageCheck['passed'] ?? true) === false) {
                    Log::warning('Chat image flagged by Vision (not blocked): '
                        . FlagHelper::describeViolations($imageCheck['violations'] ?? null));
                }
            }
        }

        return FlagHelper::merge(
            TextModerationService::combineWithModel($textRules, $textModel),
            $imageCheck,
            SensitiveModerationService::combineWithModel($sensitiveRules, $sensitiveModel)
        );
    }

    /**
     * Bắn các request cần thiết ĐỒNG THỜI rồi gom response.
     *
     * LƯU Ý: `Http::pool()` gọi `wait()` cho từng promise nên nếu 1 server
     * không kết nối được thì `ConnectionException` sẽ ném ra khỏi pool — ta
     * bắt lại và coi TẤT CẢ là "không có kết quả model" (rơi về rules) để
     * chat không bao giờ bị treo (fail-open).
     *
     * @return array<string, Response>
     */
    private function runPool(
        bool $needText,
        bool $needSensitive,
        bool $needVision,
        string $content,
        ?string $imageRelativePath
    ): array {
        try {
            return Http::pool(function (Pool $pool) use ($needText, $needSensitive, $needVision, $content, $imageRelativePath) {
                // `Pool::as()/__call()` tự đăng ký request vào pool — không cần return.
                if ($needText) {
                    $pool->as('text')
                        ->timeout(TextModerationService::timeout())
                        ->acceptJson()
                        ->post(TextModerationService::modelUrl() . '/predict', ['text' => $content]);
                }

                if ($needSensitive) {
                    $pool->as('sensitive')
                        ->timeout(SensitiveModerationService::timeout())
                        ->post(rtrim((string) SensitiveModerationService::url(), '/') . '/predict', ['text' => $content]);
                }

                if ($needVision) {
                    $spec = ImageModerationService::requestSpec((string) $imageRelativePath);
                    $pool->as('vision')
                        ->timeout(ImageModerationService::timeout())
                        ->post($spec['url'], $spec['payload']);
                }
            });
        } catch (\Throwable $e) {
            Log::warning('Chat moderation pool skipped (mot server AI offline?): ' . $e->getMessage());

            return [];
        }
    }

    /** Response → result qua `$parse`; lỗi HTTP / body sai ⇒ null (fallback rules). */
    private function parseResponse(mixed $response, string $label, callable $parse): ?array
    {
        if (! $response instanceof Response) {
            return null;
        }

        if (! $response->successful()) {
            Log::warning($label . ' moderation HTTP error: ' . $response->status());

            return null;
        }

        return $parse($response->json());
    }
}
