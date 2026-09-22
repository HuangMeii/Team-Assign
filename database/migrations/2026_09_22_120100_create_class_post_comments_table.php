<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bình luận dưới bài viết của bảng tin lớp học (kiểu Google Classroom).
 *
 * - Sinh viên trong lớp, giảng viên phụ trách và admin đều bình luận được.
 * - `parent_id` cho phép TRẢ LỜI 1 CẤP một bình luận khác (reply).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_post_comments', function (Blueprint $table) {
            $table->id('comment_id');
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('parent_id')->nullable(); // reply 1 cấp
            $table->text('content');
            $table->timestamps();

            $table->foreign('post_id')->references('post_id')->on('class_posts')->onDelete('cascade');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('parent_id')->references('comment_id')->on('class_post_comments')->onDelete('cascade');

            $table->index(['post_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_post_comments');
    }
};
