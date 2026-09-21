<?php

namespace App\Console\Commands;

use App\Models\TopicEmbedding;
use App\Models\Topics;
use App\Services\TopicEmbeddingService;
use Illuminate\Console\Command;

/**
 * Sinh & lưu vector ngữ nghĩa cho đề tài (bảng `topic_embeddings`).
 *
 * Dùng để backfill 1 lần cho dữ liệu cũ, hoặc sau khi đổi model embedding.
 * Bình thường KHÔNG cần chạy tay: mỗi lần gợi ý, service chỉ embed những đề tài còn thiếu.
 *
 *   php artisan topics:embed                 # chỉ embed đề tài thiếu / đổi nội dung
 *   php artisan topics:embed --class=3       # giới hạn theo lớp học phần
 *   php artisan topics:embed --topic=12      # đúng 1 đề tài
 *   php artisan topics:embed --force         # embed lại TẤT CẢ (sau khi đổi model)
 */
class EmbedTopicsCommand extends Command
{
    protected $signature = 'topics:embed
        {--class= : Chỉ xử lý đề tài của lớp học phần này (class_id)}
        {--topic= : Chỉ xử lý đúng 1 đề tài (topic_id)}
        {--force : Embed lại cả những đề tài đã có vector (dùng khi đổi model)}';

    protected $description = 'Sinh & lưu vector ngữ nghĩa cho đề tài (phục vụ gợi ý đề tài, AI service :8891)';

    public function handle(TopicEmbeddingService $embeddings): int
    {
        $model = TopicEmbeddingService::modelTag();

        $this->line("Model tag : <info>{$model}</info>");
        $this->line('AI service: <info>' . ($embeddings->url() ?: '(chưa cấu hình)') . '</info>');

        if (! $embeddings->enabled()) {
            $this->error('Tính năng chưa bật: đặt TOPIC_RECOMMENDER_ENABLED=true và TOPIC_RECOMMENDER_URL trong .env rồi chạy `php artisan config:clear`.');

            return self::FAILURE;
        }

        if (! $embeddings->isAvailable()) {
            $this->error('Service AI :8891 chưa sẵn sàng — bật bằng AI-Services\\start-servers.ps1 rồi chạy lại.');

            return self::FAILURE;
        }

        $query = Topics::query()->orderBy('topic_id');

        if ($this->option('class') !== null) {
            $query->where('class_id', (int) $this->option('class'));
        }

        if ($this->option('topic') !== null) {
            $query->where('topic_id', (int) $this->option('topic'));
        }

        $topics = $query->get();

        if ($topics->isEmpty()) {
            $this->warn('Không có đề tài nào khớp bộ lọc.');

            return self::SUCCESS;
        }

        $force = (bool) $this->option('force');
        $stored = TopicEmbedding::where('model', $model)->pluck('content_hash', 'topic_id');

        $need = $topics->filter(function (Topics $topic) use ($stored, $force) {
            if ($force) {
                return true;
            }

            $hash = TopicEmbeddingService::hashFor(TopicEmbeddingService::embeddingText($topic));

            return ($stored[$topic->topic_id] ?? null) !== $hash;
        });

        $this->line("Đề tài khớp bộ lọc: <info>{$topics->count()}</info> · cần embedding: <info>{$need->count()}</info>");

        if ($need->isEmpty()) {
            $this->info('Tất cả đề tài đã có vector mới nhất — không cần embedding lại.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($need->count());
        $bar->start();

        $saved = 0;

        foreach ($need->chunk(100) as $chunk) {
            $vectors = $embeddings->ensureFor($chunk, $force);
            $saved += count($vectors);
            $bar->advance($chunk->count());
        }

        $bar->finish();
        $this->newLine(2);

        if ($saved === 0) {
            $this->error('Không lưu được vector nào — xem storage/logs/laravel.log (service AI lỗi?).');

            return self::FAILURE;
        }

        $this->info("Đã lưu vector cho {$saved}/{$need->count()} đề tài (bảng topic_embeddings).");

        return self::SUCCESS;
    }
}
