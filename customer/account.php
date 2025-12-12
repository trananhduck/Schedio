<?php
// customer/account.php
session_start();
require_once '../config/db.php'; 

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// --- XỬ LÝ 1: CẬP NHẬT THÔNG TIN & AVATAR ---
// (Giữ nguyên logic cũ)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $fullname = $last_name . ' ' . $first_name;

    $avatar_path = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $target_dir = "../uploads/avatars/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_extension = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
        $new_filename = "user_" . $user_id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
            $avatar_path = "uploads/avatars/" . $new_filename;
            $_SESSION['user_avatar'] = $avatar_path;
        }
    }

    $sql = "UPDATE users SET fullname = ?, phonenumber = ?" . ($avatar_path ? ", avatar = ?" : "") . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($avatar_path) $stmt->bind_param("sssi", $fullname, $phone, $avatar_path, $user_id);
    else $stmt->bind_param("ssi", $fullname, $phone, $user_id);
    
    if ($stmt->execute()) {
        $message = "Cập nhật thông tin thành công!";
        $_SESSION['user_name'] = $fullname;
    }
}

// --- XỬ LÝ 2: ĐỔI MẬT KHẨU ---
// (Giữ nguyên logic cũ)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    // ... (Code đổi mật khẩu cũ) ...
    // Để code gọn, bạn có thể copy lại phần này từ file cũ nếu cần
}

// --- LẤY THÔNG TIN USER ---
$stmt = $conn->prepare("SELECT fullname, email, phonenumber, avatar FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$name_parts = explode(" ", $user['fullname']);
$first_name_display = array_pop($name_parts);
$last_name_display = implode(" ", $name_parts);
$avatar_src = !empty($user['avatar']) ? "../" . $user['avatar'] : "https://via.placeholder.com/150";

// --- XÁC ĐỊNH TAB ---
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';
$current_status = isset($_GET['status']) ? $_GET['status'] : 'all';

// --- XỬ LÝ 3: LẤY ĐƠN HÀNG ---
$orders = [];
if ($current_tab == 'orders') {
    $sql_orders = "SELECT o.id, p.name AS package_name, pl.name AS platform_name, o.price_at_purchase, o.created_at, o.status, 
                  (SELECT start_time FROM schedules WHERE post_id IN (SELECT id FROM post WHERE order_id = o.id) ORDER BY start_time ASC LIMIT 1) as first_schedule 
                  FROM orders o 
                  JOIN service_option so ON o.service_option_id = so.id 
                  JOIN package p ON so.package_id = p.id 
                  JOIN platform pl ON so.platform_id = pl.id 
                  WHERE o.user_id = ?";
    
    if ($current_status != 'all') {
        if ($current_status == 'in_progress') $sql_orders .= " AND o.status IN ('paid', 'design_review', 'waiting_payment', 'in_progress')";
        else $sql_orders .= " AND o.status = '$current_status'";
    }
    $sql_orders .= " ORDER BY o.created_at DESC";
    
    $stmt = $conn->prepare($sql_orders);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $orders[] = $row;
}

// --- XỬ LÝ 4: LẤY THÔNG BÁO (MỚI THÊM) ---
$notifications = [];
if ($current_tab == 'notifications') {
    // Đánh dấu đã đọc tất cả
    $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");
    
    // Lấy danh sách
    $sql_notif = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql_notif);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
}

include '../templates/header.php';
?>

<div class="container my-5">
    <h1 class="display-5 fw-bold text-dark-blue mb-5">
        <?php 
            if($current_tab == 'orders') echo 'Đơn mua';
            elseif($current_tab == 'notifications') echo 'Thông báo';
            else echo 'Tài khoản';
        ?>
    </h1>

    <?php if ($message): ?> <div class="alert alert-success"><?php echo $message; ?></div> <?php endif; ?>
    <?php if ($error): ?> <div class="alert alert-danger"><?php echo $error; ?></div> <?php endif; ?>

    <div class="row">
        <div class="col-lg-3 mb-5">
            <div class="account-sidebar">
                <ul class="list-unstyled">
                    <li>
                        <a href="account.php?tab=profile"
                            class="sidebar-link <?php echo ($current_tab == 'profile') ? 'active' : ''; ?>">
                            <i class="bi bi-person me-2"></i> Hồ sơ cá nhân
                        </a>
                    </li>
                    <li>
                        <a href="account.php?tab=notifications"
                            class="sidebar-link <?php echo ($current_tab == 'notifications') ? 'active' : ''; ?>">
                            <i class="bi bi-bell me-2"></i> Thông báo
                        </a>
                    </li>
                    <li>
                        <a href="account.php?tab=orders"
                            class="sidebar-link <?php echo ($current_tab == 'orders') ? 'active' : ''; ?>">
                            <i class="bi bi-bag me-2"></i> Đơn hàng
                        </a>
                    </li>
                    <li class="mt-3 border-top pt-3">
                        <a href="../logout.php" class="sidebar-link text-danger">Đăng xuất <i
                                class="bi bi-box-arrow-right ms-2"></i></a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-lg-9">

            <?php if ($current_tab == 'profile'): ?>
            <div class="account-tabs mb-4">
                <ul class="nav nav-tabs border-0" id="profileTab" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab"
                            data-bs-target="#profile-info">Thông tin của tôi</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab"
                            data-bs-target="#change-password">Thay đổi mật khẩu</button></li>
                </ul>
            </div>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="profile-info">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="mb-4">
                            <label class="form-label-schedio mb-2">Ảnh đại diện</label>
                            <div class="d-flex align-items-center">
                                <img src="<?php echo htmlspecialchars($avatar_src); ?>" class="rounded-circle me-3"
                                    style="width: 80px; height: 80px; object-fit: cover;">
                                <input type="file" class="form-control form-control-sm" name="avatar">
                            </div>
                        </div>
                        <div class="mb-3"><label class="form-label-schedio">Họ*</label><input type="text"
                                class="form-control form-control-schedio" name="last_name"
                                value="<?php echo htmlspecialchars($last_name_display); ?>" required></div>
                        <div class="mb-3"><label class="form-label-schedio">Tên*</label><input type="text"
                                class="form-control form-control-schedio" name="first_name"
                                value="<?php echo htmlspecialchars($first_name_display); ?>" required></div>
                        <div class="mb-3"><label class="form-label-schedio">Email</label><input type="email"
                                class="form-control form-control-schedio"
                                value="<?php echo htmlspecialchars($user['email']); ?>" readonly
                                style="background-color: #f8f9fa;"></div>
                        <div class="mb-3"><label class="form-label-schedio">SĐT</label><input type="text"
                                class="form-control form-control-schedio" name="phone"
                                value="<?php echo htmlspecialchars($user['phonenumber']); ?>"></div>
                        <button type="submit" class="btn btn-schedio-primary px-4 mt-3">Lưu thay đổi</button>
                    </form>
                </div>
            </div>

            <?php elseif ($current_tab == 'notifications'): ?>
            <div class="list-group border-0">
                <?php if (count($notifications) > 0): ?>
                <?php foreach ($notifications as $notif): ?>
                <?php 
                                $icon = 'bi-info-circle text-primary';
                                if($notif['type'] == 'success') $icon = 'bi-check-circle-fill text-success';
                                if($notif['type'] == 'warning') $icon = 'bi-exclamation-circle-fill text-warning';
                                if($notif['type'] == 'danger') $icon = 'bi-x-circle-fill text-danger';
                            ?>
                <a href="order_detail.php?id=<?php echo $notif['order_id']; ?>"
                    class="list-group-item list-group-item-action p-4 d-flex gap-3 border-0 shadow-sm mb-3 rounded align-items-start">
                    <div class="fs-4 mt-1"><i class="bi <?php echo $icon; ?>"></i></div>
                    <div class="w-100">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1 fw-bold text-dark-blue"><?php echo htmlspecialchars($notif['title']); ?>
                            </h5>
                            <small
                                class="text-muted"><?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?></small>
                        </div>
                        <p class="mb-1 text-secondary"><?php echo htmlspecialchars($notif['message']); ?></p>
                        <small class="text-primary fw-bold">Xem chi tiết <i class="bi bi-arrow-right"></i></small>
                    </div>
                </a>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-bell-slash display-1 text-muted opacity-25"></i>
                    <p class="text-muted mt-3">Bạn chưa có thông báo nào.</p>
                </div>
                <?php endif; ?>
            </div>

            <?php elseif ($current_tab == 'orders'): ?>
            <ul class="nav nav-underline mb-4 border-bottom">
                <li class="nav-item"><a
                        class="nav-link <?php echo ($current_status == 'all') ? 'active fw-bold' : ''; ?>"
                        href="account.php?tab=orders&status=all">Tất cả</a></li>
                <li class="nav-item"><a
                        class="nav-link <?php echo ($current_status == 'pending') ? 'active fw-bold' : ''; ?>"
                        href="account.php?tab=orders&status=pending">Chờ xử lý</a></li>
            </ul>

            <div class="table-responsive rounded shadow-sm border">
                <table class="table table-borderless align-middle mb-0 bg-light-yellow">
                    <tbody>
                        <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                        <tr class="border-bottom">
                            <td class="ps-4 fw-bold">SCD-<?php echo str_pad($order['id'], 3, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($order['package_name']); ?></td>
                            <td><?php echo $order['first_schedule'] ? date('d/m/Y', strtotime($order['first_schedule'])) : '--'; ?>
                            </td>
                            <td class="fw-bold"><?php echo number_format($order['price_at_purchase'], 0, ',', '.'); ?>đ
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                            <td>
                                <?php 
                                                // Badge trạng thái (giữ nguyên logic cũ)
                                                $s_label = 'Đang xử lý'; $s_class = 'bg-warning text-dark';
                                                if($order['status']=='completed') { $s_label='Hoàn thành'; $s_class='bg-success text-white'; }
                                                // ...
                                            ?>
                                <span class="badge <?php echo $s_class; ?> rounded-pill"><?php echo $s_label; ?></span>
                            </td>
                            <td><a href="order_detail.php?id=<?php echo $order['id']; ?>"
                                    class="btn btn-sm btn-light border">Xem</a></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">Chưa có đơn hàng.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>