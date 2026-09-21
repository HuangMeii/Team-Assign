@extends('layouts.app')

@section('title', 'Tạo lớp học phần')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-chalkboard-teacher"></i> Tạo lớp học phần mới</h4>
                </div>

                <div class="card-body p-4">
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

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Một môn học có thể có <strong>nhiều lớp học phần</strong> (ví dụ: cùng môn "Lập trình Web" có lớp sáng, lớp chiều).
                    </div>

                    <form action="{{ route('lecturer.classes.store') }}" method="POST">
                        @csrf

                        <!-- Tên lớp -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-heading text-primary"></i> Tên lớp học phần <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="class_name" value="{{ old('class_name') }}"
                                class="form-control @error('class_name') is-invalid @enderror"
                                placeholder="Ví dụ: Lập trình Web - Lớp Sáng (K1)" required>
                            @error('class_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Mã lớp tự sinh -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-key text-success"></i> Mã lớp (sinh viên dùng để tham gia)
                            </label>
                            <div class="alert alert-light border mb-0">
                                <i class="fas fa-magic me-2 text-success"></i>
                                Mã lớp gồm <strong>5 ký tự (chữ và số)</strong> sẽ được
                                <strong>tự động tạo</strong> sau khi bạn bấm "Tạo lớp học phần".
                                Sinh viên nhập mã này để tự tham gia lớp học.
                            </div>
                        </div>

                        <!-- Môn học -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-book text-info"></i> Môn học <span class="text-danger">*</span>
                            </label>
                            <select name="subject_id" class="form-select @error('subject_id') is-invalid @enderror" required>
                                <option value="">-- Chọn môn học --</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->subject_id }}" {{ old('subject_id') == $subject->subject_id ? 'selected' : '' }}>
                                        {{ $subject->subject_code }} - {{ $subject->subject_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('subject_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Tạo lớp học phần
                            </button>
                            <a href="{{ route('lecturer.classes.index') }}" class="btn btn-secondary btn-lg">
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
