@extends('layouts.user')

@section('title', 'Tạo nhóm mới')

@push('styles')
<style>
    .create-group-container {
        max-width: 900px;
        margin: 0 auto;
    }
    
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
        color: white;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }
    
    .form-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        overflow: hidden;
        margin-bottom: 2rem;
    }
    
    .form-card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 1.5rem;
        color: white;
    }
    
    .form-card-body {
        padding: 2rem;
    }
    
    .form-control, .form-select {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        font-size: 1rem;
        transition: all 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
    }
    
    .input-group-icon {
        position: relative;
    }
    
    .input-group-icon i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #667eea;
        z-index: 10;
    }
    
    .input-group-icon .form-control,
    .input-group-icon .form-select {
        padding-left: 45px;
    }
    
    .info-box {
        background: linear-gradient(135deg, #e0e7ff 0%, #f3e8ff 100%);
        border-left: 4px solid #667eea;
        border-radius: 10px;
        padding: 1.5rem;
        margin-top: 1.5rem;
    }
    
    .info-box-title {
        color: #667eea;
        font-weight: 600;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .info-item {
        display: flex;
        align-items: start;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
        padding: 0.5rem;
        background: rgba(255,255,255,0.6);
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .info-item:hover {
        background: rgba(255,255,255,0.9);
        transform: translateX(5px);
    }
    
    .info-item:last-child {
        margin-bottom: 0;
    }
    
    .info-item i {
        font-size: 1.2rem;
        margin-top: 2px;
    }
    
    .btn-create {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 0.875rem 2rem;
        border-radius: 10px;
        color: white;
        font-weight: 600;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    
    .btn-create:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .btn-create:active {
        transform: translateY(0);
    }
    
    .btn-cancel {
        background: white;
        border: 2px solid #e9ecef;
        padding: 0.875rem 2rem;
        border-radius: 10px;
        color: #6c757d;
        font-weight: 600;
        font-size: 1.1rem;
        transition: all 0.3s ease;
    }
    
    .btn-cancel:hover {
        background: #f8f9fa;
        border-color: #dee2e6;
        color: #495057;
    }
    
    .feature-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-top: 1.5rem;
    }
    
    .feature-item {
        background: white;
        padding: 1.5rem;
        border-radius: 12px;
        text-align: center;
        transition: all 0.3s ease;
        border: 2px solid #f8f9fa;
    }
    
    .feature-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        border-color: #667eea;
    }
    
    .feature-icon {
        width: 60px;
        height: 60px;
        margin: 0 auto 1rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .feature-icon.purple {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .feature-icon.green {
        background: linear-gradient(135deg, #06d6a0 0%, #05c896 100%);
        color: white;
    }
    
    .feature-icon.orange {
        background: linear-gradient(135deg, #ffa94d 0%, #ff8c42 100%);
        color: white;
    }
    
    .feature-icon.blue {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
    }
    
    .alert-warning-custom {
        background: linear-gradient(135deg, #fff4e6 0%, #ffe8cc 100%);
        border-left: 4px solid #ff8c42;
        border-radius: 10px;
        padding: 1.5rem;
    }
</style>
@endpush

@section('content')
<div class="create-group-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mb-2 fw-bold">
                    <i class="fas fa-users-cog me-2"></i>
                    Tạo nhóm mới
                </h1>
                <p class="mb-0 opacity-90">
                    Bắt đầu hành trình học tập cùng bạn bè của bạn
                </p>
            </div>
            <a href="{{ route('user.my_groups') }}" class="btn btn-light btn-lg">
                <i class="fas fa-arrow-left me-2"></i> Quay lại
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="form-card">
        <div class="form-card-header">
            <h4 class="mb-0 fw-bold">
                <i class="fas fa-edit me-2"></i>
                Thông tin nhóm
            </h4>
        </div>
        
        <div class="form-card-body">
            <form action="{{ route('user.store_group') }}" method="POST">
                @csrf

                <!-- Group Name -->
                <div class="mb-4">
                    <label class="form-label fw-semibold mb-3">
                        Tên nhóm <span class="text-danger">*</span>
                    </label>
                    <div class="input-group-icon">
                        <i class="fas fa-users"></i>
                        <input type="text" 
                               class="form-control @error('group_name') is-invalid @enderror" 
                               name="group_name" 
                               value="{{ old('group_name') }}"
                               placeholder="Nhập tên nhóm của bạn (VD: Team Alpha, Nhóm 1...)" 
                               required>
                        @error('group_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-lightbulb text-warning me-1"></i>
                        Đặt tên ngắn gọn, dễ nhớ và thể hiện tinh thần nhóm
                    </small>
                </div>

                <!-- Class Selection -->
                <div class="mb-4">
                    <label class="form-label fw-semibold mb-3">
                        Chọn lớp học <span class="text-danger">*</span>
                    </label>
                    
                    @if($userClasses->isEmpty())
                        <div class="alert-warning-custom">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-exclamation-triangle fa-2x text-warning me-3"></i>
                                <div>
                                    <h6 class="fw-bold mb-2">Chưa có lớp học</h6>
                                    <p class="mb-0">Bạn chưa được thêm vào lớp học nào. Vui lòng liên hệ giảng viên để được thêm vào lớp.</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="input-group-icon">
                            <i class="fas fa-chalkboard"></i>
                            <select class="form-select @error('class_id') is-invalid @enderror" 
                                    name="class_id" 
                                    required>
                                <option value="">-- Chọn lớp học của bạn --</option>
                                @foreach($userClasses as $class)
                                    <option value="{{ $class->class_id }}" {{ old('class_id') == $class->class_id ? 'selected' : '' }}>
                                        {{ $class->class_name }}
                                        @if($class->subject)
                                            - {{ $class->subject->subject_name }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('class_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-info-circle text-info me-1"></i>
                            Nhóm sẽ được tạo trong lớp học này
                        </small>
                    @endif
                </div>

                <!-- Info Box -->
                <div class="info-box">
                    <div class="info-box-title">
                        <i class="fas fa-info-circle fa-lg"></i>
                        <span>Điều bạn cần biết</span>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-crown text-warning"></i>
                        <div>
                            <strong>Bạn sẽ là trưởng nhóm</strong>
                            <div class="small text-muted">Có toàn quyền quản lý và điều hành nhóm</div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-user-shield text-primary"></i>
                        <div>
                            <strong>Mỗi người chỉ làm trưởng 1 nhóm</strong>
                            <div class="small text-muted">Bạn không thể tạo thêm nhóm khác với vai trò trưởng nhóm</div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-user-plus text-success"></i>
                        <div>
                            <strong>Mời thêm thành viên</strong>
                            <div class="small text-muted">Sau khi tạo, bạn có thể mời bạn bè cùng lớp tham gia</div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-tasks text-info"></i>
                        <div>
                            <strong>Quyền đăng ký đề tài</strong>
                            <div class="small text-muted">Trưởng nhóm có quyền chọn và đăng ký đề tài cho nhóm</div>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="d-flex gap-3 justify-content-end mt-4 pt-3">
                    <a href="{{ route('user.my_groups') }}" class="btn-cancel">
                        <i class="fas fa-times me-2"></i>
                        Hủy bỏ
                    </a>
                    <button type="submit" class="btn-create" {{ $userClasses->isEmpty() ? 'disabled' : '' }}>
                        <i class="fas fa-check-circle me-2"></i>
                        Tạo nhóm ngay
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Features Section -->
    <div class="form-card">
        <div class="form-card-body">
            <h5 class="fw-bold mb-3 text-center">
                <i class="fas fa-star text-warning me-2"></i>
                Sau khi tạo nhóm thành công
            </h5>
            
            <div class="feature-grid">
                <div class="feature-item">
                    <div class="feature-icon green">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h6 class="fw-semibold mb-2">Mời thành viên</h6>
                    <p class="small text-muted mb-0">Mời bạn bè cùng lớp vào nhóm</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon purple">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h6 class="fw-semibold mb-2">Đăng ký đề tài</h6>
                    <p class="small text-muted mb-0">Chọn đề tài phù hợp cho nhóm</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon orange">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <h6 class="fw-semibold mb-2">Quản lý nhóm</h6>
                    <p class="small text-muted mb-0">Duyệt yêu cầu tham gia</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon blue">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h6 class="fw-semibold mb-2">Cộng tác</h6>
                    <p class="small text-muted mb-0">Làm việc cùng thành viên</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection