@extends('layouts.app')

@section('title', 'Chi tiết tài khoản')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">{{ $user->name }}</h1>
    <div class="mb-3">
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Quay lại</a>
        <a href="{{ route('admin.users.edit', $user->user_id) }}" class="btn btn-warning">Chỉnh sửa</a>
        <a href="{{ route('chat.show', $user->user_id) }}" class="btn btn-primary">Chat</a>
    </div>
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    <div class="card mb-4"><div class="card-body">
        <p><strong>Email:</strong> {{ $user->email }}</p>
        <p><strong>Vai trò:</strong> {{ $user->role }}</p>
        <p class="mb-0"><strong>Tạo lúc:</strong> {{ $user->created_at?->format('d/m/Y H:i') }}</p>
    </div></div>
    <div class="card"><div class="card-header">Lịch sử cập nhật mật khẩu</div><div class="card-body p-0">
        <div class="table-responsive"><table class="table mb-0">
            <thead><tr><th>Thời gian</th><th>Người thực hiện</th><th>Nguồn</th></tr></thead>
            <tbody>
            @forelse($user->passwordHistories as $history)
                <tr><td>{{ $history->created_at->format('d/m/Y H:i') }}</td><td>{{ $history->changer?->name ?? 'Hệ thống / đặt lại qua email' }}</td><td>{{ $history->source }}</td></tr>
            @empty <tr><td colspan="3" class="text-center text-muted">Chưa có lịch sử.</td></tr> @endforelse
            </tbody>
        </table></div>
    </div></div>
</div>
@endsection