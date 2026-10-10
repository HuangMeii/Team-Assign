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
                    <h5 class="card-title mb-1">
                        <i class="fas fa-history text-primary me-1"></i> Lịch sử đăng nhập ({{ $loginCount }})
                    </h5>
                    <p class="text-muted mb-3">
                        <i class="fas fa-shield-alt me-1"></i> Số phiên đăng nhập: <strong>{{ $sessionCount }}</strong>
                    </p>

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
                                        <td>{{ $history->created_at?->displayTz()->format('d/m/Y H:i') }}</td>
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
