<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trạng thái "đã đọc" của từng user theo từng NHÓM.
     *
     * Chat 1-1 đã có sẵn direct_messages.is_read (đã đọc theo từng tin nhắn),
     * còn chat nhóm chỉ cần mốc thời gian đọc cuối cùng:
     *   số tin chưa đọc của nhóm = chat_messages.created_at > last_read_at.
     * Một dòng / (user, group) — nhóm chưa từng mở thì chưa có dòng (coi như chưa đọc gì).
     */
    public function up(): void
    {
        Schema::create('group_chat_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups', 'group_id')->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_chat_reads');
    }
};
