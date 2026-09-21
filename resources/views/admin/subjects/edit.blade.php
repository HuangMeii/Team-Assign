@extends('layouts.app')

@section('title', 'Cập nhật Môn học')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Cập nhật Môn học</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.subjects.index') }}">Môn học</a></li>
        <li class="breadcrumb-item active">Cập nhật</li>
    </ol>

    <div class="card mb-4" style="max-width: 800px;">
        <div class="card-header">
            <i class="fas fa-edit me-1"></i>
            Chỉnh sửa: {{ $subject->subject_name }}
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.subjects.update', $subject->subject_id) }}">
                @csrf
                @method('PUT')
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="subject_code" class="form-label">Mã môn học</label>
                        <input type="text" class="form-control" id="subject_code" value="{{ $subject->subject_code }}" readonly tabindex="-1" aria-readonly="true">
                        <input type="hidden" name="subject_code" value="{{ $subject->subject_code }}">
                        <div class="form-text text-muted"><i class="fas fa-lock"></i> Không thể sửa mã môn học.</div>
                    </div>
                    <div class="col-md-5">
                        <label for="subject_name" class="form-label">Tên môn học <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('subject_name') is-invalid @enderror" id="subject_name" name="subject_name" value="{{ old('subject_name', $subject->subject_name) }}" required>
                        @error('subject_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label for="credits" class="form-label">Số tín chỉ <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('credits') is-invalid @enderror" id="credits" name="credits" value="{{ old('credits', $subject->credits) }}" min="1" max="10" required>
                        @error('credits')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Giảng viên được phân công ở cấp <strong>lớp học phần</strong>
                            (Quản lý Lớp học), mỗi lớp học phần do 1 giảng viên quản lý.
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary">Quay lại</a>
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection