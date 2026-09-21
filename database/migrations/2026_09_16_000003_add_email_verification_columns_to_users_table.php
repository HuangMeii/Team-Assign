<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Đổi email phải xác thực qua email:
     * - email_verified_at: đánh dấu email hiện tại đã xác thực
     * - pending_email: email mới đang chờ người dùng click liên kết xác thực
     * Email hiện tại của mọi user có sẵn coi như đã xác thực (chỉ ĐỔI email mới phát sinh xác thực).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable();
            $table->string('pending_email')->nullable()->unique();
        });

        DB::table('users')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['pending_email']);
            $table->dropColumn(['email_verified_at', 'pending_email']);
        });
    }
};