@props([
    'classId' => null,
    'limit' => 3,
    'title' => 'Bảng tin lớp',
])

@php
    // Thẻ tóm tắt bảng tin dùng lại được ở trang lớp của sinh viên/giảng viên/admin.
    // Tự kiểm tra quyền: chỉ render khi người xem thuộc lớp (SV/GV phụ trách/admin).
    $stream = app(\App\Services\ClassStreamService::class);
    $classModel = $classId ? \App\Models\ClassSection::find($classId) : null;
    $canView = $classModel && $stream->canView(auth()->user(), $classModel);
    $latest = $canView ? $stream->preview($classModel, (int) $limit) : collect();
@endphp

@if($canView)
<div class="card border-0 shadow-sm mb-4" id="class-stream-preview">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-bullhorn text-primary me-2"></i>{{ $title }}
            </h5>
            <a href="{{ route('class.stream', $classModel->class_id) }}" class="btn btn-sm btn-outline-primary">
                Xem tất cả <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>

        @if($latest->isEmpty())
            <p class="text-muted mb-0">
                <i class="fas fa-circle-info me-1"></i>
                Chưa có thông báo hay hoạt động nhóm nào trong lớp này.
            </p>
        @else
            <div class="list-group list-group-flush">
                @foreach($latest as $post)
                    <div class="list-group-item px-0 py-2 border-0 border-bottom">
                        <div class="d-flex align-items-start">
                            <span class="badge bg-{{ $post->color }}-subtle text-{{ $post->color }} border border-{{ $post->color }}-subtle me-3 mt-1">
                                <i class="fas {{ $post->icon }}"></i>
                            </span>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="small">
                                        @if($post->is_pinned)<i class="fas fa-thumbtack text-warning me-1"></i>@endif
                                        {{ $post->label }}
                                    </strong>
                                    <small class="text-muted">{{ $post->created_at?->diffForHumans() }}</small>
                                </div>
                                <div class="small text-muted text-truncate" style="max-width: 100%;">
                                    {{ \Illuminate\Support\Str::limit($post->content, 110) }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endif
