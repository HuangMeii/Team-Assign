@php($layout = (Auth::user()->role ?? 'student') === 'student' ? 'layouts.user' : 'layouts.app')
@extends($layout)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4 fw-bold">Thiết lập tài khoản</h2>

            @include('users.settings._tabs', ['active' => 'security'])

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('info'))
                <div class="alert alert-info">{{ session('info') }}</div>
            @endif

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-shield-alt text-primary me-1"></i> Bảo mật &amp; phiên đăng nhập
                    </h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <form action="{{ route('users.settings.revoke-sessions') }}" method="POST"
                                  onsubmit="return confirm('Đăng xuất khỏi TẤT CẢ các phiên khác?');">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i class="fas fa-sign-out-alt me-1"></i> Đăng xuất khỏi các phiên khác
                                </button>
                            </form>
                            <small class="text-muted">
                                Đang có <strong>{{ $activeSessions->count() }}</strong> phiên mở (gồm phiên này).
                            </small>
                        </div>
                        <div class="col-md-6">
                            <form action="{{ route('users.settings.revoke-remember') }}" method="POST"
                                  onsubmit="return confirm('Thu hồi &quot;ghi nhớ đăng nhập&quot; trên mọi thiết bị?');">
                                @csrf
                                <button type="submit" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-key me-1"></i> Thu hồi "ghi nhớ đăng nhập"
                                </button>
                            </form>
                            <small class="text-muted">Vô hiệu hoá cookie "ghi nhớ" trên mọi thiết bị.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mt-4">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-history text-primary me-1"></i> Lịch sử đăng nhập ({{ $loginCount }})
                    </h5>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Thời gian</th>
                                    <th>IP</th>
                                    <th>Thiết bị</th>
                                    <th>Trình duyệt</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($histories as $history)
                                    <tr>
                                        <td>{{ $history->created_at?->format('d/m/Y H:i') }}</td>
                                        <td>{{ $history->ip_address ?: '—' }}</td>
                                        <td>{{ $history->deviceLabel() }}</td>
                                        <td>{{ $history->browserLabel() }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">Chưa có lịch sử đăng nhập.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
