<?php

namespace App\Imports\Concerns;

/**
 * Dò dấu phân cách của file CSV (dấu phẩy / chấm phẩy / TAB / sổ đứng) — dùng CHUNG cho các lớp import.
 *
 * Vì sao cần: Maatwebsite KHOÁ CỨNG dấu phân cách là dấu phẩy (MapsCsvSettings::$delimiter +
 * ReaderFactory::make() luôn gọi setDelimiter()) và dự án KHÔNG có config/excel.php nên
 * config('excel.imports.csv') rỗng. Vì vậy file CSV phân cách bằng TAB (rất hay gặp khi sao chép từ
 * Excel rồi dán vào Notepad) hoặc bằng dấu chấm phẩy bị đọc thành MỘT cột ⇒ mọi cột bắt buộc rỗng
 * ⇒ báo lỗi "không được để trống" cho TOÀN BỘ các dòng (file 9 dòng ⇒ 27 dòng lỗi).
 *
 * Lớp dùng trait PHẢI:
 *  - implements Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
 *  - khai báo thuộc tính `protected readonly ?string $csvDelimiter` (thường qua constructor).
 */
trait DetectsCsvDelimiter
{
    /**
     * Cấu hình đọc CSV dùng chung.
     *
     * @param  string|null  $delimiter  Dấu phân cách đã dò được (xem detectCsvDelimiter());
     *                                  null ⇒ để PhpSpreadsheet tự dò (Reader\Csv::inferSeparator()).
     */
    public static function csvSettings(?string $delimiter = null): array
    {
        return [
            'delimiter'      => $delimiter,
            'input_encoding' => 'UTF-8',
        ];
    }

    /**
     * Dò dấu phân cách của file CSV: dấu phẩy, dấu chấm phẩy, TAB hoặc sổ đứng.
     *
     * Đọc tối đa 20 dòng KHÔNG TRỐNG đầu tiên, bỏ qua dòng trống; chỉ nhận dấu phân cách có mặt ở >= 80%
     * số dòng (tránh nhầm với dấu phẩy nằm trong phần mô tả) rồi chọn dấu có nhiều dòng cùng số lần xuất
     * hiện nhất, hoà thì chọn dấu xuất hiện nhiều hơn. Trả về null nếu không đủ tự tin ⇒ để PhpSpreadsheet
     * tự dò, cuối cùng mặc định là dấu phẩy.
     */
    public static function detectCsvDelimiter(string $path): ?string
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return null;
        }

        $series = [',' => [], ';' => [], "\t" => [], '|' => []];
        $lines  = 0;

        while ($lines < 20 && ($line = fgets($handle)) !== false) {
            $line = trim($line, "\r\n");

            if ($line === '') {
                continue;
            }

            $lines++;

            foreach (array_keys($series) as $delimiter) {
                $series[$delimiter][] = substr_count($line, $delimiter);
            }
        }

        fclose($handle);

        if ($lines === 0) {
            return null;
        }

        $minLines  = (int) ceil($lines * 0.8);
        $best      = null;
        $bestVotes = 0;
        $bestTotal = 0;

        foreach ($series as $delimiter => $counts) {
            $total = array_sum($counts);

            if ($total === 0 || count(array_filter($counts)) < $minLines) {
                continue;
            }

            $frequencies = array_count_values($counts);
            arsort($frequencies);

            $votes = (int) reset($frequencies);

            if ($votes > $bestVotes || ($votes === $bestVotes && $total > $bestTotal)) {
                $best      = $delimiter;
                $bestVotes = $votes;
                $bestTotal = $total;
            }
        }

        return $best;
    }

    /**
     * @see csvSettings()
     */
    public function getCsvSettings(): array
    {
        return static::csvSettings($this->csvDelimiter);
    }
}