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
                    @php
                        // L05: phân biệt sinh viên ĐANG HỌC / ĐÃ RỜI LỚP (pivot user_classes.status)
                        $activeStudentCount = $students->filter(fn ($s) => ($s->pivot->status ?? 'studying') === 'studying')->count();
                        $leftStudentCount = $students->count() - $activeStudentCount;
                    @endphp
                    <div class="fw-bold">
                        {{ $activeStudentCount }} đang học
                        @if($leftStudentCount > 0)
                            <span class="text-muted small">· {{ $leftStudentCount }} đã rời</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted small">Nhóm</label>
                    <div class="fw-bold">{{ $groups->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bảng tin lớp (kiểu Google Classroom): thông báo của GV + hoạt động nhóm --}}
    <x-class-stream-preview :class-id="$class->class_id" />

    <ul class="nav nav-tabs mb-3" id="classDetailTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="students-tab" data-bs-toggle="tab" data-bs-target="#students" type="button">
                <i class="fas fa-user-graduate"></i> Danh sách sinh viên ({{ $activeStudentCount }} đang học)
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
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>
                        <i class="fas fa-user-graduate me-1"></i>
                        Sinh viên của lớp ({{ $activeStudentCount }} đang học @if($leftStudentCount > 0)· {{ $leftStudentCount }} đã rời @endif)
                    </span>
                    {{-- L05: bộ lọc trạng thái Đang học / Đã rời lớp --}}
                    <span class="btn-group btn-group-sm" role="group" aria-label="Lọc trạng thái sinh viên">
                        <a href="{{ route('admin.classes.show', $class->class_id) }}"
                           class="btn btn-outline-secondary {{ request('status') ? '' : 'active' }}">Tất cả</a>
                        <a href="{{ route('admin.classes.show', [$class->class_id, 'status' => 'studying']) }}"
                           class="btn btn-outline-success {{ request('status') === 'studying' ? 'active' : '' }}">Đang học</a>
                        <a href="{{ route('admin.classes.show', [$class->class_id, 'status' => 'left']) }}"
                           class="btn btn-outline-secondary {{ request('status') === 'left' ? 'active' : '' }}">Đã rời</a>
                    </span>
                </div>
                <div class="card-body border-bottom">
                    @if($availableStudents->isEmpty())
                        <p class="text-muted small mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            Tất cả tài khoản sinh viên trong hệ thống đã tham gia lớp học phần này.
                        </p>
                    @else
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <span class="small text-muted">Tìm kiếm, tick chọn nhiều sinh viên (kèm nhóm/lớp hiện tại) rồi thêm cùng lúc.</span>
                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#adminStudentPickerModal">
                                <i class="fas fa-user-plus me-1"></i>Chọn sinh viên
                            </button>
                        </div>
                        @error('student_ids')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    @endif
                    @include('components.student-picker-modal', [
                        'id' => 'adminStudentPickerModal',
                        'students' => $availableStudents,
                        'action' => route('admin.classes.students.add', $class->class_id),
                        'title' => 'Thêm sinh viên vào lớp ' . $class->class_name,
                    ])
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Tên sinh viên</th>
                                    <th>Email</th>
                                    <th>Trạng thái</th>
                                    <th>Nhóm</th>
                                    <th class="text-end">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $student)
                                    @php
                                        // L05: trạng thái từng (sinh viên, lớp) nằm ở pivot user_classes.status
                                        $isLeft = ($student->pivot->status ?? 'studying') === 'left';
                                    @endphp
                                    <tr class="{{ $isLeft ? 'opacity-60 bg-light' : '' }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <a href="{{ route('students.show', $student->user_id) }}"
                                               class="fw-semibold text-decoration-none" title="Xem chi tiết sinh viên">
                                                {{ $student->name }}
                                            </a>
                                        </td>
                                        <td>{{ $student->email }}</td>
                                        <td>
                                            @if($isLeft)
                                                <span class="badge bg-secondary">Đã rời lớp</span>
                                                @if($student->pivot->left_at)
                                                    <div class="text-muted small">{{ \Illuminate\Support\Carbon::parse($student->pivot->left_at)->displayTz()->format('d/m/Y H:i') }}</div>
                                                @endif
                                            @else
                                                <span class="badge bg-success">Đang học</span>
                                            @endif
                                        </td>
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
                                            @if($isLeft)
                                                {{-- L05: sinh viên đã rời lớp -> cho quay lại lớp (khôi phục 'studying',
                                                     và nếu là trưởng nhóm thì nhóm cũ hồi sinh) --}}
                                                <form action="{{ route('admin.classes.students.add', $class->class_id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="student_ids[]" value="{{ $student->user_id }}">
                                                    <button type="submit" class="btn btn-outline-success btn-sm"
                                                            onclick="return confirm('Thêm lại {{ $student->name }} vào lớp {{ $class->class_name }}?');">
                                                        <i class="fas fa-user-plus"></i> Thêm lại
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.classes.students.remove', [$class->class_id, $student->user_id]) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('Bạn có chắc muốn cho {{ $student->name }} rời khỏi lớp {{ $class->class_name }}?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-user-minus"></i> Cho rời lớp
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
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
                                            // L05 (Chốt 2a): đếm thành viên ĐANG HỌC; nhóm 0 thành viên
                                            // = nhóm "ghost" giữ lại lịch sử (trưởng nhóm = người cuối cùng rời lớp).
                                            $memberCount = $group->activeMemberCount();
                                        @endphp
                                        <tr class="{{ $memberCount === 0 ? 'opacity-60 bg-light' : '' }}">
                                            <td>{{ $group->group_name }}</td>
                                            <td>{{ $group->leader->name ?? 'N/A' }}</td>
                                            <td>
                                                {{ $memberCount }}
                                                @if($memberCount === 0)
                                                    <span class="badge bg-secondary ms-1">Đã rời hết</span>
                                                @endif
                                            </td>
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

{{-- Modal chon nhieu sinh vien (JS dung chung) --}}
@push('scripts')
<script src="{{ asset('js/student-picker.js') }}"></script>
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
