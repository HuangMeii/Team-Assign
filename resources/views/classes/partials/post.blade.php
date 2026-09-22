@php
    $canDeletePost = $canDeletePost ?? app(\App\Services\ClassStreamService::class)->canDeletePost(auth()->user(), $post);
    $author = $post->author;
    $initial = mb_strtoupper(mb_substr($author->name ?? 'H', 0, 1));
    $isLecturer = ! $post->is_system && (($author->role ?? null) === 'lecturer');
    $isStudent = (auth()->user()->role ?? null) === 'student';
@endphp

<div class="card border-0 shadow-sm mb-3 class-post {{ $post->is_pinned ? 'cs-pinned' : '' }}"
     id="post-{{ $post->post_id }}" data-post-id="{{ $post->post_id }}">
    <div class="card-body p-4">

        @if($post->is_system)
            {{-- ===== Bài HỆ THỐNG: hoạt động nhóm ===== --}}
            <div class="d-flex align-items-start">
                <span class="cs-avatar-sm bg-{{ $post->color }} text-white me-3">
                    <i class="fas {{ $post->icon }}"></i>
                </span>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <strong class="small">{{ $post->label }}</strong>
                            @if($post->group_id)
                                @if($isStudent)
                                    <a href="{{ route('user.group_detail', $post->group_id) }}"
                                       class="badge bg-light text-dark text-decoration-none ms-1">
                                        {{ $post->meta['group_name'] ?? ($post->group->group_name ?? '') }}
                                    </a>
                                @else
                                    <span class="badge bg-light text-dark ms-1">
                                        {{ $post->meta['group_name'] ?? ($post->group->group_name ?? '') }}
                                    </span>
                                @endif
                            @endif
                            @if($post->is_pinned)
                                <span class="badge bg-warning-subtle text-warning ms-1">
                                    <i class="fas fa-thumbtack me-1"></i>Đã ghim
                                </span>
                            @endif
                        </div>
                        <small class="text-muted text-nowrap">{{ $post->created_at?->diffForHumans() }}</small>
                    </div>

                    <div class="small text-muted mt-1 cs-break">{{ $post->content }}</div>

                    @if($canManage)
                        <div class="d-flex gap-3 mt-2">
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none small"
                                    data-role="pin" data-post-id="{{ $post->post_id }}" data-pinned="{{ $post->is_pinned ? 1 : 0 }}">
                                <i class="fas fa-thumbtack me-1"></i>{{ $post->is_pinned ? 'Bỏ ghim' : 'Ghim' }}
                            </button>
                            @if($canDeletePost)
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none small text-danger"
                                        data-role="delete-post" data-post-id="{{ $post->post_id }}">
                                    <i class="fas fa-trash me-1"></i>Xóa
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @else
            {{-- ===== THÔNG BÁO của giảng viên ===== --}}
            <div class="d-flex align-items-start">
                <div class="cs-avatar {{ $isLecturer ? 'bg-primary' : 'bg-secondary' }}">{{ $initial }}</div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="small">
                            <strong>{{ $author->name ?? 'Người dùng' }}</strong>
                            @if($isLecturer)
                                <span class="badge bg-primary-subtle text-primary ms-1">Giảng viên</span>
                            @endif
                            <div class="text-muted">{{ $post->created_at?->diffForHumans() }}</div>
                        </div>
                        @if($post->is_pinned)
                            <span class="badge bg-warning-subtle text-warning">
                                <i class="fas fa-thumbtack me-1"></i>Đã ghim
                            </span>
                        @endif
                    </div>

                    @if($post->title)
                        <h5 class="fw-bold mt-2 mb-1">{{ $post->title }}</h5>
                    @endif
                    <div class="cs-break mt-1">{{ $post->content }}</div>

                    @if($canManage || $canDeletePost)
                        <div class="d-flex gap-3 mt-2">
                            @if($canManage)
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none small"
                                        data-role="pin" data-post-id="{{ $post->post_id }}" data-pinned="{{ $post->is_pinned ? 1 : 0 }}">
                                    <i class="fas fa-thumbtack me-1"></i>{{ $post->is_pinned ? 'Bỏ ghim' : 'Ghim' }}
                                </button>
                            @endif
                            @if($canDeletePost)
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none small text-danger"
                                        data-role="delete-post" data-post-id="{{ $post->post_id }}">
                                    <i class="fas fa-trash me-1"></i>Xóa
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @include('classes.partials.comments')
    </div>
</div>

