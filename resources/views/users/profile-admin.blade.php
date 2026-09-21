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

                    @if(session('info'))
                        <div class="alert alert-info">{{ session('info') }}</div>
                    @endif

                    @if(session('warning'))
                        <div class="alert alert-warning">{{ session('warning') }}</div>
                    @endif

                    {{-- Banner email đang chờ xác thực --}}
                    @if($user->pending_email)
                        <div class="alert alert-warning">
                            <div>
                                <i class="fas fa-hourglass-half me-1"></i>
                                Bạn có yêu cầu đổi email sang <strong>{{ $user->pending_email }}</strong> — đang chờ xác thực.
                                Email hiện tại (<strong>{{ $user->email }}</strong>) vẫn dùng để đăng nhập.
                            </div>
                            <form action="{{ route('users.email.resend') }}" method="POST" class="mt-2">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-dark">
                                    <i class="fas fa-paper-plane me-1"></i> Gửi lại email xác thực
                                </button>
                            </form>
                        </div>
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
                            <small class="text-muted">Nếu thay đổi email, hệ thống sẽ gửi email xác thực tới địa chỉ mới trước khi có hiệu lực.</small>
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

            <div class="card shadow-sm border-0 mt-4">
                <div class="card-body p-4">
                    <h5 class="card-title">Gợi ý cài đặt tài khoản</h5>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item px-0">Thông tin cá nhân và email xác thực</div>
                        <div class="list-group-item px-0">Đổi mật khẩu và xem lịch sử thay đổi</div>
                        <div class="list-group-item px-0">Thông báo hệ thống và tin nhắn riêng</div>
                        <div class="list-group-item px-0">Thiết bị/phiên đăng nhập và đăng xuất khỏi phiên khác</div>
                        <div class="list-group-item px-0">Tùy chọn nhận email và thông báo</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection