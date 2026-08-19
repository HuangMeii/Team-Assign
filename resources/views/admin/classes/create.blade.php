@extends('layouts.app')

@section('title', 'Tạo Lớp học phần mới')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Tạo Lớp học phần</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.classes.index') }}">Lớp học</a></li>
        <li class="breadcrumb-item active">Thêm mới</li>
    </ol>

    <div class="card mb-4" style="max-width: 900px;">
        <div class="card-header">
            <i class="fas fa-plus-square me-1"></i>
            Thông tin lớp học
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.classes.store') }}">
                @csrf
                
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label for="class_name" class="form-label">Tên lớp <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('class_name') is-invalid @enderror" id="class_name" name="class_name" value="{{ old('class_name') }}" placeholder="Ví dụ: DHKTPM18A" required>
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
                                <option value="{{ $subject->subject_id }}" {{ old('subject_id') == $subject->subject_id ? 'selected' : '' }}>
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
                            @foreach($lecturers as $lecturer)
                                <option value="{{ $lecturer->user_id }}" {{ old('lecturer_id') == $lecturer->user_id ? 'selected' : '' }}>
                                    {{ $lecturer->name }} ({{ $lecturer->email }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Bạn có thể chọn giảng viên sau nếu chưa xác định.</div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.classes.index') }}" class="btn btn-secondary">Quay lại</a>
                    <button type="submit" class="btn btn-primary">Lưu Lớp học</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection