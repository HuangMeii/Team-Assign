@extends('layouts.app')

@section('title', 'Thống kê hệ thống')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Thống kê hệ thống</h1>

    <div class="row g-3 mb-4">
        <div class="col-md-4 col-xl">
            <div class="card text-bg-primary h-100"><div class="card-body">
                <div class="fs-2 fw-bold">{{ $users }}</div>
                <div>Người dùng</div>
            </div></div>
        </div>
        <div class="col-md-4 col-xl">
            <div class="card text-bg-success h-100"><div class="card-body">
                <div class="fs-2 fw-bold">{{ $topics }}</div>
                <div>Đề tài</div>
            </div></div>
        </div>
        <div class="col-md-4 col-xl">
            <div class="card text-bg-warning h-100"><div class="card-body">
                <div class="fs-2 fw-bold">{{ $groups }}</div>
                <div>Nhóm</div>
            </div></div>
        </div>
        <div class="col-md-6 col-xl">
            <div class="card text-bg-info h-100"><div class="card-body">
                <div class="fs-2 fw-bold">{{ $requests }}</div>
                <div>Yêu cầu đăng ký đề tài</div>
            </div></div>
        </div>
        <div class="col-md-6 col-xl">
            <a href="{{ route('admin.chat.monitor', ['tab' => 'flagged']) }}" class="text-decoration-none">
            <div class="card text-bg-danger h-100"><div class="card-body">
                <div class="fs-2 fw-bold">{{ $pendingRequests }}</div>
                <div>Yêu cầu đang chờ duyệt</div>
            </div></div>
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6"><div class="card h-100"><div class="card-body">
            <h5 class="card-title"><i class="fas fa-book me-2"></i>Đề tài</h5>
            <p class="card-text">Phân bố đề tài còn trống / đã có nhóm, theo giảng viên và lớp học phần.</p>
            <a href="{{ route('admin.statistics.topics') }}" class="btn btn-outline-primary">Xem chi tiết</a>
        </div></div></div>
        <div class="col-md-6"><div class="card h-100"><div class="card-body">
            <h5 class="card-title"><i class="fas fa-users me-2"></i>Nhóm</h5>
            <p class="card-text">Nhóm có / chưa có đề tài, số thành viên trung bình theo lớp.</p>
            <a href="{{ route('admin.statistics.groups') }}" class="btn btn-outline-primary">Xem chi tiết</a>
        </div></div></div>
        <div class="col-md-6"><div class="card h-100"><div class="card-body">
            <h5 class="card-title"><i class="fas fa-inbox me-2"></i>Yêu cầu đăng ký đề tài</h5>
            <p class="card-text">Chờ duyệt / đã duyệt / bị từ chối.</p>
            <a href="{{ route('admin.statistics.requests') }}" class="btn btn-outline-primary">Xem chi tiết</a>
        </div></div></div>
        <div class="col-md-6"><div class="card h-100"><div class="card-body">
            <h5 class="card-title"><i class="fas fa-user-friends me-2"></i>Người dùng</h5>
            <p class="card-text">Theo vai trò, số trưởng nhóm, người chưa có nhóm.</p>
            <a href="{{ route('admin.statistics.users') }}" class="btn btn-outline-primary">Xem chi tiết</a>
        </div></div></div>
    </div>
</div>
@endsection
