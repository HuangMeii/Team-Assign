<?php

/**
 * CLI trích cụm từ (1-3 gram) xuất hiện nhiều ở nhóm fraud (label=1)
 * trong chat_fraud_dataset.csv để bổ sung/thẩm định luật cho
 * TextModerationService (violation-detection/src/TextModerationService.php).
 *
 * Cách dùng (chạy từ thư mục gốc project):
 *   php violation-detection/tools/extract_keywords.php
 *   php violation-detection/tools/extract_keywords.php violation-detection/datasets/chat_fraud_dataset.csv 40
 */

$csvPath = $argv[1] ?? __DIR__ . '/../datasets/chat_fraud_dataset.csv';
$topN = (int) ($argv[2] ?? 30);

if (!is_file($csvPath)) {
    fwrite(STDERR, "Không tìm thấy dataset: {$csvPath}\n");
    exit(1);
}

$stopwords = [
    'la', 'cua', 'va', 'co', 'khong', 'duoc', 'cho', 'voi', 'thi', 'nay', 'do', 'mot', 'nhung', 'de',
    'minh', 'ban', 'em', 'anh', 'chi', 'ai', 'gi', 'sao', 'the', 'nhu', 'nen', 'rat', 'qua', 'ra',
    'vao', 'tren', 'duoi', 'trong', 'ngoai', 'den', 'tu', 'hay', 'hoac', 'ma', 'nhe', 'a', 'ak', 'ha',
    'oi', 'vay', 'can', 'muon', 'lam', 'giup', 'giup', 'lieu', 'bao', 'nhieu', 'moi', 'cung', 'da',
];

/** Chuẩn hoá giống TextModerationService::normalize() nhưng không cần Laravel. */
function vd_normalize(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    if (class_exists('Normalizer')) {
        $text = Normalizer::normalize($text, Normalizer::FORM_D) ?: $text;
        $text = (string) preg_replace('/\p{Mn}/u', '', $text);
    }
    $text = str_replace(['đ', 'Ð'], ['d', 'd'], $text);

    return trim((string) preg_replace('/\s+/u', ' ', $text));
}

$handle = fopen($csvPath, 'r');
if ($handle === false) {
    fwrite(STDERR, "Không mở được dataset: {$csvPath}\n");
    exit(1);
}

$header = fgetcsv($handle) ?: [];
$textCol = 1;
$labelCol = 2;
foreach ($header as $index => $name) {
    $name = strtolower(trim((string) $name, " \t\n\r\0\xEF\xBB\xBF"));
    if ($name === 'text') {
        $textCol = $index;
    }
    if ($name === 'label') {
        $labelCol = $index;
    }
}

$fraud = [];
$clean = [];
$total = $fraudRows = $cleanRows = $unlabeled = 0;

while (($row = fgetcsv($handle)) !== false) {
    if (!isset($row[$textCol])) {
        continue;
    }

    $text = trim((string) $row[$textCol]);
    $label = trim((string) ($row[$labelCol] ?? ''));
    if ($text === '') {
        continue;
    }

    $total++;

    if ($label === '1') {
        $fraudRows++;
        $bucket = &$fraud;
    } elseif ($label === '0') {
        $cleanRows++;
        $bucket = &$clean;
    } else {
        $unlabeled++;
        unset($bucket);
        continue;
    }

    $words = array_values(array_filter(
        explode(' ', (string) preg_replace('/[^a-z0-9@.]+/u', ' ', vd_normalize($text))),
        static fn ($w) => $w !== ''
    ));

    for ($n = 1; $n <= 3; $n++) {
        $limit = count($words) - $n;
        for ($i = 0; $i <= $limit; $i++) {
            $gram = implode(' ', array_slice($words, $i, $n));

            if ($n === 1 && (mb_strlen($gram) < 4 || in_array($gram, $stopwords, true))) {
                continue;
            }
            if ($n > 1 && in_array($words[$i], $stopwords, true) && in_array($words[$i + $n - 1], $stopwords, true)) {
                continue;
            }

            $bucket[$gram] = ($bucket[$gram] ?? 0) + 1;
        }
    }
    unset($bucket);
}

fclose($handle);

$candidates = [];
foreach ($fraud as $gram => $count) {
    if ($count < 3) {
        continue;
    }
    $ratio = $count / (($clean[$gram] ?? 0) + 1);
    if ($ratio < 3.0) {
        continue;
    }
    $candidates[$gram] = ['count' => $count, 'clean' => $clean[$gram] ?? 0, 'ratio' => $ratio];
}

uasort($candidates, static function ($a, $b) {
    return [$b['count'], $b['ratio']] <=> [$a['count'], $a['ratio']];
});

printf("Dataset: %s\n", $csvPath);
printf("Tổng dòng có nội dung: %d | fraud(label=1): %d | sạch(label=0): %d | không nhãn: %d\n", $total, $fraudRows, $cleanRows, $unlabeled);
printf("Top %d cụm đặc trưng fraud (count>=3, ratio>=3, đã bỏ stopword):\n\n", $topN);
printf("%-45s %8s %8s %8s\n", 'Cụm từ', 'Fraud', 'Sạch', 'Ratio');
foreach (array_slice($candidates, 0, $topN, true) as $gram => $stat) {
    printf("%-45s %8d %8d %8.1f\n", $gram, $stat['count'], $stat['clean'], $stat['ratio']);
}
