<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm cột status (trạng thái nhóm: chưa đủ thành viên / đã đủ thành viên)
     * vào bảng groups.
     */
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->enum('status', ['incomplete', 'complete'])->default('incomplete')->after('class_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
