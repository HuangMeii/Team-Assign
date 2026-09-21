<?php

namespace App\Services\ViolationDetection;

/**
 * Merge text + image moderation outcomes into one flag-only decision.
 *
 * Policy: NEVER block. Controllers always store + broadcast the message.
 * When any checker flags, we set is_flagged + flag_reason + moderation_score
 * so the admin "Bị gắn cờ" tab can review.
 *
 * File này nằm trong violation-detection/src/ và được autoload qua
 * composer.json (psr-4: "App\Services\ViolationDetection\": violation-detection/src/).
 */
class FlagHelper
{
    /**
     * @param array $textCheck  ['is_violation'=>bool,'score'=>float,'reasons'=>array]
     * @param array|null $imageCheck ['passed'=>bool,'violations'=>string|array|null]
     * @param array|null $sensitiveCheck Phase 3 (SensitiveModerationService): cùng shape $textCheck.
     *                                    Nhãn: profanity|insult|threat|dangerous|adult.
     */
    public static function merge(array $textCheck, ?array $imageCheck = null, ?array $sensitiveCheck = null): array
    {
        $flags = [];
        $score = (float) ($textCheck['score'] ?? 0);

        if (!empty($textCheck['is_violation'])) {
            $reasons = implode(',', array_slice($textCheck['reasons'] ?? [], 0, 3));
            $flags[] = 'text:' . ($reasons !== '' ? $reasons : 'fraud') . ' (' . number_format($score, 2) . ')';
        }

        if (is_array($sensitiveCheck) && !empty($sensitiveCheck['is_violation'])) {
            $sensitiveScore = (float) ($sensitiveCheck['score'] ?? 0);
            $score = max($score, $sensitiveScore);
            $reasons = implode(',', array_slice($sensitiveCheck['reasons'] ?? [], 0, 3));
            $flags[] = 'sensitive:' . ($reasons !== '' ? $reasons : 'sensitive') . ' (' . number_format($sensitiveScore, 2) . ')';
        }

        if (is_array($imageCheck) && ($imageCheck['passed'] ?? true) === false) {
            $flags[] = 'image:' . self::describeViolations($imageCheck['violations'] ?? null);
            $score = max($score, 0.9);
        }

        if (empty($flags)) {
            return ['is_flagged' => false, 'flag_reason' => null, 'moderation_score' => $score ?: null, 'flagged_at' => null];
        }

        return [
            'is_flagged' => true,
            'flag_reason' => mb_substr(implode(' | ', $flags), 0, 500),
            'moderation_score' => round($score, 3),
            'flagged_at' => now(),
        ];
    }

    /**
     * Vision có thể trả `violations` dạng string ("adult VERY_LIKELY")
     * hoặc array (['adult','racy']) => luôn chuẩn hoá về 1 chuỗi an toàn
     * để không bị "Array to string conversion" khi ghi flag_reason.
     */
    public static function describeViolations(mixed $violations): string
    {
        if (is_string($violations) && trim($violations) !== '') {
            return trim($violations);
        }

        if (is_array($violations)) {
            $flat = [];
            array_walk_recursive($violations, function ($item) use (&$flat) {
                if (is_scalar($item) && (string) $item !== '') {
                    $flat[] = (string) $item;
                }
            });

            if ($flat !== []) {
                return mb_substr(implode(', ', array_unique($flat)), 0, 255);
            }
        }

        return 'sensitive';
    }
}
