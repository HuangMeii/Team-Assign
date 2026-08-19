@extends('layouts.app')

@section('title', 'Quản lý sinh viên')

@section('content')
<div class="container-fluid px-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Sinh viên</li>
        </ol>
    </nav>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            
            @if(session('errors_list'))
                <hr>
                <ul class="mb-0 small">
                    @foreach(session('errors_list') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <!-- Header Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2 class="mb-2 fw-bold">
                        <i class="fas fa-user-graduate text-primary me-2"></i>
                        Quản lý sinh viên
                    </h2>
                    <p class="text-muted mb-0">
                        Tổng số: <strong>{{ $students->total() }}</strong> sinh viên
                        @if(request('class_id'))
                            @php
                                $selectedClass = $classes->firstWhere('class_id', request('class_id'));
                            @endphp
                            @if($selectedClass)
                                trong lớp <strong>{{ $selectedClass->class_name }}</strong>
                            @endif
                        @endif
                    </p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <div class="btn-group">
                        <button type="button" class="btn btn-success" data-bs-toggle="dropdown">
                            <i class="fas fa-file-excel me-2"></i>Export
                            <i class="fas fa-chevron-down ms-1"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('students.export') }}">
                                    <i class="fas fa-users me-2"></i>Tất cả sinh viên
                                </a>
                            </li>
                            @if(request('class_id'))
                                <li>
                                    <a class="dropdown-item" href="{{ route('students.export', ['class_id' => request('class_id')]) }}">
                                        <i class="fas fa-chalkboard me-2"></i>Lớp hiện tại
                                    </a>
                                </li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#exportModal">
                                    <i class="fas fa-filter me-2"></i>Chọn lớp...
                                </a>
                            </li>
                        </ul>
                    </div>
                    <a href="{{ route('students.import.form') }}" class="btn btn-warning ms-2">
                        <i class="fas fa-file-import me-2"></i>Import
                    </a>
                    <a href="{{ route('students.create') }}" class="btn btn-primary ms-2">
                        <i class="fas fa-plus me-2"></i>Thêm sinh viên
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('students.index') }}">
                <div class="row g-3 align-items-end">
                    <!-- Search -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-search text-primary me-1"></i>Tìm kiếm
                        </label>
                        <input type="text" 
                               name="search" 
                               value="{{ request('search') }}"
                               class="form-control" 
                               placeholder="Nhập tên hoặc email...">
                    </div>

                    <!-- Filter by Class -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-chalkboard text-primary me-1"></i>Lớp học
                        </label>
                        <select name="class_id" class="form-select">
                            <option value="">Tất cả lớp</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->class_id }}" 
                                        {{ request('class_id') == $class->class_id ? 'selected' : '' }}>
                                    {{ $class->class_name }}
                                    @if($class->subject)
                                        - {{ $class->subject->subject_name }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="col-md-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-filter me-2"></i>Lọc
                            </button>
                            @if(request()->hasAny(['search', 'class_id']))
                                <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Students Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background-color: #f8f9fa;">
                        <tr>
                            <th class="px-4 py-3" style="width: 60px;">#</th>
                            <th class="py-3">Sinh viên</th>
                            <th class="py-3">Lớp học</th>
                            <th class="py-3">Trạng thái</th>
                            <th class="py-3 text-center" style="width: 150px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $index => $student)
                            <tr>
                                <td class="px-4">
                                    <span class="text-muted">{{ $students->firstItem() + $index }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold me-3"
                                             style="width: 40px; height: 40px;">
                                            {{ strtoupper(substr($student->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $student->name }}</div>
                                            <small class="text-muted">{{ $student->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($student->classes->isEmpty())
                                        <span class="badge bg-secondary">Chưa có lớp</span>
                                    @else
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($student->classes->take(3) as $class)
                                                <span class="badge bg-info">
                                                    {{ $class->class_name }}
                                                </span>
                                            @endforeach
                                            @if($student->classes->count() > 3)
                                                <span class="badge bg-secondary">
                                                    +{{ $student->classes->count() - 3 }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        // Kiểm tra xem sinh viên có nhóm không
                                        $hasGroup = $student->groupsJoined->isNotEmpty() || $student->groupsLed->isNotEmpty();
                                    @endphp
                                    @if($hasGroup)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Đã có nhóm
                                        </span>
                                    @else
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-clock me-1"></i>Chưa có nhóm
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="{{ route('students.show', $student->user_id) }}" 
                                           class="btn btn-sm btn-outline-primary"
                                           title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('students.edit', $student->user_id) }}" 
                                           class="btn btn-sm btn-outline-warning"
                                           title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('students.destroy', $student->user_id) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('Bạn chắc chắn muốn xóa sinh viên {{ $student->name }}?')">
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
                                <td colspan="5" class="text-center py-5">
                                    <i class="fas fa-user-graduate fa-4x text-muted opacity-50 mb-3"></i>
                                    <h5 class="text-muted">Không tìm thấy sinh viên</h5>
                                    <p class="text-muted mb-3">
                                        @if(request()->hasAny(['search', 'class_id']))
                                            Thử điều chỉnh bộ lọc hoặc thêm sinh viên mới
                                        @else
                                            Bắt đầu bằng cách thêm sinh viên mới
                                        @endif
                                    </p>
                                    <a href="{{ route('students.create') }}" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Thêm sinh viên
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($students->hasPages())
            <div class="card-footer bg-white border-top-0 py-3">
                <div class="d-flex justify-content-center">
                    {{ $students->withQueryString()->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-file-excel text-success me-2"></i>
                    Export danh sách sinh viên
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('students.export') }}" method="GET" id="exportForm">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Chọn lớp học</label>
                        <select name="class_id" class="form-select">
                            <option value="">Tất cả lớp</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->class_id }}">
                                    {{ $class->class_name }}
                                    @if($class->subject)
                                        - {{ $class->subject->subject_name }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="alert alert-info border-0 mb-0">
                        <small>
                            <i class="fas fa-info-circle me-2"></i>
                            File Excel sẽ chứa: Họ tên, Email, Lớp, Môn học, Nhóm và Trạng thái
                        </small>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Hủy
                </button>
                <button type="submit" form="exportForm" class="btn btn-success">
                    <i class="fas fa-download me-2"></i>Tải xuống
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .table tbody tr {
        transition: all 0.2s ease;
    }
    
    .table tbody tr:hover {
        background-color: #f8f9fa;
        transform: translateX(2px);
    }
    
    .badge {
        font-weight: 500;
        padding: 0.4em 0.65em;
    }
    
    .btn-group .btn {
        border-radius: 0;
    }
    
    .btn-group .btn:first-child {
        border-top-left-radius: 0.375rem;
        border-bottom-left-radius: 0.375rem;
    }
    
    .btn-group .btn:last-child {
        border-top-right-radius: 0.375rem;
        border-bottom-right-radius: 0.375rem;
    }
</style>
@endsection