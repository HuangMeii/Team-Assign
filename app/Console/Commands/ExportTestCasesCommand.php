<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

/**
 * Sinh BỘ TEST CASE từ nguồn dữ liệu duy nhất `docs/test-cases/data/*.php`:
 *
 *   php artisan testcases:export            # sinh cả Excel + Markdown (mặc định)
 *   php artisan testcases:export --xlsx     # chỉ Excel
 *   php artisan testcases:export --md       # chỉ Markdown
 *
 * Kết quả:
 *   - docs/test-cases/test-cases.xlsx        (sheet Tổng hợp + 1 sheet/nhóm + Hướng dẫn + Dữ liệu mẫu)
 *   - docs/test-cases/<slug>.md              (mỗi nhóm 1 file, cùng tên với file dữ liệu)
 *
 * Sửa nội dung test case CHỈ ở `data/*.php` rồi chạy lại lệnh ⇒ Excel và Markdown luôn khớp nhau.
 */
class ExportTestCasesCommand extends Command
{
    protected $signature = 'testcases:export {--xlsx : Chỉ xuất Excel} {--md : Chỉ xuất Markdown}';

    protected $description = 'Sinh bộ test case (Excel nhiều sheet + Markdown mỗi nhóm) từ docs/test-cases/data/*.php';

    /** Cột của mỗi sheet nhóm (khớp phong cách bug-report.xlsx) */
    private const HEADERS = [
        'Mã TC', 'Role', 'Nhóm chức năng', 'Chức năng', 'Loại', 'Ưu tiên',
        'Mô tả / Mục tiêu', 'Tiền điều kiện', 'Các bước thực hiện', 'Dữ liệu đầu vào',
        'Kết quả mong đợi', 'Kết quả thực tế', 'Trạng thái', 'Test tự động', 'Ghi chú',
    ];

    /** Dữ liệu mẫu dùng chung cho các bước test (khớp tests/Support/Fixtures.php) */
    private const SAMPLE_DATA = [
        ['Tài khoản', 'admin@test.com / password', 'Admin', 'Quản trị toàn hệ thống'],
        ['Tài khoản', 'gv1@test.com / password', 'Giảng viên', 'Phụ trách lớp CNTT01-K1'],
        ['Tài khoản', 'sv1@test.com / password', 'Sinh viên', 'Thuộc lớp CNTT01-K1, chưa có nhóm'],
        ['Tài khoản', 'sv2@test.com / password', 'Sinh viên', 'Thuộc lớp CNTT01-K1, trưởng nhóm Alpha'],
        ['Lớp học phần', 'CNTT01-K1 (mã lớp tự sinh 5 ký tự)', 'Giảng viên', 'Lớp dùng cho hầu hết test case'],
        ['Nhóm', 'Nhóm Alpha (trưởng nhóm sv2, 1-2 thành viên)', 'Sinh viên', 'Test nhóm/đề tài/bảng tin'],
        ['Đề tài', '"Xây dựng website quản lý thư viện" (còn trống)', 'Giảng viên', 'Test đăng ký/duyệt/gợi ý đề tài'],
        ['Yêu cầu đăng ký', 'topic_requests: Pending / Accepted / Rejected', 'Sinh viên', 'Test duyệt–từ chối'],
        ['Dịch vụ AI (tuỳ case)', 'AI-Services/services/topic-recommender-8891 :8891', 'Hệ thống', 'Test gợi ý đề tài theo ngữ nghĩa'],
        ['Dịch vụ AI (tuỳ case)', 'PhoBERT fraud :8889 · moderation :8890 · Vision :8888', 'Hệ thống', 'Test kiểm duyệt nội dung (flag-only)'],
        ['Realtime (tuỳ case)', 'Laravel Reverb :8080 (php artisan reverb:start)', 'Hệ thống', 'Test tick trạng thái / bảng tin / badge'],
    ];

    public function handle(): int
    {
        $groups = $this->loadGroups();

        if ($groups === []) {
            $this->error('Không tìm thấy dữ liệu: docs/test-cases/data/*.php');

            return self::FAILURE;
        }

        $both = ! $this->option('xlsx') && ! $this->option('md');

        if ($both || $this->option('xlsx')) {
            $this->exportXlsx($groups);
        }

        if ($both || $this->option('md')) {
            $this->exportMarkdown($groups);
        }

        $total = array_sum(array_map(fn ($g) => count($g['cases']), $groups));
        $this->newLine();
        $this->info("Tổng: {$total} test case / " . count($groups) . ' nhóm.');

        return self::SUCCESS;
    }

    /**
     * Đọc toàn bộ file dữ liệu trong docs/test-cases/data/ và tự sinh mã TC nếu thiếu.
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadGroups(): array
    {
        $dir = base_path('docs/test-cases/data');
        $files = glob($dir . '/*.php') ?: [];
        sort($files);

        $groups = [];

        foreach ($files as $file) {
            /** @var array<string, mixed> $data */
            $data = require $file;
            $data['path'] = $file;
            $data['slug'] = pathinfo($file, PATHINFO_FILENAME);

            foreach ($data['cases'] as $index => $case) {
                $data['cases'][$index]['code'] = $case['code'] ?? sprintf('%s-%02d', $data['code_prefix'], $index + 1);
                $data['cases'][$index]['status'] = $case['status'] ?? 'Chưa chạy tay';
                $data['cases'][$index]['actual'] = $case['actual'] ?? $this->defaultActual($case['status'] ?? '');
                $data['cases'][$index]['note'] = $case['note'] ?? '';
                $data['cases'][$index]['auto'] = $case['auto'] ?? '';
            }

            $groups[] = $data;
        }

        return $groups;
    }

    private function defaultActual(string $status): string
    {
        return match ($status) {
            'Pass' => 'Đúng như mong đợi (kiểm chứng bằng test tự động)',
            'Fail' => 'Lệch với mong đợi (xem cột Ghi chú)',
            default => 'Chưa chạy tay',
        };
    }

    /** Thống kê Pass/Fail/Chưa chạy của 1 nhóm. */
    private function statusCounts(array $cases): array
    {
        $counts = ['Pass' => 0, 'Fail' => 0, 'Chưa chạy tay' => 0];

        foreach ($cases as $case) {
            $status = $case['status'] ?? 'Chưa chạy tay';
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }

        return $counts;
    }

    // =====================================================================
    // EXCEL
    // =====================================================================

    private function exportXlsx(array $groups): void
    {
        $path = base_path('docs/test-cases/test-cases.xlsx');

        $book = new Spreadsheet();
        $book->removeSheetByIndex(0);

        $this->buildSummarySheet($book->createSheet()->setTitle('Tổng hợp'), $groups);
        $this->buildGuideSheet($book->createSheet()->setTitle('Hướng dẫn'), $groups);
        $this->buildSampleSheet($book->createSheet()->setTitle('Dữ liệu mẫu'));

        foreach ($groups as $group) {
            $sheet = $book->createSheet();
            $sheet->setTitle($group['sheet']);
            $this->buildGroupSheet($sheet, $group);
        }

        $book->setActiveSheetIndex(0);

        (new XlsxWriter($book))->save($path);

        $this->line('  [xlsx] docs/test-cases/test-cases.xlsx — ' . $book->getSheetCount() . ' sheet');
    }

    private function buildGroupSheet(Worksheet $sheet, array $group): void
    {
        $sheet->fromArray(self::HEADERS, null, 'A1');

        $row = 2;

        foreach ($group['cases'] as $case) {
            $sheet->fromArray([
                $case['code'],
                $case['role'],
                $group['title'],
                $case['feature'] ?? '',
                $case['type'],
                $case['prio'],
                $case['goal'],
                $case['pre'] ?? '',
                implode("\n", $case['steps'] ?? []),
                $case['input'] ?? '',
                $case['expect'] ?? '',
                $case['actual'],
                $case['status'],
                $case['auto'] ?: '(thủ công)',
                $case['note'],
            ], null, 'A' . $row);

            $this->colorStatus($sheet, 'M' . $row, $case['status']);
            $row++;
        }

        $last = max($row - 1, 1);

        $sheet->getStyle('A1:O1')->getFont()->setBold(true);
        $sheet->getStyle('A1:O1')->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:O1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2563EB');
        $sheet->getStyle('A1:O' . $last)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        $sheet->getStyle('A2:A' . $last)->getFont()->setBold(true);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:O' . $last);

        foreach ([
            'A' => 13, 'B' => 12, 'C' => 20, 'D' => 26, 'E' => 11, 'F' => 10, 'G' => 42, 'H' => 34,
            'I' => 48, 'J' => 26, 'K' => 48, 'L' => 30, 'M' => 14, 'N' => 42, 'O' => 24,
        ] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    /** Tô màu ô "Trạng thái": Pass = xanh lá · Fail = đỏ · Chưa chạy tay = vàng. */
    private function colorStatus(Worksheet $sheet, string $cell, string $status): void
    {
        $fill = [
            'Pass' => 'FFD1FAE5',
            'Fail' => 'FFFEE2E2',
            'Chưa chạy tay' => 'FFFEF3C7',
        ][$status] ?? 'FFF3F4F6';

        $font = [
            'Pass' => 'FF065F46',
            'Fail' => 'FF991B1B',
            'Chưa chạy tay' => 'FF92400E',
        ][$status] ?? 'FF374151';

        $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($fill);
        $sheet->getStyle($cell)->getFont()->setBold(true);
        $sheet->getStyle($cell)->getFont()->getColor()->setARGB($font);
    }

    private function buildSummarySheet(Worksheet $sheet, array $groups): void
    {
        $sheet->fromArray(
            ['Nhóm', 'Mã nhóm', 'Chức năng (FEATURE_STATUS)', 'Số TC', 'Pass', 'Fail', 'Chưa chạy tay', 'Sheet chi tiết'],
            null,
            'A1'
        );
        $row = 2;
        $totals = ['total' => 0, 'Pass' => 0, 'Fail' => 0, 'Chưa chạy tay' => 0];

        foreach ($groups as $group) {
            $counts = $this->statusCounts($group['cases']);
            $total = count($group['cases']);

            $sheet->fromArray([
                $group['title'], $group['code_prefix'], $group['features'], $total,
                $counts['Pass'], $counts['Fail'], $counts['Chưa chạy tay'], $group['sheet'],
            ], null, 'A' . $row, true);   // strictNullComparison: giữ giá trị 0 (nếu không, ô Fail=0 sẽ bị bỏ trống)

            $totals['total'] += $total;
            $totals['Pass'] += $counts['Pass'];
            $totals['Fail'] += $counts['Fail'];
            $totals['Chưa chạy tay'] += $counts['Chưa chạy tay'];
            $row++;
        }

        $sheet->fromArray([
            'TỔNG CỘNG', '', '', $totals['total'], $totals['Pass'], $totals['Fail'], $totals['Chưa chạy tay'], '',
        ], null, 'A' . $row, true);

        $last = $row;

        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:H1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2563EB');
        $sheet->getStyle('A' . $last . ':H' . $last)->getFont()->setBold(true);
        $sheet->getStyle('D2:G' . $last)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('A2');

        foreach (['A' => 46, 'B' => 13, 'C' => 44, 'D' => 8, 'E' => 8, 'F' => 8, 'G' => 16, 'H' => 14] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    private function buildGuideSheet(Worksheet $sheet, array $groups): void
    {
        $rows = [
            ['MỤC', 'NỘI DUNG'],
            ['Mã test case', 'TC-<NHÓM>-<số> — vd TC-CHAT-07 (nhóm Chat, case 7); mã tự sinh theo thứ tự trong data/*.php'],
            ['Cột "Trạng thái"', 'Pass = đã kiểm chứng (thường bằng test tự động) · Fail = lệch mong đợi · Chưa chạy tay = case UI/realtime cần chạy bằng trình duyệt'],
            ['Cột "Test tự động"', 'File Pest phủ case đó (`php artisan test <file>`), hoặc "(thủ công)"'],
            ['Nguồn dữ liệu', 'docs/test-cases/data/*.php — SỬA Ở ĐÂY rồi chạy lại lệnh export để sinh lại Excel + Markdown'],
            ['Lệnh sinh lại', 'php artisan testcases:export        (hoặc --xlsx / --md)'],
            ['Môi trường test', 'MySQL local đang chạy + DB `team_assign_test` (phpunit.xml hardcode 127.0.0.1:3306)'],
            ['AI service (tuỳ case)', 'AI-Services\\start-servers.ps1 → Vision 8888 · PhoBERT fraud 8889 · moderation 8890 · recommender 8891'],
            ['Realtime (tuỳ case)', 'php artisan reverb:start (badge, tick trạng thái, bảng tin). Reverb tắt ⇒ vẫn đúng khi tải lại trang (fail-open)'],
            ['Lưu ý mail', 'Mail đã được ép về `array` trong phpunit.xml ⇒ KHÔNG cần SMTP và không còn là nguyên nhân test đỏ'],
            ['4 case đang Fail', 'TC-AUTH-07/08/09 (ForgotPasswordTest) + TC-AUTH-11 (ChangePasswordTest): 3 case lệch đặc tả test ↔ code (key session, điều hướng sau khi đặt lại mật khẩu); 1 case là BUG THẬT — `route(\'verification.notice\')` không tồn tại vì `bootstrap/app.php` không nạp `routes/auth.php` ⇒ tài khoản chưa xác thực email đăng nhập bị HTTP 500'],
            ['', ''],
            ['CÁCH CHẠY TỪNG NHÓM', ''],
        ];

        foreach ($groups as $group) {
            $rows[] = [$group['code_prefix'] . ' — ' . $group['title'], $group['run']];
        }

        $sheet->fromArray($rows, null, 'A1');

        $sheet->getStyle('A1:B1')->getFont()->setBold(true);
        $sheet->getStyle('A1:B1')->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:B1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2563EB');
        $sheet->getStyle('A1:B' . count($rows))->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        $sheet->getStyle('A' . (count($rows) - count($groups)))->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(32);
        $sheet->getColumnDimension('B')->setWidth(120);
    }

    private function buildSampleSheet(Worksheet $sheet): void
    {
        $sheet->fromArray([['Loại dữ liệu', 'Giá trị mẫu', 'Vai trò', 'Dùng cho']], null, 'A1');
        $sheet->fromArray(self::SAMPLE_DATA, null, 'A2');

        $last = count(self::SAMPLE_DATA) + 1;

        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getStyle('A1:D1')->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:D1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2563EB');
        $sheet->getStyle('A1:D' . $last)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);

        foreach (['A' => 24, 'B' => 52, 'C' => 14, 'D' => 46] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    // =====================================================================
    // MARKDOWN
    // =====================================================================

    private function exportMarkdown(array $groups): void
    {
        foreach ($groups as $group) {
            $path = base_path('docs/test-cases/' . $group['slug'] . '.md');

            file_put_contents($path, $this->markdownFor($group));

            $lines = count(file($path));

            $this->line('  [md]   docs/test-cases/' . $group['slug'] . '.md — ' . $lines . ' dòng');
        }
    }

    private function markdownFor(array $group): string
    {
        $counts = $this->statusCounts($group['cases']);
        $total = count($group['cases']);

        $out = [];
        $out[] = '# ' . $group['title'];
        $out[] = '';
        $out[] = '> **Mã nhóm**: `' . $group['code_prefix'] . '` · **Chức năng**: ' . $group['features'];
        $out[] = '> **Số test case**: ' . $total . ' — Pass: **' . $counts['Pass'] . '** · Fail: **' . $counts['Fail']
            . '** · Chưa chạy tay: **' . $counts['Chưa chạy tay'] . '**';
        $out[] = '> **Môi trường**: ' . $group['env'];
        $out[] = '> ↻ File này **sinh tự động** từ `docs/test-cases/data/' . $group['slug']
            . '.php` — sửa dữ liệu ở đó rồi chạy `php artisan testcases:export` (đừng sửa file .md này).';
        $out[] = '';
        $out[] = '## 1. Mục tiêu & phạm vi';
        $out[] = '';
        $out[] = $group['summary'];
        $out[] = '';
        $out[] = '## 2. Bảng tóm tắt test case';
        $out[] = '';
        $out[] = '| Mã TC | Tên / Mục tiêu | Role | Loại | Ưu tiên | Trạng thái |';
        $out[] = '|---|---|---|---|---|---|';

        foreach ($group['cases'] as $case) {
            $out[] = '| `' . $case['code'] . '` | ' . $this->cell($case['goal']) . ' | ' . $case['role']
                . ' | ' . $case['type'] . ' | ' . $case['prio'] . ' | ' . $case['status'] . ' |';
        }

        $out[] = '';
        $out[] = '## 3. Chi tiết test case';

        foreach ($group['cases'] as $case) {
            $out[] = '';
            $out[] = '### ' . $case['code'] . ' — ' . $case['goal'];
            $out[] = '';
            $out[] = '- **Chức năng**: ' . ($case['feature'] ?? '—') . ' · **Role**: ' . $case['role']
                . ' · **Loại**: ' . $case['type'] . ' · **Ưu tiên**: ' . $case['prio'];
            $out[] = '- **Tiền điều kiện**: ' . ($case['pre'] ?? '—');
            $out[] = '- **Các bước thực hiện**:';

            foreach ($case['steps'] ?? [] as $index => $step) {
                $out[] = '  ' . ($index + 1) . '. ' . $step;
            }

            $out[] = '- **Dữ liệu đầu vào**: ' . ($case['input'] ?? '—');
            $out[] = '- **Kết quả mong đợi**: ' . ($case['expect'] ?? '—');

            if (! empty($case['db'])) {
                $out[] = '- **Kiểm tra thêm (DB / log / API)**: ' . $case['db'];
            }

            $out[] = '- **Kết quả thực tế**: ' . $case['actual'];
            $out[] = '- **Trạng thái**: **' . $case['status'] . '**';
            $out[] = '- **Test tự động**: ' . ($case['auto'] !== '' ? '`' . $case['auto'] . '`' : '(thủ công — chạy trên trình duyệt)');

            if ($case['note'] !== '') {
                $out[] = '- **Ghi chú**: ' . $case['note'];
            }
        }

        $out[] = '';
        $out[] = '## 4. Cách chạy nhóm test này';
        $out[] = '';
        $out[] = '```powershell';
        $out[] = 'cd ' . base_path();
        $out[] = $group['run'];
        $out[] = '```';
        $out[] = '';
        $out[] = '## 5. Ghi chú & rủi ro';
        $out[] = '';

        foreach (($group['notes'] ?? []) as $note) {
            $out[] = '- ' . $note;
        }

        $out[] = '- Case có nhãn `Chưa chạy tay` cần tự chạy trên trình duyệt (2 tài khoản nếu cần realtime) rồi đổi trạng thái trong `data/'
            . $group['slug'] . '.php` và export lại.';
        $out[] = '';

        $out[] = '<sub>Sinh tự động bởi `php artisan testcases:export` · nguồn: `docs/test-cases/data/'
            . $group['slug'] . '.php`</sub>';
        $out[] = '';

        return implode("\n", $out);
    }

    /** Escape ký tự phá bảng Markdown. */
    private function cell(string $text): string
    {
        return str_replace(['|', "\n"], ['\\|', ' '], $text);
    }
}

