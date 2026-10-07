<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quy doi toan bo class_code ve dung 5 ky tu (thong nhat admin + giang vien).
 * - Ma dai > 5 hoac NULL/rong: sinh ma 5 ky tu moi (alphabet bo I,O,0,1 de nham), dam bao duy nhat.
 * - Ghi log mapping ma cu -> ma moi de thong bao lai cho SV.
 * - Lien ket user_classes/groups/topics theo class_id nen khong anh huong.
 */
return new class extends Migration
{
    public function up(): void
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        $makeCode = function () use ($alphabet) {
            $code = '';
            for ($i = 0; $i < 5; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            return $code;
        };

        $used = DB::table('class_sections')
            ->whereNotNull('class_code')
            ->whereRaw('CHAR_LENGTH(class_code) = 5')
            ->pluck('class_code')
            ->all();
        $usedMap = array_flip($used);

        $rows = DB::table('class_sections')
            ->select('class_id', 'class_code')
            ->where(function ($q) {
                $q->whereNull('class_code')
                    ->orWhere('class_code', '')
                    ->orWhereRaw('CHAR_LENGTH(class_code) != 5');
            })
            ->orderBy('class_id')
            ->get();

        foreach ($rows as $row) {
            do {
                $code = $makeCode();
            } while (isset($usedMap[$code]) || DB::table('class_sections')->where('class_code', $code)->exists());
            $usedMap[$code] = true;

            DB::table('class_sections')->where('class_id', $row->class_id)->update(['class_code' => $code]);

            logger()->info("Normalize class_code: class_id={$row->class_id} '{$row->class_code}' -> '{$code}'");
        }
    }

    public function down(): void
    {
        // Khong the khoi phuc ma cu (khong luu mapping) — migration 1 chieu.
    }
};
