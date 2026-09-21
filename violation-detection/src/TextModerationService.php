<?php

namespace App\Services\ViolationDetection;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Phase 1: rule-based text moderation derived from
 * violation-detection/datasets/chat_fraud_dataset.csv (label 1 = fraud).
 *
 * - Offline, fast, không cần GPU/thêm dependency.
 * - Unicode-safe: bỏ dấu tiếng Việt nên "lam ho" vẫn khớp "làm hộ".
 * - Bộ luật bên dưới được trích thủ công từ các cụm fraud lặp nhiều nhất
 *   trong dataset (bán/mua đề tài, làm hộ, xin/bán đáp án, giá + liên hệ
 *   riêng, đe doạ tống tiền...). Muốn trích lại tự động:
 *   `php violation-detection/tools/extract_keywords.php`
 * - Ngưỡng nằm ở violation-detection/config/thresholds.json để admin tune
 *   mà không phải sửa code.
 * - Phase 2 (PhoBERT FastAPI) giữ NGUYÊN chữ ký check() nên Controller
 *   không phải sửa lại lần nữa.
 *
 * File này nằm trong violation-detection/src/ và được autoload qua
 * composer.json (psr-4: App\Services\ViolationDetection\ => violation-detection/src/).
 */
class TextModerationService
{
    /** Strong fraud signals: phrase => weight */
    private const STRONG_RULES = [
        'bán đề tài' => 0.55, 'mua đề tài' => 0.55, 'bán slot' => 0.5,
        'pass lại đề tài' => 0.55, 'thanh lý đề tài' => 0.5,
        'làm hộ' => 0.55, 'làm ho ' => 0.4, 'viết hộ' => 0.5, 'thuê làm hộ' => 0.6,
        'bao bảo vệ' => 0.45, 'bao qua môn' => 0.45, 'bao trọn gói' => 0.45,
        'đáp án' => 0.35, 'đề thi và đáp án' => 0.55, 'bán đáp án' => 0.6, 'xin đáp án' => 0.5,
        'chia sẻ acc' => 0.5, 'bán acc' => 0.5, 'share acc' => 0.5,
        'tống tiền' => 0.8, 'nếu không chuyển' => 0.6, 'nếu không muốn bị' => 0.65,
        'chuyển khoản' => 0.3, 'chuyển tiền' => 0.3,
        'đánh giá cao thì chuyển' => 0.6, 'qua môn thì' => 0.3,
        'giả mạo' => 0.45, 'mạo danh' => 0.5, 'lừa' => 0.4,
        'cần tiền gấp' => 0.4, 'khó khăn, cần' => 0.3,
        'bán quyền truy cập' => 0.6, 'quyền truy cập hệ thống' => 0.55,
        'đe dọa' => 0.6, 'xóa đề tài' => 0.35, 'hủy đăng ký' => 0.3,
    ];

    /** Transaction/contact combo signals */
    private const CONTACT_TOKENS = ['zalo', 'telegram', 'inbox', 'ib riêng', 'nick mess', 'số 0987', '09xx', '@abc', 'facebook', 'ib '];
    private const MONEY_PATTERN = '/\b(\d+\s?(k|trieu|triệu|d|đ|vnd|dong|đồng)|50k|100k|200k|500k|1 trieu|2 trieu|5 trieu|10 trieu)\b/u';

    /**
     * Entry point: chọn cách chấm điểm theo cấu hình (TEXT_MODERATION_MODE).
     *  - rules  : rule-based ở dưới (mặc định, không gọi mạng)
     *  - model  : gọi server PhoBERT (violation-detection/python/app.py)
     *  - hybrid : rule OR model (điểm cao nhất) => giữ recall của rules
     * Mọi lỗi/timeout của server model đều fallback về rules (fail-open),
     * nên chat KHÔNG bao giờ bị chặn/treo vì moderation.
     *
     * @return array{is_violation:bool, score:float, reasons:array<int,string>, needs_review:bool, skipped:bool}
     */
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

        // Server model chưa cấu hình / tắt / lỗi => dùng rules.
        if ($modelResult === null) {
            return $ruleResult;
        }

        if ($mode === 'model') {
            return $modelResult;
        }

        // hybrid
        return self::mergeResults($ruleResult, $modelResult);
    }

    /** Rule-based (dataset + STRONG_RULES + combo tiền/liên hệ riêng) — logic gốc, không đổi. */
    public static function checkRules(?string $text): array
    {
        $text = trim((string) $text);
        if ($text === '') {
            return ['is_violation' => false, 'score' => 0.0, 'reasons' => [], 'needs_review' => false, 'skipped' => false];
        }

        $norm = self::normalize($text);
        $score = 0.0;
        $reasons = [];

        foreach (self::STRONG_RULES as $phrase => $weight) {
            if (mb_strpos($norm, self::normalize($phrase)) !== false) {
                $score += $weight;
                $reasons[] = $phrase;
            }
        }

        $hasMoney = (bool) preg_match(self::MONEY_PATTERN, $norm);
        $contactHits = [];
        foreach (self::CONTACT_TOKENS as $token) {
            if (mb_strpos($norm, $token) !== false) {
                $contactHits[] = $token;
            }
        }

        // Money + private contact is the classic fraud combo in the dataset.
        if ($hasMoney && count($contactHits) > 0) {
            $score += 0.35;
            $reasons[] = 'tiền + liên hệ riêng (' . implode(',', array_slice($contactHits, 0, 3)) . ')';
        } elseif ($hasMoney) {
            $score += 0.12;
        } elseif (count($contactHits) >= 2) {
            $score += 0.15;
            $reasons[] = 'nhiều kênh liên hệ riêng';
        }

        // Short-link sharing + money/account
        if (preg_match('/(bit\.ly|tinyurl|drive\.google|docs\.google)/u', $norm) && ($hasMoney || mb_strpos($norm, 'acc') !== false)) {
            $score += 0.2;
            $reasons[] = 'link rút gọn + tiền/tài khoản';
        }

        $score = min(1.0, round($score, 3));
        $thresholds = self::thresholds();
        $isViolation = $score >= ($thresholds['text_threshold'] ?? 0.55);

        return [
            'is_violation' => $isViolation,
            'score' => $score,
            'reasons' => array_values(array_unique($reasons)),
            'needs_review' => $score >= ($thresholds['text_review_threshold'] ?? 0.35) && !$isViolation,
            'skipped' => false,
        ];
    }

    /** Chế độ chấm điểm: rules | model | hybrid (mặc định rules). */
    public static function mode(): string
    {
        $mode = strtolower(trim((string) (config('services.text_moderation.mode') ?? env('TEXT_MODERATION_MODE', 'rules'))));

        return in_array($mode, ['rules', 'model', 'hybrid'], true) ? $mode : 'rules';
    }

    /** URL server PhoBERT (null = chưa cấu hình => chỉ dùng rules). */
    public static function modelUrl(): ?string
    {
        $url = config('services.text_moderation.url') ?: env('TEXT_MODERATION_URL');

        return $url ? rtrim((string) $url, '/') : null;
    }

    /** Ngưỡng gắn cờ của model — đọc chung thresholds.json để tune không cần build lại. */
    public static function modelThreshold(): float
    {
        return (float) (self::thresholds()['text_model_threshold'] ?? 0.5);
    }

    /**
     * Gọi server PhoBERT: POST {url}/predict {text} -> {is_violation, score, reasons, needs_review}.
     * Trả null khi chưa cấu hình / lỗi / timeout / response sai => caller fallback về rules.
     */
    private static function checkModel(string $text): ?array
    {
        $url = self::modelUrl();
        if ($url === null) {
            return null;
        }

        try {
            $response = Http::timeout(3)->acceptJson()->post($url . '/predict', ['text' => $text]);

            if (!$response->successful()) {
                Log::warning('Text moderation HTTP error: ' . $response->status());

                return null;
            }

            $data = $response->json();
            if (!is_array($data) || !array_key_exists('is_violation', $data)) {
                Log::warning('Text moderation response không hợp lệ.');

                return null;
            }

            $score = round((float) ($data['score'] ?? 0), 3);
            $isViolation = (bool) $data['is_violation'];
            $thresholds = self::thresholds();

            return [
                'is_violation' => $isViolation,
                'score' => $score,
                'reasons' => array_values(array_filter((array) ($data['reasons'] ?? []), 'is_string')),
                'needs_review' => array_key_exists('needs_review', $data)
                    ? (bool) $data['needs_review']
                    : ($score >= (float) ($thresholds['text_review_threshold'] ?? 0.35) && !$isViolation),
                'skipped' => false,
            ];
        } catch (\Throwable $e) {
            Log::warning('Text moderation skipped (model server offline?): ' . $e->getMessage());

            return null;
        }
    }

    /** hybrid: rule OR model — lấy điểm cao nhất và gộp lý do để admin thấy đủ ngữ cảnh. */
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

    /** Ngưỡng đọc từ violation-detection/config/thresholds.json (cache per-request). */
    public static function thresholds(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $path = self::configPath();
        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded)) {
                return $cache = $decoded;
            }
        }
        return $cache = ['text_threshold' => 0.55, 'text_review_threshold' => 0.35];
    }

    /** Đường dẫn config của module (tách khỏi app/ để admin/audit dễ thấy). */
    public static function configPath(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'thresholds.json';
    }

    /** Đường dẫn dataset fraud dùng để derive rule / train PhoBERT. */
    public static function datasetPath(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'datasets' . DIRECTORY_SEPARATOR . 'chat_fraud_dataset.csv';
    }

    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        // Fold combining diacritics so "lam ho" still matches "làm hộ".
        if (class_exists('Normalizer')) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_D) ?: $text;
            $text = (string) preg_replace('/\p{Mn}/u', '', $text);
        }
        $text = str_replace(['đ', 'Ð'], ['d', 'd'], $text);
        $text = (string) preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }
}
