@extends('layouts.app')

@section('title', 'Thống kê nhóm')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Thống kê nhóm</h1>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card text-bg-warning h-100"><div class="card-body">
                <div class="fs-2 fw-bold">{{ $totalGroups }}</div><div>Tổng số nhóm</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card text-bg-success h-100"><div class="card-body">
                <div class="fs-2 fw-bold">{{ $groupsWithTopic }}</div><div>Đã có đề tài</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card text-bg-light h-100"><div class="card-body">
                <div class="fs-2 fw-bold">{{ $groupsWithoutTopic }}</div><div>Chưa có đề tài</div>
            </div></div>
        </div>
    </div>

    <div class="card mb-4"><div class="card-body">
        <div class="d-flex justify-content-between mb-1">
            <span>Số thành viên trung bình mỗi nhóm</span><span class="fw-bold">{{ $avgMembersPerGroup }}</span>
        </div>
    </div></div>

    <div class="card mb-4"><div class="card-header">Nhóm theo lớp học phần</div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Lớp học phần</th><th class="text-end">Số nhóm</th></tr></thead>
            <tbody>
            @forelse($groupsByClass as $class)
                <tr><td>{{ $class->class_name }}</td><td class="text-end">{{ $class->groups_count }}</td></tr>
            @empty
                <tr><td colspan="2" class="text-center text-muted">Chưa có dữ liệu</td></tr>
            @endforelse
            </tbody>
        </table></div></div>
    </div>

    <a href="{{ route('admin.statistics.index') }}" class="btn btn-secondary mb-4">&larr; Tổng quan</a>
</div>
@endsection
