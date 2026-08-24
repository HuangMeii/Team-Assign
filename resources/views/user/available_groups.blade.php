@extends('layouts.user')

@section('title', 'Nhóm còn thiếu thành viên')

@section('content')
    <div class="container-fluid px-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h2 class="fw-bold mb-2">Nhóm còn thiếu thành viên</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('user.dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Nhóm còn thiếu thành viên</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('user.create_group') }}" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-2"></i>Tạo nhóm mới
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if($availableGroups->isEmpty())
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-users fa-4x text-primary opacity-50"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Không có nhóm nào còn thiếu thành viên</h5>
                            <p class="text-muted mb-0">Hiện tại không có nhóm nào đang tuyển thành viên trong các lớp bạn tham gia.</p>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- Groups Grid -->
            <div class="row g-3">
                @foreach($availableGroups as $group)
                    @php
                        $isMember = $group->members->contains('user_id', Auth::id()) || $group->leader_id == Auth::id();
                        $hasPendingRequest = in_array($group->group_id, $requestedGroupIds);
                        $groupTotalMembers = $group->members_count + 1;
                        $groupMaxMembers = $maxMembersByGroup[$group->group_id] ?? 5;
                        $remainingSlots = $groupMaxMembers - $groupTotalMembers;
                    @endphp

                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card border-0 shadow-sm h-100 hover-card">
                            <div class="card-body p-4">
                                <!-- Header -->
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h6 class="mb-0 fw-bold flex-grow-1 me-2"
                                        style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"
                                        title="{{ $group->group_name }}">
                                        {{ $group->group_name }}
                                    </h6>
                                    <span class="badge bg-success rounded-pill">Còn {{ $remainingSlots }} chỗ</span>
                                </div>

                                <!-- Info -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-chalkboard text-primary me-2" style="width: 20px;"></i>
                                        <small class="text-muted">
                                            {{ $group->class->class_name ?? 'Chưa phân lớp' }}
                                            @if($group->class && $group->class->subject)
                                                - {{ $group->class->subject->subject_name }}
                                            @endif
                                        </small>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-user-tie text-primary me-2" style="width: 20px;"></i>
                                        <small class="text-muted">Trưởng nhóm: {{ $group->leader->name }}</small>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-users text-primary me-2" style="width: 20px;"></i>
                                        <small class="text-muted">{{ $groupTotalMembers }}/{{ $groupMaxMembers }} thành viên</small>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="mt-auto">
                                    @if($isMember)
                                        <div class="d-grid">
                                            <span class="badge bg-success py-2">
                                                <i class="fas fa-check me-1"></i>Đã tham gia
                                            </span>
                                        </div>
                                    @elseif($hasPendingRequest)
                                        <div class="d-grid">
                                            <span class="badge bg-warning py-2">
                                                <i class="fas fa-clock me-1"></i>Đang chờ duyệt
                                            </span>
                                        </div>
                                    @else
                                        <form action="{{ route('user.send-join-request') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="group_id" value="{{ $group->group_id }}">
                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-paper-plane me-1"></i>Xin tham gia
                                                </button>
                                            </div>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <style>
        .hover-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        .hover-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12) !important;
            border-color: rgba(var(--bs-primary-rgb), 0.3);
        }

        .badge {
            font-weight: 500;
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }
    </style>
@endsection
