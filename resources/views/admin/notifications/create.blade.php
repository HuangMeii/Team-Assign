@extends('layouts.admin')

@section('title', 'Gửi thông báo hệ thống')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Gửi thông báo hệ thống</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Thông báo</li>
    </ol>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-bullhorn me-1"></i>
            Soạn thông báo
        </div>
        <div class="card-body">
            <form action="{{ route('admin.notifications.send') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-bold">Tiêu đề <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}"
                        class="form-control @error('title') is-invalid @enderror"
                        placeholder="VD: Cập nhật đề tài học kỳ mới" required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Nội dung <span class="text-danger">*</span></label>
                    <textarea name="message" rows="5"
                        class="form-control @error('message') is-invalid @enderror"
                        placeholder="Nhập nội dung thông báo..." required>{{ old('message') }}</textarea>
                    @error('message')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Người nhận</label>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="recipient_scope" id="scope_all" value="all" checked
                            onchange="toggleLecturerSelect(false)">
                        <label class="form-check-label" for="scope_all">
                            Tất cả giảng viên
                        </label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="recipient_scope" id="scope_specific" value="specific"
                            onchange="toggleLecturerSelect(true)">
                        <label class="form-check-label" for="scope_specific">
                            Chọn giảng viên cụ thể
                        </label>
                    </div>

                    <div id="lecturerSelect" class="border rounded p-3 bg-light d-none">
                        <p class="small text-muted mb-2">Đánh dấu các giảng viên muốn gửi:</p>
                        @forelse($lecturers as $lecturer)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                    name="lecturer_ids[]" value="{{ $lecturer->user_id }}"
                                    id="lec_{{ $lecturer->user_id }}">
                                <label class="form-check-label" for="lec_{{ $lecturer->user_id }}">
                                    {{ $lecturer->name }}
                                    <small class="text-muted">({{ $lecturer->email }})</small>
                                </label>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">Chưa có giảng viên nào trong hệ thống.</p>
                        @endforelse
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-1"></i> Gửi thông báo
                </button>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    <i class="fas fa-times me-1"></i> Hủy
                </a>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleLecturerSelect(show) {
        const box = document.getElementById('lecturerSelect');
        if (show) {
            box.classList.remove('d-none');
        } else {
            box.classList.add('d-none');
        }
    }
</script>
@endsection
