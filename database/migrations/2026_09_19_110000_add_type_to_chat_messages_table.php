<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Loại tin nhắn trong khung chat NHÓM:
     *   member       = tin nhắn thường của thành viên / trưởng nhóm;
     *   announcement = thông báo do admin gửi thẳng vào nhóm (không cần tham gia nhóm);
     *   warning      = cảnh báo do admin gửi thẳng vào nhóm.
     *
     * View và payload broadcast dựa vào cột này để render khác nhau, đồng thời giúp
     * phân biệt tin hệ thống với tin nhắn của thành viên.
     */
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->string('type', 20)->default('member')->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
