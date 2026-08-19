<!DOCTYPE html>
<html lang="vi">
@include('components.chatbot')

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Quản lý Đăng ký Đề tài Nhóm')</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            background: linear-gradient(135deg, #fcfdffff 0%, #fefdffff 100%);
            min-height: 100vh;
            padding: 20px 0;
            box-shadow: 3px 0 15px rgba(0, 0, 0, 0.08);
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar.collapsed {
            left: -260px;
        }

        /* Logo Styles - GIỮ NGUYÊN HÌNH CHỮ NHẬT */
        .logo {
            text-align: center;
            margin-bottom: 30px;
            padding: 15px 20px;
            border-bottom: 2px solid rgba(102, 126, 234, 0.1);
        }

        .logo img {
            width: 100%;
            max-width: 200px;
            height: auto;
            object-fit: contain;
            filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.1));
        }

        /* Navigation Styles */
        .sidebar .nav-link {
            color: rgba(16, 15, 15, 0.85);
            padding: 12px 20px;
            margin: 3px 10px;
            border-radius: 10px;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
            color: #667eea;
        }

        .sidebar .nav-link:hover {
            color: #667eea;
            background-color: rgba(102, 126, 234, 0.08);
            border-left-color: #667eea;
            transform: translateX(3px);
        }

        .sidebar .nav-link.active {
            color: #667eea;
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.15) 0%, rgba(102, 126, 234, 0.05) 100%);
            border-left-color: #667eea;
            font-weight: 600;
        }

        .sidebar .nav-link.active i {
            color: #667eea;
        }

        /* Divider */
        .sidebar hr {
            background-color: rgba(102, 126, 234, 0.15);
            margin: 20px 15px;
            height: 1px;
            border: none;
        }

        /* Main Content Area */
        .main-container {
            margin-left: 260px;
            transition: margin-left 0.3s ease;
            min-height: 100vh;
        }

        .main-container.expanded {
            margin-left: 0;
        }

        /* Header Navbar */
        .navbar-header {
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            padding: 15px 30px;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .navbar-header h5 {
            color: #2d3748;
            font-weight: 600;
            margin: 0;
        }

        /* Toggle Button */
        .btn-toggle-sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-toggle-sidebar:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        /* User Dropdown */
        .user-dropdown .btn {
            border-radius: 25px;
            padding: 8px 20px;
            border: 1px solid #e2e8f0;
            background: white;
            color: #2d3748;
            transition: all 0.3s ease;
        }

        .user-dropdown .btn:hover {
            background: #f7fafc;
            border-color: #667eea;
        }

        .dropdown-menu {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
            margin-top: 10px;
        }

        .dropdown-item {
            padding: 10px 20px;
            transition: all 0.2s ease;
            color: #2d3748;
        }

        .dropdown-item:hover {
            background: #f7fafc;
            color: #667eea;
            padding-left: 25px;
        }

        /* Content Area */
        .content-area {
            padding: 30px;
            min-height: calc(100vh - 70px);
        }

        /* Notification Button */
        .btn-notification {
            background: white;
            border: 1px solid #e2e8f0;
            color: #2d3748;
            padding: 8px 12px;
            border-radius: 8px;
            position: relative;
            transition: all 0.3s ease;
        }

        .btn-notification:hover {
            background: #f7fafc;
            border-color: #667eea;
            color: #667eea;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            font-size: 0.65rem;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                left: -260px;
            }

            .sidebar.active {
                left: 0;
            }

            .main-container {
                margin-left: 0;
            }

            .navbar-header {
                padding: 12px 15px;
            }

            .navbar-header h5 {
                font-size: 0.95rem;
            }
        }

        /* Scrollbar for Sidebar */
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(102, 126, 234, 0.05);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(102, 126, 234, 0.2);
            border-radius: 10px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(102, 126, 234, 0.3);
        }

        /* Custom Buttons */
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 10px 24px;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">

            <div class="logo">
                <img src="{{ asset('logo.png') }}" alt="Logo">
            </div>

            <nav class="nav flex-column">
                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}"
                    class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home"></i>
                    <span>Trang chủ</span>
                </a>

                <!-- Topics -->
                <a href="{{ route('topics.index') }}"
                    class="nav-link {{ request()->routeIs('topics.*') ? 'active' : '' }}">
                    <i class="fas fa-lightbulb"></i>
                    <span>Đề tài</span>
                </a>

                <!-- Groups -->
                <a href="{{ route('groups.index') }}"
                    class="nav-link {{ request()->routeIs('groups.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span>Nhóm</span>
                </a>

                <!-- Students -->
                <a href="/admin/users" class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}">
                    <i class="fas fa-user-graduate"></i>
                    <span>Người dùng</span>
                </a>

                <a href="{{ route('classes.index') }}"
                    class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}">
                    <i class="fas fa-user-graduate"></i>
                    <span>Lớp học</span>
                </a>

                <a href="{{ route('admin.subjects.index') }}"
                    class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}">
                    <i class="fas fa-user-graduate"></i>
                    <span>Môn học</span>
                </a>
                <hr>

                <!-- Settings -->
                <a href="#" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Cài đặt</span>
                </a>

                <!-- Logout -->
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="nav-link text-start bg-transparent border-0 w-100">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Đăng xuất</span>
                    </button>
                </form>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-container flex-grow-1" id="mainContainer">
            <!-- Header -->
            <div class="navbar-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-toggle-sidebar" id="toggleSidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h5 class="d-none d-md-block">Hệ thống quản lý đăng ký đề tài nhóm</h5>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <!-- Notifications -->
                    <!-- Notifications Dropdown trong layout -->
                    <div class="dropdown">
                        <button class="btn btn-notification" type="button" id="notificationDropdown"
                            data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end"
                            style="width: 350px; max-height: 400px; overflow-y: auto;">
                            <li class="px-3 py-2 border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold">Thông báo</h6>
                                    <button class="btn btn-link btn-sm text-primary p-0" onclick="markAllAsRead()">
                                        Đánh dấu đã đọc
                                    </button>
                                </div>
                            </li>
                            <div id="notificationList">
                                <li class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </li>
                            </div>
                            <li class="border-top">
                                <a class="dropdown-item text-center text-primary fw-semibold py-2"
                                    href="{{ route('notifications.index') }}">
                                    Xem tất cả
                                </a>
                            </li>
                        </ul>
                    </div>

                    @push('scripts')
                        <script>
                            // Load notifications on page load
                            document.addEventListener('DOMContentLoaded', function () {
                                loadNotifications();

                                // Reload every 30 seconds
                                setInterval(loadNotifications, 30000);
                            });

                            // Load notifications
                            function loadNotifications() {
                                fetch('{{ route("notifications.recent") }}')
                                    .then(response => response.json())
                                    .then(data => {
                                        updateNotificationBadge(data.unread_count);
                                        renderNotifications(data.notifications);
                                    })
                                    .catch(error => console.error('Error loading notifications:', error));
                            }

                            // Update badge
                            function updateNotificationBadge(count) {
                                const badge = document.getElementById('notificationBadge');
                                if (count > 0) {
                                    badge.textContent = count > 99 ? '99+' : count;
                                    badge.style.display = 'block';
                                } else {
                                    badge.style.display = 'none';
                                }
                            }

                            // Render notifications
                            function renderNotifications(notifications) {
                                const list = document.getElementById('notificationList');

                                if (notifications.length === 0) {
                                    list.innerHTML = `
                                            <li class="text-center py-4">
                                                <i class="fas fa-bell-slash fa-2x text-muted mb-2"></i>
                                                <p class="text-muted mb-0 small">Không có thông báo mới</p>
                                            </li>
                                        `;
                                    return;
                                }

                                list.innerHTML = notifications.map(notif => `
                                        <li>
                                            <a class="dropdown-item ${!notif.is_read ? 'bg-light' : ''}" 
                                               href="{{ url('/') }}/notifications/${notif.notification_id}/read"
                                               style="white-space: normal;">
                                                <div class="d-flex align-items-start py-2">
                                                    <div class="me-3">
                                                        <i class="fas ${notif.icon} text-${notif.color}"></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <p class="mb-1 fw-semibold small">${notif.title}</p>
                                                        <p class="mb-1 text-muted" style="font-size: 0.85rem;">${notif.message}</p>
                                                        <small class="text-muted">${formatTime(notif.created_at)}</small>
                                                    </div>
                                                    ${!notif.is_read ? '<span class="badge bg-primary rounded-circle" style="width: 8px; height: 8px; padding: 0;"></span>' : ''}
                                                </div>
                                            </a>
                                        </li>
                                    `).join('');
                            }

                            // Format time ago
                            function formatTime(dateString) {
                                const date = new Date(dateString);
                                const now = new Date();
                                const seconds = Math.floor((now - date) / 1000);

                                if (seconds < 60) return 'Vừa xong';
                                if (seconds < 3600) return Math.floor(seconds / 60) + ' phút trước';
                                if (seconds < 86400) return Math.floor(seconds / 3600) + ' giờ trước';
                                if (seconds < 604800) return Math.floor(seconds / 86400) + ' ngày trước';

                                return date.toLocaleDateString('vi-VN');
                            }

                            // Mark all as read
                            function markAllAsRead() {
                                fetch('{{ route("notifications.mark-all-read") }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    }
                                })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            loadNotifications();
                                        }
                                    })
                                    .catch(error => console.error('Error:', error));
                            }
                        </script>
                    @endpush

                    <!-- User Dropdown -->
                    <div class="dropdown user-dropdown">
                        <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-2"></i>
                            {{ Auth::user()->name ?? 'Người dùng' }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('users.profile') }}">
                                    <i class="fas fa-user me-2"></i>Hồ sơ
                                </a></li>
                            <li><a class="dropdown-item" href="#">
                                    <i class="fas fa-cog me-2"></i>Cài đặt
                                </a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST"
                                    onsubmit="return confirm('Bạn có chắc chắn muốn đăng xuất không?');">
                                    @csrf
                                    <button class="dropdown-item text-danger" type="submit">
                                        <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area">
                @yield('content')
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle Sidebar
        const toggleBtn = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebar');
        const mainContainer = document.getElementById('mainContainer');

        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
            mainContainer.classList.toggle('expanded');

            // Save state to localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });

        // Restore sidebar state on page load
        window.addEventListener('DOMContentLoaded', function () {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
                mainContainer.classList.add('expanded');
            }
        });

        // Mobile: Toggle sidebar
        if (window.innerWidth <= 768) {
            toggleBtn.addEventListener('click', function () {
                sidebar.classList.toggle('active');
            });

            // Close sidebar when clicking outside
            document.addEventListener('click', function (event) {
                const isClickInside = sidebar.contains(event.target) || toggleBtn.contains(event.target);
                if (!isClickInside && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            });
        }
    </script>

    @stack('scripts')
</body>

</html>