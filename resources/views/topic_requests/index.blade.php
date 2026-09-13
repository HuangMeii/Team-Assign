@extends('layouts.app')

@section('title', 'Duyệt yêu cầu đăng ký đề tài')

@section('content')
    <div class="container-fluid px-4">
        <h3 class="mt-4 fw-bold text-primary">Danh sách yêu cầu đăng ký đề tài</h3>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Duyệt đăng ký</li>
        </ol>

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

        {{-- Bugfix C5 [R16]: Filter row --}}
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('topic_requests.index') }}" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Tìm kiếm</label>
                        <input type="text" name="search" class="form-control" placeholder="Tên đề tài, nhóm, người gửi..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="">-- Tất cả --</option>
                            <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Đang chờ</option>
                            <option value="Accepted" {{ request('status') == 'Accepted' ? 'selected' : '' }}>Đã duyệt</option>
                            <option value="Rejected" {{ request('status') == 'Rejected' ? 'selected' : '' }}>Từ chối</option>
                        </select>
                    </div>
                    @if($classes->isNotEmpty())
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Lớp học phần</label>
                        <select name="class_id" class="form-select">
                            <option value="">-- Tất cả --</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->class_id }}" {{ request('class_id') == $class->class_id ? 'selected' : '' }}>
                                    {{ $class->class_name }} - {{ $class->subject->subject_name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Lọc
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if($topicRequests->isEmpty())
            <div class="alert alert-info text-center">
                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                Hiện chưa có yêu cầu nào được gửi.
            </div>
        @else
            <div class="card shadow border-0">
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-center">
                            <tr>
                                <th>STT</th>
                                <th>Tên đề tài</th>
                                {{-- Bugfix C5 [R16]: Thêm cột Môn học và Lớp --}}
                                <th>Môn học</th>
                                <th>Lớp</th>
                                <th>Tên nhóm</th>
                                <th>Người gửi</th>
                                <th>Trạng thái</th>
                                <th>Ngày gửi</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topicRequests as $index => $req)
                                <tr>
                                    <td class="text-center">{{ $topicRequests->firstItem() + $index }}</td>
                                    <td>{{ $req->topic->name ?? '—' }}</td>
                                    {{-- Bugfix C5 [R16]: Hiển thị Môn học và Lớp --}}
                                    <td>
                                        @if($req->topic && $req->topic->class && $req->topic->class->subject)
                                            <span class="badge bg-info text-dark">{{ $req->topic->class->subject->subject_name }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if($req->topic && $req->topic->class)
                                            {{ $req->topic->class->class_name }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $req->group->group_name ?? '—' }}</td>
                                    <td>{{ $req->user->name ?? '—' }}</td>
                                    <td class="text-center">
                                        @if($req->status === 'Pending')
                                            <span class="badge bg-warning text-dark">Đang chờ</span>
                                        @elseif($req->status === 'Accepted')
                                            <span class="badge bg-success">Đã duyệt</span>
                                        @else
                                            <span class="badge bg-danger">Từ chối</span>
                                            @if($req->rejection_reason)
                                                <div class="small text-muted mt-1" style="max-width: 200px;">
                                                    <i class="fas fa-comment-dots"></i> {{ $req->rejection_reason }}
                                                </div>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $req->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="text-center">
                                        @if($req->status === 'Pending')
                                            {{-- Nút DUYỆT --}}
                                            <form action="{{ route('topic_requests.approve', $req) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="fas fa-check"></i> Duyệt
                                                </button>
                                            </form>

                                            {{-- Nút TỪ CHỐI --}}
                                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $req->request_id }}">
                                                <i class="fas fa-times"></i> Từ chối
                                            </button>

                                            {{-- Modal nhập lý do từ chối --}}
                                            <div class="modal fade" id="rejectModal{{ $req->request_id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form action="{{ route('topic_requests.reject', $req) }}" method="POST">
                                                            @csrf
                                                            @method('PATCH')
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Từ chối yêu cầu đăng ký đề tài</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p class="text-muted">
                                                                    Đề tài: <strong>{{ $req->topic->name ?? '—' }}</strong><br>
                                                                    Nhóm: <strong>{{ $req->group->group_name ?? '—' }}</strong>
                                                                </p>
                                                                <label class="form-label fw-bold">Lý do từ chối</label>
                                                                <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Nhập lý do từ chối (bắt buộc)..." required></textarea>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                                <button type="submit" class="btn btn-danger">
                                                                    <i class="fas fa-times"></i> Xác nhận từ chối
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Bugfix C5 [R16]: Pagination --}}
            <div class="d-flex justify-content-end mt-3">
                {{ $topicRequests->links() }}
            </div>
        @endif
    </div>
@endsection