<?php

namespace App\Imports;

use App\Imports\Concerns\DetectsCsvDelimiter;
use App\Models\ClassSection;
use App\Models\Subject;
use App\Models\Topics;
use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Import ĐỀ TÀI từ Excel/CSV (bám theo đúng pattern của SubjectsImport).
 *
 * Cột tiêu đề (heading row): ten_de_tai, mo_ta, muc_tieu, yeu_cau, ma_lop, so_tv_min, so_tv_max, han_dang_ky, loai_bao_cao (rỗng ⇒ cuối kì)
 *
 * Quy tắc:
 *  - `ma_lop` là MÃ LỚP (class_sections.class_code) — hệ thống tự suy ra class_id, subject_id
 *    và giảng viên phụ trách lớp (giống hệt khi thêm đề tài bằng tay ở TopicController::store()).
 *  - Giảng viên CHỈ import được cho lớp mình phụ trách; admin import được mọi lớp.
 *  - Tên đề tài đã tồn tại ⇒ BỎ QUA (không ghi đè đề tài đang chạy) và đếm vào `skipped`.
 *  - `so_tv_min/so_tv_max` rỗng ⇒ 2/4; nếu max < min thì tự đảo lại cho hợp lệ.
 *  - `han_dang_ky` rỗng ⇒ now() + 30 ngày (giống dữ liệu mẫu trong tests/Support/Fixtures.php).
 */
class TopicsImport implements ToModel, WithHeadingRow, WithChunkReading, WithValidation, SkipsOnFailure, SkipsEmptyRows, WithCustomCsvSettings
{
    use SkipsFailures;
    use DetectsCsvDelimiter;

    /**
     * Cột tiêu đề chuẩn của file import (đã được slug hoá).
     */
    public const HEADINGS = [
        'ten_de_tai',
        'mo_ta',
        'muc_tieu',
        'yeu_cau',
        'ma_lop',
        'so_tv_min',
        'so_tv_max',
        'han_dang_ky',
        'loai_bao_cao',
    ];

    /**
     * Các cột BẮT BUỘC phải đọc được — thiếu là file sai định dạng (xem TopicController::import()).
     */
    public const REQUIRED_HEADINGS = ['ten_de_tai', 'mo_ta', 'ma_lop'];
    protected array $stats = [
        'created' => 0,
        'skipped' => 0,
    ];

    /**
     * @param  User|null  $actor  Người thực hiện import (dùng để kiểm tra quyền theo lớp).
     */
    public function __construct(protected readonly ?User $actor = null, protected readonly ?string $csvDelimiter = null) {}

    /**
     * Mỗi hàng hợp lệ được chuyển thành 1 đề tài mới.
     */
    public function model(array $row)
    {
        $name = trim((string) ($row['ten_de_tai'] ?? ''));

        // Hàng rỗng -> bỏ qua
        if ($name === '') {
            $this->stats['skipped']++;

            return null;
        }

        $class = ClassSection::where('class_code', trim((string) ($row['ma_lop'] ?? '')))->first();

        // Lớp không tồn tại (đã bị chặn ở rules) -> phòng hờ
        if (!$class) {
            $this->stats['skipped']++;

            return null;
        }

        // Tên đề tài đã có -> BỎ QUA, không ghi đè
        if (Topics::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name, 'UTF-8')])->exists()) {
            $this->stats['skipped']++;

            return null;
        }

        $min = (int) ($row['so_tv_min'] ?? 0);
        $max = (int) ($row['so_tv_max'] ?? 0);

        $min = $min > 0 ? $min : 2;
        $max = $max > 0 ? $max : max($min, 4);

        if ($max < $min) {
            [$min, $max] = [$max, $min];
        }

        $goal = trim((string) ($row['muc_tieu'] ?? ''));
        $requirements = trim((string) ($row['yeu_cau'] ?? ''));

        // Loại báo cáo: rỗng / cuoi_ki / final / 2 ⇒ cuối kì; giua_ki / midterm / 1 ⇒ giữa kì.
        // Môn học chỉ có 1 bài báo cáo (subjects.report_count = 1) ⇒ luôn là cuối kì.
        $rawReportType = mb_strtolower(trim((string) ($row['loai_bao_cao'] ?? '')), 'UTF-8');
        $reportType    = in_array($rawReportType, ['giua_ki', 'midterm', '1'], true)
            ? Topics::REPORT_MIDTERM
            : Topics::REPORT_FINAL;

        $subjectReportCount = (int) (Subject::where('subject_id', $class->subject_id)->value('report_count') ?? 1);

        if ($subjectReportCount !== 2) {
            $reportType = Topics::REPORT_FINAL;
        }

        Topics::create([
            'name'                  => $name,
            'description'           => trim((string) ($row['mo_ta'] ?? '')),
            'goal'                  => $goal !== '' ? $goal : null,
            'requirements'          => $requirements !== '' ? $requirements : null,
            // Giảng viên phụ trách lớp (admin import -> ghi tên GV của lớp, không ghi tên admin)
            'lecturer'              => $class->lecturer?->name ?? $this->actor?->name,
            'min_members'           => $min,
            'max_members'           => $max,
            'registration_deadline' => $this->parseDeadline($row['han_dang_ky'] ?? null),
            'is_active'             => true,
            'subject_id'            => $class->subject_id,
            'class_id'              => $class->class_id,
            'report_type'           => $reportType,
        ]);

        $this->stats['created']++;

        return null;
    }

    public function rules(): array
    {
        return [
            'ten_de_tai'  => ['required', 'string', 'max:255'],
            'mo_ta'       => ['required', 'string', 'min:10'],
            'muc_tieu'    => ['nullable', 'string'],
            'yeu_cau'     => ['nullable', 'string'],
            'ma_lop'      => ['required', 'string', 'max:20', $this->classRule()],
            'so_tv_min'   => ['nullable', 'integer', 'min:1', 'max:50'],
            'so_tv_max'   => ['nullable', 'integer', 'min:1', 'max:50'],
            'han_dang_ky' => ['nullable'],
            'loai_bao_cao' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'ten_de_tai.required' => 'Tên đề tài không được để trống.',
            'ten_de_tai.max'      => 'Tên đề tài tối đa 255 ký tự.',
            'mo_ta.required'      => 'Mô tả đề tài không được để trống.',
            'mo_ta.min'           => 'Mô tả đề tài phải có ít nhất 10 ký tự.',
            'ma_lop.required'     => 'Mã lớp học phần không được để trống.',
            'so_tv_min.integer'   => 'Số thành viên tối thiểu phải là số nguyên.',
            'so_tv_min.min'       => 'Số thành viên tối thiểu nhỏ nhất là 1.',
            'so_tv_max.integer'   => 'Số thành viên tối đa phải là số nguyên.',
            'so_tv_max.min'       => 'Số thành viên tối đa nhỏ nhất là 1.',
        ];
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Kiểm tra MÃ LỚP tồn tại + quyền của người import (giảng viên chỉ lớp mình phụ trách).
     * Trả về closure để Validator gọi cho TỪNG DÒNG (báo lỗi kèm số dòng).
     */
    private function classRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $code = trim((string) $value);
            $class = ClassSection::where('class_code', $code)->first();

            if (!$class) {
                $fail('Mã lớp "' . $code . '" không tồn tại trong hệ thống.');

                return;
            }

            if ($this->actor && $this->actor->role === 'lecturer'
                && !$this->actor->classes()->where('class_sections.class_id', $class->class_id)->exists()) {
                $fail('Bạn không phụ trách lớp ' . $code . ' nên không thể import đề tài cho lớp này.');
            }
        };
    }

    /**
     * Chuẩn hoá hạn đăng ký: chấp nhận ngày dạng chuỗi (Y-m-d, d/m/Y) hoặc số serial của Excel.
     * Rỗng/không đọc được ⇒ mặc định 30 ngày kể từ hôm nay.
     */
    private function parseDeadline(mixed $value): Carbon
    {
        if ($value === null || $value === '') {
            return now()->addDays(30);
        }

        // Ô ngày trong Excel thường là số serial
        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
            } catch (\Throwable) {
                return now()->addDays(30);
            }
        }

        try {
            // Người dùng VN thường nhập hạn dạng d/m/Y (vd 31/12/2026). Carbon/PHP hiểu chuỗi có dấu "/"
            // theo kiểu Mỹ (m/d/Y) nên phải xử lý d/m/Y trước, không hợp lệ mới để Carbon tự quyết định.
            if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', trim((string) $value), $parts)
                && checkdate((int) $parts[2], (int) $parts[1], (int) $parts[3])) {
                return Carbon::createFromFormat('d/m/Y', trim((string) $value))->startOfDay();
            }
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return now()->addDays(30);
        }
    }

    /**
     * Xử lý theo khối để tránh hết bộ nhớ với file lớn.
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Hàng đầu tiên là hàng tiêu đề.
     */
    public function headingRow(): int
    {
        return 1;
    }
}
