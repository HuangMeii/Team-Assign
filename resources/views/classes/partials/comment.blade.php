@php
    $author = $comment->author;
    $initial = mb_strtoupper(mb_substr($author->name ?? '?', 0, 1));
    $canDelete = app(\App\Services\ClassStreamService::class)->canDeleteComment(auth()->user(), $comment);
    $isLecturer = ($author->role ?? null) === 'lecturer';
@endphp

<div class="d-flex mb-2" data-comment-id="{{ $comment->comment_id }}">
    <div class="cs-avatar-sm {{ $isLecturer ? 'bg-primary' : 'bg-secondary' }}">{{ $initial }}</div>

    <div class="flex-grow-1">
        <div class="bg-light rounded px-3 py-2">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <strong class="small">
                    {{ $author->name ?? 'Người dùng' }}
                    @if($isLecturer)
                        <span class="badge bg-primary-subtle text-primary ms-1">Giảng viên</span>
                    @endif
                </strong>
                <small class="text-muted text-nowrap">{{ $comment->created_at?->diffForHumans() }}</small>
            </div>
            <div class="small cs-break">{{ $comment->content }}</div>
        </div>

        <div class="d-flex gap-3 mt-1">
            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none small" data-role="reply"
                    data-comment-id="{{ $comment->comment_id }}" data-author="{{ $author->name ?? '' }}">
                <i class="fas fa-reply me-1"></i>Trả lời
            </button>
            @if($canDelete)
                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none small text-danger" data-role="delete-comment"
                        data-comment-id="{{ $comment->comment_id }}">
                    <i class="fas fa-trash me-1"></i>Xóa
                </button>
            @endif
        </div>

        @if($comment->replies && $comment->replies->isNotEmpty())
            <div class="mt-2 ms-4 border-start ps-3">
                @foreach($comment->replies as $reply)
                    @include('classes.partials.comment', ['comment' => $reply])
                @endforeach
            </div>
        @endif
    </div>
</div>
