@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-file-import"></i> Import sinh viên từ Excel</h4>
                </div>

                <div class="card-body p-4">
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

                    <!-- Instructions -->
                    <div class="alert alert-info mb-4">
                        <h5 class="alert-heading"><i class="fas fa-info-circle"></i> Hướng dẫn import</h5>
                        <ol class="mb-0">
                            <li>Tải file mẫu Excel bên dưới</li>
                            <li>Điền thông tin sinh viên vào file (họ tên, email, mật khẩu)</li>
                            <li>Chọn lớp học phần muốn import</li>
                            <li>Upload file lên hệ thống</li>
                        </ol>
                        <hr>
                        <p class="mb-0">
                            <strong>Lưu ý:</strong> 
                            <ul class="mb-0">
                                <li>File phải có định dạng .xlsx, .xls hoặc .csv</li>
                                <li>Cột <code>ho_va_ten</code> và <code>email</code> là bắt buộc</li>
                                <li>Nếu không có cột <code>mat_khau</code>, mật khẩu mặc định sẽ là <code>123456</code></li>
                                <li>Email trùng sẽ tự động thêm vào lớp mới (không tạo user mới)</li>
                            </ul>
                        </p>
                    </div>

                    <!-- Download Template -->
                    <div class="text-center mb-4">
                        <a href="{{ route('students.download-template') }}" class="btn btn-outline-success btn-lg">
                            <i class="fas fa-download"></i> Tải file mẫu Excel
                        </a>
                    </div>

                    <hr>

                    <!-- Import Form -->
                    <form action="{{ route('students.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- Class Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-chalkboard-teacher text-info"></i> Chọn lớp học phần <span class="text-danger">*</span>
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
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> Tất cả sinh viên trong file sẽ được thêm vào lớp này
                            </small>
                        </div>

                        <!-- File Upload -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-file-excel text-success"></i> Chọn file Excel <span class="text-danger">*</span>
                            </label>
                            <input type="file" 
                                   name="file" 
                                   class="form-control form-control-lg @error('file') is-invalid @enderror"
                                   accept=".xlsx,.xls,.csv"
                                   required>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> Định dạng hỗ trợ: .xlsx, .xls, .csv
                            </small>
                        </div>

                        <!-- Preview Area -->
                        <div class="mb-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-table"></i> Cấu trúc file mẫu:
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="table-success">
                                                <tr>
                                                    <th>ho_va_ten</th>
                                                    <th>email</th>
                                                    <th>mat_khau</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Nguyễn Văn A</td>
                                                    <td>nguyenvana@example.com</td>
                                                    <td>123456</td>
                                                </tr>
                                                <tr>
                                                    <td>Trần Thị B</td>
                                                    <td>tranthib@example.com</td>
                                                    <td>123456</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-upload"></i> Import sinh viên
                            </button>
                            <a href="{{ route('students.index') }}" class="btn btn-secondary btn-lg">
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