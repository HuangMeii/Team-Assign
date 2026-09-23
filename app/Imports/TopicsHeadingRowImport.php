<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\HeadingRowImport;

/**
 * Đọc ĐÚNG dòng tiêu đề của file import đề tài để CHẨN ĐOÁN SỚM.
 *
 * Dùng trong TopicController::import(): nếu file thiếu dòng tiêu đề / sai tên cột / sai dấu phân cách thì
 * báo MỘT thông báo dễ hiểu, thay vì để từng dòng dữ liệu lỗi "không được để trống" (file 9 dòng sai tiêu
 * đề trước đây sinh ra 27 dòng lỗi).
 *
 * Kế thừa Maatwebsite\Excel\HeadingRowImport (đọc 1 dòng đầu + slug hoá tiêu đề) và dùng CHUNG cấu hình CSV
 * với TopicsImport để việc chẩn đoán khớp với việc import thật.
 */
class TopicsHeadingRowImport extends HeadingRowImport implements WithCustomCsvSettings
{
    /**
     * @param  int  $headingRow  Số thứ tự dòng tiêu đề (mặc định 1).
     * @param  string|null  $csvDelimiter  Dấu phân cách CSV đã dò được (null ⇒ để PhpSpreadsheet tự dò).
     */
    public function __construct(int $headingRow = 1, protected readonly ?string $csvDelimiter = null)
    {
        parent::__construct($headingRow);
    }

    public function getCsvSettings(): array
    {
        return TopicsImport::csvSettings($this->csvDelimiter);
    }
}