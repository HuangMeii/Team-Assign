@extends('layouts.app')

@section('title', 'Thêm sinh viên')

@section('content')
<div class="container-fluid px-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Sinh viên</a></li>
            <li class="breadcrumb-item active">Thêm sinh viên</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-user-plus text-primary me-2"></i>
                        Thêm sinh viên
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('students.store') }}" method="POST" id="studentForm">
                        @csrf

                        <!-- Email field with check button -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                Email <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       name="email" 
                                       id="emailInput"
                                       value="{{ old('email') }}" 
                                       placeholder="example@email.com"
                                       required>
                                <button type="button" class="btn btn-outline-primary" id="checkEmailBtn">
                                    <i class="fas fa-search me-2"></i>Kiểm tra
                                </button>
                            </div>
                            @error('email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Nhập email và nhấn "Kiểm tra" để xem sinh viên đã tồn tại chưa</small>
                        </div>

                        <!-- Student Info Result (hidden by default) -->
                        <div id="existingStudentInfo" class="alert alert-info border-0 mb-4" style="display: none;">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-info-circle fa-2x me-3"></i>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold mb-2">Sinh viên đã tồn tại trong hệ thống</h6>
                                    <p class="mb-1"><strong>Tên:</strong> <span id="existingName"></span></p>
                                    <p class="mb-1"><strong>Email:</strong> <span id="existingEmail"></span></p>
                                    <p class="mb-2"><strong>Lớp hiện tại:</strong> <span id="existingClasses"></span></p>
                                    <p class="mb-0 small text-muted">
                                        <i class="fas fa-check-circle me-1"></i>
                                        Bạn có thể thêm sinh viên này vào lớp mới. Thông tin sẽ được cập nhật nếu cần.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Name field -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                Họ và tên <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   name="name" 
                                   id="nameInput"
                                   value="{{ old('name') }}" 
                                   placeholder="Nguyễn Văn A"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password field -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                Mật khẩu <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control @error('password') is-invalid @enderror" 
                                       name="password" 
                                       id="passwordInput"
                                       placeholder="Tối thiểu 6 ký tự"
                                       required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Sinh viên sẽ được yêu cầu đổi mật khẩu khi đăng nhập lần đầu</small>
                        </div>

                        <!-- Classes selection -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                Lớp học <span class="text-danger">*</span>
                            </label>
                            <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                @forelse($classes as $class)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               name="class_ids[]" 
                                               value="{{ $class->class_id }}" 
                                               id="class{{ $class->class_id }}"
                                               {{ in_array($class->class_id, old('class_ids', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="class{{ $class->class_id }}">
                                            <strong>{{ $class->class_name }}</strong>
                                            @if($class->subject)
                                                <span class="text-muted">- {{ $class->subject->subject_name }}</span>
                                            @endif
                                        </label>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">Chưa có lớp học nào</p>
                                @endforelse
                            </div>
                            @error('class_ids')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Action buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Lưu
                            </button>
                            <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Hủy
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Help sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-question-circle text-info me-2"></i>
                        Hướng dẫn
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Email đã tồn tại?</strong>
                            <p class="text-muted small mb-0">Sinh viên sẽ được cập nhật và thêm vào lớp mới</p>
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Nhiều lớp</strong>
                            <p class="text-muted small mb-0">Có thể chọn nhiều lớp cùng lúc</p>
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Mật khẩu mặc định</strong>
                            <p class="text-muted small mb-0">Sinh viên sẽ đổi khi đăng nhập lần đầu</p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('passwordInput');
    
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });

    // Check email existence
    const checkEmailBtn = document.getElementById('checkEmailBtn');
    const emailInput = document.getElementById('emailInput');
    const nameInput = document.getElementById('nameInput');
    const existingStudentInfo = document.getElementById('existingStudentInfo');
    
    checkEmailBtn.addEventListener('click', function() {
        const email = emailInput.value.trim();
        
        if (!email) {
            alert('Vui lòng nhập email');
            return;
        }

        // Show loading
        checkEmailBtn.disabled = true;
        checkEmailBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang kiểm tra...';

        // AJAX request
        fetch(`/check-student-email?email=${encodeURIComponent(email)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    // Student exists
                    document.getElementById('existingName').textContent = data.student.name;
                    document.getElementById('existingEmail').textContent = data.student.email;
                    document.getElementById('existingClasses').textContent = data.student.classes || 'Chưa có lớp';
                    
                    existingStudentInfo.style.display = 'block';
                    
                    // Pre-fill name
                    nameInput.value = data.student.name;
                    
                } else {
                    // Student doesn't exist
                    existingStudentInfo.style.display = 'none';
                    alert('Email chưa tồn tại trong hệ thống. Bạn có thể tạo sinh viên mới.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra. Vui lòng thử lại.');
            })
            .finally(() => {
                checkEmailBtn.disabled = false;
                checkEmailBtn.innerHTML = '<i class="fas fa-search me-2"></i>Kiểm tra';
            });
    });
});
</script>
@endsection