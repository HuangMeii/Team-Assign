<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topic_requests', function (Blueprint $table) {
            $table->id('request_id');
            $table->unsignedBigInteger('topic_id');
            $table->unsignedBigInteger('group_id');
            $table->enum('status', ['Pending', 'Accepted', 'Rejected'])->default('Pending');
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('topic_id')
                ->references('topic_id')
                ->on('topics')
                ->onDelete('cascade');

            $table->foreign('group_id')
                ->references('group_id')
                ->on('groups')
                ->onDelete('cascade');

            $table->foreign('created_by')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');

            $table->unique(['topic_id', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topic_requests');
    }
};