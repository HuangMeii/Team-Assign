<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bổ sung trạng thái 'Expired' (hết hiệu lực khi nhóm đã đủ thành viên)
     * vào enum status của bảng join_requests.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE join_requests MODIFY COLUMN status ENUM('Pending', 'Accepted', 'Rejected', 'Expired') NOT NULL DEFAULT 'Pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE join_requests MODIFY COLUMN status ENUM('Pending', 'Accepted', 'Rejected') NOT NULL DEFAULT 'Pending'");
    }
};
