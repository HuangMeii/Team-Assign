@extends('layouts.app')

@section('title', 'Quản lý lớp học')

@section('content')
    <div class="container-fluid px-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h2 class="fw-bold mb-2">Quản lý lớp học</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Lớp học</li>
                            </ol>
                        </nav>
                    </div>
                    <a href="{{ route('classes.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Tạo lớp mới
                    </a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <form method="GET" action="{{ route('classes.index') }}">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-search me-1"></i> Tìm kiếm
                            </label>
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Tên lớp, môn học..." class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-book me-1"></i> Môn học
                            </label>
                            <select name="subject_id" class="form-select">
                                <option value="">Tất cả môn học</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->subject_id }}" {{ request('subject_id') == $subject->subject_id ? 'selected' : '' }}>
                                        {{ $subject->subject_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i> Tìm kiếm
                        </button>
                        <a href="{{ route('classes.index') }}" class="btn btn-secondary">
                            <i class="fas fa-redo me-2"></i> Đặt lại
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Classes List -->
        @if($classes->isEmpty())
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-chalkboard fa-4x text-muted opacity-50 mb-3"></i>
                    <h5 class="text-muted mb-2">Chưa có lớp học nào</h5>
                    <p class="text-muted mb-4">Hãy tạo lớp học đầu tiên</p>
                    <a href="{{ route('classes.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Tạo lớp mới
                    </a>
                </div>
            </div>
        @else
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-4 py-3">Tên lớp</th>
                                    <th class="px-4 py-3">Môn học</th>
                                    <th class="px-4 py-3 text-center">Sinh viên</th>
                                    <th class="px-4 py-3 text-center">Nhóm</th>
                                    <th class="px-4 py-3 text-center">Đề tài</th>
                                    <th class="px-4 py-3 text-end">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($classes as $class)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary bg-opacity-10 rounded p-2 me-3">
                                                    <i class="fas fa-chalkboard text-primary"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold">{{ $class->class_name }}</h6>
                                                    <small class="text-muted">{{ $class->subject->subject_code ?? 'N/A' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-muted">{{ $class->subject->subject_name ?? 'N/A' }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="badge bg-info rounded-pill">
                                                {{ $class->users->count() }} SV
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="badge bg-primary rounded-pill">
                                                {{ $class->groups->count() }} nhóm
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="badge bg-success rounded-pill">
                                                {{ $class->topics->count() }} đề tài
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-end">
                                            <div class="btn-group">
                                                <a href="{{ route('classes.show', $class->class_id) }}"
                                                    class="btn btn-sm btn-outline-primary" title="Chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('classes.edit', $class->class_id) }}"
                                                    class="btn btn-sm btn-outline-warning" title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-class-id="{{ $class->class_id }}" onclick="confirmDelete(this)"
                                                    title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>

                                            <form id="delete-form-{{ $class->class_id }}"
                                                action="{{ route('classes.destroy', $class->class_id) }}" method="POST"
                                                class="d-none">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            @if($classes->hasPages())
                <div class="mt-4">
                    <div class="d-flex justify-content-center">
                        {{ $classes->links() }}
                    </div>
                </div>
            @endif
        @endif
    </div>

    <script>
        function confirmDelete(button) {
            const classId = button.getAttribute('data-class-id');

            if (confirm('Bạn có chắc chắn muốn xóa lớp này?\n\nLưu ý: Không thể xóa lớp đã có sinh viên hoặc nhóm!')) {
                document.getElementById('delete-form-' + classId).submit();
            }
        }
    </script>
    <style>
        .bg-opacity-10 {
            --bs-bg-opacity: 0.1;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .btn-group .btn {
            padding: 0.25rem 0.5rem;
        }
    </style>
@endsection