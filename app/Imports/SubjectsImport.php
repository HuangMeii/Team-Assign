<?php

namespace App\Imports;

use App\Imports\Concerns\DetectsCsvDelimiter;
use App\Models\Subject;
use App\Services\SubjectCodeService;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithValidation;

class SubjectsImport implements ToModel, WithHeadingRow, WithChunkReading, WithValidation, SkipsOnFailure, SkipsEmptyRows, WithCustomCsvSettings
{
    use SkipsFailures;
    use DetectsCsvDelimiter;

    protected array $stats = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
    ];

    /**
     * @param  string|null  $csvDelimiter  Dấu phân cách CSV đã dò được (xem DetectsCsvDelimiter);
     *                                     null ⇒ để PhpSpreadsheet tự dò (cuối cùng mặc định là dấu phẩy).
     */
    public function __construct(protected readonly ?string $csvDelimiter = null) {}

    /**
     * Mỗi hàng được chuyển thành một Subject mới.
     * Cột Excel (heading row): ten_mon, so_tc, so_bai_bao_cao
     * Mã môn LUÔN do hệ thống tự sinh (giống thêm tay), không nhận từ file.
     * Tên môn đã tồn tại (trim, không phân biệt hoa/thường) -> cập nhật số tín chỉ.
     */
    public function model(array $row)
    {
        $subjectName = trim((string) ($row['ten_mon'] ?? ''));

        // Bỏ qua hàng rỗng
        if ($subjectName === '') {
            $this->stats['skipped']++;

            return null;
        }

        // so_tc rỗng -> mặc định 3 (tránh (int) '' = 0 vi phạm min:1)
        $rawCredits = $row['so_tc'] ?? null;
        $credits = ($rawCredits === null || $rawCredits === '')
            ? 3
            : (int) $rawCredits;

        // so_bai_bao_cao rỗng/không có cột -> 1 (chỉ cuối kì); chỉ nhận 1 hoặc 2
        $rawReports  = $row['so_bai_bao_cao'] ?? null;
        $reportCount = ($rawReports === null || $rawReports === '') ? 1 : (int) $rawReports;

        $subject = Subject::whereRaw('LOWER(TRIM(subject_name)) = ?', [mb_strtolower($subjectName, 'UTF-8')])->first();

        // Tên môn đã có thì cập nhật số tín chỉ
        if ($subject) {
            $update = ['credits' => $credits];

            // Chỉ cập nhật số bài báo cáo khi file CÓ giá trị (tránh vô tình hạ môn 2 bài xuống 1)
            if ($rawReports !== null && $rawReports !== '') {
                $update['report_count'] = $reportCount;
            }

            $subject->update($update);
            $this->stats['updated']++;

            return null;
        }

        Subject::create([
            'subject_code' => SubjectCodeService::generate($subjectName),
            'subject_name' => $subjectName,
            'credits'      => $credits,
            'report_count' => $reportCount,
        ]);
        $this->stats['created']++;

        return null;
    }

    public function rules(): array
    {
        return [
            'ten_mon' => ['required', 'string', 'max:255'],
            'so_tc' => ['nullable', 'integer', 'min:1', 'max:10'],
            'so_bai_bao_cao' => ['nullable', 'integer', 'in:1,2'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'ten_mon.required' => 'Tên môn học không được để trống.',
            'so_tc.integer' => 'Số tín chỉ phải là số nguyên.',
            'so_tc.min' => 'Số tín chỉ tối thiểu là 1.',
            'so_tc.max' => 'Số tín chỉ tối đa là 10.',
            'so_bai_bao_cao.integer' => 'Số bài báo cáo phải là số nguyên.',
            'so_bai_bao_cao.in'      => 'Số bài báo cáo chỉ nhận 1 (chỉ cuối kì) hoặc 2 (giữa kì + cuối kì).',
        ];
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Xử lý theo khối để tránh hết bộ nhớ với file lớn.
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Tên cột tiêu đề dòng tiêu đề (heading row) có thứ tự bao nhiêu.
     * Mặc định hàng đầu tiên là tiêu đề.
     */
    public function headingRow(): int
    {
        return 1;
    }
}
