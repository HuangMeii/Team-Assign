<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L09 (2.1) — Dựng lại bảng `sessions` ĐÚNG chuẩn Laravel để dùng được
 * `SESSION_DRIVER=database` (và tính năng "Đăng xuất khỏi các phiên khác").
 *
 * Bảng cũ (migration 2026_08_18_164007) chỉ có `id` + `timestamps` và chưa
 * từng được dùng (driver đang là `file`) nên dựng lại là an toàn.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('sessions');

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');

        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }
};
