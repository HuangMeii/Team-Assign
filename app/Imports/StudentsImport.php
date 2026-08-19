<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class StudentsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    protected $classId;
    protected $stats = [
        'created' => 0,
        'updated' => 0,
        'assigned' => 0,
        'skipped' => 0,
    ];

    public function __construct($classId)
    {
        $this->classId = $classId;
    }

    /**
     * Xử lý từng row trong Excel
     */
    public function model(array $row)
    {
        // Tìm sinh viên theo email
        $student = User::where('email', $row['email'])->first();

        if ($student) {
            // Nếu sinh viên đã tồn tại
            
            // Kiểm tra role
            if ($student->role !== 'student') {
                $this->stats['skipped']++;
                return null; // Bỏ qua nếu không phải student
            }

            // Cập nhật thông tin nếu cần
            if (isset($row['name']) && !empty($row['name'])) {
                $student->name = $row['name'];
            }
            
            // Cập nhật password nếu có
            if (isset($row['password']) && !empty($row['password'])) {
                $student->password = Hash::make($row['password']);
            }
            
            $student->save();
            $this->stats['updated']++;

            // Thêm vào lớp nếu chưa có
            if (!$student->classes()->where('class_sections.class_id', $this->classId)->exists()) {
                $student->classes()->attach($this->classId);
                $this->stats['assigned']++;
            }

            return null; // Không tạo mới
        }

        // Nếu sinh viên chưa tồn tại - Tạo mới
        $newStudent = User::create([
            'name' => $row['name'],
            'email' => $row['email'],
            'password' => Hash::make($row['password'] ?? 'password123'),
            'role' => 'student',
            'isFirstLogin' => true,
            'isHaveGroup' => false,
        ]);

        // Gán vào lớp
        $newStudent->classes()->attach($this->classId);
        
        $this->stats['created']++;

        return null; // Return null vì đã xử lý thủ công
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'nullable|string|min:6',
        ];
    }

    /**
     * Custom validation messages
     */
    public function customValidationMessages()
    {
        return [
            'name.required' => 'Tên sinh viên không được để trống',
            'email.required' => 'Email không được để trống',
            'email.email' => 'Email không đúng định dạng',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự',
        ];
    }

    /**
     * Lấy thống kê sau khi import
     */
    public function getStats()
    {
        return $this->stats;
    }
}