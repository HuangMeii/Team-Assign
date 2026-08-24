<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm các cột quản lý số lượng thành viên tối thiểu/tối đa, thời gian đăng ký
     * và trạng thái hoạt động vào bảng topics.
     */
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->unsignedInteger('min_members')->default(1)->after('requirements');
            $table->unsignedInteger('max_members')->default(5)->after('min_members');
            $table->timestamp('registration_deadline')->nullable()->after('max_members');
            $table->boolean('is_active')->default(true)->after('registration_deadline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropColumn([
                'min_members',
                'max_members',
                'registration_deadline',
                'is_active',
            ]);
        });
    }
};
