<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            // Thêm class_id column
            $table->unsignedBigInteger('class_id')->nullable()->after('subject_id');
            
            // Thêm foreign key
            $table->foreign('class_id')
                ->references('class_id')
                ->on('class_sections')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            // Xóa foreign key
            $table->dropForeign(['class_id']);
            
            // Xóa column
            $table->dropColumn('class_id');
        });
    }
};