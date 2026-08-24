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

                    <form action="{{ route('users.password.update') }}" method="POST">
                        @csrf
                        @method('PUT')

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

                        <div class="mb-3">
                            <label class="form-label">Mật khẩu mới</label>
                            <div class="position-relative">
                                <input type="password" name="new_password" id="newPassword" class="form-control @error('new_password') is-invalid @enderror">
                                <button type="button" class="password-toggle" onclick="togglePassword('newPassword', this)" tabindex="-1" aria-label="Hiện/ẩn mật khẩu">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('new_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
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