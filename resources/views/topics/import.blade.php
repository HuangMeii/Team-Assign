@extends('layouts.app')

@section('title', 'Import Đề tài')

@section('content')
<div class="container mt-4">
    <h1 class="mt-2">Import Đề tài</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('topics.index') }}">Quản lý Đề tài</a></li>
        <li class="breadcrumb-item active">Import</li>
    </ol>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-file-import me-1"></i> Form Import Đề tài</span>
            <a href="{{ route('topics.download-template') }}" class="btn btn-success btn-sm">
                <i class="fas fa-download me-1"></i> Tải file mẫu
            </a>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                <i class="fas fa-info-circle me-1"></i>
                File Excel/CSV cần có dòng tiêu đề với các cột:
                <strong>ten_de_tai, mo_ta, muc_tieu, yeu_cau, ma_lop, so_tv_min, so_tv_max, han_dang_ky</strong>.
            </p>

            <ul class="small text-muted mb-4">
                <li><strong>ten_de_tai</strong>: bắt buộc — trùng tên đề tài đã có sẽ bị <em>bỏ qua</em> (không ghi đè).</li>
                <li><strong>mo_ta</strong>: bắt buộc, tối thiểu 10 ký tự.</li>
                <li><strong>ma_lop</strong>: bắt buộc — là <em>mã lớp học phần</em> (5 ký tự), hệ thống tự suy ra môn học và giảng viên phụ trách.</li>
                <li><strong>so_tv_min / so_tv_max</strong>: để trống ⇒ mặc định 2 / 4.</li>
                                <li>
                    <strong>Dấu phân cách</strong>: hệ thống TỰ DÒ dấu phẩy, dấu chấm phẩy hoặc TAB, nên file CSV sao
                    chép từ Excel sang Notepad (phân cách TAB) vẫn import được.
                </li>
                <li><strong>han_dang_ky</strong>: để trống ⇒ mặc định 30 ngày kể từ hôm nay (nhận cả dạng <code>2026-12-31</code>, <code>31/12/2026</code> hoặc ô ngày của Excel).</li>
            </ul>

            @if(isset($classes) && $classes->isNotEmpty())
                <div class="alert alert-light border">
                    <strong class="small d-block mb-2">
                        <i class="fas fa-chalkboard me-1"></i>
                        Mã lớp bạn có thể dùng trong file
                        ({{ Auth::user()->role === 'admin' ? 'admin: toàn bộ lớp học phần' : 'giảng viên: lớp bạn phụ trách' }}):
                    </strong>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($classes as $class)
                            <span class="badge bg-secondary">
                                {{ $class->class_code }} — {{ $class->class_name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="alert alert-warning">
                    Bạn chưa phụ trách lớp học phần nào nên chưa thể import đề tài.
                </div>
            @endif

            <form method="POST" action="{{ route('topics.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-bold">Chọn file (Excel/CSV)</label>
                    <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" required
                           accept=".xlsx,.xls,.csv">
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('topics.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Hủy
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-1"></i> Import ngay
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
