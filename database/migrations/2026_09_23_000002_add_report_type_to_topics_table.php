<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LOẠI BÁO CÁO của đề tài:
 *  final   = đồ án cuối kì (mặc định — mọi đề tài đang có)
 *  midterm = đồ án giữa kì (chỉ dùng khi môn học có report_count = 2)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->string('report_type', 10)->default('final')->after('is_active');
            $table->index(['class_id', 'report_type'], 'topics_class_report_idx');
        });

        // Backfill: mọi đề tài hiện có là đồ án cuối kì
        DB::table('topics')->update(['report_type' => 'final']);
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropIndex('topics_class_report_idx');
            $table->dropColumn('report_type');
        });
    }
};