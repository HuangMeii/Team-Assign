<?php

namespace App\Services;

use App\Models\Subject;

/**
 * Sinh mã môn học duy nhất từ tên môn.
 * Format: Viết tắt chữ cái đầu mỗi từ + số thứ tự 3 số.
 * Ví dụ: "Lập trình Web" -> LTW001, tiếp theo LTW002.
 *
 * Dùng chung cho SubjectController@store và SubjectsImport
 * để không lệch logic giữa thêm tay và import.
 */
class SubjectCodeService
{
    public static function generate(string $subjectName): string
    {
        $words = preg_split('/\s+/u', trim($subjectName));
        $prefix = '';

        if (is_array($words)) {
            foreach ($words as $word) {
                if ($word !== '') {
                    $prefix .= mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8');
                }
            }
        }

        $prefix = $prefix !== '' ? mb_substr($prefix, 0, 5, 'UTF-8') : 'SUB';
        $prefixLen = mb_strlen($prefix, 'UTF-8');

        // Tìm số thứ tự lớn nhất đang dùng cho prefix này.
        // Dùng pluck + parse PHP để tương thích MySQL/SQLite.
        $max = 0;
        $codes = Subject::where('subject_code', 'like', $prefix . '%')->pluck('subject_code');
        foreach ($codes as $code) {
            $suffix = mb_substr((string) $code, $prefixLen, null, 'UTF-8');
            if ($suffix !== '' && ctype_digit($suffix)) {
                $max = max($max, (int) $suffix);
            }
        }

        $nextNumber = $max + 1;

        do {
            $code = $prefix . str_pad((string) $nextNumber++, 3, '0', STR_PAD_LEFT);
        } while (Subject::where('subject_code', $code)->exists());

        return $code;
    }
}
