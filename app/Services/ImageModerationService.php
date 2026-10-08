<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Kiểm duyệt ảnh qua server Cloud Vision (repo AI-Services: services/vision-8888).
 * Server Node chạy ở VISION_MODERATION_URL (mặc định http://localhost:8888),
 * endpoint POST /check-review-images { imageUrls: [...] } -> { passed, flagged }.
 *
 * Nguyên tắc fail-open: nếu server Vision tắt/lỗi mạng thì CHO QUA và log lại,
 * để chat không bị đứng. Chỉ chặn khi Vision trả về passed=false rõ ràng.
 */
class ImageModerationService
{
    /**
     * Kiểm tra 1 file ảnh local (đường dẫn tương đối trong disk public).
     * Trả về ['passed' => bool, 'violations' => string|null].
     */
    public static function checkStoredImage(string $relativePath): array
    {
        $spec = self::requestSpec($relativePath);

        try {
            $response = Http::timeout(self::timeout())->post($spec['url'], $spec['payload']);

            if (!$response->successful()) {
                Log::warning('Vision moderation HTTP error: ' . $response->status());

                return ['passed' => true, 'violations' => null, 'skipped' => true];
            }

            return self::parseResponse($response->json());
        } catch (\Throwable $e) {
            Log::warning('Vision moderation skipped (server offline?): ' . $e->getMessage());

            return ['passed' => true, 'violations' => null, 'skipped' => true];
        }
    }

    /**
     * L08: mô tả request cần gửi tới server Vision (url + payload) để có thể đưa vào
     * `Http::pool()` chạy SONG SONG với các check text/sensitive.
     *
     * @return array{url:string,payload:array{imageUrls:array<int,string>}}
     */
    public static function requestSpec(string $relativePath): array
    {
        $url = config('services.vision.url', env('VISION_MODERATION_URL', 'http://localhost:8888'));

        // Tạo URL công khai để Vision fetch. Nếu APP_URL là localhost mà
        // server Vision chạy máy khác thì đổi VISION_PUBLIC_BASE_URL cho đúng.
        $publicBase = rtrim(env('VISION_PUBLIC_BASE_URL', config('app.url')), '/');
        $imageUrl = $publicBase . Storage::disk('public')->url($relativePath);

        return [
            'url' => rtrim($url, '/') . '/check-review-images',
            'payload' => ['imageUrls' => [$imageUrl]],
        ];
    }

    /** Chuẩn hoá body JSON của Vision về ['passed', 'violations'] (fail-open khi sai). */
    public static function parseResponse(mixed $data): array
    {
        if (!is_array($data)) {
            return ['passed' => true, 'violations' => null, 'skipped' => true];
        }

        return [
            'passed' => (bool) ($data['passed'] ?? true),
            'violations' => $data['flagged'][0]['violations'] ?? null,
        ];
    }

    /** Timeout gọi Vision (L08: giảm 15s -> 5s để chat kèm ảnh hiện nhanh hơn). */
    public static function timeout(): float
    {
        return (float) config('services.vision.timeout', 5);
    }

    /**
     * Kiểm tra nhiều URL ảnh công khai. Trả về ['passed', 'flagged'].
     */
    public static function checkImageUrls(array $imageUrls): array
    {
        $url = config('services.vision.url', env('VISION_MODERATION_URL', 'http://localhost:8888'));

        try {
            $response = Http::timeout(20)->post(rtrim($url, '/') . '/check-review-images', [
                'imageUrls' => array_values($imageUrls),
            ]);

            if (!$response->successful()) {
                return ['passed' => true, 'flagged' => []];
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::warning('Vision moderation skipped (server offline?): ' . $e->getMessage());

            return ['passed' => true, 'flagged' => []];
        }
    }
}
