<?php
// Bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kết nối DB để check thông báo (Nếu chưa kết nối ở trang gọi header)
if (!isset($conn)) {
    // Đường dẫn tương đối an toàn, giả sử header được gọi từ file cùng cấp hoặc thư mục con
    $db_path = __DIR__ . '/../config/db.php';
    if (file_exists($db_path)) {
        require_once $db_path;
    }
}

// Lấy tên file hiện tại để active menu
$current_page = basename($_SERVER['SCRIPT_NAME']);

// ĐƯỜNG DẪN GỐC CỦA DỰ ÁN
// Tự động phát hiện base url nếu cần, hoặc fix cứng như bạn đã làm
$base_url = '/Schedio';

// --- LOGIC LẤY THÔNG BÁO MỚI NHẤT (TOAST) ---
$toast_notif = null;
if (isset($_SESSION['user_id']) && isset($conn)) {
    $uid = $_SESSION['user_id'];
    // Lấy 1 thông báo chưa đọc mới nhất (trong vòng 30 phút qua để tránh hiện lại thông báo quá cũ)
    $sql_toast = "SELECT * FROM notifications WHERE user_id = $uid AND is_read = 0 ORDER BY created_at DESC LIMIT 1";
    $res_toast = $conn->query($sql_toast);
    if ($res_toast && $res_toast->num_rows > 0) {
        $toast_notif = $res_toast->fetch_assoc();

        // Đánh dấu đã hiện toast để không hiện lại lần sau (Tùy chọn, ở đây mình giữ nguyên chưa đọc để user vào trang thông báo xem lại)
        // Nếu muốn hiện 1 lần rồi thôi thì dùng session flash hoặc update db ở đây
    }
}
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

    <style>
        /* CSS cho Avatar tròn trên header */
        .user-avatar-img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.2s;
        }

        .user-avatar-btn:hover .user-avatar-img {
            transform: scale(1.05);
            border-color: var(--schedio-primary, #fdd03b);
        }

        .btn-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
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

                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php
                    $avatar_url = !empty($_SESSION['user_avatar'])
                        ? $base_url . '/' . htmlspecialchars($_SESSION['user_avatar'])
                        : 'https://i.ibb.co/5Y8wNcz/user-placeholder.png';
                    ?>
                    <div class="dropdown">
                        <a href="#" class="btn p-0 border-0 user-avatar-btn" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <img src="<?php echo $avatar_url; ?>" alt="Avatar" class="user-avatar-img">
                            <?php if ($toast_notif): ?>
                                <span
                                    class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                                    <span class="visually-hidden">New alerts</span>
                                </span>
                            <?php endif; ?>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 rounded-3">
                            <li>
                                <h6 class="dropdown-header">Xin chào,
                                    <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Khách'); ?></h6>
                            </li>
                            <li><a class="dropdown-item py-2"
                                    href="<?php echo $base_url; ?>/customer/account.php?tab=profile"><i
                                        class="bi bi-person me-2"></i> Hồ sơ cá nhân</a></li>
                            <li><a class="dropdown-item py-2"
                                    href="<?php echo $base_url; ?>/customer/account.php?tab=orders"><i
                                        class="bi bi-bag me-2"></i> Đơn mua</a></li>
                            <li>
                                <a class="dropdown-item py-2 d-flex justify-content-between align-items-center"
                                    href="<?php echo $base_url; ?>/customer/account.php?tab=notifications">
                                    <span><i class="bi bi-bell me-2"></i> Thông báo</span>
                                    <?php if ($toast_notif): ?>
                                        <span class="badge bg-danger rounded-pill">New</span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item py-2 text-danger" href="<?php echo $base_url; ?>/logout.php"><i
                                        class="bi bi-box-arrow-right me-2"></i> Đăng xuất</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $base_url; ?>/login.php" class="btn btn-outline-dark btn-circle" title="Đăng nhập">
                        <i class="bi bi-person"></i>
                    </a>
                <?php endif; ?>

            </div>
        </nav>
    </header>

    <?php if ($toast_notif): ?>
        <div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index: 9999;">
            <div id="liveToast" class="toast fade show border-0 shadow-lg" role="alert" aria-live="assertive"
                aria-atomic="true">
                <div
                    class="toast-header text-white bg-<?php echo ($toast_notif['type'] == 'info' ? 'primary' : $toast_notif['type']); ?>">
                    <i class="bi bi-bell-fill me-2"></i>
                    <strong class="me-auto">Thông báo mới</strong>
                    <small>Vừa xong</small>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"
                        aria-label="Close"></button>
                </div>
                <div class="toast-body bg-white rounded-bottom">
                    <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($toast_notif['title']); ?></h6>
                    <p class="mb-2 text-secondary small"><?php echo htmlspecialchars($toast_notif['message']); ?></p>

                    <?php if ($toast_notif['order_id']): ?>
                        <div class="mt-2 pt-2 border-top text-end">
                            <a href="<?php echo $base_url; ?>/customer/order_detail.php?id=<?php echo $toast_notif['order_id']; ?>"
                                class="btn btn-sm btn-outline-primary stretched-link">Xem chi tiết</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($toast_notif): ?>
            <div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index: 9999;" id="toastContainer"
                data-notif-id="<?php echo $toast_notif['id']; ?>">

                <div id="liveToast" class="toast fade show border-0 shadow-lg" role="alert" aria-live="assertive"
                    aria-atomic="true">
                    <div
                        class="toast-header text-white bg-<?php echo ($toast_notif['type'] == 'info' ? 'primary' : $toast_notif['type']); ?>">
                        <i class="bi bi-bell-fill me-2"></i>
                        <strong class="me-auto">Thông báo mới</strong>
                        <small>Vừa xong</small>
                        <button type="button" class="btn-close btn-close-white" aria-label="Close"></button>
                    </div>
                    <div class="toast-body bg-white rounded-bottom">
                        <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($toast_notif['title']); ?></h6>
                        <p class="mb-2 text-secondary small"><?php echo htmlspecialchars($toast_notif['message']); ?></p>

                        <?php if ($toast_notif['order_id']): ?>
                            <div class="mt-2 pt-2 border-top text-end">
                                <a href="<?php echo $base_url; ?>/customer/order_detail.php?id=<?php echo $toast_notif['order_id']; ?>"
                                    class="btn btn-sm btn-outline-primary stretched-link btn-view-detail">Xem chi tiết</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var toastEl = document.getElementById('liveToast');
                    var container = document.getElementById('toastContainer');

                    // Đường dẫn đến file xử lý (Sử dụng base_url từ PHP)
                    var apiUrl = '<?php echo $base_url; ?>/mark_notification_read.php';

                    if (toastEl && container) {
                        var notifId = container.getAttribute('data-notif-id');
                        var toast = new bootstrap.Toast(toastEl, {
                            autohide: false
                        }); // Tắt tự động ẩn của Bootstrap để ta tự quản lý

                        toast.show();

                        // Hàm gọi API đánh dấu đã đọc
                        function markAsRead() {
                            fetch(apiUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        id: notifId
                                    })
                                }).then(res => res.json())
                                .then(data => console.log('Notification marked as read:', data))
                                .catch(err => console.error('Error:', err));
                        }

                        // 1. Tự động ẩn và đánh dấu đã đọc sau 10 giây
                        var autoHideTimer = setTimeout(function() {
                            markAsRead();
                            toast.hide();
                        }, 10000);

                        // 2. Xử lý khi bấm nút X
                        var btnClose = toastEl.querySelector('.btn-close');
                        if (btnClose) {
                            btnClose.addEventListener('click', function(e) {
                                e.preventDefault(); // Ngăn chặn mọi hành vi reload mặc định
                                clearTimeout(autoHideTimer); // Xóa timer đếm ngược
                                markAsRead(); // Gọi API ngay
                                toast.hide(); // Ẩn giao diện
                            });
                        }

                        // 3. Xử lý khi bấm "Xem chi tiết" (cũng đánh dấu đã đọc luôn)
                        var btnView = toastEl.querySelector('.btn-view-detail');
                        if (btnView) {
                            btnView.addEventListener('click', function() {
                                markAsRead();
                            });
                        }
                    }
                });
            </script>
        <?php endif; ?>
    <?php endif; ?>

    <main>