<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
            line-height: 1.6;
            color: #333;
        }
        .btn-verify {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            text-decoration: none;
            padding: 14px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
        }
        .pending-email {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 8px;
            padding: 12px 16px;
            font-weight: 600;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $subject }}</h1>
        </div>
        <div class="content">
            <p>Xin chào <strong>{{ $user->name }}</strong>,</p>
            <p>Bạn đã yêu cầu thay đổi email tài khoản sang:</p>
            <p class="pending-email">{{ $newEmail }}</p>
            <p>Vui lòng click vào nút bên dưới để <strong>xác thực</strong> email mới.
                Email mới chỉ có hiệu lực sau khi xác thực thành công.</p>
            <p style="text-align: center; margin: 30px 0;">
                <a href="{{ $verificationUrl }}" class="btn-verify">Xác thực email mới</a>
            </p>
            <p>Liên kết này sẽ hết hạn sau <strong>60 phút</strong>.</p>
            <p>Email hiện tại (<strong>{{ $user->email }}</strong>) vẫn được dùng để đăng nhập
                cho đến khi bạn xác thực email mới.</p>
            <p>Nếu bạn không yêu cầu thay đổi email, hãy bỏ qua email này.</p>
        </div>
        <div class="footer">
            <p>Đây là email tự động từ hệ thống Quản lý Đăng ký Đề tài Nhóm.</p>
            <p>Vui lòng không trả lời email này.</p>
        </div>
    </div>
</body>
</html>