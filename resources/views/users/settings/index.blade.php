@php($layout = (Auth::user()->role ?? 'student') === 'student' ? 'layouts.user' : 'layouts.app')
@extends($layout)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <h2 class="mb-4 fw-bold">Thiết lập tài khoản</h2>

            @include('users.settings._tabs', ['active' => 'index'])

            @php($sections = [
                [
                    'route' => 'users.profile.info',
                    'icon'  => 'fas fa-user',
                    'title' => 'Thông tin chung',
                    'desc'  => 'Họ tên, email và xác thực khi đổi email.',
                ],
                [
                    'route' => 'users.profile.password',
                    'icon'  => 'fas fa-key',
                    'title' => 'Đổi mật khẩu',
                    'desc'  => 'Đổi mật khẩu, xem lịch sử đổi mật khẩu và gửi liên kết đặt lại.',
                ],
                [
                    'route' => 'users.settings.security',
                    'icon'  => 'fas fa-shield-alt',
                    'title' => 'Bảo mật',
                    'desc'  => 'Lịch sử đăng nhập, đăng xuất khỏi các phiên khác, thu hồi "ghi nhớ đăng nhập".',
                ],
                [
                    'route' => 'users.settings.profile',
                    'icon'  => 'fas fa-id-badge',
                    'title' => 'Hồ sơ',
                    'desc'  => 'Ảnh đại diện.',
                ],
                [
                    'route' => 'users.settings.privacy',
                    'icon'  => 'fas fa-user-shield',
                    'title' => 'Riêng tư',
                    'desc'  => 'Ẩn trạng thái online, ai được mời vào nhóm và danh sách chặn.',
                ],
            ])

            <div class="row g-3">
                @foreach($sections as $section)
                    <div class="col-md-6">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">
                                    <i class="{{ $section['icon'] }} text-primary me-2"></i>{{ $section['title'] }}
                                </h5>
                                <p class="text-muted flex-grow-1 mb-3">{{ $section['desc'] }}</p>
                                <a href="{{ route($section['route']) }}"
                                   class="btn btn-outline-primary btn-sm align-self-start">
                                    Mở <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection