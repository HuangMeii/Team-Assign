@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <h1 class="fw-bold text-primary">
            <i class="fas fa-tachometer-alt me-2"></i> Dashboard Quản trị
        </h1>
        <div class="text-muted">
            {{ now()->format('l, d/m/Y') }}
        </div>
    </div>

    {{-- Row 1: Thống kê tổng quan --}}
    <div class="row g-4 mb-4">
        <!-- Total Users -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50 small text-uppercase fw-bold mb-1">Tổng Người dùng</div>
                            <div class="h2 mb-0 fw-bold">{{ $stats['total_users'] }}</div>
                        </div>
                        <div class="fs-1 text-white-50">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between bg-primary border-top-0 bg-opacity-10">
                    <a class="small text-white stretched-link text-decoration-none" href="{{ route('admin.users.index') }}">Xem chi tiết</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>

        <!-- Total Classes -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50 small text-uppercase fw-bold mb-1">Lớp học phần</div>
                            <div class="h2 mb-0 fw-bold">{{ $stats['total_classes'] }}</div>
                        </div>
                        <div class="fs-1 text-white-50">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between bg-success border-top-0 bg-opacity-10">
                    <a class="small text-white stretched-link text-decoration-none" href="{{ route('admin.classes.index') }}">Quản lý lớp</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>

        <!-- Total Topics -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50 small text-uppercase fw-bold mb-1">Tổng Đề tài</div>
                            <div class="h2 mb-0 fw-bold">{{ $stats['total_topics'] }}</div>
                        </div>
                        <div class="fs-1 text-white-50">
                            <i class="fas fa-book-open"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between bg-warning border-top-0 bg-opacity-10">
                    <a class="small text-white stretched-link text-decoration-none" href="#">Xem thống kê</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>

        <!-- Pending Requests -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50 small text-uppercase fw-bold mb-1">Yêu cầu chờ duyệt</div>
                            <div class="h2 mb-0 fw-bold">{{ $stats['pending_reqs'] }}</div>
                        </div>
                        <div class="fs-1 text-white-50">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between bg-danger border-top-0 bg-opacity-10">
                    <span class="small text-white">Cần giảng viên xử lý</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Row 2: Main Content --}}
    <div class="row">
        {{-- Cột Trái: Danh sách quan trọng --}}
        <div class="col-lg-8 mb-4">
            
            <!-- Pending Topic Requests -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-primary">
                        <i class="fas fa-clipboard-list me-1"></i> Yêu cầu đăng ký đề tài mới nhất
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Nhóm</th>
                                    <th>Đề tài</th>
                                    <th>Lớp</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingTopicRequests as $req)
                                    <tr>
                                        <td class="ps-4 fw-bold">{{ $req->group->group_name ?? 'N/A' }}</td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 200px;" title="{{ $req->topic->name ?? '' }}">
                                                {{ $req->topic->name ?? 'N/A' }}
                                            </div>
                                        </td>
                                        <td>{{ $req->topic->class->class_name ?? 'N/A' }}</td>
                                        <td><span class="badge bg-warning text-dark">Chờ duyệt</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">Không có yêu cầu nào đang chờ.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Classes -->
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-success">
                        <i class="fas fa-chalkboard me-1"></i> Lớp học phần vừa tạo
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($recentClasses as $class)
                            <div class="list-group-item px-4 py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold mb-1 text-primary">{{ $class->class_name }}</h6>
                                        <small class="text-muted">
                                            <i class="fas fa-book me-1"></i> {{ $class->subject->subject_name ?? 'Chưa gán môn' }}
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        @if($class->lecturer)
                                            <span class="badge bg-info text-dark">
                                                <i class="fas fa-user-tie me-1"></i> {{ $class->lecturer->name }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">Chưa có GV</span>
                                        @endif
                                        <div class="small text-muted mt-1">{{ $class->created_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 text-center text-muted">Chưa có lớp học nào.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Cột Phải: Quick Links & Recent Users --}}
        <div class="col-lg-4 mb-4">
            
            <!-- Quick Actions -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-secondary">
                        <i class="fas fa-bolt me-1"></i> Thao tác nhanh
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.users.create') }}" class="btn btn-outline-primary text-start">
                            <i class="fas fa-user-plus me-2"></i> Thêm người dùng mới
                        </a>
                        <a href="{{ route('admin.classes.create') }}" class="btn btn-outline-success text-start">
                            <i class="fas fa-plus-square me-2"></i> Tạo lớp học phần
                        </a>
                        <a href="{{ route('admin.subjects.create') }}" class="btn btn-outline-info text-start">
                            <i class="fas fa-book-medical me-2"></i> Thêm môn học
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-info">
                        <i class="fas fa-user-clock me-1"></i> Người dùng mới
                    </h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($recentUsers as $user)
                            <li class="list-group-item px-4 py-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="rounded-circle bg-light d-flex justify-content-center align-items-center text-secondary fw-bold border" style="width: 40px; height: 40px;">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0 fw-bold">{{ $user->name }}</h6>
                                        <small class="text-muted">{{ $user->email }}</small>
                                    </div>
                                    <div>
                                        @if($user->role === 'admin')
                                            <span class="badge bg-danger">Admin</span>
                                        @elseif($user->role === 'lecturer')
                                            <span class="badge bg-primary">GV</span>
                                        @else
                                            <span class="badge bg-secondary">SV</span>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="card-footer bg-white border-top-0 text-center py-3">
                    <a href="{{ route('admin.users.index') }}" class="text-decoration-none fw-bold">Xem tất cả người dùng</a>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection