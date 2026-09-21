<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác thực Email Thành công</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: url("{{ asset('background.jpg') }}") no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
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
        .verify-card {
            background-color: #fff;
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 450px;
            padding: 40px 35px;
            position: relative;
            z-index: 1;
            text-align: center;
        }
        .verify-card h3 {
            font-weight: 700;
            color: #28a745;
            margin-bottom: 15px;
        }
        .btn-choice {
            border-radius: 12px;
            padding: 14px 28px;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.3s ease;
            margin: 10px;
            min-width: 200px;
        }
        .btn-continue {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
        }
        .btn-change {
            background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
            color: white;
            border: none;
        }
    </style>
</head>
<body>
    <div class="verify-card">
        <div class="mb-4">
            <i class="fas fa-check-circle fa-4x text-success"></i>
        </div>
        <h3>Email đã được xác thực!</h3>
        <p class="text-muted mb-4">
            Bạn đã xác thực email thành công. Bạn muốn làm gì tiếp theo?
        </p>

        <div class="d-grid gap-2 justify-content-center flex-sm-row mt-4">
            <a href="{{ 
                auth()->user()->role === 'student' || auth()->user()->role === 'leader'
                    ? route('user.dashboard')
                    : (auth()->user()->role === 'lecturer' ? route('dashboard') : route('admin.users.index'))
            }}" class="btn btn-continue d-inline-flex align-items-center justify-content-center">
                <i class="fas fa-arrow-right me-2"></i> Tiếp tục
            </a>

            <form method="POST" action="{{ route('login.prepare-change-password') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-change d-inline-flex align-items-center justify-content-center">
                    <i class="fas fa-key me-2"></i> Đổi mật khẩu ngay
                </button>
            </form>
        </div>

        <div class="mt-4 pt-3 border-top">
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Đổi mật khẩu ngay sẽ mở trang bảo mật tài khoản,
                phần "mật khẩu hiện tại" sẽ được ẩn đi vì bạn đã xác thực qua email.
            </small>
        </div>
    </div>
</body>
</html>
