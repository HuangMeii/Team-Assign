<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('invitedBy');
            $table->unsignedBigInteger('member_id');
            $table->enum('status', ['Pending', 'Accepted', 'Rejected'])->default('Pending');
            $table->timestamps();

            $table->foreign('group_id')
                ->references('group_id')
                ->on('groups')
                ->onDelete('cascade');

            $table->foreign('invitedBy')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('member_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invites');
    }
};