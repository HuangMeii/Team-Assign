@extends('layouts.user')

@section('title', 'Lời mời')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="mb-4">
        <h2 class="fw-bold mb-2">Lời mời tham gia nhóm</h2>
        <p class="text-muted">Các lời mời từ trưởng nhóm</p>
    </div>

    <!-- Filter Tabs -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-0">
            <ul class="nav nav-tabs border-0" role="tablist">
                <li class="nav-item">
                    <a class="nav-link {{ !request('status') ? 'active' : '' }}" href="{{ route('user.invites') }}">
                        <i class="fas fa-inbox me-2"></i>
                        Tất cả
                        @if($invites->total() > 0)
                            <span class="badge bg-primary ms-2">{{ $invites->total() }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request('status') == 'Pending' ? 'active' : '' }}" href="{{ route('user.invites', ['status' => 'Pending']) }}">
                        <i class="fas fa-clock me-2"></i>
                        Chờ xử lý
                        @php
                            $pendingCount = $invites->where('status', 'Pending')->count();
                        @endphp
                        @if($pendingCount > 0)
                            <span class="badge bg-warning ms-2">{{ $pendingCount }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request('status') == 'Accepted' ? 'active' : '' }}" href="{{ route('user.invites', ['status' => 'Accepted']) }}">
                        <i class="fas fa-check-circle me-2"></i>
                        Đã chấp nhận
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request('status') == 'Rejected' ? 'active' : '' }}" href="{{ route('user.invites', ['status' => 'Rejected']) }}">
                        <i class="fas fa-times-circle me-2"></i>
                        Đã từ chối
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Invites List -->
    @if($invites->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-envelope-open fa-4x text-muted mb-3"></i>
                <h5 class="text-muted mb-2">Không có lời mời nào</h5>
                <p class="text-muted">Bạn chưa nhận được lời mời tham gia nhóm nào</p>
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach($invites as $invite)
                <div class="col-12">
                    <div class="card border-0 shadow-sm hover-card">
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-lg-8">
                                    <!-- Group Name -->
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <h5 class="mb-0 fw-bold">{{ $invite->group->group_name }}</h5>
                                        @if($invite->status == 'Pending')
                                            <span class="badge bg-warning">
                                                <i class="fas fa-clock me-1"></i> Chờ xử lý
                                            </span>
                                        @elseif($invite->status == 'Accepted')
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i> Đã chấp nhận
                                            </span>
                                        @elseif($invite->status == 'Rejected')
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times-circle me-1"></i> Đã từ chối
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Group Info -->
                                    <div class="row g-3 mb-3">
                                        <!-- Topic -->
                                        @if($invite->group->topic)
                                            <div class="col-md-4">
                                                <div class="p-3 rounded" style="background-color: #d4edda;">
                                                    <p class="text-muted small mb-1">Đề tài</p>
                                                    <p class="fw-semibold small mb-0" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                        {{ $invite->group->topic->name }}
                                                    </p>
                                                </div>
                                            </div>
                                        @endif

                                        <!-- Class -->
                                        @if($invite->group->class)
                                            <div class="col-md-4">
                                                <div class="p-3 rounded" style="background-color: #e7e3fc;">
                                                    <p class="text-muted small mb-1">Lớp học</p>
                                                    <p class="fw-semibold small mb-0">
                                                        {{ $invite->group->class->class_name }}
                                                    </p>
                                                </div>
                                            </div>
                                        @endif

                                        <!-- Members -->
                                        <div class="col-md-4">
                                            <div class="p-3 rounded" style="background-color: #cfe2ff;">
                                                <p class="text-muted small mb-1">Thành viên</p>
                                                <p class="fw-semibold small mb-0">
                                                    {{ $invite->group->members->count() + 1 }} người
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Invited By -->
                                    <div class="text-muted small">
                                        <i class="fas fa-user-circle me-1"></i>
                                        Được mời bởi <span class="fw-semibold">{{ $invite->invitedBy->name ?? 'N/A' }}</span>
                                        • {{ $invite->created_at->diffForHumans() }}
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="col-lg-4 mt-3 mt-lg-0">
                                    <div class="d-grid gap-2">
                                        @if($invite->status == 'Pending')
                                            <form method="POST" action="{{ route('user.accept-invite', $invite->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-success w-100">
                                                    <i class="fas fa-check me-2"></i>
                                                    Chấp nhận
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('user.reject-invite', $invite->id) }}"
                                                  onsubmit="return confirm('Bạn có chắc muốn từ chối lời mời này?');">
                                                @csrf
                                                <button type="submit" class="btn btn-danger w-100">
                                                    <i class="fas fa-times me-2"></i>
                                                    Từ chối
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('user.group_detail', $invite->group->group_id) }}" 
                                               class="btn btn-primary w-100">
                                                <i class="fas fa-eye me-2"></i>
                                                Xem nhóm
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($invites->hasPages())
            <div class="mt-4">
                <div class="d-flex justify-content-center">
                    {{ $invites->links() }}
                </div>
            </div>
        @endif
    @endif
</div>

<style>
    .hover-card {
        transition: all 0.3s ease;
    }
    .hover-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    .nav-tabs .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        color: #6c757d;
        padding: 1rem 1.5rem;
    }
    .nav-tabs .nav-link:hover {
        border-bottom-color: #dee2e6;
        color: #495057;
    }
    .nav-tabs .nav-link.active {
        border-bottom-color: #0d6efd;
        color: #0d6efd;
        background: none;
    }
</style>
@endsection