<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id('topic_id');
            $table->string('name');
            $table->longText('description')->nullable();
            $table->string('lecturer')->nullable();
            $table->longText('goal')->nullable();
            $table->longText('requirements')->nullable();
            $table->unsignedBigInteger('assigned_group_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->timestamps();

            $table->foreign('subject_id')
                ->references('subject_id')
                ->on('subjects')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};