@extends('layouts.app')

@section('title', 'Chi tiết lớp - ' . $class->class_name)

@section('content')
<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="{{ route('lecturer.classes.index') }}">Lớp học của tôi</a></li>
            <li class="breadcrumb-item active">{{ $class->class_name }}</li>
        </ol>
    </nav>

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

    {{-- Thông tin lớp --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-chalkboard-teacher"></i> {{ $class->class_name }}</h4>
            @if($class->is_active)
                <span class="badge bg-success fs-6">Hoạt động</span>
            @else
                <span class="badge bg-danger fs-6">Đã khóa</span>
            @endif
        </div>
        <div class="card-body">
            <div class="row g-4 align-items-center">
                <div class="col-md-4">
                    <p class="text-muted small mb-1">Môn học</p>
                    <p class="h5 fw-bold mb-0">
                        {{ $class->subject->subject_name ?? 'Chưa gán' }}
                        @if($class->subject)
                            <span class="text-muted small">({{ $class->subject->subject_code }})</span>
                        @endif
                    </p>
                </div>
                <div class="col-md-3">
                    <p class="text-muted small mb-1">Sinh viên / Nhóm</p>
                    <p class="h5 fw-bold mb-0">{{ $class->students_count }} SV · {{ $class->groups_count }} nhóm</p>
                </div>
                <div class="col-md-5">
                    <p class="text-muted small mb-1"><i class="fas fa-key"></i> Mã lớp tham gia</p>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-4 fw-bold font-monospace text-success">{{ $class->class_code ?? 'Chưa có mã' }}</span>
                        @if($class->class_code)
                            <button type="button" class="btn btn-outline-success btn-sm copy-code"
                                data-code="{{ $class->class_code }}">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        @endif
                    </div>
                    <small class="text-muted">Sinh viên tham gia lớp bằng mã này (mục "Lớp học" phía sinh viên).</small>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <form action="{{ route('lecturer.classes.toggle-active', $class->class_id) }}" method="POST"
                    onsubmit="return confirm('Bạn có chắc chắn muốn {{ $class->is_active ? 'khóa' : 'mở khóa' }} lớp này?');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn {{ $class->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                        <i class="fas {{ $class->is_active ? 'fa-lock' : 'fa-unlock' }}"></i>
                        {{ $class->is_active ? 'Khóa lớp' : 'Mở khóa lớp' }}
                    </button>
                </form>
                <a href="{{ route('lecturer.classes.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>
        </div>
    </div>

    {{-- Bảng tin lớp (kiểu Google Classroom): thông báo của GV + hoạt động nhóm --}}
    <x-class-stream-preview :class-id="$class->class_id" />

    {{-- Tabs: sinh viên / nhóm --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#students">
                <i class="fas fa-user-graduate"></i> Danh sách sinh viên ({{ $students->count() }})
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#groups">
                <i class="fas fa-users"></i> Danh sách nhóm ({{ $groups->count() }})
            </button>
        </li>
    </ul>

    <div class="tab-content">
        {{-- Tab sinh viên --}}
        <div class="tab-pane fade show active" id="students">
            <div class="card shadow-sm">
                <div class="card-header"><i class="fas fa-user-plus"></i> Thêm sinh viên vào lớp</div>
                <div class="card-body">
                    <form action="{{ route('lecturer.classes.students.add', $class->class_id) }}" method="POST">
                        @csrf
                        <div class="row g-2 mb-2 align-items-center">
                            <div class="col-md-7">
                                <input type="text" id="studentSearch" class="form-control"
                                    placeholder="Tìm theo tên hoặc email...">
                            </div>
                            <div class="col-md-5 text-md-end">
                                <button type="button" id="studentSelectAll" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-check-double"></i> Chọn tất cả
                                </button>
                            </div>
                        </div>
                        <select name="student_ids[]" id="studentSelect" class="form-select" size="6" multiple>
                            @forelse($availableStudents as $student)
                                <option value="{{ $student->user_id }}">
                                    {{ $student->name }} ({{ $student->email }})
                                </option>
                            @empty
                                <option disabled>Tất cả sinh viên đã tham gia lớp này.</option>
                            @endforelse
                        </select>
                        @error('student_ids')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Thêm vào lớp
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header"><i class="fas fa-users"></i> Sinh viên đang tham gia ({{ $students->count() }})</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Họ tên</th>
                                    <th>Email</th>
                                    <th>Nhóm</th>
                                    <th class="text-center" style="width: 90px;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $index => $student)
                                    @php
                                        $studentGroups = $student->groupsJoined
                                            ->merge($student->groupsLed)
                                            ->where('class_id', $class->class_id)
                                            ->unique('group_id');
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td class="fw-bold">{{ $student->name }}</td>
                                        <td>{{ $student->email }}</td>
                                        <td>
                                            @if($studentGroups->isNotEmpty())
                                                @foreach($studentGroups as $g)
                                                    <span class="badge bg-secondary">{{ $g->group_name }}</span>
                                                    @if((int) $g->leader_id === (int) $student->user_id)
                                                        <span class="badge bg-success">Trưởng nhóm</span>
                                                    @endif
                                                @endforeach
                                            @else
                                                <span class="text-muted small">Chưa có nhóm</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <form action="{{ route('lecturer.classes.students.remove', [$class->class_id, $student->user_id]) }}"
                                                method="POST"
                                                onsubmit="return confirm('Xóa sinh viên {{ $student->name }} khỏi lớp? Sinh viên cũng sẽ bị xóa khỏi nhóm trong lớp này.');">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Xóa khỏi lớp">
                                                    <i class="fas fa-user-minus"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Chưa có sinh viên nào trong lớp.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab nhóm --}}
        <div class="tab-pane fade" id="groups">
            <div class="card shadow-sm">
                <div class="card-header"><i class="fas fa-users"></i> Nhóm trong lớp</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Tên nhóm</th>
                                    <th>Trưởng nhóm</th>
                                    <th class="text-center">Thành viên</th>
                                    <th class="text-center">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($groups as $index => $group)
                                    @php
                                        // Quy ước đếm: thành viên = số dòng pivot + 1 trưởng nhóm
                                        $memberCount = $group->members->count() + 1;
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td class="fw-bold">{{ $group->group_name }}</td>
                                        <td>{{ $group->leader->name ?? 'N/A' }}</td>
                                        <td class="text-center">{{ $memberCount }}</td>
                                        <td class="text-center">
                                            @if($group->status === 'complete')
                                                <span class="badge bg-success">Đủ thành viên</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Chưa đủ</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Lớp chưa có nhóm nào.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Tìm kiếm & chọn nhanh sinh viên khi thêm vào lớp
    (function () {
        var search = document.getElementById('studentSearch');
        var select = document.getElementById('studentSelect');
        var selectAll = document.getElementById('studentSelectAll');
        if (!search || !select) return;

        function isVisible(option) {
            return option.style.display !== 'none' && !option.hidden;
        }

        search.addEventListener('input', function () {
            var keyword = this.value.toLowerCase().trim();
            Array.prototype.forEach.call(select.options, function (option) {
                var match = option.textContent.toLowerCase().indexOf(keyword) !== -1;
                option.hidden = !match;
                option.style.display = match ? '' : 'none';
                if (!match) option.selected = false;
            });
        });

        if (selectAll) {
            selectAll.addEventListener('click', function () {
                Array.prototype.forEach.call(select.options, function (option) {
                    if (isVisible(option)) option.selected = true;
                });
            });
        }
    })();

    // Copy mã lớp vào clipboard
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