@extends('layouts.user')

@section('title', 'Chi tiết đề tài')

@section('content')
<div class="container-fluid px-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('user.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('user.topics') }}">Đề tài</a></li>
            <li class="breadcrumb-item active">Chi tiết</li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Main Content - Topic Info -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <!-- Header -->
                <div class="card-header border-0 py-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1 me-3">
                            <h4 class="mb-2 fw-bold text-white">{{ $topic->name }}</h4>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                @if($topic->class)
                                    <span class="badge bg-white bg-opacity-25 text-white">
                                        <i class="fas fa-chalkboard me-1"></i>{{ $topic->class->class_name }}
                                    </span>
                                @endif
                                @if($topic->subject)
                                    <span class="badge bg-white bg-opacity-25 text-white">
                                        <i class="fas fa-book me-1"></i>{{ $topic->subject->subject_name }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        @if($topic->assignedGroup)
                            <span class="badge bg-danger bg-opacity-75 px-3 py-2">
                                <i class="fas fa-lock me-1"></i>Đã có nhóm
                            </span>
                        @else
                            <span class="badge bg-success bg-opacity-75 px-3 py-2">
                                <i class="fas fa-unlock me-1"></i>Còn trống
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Body -->
                <div class="card-body p-4">
                    <!-- Lecturer Info -->
                    <div class="mb-4 p-3 rounded" style="background-color: #f8f9fa;">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                                <i class="fas fa-user-tie text-primary fa-lg"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">Giảng viên hướng dẫn</small>
                                <h6 class="mb-0 fw-bold">{{ $topic->lecturer }}</h6>
                            </div>
                        </div>
                    </div>

                    <!-- Số lượng thành viên & Hạn đăng ký -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="p-3 rounded" style="background-color: #f8f9fa;">
                                <small class="text-muted d-block">Số lượng thành viên</small>
                                <h6 class="mb-0 fw-bold">{{ $topic->min_members ?? 1 }} – {{ $topic->max_members ?? 5 }} người</h6>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded" style="background-color: #f8f9fa;">
                                <small class="text-muted d-block">Hạn đăng ký</small>
                                <h6 class="mb-0 fw-bold">
                                    @if($topic->registration_deadline)
                                        <span class="{{ now()->greaterThan($topic->registration_deadline) ? 'text-danger' : '' }}">
                                            {{ $topic->registration_deadline->format('d/m/Y H:i') }}
                                        </span>
                                        @if(now()->greaterThan($topic->registration_deadline))
                                            <span class="badge bg-danger ms-1">Đã hết hạn</span>
                                        @else
                                            <span class="badge bg-success ms-1">Còn hạn</span>
                                        @endif
                                    @else
                                        Không giới hạn
                                    @endif
                                </h6>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    @if($topic->description)
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-align-left text-primary me-2"></i>
                                Mô tả đề tài
                            </h6>
                            <p class="text-muted mb-0" style="line-height: 1.8;">{{ $topic->description }}</p>
                        </div>
                    @endif

                    <!-- Assigned Group Alert -->
                    @if($topic->assignedGroup)
                        <div class="alert alert-info border-0 d-flex align-items-center">
                            <i class="fas fa-info-circle fa-2x me-3"></i>
                            <div>
                                <strong>Đề tài đã được đăng ký</strong>
                                <p class="mb-0 small">Nhóm <strong>{{ $topic->assignedGroup->group_name }}</strong> đã đăng ký đề tài này</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar - Registration -->
        <div class="col-lg-4">
            @if(!$topic->assignedGroup)
                <!-- Registration Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-gradient text-white py-3" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-clipboard-check me-2"></i>
                            Đăng ký đề tài
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        @php
                            // Lấy nhóm của user trong cùng lớp với đề tài
                            $myGroupInClass = $myGroups->where('class_id', $topic->class_id)->first();
                        @endphp

                        @if(!$myGroupInClass)
                            <!-- No Group in Class -->
                            <div class="text-center py-3">
                                <div class="mb-3">
                                    <i class="fas fa-users fa-3x text-warning opacity-50"></i>
                                </div>
                                <h6 class="fw-bold mb-2">Bạn chưa có nhóm</h6>
                                <p class="text-muted small mb-3">
                                    Bạn cần có nhóm trong lớp <strong>{{ $topic->class->class_name ?? 'này' }}</strong> để đăng ký đề tài
                                </p>
                                <a href="{{ route('user.my_groups') }}" class="btn btn-warning w-100">
                                    <i class="fas fa-users me-2"></i>
                                    Quản lý nhóm
                                </a>
                            </div>
                        @else
                            <!-- Has Group in Class -->
                            @php
                                $isLeader = $myGroupInClass->leader_id == Auth::id();
                                $hasRegistered = in_array($myGroupInClass->group_id, $groupsRegistered ?? []);
                                $groupHasTopic = $myGroupInClass->topic_id != null;
                                $myRejected = $topic->topic_requests->first(function ($req) use ($myGroupInClass) {
                                    return $req->group_id == $myGroupInClass->group_id && $req->status === 'Rejected';
                                });
                            @endphp

                            <!-- Group Info -->
                            <div class="mb-3 p-3 rounded" style="background-color: #f8f9fa;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0 fw-bold">{{ $myGroupInClass->group_name }}</h6>
                                    @if($isLeader)
                                        <span class="badge bg-primary">Trưởng nhóm</span>
                                    @else
                                        <span class="badge bg-secondary">Thành viên</span>
                                    @endif
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-users me-1"></i>
                                    {{ $myGroupInClass->members->count() + 1 }} thành viên
                                </small>
                            </div>

                            @if($groupHasTopic)
                                <!-- Group Already Has Topic -->
                                <div class="alert alert-warning border-0 mb-3">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <small>Nhóm đã có đề tài khác</small>
                                </div>
                                <button class="btn btn-secondary w-100" disabled>
                                    <i class="fas fa-ban me-2"></i>
                                    Không thể đăng ký
                                </button>

                            @elseif(!$isLeader)
                                <!-- Not Leader -->
                                <div class="alert alert-info border-0 mb-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <small>Chỉ trưởng nhóm mới có thể đăng ký đề tài</small>
                                </div>
                                <button class="btn btn-secondary w-100" disabled>
                                    <i class="fas fa-lock me-2"></i>
                                    Chỉ trưởng nhóm
                                </button>

                            @elseif($hasRegistered)
                                <!-- Already Registered -->
                                <div class="alert alert-warning border-0 mb-3">
                                    <i class="fas fa-clock me-2"></i>
                                    <small>Đã gửi yêu cầu đăng ký, đang chờ duyệt</small>
                                </div>
                                <button class="btn btn-warning w-100" disabled>
                                    <i class="fas fa-hourglass-half me-2"></i>
                                    Đang chờ duyệt
                                </button>

                            @else
                                <!-- Can Register -->
                                <form action="{{ route('user.register_topic') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="topic_id" value="{{ $topic->topic_id }}">
                                    <input type="hidden" name="group_id" value="{{ $myGroupInClass->group_id }}">
                                    
                                    @if($myRejected)
                                        <div class="alert alert-danger border-0 mb-3">
                                            <i class="fas fa-times-circle me-2"></i>
                                            <small>Nhóm bạn đã bị từ chối đề tài này.
                                                @if($myRejected->rejection_reason) Lý do: {{ $myRejected->rejection_reason }} @endif
                                            </small>
                                        </div>
                                    @endif

                                    <div class="alert alert-success border-0 mb-3">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <small>Bạn có thể đăng ký đề tài này cho nhóm</small>
                                    </div>

                                    <button type="submit" class="btn btn-success w-100 py-2">
                                        <i class="fas fa-paper-plane me-2"></i>
                                        Đăng ký ngay
                                    </button>
                                </form>
                            @endif

                            <!-- View Group Detail Link -->
                            <div class="mt-3 pt-3 border-top">
                                <a href="{{ route('user.group_detail', $myGroupInClass->group_id) }}" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-eye me-2"></i>
                                    Xem chi tiết nhóm
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Pending Requests Info -->
            @if($topic->topic_requests->where('status', 'Pending')->isNotEmpty())
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-clock text-warning me-2"></i>
                            Yêu cầu đang chờ
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted small mb-3">
                            Có <strong>{{ $topic->topic_requests->where('status', 'Pending')->count() }}</strong> nhóm đang chờ duyệt
                        </p>
                        <div class="list-group list-group-flush">
                            @foreach($topic->topic_requests->where('status', 'Pending')->take(3) as $request)
                                <div class="list-group-item px-0 py-2 border-0">
                                    <small class="text-muted">
                                        <i class="fas fa-users me-2"></i>
                                        {{ $request->group->group_name }}
                                    </small>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Back Button -->
    <div class="row mt-4">
        <div class="col-12">
            <a href="{{ route('user.topics') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Quay lại danh sách đề tài
            </a>
        </div>
    </div>
</div>

<style>
    .bg-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .card {
        transition: all 0.3s ease;
    }
    
    .badge {
        font-weight: 500;
        font-size: 0.8rem;
    }
    
    .alert {
        border-radius: 8px;
    }
    
    .btn {
        border-radius: 8px;
        font-weight: 500;
    }
</style>
@endsection