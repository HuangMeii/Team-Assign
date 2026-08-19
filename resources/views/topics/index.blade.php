@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-book"></i> Quản lý Đề tài</h4>
            <a href="{{ route('topics.create') }}" class="btn btn-light btn-sm">
                <i class="fas fa-plus"></i> Thêm đề tài mới
            </a>
        </div>

        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Filter Section -->
            <div class="card mb-4 border-0" style="background: linear-gradient(135deg, #afb3c2ff 0%, #d3ccdaff 100%);">
                <div class="card-body">
                    <form method="GET" action="{{ route('topics.index') }}">
                        <div class="row g-3">
                            <!-- Search -->
                            <div class="col-md-5">
                                <label class="form-label text-white"><i class="fas fa-search"></i> Tìm kiếm</label>
                                <input type="text" 
                                       name="search" 
                                       value="{{ request('search') }}"
                                       class="form-control" 
                                       placeholder="Tên đề tài...">
                            </div>

                            <!-- Filter by Class -->
                            <div class="col-md-4">
                                <label class="form-label text-white"><i class="fas fa-users"></i> Lọc theo lớp học phần</label>
                                <select name="class_id" class="form-select">
                                    <option value="">Tất cả lớp</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->class_id }}" 
                                                {{ request('class_id') == $class->class_id ? 'selected' : '' }}>
                                            {{ $class->class_name }} - {{ $class->subject->subject_name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Buttons -->
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-light me-2">
                                    <i class="fas fa-filter"></i> Lọc
                                </button>
                                <a href="{{ route('topics.index') }}" class="btn btn-outline-light">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Topics Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 35%">Tên đề tài</th>
                            <th style="width: 20%">Lớp học phần</th>
                            <th style="width: 15%">Môn học</th>
                            <th class="text-center" style="width: 10%">Yêu cầu</th>
                            <th class="text-center" style="width: 10%">Trạng thái</th>
                            <th class="text-center" style="width: 10%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topics as $topic)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $topic->name }}</div>
                                    <small class="text-muted">{{ Str::limit($topic->description, 60) }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        <i class="fas fa-chalkboard-teacher"></i> {{ $topic->class->class_name ?? 'Chưa có' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge" style="background-color: #6f42c1; color: white;">
                                        <i class="fas fa-book"></i> {{ $topic->class->subject->subject_name ?? 'Chưa có' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark fs-6">
                                        {{ $topic->topic_requests->where('status', 'pending')->count() }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($topic->assigned_group_id)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle"></i> Đã gán
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-clock"></i> Còn trống
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('topics.show', $topic->topic_id) }}" 
                                           class="btn btn-sm btn-outline-primary"
                                           title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('topics.edit', $topic->topic_id) }}" 
                                           class="btn btn-sm btn-outline-warning"
                                           title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('topics.destroy', $topic->topic_id) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('Bạn chắc chắn muốn xóa đề tài này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Chưa có đề tài nào</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $topics->links() }}
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
    }
    
    .card-header {
        border-radius: 15px 15px 0 0 !important;
        padding: 1.25rem;
    }
    
    .table > :not(caption) > * > * {
        padding: 1rem 0.75rem;
    }
    
    .badge {
        padding: 0.5em 0.8em;
        font-weight: 500;
    }
    
    .btn-group .btn {
        border-radius: 0;
    }
    
    .btn-group .btn:first-child {
        border-radius: 0.25rem 0 0 0.25rem;
    }
    
    .btn-group .btn:last-child {
        border-radius: 0 0.25rem 0.25rem 0;
    }
    
    tr:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s;
    }
</style>
@endsection