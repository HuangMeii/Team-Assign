@php($layout = (Auth::user()->role ?? 'student') === 'student' ? 'layouts.user' : 'layouts.app')
@extends($layout)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4 fw-bold">Thiết lập tài khoản</h2>

            @include('users.settings._tabs', ['active' => 'profile'])

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @foreach(['avatar', 'locale', 'timezone'] as $field)
                @error($field) <div class="alert alert-danger">{{ $message }}</div> @enderror
            @endforeach

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-id-badge text-primary me-1"></i> Hồ sơ
                    </h5>

                    <form action="{{ route('users.settings.profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="d-flex align-items-center mb-4">
                            @if($user->avatar_url)
                                <img src="{{ $user->avatar_url }}" alt="Ảnh đại diện" class="rounded-circle me-3"
                                     style="width:72px;height:72px;object-fit:cover;">
                            @else
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3 fw-bold"
                                     style="width:72px;height:72px;font-size:1.5rem;">
                                    {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                                </div>
                            @endif
                            <div class="flex-grow-1">
                                <label class="form-label fw-semibold">Ảnh đại diện (JPG/PNG, tối đa 2MB)</label>
                                <input type="file" name="avatar" accept="image/*" class="form-control">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ngôn ngữ hiển thị</label>
                                <select name="locale" class="form-select">
                                    <option value="vi" {{ $user->locale === 'en' ? '' : 'selected' }}>Tiếng Việt</option>
                                    <option value="en" {{ $user->locale === 'en' ? 'selected' : '' }}>English</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Múi giờ</label>
                                <input type="text" name="timezone" class="form-control"
                                       value="{{ old('timezone', $user->timezone) }}"
                                       placeholder="Asia/Ho_Chi_Minh">
                                <small class="text-muted">
                                    Múi giờ dùng để HIỂN THỊ thời gian (dữ liệu luôn lưu theo UTC nên không bị lệch).
                                    Để trống ⇒ dùng múi giờ mặc định của hệ thống.
                                </small>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary px-4">Lưu tuỳ chọn</button>
                    </form>

                    @if($user->avatar_path)
                        <form action="{{ route('users.settings.avatar.destroy') }}" method="POST" class="mt-3"
                              onsubmit="return confirm('Xoá ảnh đại diện?');">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-trash me-1"></i> Xoá ảnh đại diện
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
