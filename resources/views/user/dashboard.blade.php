@extends('layouts.user')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">
 

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-2">Nhóm của tôi</p>
                            <h2 class="mb-0 fw-bold text-primary">{{ $myGroups->count() }}</h2>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="fas fa-users fa-2x text-primary"></i>
                        </div>
                    </div>
                    <a href="{{ route('user.my_groups') }}" class="btn btn-sm btn-link text-primary p-0 mt-3">
                        Xem tất cả <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-2">Đề tài</p>
                            <h2 class="mb-0 fw-bold text-success">{{ $myTopics->count() }}</h2>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="fas fa-book fa-2x text-success"></i>
                        </div>
                    </div>
                    <a href="{{ route('user.my_topics') }}" class="btn btn-sm btn-link text-success p-0 mt-3">
                        Xem đề tài <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-2">Lời mời</p>
                            <h2 class="mb-0 fw-bold text-warning">{{ $pendingInvites }}</h2>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded">
                            <i class="fas fa-envelope fa-2x text-warning"></i>
                        </div>
                    </div>
                    <a href="{{ route('user.invites') }}" class="btn btn-sm btn-link text-warning p-0 mt-3">
                        Xem lời mời <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-2">Yêu cầu</p>
                            <h2 class="mb-0 fw-bold text-info">{{ $pendingRequests }}</h2>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded">
                            <i class="fas fa-paper-plane fa-2x text-info"></i>
                        </div>
                    </div>
                    <a href="{{ route('user.join-requests') }}" class="btn btn-sm btn-link text-info p-0 mt-3">
                        Xem yêu cầu <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- My Groups Section -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-users text-primary me-2"></i>
                Nhóm của tôi
            </h5>
            <a href="{{ route('user.my_groups') }}" class="btn btn-sm btn-link text-primary">
                Xem tất cả
            </a>
        </div>
        <div class="card-body p-4">
            @if($myGroups->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-users fa-4x text-muted mb-3"></i>
                    <p class="text-muted mb-3">Bạn chưa tham gia nhóm nào</p>
                    <a href="{{ route('user.topics') }}" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>
                        Tìm đề tài
                    </a>
                </div>
            @else
                <div class="row g-3">
                    @foreach($myGroups->take(3) as $group)
                        <div class="col-md-6 col-lg-4">
                            <div class="card border h-100 hover-shadow">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h6 class="mb-0 fw-bold">{{ $group->group_name }}</h6>
                                        @if($group->leader_id == Auth::id())
                                            <span class="badge bg-primary">Trưởng nhóm</span>
                                        @endif
                                    </div>
                                    
                                    @if($group->topic)
                                        <div class="mb-2">
                                            <small class="text-muted d-block text-truncate" style="max-width: 100%;" title="{{ $group->topic->name }}">
                                                <i class="fas fa-book text-success me-1"></i>
                                                {{ Str::limit($group->topic->name, 40) }}
                                            </small>
                                        </div>
                                    @endif

                                    @if($group->class)
                                        <small class="text-muted d-block mb-2">
                                            <i class="fas fa-chalkboard text-info me-1"></i>
                                            {{ $group->class->class_name }}
                                        </small>
                                    @endif

                                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                        <small class="text-muted">
                                            <i class="fas fa-user-friends me-1"></i>
                                            {{ $group->members->count() + 1 }}/{{ $maxMembersByGroup[$group->group_id] ?? 5 }} thành viên
                                            @if(($group->members->count() + 1) >= ($maxMembersByGroup[$group->group_id] ?? 5))
                                                <span class="badge bg-success ms-1">Đủ</span>
                                            @else
                                                <span class="badge bg-warning text-dark ms-1">Thiếu</span>
                                            @endif
                                        </small>
                                        <a href="{{ route('user.group_detail', $group->group_id) }}" class="btn btn-sm btn-link text-primary p-0">
                                            Chi tiết <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- My Topics Section -->
    @if($myTopics->isNotEmpty())
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-book text-success me-2"></i>
                    Đề tài của tôi
                </h5>
                <a href="{{ route('user.my_topics') }}" class="btn btn-sm btn-link text-primary">
                    Xem tất cả
                </a>
            </div>
            <div class="card-body p-4">
                <div class="list-group list-group-flush">
                    @foreach($myTopics->take(3) as $topic)
                        <div class="list-group-item px-0 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-2 fw-bold">{{ $topic->name }}</h6>
                                    <p class="text-muted mb-2 small" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        {{ $topic->description }}
                                    </p>
                                    <div class="d-flex flex-wrap gap-3">
                                        <small class="text-muted">
                                            <i class="fas fa-chalkboard-teacher me-1"></i>
                                            {{ $topic->lecturer }}
                                        </small>
                                        @if($topic->subject)
                                            <small class="text-muted">
                                                <i class="fas fa-book me-1"></i>
                                                {{ $topic->subject->name }}
                                            </small>
                                        @endif
                                    </div>
                                </div>
                                <a href="{{ route('user.topic_detail', $topic->topic_id) }}" class="btn btn-sm btn-link text-primary ms-3">
                                    Chi tiết
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Suggested Topics -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-lightbulb text-warning me-2"></i>
                Đề tài gợi ý
            </h5>
            <a href="{{ route('user.topics') }}" class="btn btn-sm btn-link text-primary">
                Xem tất cả đề tài
            </a>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                @foreach($suggestedTopics as $topic)
                    <div class="col-md-6 col-lg-4">
                        <div class="card border h-100 hover-shadow">
                            <div class="card-body">
                                <h6 class="mb-2 fw-bold" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 48px;">
                                    {{ $topic->name }}
                                </h6>
                                <p class="text-muted small mb-3" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; min-height: 63px;">
                                    {{ $topic->description }}
                                </p>
                                
                                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                    @if($topic->assignedGroup)
                                        <span class="badge bg-secondary">Đã có nhóm</span>
                                    @else
                                        <span class="badge bg-success">Còn trống</span>
                                    @endif
                                    <a href="{{ route('user.topic_detail', $topic->topic_id) }}" class="btn btn-sm btn-link text-primary p-0">
                                        Xem chi tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<style>
    .hover-shadow {
        transition: all 0.3s ease;
    }
    .hover-shadow:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        transform: translateY(-2px);
    }
</style>
@endsection