<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trạng thái online/offline của người dùng: `users.last_seen_at`.
 *
 * Được cập nhật bởi:
 *   - Middleware `App\Http\Middleware\UpdateLastSeen` (mọi request web, tối đa 1 lần/60s)
 *   - Endpoint `POST /presence/ping` (JS gọi mỗi 60s khi tab đang mở)
 *
 * Quy ước: online = last_seen_at trong vòng 2 phút (xem PresenceService::ONLINE_WINDOW_SECONDS).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_seen_at')->nullable();
            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['last_seen_at']);
            $table->dropColumn('last_seen_at');
        });
    }
};
