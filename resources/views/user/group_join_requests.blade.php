@extends('layouts.user')

@section('title', 'Yêu cầu tham gia nhóm')

@section('content')
<div class="container-fluid">
    <!-- Back Button -->
    <div class="mb-3">
        <a href="{{ route('user.group_detail', $group->group_id) }}" class="btn btn-link text-primary p-0">
            <i class="fas fa-arrow-left me-2"></i> Quay lại nhóm
        </a>
    </div>

    <!-- Header -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h2 class="mb-2 fw-bold">Yêu cầu tham gia nhóm</h2>
            <p class="text-muted mb-0">{{ $group->group_name }}</p>
        </div>
    </div>

    <!-- Requests List -->
    @if($requests->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                <h5 class="text-muted mb-2">Không có yêu cầu nào</h5>
                <p class="text-muted">Chưa có sinh viên nào gửi yêu cầu tham gia nhóm này</p>
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach($requests as $request)
                <div class="col-12">
                    <div class="card border-0 shadow-sm hover-card">
                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <!-- User Info -->
                                <div class="col-lg-8">
                                    <div class="d-flex align-items-start">
                                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold me-3 flex-shrink-0" 
                                             style="width: 64px; height: 64px; font-size: 1.5rem;">
                                            {{ strtoupper(substr($request->member->name ?? 'U', 0, 1)) }}
                                        </div>

                                        <div class="flex-grow-1">
                                            <h5 class="mb-2 fw-bold">{{ $request->member->name }}</h5>
                                            <p class="text-muted mb-3">{{ $request->member->email }}</p>

                                            <!-- User Classes -->
                                            @if($request->member->classes->isNotEmpty())
                                                <div class="d-flex flex-wrap gap-2 mb-3">
                                                    @foreach($request->member->classes as $class)
                                                        <span class="badge bg-primary bg-opacity-10 text-primary">
                                                            <i class="fas fa-chalkboard me-1"></i>
                                                            {{ $class->class_name }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif

                                            <!-- Request Time -->
                                            <div class="text-muted small">
                                                <i class="fas fa-calendar me-1"></i>
                                                Gửi lúc {{ $request->created_at->format('d/m/Y H:i') }}
                                                • {{ $request->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="col-lg-4 mt-3 mt-lg-0">
                                    <div class="d-grid gap-2">
                                        <form method="POST" action="{{ route('user.approve-join-request', $request->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-success w-100">
                                                <i class="fas fa-check me-2"></i>
                                                Chấp nhận
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('user.reject-join-request', $request->id) }}"
                                              onsubmit="return confirm('Bạn có chắc muốn từ chối yêu cầu này?');">
                                            @csrf
                                            <button type="submit" class="btn btn-danger w-100">
                                                <i class="fas fa-times me-2"></i>
                                                Từ chối
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($requests->hasPages())
            <div class="mt-4">
                <div class="d-flex justify-content-center">
                    {{ $requests->links() }}
                </div>
            </div>
        @endif
    @endif

    <!-- Current Members Section -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-users text-primary me-2"></i>
                Thành viên hiện tại
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <!-- Leader -->
                <div class="col-md-6">
                    <div class="card border border-primary bg-primary bg-opacity-10">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold me-3" 
                                         style="width: 40px; height: 40px;">
                                        {{ strtoupper(substr($group->leader->name ?? 'L', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="fw-semibold mb-0">{{ $group->leader->name }}</p>
                                        <p class="text-muted small mb-0">{{ $group->leader->email }}</p>
                                    </div>
                                </div>
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-crown me-1"></i> Trưởng nhóm
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Members -->
                @foreach($group->members as $member)
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white fw-bold me-3" 
                                             style="width: 40px; height: 40px;">
                                            {{ strtoupper(substr($member->name ?? 'M', 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="fw-semibold mb-0">{{ $member->name }}</p>
                                            <p class="text-muted small mb-0">{{ $member->email }}</p>
                                        </div>
                                    </div>
                                    <span class="badge bg-secondary">
                                        Thành viên
                                    </span>
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
    .hover-card {
        transition: all 0.3s ease;
    }
    .hover-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
</style>
@endsection