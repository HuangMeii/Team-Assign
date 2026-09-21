<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flag-only moderation: both text (CSV/PhoBERT) and image (Vision)
     * only set is_flagged + reason, never block sending.
     */
    public function up(): void
    {
        Schema::table('direct_messages', function (Blueprint $table) {
            $table->boolean('is_flagged')->default(false)->after('is_read');
            $table->string('flag_reason', 500)->nullable()->after('is_flagged');
            $table->float('moderation_score')->nullable()->after('flag_reason');
            $table->timestamp('flagged_at')->nullable()->after('moderation_score');
            $table->index('is_flagged');
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->boolean('is_flagged')->default(false)->after('attachment');
            $table->string('flag_reason', 500)->nullable()->after('is_flagged');
            $table->float('moderation_score')->nullable()->after('flag_reason');
            $table->timestamp('flagged_at')->nullable()->after('moderation_score');
            $table->index('is_flagged');
        });
    }

    public function down(): void
    {
        Schema::table('direct_messages', function (Blueprint $table) {
            $table->dropIndex(['is_flagged']);
            $table->dropColumn(['is_flagged', 'flag_reason', 'moderation_score', 'flagged_at']);
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropIndex(['is_flagged']);
            $table->dropColumn(['is_flagged', 'flag_reason', 'moderation_score', 'flagged_at']);
        });
    }
};
