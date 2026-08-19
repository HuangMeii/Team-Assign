@extends('layouts.user')

@section('title', 'Danh sách môn học')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="mb-4">
        <h2 class="fw-bold mb-2">Danh sách môn học</h2>
        <p class="text-muted">Tất cả các môn học trong hệ thống</p>
    </div>

    <!-- Search -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('user.subjects') }}">
                <div class="row g-3">
                    <div class="col-md-9">
                        <input type="text" name="search" value="{{ request('search') }}" 
                               placeholder="Tìm kiếm môn học, mã môn..."
                               class="form-control">
                    </div>
                    <div class="col-md-3">
                        <div class="d-grid gap-2 d-md-flex">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-search me-2"></i> Tìm kiếm
                            </button>
                            <a href="{{ route('user.subjects') }}" class="btn btn-secondary">
                                <i class="fas fa-redo"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Subjects Grid -->
    @if($subjects->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-book fa-4x text-muted mb-3"></i>
                <h5 class="text-muted mb-2">Không tìm thấy môn học</h5>
                <p class="text-muted">Vui lòng thử lại với từ khóa khác</p>
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach($subjects as $subject)
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 hover-card">
                        <!-- Card Header -->
                        <div class="card-header text-white p-4" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                            <p class="mb-1 opacity-75 small">{{ $subject->subject_code }}</p>
                            <h5 class="mb-0 fw-bold" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 60px;">
                                {{ $subject->subject_name }}
                            </h5>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body p-4">
                            <!-- Lecturer -->
                            @if($subject->lecturer)
                                <div class="mb-4">
                                    <p class="text-uppercase text-muted small mb-2">Giảng viên</p>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-3" 
                                             style="width: 40px; height: 40px; font-weight: 600;">
                                            {{ strtoupper(substr($subject->lecturer->name ?? 'L', 0, 1)) }}
                                        </div>
                                        <p class="fw-semibold mb-0">{{ $subject->lecturer->name }}</p>
                                    </div>
                                </div>
                            @endif

                            <!-- Stats -->
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="text-center p-3 rounded" style="background-color: #e7e3fc;">
                                        <p class="display-6 fw-bold text-primary mb-1">{{ $subject->classes->count() }}</p>
                                        <p class="text-muted small mb-0">Lớp học</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-3 rounded" style="background-color: #d4edda;">
                                        <p class="display-6 fw-bold text-success mb-1">{{ $subject->topics->count() }}</p>
                                        <p class="text-muted small mb-0">Đề tài</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="card-footer bg-white border-top-0 p-4 pt-0">
                            <a href="{{ route('user.subject_detail', $subject->subject_id) }}" 
                               class="btn btn-primary w-100">
                                <i class="fas fa-eye me-2"></i>
                                Xem chi tiết
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($subjects->hasPages())
            <div class="mt-4">
                <div class="d-flex justify-content-center">
                    {{ $subjects->links() }}
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
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
</style>
@endsection