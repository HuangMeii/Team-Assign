@extends('layouts.user')

@section('title', 'Danh sách đề tài')

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold mb-2">Danh sách đề tài</h2>
            <p class="text-muted">Tìm kiếm và đăng ký đề tài phù hợp cho nhóm của bạn</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('user.topics') }}">
                <div class="row g-3">
                    <!-- Search -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-search me-1"></i> Tìm kiếm
                        </label>
                        <input type="text" 
                               name="search" 
                               value="{{ request('search') }}" 
                               placeholder="Tên đề tài, giảng viên..."
                               class="form-control">
                    </div>

                    <!-- Class Filter -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-chalkboard me-1"></i> Lớp học
                        </label>
                        <select name="class_id" class="form-select">
                            <option value="">Tất cả lớp</option>
                            @foreach($userClasses as $class)
                                <option value="{{ $class->class_id }}" {{ request('class_id') == $class->class_id ? 'selected' : '' }}>
                                    {{ $class->class_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Subject Filter -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-book me-1"></i> Môn học
                        </label>
                        <select name="subject_id" class="form-select">
                            <option value="">Tất cả môn học</option>
                            @foreach($userClasses->pluck('subject')->unique('subject_id') as $subject)
                                @if($subject)
                                    <option value="{{ $subject->subject_id }}" {{ request('subject_id') == $subject->subject_id ? 'selected' : '' }}>
                                        {{ $subject->subject_name }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-filter me-1"></i> Trạng thái
                        </label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="status" id="status-all" value="" {{ !request('status') ? 'checked' : '' }}>
                            <label class="btn btn-outline-primary" for="status-all">
                                <i class="fas fa-list me-1"></i> Tất cả
                            </label>

                            <input type="radio" class="btn-check" name="status" id="status-available" value="available" {{ request('status') == 'available' ? 'checked' : '' }}>
                            <label class="btn btn-outline-success" for="status-available">
                                <i class="fas fa-check-circle me-1"></i> Còn trống
                            </label>

                            <input type="radio" class="btn-check" name="status" id="status-assigned" value="assigned" {{ request('status') == 'assigned' ? 'checked' : '' }}>
                            <label class="btn btn-outline-secondary" for="status-assigned">
                                <i class="fas fa-user-check me-1"></i> Đã có nhóm
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i> Tìm kiếm
                    </button>
                    <a href="{{ route('user.topics') }}" class="btn btn-secondary">
                        <i class="fas fa-redo me-2"></i> Đặt lại
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Topics List by Class -->
    @if($topics->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-lightbulb fa-4x text-muted opacity-50 mb-3"></i>
                <h5 class="text-muted mb-2">Không tìm thấy đề tài</h5>
                <p class="text-muted">Vui lòng thử lại với bộ lọc khác</p>
            </div>
        </div>
    @else
        @php
            // Group topics by class
            $topicsByClass = $topics->groupBy('class_id');
        @endphp

        @foreach($topicsByClass as $classId => $classTopics)
            @php
                $class = $classTopics->first()->class;
            @endphp
            
            <div class="mb-5">
                <!-- Class Header -->
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary bg-opacity-10 rounded p-3 me-3">
                        <i class="fas fa-chalkboard text-primary fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold">{{ $class->class_name ?? 'Chưa phân lớp' }}</h5>
                        <small class="text-muted">
                            {{ $classTopics->count() }} đề tài
                            @if($class && $class->subject)
                                - {{ $class->subject->subject_name }}
                            @endif
                        </small>
                    </div>
                    <div class="ms-auto">
                        <span class="badge bg-success rounded-pill">
                            {{ $classTopics->where('assigned_group_id', null)->count() }} còn trống
                        </span>
                        <span class="badge bg-secondary rounded-pill ms-2">
                            {{ $classTopics->whereNotNull('assigned_group_id')->count() }} đã có nhóm
                        </span>
                    </div>
                </div>

                <!-- Topics Grid -->
                <div class="row g-3">
                    @foreach($classTopics as $topic)
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm h-100 hover-card">
                                <div class="card-body p-4">
                                    <!-- Topic Header -->
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h6 class="fw-bold mb-0 flex-grow-1 me-2" 
                                            style="min-height: 48px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"
                                            title="{{ $topic->name }}">
                                            {{ $topic->name }}
                                        </h6>
                                        @if($topic->assignedGroup)
                                            <span class="badge bg-danger rounded-pill">Đã có nhóm</span>
                                        @else
                                            <span class="badge bg-success rounded-pill">Còn trống</span>
                                        @endif
                                    </div>

                                    <!-- Topic Info -->
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-user-tie text-primary me-2" style="width: 18px;"></i>
                                            <small class="text-muted">{{ $topic->lecturer }}</small>
                                        </div>
                                        
                                        @if($topic->subject)
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-book text-primary me-2" style="width: 18px;"></i>
                                                <small class="text-muted">{{ $topic->subject->subject_name }}</small>
                                            </div>
                                        @endif

                                        @if($topic->description)
                                            <div class="mt-2">
                                                <p class="text-muted small mb-0" 
                                                   style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                                    {{ $topic->description }}
                                                </p>
                                            </div>
                                        @endif

                                        <div class="d-flex align-items-center mt-2">
                                            <i class="fas fa-users text-primary me-2" style="width: 18px;"></i>
                                            <small class="text-muted">
                                                Số thành viên: {{ $topic->min_members ?? 1 }} – {{ $topic->max_members ?? 5 }}
                                            </small>
                                        </div>

                                        @if($topic->registration_deadline)
                                            <div class="d-flex align-items-center mt-1">
                                                <i class="fas fa-calendar-times {{ now()->greaterThan($topic->registration_deadline) ? 'text-danger' : 'text-warning' }} me-2" style="width: 18px;"></i>
                                                <small class="{{ now()->greaterThan($topic->registration_deadline) ? 'text-danger' : 'text-muted' }}">
                                                    Hạn đăng ký: {{ $topic->registration_deadline->format('d/m/Y H:i') }}
                                                    @if(now()->greaterThan($topic->registration_deadline))
                                                        (đã hết hạn)
                                                    @endif
                                                </small>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Assigned Group Info -->
                                    @if($topic->assignedGroup)
                                        <div class="alert alert-light border py-2 px-3 mb-3">
                                            <small class="text-muted d-flex align-items-center">
                                                <i class="fas fa-users me-2"></i>
                                                <span class="text-truncate">{{ $topic->assignedGroup->group_name }}</span>
                                            </small>
                                        </div>
                                    @endif

                                    <!-- Action Button -->
                                    <div class="d-grid">
                                        <a href="{{ route('user.topic_detail', $topic->topic_id) }}" 
                                           class="btn btn-primary">
                                            <i class="fas fa-eye me-2"></i>Chi tiết
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <!-- Pagination -->
        @if($topics->hasPages())
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex justify-content-center">
                        {{ $topics->links() }}
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>

<style>
    .hover-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(0, 0, 0, 0.08);
    }

    .hover-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12) !important;
        border-color: rgba(var(--bs-primary-rgb), 0.3);
    }

    .badge {
        font-weight: 500;
        font-size: 0.75rem;
        padding: 0.35em 0.65em;
    }

    .bg-opacity-10 {
        --bs-bg-opacity: 0.1;
    }

    .text-truncate {
        display: inline-block;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
</style>
@endsection