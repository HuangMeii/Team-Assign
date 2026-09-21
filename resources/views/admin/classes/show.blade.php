@extends('layouts.app')

@section('title', 'Chi tiết Lớp học - ' . $class->class_name)

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Chi tiết Lớp học phần</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.classes.index') }}">Lớp học</a></li>
        <li class="breadcrumb-item active">{{ $class->class_name }}</li>
    </ol>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Class Info Header --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-chalkboard-teacher me-1"></i>
                <span class="fw-bold">{{ $class->class_name }}</span>
                <span class="badge bg-warning text-dark align-middle">
                    <i class="fas fa-key me-1"></i> Mã lớp: {{ $class->class_code ?? 'Chưa có' }}
                </span>
                @if($class->class_code)
                    <button type="button" class="btn btn-outline-dark btn-sm ms-1 copy-code"
                        data-code="{{ $class->class_code }}" title="Copy mã lớp">
                        <i class="fas fa-copy"></i>
                    </button>
                @endif
            </div>
            <div>
                @if($class->is_active)
                    <span class="badge bg-success">Hoạt động</span>
                @else
                    <span class="badge bg-danger">Đã khóa</span>
                @endif
                <a href="{{ route('admin.classes.edit', $class->class_id) }}" class="btn btn-warning btn-sm ms-2">
                    <i class="fas fa-edit"></i> Chỉnh sửa
                </a>
                <a href="{{ route('admin.classes.index') }}" class="btn btn-secondary btn-sm ms-1">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label text-muted small">Môn học</label>
                    <div class="fw-bold">{{ $class->subject->subject_name ?? 'Chưa gán' }}
                        @if($class->subject)
                            <span class="text-muted small">({{ $class->subject->subject_code ?? '' }})</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small">Giảng viên phụ trách</label>
                    <div class="fw-bold">
                        @if($class->lecturers->isNotEmpty())
                            @foreach($class->lecturers as $l)
                                <span class="badge bg-info text-dark">{{ $l->name }}</span>
                            @endforeach
                        @else
                            <span class="text-warning">Chưa phân công</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted small">Sinh viên</label>
                    <div class="fw-bold">{{ $students->count() }}</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted small">Nhóm</label>
                    <div class="fw-bold">{{ $groups->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3" id="classDetailTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="students-tab" data-bs-toggle="tab" data-bs-target="#students" type="button">
                <i class="fas fa-user-graduate"></i> Danh sách sinh viên ({{ $students->count() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="groups-tab" data-bs-toggle="tab" data-bs-target="#groups" type="button">
                <i class="fas fa-users"></i> Nhóm ({{ $groups->count() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="lecturer-tab" data-bs-toggle="tab" data-bs-target="#lecturer" type="button">
                <i class="fas fa-user-tie"></i> Thay đổi giảng viên
            </button>
        </li>
    </ul>

        <div class="tab-content">

        {{-- Students Tab --}}
        <div class="tab-pane fade show active" id="students" role="tabpanel">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user-graduate me-1"></i>
                    Sinh viên đã tham gia lớp ({{ $students->count() }})
                </div>
                <div class="card-body border-bottom">
                    @if($availableStudents->isEmpty())
                        <p class="text-muted small mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            Tất cả tài khoản sinh viên trong hệ thống đã tham gia lớp học phần này.
                        </p>
                    @else
                        <form action="{{ route('admin.classes.students.add', $class->class_id) }}" method="POST" id="addStudentForm">
                            @csrf
                            <label class="form-label small fw-bold mb-1">
                                <i class="fas fa-plus-circle text-primary me-1"></i> Thêm sinh viên vào lớp
                                <span class="text-muted fw-normal">(giữ Ctrl/Cmd để chọn nhiều sinh viên)</span>
                            </label>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <input type="text" id="studentSearch" class="form-control form-control-sm"
                                        placeholder="Tìm theo tên hoặc email...">
                                </div>
                                <div class="col-md-8">
                                    <div class="d-flex gap-2">
                                        <select name="student_ids[]" id="studentSelect" class="form-select form-select-sm" multiple size="6" required>
                                            @foreach($availableStudents as $sv)
                                                <option value="{{ $sv->user_id }}">{{ $sv->name }} ({{ $sv->email }})</option>
                                            @endforeach
                                        </select>
                                        <div class="d-flex flex-column gap-2">
                                            <button type="submit" class="btn btn-primary btn-sm text-nowrap">
                                                <i class="fas fa-plus"></i> Thêm
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm text-nowrap" id="studentSelectAll">
                                                Chọn tất cả
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @error('student_ids')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </form>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Tên sinh viên</th>
                                    <th>Email</th>
                                    <th>Nhóm</th>
                                    <th class="text-end">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $student)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $student->name }}</td>
                                        <td>{{ $student->email }}</td>
                                        <td>
                                            @php
                                                // Trưởng nhóm KHÔNG nằm trong bảng pivot group_members
                                                // (chỉ có groups.leader_id) nên phải xét cả nhóm đã tham gia
                                                // lẫn nhóm đang lãnh đạo, giới hạn trong lớp học phần này.
                                                $studentGroups = $student->groupsJoined
                                                    ->merge($student->groupsLed)
                                                    ->where('class_id', $class->class_id)
                                                    ->unique('group_id');
                                            @endphp
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
                                        <td class="text-end">
                                            <form action="{{ route('admin.classes.students.remove', [$class->class_id, $student->user_id]) }}" method="POST"
                                                  onsubmit="return confirm('Bạn có chắc muốn xóa {{ $student->name }} khỏi lớp {{ $class->class_name }}?');">
                                                @csrf
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-user-minus"></i> Xóa
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="fas fa-info-circle"></i> Chưa có sinh viên nào trong lớp
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
                </div>

        {{-- Groups Tab --}}
        <div class="tab-pane fade" id="groups" role="tabpanel">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-users"></i> Nhóm trong lớp ({{ $groups->count() }})
                </div>
                <div class="card-body p-0">
                    @if($groups->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tên nhóm</th>
                                        <th>Trưởng nhóm</th>
                                        <th>Thành viên</th>
                                        <th>Đề tài</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($groups as $group)
                                        @php
                                            $memberCount = $group->members->count() + 1;
                                        @endphp
                                        <tr>
                                            <td>{{ $group->group_name }}</td>
                                            <td>{{ $group->leader->name ?? 'N/A' }}</td>
                                            <td>{{ $memberCount }}</td>
                                            <td>
                                                @if($group->topic_id)
                                                    @php $topic = \App\Models\Topics::find($group->topic_id); @endphp
                                                    {{ $topic->name ?? 'N/A' }}
                                                @else
                                                    <span class="text-muted">Chưa có</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center text-muted py-4">
                            <i class="fas fa-users"></i> Chưa có nhóm nào trong lớp này
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Change Lecturer Tab --}}
        <div class="tab-pane fade" id="lecturer" role="tabpanel">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user-tie"></i> Thay đổi giảng viên phụ trách
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.classes.update', $class->class_id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <input type="hidden" name="class_name" value="{{ $class->class_name }}">
                        <input type="hidden" name="subject_id" value="{{ $class->subject_id }}">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="lecturer_id" class="form-label fw-bold">Chọn giảng viên mới</label>
                                <select name="lecturer_id" class="form-select" id="lecturer_id">
                                    <option value="">-- Không phân công --</option>
                                    @php
                                        $currentLecturerId = $class->lecturers->isNotEmpty() ? $class->lecturers->first()->user_id : null;
                                    @endphp
                                    @foreach($lecturers as $lecturer)
                                        <option value="{{ $lecturer->user_id }}" {{ old('lecturer_id', $currentLecturerId) == $lecturer->user_id ? 'selected' : '' }}>
                                            {{ $lecturer->name }} ({{ $lecturer->email }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.classes.edit', $class->class_id) }}" class="btn btn-secondary">
                                Chỉnh sửa đầy đủ
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Lưu thay đổi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
</div>
</div>
@endsection

{{-- Tìm kiếm & chọn nhanh sinh viên cần thêm vào lớp --}}
@push('scripts')
<script>
    (function () {
        const search = document.getElementById('studentSearch');
        const select = document.getElementById('studentSelect');
        const selectAll = document.getElementById('studentSelectAll');

        if (!search || !select) {
            return;
        }

        function isVisible(option) {
            return option.style.display !== 'none' && !option.hidden;
        }

        // Lọc danh sách sinh viên theo tên/email
        search.addEventListener('input', function () {
            const keyword = this.value.toLowerCase().trim();

            Array.from(select.options).forEach(function (option) {
                const match = option.textContent.toLowerCase().includes(keyword);
                option.hidden = !match;
                option.style.display = match ? '' : 'none';
                if (!match) {
                    option.selected = false;
                }
            });
        });

        // Chọn tất cả sinh viên đang hiển thị sau khi lọc
        if (selectAll) {
            selectAll.addEventListener('click', function () {
                Array.from(select.options).forEach(function (option) {
                    if (isVisible(option)) {
                        option.selected = true;
                    }
                });
            });
        }
    })();
</script>
@endpush

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
