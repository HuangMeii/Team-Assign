@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-plus-circle"></i> Tạo nhóm mới</h4>
                </div>

                <div class="card-body p-4">
                    <form action="{{ route('groups.store') }}" method="POST">
                        @csrf

                        <!-- Group Name -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-users text-success"></i> Tên nhóm <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   name="group_name" 
                                   value="{{ old('group_name') }}"
                                   class="form-control form-control-lg @error('group_name') is-invalid @enderror"
                                   placeholder="Nhập tên nhóm..."
                                   required>
                            @error('group_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Class -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-chalkboard-teacher text-info"></i> Lớp học phần <span class="text-danger">*</span>
                            </label>
                            <select name="class_id" 
                                    class="form-select form-select-lg @error('class_id') is-invalid @enderror"
                                    required>
                                <option value="">-- Chọn lớp học phần --</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->class_id }}" {{ old('class_id') == $class->class_id ? 'selected' : '' }}>
                                        {{ $class->class_name }} - {{ $class->subject->subject_name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('class_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Leader (you can add later, for now just set to current user or select) -->
                        <input type="hidden" name="leader_id" value="{{ auth()->user()->user_id }}">

                        <!-- Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save"></i> Tạo nhóm
                            </button>
                            <a href="{{ route('groups.index') }}" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times"></i> Hủy
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection