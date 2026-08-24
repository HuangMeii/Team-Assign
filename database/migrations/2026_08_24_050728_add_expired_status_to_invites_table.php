<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bổ sung trạng thái 'Expired' (hết hiệu lực) vào enum status của bảng invites.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE invites MODIFY COLUMN status ENUM('Pending', 'Accepted', 'Rejected', 'Expired') NOT NULL DEFAULT 'Pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE invites MODIFY COLUMN status ENUM('Pending', 'Accepted', 'Rejected') NOT NULL DEFAULT 'Pending'");
    }
};
