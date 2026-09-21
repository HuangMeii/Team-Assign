<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mốc "admin đã mở trang Giám sát Chat / tab Bị gắn cờ lần cuối".
     *
     * Badge "Giám sát Chat" = số tin nhắn/ảnh bị gắn cờ có flagged_at MỚI HƠN
     * flagged_seen_at, tức là tin bị cờ CHƯA ĐƯỢC XEM. Mở trang/tab "Bị gắn cờ"
     * sẽ set mốc này = now() nên badge reset về 0 ngay, không cần tải lại trang;
     * khi có tin bị gắn cờ mới thì badge đếm lại từ 1.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('flagged_seen_at')->nullable()->after('unread_notifications');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('flagged_seen_at');
        });
    }
};
