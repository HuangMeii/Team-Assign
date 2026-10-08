@php($layout = (Auth::user()->role ?? 'student') === 'student' ? 'layouts.user' : 'layouts.app')
@extends($layout)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4 fw-bold">Thiết lập tài khoản</h2>

            @include('users.settings._tabs', ['active' => 'privacy'])

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @error('invite_policy') <div class="alert alert-danger">{{ $message }}</div> @enderror

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-user-shield text-primary me-1"></i> Riêng tư
                    </h5>

                    <form action="{{ route('users.settings.privacy.update') }}" method="POST">
                        @csrf

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" id="hideOnline"
                                   name="hide_online" value="1" {{ $user->hide_online ? 'checked' : '' }}>
                            <label class="form-check-label" for="hideOnline">
                                Ẩn trạng thái online — người khác thấy "Ẩn" thay vì chấm xanh
                            </label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Ai được mời tôi vào nhóm?</label>
                            @php($policy = $user->invite_policy ?: 'everyone')
                            <select name="invite_policy" class="form-select">
                                <option value="everyone" {{ $policy === 'everyone' ? 'selected' : '' }}>Mọi người</option>
                                <option value="classmates" {{ $policy === 'classmates' ? 'selected' : '' }}>Chỉ bạn cùng lớp</option>
                                <option value="none" {{ $policy === 'none' ? 'selected' : '' }}>Không ai</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary px-4">Lưu tuỳ chọn</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0 mt-4">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-ban text-danger me-1"></i> Danh sách chặn ({{ $blockedUsers->count() }})
                    </h5>

                    @forelse($blockedUsers as $blocked)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div>
                                <div class="fw-semibold">{{ $blocked->blocked?->name ?? 'Người dùng' }}</div>
                                <small class="text-muted">{{ $blocked->blocked?->email }}</small>
                            </div>
                            <form action="{{ route('users.settings.unblock') }}" method="POST">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $blocked->blocked_id }}">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Bỏ chặn</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Bạn chưa chặn ai.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
