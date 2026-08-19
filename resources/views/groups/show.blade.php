@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row">
        <!-- Group Info -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-users"></i> {{ $group->group_name }}</h4>
                    <div>
                        <a href="{{ route('groups.edit', $group->group_id) }}" class="btn btn-light btn-sm">
                            <i class="fas fa-edit"></i> Chỉnh sửa
                        </a>
                        <a href="{{ route('groups.index') }}" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Group Details -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <label class="text-muted"><i class="fas fa-chalkboard-teacher"></i> Lớp học phần:</label>
                                <div class="fw-bold">{{ $group->class->class_name ?? 'Chưa có' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <label class="text-muted"><i class="fas fa-book"></i> Môn học:</label>
                                <div class="fw-bold">{{ $group->class->subject->subject_name ?? 'Chưa có' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <label class="text-muted"><i class="fas fa-user-tie"></i> Nhóm trưởng:</label>
                                <div class="fw-bold">{{ $group->leader->name ?? 'Chưa có' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <label class="text-muted"><i class="fas fa-user-friends"></i> Số thành viên:</label>
                                <div class="fw-bold">{{ $group->members->count() }} người</div>
                            </div>
                        </div>
                    </div>

                    <!-- Members List -->
                    <h5 class="mb-3"><i class="fas fa-users"></i> Danh sách thành viên</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Họ tên</th>
                                    <th>Email</th>
                                    <th>Vai trò</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($group->members as $index => $member)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            {{ $member->name }}
                                            @if($member->user_id == $group->leader_id)
                                                <span class="badge bg-warning text-dark ms-2">
                                                    <i class="fas fa-crown"></i> Nhóm trưởng
                                                </span>
                                            @endif
                                        </td>
                                        <td>{{ $member->email }}</td>
                                        <td>
                                            @if($member->user_id == $group->leader_id)
                                                <span class="badge bg-primary">Leader</span>
                                            @else
                                                <span class="badge bg-secondary">Member</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            Chưa có thành viên nào
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Topic Info Sidebar -->
        <div class="col-lg-4">
            <!-- Current Topic -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-lightbulb"></i> Đề tài</h5>
                </div>
                <div class="card-body">
                    @if($group->topic)
                        <h6 class="text-success mb-3">
                            <i class="fas fa-check-circle"></i> Đã có đề tài
                        </h6>
                        <div class="topic-info">
                            <h6 class="fw-bold">{{ $group->topic->name }}</h6>
                            <p class="text-muted small mb-2">{{ Str::limit($group->topic->description, 100) }}</p>
                            <div class="text-muted small">
                                <i class="fas fa-user-tie"></i> Giảng viên: {{ $group->topic->lecturer }}
                            </div>
                        </div>
                    @else
                        <h6 class="text-warning mb-3">
                            <i class="fas fa-exclamation-triangle"></i> Chưa có đề tài
                        </h6>
                        
                        @if(auth()->user()->role === 'lecturer' && $availableTopics->count() > 0)
                            <!-- Form gán đề tài -->
                            <form action="{{ route('groups.assignTopic', $group->group_id) }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label small">Chọn đề tài:</label>
                                    <select name="topic_id" class="form-select form-select-sm" required>
                                        <option value="">-- Chọn đề tài --</option>
                                        @foreach($availableTopics as $topic)
                                            <option value="{{ $topic->topic_id }}">
                                                {{ $topic->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-check"></i> Gán đề tài
                                </button>
                            </form>
                        @else
                            <p class="text-muted small mb-0">
                                <i class="fas fa-info-circle"></i> Không có đề tài khả dụng trong lớp này
                            </p>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Statistics -->
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Thống kê</h5>
                </div>
                <div class="card-body">
                    <div class="stat-item mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Số thành viên:</span>
                            <span class="fw-bold">{{ $group->members->count() }}</span>
                        </div>
                    </div>
                    <div class="stat-item mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Đề tài:</span>
                            <span class="fw-bold">
                                @if($group->topic_id)
                                    <span class="text-success">✓ Đã có</span>
                                @else
                                    <span class="text-warning">✗ Chưa có</span>
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Ngày tạo:</span>
                            <span class="fw-bold">{{ $group->created_at->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .info-item label {
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }
    
    .topic-info {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 0.5rem;
        border-left: 3px solid #28a745;
    }
    
    .stat-item {
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e9ecef;
    }
    
    .stat-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
</style>
@endsection