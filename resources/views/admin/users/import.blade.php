@extends('layouts.app')

@section('title', 'Import tài khoản')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Import tài khoản bằng Excel</h1>
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
    <div class="card"><div class="card-body">
        <p>Cột bắt buộc: <code>name</code>, <code>email</code>. Cột tùy chọn: <code>password</code> (tối thiểu 6 ký tự), <code>role</code> (student, lecturer, admin).</p>
        <form method="POST" action="{{ route('admin.users.import') }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="file" accept=".xlsx,.xls,.csv" class="form-control mb-3" required>
            @error('file') <div class="text-danger mb-3">{{ $message }}</div> @enderror
            <button class="btn btn-primary">Import tài khoản</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </form>
    </div></div>
</div>
@endsection