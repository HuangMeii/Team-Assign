<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vector ngữ nghĩa của 1 đề tài (bảng `topic_embeddings`) — 1 hàng / đề tài / model.
 *
 * Được sinh bởi service AI (AI-Services/topic-recommender, port 8891) và LƯU LẠI để
 * không phải embedding lại đề tài ở mỗi lần gợi ý (xem App\Services\TopicEmbeddingService).
 *
 * @property int $topic_id
 * @property string $model
 * @property int $dim
 * @property string $content_hash
 * @property string $embedding
 * @property \Illuminate\Support\Carbon|null $embedded_at
 * @property-read float[] $vector
 * @property-read \App\Models\Topics|null $topic
 */
class TopicEmbedding extends Model
{
    protected $table = 'topic_embeddings';

    protected $primaryKey = 'topic_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'topic_id',
        'model',
        'dim',
        'content_hash',
        'embedding',
        'embedded_at',
    ];

    protected $casts = [
        'dim' => 'integer',
        'embedded_at' => 'datetime',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topics::class, 'topic_id', 'topic_id');
    }

    /**
     * float[] -> base64(float32 little-endian).
     * 768 chiều => 3.072 byte nhị phân => ~4 KB base64 (nhỏ hơn nhiều so với JSON).
     */
    public static function encodeVector(array $vector): string
    {
        $floats = array_map('floatval', array_values($vector));

        return base64_encode(pack('g*', ...$floats));
    }

    /**
     * base64(float32 little-endian) -> float[].
     * Dữ liệu hỏng/ rỗng trả về [] để caller coi như "chưa có vector" (fail-open).
     */
    public static function decodeVector(?string $payload): array
    {
        $payload = (string) $payload;
        if ($payload === '') {
            return [];
        }

        $binary = base64_decode($payload, true);
        if ($binary === false || $binary === '') {
            return [];
        }

        $values = unpack('g*', $binary);
        if ($values === false) {
            return [];
        }

        return array_values($values);
    }

    /** Tiện dụng: $embedding->vector trả về mảng float (rỗng nếu chưa có dữ liệu). */
    public function getVectorAttribute(): array
    {
        return self::decodeVector($this->attributes['embedding'] ?? null);
    }
}
