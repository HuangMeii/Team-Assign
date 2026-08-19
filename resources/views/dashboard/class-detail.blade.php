{{-- resources/views/dashboard/class-detail.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">{{ $class->class_code }}</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-5 fw-bold">{{ $class->class_name }}</h1>
                    <p class="text-muted">
                        <i class="fas fa-code me-2"></i>{{ $class->class_code }} | 
                        <i class="fas fa-calendar me-2 ms-3"></i>{{ $class->semester ?? 'HK1' }} - {{ $class->year ?? '2024' }}
                    </p>
                </div>
                <div>
                    <a href="{{ route('topics.create') }}?class_id={{ $class->class_id }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Thêm đề tài
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-primary-light me-3">
                            <i class="fas fa-book fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Tổng đề tài</h6>
                            <h3 class="mb-0">{{ $topics->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-success-light me-3">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Đã phân công</h6>
                            <h3 class="mb-0">{{ $topics->whereNotNull('assigned_group_id')->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-info-light me-3">
                            <i class="fas fa-users fa-2x text-info"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Số nhóm</h6>
                            <h3 class="mb-0">{{ $groups->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-warning-light me-3">
                            <i class="fas fa-user-graduate fa-2x text-warning"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Sinh viên</h6>
                            <h3 class="mb-0">{{ $students->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs Navigation --}}
    <ul class="nav nav-tabs mb-4" id="classDetailTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="topics-tab" data-bs-toggle="tab" data-bs-target="#topics" 
                    type="button" role="tab">
                <i class="fas fa-book me-2"></i>Đề tài ({{ $topics->count() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="groups-tab" data-bs-toggle="tab" data-bs-target="#groups" 
                    type="button" role="tab">
                <i class="fas fa-users me-2"></i>Nhóm ({{ $groups->count() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="requests-tab" data-bs-toggle="tab" data-bs-target="#requests" 
                    type="button" role="tab">
                <i class="fas fa-bell me-2"></i>Yêu cầu 
                @if($topicRequests->where('status', 'Pending')->count() > 0)
                    <span class="badge bg-warning">{{ $topicRequests->where('status', 'Pending')->count() }}</span>
                @endif
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="students-tab" data-bs-toggle="tab" data-bs-target="#students" 
                    type="button" role="tab">
                <i class="fas fa-user-graduate me-2"></i>Sinh viên ({{ $students->count() }})
            </button>
        </li>
    </ul>

    {{-- Tab Contents --}}
    <div class="tab-content" id="classDetailTabsContent">
        {{-- Topics Tab --}}
        <div class="tab-pane fade show active" id="topics" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Mã đề tài</th>
                                    <th>Tên đề tài</th>
                                    <th>Mô tả</th>
                                    <th>Nhóm đã nhận</th>
                                    <th>Trạng thái</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topics as $topic)
                                <tr>
                                    <td><code>{{ $topic->topic_id ?? 'N/A' }}</code></td>
                                    <td><strong>{{ $topic->name }}</strong></td>
                                    <td>{{ Str::limit($topic->description, 50) }}</td>
                                    <td>
                                        @if($topic->assignedGroup)
                                            <span class="badge bg-primary">{{ $topic->assignedGroup->group_name }}</span>
                                        @else
                                            <span class="text-muted">Chưa phân công</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($topic->assigned_group_id)
                                            <span class="badge bg-success">Đã phân công</span>
                                        @else
                                            <span class="badge bg-secondary">Chờ phân công</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('topics.show', $topic->topic_id) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('topics.edit', $topic->topic_id) }}" 
                                           class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Chưa có đề tài nào</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Groups Tab --}}
        <div class="tab-pane fade" id="groups" role="tabpanel">
            <div class="row">
                @forelse($groups as $group)
                <div class="col-md-6 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="card-title">{{ $group->group_name }}</h5>
                                @if($group->topic)
                                    <span class="badge bg-success">Đã có đề tài</span>
                                @else
                                    <span class="badge bg-warning">Chưa có đề tài</span>
                                @endif
                            </div>
                            <p class="text-muted mb-2">
                                <i class="fas fa-user-tie me-2"></i>
                                Trưởng nhóm: <strong>{{ optional($group->leader)->name ?? 'N/A' }}</strong>
                            </p>
                            @if($group->topic)
                                <p class="text-muted mb-2">
                                    <i class="fas fa-book me-2"></i>
                                    Đề tài: <strong>{{ $group->topic->name }}</strong>
                                </p>
                            @endif
                            <p class="text-muted mb-3">
                                <i class="fas fa-users me-2"></i>
                                Số thành viên: <strong>{{ $group->members->count() }}</strong>
                            </p>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary" 
                                        data-group-id="{{ $group->group_id }}"
                                        onclick="viewGroupDetail(this)">
                                    <i class="fas fa-eye me-1"></i>Chi tiết
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle"></i> Chưa có nhóm nào trong lớp
                    </div>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Requests Tab --}}
        <div class="tab-pane fade" id="requests" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Thời gian</th>
                                    <th>Nhóm</th>
                                    <th>Đề tài</th>
                                    <th>Ghi chú</th>
                                    <th>Trạng thái</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topicRequests as $request)
                                <tr>
                                    <td>{{ $request->created_at->format('d/m/Y H:i') }}</td>
                                    <td><strong>{{ optional($request->group)->group_name ?? 'N/A' }}</strong></td>
                                    <td>{{ optional($request->topic)->name ?? 'N/A' }}</td>
                                    <td>{{ $request->note ?? '-' }}</td>
                                    <td>
                                        @if($request->status == 'Pending')
                                            <span class="badge bg-warning">Chờ duyệt</span>
                                        @elseif($request->status == 'Accepted')
                                            <span class="badge bg-success">Đã duyệt</span>
                                        @else
                                            <span class="badge bg-danger">Từ chối</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($request->status == 'Pending')
                                            <button class="btn btn-sm btn-success" 
                                                    data-request-id="{{ $request->id }}"
                                                    onclick="approveRequest(this)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    data-request-id="{{ $request->id }}"
                                                    onclick="rejectRequest(this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @else
                                            <span class="text-muted">--</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <p class="text-muted mb-0">Không có yêu cầu nào</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Students Tab --}}
        <div class="tab-pane fade" id="students" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>MSSV</th>
                                    <th>Họ tên</th>
                                    <th>Email</th>
                                    <th>Nhóm</th>
                                    <th>Vai trò</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $student)
                                <tr>
                                    <td><code>{{ $student->student_id ?? $student->user_id }}</code></td>
                                    <td>{{ $student->name ?? 'N/A' }}</td>
                                    <td>{{ $student->email }}</td>
                                    <td>
                                        @php
                                            $studentGroup = $student->groupsJoined ? $student->groupsJoined->first() : null;
                                        @endphp
                                        @if($studentGroup)
                                            <span class="badge bg-primary">{{ $studentGroup->group_name }}</span>
                                        @else
                                            <span class="text-muted">Chưa có nhóm</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($studentGroup && $studentGroup->leader_id == $student->user_id)
                                            <span class="badge bg-success">Trưởng nhóm</span>
                                        @else
                                            <span class="badge bg-secondary">Thành viên</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <p class="text-muted mb-0">Không có sinh viên nào</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .icon-box {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .bg-primary-light { background-color: rgba(13, 110, 253, 0.1); }
    .bg-info-light { background-color: rgba(13, 202, 240, 0.1); }
    .bg-warning-light { background-color: rgba(255, 193, 7, 0.1); }
    .bg-success-light { background-color: rgba(25, 135, 84, 0.1); }
</style>
@endsection

@section('scripts')
<script>
function approveRequest(button) {
    const requestId = button.getAttribute('data-request-id');
    if(confirm('Bạn có chắc muốn duyệt yêu cầu này?')) {
        window.location.href = '/topic-requests/' + requestId + '/approve';
    }
}

function rejectRequest(button) {
    const requestId = button.getAttribute('data-request-id');
    if(confirm('Bạn có chắc muốn từ chối yêu cầu này?')) {
        window.location.href = '/topic-requests/' + requestId + '/reject';
    }
}

function viewGroupDetail(button) {
    const groupId = button.getAttribute('data-group-id');
    window.location.href = '/groups/' + groupId;
}
</script>
@endsection