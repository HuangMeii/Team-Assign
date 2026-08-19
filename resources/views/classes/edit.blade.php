@extends('layouts.app')

@section('title', 'Chỉnh sửa lớp học')

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold mb-2">Chỉnh sửa lớp học</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('classes.index') }}">Lớp học</a></li>
                    <li class="breadcrumb-item active">{{ $class->class_name }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-edit text-warning me-2"></i>
                        Cập nhật thông tin
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('classes.update', $class->class_id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Class Name -->
                        <div class="mb-4">
                            <label for="class_name" class="form-label fw-semibold">
                                Tên lớp <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('class_name') is-invalid @enderror" 
                                   id="class_name" 
                                   name="class_name" 
                                   value="{{ old('class_name', $class->class_name) }}"
                                   required>
                            @error('class_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Subject -->
                        <div class="mb-4">
                            <label for="subject_id" class="form-label fw-semibold">
                                Môn học <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('subject_id') is-invalid @enderror" 
                                    id="subject_id" 
                                    name="subject_id" 
                                    required>
                                <option value="">-- Chọn môn học --</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->subject_id }}" 
                                            {{ old('subject_id', $class->subject_id) == $subject->subject_id ? 'selected' : '' }}>
                                        {{ $subject->subject_code }} - {{ $subject->subject_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('subject_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Cập nhật
                            </button>
                            <a href="{{ route('classes.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Hủy
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Info Card -->
        <div class="col-lg-4 col-xl-6">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-chart-bar text-primary me-2"></i>
                        Thống kê
                    </h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Sinh viên:</span>
                        <span class="fw-bold">{{ $class->users->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Nhóm:</span>
                        <span class="fw-bold">{{ $class->groups->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Đề tài:</span>
                        <span class="fw-bold">{{ $class->topics->count() }}</span>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        Lưu ý
                    </h6>
                    <ul class="text-muted small mb-0">
                        <li class="mb-2">Thay đổi môn học có thể ảnh hưởng đến đề tài của lớp</li>
                        <li class="mb-2">Không thể xóa lớp đã có sinh viên hoặc nhóm</li>
                        <li>Tên lớp phải là duy nhất</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection