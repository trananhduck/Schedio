<?php
// customer/order_detail.php
session_start();
require_once '../config/db.php';
include '../templates/header.php';

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='../login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 2. XỬ LÝ POST (CÁC HÀNH ĐỘNG CỦA KHÁCH)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $msg = '';
    $redirect = false;

    // --- A. Khách bấm HỦY ---
    if ($action === 'cancel') {
        $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
        if ($stmt->execute()) {
            $msg = "Đã hủy đơn hàng.";
            $redirect = true;
        }
    }

    // --- B. Khách bấm DUYỆT & THANH TOÁN ---
    elseif ($action === 'approve_pay') {
        $stmt = $conn->prepare("UPDATE orders SET status = 'waiting_payment' WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();

        echo "<script>window.location.href='checkout.php?order_id=$order_id';</script>";
        exit;
    }

    // --- C. Khách YÊU CẦU CHỈNH SỬA ---
    elseif ($action === 'request_edit') {
        $feedback = trim($_POST['feedback_content']);
        if (!empty($feedback)) {
            $get_note = $conn->query("SELECT note FROM orders WHERE id = $order_id")->fetch_assoc()['note'];
            $new_note = $get_note . "\n\n----------------\n[KHÁCH YÊU CẦU SỬA " . date('d/m H:i') . "]:\n" . $feedback;

            $stmt = $conn->prepare("UPDATE orders SET status = 'pending', note = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sii", $new_note, $order_id, $user_id);

            if ($stmt->execute()) {
                $msg = "Đã gửi yêu cầu chỉnh sửa cho Admin.";
                $redirect = true;
            }
        }
    }

    if ($redirect) {
        echo "<script>alert('$msg'); window.location.href='order_detail.php?id=$order_id';</script>";
        exit;
    }
}

// 3. LẤY DỮ LIỆU ĐƠN HÀNG TỪ DB
$sql = "
    SELECT 
        o.*,
        p.name AS package_name, 
        pl.name AS platform_name,
        (SELECT start_time FROM schedules WHERE post_id IN (SELECT id FROM post WHERE order_id = o.id) ORDER BY start_time ASC LIMIT 1) as first_schedule
    FROM orders o
    JOIN service_option so ON o.service_option_id = so.id
    JOIN package p ON so.package_id = p.id
    JOIN platform pl ON so.platform_id = pl.id
    WHERE o.id = ? AND o.user_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    echo '<div class="container my-5 py-5 text-center"><div class="alert alert-danger d-inline-block px-5"><strong>Không tìm thấy đơn hàng!</strong></div></div>';
    include '../templates/footer.php';
    exit;
}

// --- DỮ LIỆU TỪ ADMIN GỬI SANG ---
$admin_demo_file = $order['admin_feedback_files']; // Link ảnh/video demo
$admin_message = $order['admin_feedback_content']; // Lời nhắn demo
$result_links = $order['result_links'];            // Link kết quả

$booking_date = $order['first_schedule'] ? date('d/m/Y H:i', strtotime($order['first_schedule'])) : '<span class="text-muted fst-italic">Chưa xếp lịch</span>';
$price_display = number_format($order['price_at_purchase'], 0, ',', '.') . ' đ';

// Map trạng thái
$status_map = [
    'pending' => ['bg-warning text-dark', 'Đang thiết kế'],
    'design_review' => ['bg-info text-white', 'Đã có Demo - Cần duyệt'],
    'waiting_payment' => ['bg-primary', 'Chờ thanh toán'],
    'paid' => ['bg-success', 'Đã thanh toán'],
    'in_progress' => ['bg-primary-subtle text-primary border border-primary', 'Đang thực hiện'],
    'completed' => ['bg-success', 'Hoàn thành'],
    'cancelled' => ['bg-danger', 'Đã hủy']
];
$status_info = $status_map[$order['status']] ?? ['bg-secondary', $order['status']];
?>

<div class="container my-5">

    <div class="mb-4">
        <a href="account.php?tab=orders" class="text-decoration-none text-muted hover-underline">
            <i class="bi bi-arrow-left"></i> Quay lại danh sách đơn hàng
        </a>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <h1 class="display-6 fw-bold text-dark-blue mb-0">
            Chi tiết đơn hàng #SCD-<?php echo str_pad($order['id'], 3, '0', STR_PAD_LEFT); ?>
        </h1>
        <span class="badge <?php echo $status_info[0]; ?> fs-6 px-4 py-2 rounded-pill shadow-sm">
            <?php echo $status_info[1]; ?>
        </span>
    </div>

    <div class="row g-4">

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 h-100 bg-white">
                <h5 class="fw-bold text-dark-blue mb-3 border-bottom pb-2">Thông tin dịch vụ</h5>
                <div class="mb-3">
                    <label class="small text-muted fw-bold">GÓI DỊCH VỤ</label>
                    <div class="fw-bold text-primary fs-5"><?php echo htmlspecialchars($order['package_name']); ?></div>
                </div>
                <div class="mb-3">
                    <label class="small text-muted fw-bold">KÊNH TRUYỀN THÔNG</label>
                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($order['platform_name']); ?></div>
                </div>
                <div class="mb-3">
                    <label class="small text-muted fw-bold">LỊCH ĐĂNG (DỰ KIẾN)</label>
                    <div class="text-danger fw-bold"><?php echo $booking_date; ?></div>
                </div>
                <div class="mb-3">
                    <label class="small text-muted fw-bold">GIÁ TRỊ ĐƠN HÀNG</label>
                    <div class="fs-4 fw-bold text-success"><?php echo $price_display; ?></div>
                </div>
                <hr class="text-muted">
                <div class="mb-3">
                    <label class="small text-muted fw-bold mb-2">YÊU CẦU CỦA BẠN</label>
                    <div class="bg-light p-3 rounded small text-secondary fst-italic">
                        <?php echo !empty($order['note']) ? nl2br(htmlspecialchars($order['note'])) : 'Không có ghi chú.'; ?>
                    </div>
                </div>
                <div class="mt-auto">
                    <label class="small text-muted fw-bold mb-1">TÀI NGUYÊN</label>
                    <a href="<?php echo htmlspecialchars($order['content_url']); ?>" target="_blank"
                        class="btn btn-outline-primary w-100 btn-sm">
                        <i class="bi bi-folder2-open me-1"></i> Mở Link Drive gốc
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">

            <?php if ($order['status'] == 'pending'): ?>
                <div
                    class="card border-0 bg-light p-5 text-center h-100 d-flex justify-content-center align-items-center dashed-border">
                    <div>
                        <div class="spinner-border text-warning mb-3" style="width: 3rem; height: 3rem;" role="status">
                        </div>
                        <h4 class="fw-bold text-dark-blue">Đang thiết kế...</h4>
                        <p class="text-muted">Đội ngũ Admin đang xử lý yêu cầu của bạn.<br>Vui lòng quay lại sau để xem bản
                            Demo.</p>

                        <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này không?');"
                            class="mt-4">
                            <input type="hidden" name="action" value="cancel">
                            <button class="btn btn-link text-danger text-decoration-none">
                                <i class="bi bi-x-circle me-1"></i> Hủy đơn hàng
                            </button>
                        </form>
                    </div>
                </div>

            <?php elseif ($order['status'] == 'design_review'): ?>
                <div class="card border-0 shadow-sm h-100">
                    <div
                        class="card-header bg-primary text-white fw-bold py-3 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-stars me-2"></i> BẢN DEMO TỪ ADMIN</span>
                        <span class="badge bg-white text-primary">Cần hành động</span>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($admin_message)): ?>
                            <div class="alert alert-primary bg-opacity-10 border-primary border-opacity-25 mb-4">
                                <i class="bi bi-chat-quote-fill me-2 text-primary"></i>
                                <strong>Lời nhắn:</strong> <?php echo nl2br(htmlspecialchars($admin_message)); ?>
                            </div>
                        <?php endif; ?>

                        <div class="mb-4 text-center border rounded p-4 bg-light">
                            <?php if (empty($admin_demo_file)): ?>
                                <p class="text-muted fst-italic">Lỗi hiển thị file demo.</p>
                            <?php elseif (strpos($admin_demo_file, 'drive.google.com') !== false): ?>
                                <i class="bi bi-google display-1 text-primary mb-3 d-block"></i>
                                <h5 class="fw-bold">File demo được lưu trên Drive</h5>
                                <a href="<?php echo $admin_demo_file; ?>" target="_blank" class="btn btn-primary mt-2 px-4">
                                    <i class="bi bi-box-arrow-up-right me-2"></i> Xem Demo ngay
                                </a>
                            <?php else: ?>
                                <img src="<?php echo $admin_demo_file; ?>" class="img-fluid rounded shadow-sm"
                                    style="max-height: 400px; object-fit: contain;">
                                <div class="mt-2">
                                    <a href="<?php echo $admin_demo_file; ?>" target="_blank"
                                        class="small text-decoration-none">
                                        <i class="bi bi-zoom-in"></i> Xem ảnh lớn
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-end gap-3 pt-3 border-top">
                            <button type="button" class="btn btn-outline-warning text-dark fw-bold px-4"
                                data-bs-toggle="modal" data-bs-target="#feedbackModal">
                                <i class="bi bi-pencil-square me-2"></i> Yêu cầu chỉnh sửa
                            </button>

                            <form method="POST">
                                <input type="hidden" name="action" value="approve_pay">
                                <button class="btn btn-success fw-bold px-4 shadow-sm">
                                    <i class="bi bi-check-lg me-2"></i> Ưng ý & Thanh toán
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            <?php elseif ($order['status'] == 'waiting_payment'): ?>
                <div
                    class="card border-0 border-primary border-2 shadow-sm p-5 text-center h-100 d-flex justify-content-center align-items-center">
                    <div>
                        <i class="bi bi-credit-card-2-front text-primary display-1 mb-3"></i>
                        <h3 class="fw-bold text-dark-blue">Đơn hàng đang chờ thanh toán</h3>
                        <p class="text-muted mb-4">Bạn đã duyệt thiết kế. Vui lòng hoàn tất thanh toán để chúng tôi đăng
                            bài.</p>
                        <a href="checkout.php?order_id=<?php echo $order_id; ?>"
                            class="btn btn-schedio-primary btn-lg px-5 shadow-sm">
                            Tiếp tục thanh toán <i class="bi bi-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>

            <?php elseif ($order['status'] == 'completed' || $order['status'] == 'paid' || $order['status'] == 'in_progress'): ?>
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-bold py-3 border-bottom">
                        <i class="bi bi-info-circle me-2 text-primary"></i> Trạng thái hoạt động
                    </div>
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <i class="bi bi-check-circle-fill text-success display-3 mb-3 d-block"></i>
                            <h3 class="fw-bold text-success">
                                <?php echo ($order['status'] == 'completed') ? 'Đơn hàng hoàn tất!' : 'Đã thanh toán thành công!'; ?>
                            </h3>
                            <p class="text-muted">
                                <?php echo ($order['status'] == 'completed') ? 'Bài viết của bạn đã được đăng tải.' : 'Đội ngũ Admin đang tiến hành đăng bài theo lịch.'; ?>
                            </p>
                        </div>

                        <?php if ($order['status'] == 'completed'): ?>
                            <div class="bg-light p-4 rounded border">
                                <h6 class="fw-bold text-dark-blue mb-3">Kết quả bài đăng:</h6>
                                <?php if (!empty($result_links)): ?>
                                    <?php
                                    $links = explode("\n", $result_links);
                                    foreach ($links as $link):
                                        $link = trim($link);
                                        if ($link == '') continue;

                                        // --- LOGIC KIỂM TRA LINK (ĐÃ THÊM MỚI) ---
                                        $link_text = "Xem bài viết kết quả";
                                        $icon_class = "bi-link-45deg";
                                        $text_class = "text-dark";

                                        if (strpos($link, 'facebook.com') !== false || strpos($link, 'fb.com') !== false) {
                                            $link_text = "Xem bài viết trên Facebook";
                                            $icon_class = "bi-facebook";
                                            $text_class = "text-primary"; // Màu xanh Facebook
                                        } elseif (strpos($link, 'tiktok.com') !== false) {
                                            $link_text = "Xem bài viết trên TikTok";
                                            $icon_class = "bi-tiktok";
                                            $text_class = "text-dark"; // Màu đen TikTok
                                        } elseif (strpos($link, 'youtube.com') !== false || strpos($link, 'youtu.be') !== false) {
                                            $link_text = "Xem video trên YouTube";
                                            $icon_class = "bi-youtube";
                                            $text_class = "text-danger"; // Màu đỏ YouTube
                                        }
                                    ?>
                                        <div class="mb-2">
                                            <a href="<?php echo $link; ?>" target="_blank"
                                                class="d-flex align-items-center text-decoration-none p-3 bg-white rounded border hover-shadow">
                                                <i class="bi <?php echo $icon_class; ?> fs-3 me-3 <?php echo $text_class; ?>"></i>
                                                <div>
                                                    <span
                                                        class="fw-bold <?php echo $text_class; ?> d-block"><?php echo $link_text; ?></span>
                                                    <small class="text-muted text-truncate d-block"
                                                        style="max-width: 300px;"><?php echo $link; ?></small>
                                                </div>
                                                <i class="bi bi-box-arrow-up-right ms-auto text-muted"></i>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-warning small">Admin chưa cập nhật link bài đăng.</div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($order['status'] == 'cancelled'): ?>
                <div class="card border-0 bg-light p-5 text-center h-100 d-flex justify-content-center align-items-center">
                    <div>
                        <i class="bi bi-x-circle text-danger display-3 mb-3"></i>
                        <h3 class="fw-bold text-danger">Đơn hàng đã bị hủy</h3>
                        <p class="text-muted">Nếu bạn muốn đặt lại, vui lòng tạo đơn hàng mới.</p>
                        <a href="../services.php" class="btn btn-outline-dark mt-3">Đặt đơn mới</a>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<div class="modal fade" id="feedbackModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Yêu cầu chỉnh sửa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Bạn muốn thay đổi điểm nào?</label>
                        <textarea class="form-control" name="feedback_content" rows="4"
                            placeholder="Ví dụ: Đổi màu chữ sang màu đỏ, làm logo to hơn..." required></textarea>
                    </div>
                    <input type="hidden" name="action" value="request_edit">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-warning text-dark">Gửi yêu cầu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>