<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_sections', function (Blueprint $table) {
            $table->id('class_id');
            $table->unsignedBigInteger('subject_id');
            $table->string('class_name');
            $table->timestamps();

            $table->foreign('subject_id')
                ->references('subject_id')
                ->on('subjects')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sections');
    }
};