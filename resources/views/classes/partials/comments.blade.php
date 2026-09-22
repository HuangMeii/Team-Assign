<div class="mt-3 pt-3 border-top" data-role="comments" data-post-id="{{ $post->post_id }}">
    <div data-role="comments-list">
        @forelse($post->orderedComments as $comment)
            @include('classes.partials.comment', ['comment' => $comment])
        @empty
            <p class="text-muted small mb-2" data-role="no-comments">
                <i class="fas fa-comment-dots me-1"></i>Chưa có bình luận nào.
            </p>
        @endforelse
    </div>

    <form class="d-flex gap-2 mt-2" data-role="comment-form" data-post-id="{{ $post->post_id }}">
        <input type="hidden" name="parent_id" value="">
        <input type="text" class="form-control form-control-sm" name="content" maxlength="500"
               placeholder="Viết bình luận..." autocomplete="off" required>
        <button class="btn btn-sm btn-primary text-nowrap" type="submit">
            <i class="fas fa-paper-plane"></i>
        </button>
    </form>

    <div class="small text-primary mt-1 d-none" data-role="reply-hint">
        <i class="fas fa-reply me-1"></i>Đang trả lời <span data-role="reply-name"></span>
        <button type="button" class="btn btn-link btn-sm p-0 ms-1 text-decoration-none" data-role="cancel-reply">Hủy</button>
    </div>
</div>
