<?php

namespace App\Services;

use App\Models\Topics;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gợi ý đề tài theo NGỮ NGHĨA: sinh viên nhập mô tả mong muốn → trả về Top K đề tài
 * gần nghĩa nhất trong các lớp học phần mà sinh viên tham gia.
 *
 * Luồng (khớp sơ đồ thiết kế):
 *   UI → POST /api/recommend → Controller → Service này
 *     ├─ lấy đề tài ứng viên (giới hạn theo lớp + filter "còn trống")
 *     ├─ TopicEmbeddingService::ensureFor()  → chỉ embed đề tài THIẾU vector (lưu DB)
 *     └─ POST {AI}/recommend  → cosine similarity → Top K
 *
 * Fail-open: service AI tắt/lỗi ⇒ trả ['ok' => false, 'message' => ...] để UI báo nhẹ nhàng,
 * trang tìm kiếm/ đăng ký đề tài vẫn hoạt động bình thường.
 */
class TopicRecommendationService
{
    public function __construct(
        private readonly TopicEmbeddingService $embeddings,
    ) {}

    public function enabled(): bool
    {
        return $this->embeddings->enabled();
    }

    public function isAvailable(): bool
    {
        return $this->embeddings->isAvailable();
    }

    public function topK(): int
    {
        return max(1, (int) config('services.topic_recommender.top_k', 5));
    }

    public function minScore(): float
    {
        return (float) config('services.topic_recommender.min_score', 0.0);
    }

    public function maxCandidates(): int
    {
        return max(1, (int) config('services.topic_recommender.max_candidates', 500));
    }

    /**
     * Gợi ý Top K đề tài cho 1 câu mô tả.
     *
     * @param  int[]  $classIds  lớp học phần được phép tìm (sinh viên: lớp đã tham gia)
     * @return array{ok: bool, message: string|null, results: EloquentCollection<int, Topics>, debug: array<string, mixed>}
     */
    public function recommend(array $classIds, string $query, ?int $topK = null, bool $onlyAvailable = false): array
    {
        $query = trim($query);

        if ($query === '') {
            return $this->failure('Bạn hãy nhập mô tả đề tài mà nhóm mình muốn làm.');
        }

        if (! $this->enabled()) {
            return $this->failure('Chức năng gợi ý theo ngữ nghĩa đang tắt (TOPIC_RECOMMENDER_ENABLED). Bạn vẫn có thể tìm đề tài bằng ô tìm kiếm.');
        }

        $classIds = array_values(array_unique(array_filter(array_map('intval', $classIds))));
        if ($classIds === []) {
            return $this->failure('Bạn chưa tham gia lớp học phần nào nên chưa có đề tài để gợi ý.');
        }

        $topics = Topics::with(['subject', 'class'])
            ->whereIn('class_id', $classIds)
            ->when($onlyAvailable, fn ($builder) => $builder->whereNull('assigned_group_id'))
            ->orderBy('topic_id')
            ->limit($this->maxCandidates())
            ->get();

        if ($topics->isEmpty()) {
            return [
                'ok' => true,
                'message' => 'Không có đề tài nào phù hợp bộ lọc trong lớp của bạn.',
                'results' => new EloquentCollection(),
                'debug' => ['candidates' => 0],
            ];
        }

        // Chỉ embed đề tài CHƯA có vector (hoặc nội dung đã đổi) — kết quả lưu vào DB.
        $vectors = $this->embeddings->ensureFor($topics);

        if ($vectors === []) {
            return $this->failure('Service AI gợi ý đang không khả dụng, vui lòng thử lại sau (tìm kiếm thường vẫn dùng được).');
        }

        return $this->score($topics, $vectors, $query, $topK, $onlyAvailable);
    }

    /**
     * Gọi /recommend (cosine similarity ở service AI) rồi map kết quả về model Topics.
     *
     * @param  EloquentCollection<int, Topics>  $topics
     * @param  array<int, float[]>  $vectors
     * @return array{ok: bool, message: string|null, results: EloquentCollection<int, Topics>, debug: array<string, mixed>}
     */
    private function score(EloquentCollection $topics, array $vectors, string $query, ?int $topK, bool $onlyAvailable): array
    {
        $payload = [];
        foreach ($topics as $topic) {
            $item = ['id' => (int) $topic->topic_id];

            if (isset($vectors[$topic->topic_id])) {
                $item['embedding'] = $vectors[$topic->topic_id];
            } else {
                // Server AI sẽ embed hộ đề tài này và trả vector về để Laravel lưu lại.
                $item['text'] = TopicEmbeddingService::embeddingText($topic);
            }

            $payload[] = $item;
        }

        try {
            $response = Http::timeout((int) config('services.topic_recommender.timeout', 15))
                ->acceptJson()
                ->post($this->embeddings->url() . '/recommend', [
                    'query' => $query,
                    'topics' => $payload,
                    'top_k' => $topK ?? $this->topK(),
                    'min_score' => $this->minScore(),
                ]);
        } catch (\Throwable $e) {
            Log::warning('Topic recommend bỏ qua (service AI tắt/lỗi?): ' . $e->getMessage());

            return $this->failure('Không kết nối được service AI gợi ý, vui lòng thử lại sau.');
        }

        if (! $response->ok()) {
            Log::warning('Topic recommend lỗi: HTTP ' . $response->status());

            return $this->failure('Service AI gợi ý trả lỗi, vui lòng thử lại sau.');
        }

        $data = $response->json();

        // Lưu vector mà server vừa embed hộ ⇒ lần sau không phải embed lại đề tài đó.
        $this->persistReturnedVectors($topics, (array) ($data['embedded'] ?? []));

        $byId = $topics->keyBy('topic_id');
        $model = TopicEmbeddingService::modelTag();
        $results = new EloquentCollection();

        foreach ((array) ($data['results'] ?? []) as $row) {
            $topic = $byId->get((int) ($row['id'] ?? 0));
            if (! $topic) {
                continue;
            }

            $similarity = round((float) ($row['score'] ?? 0), 4);
            $topic->setAttribute('similarity', $similarity);
            $topic->setAttribute('similarity_percent', (int) round(max(0.0, $similarity) * 100));
            $results->push($topic);
        }

        return [
            'ok' => true,
            'message' => $results->isEmpty()
                ? 'Chưa tìm thấy đề tài nào đủ gần với mô tả của bạn, thử mô tả chi tiết hơn nhé.'
                : null,
            'results' => $results,
            'debug' => [
                'model' => $data['model'] ?? $model,
                'top_k' => $data['top_k'] ?? ($topK ?? $this->topK()),
                'candidates' => $data['candidates'] ?? $topics->count(),
                'scored' => $data['scored'] ?? null,
                'embedded_now' => $data['embedded_count'] ?? 0,
                'only_available' => $onlyAvailable,
                'latency_ms' => $data['latency_ms'] ?? null,
            ],
        ];
    }

    /**
     * Lưu các vector server vừa sinh hộ (đề tài chưa có vector trong DB).
     *
     * @param  EloquentCollection<int, Topics>  $topics
     * @param  array<string|int, float[]>  $embedded
     */
    private function persistReturnedVectors(EloquentCollection $topics, array $embedded): void
    {
        if ($embedded === []) {
            return;
        }

        $model = TopicEmbeddingService::modelTag();
        $byId = $topics->keyBy('topic_id');

        foreach ($embedded as $topicId => $vector) {
            $topic = $byId->get((int) $topicId);
            if (! $topic || ! is_array($vector) || $vector === []) {
                continue;
            }

            try {
                $this->embeddings->store(
                    (int) $topicId,
                    $model,
                    $vector,
                    TopicEmbeddingService::hashFor(TopicEmbeddingService::embeddingText($topic))
                );
            } catch (\Throwable $e) {
                Log::warning('Không lưu được vector đề tài #' . $topicId . ': ' . $e->getMessage());
            }
        }
    }

    private function failure(string $message): array
    {
        return [
            'ok' => false,
            'message' => $message,
            'results' => new EloquentCollection(),
            'debug' => [],
        ];
    }
}
