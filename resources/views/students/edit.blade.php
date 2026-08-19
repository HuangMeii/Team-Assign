@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-white">
                    <h4 class="mb-0"><i class="fas fa-user-edit"></i> Chỉnh sửa sinh viên</h4>
                </div>

                <div class="card-body p-4">
                    <form action="{{ route('students.update', $student->user_id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Name -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-user text-primary"></i> Họ và tên <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   name="name" 
                                   value="{{ old('name', $student->name) }}"
                                   class="form-control form-control-lg @error('name') is-invalid @enderror"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-envelope text-success"></i> Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" 
                                   name="email" 
                                   value="{{ old('email', $student->email) }}"
                                   class="form-control form-control-lg @error('email') is-invalid @enderror"
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-lock text-danger"></i> Mật khẩu mới
                            </label>
                            <input type="password" 
                                   name="password" 
                                   class="form-control form-control-lg @error('password') is-invalid @enderror"
                                   placeholder="Để trống nếu không muốn thay đổi">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> Chỉ nhập nếu muốn thay đổi mật khẩu
                            </small>
                        </div>

                        <!-- Classes -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-chalkboard-teacher text-warning"></i> Lớp học phần <span class="text-danger">*</span>
                            </label>
                            <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                @foreach($classes as $class)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               name="class_ids[]" 
                                               value="{{ $class->class_id }}"
                                               id="class_{{ $class->class_id }}"
                                               {{ (is_array(old('class_ids')) ? in_array($class->class_id, old('class_ids')) : $student->classes->contains($class->class_id)) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="class_{{ $class->class_id }}">
                                            {{ $class->class_name }} - {{ $class->subject->subject_name ?? '' }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('class_ids')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning btn-lg text-white">
                                <i class="fas fa-save"></i> Cập nhật
                            </button>
                            <a href="{{ route('students.show', $student->user_id) }}" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times"></i> Hủy
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="card shadow-sm mt-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Vùng nguy hiểm</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Xóa sinh viên này sẽ xóa tất cả dữ liệu liên quan. Hành động này không thể hoàn tác.
                    </p>
                    <form action="{{ route('students.destroy', $student->user_id) }}" 
                          method="POST" 
                          onsubmit="return confirm('Bạn chắc chắn muốn xóa sinh viên này? Hành động này không thể hoàn tác!')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Xóa sinh viên vĩnh viễn
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection