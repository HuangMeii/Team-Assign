<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm cột rejection_reason (lý do từ chối) và bổ sung trạng thái
     * 'Cancelled'/'Expired' vào enum status của bảng topic_requests.
     */
    public function up(): void
    {
        Schema::table('topic_requests', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('status');
        });

        // MySQL không hỗ trợ sửa enum trực tiếp, dùng DB::statement
        DB::statement("ALTER TABLE topic_requests MODIFY COLUMN status ENUM('Pending', 'Accepted', 'Rejected', 'Cancelled', 'Expired') NOT NULL DEFAULT 'Pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE topic_requests MODIFY COLUMN status ENUM('Pending', 'Accepted', 'Rejected') NOT NULL DEFAULT 'Pending'");

        Schema::table('topic_requests', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }
};
