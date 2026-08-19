@extends('layouts.user')

@section('title', 'Chi tiết môn học')

@section('content')
<div class="container-fluid">
    <!-- Back Button -->
    <div class="mb-3">
        <a href="{{ route('user.subjects') }}" class="btn btn-link text-primary p-0">
            <i class="fas fa-arrow-left me-2"></i> Quay lại danh sách
        </a>
    </div>

    <!-- Subject Header -->
    <div class="card border-0 shadow-sm mb-4 text-white" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
        <div class="card-body p-4">
            <p class="mb-2 opacity-75">{{ $subject->subject_code }}</p>
            <h1 class="mb-4 fw-bold">{{ $subject->name }}</h1>
            <div class="d-flex flex-wrap gap-4">
                <span class="d-flex align-items-center">
                    <i class="fas fa-chalkboard me-2"></i>
                    {{ $subject->classes->count() }} lớp học
                </span>
                <span class="d-flex align-items-center">
                    <i class="fas fa-book me-2"></i>
                    {{ $subject->topics->count() }} đề tài
                </span>
            </div>
        </div>
    </div>

    <!-- Lecturer Info -->
    @if($subject->lecturer)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-chalkboard-teacher text-primary me-2"></i>
                    Giảng viên phụ trách
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="d-flex align-items-center p-4 rounded" style="background-color: #cfe2ff;">
                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold me-4" 
                         style="width: 64px; height: 64px; font-size: 1.5rem;">
                        {{ strtoupper(substr($subject->lecturer->name, 0, 1)) }}
                    </div>
                    <div>
                        <h5 class="mb-1 fw-bold">{{ $subject->lecturer->name }}</h5>
                        <p class="text-muted mb-0">{{ $subject->lecturer->email }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <!-- Classes -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-chalkboard text-primary me-2"></i>
                        Lớp học ({{ $subject->classes->count() }})
                    </h5>
                </div>
                <div class="card-body p-4">
                    @if($subject->classes->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-chalkboard fa-4x text-muted mb-3"></i>
                            <p class="text-muted">Chưa có lớp học nào</p>
                        </div>
                    @else
                        <div style="max-height: 600px; overflow-y: auto;">
                            @foreach($subject->classes as $class)
                                <div class="card border mb-3 hover-card">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h6 class="fw-bold mb-0">{{ $class->class_name }}</h6>
                                            <span class="badge bg-primary bg-opacity-10 text-primary">
                                                {{ $class->groups->count() }} nhóm
                                            </span>
                                        </div>

                                        @if($class->groups->isNotEmpty())
                                            <div class="mb-3">
                                                <p class="text-muted small mb-2">Một số nhóm:</p>
                                                @foreach($class->groups->take(2) as $group)
                                                    <p class="text-muted small mb-1">
                                                        <i class="fas fa-users text-primary me-1"></i>
                                                        {{ $group->group_name }}
                                                    </p>
                                                @endforeach
                                                @if($class->groups->count() > 2)
                                                    <p class="text-muted small mb-0">
                                                        và {{ $class->groups->count() - 2 }} nhóm khác...
                                                    </p>
                                                @endif
                                            </div>
                                        @endif

                                        <a href="{{ route('user.class_detail', $class->class_id) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            Xem chi tiết <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Topics -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-book text-success me-2"></i>
                        Đề tài ({{ $subject->topics->count() }})
                    </h5>
                </div>
                <div class="card-body p-4">
                    @if($subject->topics->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-book-open fa-4x text-muted mb-3"></i>
                            <p class="text-muted">Chưa có đề tài nào</p>
                        </div>
                    @else
                        <div style="max-height: 600px; overflow-y: auto;">
                            @foreach($subject->topics as $topic)
                                <div class="card border mb-3 hover-card">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="fw-bold mb-0 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                {{ $topic->name }}
                                            </h6>
                                            @if($topic->assignedGroup)
                                                <span class="badge bg-secondary ms-2">Đã có nhóm</span>
                                            @else
                                                <span class="badge bg-success ms-2">Còn trống</span>
                                            @endif
                                        </div>

                                        <p class="text-muted small mb-3" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                            {{ $topic->description }}
                                        </p>

                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted small">
                                                <i class="fas fa-chalkboard-teacher me-1"></i>
                                                {{ $topic->lecturer }}
                                            </span>
                                            <a href="{{ route('user.topic_detail', $topic->topic_id) }}" 
                                               class="btn btn-sm btn-link text-primary p-0">
                                                Chi tiết
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="pt-3 border-top">
                            <a href="{{ route('user.topics', ['subject_id' => $subject->subject_id]) }}" 
                               class="btn btn-success w-100">
                                <i class="fas fa-eye me-2"></i>
                                Xem tất cả đề tài
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-chart-bar text-warning me-2"></i>
                Thống kê tổng quan
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="text-center p-4 rounded" style="background-color: #e7e3fc;">
                        <i class="fas fa-chalkboard fa-3x text-primary mb-3"></i>
                        <p class="display-6 fw-bold text-primary mb-1">{{ $subject->classes->count() }}</p>
                        <p class="text-muted mb-0">Lớp học</p>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="text-center p-4 rounded" style="background-color: #d4edda;">
                        <i class="fas fa-book fa-3x text-success mb-3"></i>
                        <p class="display-6 fw-bold text-success mb-1">{{ $subject->topics->count() }}</p>
                        <p class="text-muted mb-0">Đề tài</p>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="text-center p-4 rounded" style="background-color: #cfe2ff;">
                        <i class="fas fa-users fa-3x text-info mb-3"></i>
                        <p class="display-6 fw-bold text-info mb-1">
                            {{ $subject->classes->sum(fn($class) => $class->groups->count()) }}
                        </p>
                        <p class="text-muted mb-0">Nhóm</p>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="text-center p-4 rounded" style="background-color: #fff3cd;">
                        <i class="fas fa-check-circle fa-3x text-warning mb-3"></i>
                        <p class="display-6 fw-bold text-warning mb-1">
                            {{ $subject->topics->filter(fn($topic) => $topic->assignedGroup != null)->count() }}
                        </p>
                        <p class="text-muted mb-0">Đề tài đã có nhóm</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-card {
        transition: all 0.3s ease;
    }
    .hover-card:hover {
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1) !important;
    }
</style>
@endsection