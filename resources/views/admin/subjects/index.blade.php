@extends('layouts.app')

@section('title', 'Quản lý Môn học')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Quản lý Môn học</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Môn học</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-book me-1"></i>
                Danh sách Môn học
            </div>
            <a href="{{ route('admin.subjects.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i> Thêm mới
            </a>
        </div>
        <div class="card-body">
            {{-- Search Form --}}
            <form method="GET" action="{{ route('admin.subjects.index') }}" class="row g-3 mb-4">
                <div class="col-md-6 offset-md-6">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Tìm mã môn hoặc tên môn..." value="{{ request('search') }}">
                        <button class="btn btn-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Mã MH</th>
                            <th>Tên Môn học</th>
              
                            <th class="text-center">Số lớp đang mở</th>
                            <th class="text-center" style="width: 150px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subjects as $subject)
                            <tr>
                                <td class="fw-bold">{{ $subject->subject_code }}</td>
                                <td>{{ $subject->subject_name }}</td>
                              
                                <td class="text-center">
                                    @if($subject->classes_count > 0)
                                        <span class="badge bg-success">{{ $subject->classes_count }} lớp</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.subjects.edit', $subject->subject_id) }}" class="btn btn-warning btn-sm" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <form action="{{ route('admin.subjects.destroy', $subject->subject_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa môn học này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Chưa có dữ liệu môn học.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="d-flex justify-content-end">
                {{ $subjects->links() }}
            </div>
        </div>
    </div>
</div>
@endsection