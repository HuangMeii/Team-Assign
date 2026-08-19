@extends('layouts.app')

@section('title', 'Chỉnh sửa đề tài')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-warning text-white py-4">
                        <h4 class="mb-0">
                            <i class="fas fa-edit"></i> Chỉnh sửa đề tài
                        </h4>
                    </div>

                    <div class="card-body p-4">
                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <h5 class="alert-heading">
                                    <i class="fas fa-exclamation-circle"></i> Lỗi!
                                </h5>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form action="{{ route('topics.update', $topic) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-4">
                                <label for="name" class="form-label fw-bold">
                                    <i class="fas fa-heading"></i> Tên đề tài
                                </label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                    id="name" name="name" placeholder="Nhập tên đề tài"
                                    value="{{ old('name', $topic->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                     <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-user-tie"></i> Giảng viên hướng dẫn
                        </label>
                        <div class="form-control-plaintext">
                            {{ Auth::user()->name }}
                        </div>
                    </div>

                            <div class="mb-4">
                                <label for="description" class="form-label fw-bold">
                                    <i class="fas fa-align-left"></i> Mô tả chi tiết
                                </label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                    id="description" name="description" rows="5" 
                                    placeholder="Nhập mô tả chi tiết về đề tài" required>{{ old('description', $topic->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="goal" class="form-label fw-bold">
                                    <i class="fas fa-target"></i> Mục tiêu
                                </label>
                                <textarea class="form-control @error('goal') is-invalid @enderror"
                                    id="goal" name="goal" rows="3" 
                                    placeholder="Nhập mục tiêu của đề tài (tùy chọn)">{{ old('goal', $topic->goal) }}</textarea>
                                @error('goal')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="requirements" class="form-label fw-bold">
                                    <i class="fas fa-list-check"></i> Yêu cầu
                                </label>
                                <textarea class="form-control @error('requirements') is-invalid @enderror"
                                    id="requirements" name="requirements" rows="3"
                                    placeholder="Nhập các yêu cầu (tùy chọn)">{{ old('requirements', $topic->requirements) }}</textarea>
                                @error('requirements')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-warning btn-lg flex-grow-1">
                                    <i class="fas fa-save"></i> Cập nhật
                                </button>
                                <a href="{{ route('topics.index') }}" class="btn btn-secondary btn-lg flex-grow-1">
                                    <i class="fas fa-arrow-left"></i> Quay lại
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection