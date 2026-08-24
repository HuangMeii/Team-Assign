@extends('layouts.user')

@section('title', 'Nhóm của tôi')

@section('content')
    <div class="container-fluid px-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h2 class="fw-bold mb-2">Nhóm của tôi</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('user.dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Nhóm của tôi</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('user.create_group') }}" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-2"></i>Tạo nhóm mới
                        </a>
                        @if($userClasses && $userClasses->count() > 0)
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#findGroupModal">
                                <i class="fas fa-search me-2"></i>Tìm nhóm
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @php
            // Group các nhóm theo lớp
            $groupsByClass = $groups->groupBy('class_id');
            
            // Lấy danh sách class_id từ các nhóm đã có
            $classIdsWithGroups = $groupsByClass->keys()->toArray();
        @endphp

        <!-- Hiển thị các lớp có nhóm -->
        @foreach($groupsByClass as $classId => $classGroups)
            @php
                $class = $classGroups->first()->class;
            @endphp
            
            <div class="mb-5">
                <!-- Class Header -->
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 rounded p-3 me-3">
                            <i class="fas fa-chalkboard text-primary fa-lg"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold">{{ $class->class_name ?? 'Chưa phân lớp' }}</h5>
                            <small class="text-muted">
                                {{ $classGroups->count() }} nhóm
                                @if($class && $class->subject)
                                    - {{ $class->subject->subject_name }}
                                @endif
                            </small>
                        </div>
                    </div>
                    <span class="badge bg-success">Đã có nhóm</span>
                </div>

                <!-- Groups Grid -->
                <div class="row g-3">
                    @foreach($classGroups as $group)
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
                                        @if($group->leader_id == Auth::id())
                                            <span class="badge bg-primary rounded-pill">Trưởng nhóm</span>
                                        @else
                                            <span class="badge bg-secondary rounded-pill">Thành viên</span>
                                        @endif
                                    </div>

                                    <!-- Info -->
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-user-tie text-primary me-2" style="width: 20px;"></i>
                                            <small class="text-muted">{{ $group->leader->name }}</small>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-users text-primary me-2" style="width: 20px;"></i>
                                            <small class="text-muted">{{ $group->members->count() + 1 }}/{{ $maxMembersByGroup[$group->group_id] ?? 5 }} thành viên</small>
                                            @if(($group->members->count() + 1) >= ($maxMembersByGroup[$group->group_id] ?? 5))
                                                <span class="badge bg-success ms-1">Đủ</span>
                                            @else
                                                <span class="badge bg-warning text-dark ms-1">Thiếu</span>
                                            @endif
                                        </div>

                                        @if($group->topic)
                                            <div class="d-flex align-items-start mt-3">
                                                <i class="fas fa-lightbulb text-warning me-2 mt-1" style="width: 20px;"></i>
                                                <small class="text-muted" 
                                                       style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"
                                                       title="{{ $group->topic->name }}">
                                                    {{ $group->topic->name }}
                                                </small>
                                            </div>
                                        @else
                                            <div class="alert alert-warning py-2 px-3 mb-0 mt-3">
                                                <i class="fas fa-exclamation-circle me-2"></i>
                                                <small>Chưa có đề tài</small>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Actions -->
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('user.group_detail', $group->group_id) }}" class="btn btn-primary">
                                            <i class="fas fa-eye me-2"></i>Chi tiết
                                        </a>

                                        <a href="{{ route('groups.chat.show', $group->group_id) }}" class="btn btn-success">
                                            <i class="fas fa-comments me-2"></i>Chat nhóm
                                        </a>

                                        @if($group->leader_id == Auth::id())
                                            <a href="{{ route('user.invite-member', $group->group_id) }}" class="btn btn-outline-success">
                                                <i class="fas fa-user-plus me-2"></i>Mời thành viên
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <!-- Hiển thị các lớp CHƯA có nhóm -->
        @foreach($userClasses as $class)
            @if(!in_array($class->class_id, $classIdsWithGroups))
                <div class="mb-5">
                    <!-- Class Header -->
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-warning bg-opacity-10 rounded p-3 me-3">
                                <i class="fas fa-chalkboard text-warning fa-lg"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold">{{ $class->class_name }}</h5>
                                <small class="text-muted">
                                    0 nhóm
                                    @if($class->subject)
                                        - {{ $class->subject->subject_name }}
                                    @endif
                                </small>
                            </div>
                        </div>
                        <span class="badge bg-warning">Chưa có nhóm</span>
                    </div>

                    <!-- Empty State -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body text-center py-5">
                                    <div class="mb-3">
                                        <i class="fas fa-users fa-3x text-warning opacity-50"></i>
                                    </div>
                                    <h6 class="fw-bold mb-2">Bạn chưa có nhóm trong lớp này</h6>
                                    <p class="text-muted mb-4">Tạo nhóm mới hoặc tìm nhóm để tham gia</p>
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a href="{{ route('user.create_group') }}" class="btn btn-outline-primary">
                                            <i class="fas fa-plus me-2"></i>Tạo nhóm mới
                                        </a>
                                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#findGroupModal">
                                            <i class="fas fa-search me-2"></i>Tìm nhóm
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach

        <!-- Empty State nếu không có nhóm nào -->
        @if($groups->isEmpty() && $userClasses->isEmpty())
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-users fa-4x text-primary opacity-50"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Bạn chưa tham gia lớp học nào</h5>
                            <p class="text-muted mb-0">Vui lòng liên hệ giáo viên để được thêm vào lớp học</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Pagination -->
        @if($groups->hasPages())
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex justify-content-center">
                        {{ $groups->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal Find Groups -->
    @if($userClasses && $userClasses->count() > 0)
        <div class="modal fade" id="findGroupModal" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold">
                            <i class="fas fa-search me-2 text-primary"></i>
                            Tìm nhóm để tham gia
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        @foreach($userClasses as $class)
                            <div class="mb-5">
                                <!-- Class Header -->
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary bg-opacity-10 rounded p-3 me-3">
                                        <i class="fas fa-chalkboard text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold">{{ $class->class_name }}</h6>
                                        <small class="text-muted">
                                            {{ $class->groups->count() }} nhóm
                                            @if($class->subject)
                                                - {{ $class->subject->subject_name }}
                                            @endif
                                        </small>
                                    </div>
                                </div>

                                <!-- Groups Grid -->
                                <div class="row g-3">
                                    @forelse($class->groups as $classGroup)
                                        @php
                                            $isMember = $classGroup->members->contains('user_id', Auth::id()) || $classGroup->leader_id == Auth::id();
                                            $hasPendingRequest = $classGroup->joinRequests()
                                                ->where('member_id', Auth::id())
                                                ->where('status', 'Pending')
                                                ->exists();
                                            $totalMembers = $classGroup->members->count() + 1;
                                            $isFull = $totalMembers >= ($maxMembersByGroup[$classGroup->group_id] ?? 5);
                                            $alreadyHasGroupInClass = in_array($classGroup->class_id, $joinedClassIds ?? []);
                                        @endphp

                                        <div class="col-12 col-md-6 col-lg-4">
                                            <div class="card h-100 border {{ $isMember ? 'border-success' : '' }}">
                                                <div class="card-body p-3">
                                                    <h6 class="mb-3 fw-bold" title="{{ $classGroup->group_name }}">
                                                        {{ $classGroup->group_name }}
                                                    </h6>
                                                    <div class="mb-3">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <i class="fas fa-user-tie text-muted me-2"></i>
                                                            <small class="text-muted">{{ $classGroup->leader->name }}</small>
                                                        </div>
                                                        
                                                        <div class="d-flex align-items-center mb-2">
                                                            <i class="fas fa-users {{ $isFull ? 'text-danger' : 'text-muted' }} me-2"></i>
                                                            <small class="{{ $isFull ? 'text-danger fw-bold' : 'text-muted' }}">
                                                                {{ $totalMembers }}/{{ $maxMembersByGroup[$classGroup->group_id] ?? 5 }} thành viên
                                                            </small>
                                                        </div>

                                                        @if($classGroup->topic)
                                                            <div class="d-flex align-items-center">
                                                                <i class="fas fa-lightbulb text-warning me-2"></i>
                                                                <small class="text-muted">Đã có đề tài</small>
                                                            </div>
                                                        @endif
                                                    </div>

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

                                                        @elseif($alreadyHasGroupInClass)
                                                            <div class="d-grid">
                                                                <button class="btn btn-secondary disabled" disabled style="opacity: 0.7; cursor: not-allowed;">
                                                                    <i class="fas fa-ban me-1"></i>Bạn đã có nhóm lớp này
                                                                </button>
                                                            </div>

                                                        @elseif($isFull)
                                                            <div class="d-grid">
                                                                <button class="btn btn-danger disabled" disabled style="opacity: 0.7; cursor: not-allowed;">
                                                                    <i class="fas fa-user-slash me-1"></i>Nhóm đã đầy
                                                                </button>
                                                            </div>

                                                        @else
                                                            <form action="{{ route('user.send-join-request') }}" method="POST">
                                                                @csrf
                                                                <input type="hidden" name="group_id" value="{{ $classGroup->group_id }}">
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

                                    @empty
                                        <div class="col-12">
                                            <div class="text-center py-5">
                                                <i class="fas fa-users fa-3x text-muted opacity-50 mb-3"></i>
                                                <p class="text-muted mb-0">Lớp này chưa có nhóm nào</p>
                                            </div>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

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

        .bg-opacity-10 {
            --bs-bg-opacity: 0.1;
        }

        .text-truncate {
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
@endsection