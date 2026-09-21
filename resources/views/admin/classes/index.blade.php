@extends('layouts.app')

@section('title', 'Quản lý Lớp học phần')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Quản lý Lớp học phần</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Lớp học</li>
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

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-chalkboard-teacher me-1"></i>
                Danh sách Lớp học
            </div>
            <a href="{{ route('admin.classes.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i> Thêm mới
            </a>
        </div>
        <div class="card-body">
            {{-- Bugfix C6 [R24]: Multi-select filters --}}
            @php
                $selectedSubjectIds = array_map('strval', array_filter((array) request('subject_ids', [])));
                $selectedLecturerIds = array_map('strval', array_filter((array) request('lecturer_ids', [])));
                $selectedStatuses = array_filter((array) request('statuses', []));

                // Tương thích link cũ dùng tham số đơn (subject_id, lecturer_id, status)
                if (empty($selectedSubjectIds) && request()->filled('subject_id')) {
                    $selectedSubjectIds = [(string) request('subject_id')];
                }
                if (empty($selectedLecturerIds) && request()->filled('lecturer_id')) {
                    $selectedLecturerIds = [(string) request('lecturer_id')];
                }
                if (empty($selectedStatuses) && request()->filled('status')) {
                    $selectedStatuses = [request('status')];
                }

                $filterLabel = function (array $selected, array $names) {
                    $count = count($selected);
                    if ($count === 0) {
                        return '-- Tất cả --';
                    }
                    if ($count === 1) {
                        return $names[(string) reset($selected)] ?? '1 mục đã chọn';
                    }
                    return $count . ' mục đã chọn';
                };

                $subjectNames = $subjects->pluck('subject_name', 'subject_id')->all();
                $lecturerNames = $lecturers->pluck('name', 'user_id')->all();
                $statusNames = ['active' => 'Hoạt động', 'locked' => 'Đã khóa'];
            @endphp
            <form method="GET" action="{{ route('admin.classes.index') }}" class="row g-2 mb-4 align-items-end" id="filterForm">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Môn học</label>
                    <div class="dropdown" id="subjectDropdown">
                        <button class="btn btn-outline-secondary w-100 text-start d-flex justify-content-between align-items-center" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                            <span id="subjectLabel">{{ $filterLabel($selectedSubjectIds, $subjectNames) }}</span>
                            <i class="fas fa-caret-down"></i>
                        </button>
                        <div class="dropdown-menu w-100 p-2" style="max-height: 280px; overflow-y: auto;">
                            <input type="text" id="subjectSearch" class="form-control form-control-sm mb-2" placeholder="Tìm môn học...">
                            @forelse($subjects as $subj)
                                <label class="dropdown-item d-flex align-items-center gap-2">
                                    <input class="form-check-input filter-checkbox m-0" type="checkbox" name="subject_ids[]" value="{{ $subj->subject_id }}" {{ in_array((string) $subj->subject_id, $selectedSubjectIds, true) ? 'checked' : '' }}>
                                    <span>{{ $subj->subject_name }}</span>
                                </label>
                            @empty
                                <span class="dropdown-item-text text-muted small">Chưa có môn học</span>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Giảng viên</label>
                    <div class="dropdown" id="lecturerDropdown">
                        <button class="btn btn-outline-secondary w-100 text-start d-flex justify-content-between align-items-center" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                            <span id="lecturerLabel">{{ $filterLabel($selectedLecturerIds, $lecturerNames) }}</span>
                            <i class="fas fa-caret-down"></i>
                        </button>
                        <div class="dropdown-menu w-100 p-2" style="max-height: 280px; overflow-y: auto;">
                            <input type="text" id="lecturerSearch" class="form-control form-control-sm mb-2" placeholder="Tìm giảng viên...">
                            @forelse($lecturers as $lec)
                                <label class="dropdown-item d-flex align-items-center gap-2">
                                    <input class="form-check-input filter-checkbox m-0" type="checkbox" name="lecturer_ids[]" value="{{ $lec->user_id }}" {{ in_array((string) $lec->user_id, $selectedLecturerIds, true) ? 'checked' : '' }}>
                                    <span>{{ $lec->name }}</span>
                                </label>
                            @empty
                                <span class="dropdown-item-text text-muted small">Chưa có giảng viên</span>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Trạng thái</label>
                    <div class="dropdown" id="statusDropdown">
                        <button class="btn btn-outline-secondary w-100 text-start d-flex justify-content-between align-items-center" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                            <span id="statusLabel">{{ $filterLabel(array_values($selectedStatuses), $statusNames) }}</span>
                            <i class="fas fa-caret-down"></i>
                        </button>
                        <div class="dropdown-menu w-100 p-2">
                            @foreach($statusNames as $statusValue => $statusText)
                                <label class="dropdown-item d-flex align-items-center gap-2">
                                    <input class="form-check-input filter-checkbox m-0" type="checkbox" name="statuses[]" value="{{ $statusValue }}" {{ in_array($statusValue, $selectedStatuses, true) ? 'checked' : '' }}>
                                    <span>{{ $statusText }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">Tìm kiếm</label>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Nhập tên lớp..." value="{{ request('search') }}">
                        <button class="btn btn-secondary" type="submit"><i class="fas fa-search"></i></button>
                        <a href="{{ route('admin.classes.index') }}" class="btn btn-outline-secondary" title="Reset">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tên lớp</th>
                            <th>Môn học</th>
                            <th>Giảng viên</th>
                            <th class="text-center">Sinh viên</th>
                            <th class="text-center">Nhóm</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-center" style="width: 210px;">Hành động</th>
                        </tr>

                    </thead>
                    <tbody>
                        @forelse($classes as $class)
                            @php
                                $toggleAction = $class->is_active ? 'khóa' : 'mở khóa';
                            @endphp
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
                                <td>
                                    @if($class->lecturer)
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width: 30px; height: 30px; font-size: 12px;">
                                                {{ strtoupper(substr($class->lecturer->name, 0, 1)) }}
                                            </div>
                                            <span>{{ $class->lecturer->name }}</span>
                                        </div>
                                    @else
                                        <span class="badge bg-warning text-dark">Chưa phân công</span>
                                    @endif
                                </td>
                                
                                <td class="text-center">
                                    <span class="badge bg-primary">{{ $class->students_count }}</span>
                                </td>
                                
                                <td class="text-center">
                                    <span class="badge bg-info text-dark">{{ $class->groups_count }}</span>
                                </td>

                                <td class="text-center">
                                    @if($class->is_active)
                                        <span class="badge bg-success">Hoạt động</span>
                                    @else
                                        <span class="badge bg-danger">Đã khóa</span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                    <a href="{{ route('admin.classes.show', $class->class_id) }}" class="btn btn-info btn-sm text-white" title="Chi tiết & quản lý sinh viên" data-bs-toggle="tooltip">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.classes.edit', $class->class_id) }}" class="btn btn-warning btn-sm" title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.classes.toggle-active', $class->class_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn {{ $toggleAction }} lớp này?');">


                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm {{ $class->is_active ? 'btn-secondary' : 'btn-success' }}" title="{{ $class->is_active ? 'Khóa lớp' : 'Mở khóa lớp' }}">
                                                <i class="fas {{ $class->is_active ? 'fa-lock' : 'fa-unlock' }}"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.classes.destroy', $class->class_id) }}" method="POST" class="d-inline" onsubmit="return confirm('CẢNH BÁO: Bạn có chắc chắn muốn xóa lớp {{ $class->class_name }}? Hành động này không thể hoàn tác!');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Xóa lớp">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">

                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                    Không tìm thấy lớp học phần nào phù hợp.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                {{ $classes->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

{{-- Bugfix C6 [R24]: Multi-select filter JavaScript --}}
@push('scripts')
<script>
    // Bugfix C6 [R24]: Multi-select dropdown cho filter lớp học phần
    (function () {
        function updateLabel(dropdownId, labelId, items, allText) {
            const checked = document.querySelectorAll(dropdownId + ' .filter-checkbox:checked');
            const label = document.getElementById(labelId);
            if (checked.length === 0) {
                label.textContent = allText;
            } else if (checked.length === 1) {
                const text = checked[0].nextElementSibling.textContent.trim();
                label.textContent = text;
            } else {
                label.textContent = checked.length + ' mục đã chọn';
            }
        }

        // Subject filter
        const subjectCheckboxes = document.querySelectorAll('#subjectDropdown').length > 0 ?
            document.querySelectorAll('input[name="subject_ids[]"]') : [];
        const lecturerCheckboxes = document.querySelectorAll('input[name="lecturer_ids[]"]');
        const statusCheckboxes = document.querySelectorAll('input[name="statuses[]"]');

        // Search functionality for dropdowns
        const subjectSearch = document.getElementById('subjectSearch');
        if (subjectSearch) {
            subjectSearch.addEventListener('input', function () {
                const keyword = this.value.toLowerCase();
                const items = subjectSearch.closest('.dropdown-menu').querySelectorAll('.dropdown-item');
                items.forEach(function (item) {
                    const text = item.textContent.toLowerCase();
                    item.style.display = text.includes(keyword) ? '' : 'none';
                });
            });
        }

        const lecturerSearch = document.getElementById('lecturerSearch');
        if (lecturerSearch) {
            lecturerSearch.addEventListener('input', function () {
                const keyword = this.value.toLowerCase();
                const items = lecturerSearch.closest('.dropdown-menu').querySelectorAll('.dropdown-item');
                items.forEach(function (item) {
                    const text = item.textContent.toLowerCase();
                    item.style.display = text.includes(keyword) ? '' : 'none';
                });
            });
        }

        // Auto-submit on checkbox change
        document.querySelectorAll('.filter-checkbox').forEach(function (cb) {
            cb.addEventListener('change', function () {
                // Update label
                if (this.name === 'subject_ids[]') {
                    updateLabel('#subjectDropdown', 'subjectLabel', subjectCheckboxes, '-- Tất cả --');
                } else if (this.name === 'lecturer_ids[]') {
                    updateLabel('#lecturerDropdown', 'lecturerLabel', lecturerCheckboxes, '-- Tất cả --');
                } else if (this.name === 'statuses[]') {
                    updateLabel('#statusDropdown', 'statusLabel', statusCheckboxes, '-- Tất cả --');
                }
                // Submit form
                document.getElementById('filterForm').submit();
            });
        });

        // Prevent dropdown close on checkbox click
        document.querySelectorAll('.dropdown-menu').forEach(function (menu) {
            menu.addEventListener('click', function (e) {
                if (e.target.classList.contains('filter-checkbox') || e.target.tagName === 'LABEL') {
                    e.stopPropagation();
                }
            });
        });
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