<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id('group_id');
            $table->string('group_name');
            $table->unsignedBigInteger('leader_id');
            $table->unsignedBigInteger('topic_id')->nullable();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->timestamps();

            $table->foreign('leader_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('topic_id')
                ->references('topic_id')
                ->on('topics')
                ->onDelete('set null');

            $table->foreign('class_id')
                ->references('class_id')
                ->on('class_sections')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
