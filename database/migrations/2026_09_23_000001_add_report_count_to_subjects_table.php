<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Số BÀI BÁO CÁO của môn học:
 *  1 = chỉ cuối kì (mặc định) · 2 = giữa kì + cuối kì.
 *
 * Mọi môn đang có được đặt = 1 (chỉ cuối kì) vì trước đây mỗi môn chỉ có 1 bài.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->unsignedTinyInteger('report_count')->default(1)->after('credits');
        });

        // Backfill: mọi môn hiện có = 1 bài (chỉ cuối kì)
        DB::table('subjects')->update(['report_count' => 1]);
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('report_count');
        });
    }
};