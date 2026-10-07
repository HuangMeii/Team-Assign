<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * L05: them trang thai Dang hoc / Da roi lop cho tung (user_id, class_id).
     * Xoa khoi lop = UPDATE status='left' (xoa mem), khong detach dong pivot.
     */
    public function up(): void
    {
        Schema::table('user_classes', function (Blueprint $table) {
            $table->enum('status', ['studying', 'left'])->default('studying')->after('class_id');
            $table->timestamp('left_at')->nullable()->after('status');

            $table->index(['class_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_classes', function (Blueprint $table) {
            $table->dropIndex(['class_id', 'status']);
            $table->dropColumn(['status', 'left_at']);
        });
    }
};
