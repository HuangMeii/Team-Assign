@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4 fw-bold">Thiết lập tài khoản</h2>

            {{-- THANH TAB CHUYỂN HƯỚNG --}}
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link active fw-bold" href="#">Thông tin chung</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-secondary" href="{{ route('users.profile.password') }}">Đổi mật khẩu</a>
                </li>
            </ul>

            {{-- NỘI DUNG FORM THÔNG TIN --}}
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h5 class="card-title mb-4">Thông tin hồ sơ</h5>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form action="{{ route('users.profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Họ và tên</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Vai trò</label>
                            <input type="text" class="form-control bg-light" value="{{ $user->role }}" readonly>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary px-4">Lưu thay đổi</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection