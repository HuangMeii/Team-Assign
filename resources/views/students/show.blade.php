@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row">
        <!-- Student Info -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-user-graduate"></i> {{ $student->name }}</h4>
                    <div>
                        <a href="{{ route('students.edit', $student->user_id) }}" class="btn btn-light btn-sm">
                            <i class="fas fa-edit"></i> Chỉnh sửa
                        </a>
                        <a href="{{ route('students.index') }}" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- L06: thông báo lỗi khi gửi email / reset mật khẩu thất bại --}}
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Basic Info -->
                    <h5 class="mb-3"><i class="fas fa-info-circle"></i> Thông tin cơ bản</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <label class="text-muted"><i class="fas fa-user"></i> Họ và tên:</label>
                                <div class="fw-bold">{{ $student->name }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <label class="text-muted"><i class="fas fa-envelope"></i> Email:</label>
                                <div class="fw-bold">{{ $student->email }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <label class="text-muted"><i class="fas fa-calendar"></i> Ngày tạo:</label>
                                <div class="fw-bold">{{ $student->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <label class="text-muted"><i class="fas fa-sign-in-alt"></i> Trạng thái đăng nhập:</label>
                                <div class="fw-bold">
                                    @if($student->isFirstLogin)
                                        <span class="badge bg-warning text-dark">Chưa đăng nhập lần đầu</span>
                                    @else
                                        <span class="badge bg-success">Đã đăng nhập</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Classes -->
                    <h5 class="mb-3"><i class="fas fa-chalkboard-teacher"></i> Lớp học phần</h5>
                    <div class="row mb-4">
                        @forelse($student->classes as $class)
                            <div class="col-md-6 mb-3">
                                <div class="card border-info">
                                    <div class="card-body">
                                        <h6 class="card-title text-info">{{ $class->class_name }}</h6>
                                        <p class="card-text small text-muted mb-0">
                                            <i class="fas fa-book"></i> {{ $class->subject->subject_name ?? 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <p class="text-muted">Chưa tham gia lớp nào</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Groups -->
                    <h5 class="mb-3"><i class="fas fa-users"></i> Nhóm tham gia</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Tên nhóm</th>
                                    <th>Lớp</th>
                                    <th>Vai trò</th>
                                    <th>Đề tài</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($student->groupsJoined as $group)
                                    <tr>
                                        <td>{{ $group->group_name }}</td>
                                        <td>
                                            <span class="badge bg-info text-dark">
                                                {{ $group->class->class_name ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($group->leader_id == $student->user_id)
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-crown"></i> Nhóm trưởng
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
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            Chưa tham gia nhóm nào
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Sidebar -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Thống kê</h5>
                </div>
                <div class="card-body">
                    <div class="stat-item mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">
                                <i class="fas fa-chalkboard"></i> Số lớp:
                            </span>
                            <span class="badge bg-primary fs-6">{{ $student->classes->count() }}</span>
                        </div>
                    </div>
                    <div class="stat-item mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">
                                <i class="fas fa-users"></i> Số nhóm:
                            </span>
                            <span class="badge bg-success fs-6">{{ $student->groupsJoined->count() }}</span>
                        </div>
                    </div>
                    <div class="stat-item mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">
                                <i class="fas fa-crown"></i> Nhóm trưởng:
                            </span>
                            <span class="badge bg-warning text-dark fs-6">{{ $student->groupsLed->count() }}</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">
                                <i class="fas fa-clipboard-check"></i> Trạng thái:
                            </span>
                            @if($student->has_group)
                                <span class="badge bg-success fs-6">Đã có nhóm</span>
                            @else
                                <span class="badge bg-secondary fs-6">Chưa có nhóm</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-bolt"></i> Thao tác nhanh</h5>
                </div>
                <div class="card-body">
                    <a href="{{ route('students.edit', $student->user_id) }}" class="btn btn-warning w-100 mb-2 text-white">
                        <i class="fas fa-edit"></i> Chỉnh sửa thông tin
                    </a>

                    {{-- L06 (Chốt 5a-B): chỉ Admin thấy & dùng được 2 thao tác nhanh.
                         Giảng viên / sinh viên gọi trực tiếp URL sẽ bị 403 ở controller. --}}
                    @if(Auth::user()->role === 'admin')
                        <button type="button" class="btn btn-info w-100 mb-2 text-white"
                                data-bs-toggle="modal" data-bs-target="#sendEmailModal">
                            <i class="fas fa-envelope"></i> Gửi email
                        </button>

                        {{-- L06 (Chốt 5b): reset về mật khẩu mặc định 'password' + form confirm --}}
                        <form action="{{ route('students.reset-password', $student->user_id) }}" method="POST"
                              onsubmit="return confirm('Reset mật khẩu của sinh viên {{ $student->name }} về mặc định \'password\'?')">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-key"></i> Reset mật khẩu
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if(Auth::user()->role === 'admin')
{{-- L06: Modal gửi email cho sinh viên (chỉ Admin) --}}
<div class="modal fade" id="sendEmailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('students.send-email', $student->user_id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-envelope text-info me-2"></i>Gửi email cho {{ $student->name }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tiêu đề</label>
                        <input type="text" name="subject" class="form-control" maxlength="255"
                               value="{{ old('subject') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nội dung</label>
                        <textarea name="message" class="form-control" rows="5" required>{{ old('message') }}</textarea>
                    </div>
                    <p class="text-muted small mb-0">
                        Email sẽ được gửi tới <strong>{{ $student->email }}</strong>.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-info text-white">
                        <i class="fas fa-paper-plane me-1"></i>Gửi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<style>
    .info-item label {
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }
</style>
@endsection