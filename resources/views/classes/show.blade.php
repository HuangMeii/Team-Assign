@extends('layouts.app')

@section('title', 'Chi tiết lớp học')

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="fw-bold mb-2">{{ $class->class_name }}</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('classes.index') }}">Lớp học</a></li>
                            <li class="breadcrumb-item active">{{ $class->class_name }}</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('classes.edit', $class->class_id) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>Chỉnh sửa
                    </a>
                    <button type="button" 
                            class="btn btn-danger"
                            onclick="confirmDelete()">
                        <i class="fas fa-trash me-2"></i>Xóa lớp
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-info bg-opacity-10 rounded p-3 me-3">
                            <i class="fas fa-users fa-2x text-info"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ $class->users->count() }}</h3>
                            <p class="text-muted mb-0 small">Sinh viên</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 rounded p-3 me-3">
                            <i class="fas fa-user-friends fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ $class->groups->count() }}</h3>
                            <p class="text-muted mb-0 small">Nhóm</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-success bg-opacity-10 rounded p-3 me-3">
                            <i class="fas fa-lightbulb fa-2x text-success"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold">{{ $class->topics->count() }}</h3>
                            <p class="text-muted mb-0 small">Đề tài</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning bg-opacity-10 rounded p-3 me-3">
                            <i class="fas fa-book fa-2x text-warning"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold text-truncate">{{ $class->subject->subject_name ?? 'N/A' }}</h6>
                            <p class="text-muted mb-0 small">Môn học</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Class Info -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Thông tin lớp
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Tên lớp</label>
                        <p class="fw-bold mb-0">{{ $class->class_name }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small mb-1">Môn học</label>
                        <p class="fw-bold mb-0">{{ $class->subject->subject_name ?? 'N/A' }}</p>
                        @if($class->subject)
                            <small class="text-muted">{{ $class->subject->subject_code }}</small>
                        @endif
                    </div>

                    @if($class->subject && $class->subject->lecturer)
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Giảng viên phụ trách</label>
                            <p class="fw-bold mb-0">{{ $class->subject->lecturer->name }}</p>
                            <small class="text-muted">{{ $class->subject->lecturer->email }}</small>
                        </div>
                    @endif

                    <div>
                        <label class="text-muted small mb-1">Ngày tạo</label>
                        <p class="mb-0">{{ $class->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students List -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-users text-info me-2"></i>
                        Danh sách sinh viên ({{ $class->users->count() }})
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if($class->users->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted opacity-50 mb-3"></i>
                            <p class="text-muted mb-0">Chưa có sinh viên nào</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="px-4 py-3">STT</th>
                                        <th class="px-4 py-3">Họ tên</th>
                                        <th class="px-4 py-3">Email</th>
                                        <th class="px-4 py-3 text-center">Nhóm</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($class->users as $index => $user)
                                        @php
                                            $userGroups = $class->groups->filter(function($group) use ($user) {
                                                return $group->leader_id === $user->user_id || 
                                                       $group->members->contains('user_id', $user->user_id);
                                            });
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-3">{{ $index + 1 }}</td>
                                            <td class="px-4 py-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-2" 
                                                         style="width: 35px; height: 35px;">
                                                        <span class="text-primary fw-bold small">
                                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                                        </span>
                                                    </div>
                                                    <span class="fw-semibold">{{ $user->name }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <small class="text-muted">{{ $user->email }}</small>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                @if($userGroups->isNotEmpty())
                                                    @foreach($userGroups as $group)
                                                        <span class="badge bg-success rounded-pill">
                                                            {{ $group->group_name }}
                                                        </span>
                                                    @endforeach
                                                @else
                                                    <span class="badge bg-secondary rounded-pill">Chưa có nhóm</span>
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

            <!-- Groups List -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-user-friends text-primary me-2"></i>
                        Danh sách nhóm ({{ $class->groups->count() }})
                    </h5>
                </div>
                <div class="card-body p-4">
                    @if($class->groups->isEmpty())
                        <div class="text-center py-4">
                            <i class="fas fa-user-friends fa-3x text-muted opacity-50 mb-3"></i>
                            <p class="text-muted mb-0">Chưa có nhóm nào</p>
                        </div>
                    @else
                        <div class="row g-3">
                            @foreach($class->groups as $group)
                                <div class="col-md-6">
                                    <div class="card border">
                                        <div class="card-body p-3">
                                            <h6 class="fw-bold mb-2">{{ $group->group_name }}</h6>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-user-tie text-muted me-2" style="width: 18px;"></i>
                                                <small class="text-muted">{{ $group->leader->name }}</small>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-users text-muted me-2" style="width: 18px;"></i>
                                                <small class="text-muted">{{ $group->members->count() + 1 }} thành viên</small>
                                            </div>
                                            @if($group->topic)
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-lightbulb text-success me-2" style="width: 18px;"></i>
                                                    <small class="text-success">{{ Str::limit($group->topic->name, 30) }}</small>
                                                </div>
                                            @else
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-exclamation-circle text-warning me-2" style="width: 18px;"></i>
                                                    <small class="text-muted">Chưa có đề tài</small>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Form -->
<form id="delete-form" 
      action="{{ route('classes.destroy', $class->class_id) }}" 
      method="POST" 
      class="d-none">
    @csrf
    @method('DELETE')
</form>

<script>
function confirmDelete() {
    if (confirm('Bạn có chắc chắn muốn xóa lớp này?\n\nLưu ý: Không thể xóa lớp đã có sinh viên hoặc nhóm!')) {
        document.getElementById('delete-form').submit();
    }
}
</script>

<style>
    .bg-opacity-10 {
        --bs-bg-opacity: 0.1;
    }
</style>
@endsection