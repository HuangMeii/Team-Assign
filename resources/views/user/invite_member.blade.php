@extends('layouts.user')

@section('title', 'Mời thành viên')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('user.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('user.my_groups') }}">Nhóm của tôi</a></li>
            <li class="breadcrumb-item"><a href="{{ route('user.group_detail', $group->group_id) }}">{{ $group->group_name }}</a></li>
            <li class="breadcrumb-item active">Mời thành viên</li>
        </ol>
    </nav>

    <!-- Group Info -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">{{ $group->group_name }}</h5>
            <div class="row">
                <div class="col-md-6">
                    <span class="text-muted">Lớp học:</span>
                    <strong class="ms-2">{{ $group->class->class_name ?? 'N/A' }}</strong>
                </div>
                <div class="col-md-6">
                    <span class="text-muted">Số thành viên hiện tại:</span>
                    <strong class="ms-2">{{ $group->members->count() + 1 }} người</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Available Users -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Sinh viên cùng lớp</h5>
                </div>
                <div class="card-body p-4">
                    @if($availableUsers->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-user-friends fa-4x text-muted mb-3"></i>
                            <p class="text-muted">Không còn sinh viên nào để mời</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mã SV</th>
                                        <th>Họ tên</th>
                                        <th>Email</th>
                                        <th width="120" class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($availableUsers as $user)
                                        <tr>
                                            <td>{{ $user->student_code }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-2" 
                                                         style="width: 35px; height: 35px; font-weight: 600;">
                                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                                    </div>
                                                    {{ $user->name }}
                                                </div>
                                            </td>
                                            <td>{{ $user->email }}</td>
                                            <td class="text-center">
                                                <form action="{{ route('user.send-invite') }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="group_id" value="{{ $group->group_id }}">
                                                    <input type="hidden" name="member_id" value="{{ $user->user_id }}">
                                                    <button type="submit" class="btn btn-sm btn-primary" 
                                                            onclick="return confirm('Mời {{ $user->name }} vào nhóm?')">
                                                        <i class="fas fa-paper-plane me-1"></i>Mời
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Pending Invites -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Lời mời đang chờ</h5>
                </div>
                <div class="card-body p-4">
                    @if($pendingInvites->isEmpty())
                        <div class="text-center py-4">
                            <i class="fas fa-envelope-open-text fa-3x text-muted mb-3"></i>
                            <p class="text-muted small mb-0">Chưa có lời mời nào</p>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($pendingInvites as $invite)
                                <div class="list-group-item px-0 py-3">
                                    <div class="mb-2">
                                        <h6 class="mb-1">{{ $invite->member->name }}</h6>
                                        <small class="text-muted d-block">{{ $invite->member->email }}</small>
                                    </div>
                                    <small class="text-muted d-block mb-2">
                                        <i class="fas fa-clock me-1"></i>
                                        {{ $invite->created_at->diffForHumans() }}
                                    </small>
                                    <form action="{{ route('user.cancel-invite', $invite->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger w-100"
                                                onclick="return confirm('Hủy lời mời này?')">
                                            <i class="fas fa-times me-1"></i>Hủy lời mời
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection