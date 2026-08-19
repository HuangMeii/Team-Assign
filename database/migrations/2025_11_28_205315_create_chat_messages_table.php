<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            // Khóa ngoại liên kết với Groups (group_id trong bảng groups)
            $table->foreignId('group_id')->constrained('groups', 'group_id')->onDelete('cascade');
            // Khóa ngoại liên kết với User (user_id trong bảng users)
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->text('content');
            $table->timestamps();
            $table->index('group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};