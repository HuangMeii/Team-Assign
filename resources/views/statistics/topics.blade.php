@extends('layouts.app')

@section('title', 'Thống kê đề tài')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Thống kê đề tài</h1>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card text-bg-success h-100"><div class="card-body">
                <div class="fs-2 fw-bold">{{ $totalTopics }}</div><div>Tổng số đề tài</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card text-bg-light h-100"><div class="card-body">
                <div class="fs-2 fw-bold">{{ $freeTopics }}</div><div>Còn trống</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card text-bg-dark h-100"><div class="card-body">
                <div class="fs-2 fw-bold">{{ $assignedTopics }}</div><div>Đã có nhóm đăng ký</div>
            </div></div>
        </div>
    </div>
    @if ($totalTopics > 0)
    <div class="card mb-4"><div class="card-body">
        <div class="d-flex justify-content-between mb-1"><span>Tỉ lệ đã có nhóm đăng ký</span><span>{{ round($assignedTopics * 100 / $totalTopics) }}%</span></div>
        <div class="progress" role="progressbar">
            <div class="progress-bar bg-success" style="width: {{ round($assignedTopics * 100 / $totalTopics) }}%"></div>
        </div>
    </div></div>
    @endif

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card mb-4"><div class="card-header">Theo giảng viên</div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Giảng viên</th><th class="text-end">Số đề tài</th></tr></thead>
                    <tbody>
                    @forelse($topicsByLecturer as $row)
                        <tr><td>{{ $row->lecturer ?: '(chưa rõ)' }}</td><td class="text-end">{{ $row->total }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted">Chưa có dữ liệu</td></tr>
                    @endforelse
                    </tbody>
                </table></div></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-4"><div class="card-header">Theo lớp học phần</div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Lớp học phần</th><th class="text-end">Số đề tài</th></tr></thead>
                    <tbody>
                    @forelse($topicsByClass as $class)
                        <tr><td>{{ $class->class_name }}</td><td class="text-end">{{ $class->topics_count }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted">Chưa có dữ liệu</td></tr>
                    @endforelse
                    </tbody>
                </table></div></div>
            </div>
        </div>
    </div>

    <a href="{{ route('admin.statistics.index') }}" class="btn btn-secondary mb-4">&larr; Tổng quan</a>
</div>
@endsection
