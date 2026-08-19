@extends('layouts.app') 

@section('title', 'Quản lý người dùng')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Quản lý người dùng</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Người dùng</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-table me-1"></i>
                Danh sách tài khoản
            </div>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i> Thêm mới
            </a>
        </div>
        <div class="card-body">
            {{-- Filter & Search Form --}}
            <form method="GET" action="{{ route('admin.users.index') }}" class="row g-3 mb-4">
                <div class="col-md-3">
                    <select name="role" class="form-select">
                        <option value="">-- Tất cả vai trò --</option>
                        <option value="student" {{ request('role') == 'student' ? 'selected' : '' }}>Sinh viên</option>
                        <option value="lecturer" {{ request('role') == 'lecturer' ? 'selected' : '' }}>Giảng viên</option>
                        <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Tìm tên hoặc email..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">
                        <i class="fas fa-search me-1"></i> Lọc
                    </button>
                </div>
            </form>

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>Vai trò</th>
                            <th>Ngày tạo</th>
                            <th class="text-center" style="width: 150px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>{{ $user->user_id }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @if($user->role === 'admin')
                                        <span class="badge bg-danger">Admin</span>
                                    @elseif($user->role === 'lecturer')
                                        <span class="badge bg-primary">Giảng viên</span>
                                    @else
                                        <span class="badge bg-success">Sinh viên</span>
                                    @endif
                                </td>
                                <td>{{ $user->created_at->format('d/m/Y') }}</td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.users.edit', $user->user_id) }}" class="btn btn-warning btn-sm" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        {{-- Không cho xóa chính mình --}}
                                        @if(Auth::id() !== $user->user_id)
                                            <form action="{{ route('admin.users.destroy', $user->user_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa người dùng này? Hành động này không thể hoàn tác!');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Không tìm thấy người dùng nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
          
        </div>
    </div>
</div>
@endsection