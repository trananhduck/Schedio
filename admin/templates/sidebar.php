<?php
// Lấy tên file hiện tại để active menu
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="admin-sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">Schedio</a>
    </div>

    <div class="sidebar-menu">
        <nav class="nav flex-column">
            <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="bi bi-grid-fill"></i> Bảng điều khiển
            </a>

            <a href="orders.php"
                class="nav-link <?php echo ($current_page == 'orders.php' || $current_page == 'order_detail.php') ? 'active' : ''; ?>">
                <i class="bi bi-cart3"></i> Quản lý đơn hàng
            </a>

            <a href="calendar.php" class="nav-link <?php echo $current_page == 'calendar.php' ? 'active' : ''; ?>">
                <i class="bi bi-calendar-week"></i> Lịch đăng bài
            </a>

            <a href="packages.php" class="nav-link <?php echo $current_page == 'packages.php' ? 'active' : ''; ?>">
                <i class="bi bi-box-seam"></i> Quản lý gói dịch vụ
            </a>

            <a href="users.php" class="nav-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                <i class="bi bi-people"></i> Quản lý người dùng
            </a>

            <a href="reports.php" class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <i class="bi bi-bar-chart-line"></i> Báo cáo thống kê
            </a>

            <a href="settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="bi bi-gear"></i> Cài đặt hệ thống
            </a>
        </nav>
    </div>

    <div class="p-3 border-top">
        <a href="../logout.php" class="nav-link text-danger">
            <i class="bi bi-box-arrow-right"></i> Đăng xuất
        </a>
    </div>
</div>