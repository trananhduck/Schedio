<?php
// customer/account.php
session_start();
require_once '../config/db.php'; // Kết nối CSDL

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// --- XỬ LÝ 1: CẬP NHẬT THÔNG TIN & AVATAR ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $fullname = $last_name . ' ' . $first_name;

    // Xử lý upload Avatar
    $avatar_path = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $target_dir = "../uploads/avatars/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
        $new_filename = "user_" . $user_id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_extension, $allowed_types)) {
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                $avatar_path = "uploads/avatars/" . $new_filename;
                $_SESSION['user_avatar'] = $avatar_path;
            } else {
                $error = "Lỗi khi tải ảnh lên.";
            }
        } else {
            $error = "Chỉ chấp nhận file ảnh (JPG, PNG, GIF).";
        }
    }

    if (empty($error)) {
        if ($avatar_path) {
            $stmt = $conn->prepare("UPDATE users SET fullname = ?, phonenumber = ?, avatar = ? WHERE id = ?");
            $stmt->bind_param("sssi", $fullname, $phone, $avatar_path, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET fullname = ?, phonenumber = ? WHERE id = ?");
            $stmt->bind_param("ssi", $fullname, $phone, $user_id);
        }

        if ($stmt->execute()) {
            $message = "Cập nhật thông tin thành công!";
            $_SESSION['user_name'] = $fullname;
        } else {
            $error = "Lỗi cập nhật: " . $conn->error;
        }
        $stmt->close();
    }
}

// --- XỬ LÝ 2: ĐỔI MẬT KHẨU ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_new_password'];

    if (strlen($new_pass) < 6) {
        $error = "Mật khẩu mới phải có ít nhất 6 ký tự.";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "Mật khẩu nhập lại không khớp.";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($hashed_password);
        $stmt->fetch();
        $stmt->close();

        if (password_verify($current_pass, $hashed_password)) {
            $new_hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_hashed_pass, $user_id);

            if ($update_stmt->execute()) {
                $message = "Đổi mật khẩu thành công!";
            } else {
                $error = "Lỗi hệ thống.";
            }
            $update_stmt->close();
        } else {
            $error = "Mật khẩu hiện tại không đúng.";
        }
    }
}

// --- LẤY THÔNG TIN USER HIỆN TẠI ---
$stmt = $conn->prepare("SELECT fullname, email, phonenumber, avatar FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$name_parts = explode(" ", $user['fullname']);
$first_name_display = array_pop($name_parts);
$last_name_display = implode(" ", $name_parts);
$avatar_src = !empty($user['avatar']) ? "../" . $user['avatar'] : "https://via.placeholder.com/150";

// --- XÁC ĐỊNH TAB & TRẠNG THÁI HIỆN TẠI ---
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';
$current_status = isset($_GET['status']) ? $_GET['status'] : 'all';

// --- XỬ LÝ 3: LẤY DANH SÁCH ĐƠN HÀNG (CÓ LỌC) ---
$orders = [];
if ($current_tab == 'orders') {
    $sql_orders = "
        SELECT 
            o.id, 
            p.name AS package_name, 
            pl.name AS platform_name, 
            o.price_at_purchase, 
            o.created_at, 
            o.status,
            (SELECT start_time FROM schedules WHERE post_id IN (SELECT id FROM post WHERE order_id = o.id) ORDER BY start_time ASC LIMIT 1) as first_schedule
        FROM orders o
        JOIN service_option so ON o.service_option_id = so.id
        JOIN package p ON so.package_id = p.id
        JOIN platform pl ON so.platform_id = pl.id
        WHERE o.user_id = ?
    ";

    if ($current_status != 'all') {
        if ($current_status == 'pending') {
            $sql_orders .= " AND o.status = 'pending'";
        } elseif ($current_status == 'in_progress') {
            $sql_orders .= " AND o.status IN ('paid', 'design_review', 'waiting_payment', 'in_progress')";
        } elseif ($current_status == 'completed') {
            $sql_orders .= " AND o.status = 'completed'";
        } elseif ($current_status == 'cancelled') {
            $sql_orders .= " AND o.status = 'cancelled'";
        }
    }

    $sql_orders .= " ORDER BY o.created_at DESC";

    $stmt = $conn->prepare($sql_orders);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    $stmt->close();
}

include '../templates/header.php';
?>

<div class="container my-5">
    <h1 class="display-5 fw-bold text-dark-blue mb-5">
        <?php echo ($current_tab == 'orders') ? 'Đơn mua' : 'Tài khoản'; ?>
    </h1>

    <?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-3 mb-5">
            <div class="account-sidebar">
                <ul class="list-unstyled">
                    <li><a href="account.php?tab=profile"
                            class="sidebar-link <?php echo ($current_tab == 'profile') ? 'active' : ''; ?>">Hồ sơ cá
                            nhân</a></li>
                    <li><a href="#" class="sidebar-link">Thông báo</a></li>
                    <li>
                        <a href="account.php?tab=orders"
                            class="sidebar-link <?php echo ($current_tab == 'orders') ? 'active' : ''; ?>">
                            Đơn hàng
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
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="info-tab" data-bs-toggle="tab"
                            data-bs-target="#profile-info" type="button">Thông tin của tôi</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="password-tab" data-bs-toggle="tab"
                            data-bs-target="#change-password" type="button">Thay đổi mật khẩu</button>
                    </li>
                </ul>
            </div>

            <div class="tab-content" id="profileTabContent">
                <div class="tab-pane fade show active" id="profile-info" role="tabpanel">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="update_profile" value="1">

                        <div class="mb-4">
                            <label class="form-label-schedio mb-2">Ảnh đại diện</label>
                            <div class="d-flex align-items-center">
                                <img src="<?php echo htmlspecialchars($avatar_src); ?>" alt="Avatar"
                                    class="rounded-circle me-3"
                                    style="width: 80px; height: 80px; object-fit: cover; border: 1px solid #eee;">
                                <div>
                                    <input type="file" class="form-control form-control-sm" id="avatar" name="avatar">
                                    <div class="form-text text-muted">Định dạng: .jpg, .png (Tối đa 2MB)</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-schedio">Họ*</label>
                            <input type="text" class="form-control form-control-schedio" name="last_name"
                                value="<?php echo htmlspecialchars($last_name_display); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-schedio">Tên*</label>
                            <input type="text" class="form-control form-control-schedio" name="first_name"
                                value="<?php echo htmlspecialchars($first_name_display); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-schedio">Email*</label>
                            <input type="email" class="form-control form-control-schedio"
                                value="<?php echo htmlspecialchars($user['email']); ?>" readonly
                                style="background-color: #f8f9fa;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label-schedio">Số điện thoại</label>
                            <input type="text" class="form-control form-control-schedio" name="phone"
                                value="<?php echo htmlspecialchars($user['phonenumber']); ?>">
                        </div>
                        <div class="mt-5">
                            <button type="submit" class="btn btn-schedio-primary px-4">Lưu thay đổi</button>
                        </div>
                    </form>
                </div>

                <div class="tab-pane fade" id="change-password" role="tabpanel">
                    <form action="" method="POST">
                        <input type="hidden" name="change_password" value="1">
                        <div class="mb-4">
                            <label class="form-label-schedio">Mật khẩu hiện tại*</label>
                            <input type="password" class="form-control form-control-schedio" name="current_password"
                                required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label-schedio">Mật khẩu mới*</label>
                            <input type="password" class="form-control form-control-schedio" name="new_password"
                                required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label-schedio">Nhập lại mật khẩu mới*</label>
                            <input type="password" class="form-control form-control-schedio" name="confirm_new_password"
                                required>
                        </div>
                        <div class="mt-5">
                            <button type="submit" class="btn btn-schedio-primary px-4">Đổi mật khẩu</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php elseif ($current_tab == 'orders'): ?>

            <ul class="nav nav-underline mb-4 border-bottom">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_status == 'all') ? 'active text-dark fw-bold bg-light' : 'text-dark'; ?> px-4 py-3"
                        href="account.php?tab=orders&status=all">Tất cả</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_status == 'pending') ? 'active text-dark fw-bold bg-light' : 'text-dark'; ?> px-4 py-3"
                        href="account.php?tab=orders&status=pending">Chờ xử lý</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_status == 'in_progress') ? 'active text-dark fw-bold bg-light' : 'text-dark'; ?> px-4 py-3"
                        href="account.php?tab=orders&status=in_progress">Đang thực hiện</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_status == 'completed') ? 'active text-dark fw-bold bg-light' : 'text-dark'; ?> px-4 py-3"
                        href="account.php?tab=orders&status=completed">Hoàn thành</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_status == 'cancelled') ? 'active text-dark fw-bold bg-light' : 'text-dark'; ?> px-4 py-3"
                        href="account.php?tab=orders&status=cancelled">Đã hủy</a>
                </li>
            </ul>

            <div class="table-responsive rounded shadow-sm border">
                <table class="table table-borderless align-middle mb-0 bg-light-yellow">
                    <thead class="table-light border-bottom">
                        <tr class="text-dark-blue small fw-bold">
                            <th class="py-3 ps-4">Mã đơn</th>
                            <th class="py-3">Tên gói</th>
                            <th class="py-3">Lịch đăng</th>
                            <th class="py-3">Thành tiền</th>
                            <th class="py-3">Ngày đăng ký</th>
                            <th class="py-3 text-center">Trạng thái</th>
                            <th class="py-3 text-center">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                        <tr class="border-bottom">
                            <td class="ps-4 fw-bold">SCD-<?php echo str_pad($order['id'], 3, '0', STR_PAD_LEFT); ?></td>
                            <td>
                                <?php echo htmlspecialchars($order['package_name']); ?>
                                <br>
                                <small class="text-muted"
                                    style="font-size: 0.85rem;"><?php echo htmlspecialchars($order['platform_name']); ?></small>
                            </td>
                            <td>
                                <?php echo $order['first_schedule'] ? date('d/m/Y - h:i A', strtotime($order['first_schedule'])) : 'Chưa xếp lịch'; ?>
                            </td>
                            <td class="fw-bold"><?php echo number_format($order['price_at_purchase'], 0, ',', '.'); ?>đ
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                            <td class="text-center">
                                <?php
                                            $status_labels = [
                                                'pending' => ['bg-warning text-dark', 'Chờ xử lý'],
                                                'design_review' => ['bg-info text-white', 'Đã thiết kế'],
                                                'waiting_payment' => ['bg-primary text-white', 'Chờ thanh toán'],
                                                'paid' => ['bg-info text-white', 'Đã thanh toán'],
                                                'in_progress' => ['bg-primary-light text-primary', 'Đang thực hiện'],
                                                'completed' => ['bg-success-light text-success', 'Hoàn thành'],
                                                'cancelled' => ['bg-danger text-white', 'Đã hủy']
                                            ];
                                            $s = $status_labels[$order['status']] ?? ['bg-secondary', $order['status']];
                                            ?>
                                <span class="badge <?php echo $s[0]; ?> rounded-pill px-3 py-2 fw-normal">
                                    <?php echo ucfirst($s[1]); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="order_detail.php?id=<?php echo $order['id']; ?>"
                                    class="btn btn-sm btn-light border"><i class="bi bi-file-text"></i> Xem</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox display-4 d-block mb-3 opacity-50"></i>
                                Không có đơn hàng nào ở trạng thái này.
                            </td>
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