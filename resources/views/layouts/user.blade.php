<!DOCTYPE html>
<html lang="vi">
@include('components.chatbot')

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Student Portal')</title>
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

        /* Logo Styles */
        .logo {
            text-align: center;
            margin-bottom: 30px;
            padding: 15px 20px;
            border-bottom: 2px solid rgba(37, 99, 235, 0.1);
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
            position: relative;
        }

        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
            color: #2563eb;
        }

        .sidebar .nav-link:hover {
            color: #2563eb;
            background-color: rgba(37, 99, 235, 0.08);
            border-left-color: #2563eb;
            transform: translateX(3px);
        }

        .sidebar .nav-link.active {
            color: #2563eb;
            background: linear-gradient(90deg, rgba(37, 99, 235, 0.15) 0%, rgba(37, 99, 235, 0.05) 100%);
            border-left-color: #2563eb;
            font-weight: 600;
        }

        .sidebar .nav-link.active i {
            color: #2563eb;
        }

        /* Notification Badge on Sidebar */
        .sidebar .notification-badge {
            position: absolute;
            top: 8px;
            right: 15px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 0.7rem;
            font-weight: 600;
            min-width: 20px;
            text-align: center;
        }

        /* Divider */
        .sidebar hr {
            background-color: rgba(37, 99, 235, 0.15);
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
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-toggle-sidebar:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
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
            border-color: #2563eb;
            color: #2563eb;
        }

        .notification-badge-header {
            position: absolute;
            top: -5px;
            right: -5px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            font-size: 0.65rem;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 600;
            min-width: 18px;
            text-align: center;
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
            border-color: #2563eb;
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
            color: #2563eb;
            padding-left: 25px;
        }

        /* Content Area */
        .content-area {
            padding: 30px;
            min-height: calc(100vh - 70px);
        }

        /* Custom Buttons */
        .btn-primary-custom {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            border-radius: 8px;
            padding: 10px 24px;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }

        /* Overlay for Mobile */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .overlay.active {
            display: block;
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
            background: rgba(37, 99, 235, 0.05);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(37, 99, 235, 0.2);
            border-radius: 10px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(37, 99, 235, 0.3);
        }
    </style>
    @stack('styles')
</head>

<body>
    <div class="overlay" id="overlay"></div>

    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <!-- Logo -->
            <div class="logo">
                <img src="{{ asset('logo.png') }}" alt="Logo">
            </div>

            <nav class="nav flex-column">
                <!-- Dashboard -->
                <a href="{{ route('user.dashboard') }}"
                    class="nav-link {{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>

                <!-- Topics -->
                <a href="{{ route('user.topics') }}"
                    class="nav-link {{ request()->routeIs('user.topics*') ? 'active' : '' }}">
                    <i class="fas fa-lightbulb"></i>
                    <span>Đề tài</span>
                </a>

                <!-- My Groups -->
                <a href="{{ route('user.my_groups') }}"
                    class="nav-link {{ request()->routeIs('user.my-groups*') || request()->routeIs('user.group*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span>Nhóm của tôi</span>
                </a>

                <!-- Invites -->
                <a href="{{ route('user.invites') }}"
                    class="nav-link {{ request()->routeIs('user.invites') ? 'active' : '' }}">
                    <i class="fas fa-envelope"></i>
                    <span>Lời mời</span>
                    @php
                        $invCount = is_int($pendingInvites ?? 0) ? ($pendingInvites ?? 0) : (isset($pendingInvites) ? $pendingInvites->count() : 0);
                    @endphp
                    @if($invCount > 0)
                        <span class="notification-badge">{{ $invCount }}</span>
                    @endif
                </a>




                <!-- Join Requests -->
                <a href="{{ route('user.join-requests') }}"
                    class="nav-link {{ request()->routeIs('user.join-requests') ? 'active' : '' }}">
                    <i class="fas fa-paper-plane"></i>
                    <span>Yêu cầu</span>
                    @php
                        $reqCount = is_int($pendingRequests ?? 0) ? ($pendingRequests ?? 0) : (isset($pendingRequests) ? $pendingRequests->count() : 0);
                    @endphp
                    @if($reqCount > 0)
                        <span class="notification-badge">{{ $reqCount }}</span>
                    @endif
                </a>

                <!-- My Topics -->
                <a href="{{ route('user.my_topics') }}"
                    class="nav-link {{ request()->routeIs('user.my-topics') ? 'active' : '' }}">
                    <i class="fas fa-bookmark"></i>
                    <span>Đề tài của tôi</span>
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
                    <h5 class="d-none d-md-block">Hệ thống quản lý nhóm và đề tài</h5>
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
                            {{ Auth::user()->name ?? 'User' }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('users.profile.info') }}">
                                    <i class="fas fa-user me-2"></i>Hồ sơ
                                </a></li>
                            <li><a class="dropdown-item" href="{{ route('users.profile.password') }}">
                                    <i class="fas fa-key me-2"></i>Đổi mật khẩu
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

            <!-- Content Area with Flash Messages -->
            <div class="content-area">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> {{ session('warning') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

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
        const overlay = document.getElementById('overlay');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                sidebar.classList.toggle('collapsed');
                mainContainer.classList.toggle('expanded');

                // For mobile
                if (window.innerWidth <= 768) {
                    sidebar.classList.toggle('active');
                    overlay.classList.toggle('active');
                }

                // Save state to localStorage
                const isCollapsed = sidebar.classList.contains('collapsed');
                localStorage.setItem('sidebarCollapsed', isCollapsed);
            });
        }


        if (overlay) {
            overlay.addEventListener('click', function () {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        }


        window.addEventListener('DOMContentLoaded', function () {
            if (window.innerWidth > 768) {
                const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                if (isCollapsed) {
                    sidebar.classList.add('collapsed');
                    mainContainer.classList.add('expanded');
                }
            }
        });


        setTimeout(function () {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
    @vite(['resources/js/app.js'])
    @stack('scripts')
</body>

</html>