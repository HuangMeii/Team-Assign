@extends('layouts.user')

@section('title', 'Tìm đề tài cho nhóm')

@section('content')
<div class="container-fluid px-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('user.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('user.my_groups') }}">Nhóm của tôi</a></li>
            <li class="breadcrumb-item"><a href="{{ route('user.group_detail', $group->group_id) }}">{{ $group->group_name }}</a></li>
            <li class="breadcrumb-item active">Tìm đề tài</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h2 class="fw-bold text-white mb-2">Tìm đề tài cho nhóm</h2>
                    <p class="text-white mb-3 opacity-90">
                        <i class="fas fa-users me-2"></i>
                        {{ $group->group_name }}
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        @if($group->class)
                            <span class="badge bg-white bg-opacity-25 text-white px-3 py-2">
                                <i class="fas fa-chalkboard me-1"></i>
                                {{ $group->class->class_name }}
                            </span>
                        @endif
                        @if($group->class && $group->class->subject)
                            <span class="badge bg-white bg-opacity-25 text-white px-3 py-2">
                                <i class="fas fa-book me-1"></i>
                                {{ $group->class->subject->subject_name }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <a href="{{ route('user.group_detail', $group->group_id) }}" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>
                        Quay lại
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('user.group_topics', $group->group_id) }}">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" 
                                   name="search" 
                                   class="form-control border-start-0" 
                                   placeholder="Tìm kiếm đề tài..." 
                                   value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">Tất cả trạng thái</option>
                            <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>
                                Còn trống
                            </option>
                            <option value="assigned" {{ request('status') == 'assigned' ? 'selected' : '' }}>
                                Đã có nhóm
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-filter me-2"></i>
                                Lọc
                            </button>
                            @if(request()->hasAny(['search', 'status']))
                                <a href="{{ route('user.group_topics', $group->group_id) }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Topics List -->
    @if($topics->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-book-open fa-4x text-muted opacity-50 mb-4"></i>
                <h5 class="fw-bold mb-2">Không tìm thấy đề tài</h5>
                <p class="text-muted mb-0">
                    @if(request()->hasAny(['search', 'status']))
                        Thử điều chỉnh bộ lọc để xem thêm đề tài
                    @else
                        Chưa có đề tài nào trong lớp {{ $group->class->class_name ?? 'này' }}
                    @endif
                </p>
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach($topics as $topic)
                <div class="col-12">
                    <div class="card border-0 shadow-sm hover-card h-100">
                        <div class="card-body p-4">
                            <div class="row">
                                <!-- Topic Info -->
                                <div class="col-lg-8">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h5 class="fw-bold mb-0 flex-grow-1 me-3">{{ $topic->name }}</h5>
                                        @if($topic->assignedGroup)
                                            <span class="badge bg-danger">Đã có nhóm</span>
                                        @else
                                            <span class="badge bg-success">Còn trống</span>
                                        @endif
                                    </div>

                                    @if($topic->description)
                                        <p class="text-muted mb-3" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                            {{ $topic->description }}
                                        </p>
                                    @endif

                                    <div class="d-flex flex-wrap gap-3 mb-3">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user-tie text-primary me-2"></i>
                                            <small class="text-muted">{{ $topic->lecturer }}</small>
                                        </div>
                                        @if($topic->subject)
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-book text-primary me-2"></i>
                                                <small class="text-muted">{{ $topic->subject->subject_name }}</small>
                                            </div>
                                        @endif
                                    </div>

                                    @php
                                        $pendingCount = $topic->topic_requests->where('status', 'Pending')->count();
                                    @endphp
                                    @if($pendingCount > 0)
                                        <div class="alert alert-warning border-0 py-2 px-3 mb-0">
                                            <small>
                                                <i class="fas fa-clock me-1"></i>
                                                {{ $pendingCount }} nhóm đang chờ duyệt
                                            </small>
                                        </div>
                                    @endif
                                </div>

                                <!-- Actions -->
                                <div class="col-lg-4 d-flex flex-column justify-content-center mt-3 mt-lg-0">
                                    @php
                                        $hasRegistered = in_array($topic->topic_id, $groupsRegistered);
                                        $groupHasTopic = $group->topic_id != null;
                                        $isLeader = $group->leader_id == Auth::id();
                                    @endphp

                                    @if($groupHasTopic)
                                        <button class="btn btn-secondary mb-2" disabled>
                                            <i class="fas fa-ban me-2"></i>
                                            Đã có đề tài
                                        </button>

                                    @elseif($topic->assignedGroup)
                                        <button class="btn btn-danger mb-2" disabled>
                                            <i class="fas fa-lock me-2"></i>
                                            Đã được đăng ký
                                        </button>

                                    @elseif(!$isLeader)
                                        <button class="btn btn-secondary mb-2" disabled>
                                            <i class="fas fa-user-lock me-2"></i>
                                            Chỉ trưởng nhóm
                                        </button>

                                    @elseif($hasRegistered)
                                        <button class="btn btn-warning mb-2" disabled>
                                            <i class="fas fa-hourglass-half me-2"></i>
                                            Đang chờ duyệt
                                        </button>

                                    @else
                                        <form action="{{ route('user.register_topic') }}" method="POST" class="mb-2">
                                            @csrf
                                            <input type="hidden" name="topic_id" value="{{ $topic->topic_id }}">
                                            <input type="hidden" name="group_id" value="{{ $group->group_id }}">
                                            <button type="submit" class="btn btn-success w-100">
                                                <i class="fas fa-paper-plane me-2"></i>
                                                Đăng ký ngay
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('user.topic_detail', $topic->topic_id) }}" class="btn btn-outline-primary">
                                        <i class="fas fa-eye me-2"></i>
                                        Chi tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($topics->hasPages())
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex justify-content-center">
                        {{ $topics->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>

<style>
    .hover-card {
        transition: all 0.3s ease;
        border: 1px solid rgba(0, 0, 0, 0.08);
    }

    .hover-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1) !important;
        border-color: rgba(102, 126, 234, 0.3);
    }

    .badge {
        font-weight: 500;
        padding: 0.5em 0.8em;
    }

    .alert {
        font-size: 0.875rem;
    }

    .input-group-text {
        border-right: 0;
    }

    .form-control.border-start-0 {
        border-left: 0;
    }

    .form-control.border-start-0:focus {
        border-left: 0;
    }
</style>
@endsection