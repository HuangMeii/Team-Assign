<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Trạng thái tin nhắn 1-1 (2 mốc): `direct_messages.seen_at`.
 *
 *   - `seen_at IS NULL`      → ĐÃ GỬI   (✓ xám)  — người nhận chưa mở xem
 *   - `seen_at` có giá trị   → ĐÃ XEM   (✓✓ xanh) — người nhận đã mở hội thoại
 *
 * Được set trong `ChatUnreadService::markDirectRead()` (gọi khi mở trang hội thoại
 * `chat.show` + AJAX `markRead` khi cửa sổ chat đang mở).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('direct_messages', function (Blueprint $table) {
            $table->timestamp('seen_at')->nullable();
        });

        // Dữ liệu cũ: tin đã đọc (is_read = 1) coi như đã xem tại lần cập nhật cuối
        // ⇒ hiển thị đúng ✓✓ xanh thay vì ✓ xám.
        DB::table('direct_messages')
            ->where('is_read', true)
            ->whereNull('seen_at')
            ->whereNotNull('updated_at')
            ->update(['seen_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('direct_messages', function (Blueprint $table) {
            $table->dropColumn('seen_at');
        });
    }
};
