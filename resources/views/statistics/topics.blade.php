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

    {{-- Biểu đồ (Chart.js CDN) — 2026-10-10 --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card"><div class="card-header">
                <i class="fas fa-chart-line me-1"></i> Sự gia tăng số lượng đề tài (12 tháng gần nhất)
            </div><div class="card-body">
                <div style="height:320px;"><canvas id="topicsGrowthChart"></canvas></div>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-header">
                <i class="fas fa-chart-pie me-1"></i> Trạng thái đề tài
            </div><div class="card-body">
                <div style="height:280px;"><canvas id="topicsStatusChart"></canvas></div>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-header">
                <i class="fas fa-chart-pie me-1"></i> Loại báo cáo (cuối kì / giữa kì)
            </div><div class="card-body">
                <div style="height:280px;"><canvas id="topicsReportTypeChart"></canvas></div>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-header">
                <i class="fas fa-chart-bar me-1"></i> Top 10 giảng viên theo số đề tài
            </div><div class="card-body">
                <div style="height:320px;"><canvas id="topicsLecturerChart"></canvas></div>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-header">
                <i class="fas fa-chart-bar me-1"></i> Top 10 lớp học phần theo số đề tài
            </div><div class="card-body">
                <div style="height:320px;"><canvas id="topicsClassChart"></canvas></div>
            </div></div>
        </div>
    </div>

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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Chart.js nạp từ CDN trong layouts/app (@stack('scripts') chạy SAU thẻ CDN).
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js chưa được nạp — kiểm tra kết nối mạng tới CDN.');
            return;
        }

        const purple = '#764ba2', blue = '#667eea', green = '#198754', orange = '#fd7e14', gray = '#adb5bd';

        // ① Line — Sự gia tăng số đề tài: tạo mới theo tháng + lũy kế.
        const growth = @json($topicsTimeline);
        new Chart(document.getElementById('topicsGrowthChart'), {
            type: 'line',
            data: {
                labels: growth.labels,
                datasets: [
                    {
                        label: 'Tạo mới trong tháng',
                        data: growth.created,
                        borderColor: blue,
                        backgroundColor: 'rgba(102,126,234,0.15)',
                        fill: true, tension: 0.3, borderWidth: 2, pointRadius: 3,
                    },
                    {
                        label: 'Lũy kế',
                        data: growth.cumulative,
                        borderColor: purple,
                        backgroundColor: 'rgba(118,75,162,0.10)',
                        fill: false, tension: 0.3, borderWidth: 2, pointRadius: 3,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + c.parsed.y } },
                },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });

        // ② Doughnut — Trạng thái: còn trống / đã có nhóm.
        new Chart(document.getElementById('topicsStatusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Còn trống', 'Đã có nhóm đăng ký'],
                datasets: [{
                    data: [{{ (int) $freeTopics }}, {{ (int) $assignedTopics }}],
                    backgroundColor: [gray, green],
                    borderWidth: 1,
                }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
        });

        // ③ Doughnut — Loại báo cáo (final / midterm).
        const reportType = @json($topicsByReportType);
        new Chart(document.getElementById('topicsReportTypeChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(reportType).map(k => k === 'midterm' ? 'Giữa kì' : (k === 'final' ? 'Cuối kì' : 'Khác')),
                datasets: [{
                    data: Object.values(reportType),
                    backgroundColor: [blue, orange, gray],
                    borderWidth: 1,
                }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
        });

        // ④ Bar ngang — Top 10 giảng viên.
        const byLecturer = @json($topicsByLecturer->take(10)->values());
        new Chart(document.getElementById('topicsLecturerChart'), {
            type: 'bar',
            data: {
                labels: byLecturer.map(r => r.lecturer || '(chưa rõ)'),
                datasets: [{ label: 'Số đề tài', data: byLecturer.map(r => Number(r.total)), backgroundColor: purple }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });

        // ⑤ Bar — Top 10 lớp học phần.
        const byClass = @json($topicsByClass->take(10)->values());
        new Chart(document.getElementById('topicsClassChart'), {
            type: 'bar',
            data: {
                labels: byClass.map(c => c.class_name),
                datasets: [{ label: 'Số đề tài', data: byClass.map(c => Number(c.topics_count)), backgroundColor: blue }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });
    });
</script>
@endpush
@endsection
