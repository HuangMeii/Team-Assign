@extends('layouts.app')

@section('title', 'Lớp học của tôi')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-chalkboard-teacher"></i> Lớp học của tôi</h4>
            <a href="{{ route('lecturer.classes.create') }}" class="btn btn-light btn-sm">
                <i class="fas fa-plus"></i> Tạo lớp mới
            </a>
        </div>

        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Tìm kiếm + lọc trạng thái --}}
            <form method="GET" action="{{ route('lecturer.classes.index') }}" class="row g-2 mb-4">
                <div class="col-md-7">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                        placeholder="Tìm theo tên lớp, mã lớp, môn học...">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Đang hoạt động</option>
                        <option value="locked" {{ request('status') == 'locked' ? 'selected' : '' }}>Đã khóa</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Lọc</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tên lớp / Mã lớp</th>
                            <th>Môn học</th>
                            <th class="text-center">Sinh viên</th>
                            <th class="text-center">Nhóm</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-center" style="width: 140px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classes as $class)
                            <tr>
                                <td>
                                    <div class="fw-bold text-primary">{{ $class->class_name }}</div>
                                    @if($class->class_code)
                                        <span class="badge bg-dark">
                                            <i class="fas fa-key me-1"></i>{{ $class->class_code }}
                                        </span>
                                        <button type="button" class="btn btn-link btn-sm p-0 ms-1 text-secondary copy-code"
                                            data-code="{{ $class->class_code }}" title="Copy mã lớp">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    @else
                                        <span class="badge bg-warning text-dark">Chưa có mã</span>
                                    @endif
                                </td>
                                <td>
                                    @if($class->subject)
                                        <div>{{ $class->subject->subject_name }}</div>
                                        <small class="text-muted fst-italic">{{ $class->subject->subject_code }}</small>
                                    @else
                                        <span class="text-danger fst-italic">Chưa gán môn</span>
                                    @endif
                                </td>
                                <td class="text-center"><span class="badge bg-primary">{{ $class->students_count }}</span></td>
                                <td class="text-center"><span class="badge bg-info text-dark">{{ $class->groups_count }}</span></td>
                                <td class="text-center">
                                    @if($class->is_active)
                                        <span class="badge bg-success">Hoạt động</span>
                                    @else
                                        <span class="badge bg-danger">Đã khóa</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('lecturer.classes.show', $class->class_id) }}"
                                            class="btn btn-info btn-sm text-white" title="Chi tiết & quản lý sinh viên">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form action="{{ route('lecturer.classes.toggle-active', $class->class_id) }}"
                                            method="POST" class="d-inline"
                                            onsubmit="return confirm('Bạn có chắc chắn muốn {{ $class->is_active ? 'khóa' : 'mở khóa' }} lớp này?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="btn btn-sm {{ $class->is_active ? 'btn-secondary' : 'btn-success' }}"
                                                title="{{ $class->is_active ? 'Khóa lớp (sinh viên không thể vào bằng mã)' : 'Mở khóa lớp' }}">
                                                <i class="fas {{ $class->is_active ? 'fa-lock' : 'fa-unlock' }}"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                    Bạn chưa phụ trách lớp học phần nào.
                                    <a href="{{ route('lecturer.classes.create') }}">Tạo lớp mới</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center">
                {{ $classes->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

{{-- Copy mã lớp vào clipboard --}}
@push('scripts')
<script>
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.copy-code');
        if (!btn || btn.disabled) return;
        var code = (btn.getAttribute('data-code') || '').trim();
        if (!code) return;

        var done = function () {
            var icon = btn.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-copy');
                icon.classList.add('fa-check');
                setTimeout(function () {
                    icon.classList.remove('fa-check');
                    icon.classList.add('fa-copy');
                }, 1500);
            }
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(code).then(done).catch(done);
        } else {
            var tmp = document.createElement('textarea');
            tmp.value = code;
            tmp.style.position = 'fixed';
            tmp.style.opacity = '0';
            document.body.appendChild(tmp);
            tmp.select();
            try { document.execCommand('copy'); } catch (err) {}
            document.body.removeChild(tmp);
            done();
        }
    });
</script>
@endpush