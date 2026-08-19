@extends('layouts.app')

@section('title', 'Student Dashboard')

@section('content')
<div class="container mt-4">
    <h2 class="mb-4 text-info"> Bảng điều khiển Sinh viên</h2>

    <div class="row text-center">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 p-3">
                <h5> Nhóm của tôi</h5>
                <h3>{{ $stats['my_group'] ? 'Đã có' : 'Chưa có' }}</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 p-3">
                <h5>Lời mời</h5>
                <h3>{{ $stats['pending_invites'] }}</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 p-3">
                <h5> Yêu cầu tham gia</h5>
                <h3>{{ $stats['pending_join_requests'] }}</h3>
            </div>
        </div>
    </div>

    <hr class="my-4">

    @if($userGroup)
        <div class="alert alert-success">
            Bạn đang trong nhóm: <strong>{{ $userGroup->group_name }}</strong>
        </div>
    @endif

    <h5>📬 Lời mời tham gia nhóm</h5>
    <ul class="list-group mb-4">
        @forelse($invites as $invite)
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>Nhóm: {{ $invite->group->group_name ?? '—' }} | Trưởng nhóm: {{ $invite->leader->name ?? '—' }}</span>
                <span class="badge bg-warning text-dark">{{ $invite->status }}</span>
            </li>
        @empty
            <li class="list-group-item text-muted">Không có lời mời nào.</li>
        @endforelse
    </ul>

    <h5> Đề tài khả dụng</h5>
    <div class="list-group">
        @forelse($availableTopics as $topic)
            <div class="list-group-item">
                <h6 class="fw-bold">{{ $topic->title }}</h6>
                <p class="mb-0 text-muted">{{ $topic->description ?? 'Không có mô tả' }}</p>
            </div>
        @empty
            <p class="text-muted">Không có đề tài khả dụng.</p>
        @endforelse
    </div>
</div>
@endsection
