<p align="center">
  <img src="public/logo.png" width="400" alt="team_assign Logo">
</p>

<h1 align="center">team_assign - Hệ thống Quản lý Đề tài Nhóm</h1>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11.x-red.svg" alt="Laravel Version">
  <img src="https://img.shields.io/badge/PHP-8.2+-blue.svg" alt="PHP Version">
  <img src="https://img.shields.io/badge/License-MIT-green.svg" alt="License">
</p>

## 📋 Giới thiệu

**team_assign** là hệ thống quản lý đề tài và nhóm sinh viên được xây dựng trên nền tảng Laravel. Hệ thống giúp tổ chức, quản lý và theo dõi quá trình đăng ký, phân công đề tài cho các nhóm sinh viên một cách hiệu quả.

### ✨ Tính năng chính

#### 👥 Quản lý Nhóm
- Tạo và quản lý nhóm sinh viên
- Mời thành viên vào nhóm
- Gửi/nhận yêu cầu tham gia nhóm
- Phân quyền trưởng nhóm và thành viên
- Theo dõi trạng thái nhóm theo lớp học

#### 📚 Quản lý Đề tài
- Duyệt danh sách đề tài theo môn học/lớp học
- Lọc đề tài theo trạng thái (còn trống/đã có nhóm)
- Đăng ký đề tài cho nhóm
- Theo dõi trạng thái duyệt đề tài
- Quản lý đề tài đã đăng ký

#### 🏫 Quản lý Lớp học & Môn học
- Quản lý danh sách lớp học
- Phân công môn học cho lớp
- Import/Export danh sách sinh viên
- Thống kê nhóm và đề tài theo lớp

#### 🔔 Hệ thống Thông báo
- Thông báo real-time
- Thông báo lời mời tham gia nhóm
- Thông báo yêu cầu tham gia nhóm
- Thông báo trạng thái đề tài (duyệt/từ chối)
- Đánh dấu đã đọc/chưa đọc

#### 👤 Phân quyền Người dùng
- **Admin**: Quản lý toàn bộ hệ thống
- **Giảng viên**: Quản lý đề tài, duyệt đăng ký
- **Sinh viên**: Tạo nhóm, đăng ký đề tài

## 🚀 Công nghệ sử dụng

- **Backend**: Laravel 11.x
- **Frontend**: Bootstrap 5, Blade Templates
- **Database**: MySQL/MariaDB
- **Icons**: Font Awesome 6.4
- **JS**: Vanilla JavaScript (ES6+)

## 📦 Yêu cầu hệ thống

- PHP >= 8.2
- Composer >= 2.0
- MySQL >= 5.7 hoặc MariaDB >= 10.3
- Node.js >= 18.x (optional, cho build assets)
- Git

## 🛠️ Cài đặt

### 1. Clone project
```bash
git clone https://github.com/yourusername/team_assign.git
cd team_assign
```

### 2. Cài đặt dependencies
```bash
composer install
```

### 3. Cấu hình môi trường
```bash
cp .env.example .env
php artisan key:generate
```

Chỉnh sửa file `.env` với thông tin database:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=team_assign
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Tạo database và chạy migrations
```bash
# Tạo database
mysql -u root -p -e "CREATE DATABASE team_assign CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Chạy migrations
php artisan migrate

# Seed dữ liệu mẫu (optional)
php artisan db:seed
```

### 5. Tạo symbolic link cho storage
```bash
php artisan storage:link
```

### 6. Khởi chạy server
```bash
php artisan serve
```

Truy cập: `http://localhost:8000`



## 📂 Cấu trúc Project
```
team_assign/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   │   └── ClassController.php
│   │   │   ├── NotificationController.php
│   │   │   ├── UserDashboardController.php
│   │   │   └── ...
│   │   └── Middleware/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Groups.php
│   │   ├── Topics.php
│   │   ├── Notifications.php
│   │   └── ...
│   └── Services/
│       └── NotificationService.php
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   └── views/
│       ├── admin/
│       ├── user/
│       ├── layouts/
│       └── ...
├── routes/
│   └── web.php
└── public/
    ├── logo.png
    └── ...
```

## 🔧 Cấu hình nâng cao

### Import sinh viên từ Excel

1. Tải template Excel:
```
GET /students-template/download
```

2. Upload file Excel:
```
POST /students-import
```

Format file Excel:
| Email | Họ và tên |
|-------|-----------|
| student1@example.com | Nguyễn Văn A |
| student2@example.com | Trần Thị B |

### Cấu hình Notification

File: `config/app.php`
```php
'notification_cleanup_days' => env('NOTIFICATION_CLEANUP_DAYS', 30),
```

Dọn dẹp notifications cũ:
```bash
php artisan schedule:work
```

## 🎨 Tùy chỉnh giao diện

### Màu sắc chính

File: `resources/views/layouts/user.blade.php`
```css
--primary-color: #667eea;
--secondary-color: #764ba2;
```

### Logo

Thay thế file `public/logo.png` bằng logo của bạn.

## 🧪 Testing
```bash
# Chạy tests
php artisan test

# Với coverage
php artisan test --coverage
```

## 📊 Database Schema

### Bảng chính

- **users** - Quản lý người dùng
- **groups** - Quản lý nhóm sinh viên
- **topics** - Quản lý đề tài
- **class_sections** - Quản lý lớp học
- **subjects** - Quản lý môn học
- **notifications** - Quản lý thông báo
- **topic_requests** - Yêu cầu đăng ký đề tài
- **join_requests** - Yêu cầu tham gia nhóm
- **invites** - Lời mời tham gia nhóm

## 🤝 Đóng góp

Mọi đóng góp đều được hoan nghênh! Vui lòng:

1. Fork project
2. Tạo branch cho tính năng (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Mở Pull Request

## 📞 Liên hệ

- **Developer**: Ngân
- **Email**: nganchoanh1.email@example.com
- **GitHub**: [@ngan17](https://github.com/ngan17)

## 📄 License

Project này được phân phối dưới giấy phép MIT. Xem file `LICENSE` để biết thêm chi tiết.

## 🙏 Acknowledgments

- [Laravel](https://laravel.com) - Framework PHP tuyệt vời
- [Bootstrap](https://getbootstrap.com) - CSS Framework
- [Font Awesome](https://fontawesome.com) - Icon library
- Cảm ơn tất cả contributors đã đóng góp cho project

---

<p align="center">Made with ❤️ by Ngân</p>