@props(['id' => 'student-picker-modal', 'students' => collect(), 'action' => '#', 'title' => 'Them sinh vien vao lop'])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ $action }}" data-student-picker-form>
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-user-plus text-primary me-2"></i>{{ $title }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 align-items-center mb-3">
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" placeholder="Tim theo ten hoac email..." data-student-picker-search>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <span class="badge bg-primary fs-6" data-student-picker-count>Da chon: 0</span>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mb-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-student-picker-select-visible>
                            <i class="fas fa-check-double me-1"></i>Chon tat ca dang hien
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-student-picker-clear>
                            <i class="fas fa-times me-1"></i>Bo chon
                        </button>
                    </div>

                    <div class="table-responsive border rounded" style="max-height: 380px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 44px;"><input type="checkbox" class="form-check-input" data-student-picker-check-all></th>
                                    <th>Ho ten</th>
                                    <th>Email</th>
                                    <th>Nhom / Lop</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $student)
                                    @php
                                        $groupName = optional($student->groupsLed->first())->group_name
                                            ?? optional($student->groupsJoined->first())->group_name;
                                        $classNames = $student->classes->pluck('class_name')->take(2)->implode(', ');
                                        if ($student->classes->count() > 2) {
                                            $classNames .= ' (+' . ($student->classes->count() - 2) . ')';
                                        }
                                    @endphp
                                    <tr data-student-picker-row data-search="{{ strtolower($student->name . ' ' . $student->email) }}">
                                        <td>
                                            <input type="checkbox" class="form-check-input" name="student_ids[]" value="{{ $student->user_id }}" data-student-picker-check>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="rounded-circle bg-primary text-white d-inline-flex justify-content-center align-items-center fw-bold" style="width: 34px; height: 34px;">
                                                    {{ mb_strtoupper(mb_substr(trim($student->name), 0, 1)) }}
                                                </span>
                                                <span class="fw-semibold">{{ $student->name }}</span>
                                            </div>
                                        </td>
                                        <td class="text-muted small">{{ $student->email }}</td>
                                        <td class="small">
                                            @if($groupName)
                                                <span class="badge bg-success">{{ $groupName }}</span>
                                            @else
                                                <span class="badge bg-secondary">Chua co nhom</span>
                                            @endif
                                            @if($classNames)
                                                <div class="text-muted mt-1"><i class="fas fa-chalkboard me-1"></i>{{ $classNames }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Khong con sinh vien nao de them.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted d-block mt-2">Giu dau tick khi loc tim kiem. Sinh vien da co trong lop se bi bo qua o server.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huy</button>
                    <button type="submit" class="btn btn-success" data-student-picker-submit disabled>
                        <i class="fas fa-user-plus me-2"></i>Them da chon
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
