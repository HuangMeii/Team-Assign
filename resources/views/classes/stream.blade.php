@extends($layout)

@section('title', 'Bảng tin - ' . $class->class_name)

@section('content')
<div class="container-fluid px-4">

    <!-- Breadcrumb + tiêu đề lớp -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h3 class="fw-bold mb-1">
                <i class="fas fa-bullhorn text-primary me-2"></i>{{ $class->class_name }}
            </h3>
            <p class="text-muted mb-0">
                {{ $class->subject->subject_name ?? '' }}
                @if($class->class_code) · <span class="font-monospace">{{ $class->class_code }}</span> @endif
                · {{ $class->students()->count() }} sinh viên · {{ $class->groups()->count() }} nhóm
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ $isAdmin ? route('admin.classes.show', $class->class_id) : (($canManage) ? route('lecturer.classes.show', $class->class_id) : route('user.class_detail', $class->class_id)) }}"
               class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Về trang lớp
            </a>
            @if($isAdmin)
                <a href="{{ route('admin.classes.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-list me-1"></i>Danh sách lớp
                </a>
            @endif
        </div>
    </div>

    @if($canManage)
        <!-- Ô đăng thông báo (chỉ giảng viên phụ trách / admin) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">
                    <i class="fas fa-pen-to-square me-2"></i>Đăng thông báo cho lớp
                </h6>
                <form id="post-form">
                    <div class="mb-2">
                        <input type="text" class="form-control" name="title" maxlength="255"
                               placeholder="Tiêu đề (không bắt buộc)">
                    </div>
                    <div class="mb-2">
                        <textarea class="form-control" name="content" rows="3" maxlength="1000" required
                                  placeholder="Nội dung thông báo tới sinh viên trong lớp..."></textarea>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="pin-now" name="is_pinned" value="1">
                            <label class="form-check-label small" for="pin-now">
                                <i class="fas fa-thumbtack me-1"></i>Ghim lên đầu bảng tin
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i>Đăng thông báo
                        </button>
                    </div>
                </form>
                <div class="mt-2 d-none" id="post-form-status"></div>
            </div>
        </div>
    @endif

    <div class="d-flex align-items-center justify-content-between mb-2">
        <h6 class="fw-bold mb-0"><i class="fas fa-list-ul me-1"></i>Bảng tin lớp</h6>
        <small class="text-muted" id="stream-count">{{ $posts->total() }} bài viết</small>
    </div>

    <div id="stream-posts">
        @forelse($posts as $post)
            @include('classes.partials.post', ['post' => $post])
        @empty
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-bullhorn fa-3x text-muted opacity-50 mb-3"></i>
                    <h6 class="fw-bold mb-1">Lớp chưa có bài nào</h6>
                    <p class="text-muted mb-0">
                        Thông báo của giảng viên và hoạt động nhóm (thành lập, thêm/rời thành viên,
                        duyệt đề tài...) sẽ xuất hiện tại đây.
                    </p>
                </div>
            </div>
        @endforelse
    </div>

    @if($posts->hasPages())
        <div class="mt-4 d-flex justify-content-center">{{ $posts->links() }}</div>
    @endif
</div>

<!-- Thông báo realtime (Reverb) -->
<div class="position-fixed bottom-0 end-0 p-3 d-none" style="z-index: 1080;" id="stream-live">
    <button type="button" class="btn btn-primary shadow" id="stream-live-btn">
        <i class="fas fa-bell me-1"></i><span id="stream-live-text">Có hoạt động mới trong lớp</span>
    </button>
</div>

<style>
    .cs-avatar,
    .cs-avatar-sm {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        border-radius: 50%;
        font-weight: 600;
        flex: 0 0 auto;
    }

    .cs-avatar { width: 42px; height: 42px; font-size: 1rem; margin-right: .75rem; }
    .cs-avatar-sm { width: 32px; height: 32px; font-size: .8rem; }
    .cs-break { white-space: pre-wrap; word-break: break-word; }
    .class-post { scroll-margin-top: 90px; }
    .class-post.cs-pinned { border-left: 4px solid #ffc107 !important; }
</style>

@push('scripts')
<script>
(function () {
    const CLASS_ID = {{ (int) $class->class_id }};
    const CSRF = '{{ csrf_token() }}';

    const URLS = {
        store: @json(route('class.stream.store', $class->class_id)),
        pin: @json(route('class.stream.pin', [$class->class_id, '__POST__'])),
        destroy: @json(route('class.stream.destroy', [$class->class_id, '__POST__'])),
        comment: @json(route('class.stream.comment', [$class->class_id, '__POST__'])),
        comments: @json(route('class.stream.comments', [$class->class_id, '__POST__'])),
        deleteComment: @json(route('class.stream.comment.destroy', [$class->class_id, '__COMMENT__']))
    };

    const postsWrap = document.getElementById('stream-posts');
    const liveBox = document.getElementById('stream-live');
    const liveText = document.getElementById('stream-live-text');

    if (!postsWrap) {
        return;
    }

    function endpoint(template, id) {
        return String(template).replace('__POST__', id).replace('__COMMENT__', id);
    }

    async function api(url, method, body) {
        try {
            const response = await fetch(url, {
                method: method,
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body ? JSON.stringify(body) : null
            });

            return { ok: response.ok, status: response.status, data: await response.json().catch(() => null) };
        } catch (error) {
            return { ok: false, status: 0, data: null };
        }
    }

    function flash(message, type) {
        const box = document.getElementById('post-form-status');
        if (!box) {
            return;
        }
        box.className = 'mt-2 alert alert-' + type + ' mb-0';
        box.textContent = message;
        box.classList.remove('d-none');
        setTimeout(() => box.classList.add('d-none'), 5000);
    }

    /** Chèn bài mới đúng vị trí: ghim lên trên cùng, còn lại ngay sau khối ghim. */
    function insertPost(html) {
        const holder = document.createElement('div');
        holder.innerHTML = String(html).trim();
        const card = holder.firstElementChild;

        if (!card) {
            return;
        }

        const pinnedCards = postsWrap.querySelectorAll('.class-post.cs-pinned');
        const isPinned = card.classList.contains('cs-pinned');

        if (pinnedCards.length) {
            pinnedCards[pinnedCards.length - 1].after(card);
        } else {
            postsWrap.prepend(card);
        }

        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function replaceComments(postId, html) {
        const post = document.getElementById('post-' + postId);
        if (!post) {
            return;
        }
        const block = post.querySelector('[data-role="comments"]');
        if (block) {
            block.outerHTML = String(html).trim();
        }
    }

    // ---------------- Đăng thông báo ----------------
    const postForm = document.getElementById('post-form');

    if (postForm) {
        postForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            const content = postForm.querySelector('[name="content"]').value.trim();
            const title = postForm.querySelector('[name="title"]').value.trim();
            const isPinned = postForm.querySelector('[name="is_pinned"]').checked;

            if (content.length < 3) {
                flash('Nội dung thông báo cần ít nhất 3 ký tự.', 'warning');
                return;
            }

            const button = postForm.querySelector('button[type="submit"]');
            const original = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang đăng...';

            const result = await api(URLS.store, 'POST', { content: content, title: title, is_pinned: isPinned ? 1 : 0 });

            button.disabled = false;
            button.innerHTML = original;

            if (!result.ok || !result.data || !result.data.ok) {
                flash((result.data && result.data.message) || 'Không đăng được thông báo (HTTP ' + result.status + ').', 'danger');
                return;
            }

            insertPost(result.data.html);
            postForm.reset();
            flash(result.data.message || 'Đã đăng thông báo.', 'success');
        });
    }

    // ---------------- Ghim / Xoá bài, Trả lời, Xoá bình luận ----------------
    document.addEventListener('click', async function (event) {
        const pinBtn = event.target.closest('[data-role="pin"]');

        if (pinBtn) {
            const postId = pinBtn.getAttribute('data-post-id');
            const result = await api(endpoint(URLS.pin, postId), 'PATCH');

            if (!result.ok || !result.data || !result.data.ok) {
                flash('Không đổi được trạng thái ghim.', 'danger');
                return;
            }

            // Ghim/bỏ ghim đổi thứ tự cả bảng tin ⇒ tải lại cho chắc chắn đúng vị trí.
            location.reload();
            return;
        }

        const deletePostBtn = event.target.closest('[data-role="delete-post"]');

        if (deletePostBtn) {
            if (! confirm('Bạn chắc chắn muốn xoá bài viết này?')) {
                return;
            }

            const postId = deletePostBtn.getAttribute('data-post-id');
            const result = await api(endpoint(URLS.destroy, postId), 'DELETE');

            if (!result.ok || !result.data || !result.data.ok) {
                flash('Không xoá được bài viết.', 'danger');
                return;
            }

            const card = document.getElementById('post-' + postId);
            if (card) {
                card.remove();
            }
            return;
        }

        const replyBtn = event.target.closest('[data-role="reply"]');

        if (replyBtn) {
            const post = replyBtn.closest('.class-post');
            const form = post ? post.querySelector('[data-role="comment-form"]') : null;

            if (form) {
                form.querySelector('[name="parent_id"]').value = replyBtn.getAttribute('data-comment-id');

                const hint = post.querySelector('[data-role="reply-hint"]');
                if (hint) {
                    hint.querySelector('[data-role="reply-name"]').textContent = replyBtn.getAttribute('data-author') || '';
                    hint.classList.remove('d-none');
                }

                form.querySelector('[name="content"]').focus();
            }
            return;
        }

        if (event.target.closest('[data-role="cancel-reply"]')) {
            const post = event.target.closest('.class-post');
            if (post) {
                post.querySelector('[data-role="comment-form"] [name="parent_id"]').value = '';

                const hint = post.querySelector('[data-role="reply-hint"]');
                if (hint) {
                    hint.classList.add('d-none');
                }
            }
            return;
        }

        const deleteCommentBtn = event.target.closest('[data-role="delete-comment"]');

        if (deleteCommentBtn) {
            if (! confirm('Xoá bình luận này?')) {
                return;
            }

            const commentId = deleteCommentBtn.getAttribute('data-comment-id');
            const result = await api(endpoint(URLS.deleteComment, commentId), 'DELETE');

            if (!result.ok || !result.data || !result.data.ok) {
                flash('Không xoá được bình luận.', 'danger');
                return;
            }

            const post = deleteCommentBtn.closest('.class-post');
            if (post && result.data.html) {
                replaceComments(post.getAttribute('data-post-id'), result.data.html);
            }
        }
    });

    // ---------------- Gửi bình luận ----------------
    document.addEventListener('submit', async function (event) {
        const form = event.target.closest('[data-role="comment-form"]');

        if (! form) {
            return;
        }

        event.preventDefault();

        const postId = form.getAttribute('data-post-id');
        const content = form.querySelector('[name="content"]').value.trim();
        const parentId = form.querySelector('[name="parent_id"]').value || null;

        if (content === '') {
            return;
        }

        const result = await api(endpoint(URLS.comment, postId), 'POST', { content: content, parent_id: parentId });

        if (!result.ok || !result.data || !result.data.ok) {
            flash((result.data && result.data.message) || 'Không gửi được bình luận.', 'danger');
            return;
        }

        replaceComments(postId, result.data.html);
    });

    // ---------------- Realtime (Reverb) ----------------
    const liveBtn = document.getElementById('stream-live-btn');
    if (liveBtn) {
        liveBtn.addEventListener('click', () => location.reload());
    }

    if (window.Echo) {
        window.Echo.private('class.' + CLASS_ID)
            .listen('.class-post', function (event) {
                const post = event.post || {};

                if (Number(post.class_id) !== CLASS_ID) {
                    return;
                }

                // Bài mới: hiện banner mời cập nhật (tránh chèn trùng với bài của chính mình).
                if (liveBox) {
                    liveText.textContent = 'Có hoạt động mới trong lớp';
                    liveBox.classList.remove('d-none');
                }
            })
            .listen('.class-comment', async function (event) {
                const comment = event.comment || {};

                if (! comment.post_id) {
                    return;
                }

                const result = await api(endpoint(URLS.comments, comment.post_id), 'GET');

                if (result.ok && result.data && result.data.html) {
                    replaceComments(comment.post_id, result.data.html);
                }
            });
    }
})();
</script>
@endpush
@endsection
