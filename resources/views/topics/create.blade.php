@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-plus-circle"></i> Thêm đề tài mới</h4>
                </div>

                <div class="card-body p-4">
                    <form action="{{ route('topics.store') }}" method="POST">
                        @csrf

                        <!-- Topic Name -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-heading text-primary"></i> Tên đề tài <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   name="name" 
                                   value="{{ old('name') }}"
                                   class="form-control form-control-lg @error('name') is-invalid @enderror"
                                   placeholder="Nhập tên đề tài..."
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Class -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-chalkboard-teacher text-info"></i> Lớp học phần <span class="text-danger">*</span>
                            </label>
                            <select name="class_id" 
                                    class="form-select form-select-lg @error('class_id') is-invalid @enderror"
                                    required>
                                <option value="">-- Chọn lớp học phần --</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->class_id }}" {{ old('class_id') == $class->class_id ? 'selected' : '' }}>
                                        {{ $class->class_name }} - {{ $class->subject->subject_name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Môn học sẽ tự động được gán theo lớp học phần
                            </small>
                            @error('class_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-align-left text-success"></i> Mô tả <span class="text-danger">*</span>
                            </label>
                            <textarea name="description" 
                                      rows="4"
                                      class="form-control @error('description') is-invalid @enderror"
                                      placeholder="Mô tả chi tiết về đề tài..."
                                      required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Goal -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-bullseye text-warning"></i> Mục tiêu
                            </label>
                            <textarea name="goal" 
                                      rows="3"
                                      class="form-control"
                                      placeholder="Mục tiêu của đề tài...">{{ old('goal') }}</textarea>
                        </div>

                        <!-- Requirements -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-clipboard-check text-danger"></i> Yêu cầu
                            </label>
                            <textarea name="requirements" 
                                      rows="3"
                                      class="form-control"
                                      placeholder="Các yêu cầu cần thiết...">{{ old('requirements') }}</textarea>
                        </div>

                        <!-- Member Limits -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-user-minus text-primary"></i> Số thành viên tối thiểu
                                </label>
                                <input type="number" 
                                       name="min_members" 
                                       value="{{ old('min_members', 1) }}"
                                       min="1"
                                       class="form-control @error('min_members') is-invalid @enderror"
                                       placeholder="VD: 3">
                                <small class="form-text text-muted">Số thành viên tối thiểu để nhóm được đăng ký đề tài</small>
                                @error('min_members')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-user-plus text-primary"></i> Số thành viên tối đa
                                </label>
                                <input type="number" 
                                       name="max_members" 
                                       value="{{ old('max_members', 5) }}"
                                       min="1"
                                       class="form-control @error('max_members') is-invalid @enderror"
                                       placeholder="VD: 5">
                                <small class="form-text text-muted">Số thành viên tối đa của nhóm</small>
                                @error('max_members')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Registration Deadline -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-calendar-times text-danger"></i> Hạn đăng ký đề tài
                            </label>
                            <input type="datetime-local" 
                                   name="registration_deadline" 
                                   value="{{ old('registration_deadline') }}"
                                   class="form-control @error('registration_deadline') is-invalid @enderror">
                            <small class="form-text text-muted">Thời hạn cuối để các nhóm đăng ký đề tài này (để trống nếu không giới hạn)</small>
                            @error('registration_deadline')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Buttons -->

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Lưu đề tài
                            </button>
                            <a href="{{ route('topics.index') }}" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times"></i> Hủy
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        border: none;
        border-radius: 15px;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    
    .form-label {
        color: #333;
    }
</style>
@endsection