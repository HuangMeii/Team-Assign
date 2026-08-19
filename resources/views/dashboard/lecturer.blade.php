@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-5 fw-bold">Lecturer Dashboard</h1>
              
            </div>
        </div>

        {{-- Overall Statistics --}}
        <div class="row mb-5">
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted mb-2">Tổng số lớp</h6>
                                <h2 class="mb-0 text-primary">{{ $stats['total_classes'] }}</h2>
                            </div>
                            <div class="icon-circle bg-primary-light">
                                <i class="fas fa-chalkboard-teacher fa-2x text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted mb-2">Tổng đề tài</h6>
                                <h2 class="mb-0 text-info">{{ $stats['assigned_topics'] }}</h2>
                            </div>
                            <div class="icon-circle bg-info-light">
                                <i class="fas fa-book fa-2x text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted mb-2">Chờ duyệt</h6>
                                <h2 class="mb-0 text-warning">{{ $stats['pending_requests'] }}</h2>
                            </div>
                            <div class="icon-circle bg-warning-light">
                                <i class="fas fa-clock fa-2x text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted mb-2">Đã duyệt</h6>
                                <h2 class="mb-0 text-success">{{ $stats['approved_topics'] }}</h2>
                            </div>
                            <div class="icon-circle bg-success-light">
                                <i class="fas fa-check-circle fa-2x text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Classes Section --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="fw-bold">Các lớp phụ trách</h3>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">
                        <i class="fas fa-th-large me-2"></i>Xem tất cả
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            @forelse($lecturerClasses as $class)
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm hover-card">
                        <div class="card-header class-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 text-white">{{ $class->class_code }}</h5>
                                <span class="badge bg-white text-dark">{{ $class->semester ?? 'HK1' }} -
                                    {{ $class->year ?? '2024' }}</span>
                            </div>
                        </div>

                        <div class="card-body">
                            <h6 class="card-title fw-bold text-dark mb-3">{{ $class->class_name }}</h6>

                            {{-- Class Statistics --}}
                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <div class="p-2 rounded bg-light">
                                        <h5 class="mb-1 text-primary">
                                            {{ isset($classStats[$class->class_id]) ? $classStats[$class->class_id]['topics'] : 0 }}
                                        </h5>
                                        <small class="text-muted">Đề tài</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 rounded bg-light">
                                        <h5 class="mb-1 text-warning">
                                            {{ isset($classStats[$class->class_id]) ? $classStats[$class->class_id]['pending'] : 0 }}
                                        </h5>
                                        <small class="text-muted">Chờ duyệt</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 rounded bg-light">
                                        <h5 class="mb-1 text-success">
                                            {{ isset($classStats[$class->class_id]) ? $classStats[$class->class_id]['approved'] : 0 }}
                                        </h5>
                                        <small class="text-muted">Đã duyệt</small>
                                    </div>
                                </div>
                            </div>

                            {{-- Progress Bar --}}
                            @php
                                $totalTopics = isset($classStats[$class->class_id]) ? $classStats[$class->class_id]['topics'] : 0;
                                $approvedTopics = isset($classStats[$class->class_id]) ? $classStats[$class->class_id]['approved'] : 0;
                                $progress = $totalTopics > 0 ? round(($approvedTopics / $totalTopics) * 100) : 0;
                            @endphp
                            
                            {{-- Action Buttons --}}
                            <div class="d-grid gap-2">
                                <a href="{{ route('dashboard.class.detail', $class->class_id) }}" class="btn btn-primary">
                                    <i class="fas fa-eye me-2"></i>Xem chi tiết
                                </a>
                                <a href="{{ route('topics.index', ['class_id' => $class->class_id]) }}"
                                    class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-list me-2"></i>Danh sách đề tài
                                </a>
                            </div>
                        </div>

                        <div class="card-footer bg-transparent text-muted small">
                            <i class="fas fa-users me-1"></i>
                            {{ isset($classStats[$class->class_id]) ? $classStats[$class->class_id]['students'] : 0 }} sinh viên
                            <span class="float-end">
                                <i class="fas fa-user-friends me-1"></i>
                                {{ isset($classStats[$class->class_id]) ? $classStats[$class->class_id]['groups'] : 0 }} nhóm
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-3x mb-3"></i>
                        <h5>Chưa có lớp nào được phân công</h5>
                        <p class="mb-0">Vui lòng liên hệ admin để được phân công lớp giảng dạy.</p>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- Recent Activities --}}
        @if(isset($recentRequests) && $recentRequests->count() > 0)
            <div class="row mt-5">
                <div class="col-12">
                    <h3 class="fw-bold mb-3">Hoạt động gần đây</h3>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Thời gian</th>
                                            <th>Lớp</th>
                                            <th>Đề tài</th>
                                            <th>Nhóm</th>
                                            <th>Trạng thái</th>
                                            <th>Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentRequests as $request)
                                            <tr>
                                                <td>
                                                    <small class="text-muted">
                                                        {{ $request->created_at->diffForHumans() }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        {{ optional($request->topic->class)->class_name ?? 'N/A' }}
                                                    </span>
                                                </td>
                                                <td>{{ Str::limit($request->topic->name ?? '', 30) }}</td>
                                                <td>{{ optional($request->group)->group_name ?? 'N/A' }}</td>
                                                <td>
                                                    @if($request->status == 'Pending')
                                                        <span class="badge bg-warning text-dark">Chờ duyệt</span>
                                                    @elseif($request->status == 'Accepted')
                                                        <span class="badge bg-success">Đã duyệt</span>
                                                    @else
                                                        <span class="badge bg-danger">Từ chối</span>
                                                    @endif
                                                </td>
                                                <script>
                                                    function approveRequest(button) {
                                                        const requestId = button.getAttribute('data-request-id');

                                                        if (confirm('Bạn có chắc chắn muốn duyệt yêu cầu này?')) {
                                                            fetch(`/topic-requests/${requestId}/approve`, {
                                                                method: 'POST',
                                                                headers: {
                                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                                    'Content-Type': 'application/json',
                                                                    'Accept': 'application/json'
                                                                },
                                                                body: JSON.stringify({
                                                                    _method: 'PUT'
                                                                })
                                                            })
                                                                .then(response => response.json())
                                                                .then(data => {
                                                                    if (data.success) {
                                                                        alert('Đã duyệt yêu cầu thành công!');
                                                                        location.reload();
                                                                    } else {
                                                                        alert('Có lỗi xảy ra: ' + (data.message || 'Unknown error'));
                                                                    }
                                                                })
                                                                .catch(error => {
                                                                    console.error('Error:', error);
                                                                    alert('Có lỗi xảy ra khi xử lý yêu cầu');
                                                                });
                                                        }
                                                    }

                                                    function rejectRequest(button) {
                                                        const requestId = button.getAttribute('data-request-id');

                                                        if (confirm('Bạn có chắc chắn muốn từ chối yêu cầu này?')) {
                                                            fetch(`/topic-requests/${requestId}/reject`, {
                                                                method: 'POST',
                                                                headers: {
                                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                                    'Content-Type': 'application/json',
                                                                    'Accept': 'application/json'
                                                                },
                                                                body: JSON.stringify({
                                                                    _method: 'PUT'
                                                                })
                                                            })
                                                                .then(response => response.json())
                                                                .then(data => {
                                                                    if (data.success) {
                                                                        alert('Đã từ chối yêu cầu!');
                                                                        location.reload();
                                                                    } else {
                                                                        alert('Có lỗi xảy ra: ' + (data.message || 'Unknown error'));
                                                                    }
                                                                })
                                                                .catch(error => {
                                                                    console.error('Error:', error);
                                                                    alert('Có lỗi xảy ra khi xử lý yêu cầu');
                                                                });
                                                        }
                                                    }
                                                </script>
                                         
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('styles')
    <style>
        .icon-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bg-primary-light {
            background-color: rgba(13, 110, 253, 0.1);
        }

        .bg-info-light {
            background-color: rgba(13, 202, 240, 0.1);
        }

        .bg-warning-light {
            background-color: rgba(255, 193, 7, 0.1);
        }

        .bg-success-light {
            background-color: rgba(25, 135, 84, 0.1);
        }

        .hover-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .hover-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
        }

        .class-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1rem;
        }

        .progress {
            background-color: #e9ecef;
        }
    </style>
@endsection

@section('scripts')
    <script>
        function approveRequest(button) {
            const requestId = button.getAttribute('data-request-id');

            if (confirm('Bạn có chắc chắn muốn duyệt yêu cầu này?')) {
                fetch(`/topic-requests/${requestId}/approve`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        _method: 'PUT'
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Đã duyệt yêu cầu thành công!');
                            location.reload();
                        } else {
                            alert('Có lỗi xảy ra: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Có lỗi xảy ra khi xử lý yêu cầu');
                    });
            }
        }

        function rejectRequest(button) {
            const requestId = button.getAttribute('data-request-id');

            if (confirm('Bạn có chắc chắn muốn từ chối yêu cầu này?')) {
                fetch(`/topic-requests/${requestId}/reject`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        _method: 'PUT'
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Đã từ chối yêu cầu!');
                            location.reload();
                        } else {
                            alert('Có lỗi xảy ra: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Có lỗi xảy ra khi xử lý yêu cầu');
                    });
            }
        }
    </script>
@endsection