@extends('layouts.user')

@section('title', 'Chi tiết lớp học')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('user.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('user.classes') }}">Lớp học</a></li>
            <li class="breadcrumb-item active">{{ $class->class_name }}</li>
        </ol>
    </nav>

    <!-- Class Header -->
    <div class="card border-0 shadow-sm mb-4 text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="card-body p-4">
            <h1 class="mb-0 fw-bold">{{ $class->class_name }}</h1>
        </div>
    </div>

    <!-- Class Info -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-6">
                    <p class="text-muted mb-1">Môn học</p>
                    <p class="h5 fw-bold">{{ $class->subject->subject_name ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                    <p class="text-muted mb-1">Giảng viên</p>
                    <p class="h5 fw-bold">{{ $class->subject->lecturer->name ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                    <p class="text-muted mb-1">Số nhóm</p>
                    <p class="h5 fw-bold">{{ $class->groups->count() }} nhóm</p>
                </div>
                <div class="col-md-6">
                    <p class="text-muted mb-1">Tổng đề tài</p>
                    <p class="h5 fw-bold">{{ $class->subject->topics->count() }} đề tài</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#topics">
                <i class="fas fa-lightbulb me-2"></i>Đề tài ({{ $class->subject->topics->count() }})
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#groups">
                <i class="fas fa-users me-2"></i>Nhóm ({{ $class->groups->count() }})
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Topics Tab -->
        <div class="tab-pane fade show active" id="topics">
            @if($class->subject->topics->isEmpty())
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-lightbulb fa-4x text-muted mb-3"></i>
                        <p class="text-muted">Lớp này chưa có đề tài nào</p>
                    </div>
                </div>
            @else
                <div class="row g-4">
                    @foreach($class->subject->topics as $topic)
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h6 class="fw-bold mb-0" style="min-height: 48px;">{{ Str::limit($topic->name, 50) }}</h6>
                                        @if($topic->assignedGroup)
                                            <span class="badge bg-danger ms-2">Đã có nhóm</span>
                                        @else
                                            <span class="badge bg-success ms-2">Còn trống</span>
                                        @endif
                                    </div>

                                    <p class="text-muted small mb-2">
                                        <i class="fas fa-user-tie me-1"></i>
                                        {{ $topic->lecturer }}
                                    </p>

                                    @if($topic->description)
                                        <p class="text-muted small" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                            {{ $topic->description }}
                                        </p>
                                    @endif

                                    @if($topic->assignedGroup)
                                        <div class="alert alert-light border py-2 mt-3">
                                            <small class="text-muted">
                                                <i class="fas fa-users me-1"></i>
                                                Nhóm: {{ $topic->assignedGroup->group_name }}
                                            </small>
                                        </div>
                                    @endif

                                    <a href="{{ route('user.topic_detail', $topic->topic_id) }}" class="btn btn-outline-primary btn-sm w-100 mt-3">
                                        Xem chi tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Groups Tab -->
        <div class="tab-pane fade" id="groups">
            @if($class->groups->isEmpty())
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-users fa-4x text-muted mb-3"></i>
                        <p class="text-muted">Lớp này chưa có nhóm nào</p>
                    </div>
                </div>
            @else
                <div class="row g-4">
                    @foreach($class->groups as $group)
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-4">
                                    <h6 class="fw-bold mb-3">{{ $group->group_name }}</h6>

                                    <p class="text-muted small mb-2">
                                        <i class="fas fa-user-tie me-1"></i>
                                        <strong>Trưởng nhóm:</strong> {{ $group->leader->name }}
                                    </p>

                                    <p class="text-muted small mb-2">
                                        <i class="fas fa-users me-1"></i>
                                        <strong>Thành viên:</strong> {{ $group->members->count() + 1 }} người
                                    </p>

                                    @if($group->topic)
                                        <p class="text-muted small mb-3">
                                            <i class="fas fa-lightbulb me-1"></i>
                                            <strong>Đề tài:</strong> {{ Str::limit($group->topic->name, 40) }}
                                        </p>
                                    @else
                                        <p class="text-danger small mb-3">
                                            <i class="fas fa-exclamation-circle me-1"></i>
                                            Chưa có đề tài
                                        </p>
                                    @endif

                                    <a href="{{ route('user.group_detail', $group->group_id) }}" class="btn btn-outline-primary btn-sm w-100">
                                        Xem chi tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection