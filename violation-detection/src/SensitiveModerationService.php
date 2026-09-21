<?php

namespace App\Services\ViolationDetection;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Phase 3: kiểm duyệt XÚC PHẠM / NỘI DUNG NHẠY CẢM bằng PhoBERT v2
 * (violation-detection/python/app_moderation.py, port 8890, model 5 nhãn multi-label).
 *
 * Nhãn (đã kiểm chứng bằng probe thực tế trên model):
 *   0=profanity, 1=insult, 2=threat, 3=dangerous, 4=adult.
 * Model KHÔNG có output "clean": clean = không nhãn nào >= moderation_threshold.
 * Một câu có thể khớp NHIỀU nhãn cùng lúc (sigmoid từng nhãn, KHÔNG softmax).
 *
 * - rules : từ điển tiếng Việt offline (fallback, không gọi mạng).
 * - model : gọi server FastAPI (Http timeout 3s).
 * - hybrid: rule OR model (điểm cao nhất) — mọi lỗi model đều fallback về rules
 *           (fail-open): chat KHÔNG bao giờ bị chặn/treo vì moderation.
 *
 * Kết quả là FLAG-ONLY: chỉ gắn cờ cho admin review, không chặn gửi.
 */
class SensitiveModerationService
{
    /**
     * Từ điển rule-based tiếng Việt (fallback offline, không cần model).
     * So khớp trên text đã bỏ dấu (normalize) nên "dit me" khớp "địt mẹ".
     * phrase => [weight, tên nhãn] — tên nhãn TRÙNG với model:
     * profanity | insult | threat | dangerous | adult.
     */
    private const RULES = [
        // --- profanity (chửi thề) ---
        'dit me' => [0.75, 'profanity'], 'ditme' => [0.7, 'profanity'],
        'du ma' => [0.7, 'profanity'], 'dm may' => [0.75, 'profanity'],
        'vai lon' => [0.75, 'profanity'], 'clgt' => [0.7, 'profanity'],

        // --- insult (xúc phạm cá nhân) ---
        'con cho' => [0.7, 'insult'], 'do ngu' => [0.6, 'insult'],
        'ngu ngoc' => [0.6, 'insult'], 'ngu nhu cho' => [0.8, 'insult'],
        'thang cho' => [0.7, 'insult'], 'vo hoc' => [0.5, 'insult'],
        'cut di' => [0.6, 'insult'], 'im mom' => [0.55, 'insult'],

        // --- threat (đe doạ) ---
        'tao se giet' => [0.9, 'threat'], 'giet may' => [0.9, 'threat'],
        'danh cho chet' => [0.85, 'threat'], 'bop chet' => [0.8, 'threat'],
        'tra thu' => [0.65, 'threat'], 'tao khong de yen' => [0.6, 'threat'],

        // --- dangerous (nội dung nguy hiểm) ---
        'lam bom' => [0.85, 'dangerous'], 'che tao sung' => [0.85, 'dangerous'],
        'mua sung' => [0.65, 'dangerous'], 'ma tuy' => [0.8, 'dangerous'],
        'heroin' => [0.75, 'dangerous'], 'thuoc l' => [0.6, 'dangerous'],

        // --- adult (nội dung 18+) ---
        'lam tinh' => [0.7, 'adult'], 'quan he tinh duc' => [0.8, 'adult'],
        'khoe than' => [0.7, 'adult'], 'anh nong' => [0.65, 'adult'],
        'clip nong' => [0.7, 'adult'], 'sex' => [0.6, 'adult'],
        'nude' => [0.7, 'adult'], 'khieu dam' => [0.7, 'adult'],
    ];

    public static function check(?string $text): array
    {
        $text = trim((string) $text);
        if ($text === '') {
            return ['is_violation' => false, 'score' => 0.0, 'reasons' => [], 'needs_review' => false, 'skipped' => false];
        }

        $mode = self::mode();
        $ruleResult = self::checkRules($text);

        if ($mode === 'rules') {
            return $ruleResult;
        }

        $modelResult = self::checkModel($text);

        // Server model chưa cấu hình / tắt / lỗi => dùng rules (fail-open).
        if ($modelResult === null) {
            return $ruleResult;
        }

        if ($mode === 'model') {
            return $modelResult;
        }

        return self::mergeResults($ruleResult, $modelResult); // hybrid
    }

    private static function mode(): string
    {
        return (string) (config('services.content_moderation.mode') ?? 'rules');
    }

    public static function url(): ?string
    {
        return config('services.content_moderation.url');
    }

    /**
     * Rule-based: mỗi nhãn giữ điểm cao nhất trong các từ khoá khớp.
     * Có thể trả NHIỀU reason nếu câu chứa từ khoá của nhiều nhãn.
     */
    public static function checkRules(?string $text): array
    {
        $text = trim((string) $text);
        if ($text === '') {
            return self::empty();
        }

        $norm = TextModerationService::normalize($text);
        $thresholds = self::thresholds();

        /** @var array<string, array{score: float, hits: array<int, string>}> $byLabel */
        $byLabel = [];

        foreach (self::RULES as $phrase => [$weight, $label]) {
            if (mb_strpos($norm, $phrase) === false) {
                continue;
            }

            $byLabel[$label] ??= ['score' => 0.0, 'hits' => []];
            $byLabel[$label]['score'] = min(1.0, $byLabel[$label]['score'] + $weight);
            $byLabel[$label]['hits'][] = $phrase;
        }

        if ($byLabel === []) {
            return self::empty();
        }

        // Sắp theo điểm giảm dần để nhãn nặng nhất đứng đầu (admin đọc là thấy ngay).
        uasort($byLabel, fn ($a, $b) => $b['score'] <=> $a['score']);

        $threshold = (float) ($thresholds['moderation_threshold'] ?? 0.5);
        $reviewThreshold = (float) ($thresholds['moderation_review_threshold'] ?? 0.35);
        $reasons = [];
        $score = 0.0;

        foreach ($byLabel as $label => $info) {
            $labelScore = round((float) $info['score'], 3);
            $score = max($score, $labelScore);

            if ($labelScore >= $threshold) {
                $reasons[] = $label; // tên nhãn thuần; FlagHelper sẽ thêm điểm tổng ở cuối flag_reason
            }
        }

        return [
            'is_violation' => $reasons !== [],
            'score' => round($score, 3),
            'reasons' => $reasons,
            'needs_review' => $reasons === [] && $score >= $reviewThreshold,
            'skipped' => false,
        ];
    }

    /**
     * Gọi server FastAPI (app_moderation.py). Trả về null khi server tắt /
     * lỗi / timeout để caller fallback về rules (fail-open).
     */
    private static function checkModel(string $text): ?array
    {
        $url = self::url();
        if (!is_string($url) || $url === '') {
            return null;
        }

        try {
            $response = Http::timeout(3)->post(rtrim($url, '/') . '/predict', ['text' => $text]);

            if (!$response->ok()) {
                Log::warning('Sensitive moderation server lỗi: HTTP ' . $response->status());

                return null;
            }

            $data = $response->json();
            if (!is_array($data) || !array_key_exists('score', $data)) {
                Log::warning('Sensitive moderation response không hợp lệ.');

                return null;
            }

            $score = round((float) ($data['score'] ?? 0), 3);
            $isViolation = (bool) ($data['is_violation'] ?? false);
            $thresholds = self::thresholds();

            return [
                'is_violation' => $isViolation,
                'score' => $score,
                'reasons' => array_values(array_filter((array) ($data['reasons'] ?? []), 'is_string')),
                'needs_review' => array_key_exists('needs_review', $data)
                    ? (bool) $data['needs_review']
                    : ($score >= (float) ($thresholds['moderation_review_threshold'] ?? 0.35) && !$isViolation),
                'skipped' => false,
            ];
        } catch (\Throwable $e) {
            Log::warning('Sensitive moderation skipped (model server offline?): ' . $e->getMessage());

            return null;
        }
    }

    /** hybrid: rule OR model — một câu có thể ra NHIỀU nhãn. */
    private static function mergeResults(array $ruleResult, array $modelResult): array
    {
        $reasons = [];
        if (!empty($ruleResult['is_violation'])) {
            $reasons = array_merge($reasons, (array) $ruleResult['reasons']);
        }
        if (!empty($modelResult['is_violation'])) {
            $reasons = array_merge($reasons, (array) $modelResult['reasons']);
        }

        return [
            'is_violation' => !empty($ruleResult['is_violation']) || !empty($modelResult['is_violation']),
            'score' => max((float) $ruleResult['score'], (float) $modelResult['score']),
            'reasons' => array_values(array_unique($reasons)),
            'needs_review' => !empty($ruleResult['needs_review']) || !empty($modelResult['needs_review']),
            'skipped' => false,
        ];
    }

    public static function empty(): array
    {
        return ['is_violation' => false, 'score' => 0.0, 'reasons' => [], 'needs_review' => false, 'skipped' => false];
    }

    /** Ngưỡng + tên nhãn nằm ở violation-detection/config/thresholds.json. */
    public static function thresholds(): array
    {
        return TextModerationService::thresholds();
    }
}

