@extends('layouts.app')

@section('title', 'Thống kê yêu cầu đăng ký đề tài')

@php
    $maxStatus = max(1, $byStatus->max());
    $colors = ['Pending' => 'bg-warning', 'Accepted' => 'bg-success', 'Rejected' => 'bg-danger',
               'Cancelled' => 'bg-secondary', 'Expired' => 'bg-secondary'];
@endphp

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Thống kê yêu cầu đăng ký đề tài</h1>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-bg-info h-100"><div class="card-body">
                <div class="fs-2 fw-bold">{{ $totalRequests }}</div><div>Tổng số yêu cầu</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body">
                <div class="fs-2 fw-bold text-warning">{{ $pendingRequests }}</div><div>Chờ duyệt (Pending)</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body">
                <div class="fs-2 fw-bold text-success">{{ $acceptedRequests }}</div><div>Đã duyệt (Accepted)</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body">
                <div class="fs-2 fw-bold text-danger">{{ $rejectedRequests }}</div><div>Từ chối (Rejected)</div>
            </div></div>
        </div>
    </div>

    <div class="card mb-4"><div class="card-header">Phân bố theo trạng thái</div>
        <div class="card-body">
        @forelse($byStatus as $status => $total)
            <div class="d-flex justify-content-between mb-1">
                <span>{{ $status }}</span><span>{{ $total }}</span>
            </div>
            <div class="progress mb-3" role="progressbar">
                <div class="progress-bar {{ $colors[$status] ?? 'bg-primary' }}"
                     style="width: {{ round($total * 100 / $maxStatus) }}%"></div>
            </div>
        @empty
            <p class="text-muted mb-0">Chưa có dữ liệu</p>
        @endforelse
        </div>
    </div>

    <a href="{{ route('admin.statistics.index') }}" class="btn btn-secondary mb-4">&larr; Tổng quan</a>
</div>
@endsection
