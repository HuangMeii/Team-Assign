<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập hệ thống</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            /* Đảm bảo file background.jpg nằm trong thư mục public/ */
            background: url("{{ asset('background.jpg') }}") no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Overlay nhẹ để text dễ đọc hơn nếu background quá sáng/tối */
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
            width: 400px;
            padding: 40px 35px;
            animation: fadeInUp 0.6s ease-in-out;
            position: relative;
            z-index: 1;
        }

        .login-card h3 {
            font-weight: 700;
            text-align: center;
            color: #4a4a4a;
            margin-bottom: 10px;
            font-size: 1.5rem;
        }

        .login-card h4 {
            text-align: center;
            margin-bottom: 15px;
        }

        .form-control {
            border-radius: 10px;
            padding-left: 45px; /* Tăng padding để không đè icon */
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

        /* Nút hiện/ẩn mật khẩu */
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #764ba2;
            cursor: pointer;
            z-index: 5;
            padding: 6px;
        }
        .password-toggle i {
            font-size: 1.05rem;
        }
        .password-toggle:hover {
            color: #667eea;
        }

        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 10px;
            font-size: 1rem;
        }

        .login-logo {
            width: 120px; /* Điều chỉnh kích thước logo cho cân đối */
            height: auto;
            object-fit: contain;
            margin-bottom: 10px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
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
        
        /* Custom Checkbox Style */
        .form-check-input:checked {
            background-color: #764ba2;
            border-color: #764ba2;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>

    <div class="login-card">
        {{-- Logo --}}
        <h4>
            <img src="{{ asset('logo.png') }}" alt="Logo" class="login-logo" onerror="this.style.display='none'">
        </h4>
        
        <h3>Đăng nhập hệ thống</h3>
        <p class="text-center text-muted mb-4 small">Vui lòng đăng nhập để tiếp tục</p>

        {{-- Hiển thị lỗi --}}
        @if ($errors->any())
            <div class="alert alert-danger text-center py-2">
                <i class="fas fa-exclamation-circle me-1"></i> {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-3 position-relative">
                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="Địa chỉ Email" required autofocus value="{{ old('email') }}">
            </div>

            <div class="mb-3 position-relative">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" name="password" id="loginPassword" class="form-control" placeholder="Mật khẩu" required>
                <button type="button" class="password-toggle" onclick="togglePassword('loginPassword', this)" tabindex="-1" aria-label="Hiện/ẩn mật khẩu">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            {{-- Thêm phần Ghi nhớ đăng nhập (Remember Me) --}}
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label text-muted small" for="remember">Ghi nhớ đăng nhập</label>
            </div>

            <button type="submit" class="btn btn-login w-100">
                <i class="fas fa-sign-in-alt me-2"></i> Đăng nhập
            </button>
        </form>

        <div class="footer-text">
            <p class="mt-3 mb-0">© {{ date('Y') }} Hệ thống Quản lý Đề tài Nhóm</p>
        </div>
    </div>

    <script>
        function togglePassword(inputId, btn) {
            var input = document.getElementById(inputId);
            var icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
    </script>

</body>

</html>