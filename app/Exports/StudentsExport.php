<?php

namespace App\Exports;

use App\Models\User;
use App\Models\ClassSection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected $classId;
    protected $className;

    public function __construct($classId = null)
    {
        $this->classId = $classId;
        
        // Lấy tên lớp để hiển thị
        if ($classId) {
            $class = ClassSection::find($classId);
            $this->className = $class ? $class->class_name : 'Unknown';
        }
    }

    /**
     * Lấy dữ liệu sinh viên
     */
    public function collection()
    {
        $query = User::where('role', 'student')
            ->with(['classes.subject', 'groupsJoined', 'groupsLed']);
        
        // Lọc theo lớp nếu có
        if ($this->classId) {
            $query->whereHas('classes', function($q) {
                $q->where('class_sections.class_id', $this->classId);
            });
        }
        
        return $query->orderBy('name')->get();
    }

    /**
     * Tiêu đề cột
     */
    public function headings(): array
    {
        return [
            'STT',
            'Họ và tên',
            'Email',
            'Lớp học',
            'Môn học',
            'Nhóm',
            'Vai trò trong nhóm',
            'Trạng thái',
            'Ngày tạo',
        ];
    }

    /**
     * Map dữ liệu cho mỗi row
     */
    public function map($student): array
    {
        static $index = 0;
        $index++;
        
        // Lấy thông tin lớp
        $classes = $this->classId 
            ? $student->classes->where('class_id', $this->classId)
            : $student->classes;
        
        $classNames = $classes->pluck('class_name')->implode(', ');
        $subjectNames = $classes->pluck('subject.subject_name')->filter()->implode(', ');
        
        // Lấy thông tin nhóm
        $groupInfo = $this->getGroupInfo($student);
        
        return [
            $index,
            $student->name,
            $student->email,
            $classNames ?: 'Chưa có lớp',
            $subjectNames ?: 'N/A',
            $groupInfo['name'],
            $groupInfo['role'],
            $groupInfo['status'],
            $student->created_at->format('d/m/Y H:i'),
        ];
    }

    /**
     * Lấy thông tin nhóm của sinh viên
     */
    private function getGroupInfo($student)
    {
        // Kiểm tra nhóm do sinh viên làm trưởng nhóm
        $ledGroups = $student->groupsLed;
        
        if ($this->classId) {
            $ledGroups = $ledGroups->where('class_id', $this->classId);
        }
        
        if ($ledGroups->isNotEmpty()) {
            $group = $ledGroups->first();
            return [
                'name' => $group->group_name,
                'role' => 'Trưởng nhóm',
                'status' => 'Đã có nhóm'
            ];
        }
        
        // Kiểm tra nhóm mà sinh viên là thành viên
        $joinedGroups = $student->groupsJoined;
        
        if ($this->classId) {
            $joinedGroups = $joinedGroups->where('class_id', $this->classId);
        }
        
        if ($joinedGroups->isNotEmpty()) {
            $group = $joinedGroups->first();
            return [
                'name' => $group->group_name,
                'role' => 'Thành viên',
                'status' => 'Đã có nhóm'
            ];
        }
        
        // Chưa có nhóm
        return [
            'name' => 'Chưa có nhóm',
            'role' => 'N/A',
            'status' => 'Chưa có nhóm'
        ];
    }

    /**
     * Styling cho Excel
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style cho header (row 1)
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => '000000']
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'color' => ['rgb' => '4472C4']
                ],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center'
                ]
            ],
        ];
    }

    /**
     * Tên sheet
     */
    public function title(): string
    {
        return $this->classId 
            ? 'Lớp ' . $this->className
            : 'Tất cả sinh viên';
    }
}