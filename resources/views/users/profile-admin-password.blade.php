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
                            <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror">
                            @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mật khẩu mới</label>
                            <input type="password" name="new_password" class="form-control @error('new_password') is-invalid @enderror">
                            @error('new_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Xác nhận mật khẩu mới</label>
                            <input type="password" name="new_password_confirmation" class="form-control">
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
@endsection