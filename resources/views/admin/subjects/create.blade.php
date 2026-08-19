@extends('layouts.app')

@section('title', 'Thêm Môn học mới')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Thêm Môn học</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.subjects.index') }}">Môn học</a></li>
        <li class="breadcrumb-item active">Thêm mới</li>
    </ol>

    <div class="card mb-4" style="max-width: 800px;">
        <div class="card-header">
            <i class="fas fa-plus-circle me-1"></i>
            Thông tin môn học
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.subjects.store') }}">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="subject_code" class="form-label">Mã môn học <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('subject_code') is-invalid @enderror" id="subject_code" name="subject_code" value="{{ old('subject_code') }}" required>
                        @error('subject_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-8">
                        <label for="subject_name" class="form-label">Tên môn học <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('subject_name') is-invalid @enderror" id="subject_name" name="subject_name" value="{{ old('subject_name') }}" required>
                        @error('subject_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

               

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary">Quay lại</a>
                    <button type="submit" class="btn btn-primary">Lưu Môn học</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection