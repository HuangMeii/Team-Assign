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
        <p class="mb-0"><strong>Tạo lúc:</strong> {{ $user->created_at?->displayTz()->format('d/m/Y H:i') }}</p>
    </div></div>

    {{-- Lớp học phần: Giảng viên = lớp đang phụ trách · Sinh viên = lớp đã tham gia (kèm trạng thái) --}}
    @if(in_array($user->role, ['lecturer', 'student'], true))
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-chalkboard-teacher me-1"></i> Lớp học phần ({{ $classes->count() }})</span>
            @if($user->role === 'lecturer')
                <span class="badge bg-warning text-dark">Giảng viên</span>
            @else
                <span class="badge bg-info text-dark">Sinh viên</span>
            @endif
        </div>
        <div class="card-body p-0">
            @if($classes->isEmpty())
                <p class="text-center text-muted py-4 mb-0">
                    <i class="fas fa-chalkboard-teacher"></i> Chưa có lớp học phần nào.
                </p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Lớp học phần</th>
                                <th>Môn học</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($classes as $class)
                                @php
                                    // L05: trạng thái nằm ở pivot user_classes.status ('studying'/'left').
                                    $isLeft = ($class->pivot->status ?? 'studying') === 'left';
                                @endphp
                                <tr class="{{ $isLeft ? 'opacity-60 bg-light' : '' }}">
                                    <td>
                                        <a href="{{ route('admin.classes.show', $class->class_id) }}"
                                           class="text-decoration-none fw-semibold">
                                            {{ $class->class_name }}
                                        </a>
                                    </td>
                                    <td>
                                        {{ $class->subject->subject_name ?? 'N/A' }}
                                        @if($class->subject && $class->subject->subject_code)
                                            <span class="text-muted small">({{ $class->subject->subject_code }})</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->role === 'lecturer')
                                            <span class="badge bg-warning text-dark">Giảng viên phụ trách</span>
                                        @elseif($isLeft)
                                            <span class="badge bg-secondary">Đã rời lớp</span>
                                            @if($class->pivot->left_at)
                                                <div class="text-muted small">
                                                    {{ \Illuminate\Support\Carbon::parse($class->pivot->left_at)->displayTz()->format('d/m/Y H:i') }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="badge bg-success">Đang học</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Nhóm (chỉ sinh viên): tên nhóm -> trang chi tiết nhóm (chỉ-đọc) --}}
    @if($user->role === 'student')
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-users me-1"></i> Nhóm ({{ $groups->count() }})
        </div>
        <div class="card-body p-0">
            @if($groups->isEmpty())
                <p class="text-center text-muted py-4 mb-0">
                    <i class="fas fa-users"></i> Chưa tham gia nhóm nào.
                </p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tên nhóm</th>
                                <th>Lớp</th>
                                <th>Vai trò</th>
                                <th>Đề tài</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groups as $group)
                                <tr>
                                    <td>
                                        <a href="{{ route('groups.show', $group->group_id) }}"
                                           class="text-decoration-none fw-semibold">
                                            {{ $group->group_name }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark">
                                            {{ $group->class->class_name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if((int) $group->leader_id === (int) $user->user_id)
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-crown"></i> Trưởng nhóm
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">Thành viên</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($group->topic_id)
                                            <span class="badge bg-success">Đã có đề tài</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Chưa có đề tài</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    @endif

    <div class="card"><div class="card-header">Lịch sử cập nhật mật khẩu</div><div class="card-body p-0">
        <div class="table-responsive"><table class="table mb-0">
            <thead><tr><th>Thời gian</th><th>Người thực hiện</th><th>Nguồn</th></tr></thead>
            <tbody>
            @forelse($user->passwordHistories as $history)
                <tr><td>{{ $history->created_at->displayTz()->format('d/m/Y H:i') }}</td><td>{{ $history->changer?->name ?? 'Hệ thống / đặt lại qua email' }}</td><td>{{ $history->source }}</td></tr>
            @empty <tr><td colspan="3" class="text-center text-muted">Chưa có lịch sử.</td></tr> @endforelse
            </tbody>
        </table></div>
    </div></div>
</div>
@endsection