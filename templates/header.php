<?php
// Bắt đầu session để kiểm tra đăng nhập
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lấy tên file hiện tại để active menu
$current_page = basename($_SERVER['SCRIPT_NAME']);

// ĐƯỜNG DẪN GỐC CỦA DỰ ÁN
$base_url = '/booking-website';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedio - Dịch vụ Booking Bài đăng</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css">
</head>

<body class="schedio-light-bg">

    <header class="py-3">
        <nav class="navbar navbar-expand-lg navbar-light bg-transparent">
            <div class="container">
                <a class="navbar-brand fw-bold" href="<?php echo $base_url; ?>/index.php">Schedio</a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main-nav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse justify-content-center" id="main-nav">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>"
                                href="<?php echo $base_url; ?>/index.php">TRANG CHỦ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'about.php') ? 'active' : ''; ?>"
                                href="<?php echo $base_url; ?>/about.php">GIỚI THIỆU</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'services.php') ? 'active' : ''; ?>"
                                href="<?php echo $base_url; ?>/services.php">BẢNG GIÁ</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'clients.php') ? 'active' : ''; ?>"
                                href="<?php echo $base_url; ?>/clients.php">KHÁCH HÀNG TIÊU BIỂU</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>"
                                href="<?php echo $base_url; ?>/contact.php">LIÊN HỆ</a>
                        </li>
                    </ul>
                </div>

                <!-- 
                ===================================================
                PHẦN HIỂN THỊ AVATAR/ICON ĐĂNG NHẬP (ĐÃ SỬA)
                ===================================================
                -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php
                    $avatar_url = !empty($_SESSION['user_avatar'])
                        ? $base_url . '/' . htmlspecialchars($_SESSION['user_avatar'])
                        : 'https://i.ibb.co/5Y8wNcz/user-placeholder.png';
                    ?>
                    <div class="dropdown">
                        <a href="<?php echo $base_url; ?>/customer/account.php" class="btn p-0 border-0 user-avatar-btn"
                            title="Tài khoản của tôi">
                            <img src="<?php echo $avatar_url; ?>" alt="Avatar" class="user-avatar-img">
                        </a>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $base_url; ?>/login.php" class="btn btn-outline-dark btn-circle" title="Đăng nhập">
                        <i class="bi bi-person"></i>
                    </a>
                <?php endif; ?>

            </div>
        </nav>
    </header>

    <main>