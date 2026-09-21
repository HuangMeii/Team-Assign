@extends('layouts.app')

@section('title', 'Import Môn học')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Import Môn học</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.subjects.index') }}">Môn học</a></li>
        <li class="breadcrumb-item active">Import</li>
    </ol>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-file-import me-1"></i> Form Import Môn học</span>
            <a href="{{ route('admin.subjects.download-template') }}" class="btn btn-success btn-sm">
                <i class="fas fa-download me-1"></i> Tải file mẫu
            </a>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                <i class="fas fa-info-circle me-1"></i>
                File Excel/CSV cần có cột tiêu đề: <strong>ten_mon, so_tc</strong>.
                Mã môn sẽ được hệ thống tự sinh; tên môn đã có sẽ được cập nhật số tín chỉ.
            </p>

            <form method="POST" action="{{ route('admin.subjects.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-bold">Chọn file (Excel/CSV)</label>
                    <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" required
                           accept=".xlsx,.xls,.csv">
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('admin.subjects.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Hủy
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-1"></i> Import ngay
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
