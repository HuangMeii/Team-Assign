<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm cột class_code (mã lớp để sinh viên tự tham gia) và is_active (khóa/mở khóa lớp)
     * vào bảng class_sections.
     */
    public function up(): void
    {
        Schema::table('class_sections', function (Blueprint $table) {
            $table->string('class_code')->nullable()->unique()->after('class_name');
            $table->boolean('is_active')->default(true)->after('class_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_sections', function (Blueprint $table) {
            $table->dropUnique(['class_code']);
            $table->dropColumn(['class_code', 'is_active']);
        });
    }
};
