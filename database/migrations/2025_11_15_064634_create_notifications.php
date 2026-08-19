<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id('notification_id');
            $table->unsignedBigInteger('user_id');
            $table->string('type'); // topic_request, join_request, invite, etc.
            $table->string('title');
            $table->text('message');
            $table->text('data')->nullable(); // JSON data
            $table->string('url')->nullable(); // Link to action
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};