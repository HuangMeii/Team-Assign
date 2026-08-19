@extends('layouts.app')

@section('title', 'Duyệt yêu cầu đăng ký đề tài')

@section('content')
    <div class="container">
        <h3 class="mb-4 fw-bold text-primary">Danh sách yêu cầu đăng ký đề tài</h3>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($topicRequests->isEmpty())
            <div class="alert alert-info text-center">
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
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>{{ $req->topic->name ?? '—' }}</td>
                                    <td>{{ $req->group->group_name ?? '—' }}</td>
                                    <td>{{ $req->user->name ?? '—' }}</td>
                                    <td class="text-center">
                                        @if($req->status === 'Pending')
                                            <span class="badge bg-warning text-dark">Đang chờ</span>
                                        @elseif($req->status === 'Accepted')
                                            <span class="badge bg-success">Đã duyệt</span>
                                        @else
                                            <span class="badge bg-danger">Từ chối</span>
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
                                            <form action="{{ route('topic_requests.reject', $req) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-times"></i> Từ chối
                                                </button>
                                            </form>
                               
                                           
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection