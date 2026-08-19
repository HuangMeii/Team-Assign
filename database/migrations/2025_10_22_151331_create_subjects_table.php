<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id('subject_id');
            $table->string('subject_code')->unique();
            $table->string('subject_name');
            $table->unsignedBigInteger('lecturer_id')->nullable();
            $table->timestamps();

            $table->foreign('lecturer_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};