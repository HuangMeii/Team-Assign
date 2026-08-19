<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            ['Nguyễn Văn A', 'nguyenvana@example.com', '123456'],
            ['Trần Thị B', 'tranthib@example.com', '123456'],
        ];
    }

    public function headings(): array
    {
        return [
            'ho_va_ten',
            'email',
            'mat_khau',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'FFD966']]],
        ];
    }
}