@extends('layouts.app')

@section('title', 'Chi tiết đề tài')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-lg border-0 mb-4">
                    <div class="card-header bg-info text-white py-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">
                                <i class="fas fa-book"></i> Chi tiết đề tài
                            </h4>
                            <small>ID: {{ $topic->topic_id }}</small>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <div class="row mb-4">
                            <div class="col-12">
                                <h3 class="text-primary fw-bold mb-3">
                                    {{ $topic->name }}
                                </h3>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="bg-light p-3 rounded-3 mb-3">
                                    <label class="fw-bold text-muted small">
                                        <i class="fas fa-user-tie"></i> GIẢNG VIÊN HƯỚNG DẪN
                                    </label>
                                    <p class="mb-0 mt-2 fs-5">{{ $topic->lecturer }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="bg-light p-3 rounded-3 mb-3">
                                    <label class="fw-bold text-muted small">
                                        <i class="fas fa-calendar-alt"></i> NGÀY TẠO
                                    </label>
                                    <p class="mb-0 mt-2 fs-5">{{ $topic->created_at?->format('d/m/Y H:i') ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="fw-bold text-muted small">
                                <i class="fas fa-align-left"></i> MÔ TẢ CHI TIẾT
                            </label>
                            <div class="bg-light p-3 rounded-3 mt-2 border-start border-4 border-primary">
                                <p class="mb-0 text-justify">{{ nl2br($topic->description) }}</p>
                            </div>
                        </div>

                        @if ($topic->goal)
                            <div class="mb-4">
                                <label class="fw-bold text-muted small">
                                    <i class="fas fa-target"></i> MỤC TIÊU
                                </label>
                                <div class="bg-light p-3 rounded-3 mt-2 border-start border-4 border-success">
                                    <p class="mb-0 text-justify">{{ nl2br($topic->goal) }}</p>
                                </div>
                            </div>
                        @endif

                        @if ($topic->requirements)
                            <div class="mb-4">
                                <label class="fw-bold text-muted small">
                                    <i class="fas fa-list-check"></i> YÊU CẦU
                                </label>
                                <div class="bg-light p-3 rounded-3 mt-2 border-start border-4 border-warning">
                                    <p class="mb-0 text-justify">{{ nl2br($topic->requirements) }}</p>
                                </div>
                            </div>
                        @endif

                        <hr class="my-4">

                        <div class="d-flex gap-2">
                            <a href="{{ route('topics.edit', $topic) }}" class="btn btn-warning btn-lg flex-grow-1">
                                <i class="fas fa-edit"></i> Chỉnh sửa
                            </a>
                            <form action="{{ route('topics.destroy', $topic) }}" method="POST" class="flex-grow-1" onsubmit="return confirm('Bạn chắc chắn muốn xóa đề tài này?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-lg w-100">
                                    <i class="fas fa-trash"></i> Xóa
                                </button>
                            </form>
                            <a href="{{ route('topics.index') }}" class="btn btn-secondary btn-lg flex-grow-1">
                                <i class="fas fa-arrow-left"></i> Quay lại
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Danh sách nhóm đăng ký -->
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-success text-white py-4">
                        <h5 class="mb-0">
                            <i class="fas fa-users"></i> Danh sách nhóm đăng ký
                        </h5>
                    </div>

                    <div class="card-body p-4">
                        @php
                            $requests = \App\Models\Topic_requests::where('topic_id', $topic->topic_id)
                                ->with(['group', 'user'])
                                ->get();
                        @endphp

                        @if ($requests->isEmpty())
                            <div class="alert alert-info text-center">
                                <i class="fas fa-inbox"></i>
                                <p class="mt-2 mb-0">Hiện chưa có nhóm nào đăng ký đề tài này</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Tên nhóm</th>
                                            <th>Người đăng ký</th>
                                            <th>Trạng thái</th>
                                            <th>Ngày đăng ký</th>
                                        </tr>
                                    </thead>
                                   <tbody>
                                        @foreach ($requests as $index => $req)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <strong>{{ $req->group->group_name ?? '—' }}</strong>
                                                </td>
                                                <td>{{ $req->user->name ?? '—' }}</td>
                                                <td class="text-center">
                                                    @php
                                                        // Lấy ID nhóm đang được gán chính thức cho đề tài này
                                                        $assignedGroupId = $req->topic->assigned_group_id ?? null;
                                                        $currentGroupId  = $req->group_id;
                                                    @endphp

                                                    @if ($assignedGroupId)
                                                        {{-- Trường hợp 1: Đề tài ĐÃ CÓ chủ --}}
                                                        @if ($assignedGroupId == $currentGroupId)
                                                            {{-- Chính là nhóm này --}}
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check-circle me-1"></i>Đã duyệt
                                                            </span>
                                                        @else
                                                            {{-- Là nhóm khác --}}
                                                            <span class="badge bg-secondary" title="Đề tài đã được gán cho nhóm khác">
                                                                <i class="fas fa-ban me-1"></i>Đã có nhóm khác
                                                            </span>
                                                        @endif
                                                    @else
                                                        {{-- Trường hợp 2: Đề tài CHƯA CÓ chủ (Null) -> Check trạng thái request --}}
                                                        @if ($req->status === 'Rejected')
                                                            <span class="badge bg-danger">Từ chối</span>
                                                        @else
                                                            <span class="badge bg-warning text-dark">
                                                                <i class="fas fa-clock me-1"></i>Đang chờ
                                                            </span>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td>{{ $req->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection