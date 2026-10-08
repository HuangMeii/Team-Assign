<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bó-4 (L07 hardening) — Ràng buộc MÃ LỚP 5 KÝ TỰ ở tầng DB.
 *
 * Trước đây `class_sections.class_code` là `varchar(255) NULL` ⇒ quy tắc "đúng 5 ký tự"
 * chỉ được ép ở tầng code (validate khi SV nhập, generator khi tạo lớp). Một câu SQL tay
 * hoặc code path mới có thể ghi mã sai mà không ai chặn.
 *
 * Migration này đổi cột thành `CHAR(5) NOT NULL` (giữ unique index sẵn có).
 * An toàn vì: (1) migration L07 `2026_10_07_000001` đã quy đổi MỌI mã về đúng 5 ký tự;
 * (2) tất cả chỗ tạo lớp (admin/giảng viên/seeder/test fixtures) đều set `class_code`.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Chốt an toàn: nếu còn dòng nào lệch 5 ký tự/NULL thì quy đổi trước khi siết cột.
        $bad = DB::table('class_sections')
            ->where(function ($q) {
                $q->whereNull('class_code')
                    ->orWhere('class_code', '')
                    ->orWhereRaw('CHAR_LENGTH(class_code) != 5');
            })
            ->count();

        if ($bad > 0) {
            $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            $used = array_flip(
                DB::table('class_sections')->pluck('class_code')->filter()->all()
            );

            $rows = DB::table('class_sections')
                ->select('class_id', 'class_code')
                ->where(function ($q) {
                    $q->whereNull('class_code')
                        ->orWhere('class_code', '')
                        ->orWhereRaw('CHAR_LENGTH(class_code) != 5');
                })
                ->get();

            foreach ($rows as $row) {
                do {
                    $code = '';
                    for ($i = 0; $i < 5; $i++) {
                        $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
                    }
                } while (isset($used[$code]));

                $used[$code] = true;

                DB::table('class_sections')
                    ->where('class_id', $row->class_id)
                    ->update(['class_code' => $code]);
            }
        }

        Schema::table('class_sections', function (Blueprint $table) {
            $table->char('class_code', 5)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('class_sections', function (Blueprint $table) {
            $table->string('class_code', 255)->nullable()->change();
        });
    }
};
