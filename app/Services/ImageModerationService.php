<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Kiểm duyệt ảnh qua server Cloud Vision (project ImageCommentClassification).
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
        $url = config('services.vision.url', env('VISION_MODERATION_URL', 'http://localhost:8888'));

        // Tạo URL công khai để Vision fetch. Nếu APP_URL là localhost mà
        // server Vision chạy máy khác thì đổi VISION_PUBLIC_BASE_URL cho đúng.
        $publicBase = rtrim(env('VISION_PUBLIC_BASE_URL', config('app.url')), '/');
        $imageUrl = $publicBase . Storage::disk('public')->url($relativePath);

        try {
            $response = Http::timeout(15)->post(rtrim($url, '/') . '/check-review-images', [
                'imageUrls' => [$imageUrl],
            ]);

            if (!$response->successful()) {
                Log::warning('Vision moderation HTTP error: ' . $response->status());

                return ['passed' => true, 'violations' => null, 'skipped' => true];
            }

            $data = $response->json();
            $passed = (bool) ($data['passed'] ?? true);
            $violations = $data['flagged'][0]['violations'] ?? null;

            return ['passed' => $passed, 'violations' => $violations];
        } catch (\Throwable $e) {
            Log::warning('Vision moderation skipped (server offline?): ' . $e->getMessage());

            return ['passed' => true, 'violations' => null, 'skipped' => true];
        }
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
