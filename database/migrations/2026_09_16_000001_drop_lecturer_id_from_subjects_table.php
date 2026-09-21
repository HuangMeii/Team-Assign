<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phân công giảng viên chuyển hẳn sang cấp lớp học phần (user_classes).
     * Xóa cột subjects.lecturer_id và khóa ngoại đi kèm.
     */
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['lecturer_id']);
            $table->dropColumn('lecturer_id');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->unsignedBigInteger('lecturer_id')->nullable();

            $table->foreign('lecturer_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('set null');
        });
    }
};
