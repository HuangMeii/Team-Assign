@extends('layouts.user')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4 fw-bold">Thiết lập tài khoản</h2>

            {{-- THANH TAB CHUYỂN HƯỚNG (L09: 5 tab dùng chung) --}}
            @include('users.settings._tabs', ['active' => 'info'])

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
                    <h5 class="card-title">Cài đặt tài khoản</h5>
                    <div class="list-group list-group-flush">
                        <a class="list-group-item list-group-item-action px-0" href="{{ route('users.profile.info') }}">
                            <i class="fas fa-user me-2 text-primary"></i>Thông tin cá nhân và email xác thực
                        </a>
                        <a class="list-group-item list-group-item-action px-0" href="{{ route('users.profile.password') }}">
                            <i class="fas fa-key me-2 text-primary"></i>Đổi mật khẩu và xem lịch sử thay đổi
                        </a>
                        <a class="list-group-item list-group-item-action px-0" href="{{ route('users.settings.security') }}">
                            <i class="fas fa-shield-alt me-2 text-primary"></i>Lịch sử đăng nhập và đăng xuất khỏi phiên khác
                        </a>
                        <a class="list-group-item list-group-item-action px-0" href="{{ route('users.settings.profile') }}">
                            <i class="fas fa-id-badge me-2 text-primary"></i>Ảnh đại diện, ngôn ngữ và múi giờ
                        </a>
                        <a class="list-group-item list-group-item-action px-0" href="{{ route('users.settings.privacy') }}">
                            <i class="fas fa-user-shield me-2 text-primary"></i>Danh sách chặn, trạng thái online và lời mời nhóm
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection