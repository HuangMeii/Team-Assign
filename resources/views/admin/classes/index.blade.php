@extends('layouts.app')

@section('title', 'Quản lý Lớp học phần')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Quản lý Lớp học phần</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Lớp học</li>
    </ol>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-chalkboard-teacher me-1"></i>
                Danh sách Lớp học
            </div>
            <a href="{{ route('admin.classes.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i> Thêm mới
            </a>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.classes.index') }}" class="row g-2 mb-4 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Môn học</label>
                    <select name="subject_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Tất cả --</option>
                        @foreach($subjects as $subj)
                            <option value="{{ $subj->subject_id }}" {{ request('subject_id') == $subj->subject_id ? 'selected' : '' }}>
                                {{ $subj->subject_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Giảng viên</label>
                    <select name="lecturer_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Tất cả --</option>
                        @foreach($lecturers as $lec)
                            <option value="{{ $lec->user_id }}" {{ request('lecturer_id') == $lec->user_id ? 'selected' : '' }}>
                                {{ $lec->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">Tìm kiếm</label>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Nhập tên lớp..." value="{{ request('search') }}">
                        <button class="btn btn-secondary" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.classes.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-undo me-1"></i> Reset
                    </a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tên lớp</th>
                            <th>Môn học</th>
                            <th>Giảng viên</th>
                            <th class="text-center">Thành viên</th>
                            <th class="text-center">Nhóm</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-center" style="width: 160px;">Hành động</th>
                        </tr>

                    </thead>
                    <tbody>
                        @forelse($classes as $class)
                            @php
                                $toggleAction = $class->is_active ? 'khóa' : 'mở khóa';
                            @endphp
                            <tr>

                                <td class="fw-bold text-primary">{{ $class->class_name }}</td>
                                <td>
                                    @if($class->subject)
                                        <div>{{ $class->subject->subject_name }}</div>
                                        <small class="text-muted fst-italic">{{ $class->subject->subject_code }}</small>
                                    @else
                                        <span class="text-danger fst-italic">Chưa gán môn</span>
                                    @endif
                                </td>
                                <td>
                                    @if($class->lecturer)
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width: 30px; height: 30px; font-size: 12px;">
                                                {{ strtoupper(substr($class->lecturer->name, 0, 1)) }}
                                            </div>
                                            <span>{{ $class->lecturer->name }}</span>
                                        </div>
                                    @else
                                        <span class="badge bg-warning text-dark">Chưa phân công</span>
                                    @endif
                                </td>
                                
                                <td class="text-center">
                                    
                                        {{ $class->users_count }} 
                                  
                                </td>
                                
                                <td class="text-center">
                                    <span class="badge bg-info text-dark">{{ $class->groups_count }}</span>
                                </td>

                                <td class="text-center">
                                    @if($class->is_active)
                                        <span class="badge bg-success">Hoạt động</span>
                                    @else
                                        <span class="badge bg-danger">Đã khóa</span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.classes.edit', $class->class_id) }}" class="btn btn-warning btn-sm" title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.classes.toggle-active', $class->class_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn {{ $toggleAction }} lớp này?');">


                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm {{ $class->is_active ? 'btn-secondary' : 'btn-success' }}" title="{{ $class->is_active ? 'Khóa lớp' : 'Mở khóa lớp' }}">
                                                <i class="fas {{ $class->is_active ? 'fa-lock' : 'fa-unlock' }}"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.classes.destroy', $class->class_id) }}" method="POST" class="d-inline" onsubmit="return confirm('CẢNH BÁO: Bạn có chắc chắn muốn xóa lớp {{ $class->class_name }}? Hành động này không thể hoàn tác!');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Xóa lớp">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">

                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                    Không tìm thấy lớp học phần nào phù hợp.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                {{ $classes->links() }}
            </div>
        </div>
    </div>
</div>
@endsection