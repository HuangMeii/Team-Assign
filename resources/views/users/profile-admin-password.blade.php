@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4 fw-bold">Thiết lập tài khoản</h2>

            {{-- THANH TAB CHUYỂN HƯỚNG --}}
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link text-secondary" href="{{ route('users.profile.info') }}">Thông tin chung</a>
                </li>
                <li class="nav-item">
                    {{-- Class 'active' được đặt ở đây --}}
                    <a class="nav-link active fw-bold" href="#">Đổi mật khẩu</a>
                </li>
            </ul>

            {{-- NỘI DUNG FORM MẬT KHẨU --}}
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h5 class="card-title mb-4">Thay đổi mật khẩu</h5>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    {{-- Bugfix C3 [R34]: Hiển thị lỗi đổi mật khẩu --}}
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('users.password.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        @if(request()->filled('token') || old('reset_token'))
                            <input type="hidden" name="reset_token" value="{{ old('reset_token', request('token')) }}">
                            <input type="hidden" name="reset_email" value="{{ old('reset_email', request('email')) }}">
                        @endif

                        @if(!session('password_reset_verified') && !request()->filled('token') && !old('reset_token'))
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu hiện tại</label>
                            <div class="position-relative">
                                <input type="password" name="current_password" id="currentPassword" class="form-control @error('current_password') is-invalid @enderror">
                                <button type="button" class="password-toggle" onclick="togglePassword('currentPassword', this)" tabindex="-1" aria-label="Hiện/ẩn mật khẩu">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        @else
                            <div class="alert alert-info py-2">Bạn vừa xác nhận qua email, không cần nhập mật khẩu cũ.</div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">Mật khẩu mới</label>
                            <div class="position-relative">
                                <input type="password" name="new_password" id="newPassword" class="form-control @error('new_password') is-invalid @enderror">
                                <button type="button" class="password-toggle" onclick="togglePassword('newPassword', this)" tabindex="-1" aria-label="Hiện/ẩn mật khẩu">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('new_password')
                                <div class="password-notice"><i class="fas fa-info-circle me-1"></i>{{ $message }}</div>
                            @else
                                <small class="text-muted">Mật khẩu phải có ít nhất 6 ký tự.</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Xác nhận mật khẩu mới</label>
                            <div class="position-relative">
                                <input type="password" name="new_password_confirmation" id="newPasswordConfirmation" class="form-control">
                                <button type="button" class="password-toggle" onclick="togglePassword('newPasswordConfirmation', this)" tabindex="-1" aria-label="Hiện/ẩn mật khẩu">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-warning px-4">Đổi mật khẩu</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0 mt-4">
                <div class="card-body p-4">
                    <h5 class="card-title">Lịch sử đổi mật khẩu</h5>
                    @forelse($user->passwordHistories()->with('changer')->latest()->get() as $history)
                        <p class="mb-1">{{ $history->created_at->format('d/m/Y H:i') }} - {{ $history->changer?->name ?? 'Đặt lại qua email' }}</p>
                    @empty
                        <p class="text-muted mb-0">Chưa có lịch sử.</p>
                    @endforelse
                </div>
            </div>

            {{-- Quên mật khẩu: gửi email chứa liên kết xác thực để đặt lại mật khẩu --}}
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-body p-4">
                    <h5 class="card-title mb-2">
                        <i class="fas fa-key text-primary me-1"></i> Quên mật khẩu?
                    </h5>
                    <p class="text-muted mb-3">
                        Nếu bạn không nhớ mật khẩu hiện tại, hãy gửi liên kết đặt lại mật khẩu về email
                        <strong>{{ Auth::user()->email ?? '' }}</strong>, sau đó mở email và click vào liên kết để xác thực.
                    </p>

                    <form action="{{ route('users.password.send-reset-link') }}" method="POST"
                        onsubmit="return confirm('Gửi email đặt lại mật khẩu tới email của bạn?');">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-paper-plane me-1"></i> Gửi email đặt lại mật khẩu
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    .password-toggle {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #6c757d;
        cursor: pointer;
        z-index: 5;
        padding: 6px;
    }
    .password-toggle:hover {
        color: #764ba2;
    }
    .form-control {
        padding-right: 40px;
    }
    .password-notice {
        margin-top: 6px;
        padding: 8px 10px;
        border-left: 3px solid #f0ad4e;
        background: #fff8e6;
        color: #856404;
        font-size: 0.875rem;
        border-radius: 4px;
    }
</style>

<script>
    function togglePassword(inputId, btn) {
        var input = document.getElementById(inputId);
        var icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'fas fa-eye';
        }
    }
</script>
@endsection