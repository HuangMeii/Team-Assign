@extends('layouts.user')

@section('title', 'Đề tài của tôi')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="mb-4">
        <h2 class="fw-bold mb-2">Đề tài của tôi</h2>
        <p class="text-muted">Các đề tài mà nhóm của bạn đã đăng ký</p>
    </div>

    @if($topics->isEmpty())
        <!-- Empty State -->
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-book-open fa-4x text-muted mb-3"></i>
                <h5 class="text-muted mb-2">Chưa có đề tài nào</h5>
                <p class="text-muted mb-4">Các nhóm của bạn chưa đăng ký đề tài nào</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="{{ route('user.topics') }}" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>
                        Tìm đề tài
                    </a>
                    <a href="{{ route('user.my_groups') }}" class="btn btn-success">
                        <i class="fas fa-users me-2"></i>
                        Xem nhóm
                    </a>
                </div>
            </div>
        </div>
    @else
        <!-- Topics List -->
        <div class="row g-4">
            @foreach($topics as $topic)
                <div class="col-12">
                    <div class="card border-0 shadow-sm hover-card">
                        <!-- Topic Header -->
                        <div class="card-header text-white p-4" style="background: linear-gradient(135deg, #2b2ed8ff 0%, #263bdeff 100%);">
                            <h4 class="mb-2 fw-bold">{{ $topic->name }}</h4>
                            <div class="d-flex flex-wrap gap-3">
                                @if($topic->subject)
                                    <span class="d-flex align-items-center">
                                        <i class="fas fa-book me-2"></i>
                                        {{ $topic->subject->name }}
                                    </span>
                                @endif
                                <span class="d-flex align-items-center">
                                    <i class="fas fa-chalkboard-teacher me-2"></i>
                                    {{ $topic->lecturer }}
                                </span>
                            </div>
                        </div>

                        <!-- Topic Content -->
                        <div class="card-body p-4">
                            <div class="row g-4">
                                <!-- Left: Topic Details -->
                                <div class="col-lg-8">
                                    <!-- Description -->
                                    <div class="mb-4">
                                        <h6 class="fw-bold mb-3">
                                            <i class="fas fa-align-left text-primary me-2"></i>
                                            Mô tả
                                        </h6>
                                        <p class="text-muted">{{ $topic->description }}</p>
                                    </div>

                                    <!-- Goal -->
                                    @if(isset($topic->goal) && $topic->goal)
                                        <div class="mb-4">
                                            <h6 class="fw-bold mb-3">
                                                <i class="fas fa-bullseye text-success me-2"></i>
                                                Mục tiêu
                                            </h6>
                                            <p class="text-muted">{{ $topic->goal }}</p>
                                        </div>
                                    @endif

                                    <!-- Requirements -->
                                    @if(isset($topic->requirements) && $topic->requirements)
                                        <div class="mb-4">
                                            <h6 class="fw-bold mb-3">
                                                <i class="fas fa-clipboard-list text-info me-2"></i>
                                                Yêu cầu
                                            </h6>
                                            <p class="text-muted">{{ $topic->requirements }}</p>
                                        </div>
                                    @endif

                                    <a href="{{ route('user.topic_detail', $topic->topic_id) }}" 
                                       class="btn btn-primary">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Xem chi tiết đầy đủ
                                    </a>
                                </div>

                                <!-- Right: Group Info -->
                                <div class="col-lg-4">
                                    @if(isset($topic->group))
                                        <div class="card border border-primary">
                                            <div class="card-body p-4">
                                                <h6 class="fw-bold mb-4">
                                                    <i class="fas fa-users text-primary me-2"></i>
                                                    Thông tin nhóm
                                                </h6>

                                                <!-- Group Name -->
                                                <div class="mb-3">
                                                    <p class="text-muted small mb-1">Tên nhóm</p>
                                                    <p class="fw-semibold">{{ $topic->group->group_name }}</p>
                                                </div>

                                                <!-- Leader -->
                                                <div class="mb-3">
                                                    <p class="text-muted small mb-2">Trưởng nhóm</p>
                                                    <div class="d-flex align-items-center">
                                                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold me-3" 
                                                             style="width: 40px; height: 40px;">
                                                            {{ strtoupper(substr($topic->group->leader->name ?? 'L', 0, 1)) }}
                                                        </div>
                                                        <div>
                                                            <p class="fw-semibold mb-0 small">{{ $topic->group->leader->name }}</p>
                                                            <p class="text-muted mb-0" style="font-size: 0.75rem;">{{ $topic->group->leader->email }}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Members Count -->
                                                <div class="mb-3">
                                                    <p class="text-muted small mb-1">Số thành viên</p>
                                                    <p class="display-6 fw-bold text-primary mb-0">
                                                        {{ $topic->group->members->count() + 1 }}
                                                    </p>
                                                </div>

                                                <!-- Class -->
                                                @if($topic->group->class)
                                                    <div class="mb-4">
                                                        <p class="text-muted small mb-1">Lớp học</p>
                                                        <p class="fw-semibold">{{ $topic->group->class->class_name }}</p>
                                                    </div>
                                                @endif

                                                <!-- Action Button -->
                                                <a href="{{ route('user.group_detail', $topic->group->group_id) }}" 
                                                   class="btn btn-primary w-100">
                                                    <i class="fas fa-external-link-alt me-2"></i>
                                                    Xem nhóm
                                                </a>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<style>
    .hover-card {
        transition: all 0.3s ease;
    }
    .hover-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
</style>
@endsection