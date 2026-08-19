@extends('layouts.app')

@section('title', 'Thêm người dùng mới')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Thêm người dùng mới</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Người dùng</a></li>
        <li class="breadcrumb-item active">Thêm mới</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-user-plus me-1"></i>
            Thông tin tài khoản
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-floating mb-3 mb-md-0">
                            <input class="form-control @error('name') is-invalid @enderror" id="inputName" type="text" name="name" value="{{ old('name') }}" placeholder="Nhập họ tên" required />
                            <label for="inputName">Họ tên</label>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input class="form-control @error('email') is-invalid @enderror" id="inputEmail" type="email" name="email" value="{{ old('email') }}" placeholder="name@example.com" required />
                            <label for="inputEmail">Địa chỉ Email</label>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-floating mb-3 mb-md-0">
                            <select class="form-select @error('role') is-invalid @enderror" id="inputRole" name="role" required>
                                <option value="" selected disabled>Chọn vai trò</option>
                                <option value="student" {{ old('role') == 'student' ? 'selected' : '' }}>Sinh viên</option>
                                <option value="lecturer" {{ old('role') == 'lecturer' ? 'selected' : '' }}>Giảng viên</option>
                                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                            <label for="inputRole">Vai trò</label>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating mb-3 mb-md-0">
                            <input class="form-control @error('password') is-invalid @enderror" id="inputPassword" type="password" name="password" placeholder="Mật khẩu" required />
                            <label for="inputPassword">Mật khẩu</label>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mt-4 mb-0">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary me-md-2">Hủy bỏ</a>
                        <button type="submit" class="btn btn-primary">Lưu người dùng</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection