<?php
session_start();
require_once '../config/db.php';
require_once '../config/functions.php'; // File chứa hàm sendNotification

// 1. KIỂM TRA QUYỀN
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'staff')) {
    header("Location: login.php");
    exit;
}

// 2. LẤY ID ĐƠN HÀNG
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Không tìm thấy ID đơn hàng.");
}
$order_id = intval($_GET['id']);

// 3. XỬ LÝ POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $success_msg = "";
    $redirect_url = "order_detail.php?id=$order_id"; // Mặc định là ở lại trang này
    
    // --- XỬ LÝ 1: GỬI DEMO ---
    if ($action == 'send_demo') {
        $demo_link = trim($_POST['admin_demo_img']);
        $msg = trim($_POST['admin_message']);

        if (empty($demo_link)) {
            echo "<script>alert('Vui lòng điền Link ảnh/video Demo!'); window.history.back();</script>";
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE orders SET status = 'design_review', admin_feedback_files = ?, admin_feedback_content = ? WHERE id = ?");
        $stmt->bind_param("ssi", $demo_link, $msg, $order_id);
        $stmt->execute();
        $stmt->close();

        // Gửi thông báo
        $u_query = $conn->query("SELECT user_id FROM orders WHERE id = $order_id");
        $u_id = $u_query->fetch_assoc()['user_id'];
        sendNotification($conn, $u_id, $order_id, "Đã có bản Demo thiết kế!", "Admin vừa gửi bản demo cho đơn hàng #$order_id. Vui lòng kiểm tra.", "warning");
        
        $success_msg = "Đã gửi demo cho khách duyệt!";
    } 
    // --- XỬ LÝ 2: XÁC NHẬN THANH TOÁN ---
    elseif ($action == 'confirm_payment') {
        $stmt = $conn->prepare("UPDATE orders SET status = 'paid' WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();
        
        $success_msg = "Đã xác nhận thanh toán!";
    }
    // --- XỬ LÝ 3: HOÀN TẤT ĐƠN HÀNG ---
    elseif ($action == 'complete_order') {
        $result_link = trim($_POST['result_links']);

        if (empty($result_link)) {
            echo "<script>alert('Vui lòng điền Link bài đăng kết quả!'); window.history.back();</script>";
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE orders SET status = 'completed', result_links = ? WHERE id = ?");
        $stmt->bind_param("si", $result_link, $order_id);
        $stmt->execute();
        $stmt->close();
        
        // Cập nhật bảng post/schedules thành 'posted'
        $conn->query("UPDATE post SET status = 'posted' WHERE order_id = $order_id");
        $conn->query("UPDATE schedules SET status = 'posted' WHERE post_id IN (SELECT id FROM post WHERE order_id = $order_id)");
        
        // Gửi thông báo
        $u_query = $conn->query("SELECT user_id FROM orders WHERE id = $order_id");
        $u_id = $u_query->fetch_assoc()['user_id'];
        sendNotification($conn, $u_id, $order_id, "Đơn hàng hoàn tất", "Bài viết của đơn hàng #$order_id đã được đăng tải.", "success");
        
        $success_msg = "Đơn hàng đã hoàn thành!";
        
        // QUAN TRỌNG: Chuyển hướng về trang danh sách đơn hàng sau khi hoàn tất
        $redirect_url = "orders.php"; 
    }
    
    // Thực hiện chuyển hướng
    echo "<script>alert('$success_msg'); window.location.href='$redirect_url';</script>";
    exit;
}

// 4. TRUY VẤN DỮ LIỆU HIỂN THỊ
$sql = "
    SELECT o.*, 
           u.fullname AS customer_name, u.email AS customer_email,
           p.name AS package_name, 
           pl.name AS platform_name,
           (SELECT start_time FROM schedules WHERE post_id IN (SELECT id FROM post WHERE order_id = o.id) ORDER BY start_time ASC LIMIT 1) as schedule_time
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN service_option so ON o.service_option_id = so.id
    JOIN package p ON so.package_id = p.id
    JOIN platform pl ON so.platform_id = pl.id
    WHERE o.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    die("Không tìm thấy đơn hàng.");
}

// Format dữ liệu
$order_code = 'SCD-' . str_pad($order['id'], 3, '0', STR_PAD_LEFT);
$formatted_price = number_format($order['price_at_purchase'], 0, ',', '.') . ' đ';
$formatted_date = date('d/m/Y H:i', strtotime($order['created_at']));
$formatted_schedule = $order['schedule_time'] ? date('d/m/Y H:i', strtotime($order['schedule_time'])) : '<span class="text-muted">Chưa xếp lịch</span>';

// Map trạng thái
$status_map = [
    'pending' => ['Chờ xử lý', 'bg-warning text-dark'],
    'design_review' => ['Duyệt Demo', 'bg-info text-white'],
    'waiting_payment' => ['Chờ thanh toán', 'bg-primary text-white'],
    'paid' => ['Đã thanh toán', 'bg-success text-white'],
    'in_progress' => ['Đang thực hiện', 'bg-primary-subtle text-primary'],
    'completed' => ['Hoàn thành', 'bg-success text-white'],
    'cancelled' => ['Đã hủy', 'bg-danger text-white']
];
$status_info = $status_map[$order['status']] ?? ['Không xác định', 'bg-secondary'];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chi tiết đơn hàng #<?php echo $order_code; ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/admin-style.css">
</head>

<body>

    <div class="admin-wrapper">

        <?php include 'templates/sidebar.php'; ?>

        <div class="admin-content">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <a href="orders.php" class="text-decoration-none text-muted small"><i class="bi bi-arrow-left"></i>
                        Quay lại</a>
                    <h2 class="text-primary fw-bold mb-0 mt-1">Chi tiết đơn hàng #<?php echo $order_code; ?></h2>
                </div>
                <div>
                    <span class="badge <?php echo $status_info[1]; ?> fs-6 px-3 py-2 rounded-pill">
                        <?php echo $status_info[0]; ?>
                    </span>
                </div>
            </div>

            <div class="row g-4">

                <div class="col-lg-8">

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white fw-bold py-3">Thông tin yêu cầu</div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4 text-muted">Khách hàng:</div>
                                <div class="col-md-8 fw-bold">
                                    <?php echo htmlspecialchars($order['customer_name']); ?>
                                    <small
                                        class="fw-normal text-muted">(<?php echo htmlspecialchars($order['customer_email']); ?>)</small>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4 text-muted">Tiêu đề nội dung:</div>
                                <div class="col-md-8"><?php echo htmlspecialchars($order['title']); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4 text-muted">Link Drive (Source):</div>
                                <div class="col-md-8">
                                    <a href="<?php echo htmlspecialchars($order['content_url']); ?>" target="_blank"
                                        class="text-break">
                                        <?php echo htmlspecialchars($order['content_url']); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4 text-muted">Link Sản phẩm:</div>
                                <div class="col-md-8">
                                    <a href="<?php echo htmlspecialchars($order['product_link']); ?>" target="_blank"
                                        class="text-break">
                                        <?php echo htmlspecialchars($order['product_link']); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 text-muted">Ghi chú của khách:</div>
                                <div class="col-md-8 fst-italic text-danger">
                                    <?php echo !empty($order['note']) ? nl2br(htmlspecialchars($order['note'])) : 'Không có ghi chú'; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($order['status'] != 'completed' && $order['status'] != 'cancelled'): ?>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white fw-bold py-3">
                            <i class="bi bi-tools me-2"></i> Xử lý đơn hàng
                        </div>
                        <div class="card-body p-4">

                            <form method="POST" action="">
                                <div class="mb-4 pb-4 border-bottom">
                                    <label class="form-label fw-bold">1. Cập nhật bản thiết kế (Demo)</label>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text">Link ảnh/video</span>
                                        <input type="text" class="form-control" name="admin_demo_img"
                                            value="<?php echo htmlspecialchars($order['admin_feedback_files'] ?? ''); ?>"
                                            placeholder="Dán link ảnh demo vào đây..." required>
                                    </div>
                                    <textarea class="form-control" name="admin_message" rows="2"
                                        placeholder="Lời nhắn cho khách hàng..."><?php echo htmlspecialchars($order['admin_feedback_content'] ?? ''); ?></textarea>

                                    <button type="submit" name="action" value="send_demo"
                                        class="btn btn-sm btn-info text-white mt-2"
                                        onclick="return confirm('Bạn có chắc chắn muốn gửi Demo này cho khách duyệt không?')">
                                        <i class="bi bi-send"></i> Gửi Demo cho khách duyệt
                                    </button>
                                </div>
                            </form>

                            <form method="POST" action="">
                                <div class="mb-4 pb-4 border-bottom">
                                    <label class="form-label fw-bold">2. Trạng thái thanh toán</label>
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if($order['status'] == 'paid' || $order['status'] == 'in_progress'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Đã thanh
                                            toán</span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">Chưa thanh toán</span>
                                        <button type="submit" name="action" value="confirm_payment"
                                            class="btn btn-sm btn-success"
                                            onclick="return confirm('Xác nhận ĐÃ NHẬN ĐƯỢC TIỀN từ khách hàng?')">
                                            <i class="bi bi-check-circle"></i> Xác nhận đã nhận tiền thủ công
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </form>

                            <form method="POST" action="">
                                <div>
                                    <label class="form-label fw-bold">3. Hoàn tất & Trả link bài đăng</label>
                                    <textarea class="form-control mb-2" name="result_links" rows="3"
                                        placeholder="Dán link bài viết Facebook/TikTok đã đăng vào đây..."
                                        required><?php echo htmlspecialchars($order['result_links'] ?? ''); ?></textarea>

                                    <button type="submit" name="action" value="complete_order"
                                        class="btn btn-primary w-100"
                                        onclick="return confirm('Bạn có chắc chắn muốn ĐÓNG đơn hàng này và gửi kết quả không?')">
                                        <i class="bi bi-check2-all"></i> Cập nhật Hoàn thành đơn hàng
                                    </button>
                                </div>
                            </form>

                        </div>
                    </div>

                    <?php else: ?>
                    <div class="alert alert-success shadow-sm p-4 text-center">
                        <i class="bi bi-check-circle-fill display-4 mb-3 d-block text-success"></i>
                        <h4 class="alert-heading fw-bold">Đơn hàng đã hoàn tất</h4>
                        <p class="mb-0">Đơn hàng này đã được xử lý xong và đóng lại. Bạn không thể chỉnh sửa thêm.</p>
                        <?php if($order['status'] == 'cancelled'): ?>
                        <p class="text-danger fw-bold mt-2">(Đơn hàng đã bị Hủy)</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white fw-bold py-3">Gói dịch vụ</div>
                        <div class="card-body">
                            <h4 class="text-primary fw-bold mb-1">
                                <?php echo htmlspecialchars($order['package_name']); ?></h4>
                            <p class="text-muted small mb-3"><?php echo htmlspecialchars($order['platform_name']); ?>
                            </p>

                            <ul class="list-group list-group-flush mb-3 small">
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span>Ngày đăng ký:</span>
                                    <span class="fw-bold"><?php echo $formatted_date; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span>Lịch đăng bài (dự kiến):</span>
                                    <span class="fw-bold text-danger"><?php echo $formatted_schedule; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span>Tổng tiền:</span>
                                    <span class="fw-bold text-primary fs-5"><?php echo $formatted_price; ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-secondary" onclick="window.print()">
                            <i class="bi bi-printer"></i> In phiếu
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>