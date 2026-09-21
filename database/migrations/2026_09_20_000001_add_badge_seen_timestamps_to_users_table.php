<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mốc "đã xem" cho 2 badge KHÔNG tự mất khi xử lý:
 *  - join_requests_seen_at: badge "Yêu cầu" (mục sidebar) — bấm vào là badge về 0,
 *    chỉ hiện lại khi có yêu cầu tham gia MỚI.
 *  - invites_seen_at: badge "Lời mời" — tương tự.
 *
 * Badge Chat và badge chuông thông báo đã có cơ chế "đã đọc" riêng
 * (direct_messages.is_read / group_chat_reads.last_read_at / notifications.is_read)
 * nên không cần mốc ở đây.
 */
return new class extends Migration
{
    public function up(): void
    {
        $hasJoinSeen = Schema::hasColumn('users', 'join_requests_seen_at');
        $hasInviteSeen = Schema::hasColumn('users', 'invites_seen_at');

        Schema::table('users', function (Blueprint $table) use ($hasJoinSeen, $hasInviteSeen) {
            if (!$hasJoinSeen) {
                $table->timestamp('join_requests_seen_at')->nullable()->after('flagged_seen_at');
            }

            if (!$hasInviteSeen) {
                $table->timestamp('invites_seen_at')->nullable()->after('join_requests_seen_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'join_requests_seen_at')) {
                $table->dropColumn('join_requests_seen_at');
            }

            if (Schema::hasColumn('users', 'invites_seen_at')) {
                $table->dropColumn('invites_seen_at');
            }
        });
    }
};
