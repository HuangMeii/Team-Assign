@extends('layouts.app')

@section('title', 'Tạo lớp học mới')

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold mb-2">Tạo lớp học mới</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('classes.index') }}">Lớp học</a></li>
                    <li class="breadcrumb-item active">Tạo mới</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-chalkboard text-primary me-2"></i>
                        Thông tin lớp học
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('classes.store') }}" method="POST">
                        @csrf

                        <!-- Class Name -->
                        <div class="mb-4">
                            <label for="class_name" class="form-label fw-semibold">
                                Tên lớp <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('class_name') is-invalid @enderror" 
                                   id="class_name" 
                                   name="class_name" 
                                   value="{{ old('class_name') }}"
                                   placeholder="VD: Lớp 01 - Học kỳ 1"
                                   required>
                            @error('class_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Tên lớp phải là duy nhất</small>
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
                                    <option value="{{ $subject->subject_id }}" {{ old('subject_id') == $subject->subject_id ? 'selected' : '' }}>
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
                                <i class="fas fa-save me-2"></i>Tạo lớp
                            </button>
                            <a href="{{ route('classes.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Hủy
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Help Card -->
        <div class="col-lg-4 col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-info-circle text-info me-2"></i>
                        Hướng dẫn
                    </h6>
                    <ul class="text-muted small mb-0">
                        <li class="mb-2">Tên lớp phải là duy nhất trong hệ thống</li>
                        <li class="mb-2">Chọn môn học phù hợp cho lớp</li>
                        <li class="mb-2">Sau khi tạo, bạn có thể thêm sinh viên vào lớp</li>
                        <li>Lớp học có thể chứa nhiều nhóm và đề tài</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection