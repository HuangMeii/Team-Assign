<?php

namespace App\Http\Controllers;

use App\Services\TopicRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * API gợi ý đề tài theo ngữ nghĩa (POST /api/recommend).
 *
 * - Khai báo trong routes/web.php (KHÔNG phải routes/api.php) để dùng đúng session + CSRF
 *   như toàn bộ app hiện tại; JS gửi kèm header X-CSRF-TOKEN (xem components/topic-recommender.blade.php).
 * - Sinh viên chỉ được gợi ý trong các lớp học phần mình đã tham gia.
 * - Fail-open: service AI tắt/lỗi ⇒ HTTP 200 + ok:false + message để UI báo nhẹ nhàng (không 500).
 */
class TopicRecommendationController extends Controller
{
    public function __construct(
        private readonly TopicRecommendationService $recommendations,
    ) {}

    public function recommend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:3|max:1000',
            'class_id' => 'nullable|integer|exists:class_sections,class_id',
            'top_k' => 'nullable|integer|min:1|max:10',
            'only_available' => 'nullable|boolean',
        ], [], [
            'query' => 'mô tả đề tài',
            'class_id' => 'lớp học phần',
        ]);

        $user = Auth::user();
        $classIds = $this->allowedClassIds($request, $validated['class_id'] ?? null);

        $result = $this->recommendations->recommend(
            $classIds,
            (string) $validated['query'],
            isset($validated['top_k']) ? (int) $validated['top_k'] : null,
            (bool) ($validated['only_available'] ?? false),
        );

        return response()->json([
            'ok' => $result['ok'],
            'message' => $result['message'],
            'model' => config('services.topic_recommender.model_tag', 'vietnamese-sbert'),
            'results' => $result['results']
                ->map(fn ($topic) => $this->formatTopic($topic))
                ->values()
                ->all(),
            'meta' => array_merge($result['debug'], [
                'user_role' => $user->role ?? null,
                'class_ids' => $classIds,
            ]),
        ]);
    }

    /**
     * Danh sách lớp học phần được phép tìm kiếm.
     *
     * - Sinh viên / giảng viên: chỉ các lớp mình tham gia (bảng user_classes).
     * - Admin: mọi lớp; nếu truyền class_id thì giới hạn đúng lớp đó.
     *
     * @return int[]
     */
    private function allowedClassIds(Request $request, ?int $requestedClassId): array
    {
        $user = Auth::user();

        $classIds = $user->classes->pluck('class_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (($user->role ?? null) === 'admin') {
            $classIds = $requestedClassId
                ? [(int) $requestedClassId]
                : \App\Models\ClassSection::query()->pluck('class_id')->map(fn ($id) => (int) $id)->all();
        } elseif ($requestedClassId !== null) {
            abort_unless(in_array((int) $requestedClassId, $classIds, true), 403, 'Bạn không có quyền xem đề tài của lớp này.');
            $classIds = [(int) $requestedClassId];
        }

        return array_values(array_filter($classIds));
    }

    /**
     * @return array<string, mixed>
     */
    private function formatTopic($topic): array
    {
        return [
            'topic_id' => (int) $topic->topic_id,
            'name' => $topic->name,
            'description' => Str::limit((string) $topic->description, 220),
            'lecturer' => $topic->lecturer,
            'class_name' => $topic->class->class_name ?? null,
            'subject_name' => $topic->subject->subject_name ?? null,
            'min_members' => $topic->min_members,
            'max_members' => $topic->max_members,
            'is_available' => $topic->assigned_group_id === null,
            'similarity' => $topic->similarity,
            'similarity_percent' => $topic->similarity_percent,
            'url' => route('user.topic_detail', $topic->topic_id),
        ];
    }
}
