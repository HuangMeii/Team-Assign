<?php

namespace App\Services;

use App\Models\TopicEmbedding;
use App\Models\Topics;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Sinh + LƯU vector ngữ nghĩa của đề tài (bảng `topic_embeddings`).
 *
 * Nguyên tắc "tránh embedding lại nhiều lần":
 *  - Mỗi đề tài được embedding 1 lần, lưu vào DB kèm `content_hash` (sha1 của text đã embed).
 *  - Lần gợi ý sau: hash còn khớp ⇒ dùng lại vector trong DB, KHÔNG gọi model.
 *  - Nội dung đề tài đổi ⇒ hash đổi ⇒ chỉ đề tài đó được embed lại.
 *  - Đổi model (model_tag) ⇒ hàng cũ không khớp model ⇒ embed lại bằng model mới.
 *
 * Fail-open: service AI (AI-Services/topic-recommender, port 8891) tắt/lỗi/timeout thì
 * ghi log warning và trả về những vector đã có — KHÔNG làm vỡ trang tìm kiếm đề tài.
 *
 * @see \App\Services\TopicRecommendationService
 */
class TopicEmbeddingService
{
    /** Số đề tài gửi trong 1 request /embed (batch) — vừa đủ để không quá lâu trên CPU. */
    public const BATCH_SIZE = 32;

    /**
     * Text đem đi embedding của 1 đề tài: tên + mô tả + mục tiêu + yêu cầu.
     * Bỏ qua phần rỗng để vector không bị nhiễu bởi nhãn vô nghĩa.
     */
    public static function embeddingText(Topics $topic): string
    {
        $fields = [
            'name' => '',
            'description' => '',
            'goal' => 'Mục tiêu: ',
            'requirements' => 'Yêu cầu: ',
        ];

        $parts = [];
        foreach ($fields as $field => $prefix) {
            $value = trim((string) ($topic->{$field} ?? ''));
            if ($value !== '') {
                $parts[] = $prefix . $value;
            }
        }

        return trim(implode("\n", $parts));
    }

    /** sha1 của text đã embed — dùng để biết vector trong DB còn khớp nội dung hay không. */
    public static function hashFor(string $text): string
    {
        return sha1($text);
    }

    /** Nhãn model lưu trong DB (`topic_embeddings.model`). */
    public static function modelTag(): string
    {
        return (string) config('services.topic_recommender.model_tag', 'vietnamese-sbert');
    }

    /** URL server AI (rỗng ⇒ coi như tính năng chưa cấu hình). */
    public function url(): string
    {
        return rtrim((string) config('services.topic_recommender.url', ''), '/');
    }

    /** Tính năng có được bật không (env TOPIC_RECOMMENDER_ENABLED + có URL). */
    public function enabled(): bool
    {
        return (bool) config('services.topic_recommender.enabled', false) && $this->url() !== '';
    }

    /**
     * Gọi POST /embed để sinh vector cho danh sách text.
     *
     * @param  string[]  $texts
     * @return array<int, float[]>
     *
     * @throws RuntimeException server tắt / lỗi / trả dữ liệu sai (caller tự fail-open)
     */
    public function embedTexts(array $texts): array
    {
        $texts = array_values(array_filter(
            array_map(static fn ($text) => (string) $text, $texts),
            static fn (string $text) => trim($text) !== ''
        ));

        if ($texts === []) {
            return [];
        }

        if (! $this->enabled()) {
            throw new RuntimeException('Topic recommender chưa bật hoặc thiếu URL cấu hình.');
        }

        $response = Http::timeout((int) config('services.topic_recommender.embed_timeout', 120))
            ->acceptJson()
            ->post($this->url() . '/embed', ['texts' => $texts]);

        if (! $response->ok()) {
            throw new RuntimeException('Topic recommender /embed lỗi: HTTP ' . $response->status());
        }

        $vectors = $response->json('embeddings');
        if (! is_array($vectors) || count($vectors) !== count($texts)) {
            throw new RuntimeException('Topic recommender /embed trả dữ liệu không hợp lệ.');
        }

        return array_values($vectors);
    }

    /**
     * Bảo đảm mọi đề tài trong $topics đều có vector, trả về [topic_id => vector].
     *
     * @param  EloquentCollection<int, Topics>  $topics
     * @return array<int, float[]>
     */
    public function ensureFor(EloquentCollection $topics, bool $force = false): array
    {
        if ($topics->isEmpty()) {
            return [];
        }

        $model = self::modelTag();
        $hashes = [];
        $texts = [];

        foreach ($topics as $topic) {
            $text = self::embeddingText($topic);
            $hashes[$topic->topic_id] = self::hashFor($text);
            $texts[$topic->topic_id] = $text;
        }

        // Vector đã lưu và còn khớp nội dung ⇒ dùng lại, KHÔNG embedding lại.
        $vectors = [];
        if (! $force) {
            $stored = TopicEmbedding::whereIn('topic_id', array_keys($hashes))
                ->where('model', $model)
                ->get()
                ->keyBy('topic_id');

            foreach ($stored as $topicId => $row) {
                $vector = $row->vector;
                if ($vector !== [] && (int) $row->dim === count($vector) && $row->content_hash === $hashes[$topicId]) {
                    $vectors[(int) $topicId] = $vector;
                }
            }
        }

        $missing = array_values(array_diff(array_keys($hashes), array_keys($vectors)));
        if ($missing === [] || ! $this->enabled()) {
            return $vectors;
        }

        foreach (array_chunk($missing, self::BATCH_SIZE) as $chunk) {
            try {
                $batch = $this->embedTexts(array_map(static fn ($id) => $texts[$id], $chunk));
            } catch (\Throwable $e) {
                Log::warning('Topic embedding skipped (service AI tắt/lỗi?): ' . $e->getMessage());

                break; // fail-open: dùng những vector đã có
            }

            foreach ($chunk as $index => $topicId) {
                $vector = $batch[$index] ?? [];
                if (! is_array($vector) || $vector === []) {
                    continue;
                }

                $this->store((int) $topicId, $model, $vector, $hashes[$topicId]);
                $vectors[(int) $topicId] = array_map('floatval', $vector);
            }
        }

        return $vectors;
    }

    /** Lưu (hoặc cập nhật) vector của 1 đề tài. */
    public function store(int $topicId, string $model, array $vector, string $contentHash): TopicEmbedding
    {
        return TopicEmbedding::updateOrCreate(
            ['topic_id' => $topicId],
            [
                'model' => $model,
                'dim' => count($vector),
                'content_hash' => $contentHash,
                'embedding' => TopicEmbedding::encodeVector($vector),
                'embedded_at' => now(),
            ]
        );
    }

    /** Server AI có sẵn sàng không — cache 30 s để không gọi /health mỗi request. */
    public function isAvailable(): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        return (bool) cache()->remember('topic_recommender_health', now()->addSeconds(30), function () {
            try {
                $response = Http::timeout(3)->acceptJson()->get($this->url() . '/health');

                return $response->ok() && $response->json('status') === 'ok';
            } catch (\Throwable $e) {
                Log::warning('Topic recommender /health lỗi: ' . $e->getMessage());

                return false;
            }
        });
    }
}
