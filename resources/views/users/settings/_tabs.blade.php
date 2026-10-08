{{-- L09 — Thanh tab dùng chung cho 5 trang "Thiết lập tài khoản".
     Truyền: @include('users.settings._tabs', ['active' => 'info'|'password'|'security'|'profile'|'privacy']) --}}
@php($active = $active ?? 'info')
<ul class="nav nav-tabs mb-4 flex-wrap">
    <li class="nav-item">
        <a class="nav-link {{ $active === 'info' ? 'active fw-bold' : 'text-secondary' }}"
           href="{{ route('users.profile.info') }}">Thông tin chung</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $active === 'password' ? 'active fw-bold' : 'text-secondary' }}"
           href="{{ route('users.profile.password') }}">Đổi mật khẩu</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $active === 'security' ? 'active fw-bold' : 'text-secondary' }}"
           href="{{ route('users.settings.security') }}">Bảo mật</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $active === 'profile' ? 'active fw-bold' : 'text-secondary' }}"
           href="{{ route('users.settings.profile') }}">Hồ sơ</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $active === 'privacy' ? 'active fw-bold' : 'text-secondary' }}"
           href="{{ route('users.settings.privacy') }}">Riêng tư</a>
    </li>
</ul>
