<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm cột attachment (ảnh/file đính kèm) vào bảng direct_messages và chat_messages.
     */
        public function up(): void
    {
        Schema::table('direct_messages', function (Blueprint $table) {
            $table->string('attachment')->nullable()->after('content');
            $table->boolean('is_read')->default(false)->after('attachment');
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->string('attachment')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('direct_messages', function (Blueprint $table) {
            $table->dropColumn(['attachment', 'is_read']);
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn('attachment');
        });
    }
};
