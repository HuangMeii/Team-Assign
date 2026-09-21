<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: url("{{ asset('background.jpg') }}") no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.1);
            z-index: -1;
        }

        .login-card {
            background-color: #fff;
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 420px;
            padding: 40px 35px;
            position: relative;
            z-index: 1;
        }

        .login-card h3 {
            font-weight: 700;
            text-align: center;
            color: #4a4a4a;
            margin-bottom: 10px;
            font-size: 1.4rem;
        }

        .form-control {
            border-radius: 10px;
            padding-left: 45px;
            height: 45px;
            font-size: 0.95rem;
            border: 1px solid #e0e0e0;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(118, 75, 162, 0.25);
            border-color: #764ba2;
        }

        .input-group-text {
            border: none;
            background-color: transparent;
            color: #764ba2;
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 4;
            padding: 0;
            font-size: 1.1rem;
        }

        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            box-shadow: 0 5px 15px rgba(118, 75, 162, 0.4);
            transform: translateY(-2px);
            color: white;
        }

        .alert {
            border-radius: 10px;
            font-size: 0.9rem;
        }

        .footer-text {
            text-align: center;
            margin-top: 25px;
            color: #777;
            font-size: 0.85rem;
        }
    </style>
</head>

<body>

    <div class="login-card">
        <h4 class="text-center">
            <img src="{{ asset('logo.png') }}" alt="Logo" onerror="this.style.display='none'">
        </h4>

        <h3>Quên mật khẩu</h3>
        <p class="text-center text-muted mb-4 small">
            Nhập địa chỉ email của bạn, hệ thống sẽ gửi liên kết xác thực để đặt lại mật khẩu.
        </p>

        {{-- Thông báo gửi email --}}
        @if (session('success'))
            <div class="alert alert-success text-center py-2">
                <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger text-center py-2">
                <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
            </div>
        @endif

        {{-- Hiển thị lỗi --}}
        @if ($errors->any())
            <div class="alert alert-danger text-center py-2">
                <i class="fas fa-exclamation-circle me-1"></i> {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="mb-4 position-relative">
                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="Địa chỉ Email" required autofocus
                    value="{{ old('email') }}">
            </div>

            <button type="submit" class="btn btn-login w-100">
                <i class="fas fa-paper-plane me-2"></i> Gửi liên kết đặt lại mật khẩu
            </button>
        </form>

        <div class="footer-text">
            <a href="{{ route('login') }}" class="text-decoration-none">
                <i class="fas fa-arrow-left me-1"></i> Quay lại đăng nhập
            </a>
            <p class="mt-3 mb-0">© {{ date('Y') }} Hệ thống Quản lý Đề tài Nhóm</p>
        </div>
    </div>

</body>

</html>
