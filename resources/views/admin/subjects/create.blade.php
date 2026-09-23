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
                    <div class="col-md-8">
                        <label for="subject_name" class="form-label">Tên môn học <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('subject_name') is-invalid @enderror" id="subject_name" name="subject_name" value="{{ old('subject_name') }}" required>
                        @error('subject_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="credits" class="form-label">Số tín chỉ <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('credits') is-invalid @enderror" id="credits" name="credits" value="{{ old('credits', 3) }}" min="1" max="10" required>
                        @error('credits')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Số bài báo cáo: 1 = chỉ cuối kì (mặc định), 2 = giữa kì + cuối kì --}}
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label d-block">Số bài báo cáo của môn <span class="text-danger">*</span></label>
                        @php $reportCount = (int) old('report_count', 1); @endphp
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="report_count" id="report_count_1" value="1" {{ $reportCount === 2 ? '' : 'checked' }}>
                            <label class="form-check-label" for="report_count_1">
                                1 bài — <strong>Chỉ cuối kì</strong> <span class="text-muted">(mặc định)</span>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="report_count" id="report_count_2" value="2" {{ $reportCount === 2 ? 'checked' : '' }}>
                            <label class="form-check-label" for="report_count_2">
                                2 bài — <strong>Giữa kì + Cuối kì</strong>
                            </label>
                        </div>
                        @error('report_count')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        <div class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> Môn 1 bài chỉ có đồ án cuối kì; môn 2 bài có cả đồ án giữa kì và cuối kì.
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-magic me-2"></i>
                            <strong>Mã môn học sẽ được hệ thống tự sinh</strong> theo tên môn học
                            (ví dụ: "Lập trình Web" → <code>LTW001</code>). Bạn không cần nhập mã.
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Giảng viên được phân công ở cấp <strong>lớp học phần</strong>
                            (Quản lý Lớp học), mỗi lớp học phần do 1 giảng viên quản lý.
                        </div>
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