<?php

namespace App\Http\Controllers;

use App\Models\ClassPost;
use App\Models\ClassPostComment;
use App\Models\ClassSection;
use App\Services\ClassStreamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * BẢNG TIN LỚP HỌC (kiểu Google Classroom) — thông báo của giảng viên + hoạt động nhóm
 * + bình luận. Trang dùng chung cho mọi vai trò; giao diện tự đổi theo quyền.
 *
 * Quyền (tập trung ở ClassStreamService):
 *   - Xem/bình luận : admin, giảng viên phụ trách lớp, thành viên lớp.
 *   - Đăng/ghim/xoá : giảng viên PHỤ TRÁCH lớp hoặc admin (xoá bình luận: thêm tác giả).
 *
 * AJAX: khi request là JSON (`Accept: application/json` + `X-CSRF-TOKEN`), API trả về
 * `html` đã render để JS chèn thẳng vào trang — không cần template JS trùng lặp.
 */
class ClassStreamController extends Controller
{
    public function __construct(
        private readonly ClassStreamService $stream,
    ) {}

    /** Trang bảng tin của lớp. */
    public function index(Request $request, $classId)
    {
        $class = ClassSection::with(['subject', 'lecturers'])->findOrFail($classId);
        $this->authorizeView($class);

        return view('classes.stream', [
            'class' => $class,
            'posts' => $this->stream->feed($class, ClassStreamService::PER_PAGE),
            'canManage' => $this->stream->canManage(Auth::user(), $class),
            'isAdmin' => (Auth::user()->role ?? null) === 'admin',
            // Mỗi vai trò dùng layout riêng (sidebar khác nhau) — view tự chọn.
            'layout' => match (Auth::user()->role ?? null) {
                'admin' => 'layouts.admin',
                'lecturer' => 'layouts.app',
                default => 'layouts.user',
            },
        ]);
    }

    /** Giảng viên phụ trách (hoặc admin) đăng thông báo vào lớp. */
    public function store(Request $request, $classId)
    {
        $class = ClassSection::findOrFail($classId);
        $this->authorizeManage($class);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|min:3|max:' . ClassStreamService::MAX_POST_LENGTH,
            'is_pinned' => 'nullable|boolean',
        ], [], ['content' => 'nội dung thông báo']);

        $post = $this->stream->postAnnouncement(
            $class,
            Auth::user(),
            $validated['content'],
            $validated['title'] ?? null,
            (bool) ($validated['is_pinned'] ?? false)
        );

        $message = 'Đã đăng thông báo tới lớp ' . $class->class_name . '.';

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'post_id' => $post->post_id,
                'html' => $this->renderPost($post, $class),
            ]);
        }

        return back()->with('success', $message);
    }

    /** Ghim / bỏ ghim một bài (bài ghim luôn nằm đầu bảng tin). */
    public function pin(Request $request, $classId, ClassPost $post)
    {
        $class = ClassSection::findOrFail($classId);
        $this->authorizeManage($class);
        $this->assertPostInClass($post, $class);

        $pinned = $this->stream->togglePin($post);
        $message = $pinned ? 'Đã ghim bài lên đầu bảng tin.' : 'Đã bỏ ghim bài.';

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message, 'is_pinned' => $pinned]);
        }

        return back()->with('success', $message);
    }

    /** Xoá bài (tác giả, giảng viên phụ trách hoặc admin). */
    public function destroy(Request $request, $classId, ClassPost $post)
    {
        $class = ClassSection::findOrFail($classId);
        $this->authorizeView($class);
        $this->assertPostInClass($post, $class);

        abort_unless($this->stream->canDeletePost(Auth::user(), $post), 403, 'Bạn không có quyền xoá bài viết này.');

        $this->stream->deletePost($post);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => 'Đã xoá bài viết.']);
        }

        return back()->with('success', 'Đã xoá bài viết.');
    }

    /** Bình luận (hoặc trả lời một bình luận) dưới một bài. */
    public function storeComment(Request $request, $classId, ClassPost $post)
    {
        $class = ClassSection::findOrFail($classId);
        $this->authorizeView($class);
        $this->assertPostInClass($post, $class);

        $validated = $request->validate([
            'content' => 'required|string|min:1|max:' . ClassStreamService::MAX_COMMENT_LENGTH,
            'parent_id' => 'nullable|integer|exists:class_post_comments,comment_id',
        ], [], ['content' => 'nội dung bình luận']);

        $comment = $this->stream->addComment(
            $post,
            Auth::user(),
            $validated['content'],
            isset($validated['parent_id']) ? (int) $validated['parent_id'] : null
        );

        $message = 'Đã gửi bình luận.';

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'comment_id' => $comment->comment_id,
                'count' => $post->fresh()->comments_count,
                'html' => $this->renderComments($post->fresh(), $class),
            ]);
        }

        return back()->with('success', $message);
    }

    /** Danh sách bình luận mới nhất của một bài (AJAX: "xem thêm" + cập nhật realtime). */
    public function comments($classId, ClassPost $post): JsonResponse
    {
        $class = ClassSection::findOrFail($classId);
        $this->authorizeView($class);
        $this->assertPostInClass($post, $class);

        $post->load(['orderedComments.author', 'orderedComments.replies.author']);

        return response()->json([
            'ok' => true,
            'count' => $post->comments_count,
            'html' => $this->renderComments($post, $class),
        ]);
    }

    /** Xoá bình luận (tác giả, giảng viên phụ trách hoặc admin). */
    public function destroyComment(Request $request, $classId, ClassPostComment $comment): JsonResponse
    {
        $class = ClassSection::findOrFail($classId);
        $this->authorizeView($class);

        $post = $comment->post;
        abort_unless($post && (int) $post->class_id === (int) $class->class_id, 404);
        abort_unless($this->stream->canDeleteComment(Auth::user(), $comment), 403, 'Bạn không có quyền xoá bình luận này.');

        $this->stream->deleteComment($comment);

        $post->refresh();

        return response()->json([
            'ok' => true,
            'message' => 'Đã xoá bình luận.',
            'count' => $post->comments_count,
            'html' => $this->renderComments($post->load(['orderedComments.author', 'orderedComments.replies.author']), $class),
        ]);
    }

    // =====================================================================
    // QUYỀN + RENDER HTML (dùng cho AJAX)
    // =====================================================================

    private function authorizeView(ClassSection $class): void
    {
        abort_unless(
            $this->stream->canView(Auth::user(), $class),
            403,
            'Bạn không thuộc lớp học phần này.'
        );
    }

    private function authorizeManage(ClassSection $class): void
    {
        abort_unless(
            $this->stream->canManage(Auth::user(), $class),
            403,
            'Chỉ giảng viên phụ trách lớp (hoặc admin) được đăng thông báo.'
        );
    }

    private function assertPostInClass(ClassPost $post, ClassSection $class): void
    {
        abort_unless((int) $post->class_id === (int) $class->class_id, 404);
    }

    private function renderPost(ClassPost $post, ClassSection $class): string
    {
        $post->load(['author', 'group', 'orderedComments.author', 'orderedComments.replies.author']);

        return view('classes.partials.post', [
            'post' => $post,
            'class' => $class,
            'canManage' => $this->stream->canManage(Auth::user(), $class),
            'canDeletePost' => $this->stream->canDeletePost(Auth::user(), $post),
        ])->render();
    }

    private function renderComments(ClassPost $post, ClassSection $class): string
    {
        $post->load(['orderedComments.author', 'orderedComments.replies.author']);

        return view('classes.partials.comments', [
            'post' => $post,
            'class' => $class,
            'canManage' => $this->stream->canManage(Auth::user(), $class),
        ])->render();
    }
}
