<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bộ đếm thông báo chưa đọc của user (badge chuông + badge "Yêu cầu" của leader).
     *
     * - Tăng ở NotificationService::create() (mọi thông báo đều đi qua đây).
     * - Reset khi user đánh dấu đã đọc (markAsRead / markAllAsRead).
     * - Đi kèm unread_message_count (chat) để badge trên nav-bar render
     *   được ngay từ server, không cần chờ AJAX.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('unread_notifications')->default(0)->after('unread_message_count');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('unread_notifications');
        });
    }
};
