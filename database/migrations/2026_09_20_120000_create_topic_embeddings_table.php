<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng lưu VECTOR NGỮ NGHĨA của đề tài (tính năng "gợi ý đề tài theo ngữ nghĩa").
 *
 * Nguyên tắc: mỗi đề tài chỉ được embedding MỘT LẦN và lưu lại đây, không embedding lại
 * ở mỗi lần gợi ý. Cột `content_hash` (sha1 của text đã embed) cho biết vector còn khớp
 * với nội dung đề tài hay không — nội dung đổi ⇒ hash đổi ⇒ chỉ đề tài đó được embed lại.
 *
 * MySQL 8.4 CHƯA có kiểu `VECTOR` (chỉ có từ MySQL 9.x) nên vector lưu dạng LONGTEXT
 * base64(float32 little-endian) ~4 KB / 768 chiều (xem TopicEmbedding::encodeVector()).
 * Cosine similarity được tính ở service AI (AI-Services/topic-recommender, port 8891).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topic_embeddings', function (Blueprint $table) {
            $table->unsignedBigInteger('topic_id')->primary();
            $table->string('model', 120);        // nhãn model: vietnamese-sbert
            $table->unsignedSmallInteger('dim'); // số chiều vector: 768
            $table->char('content_hash', 40);    // sha1 của text đã embed
            $table->longText('embedding');       // base64(float32 LE)
            $table->timestamp('embedded_at')->nullable();
            $table->timestamps();

            $table->index('model');              // đổi model ⇒ biết hàng nào cần embed lại

            $table->foreign('topic_id')
                ->references('topic_id')
                ->on('topics')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topic_embeddings');
    }
};
