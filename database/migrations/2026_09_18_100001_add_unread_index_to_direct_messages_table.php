<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index phục vụ badge "chưa đọc theo đúng người gửi":
     * COUNT(*) WHERE recipient_id = ? AND is_read = 0 GROUP BY sender_id.
     */
    public function up(): void
    {
        Schema::table('direct_messages', function (Blueprint $table) {
            $table->index(['recipient_id', 'is_read'], 'direct_messages_recipient_unread_index');
        });
    }

    public function down(): void
    {
        Schema::table('direct_messages', function (Blueprint $table) {
            $table->dropIndex('direct_messages_recipient_unread_index');
        });
    }
};
