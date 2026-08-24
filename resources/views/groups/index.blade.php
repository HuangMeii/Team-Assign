@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-users"></i> Danh sách nhóm</h4>
        </div>

        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Filter Section -->
            <div class="card mb-4 border-0" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <div class="card-body">
                    <form method="GET" action="{{ route('groups.index') }}">
                        <div class="row g-3">
                            <!-- Search -->
                            <div class="col-md-5">
                                <label class="form-label text-white"><i class="fas fa-search"></i> Tìm kiếm</label>
                                <input type="text" 
                                       name="search" 
                                       value="{{ request('search') }}"
                                       class="form-control" 
                                       placeholder="Tên nhóm...">
                            </div>

                            <!-- Filter by Class -->
                            <div class="col-md-4">
                                <label class="form-label text-white"><i class="fas fa-chalkboard"></i> Lọc theo lớp</label>
                                <select name="class_id" class="form-select">
                                    <option value="">Tất cả lớp</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->class_id }}" 
                                                {{ request('class_id') == $class->class_id ? 'selected' : '' }}>
                                            {{ $class->class_name }} - {{ $class->subject->subject_name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Buttons -->
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-light me-2">
                                    <i class="fas fa-filter"></i> Lọc
                                </button>
                                <a href="{{ route('groups.index') }}" class="btn btn-outline-light">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Groups Grid -->
            <div class="row">
                @forelse($groups as $group)
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm hover-card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-users text-success"></i> {{ $group->group_name }}
                                </h5>
                                
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-chalkboard-teacher"></i> 
                                        {{ $group->class->class_name ?? 'Chưa có lớp' }}
                                    </small>
                                </div>

                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-user-tie"></i> 
                                        Nhóm trưởng: {{ $group->leader->name ?? 'Chưa có' }}
                                    </small>
                                </div>

                                <div class="mb-2">
                                    @php
                                        $totalMembers = $group->members->count() + 1;
                                        $maxMembers = $maxMembersByGroup[$group->group_id] ?? 5;
                                    @endphp
                                    <span class="badge bg-info">
                                        <i class="fas fa-user-friends"></i> {{ $totalMembers }}/{{ $maxMembers }} thành viên
                                    </span>
                                    @if($totalMembers >= $maxMembers)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle"></i> Đủ thành viên
                                        </span>
                                    @else
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-exclamation-circle"></i> Còn thiếu {{ $maxMembers - $totalMembers }}
                                        </span>
                                    @endif
                                </div>

                                <div class="mb-3">
                                    @if($group->topic_id)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle"></i> Đã có đề tài
                                        </span>
                                    @else
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-clock"></i> Chưa có đề tài
                                        </span>
                                    @endif
                                </div>

                                <div class="d-flex gap-2">
                                    <a href="{{ route('groups.show', $group->group_id) }}" 
                                       class="btn btn-sm btn-outline-primary flex-fill">
                                        <i class="fas fa-eye"></i> Xem
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có nhóm nào</p>
                    </div>
                @endforelse
            </div>

           
        </div>
    </div>
</div>

<style>
    .hover-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .hover-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2) !important;
    }
</style>
@endsection