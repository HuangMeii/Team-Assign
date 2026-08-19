@extends('layouts.app')

@section('title', 'Cập nhật Lớp học')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Cập nhật Lớp học</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.classes.index') }}">Lớp học</a></li>
        <li class="breadcrumb-item active">Cập nhật</li>
    </ol>

    <div class="card mb-4" style="max-width: 900px;">
        <div class="card-header">
            <i class="fas fa-edit me-1"></i>
            Chỉnh sửa: {{ $class->class_name }}
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.classes.update', $class->class_id) }}">
                @csrf
                @method('PUT')
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="class_name" class="form-label">Tên lớp <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('class_name') is-invalid @enderror" id="class_name" name="class_name" value="{{ old('class_name', $class->class_name) }}" required>
                        @error('class_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="subject_id" class="form-label">Môn học <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-select @error('subject_id') is-invalid @enderror" required>
                            <option value="">-- Chọn môn học --</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->subject_id }}" {{ old('subject_id', $class->subject_id) == $subject->subject_id ? 'selected' : '' }}>
                                    {{ $subject->subject_code }} - {{ $subject->subject_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('subject_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="lecturer_id" class="form-label fw-bold text-primary">Phân công Giảng viên</label>
                        <select name="lecturer_id" class="form-select @error('lecturer_id') is-invalid @enderror">
                            <option value="">-- Chưa phân công --</option>
                            @php
                                $currentLecturerId = $class->lecturers->isNotEmpty() ? $class->lecturers->first()->user_id : null;
                            @endphp
                            @foreach($lecturers as $lecturer)
                                <option value="{{ $lecturer->user_id }}" {{ old('lecturer_id', $currentLecturerId) == $lecturer->user_id ? 'selected' : '' }}>
                                    {{ $lecturer->name }} ({{ $lecturer->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('lecturer_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.classes.index') }}" class="btn btn-secondary">Quay lại</a>
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection