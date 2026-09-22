<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng tin lớp học (kiểu Google Classroom) — mỗi lớp có 1 dòng thời gian gồm:
 *   - Thông báo do GIẢNG VIÊN phụ trách lớp (hoặc admin) đăng.
 *   - Hoạt động NHÓM do hệ thống tự ghi (thành lập nhóm, thêm/rời thành viên,
 *     đổi trưởng nhóm, nhóm được duyệt đề tài...).
 *
 * `user_id = NULL` ⇒ bài do HỆ THỐNG sinh (không phải người đăng).
 * `source_key`    ⇒ khoá chống trùng cho lệnh backfill dữ liệu cũ
 *                   (vd `backfill:group:12:created`) — chạy lại KHÔNG nhân đôi bài.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_posts', function (Blueprint $table) {
            $table->id('post_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('user_id')->nullable();   // NULL = bài hệ thống
            $table->string('type', 40)->default('announcement');  // announcement | group_created | ...
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();   // link nhanh tới nhóm liên quan
            $table->unsignedBigInteger('topic_id')->nullable();   // link nhanh tới đề tài liên quan
            $table->json('meta')->nullable();                     // dữ liệu phụ (tên nhóm, số TV, tên đề tài...)
            $table->boolean('is_pinned')->default(false);         // ghim lên đầu bảng tin
            $table->unsignedInteger('comments_count')->default(0); // cache số bình luận
            $table->string('source_key', 120)->nullable();        // chống trùng khi backfill
            $table->timestamps();

            $table->foreign('class_id')->references('class_id')->on('class_sections')->onDelete('cascade');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('group_id')->references('group_id')->on('groups')->onDelete('set null');
            $table->foreign('topic_id')->references('topic_id')->on('topics')->onDelete('set null');

            // Truy vấn bảng tin: lọc theo lớp, ghim trước, rồi mới nhất
            $table->index(['class_id', 'is_pinned', 'created_at'], 'class_posts_feed_index');
            $table->unique('source_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_posts');
    }
};
