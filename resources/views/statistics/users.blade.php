@extends('layouts.app')

@section('title', 'Thống kê người dùng')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Thống kê người dùng</h1>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-bg-primary h-100"><div class="card-body">
                <div class="fs-2 fw-bold">{{ $totalUsers }}</div><div>Tổng người dùng</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body">
                <div class="fs-2 fw-bold">{{ $students }}</div><div>Sinh viên</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body">
                <div class="fs-2 fw-bold">{{ $lecturers }}</div><div>Giảng viên</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body">
                <div class="fs-2 fw-bold">{{ $admins }}</div><div>Quản trị viên</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100"><div class="card-body">
                <h5 class="card-title"><i class="fas fa-crown me-2 text-warning"></i>Trưởng nhóm</h5>
                <div class="fs-2 fw-bold">{{ $leaders }}</div>
                <p class="text-muted mb-0">Số người đang dẫn dắt ít nhất 1 nhóm (theo groups.leader_id)</p>
            </div></div>
        </div>
        <div class="col-md-6">
            <div class="card h-100"><div class="card-body">
                <h5 class="card-title"><i class="fas fa-user-slash me-2 text-muted"></i>Chưa có nhóm</h5>
                <div class="fs-2 fw-bold">{{ $usersWithoutGroup }}</div>
                <p class="text-muted mb-0">Không là thành viên / trưởng nhóm của nhóm nào</p>
            </div></div>
        </div>
    </div>

    <a href="{{ route('admin.statistics.index') }}" class="btn btn-secondary mt-4 mb-4">&larr; Tổng quan</a>
</div>
@endsection
