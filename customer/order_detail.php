<?php
// customer/order_detail.php
session_start();
require_once '../config/db.php';
include '../templates/header.php';

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 2. XỬ LÝ CÁC HÀNH ĐỘNG (DUYỆT / HỦY)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $new_status = '';
        $msg = '';

        // Khách bấm Hủy (khi đang chờ hoặc không ưng thiết kế)
        if ($_POST['action'] === 'cancel') {
            $new_status = 'cancelled';
            $msg = "Đã hủy đơn hàng.";
        }
        // Khách bấm Duyệt & Thanh toán
        elseif ($_POST['action'] === 'approve_pay') {
            $new_status = 'waiting_payment'; // Chuyển sang trạng thái chờ thanh toán
            // Cập nhật DB
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sii", $new_status, $order_id, $_SESSION['user_id']);
            $stmt->execute();

            // Chuyển hướng ngay sang trang thanh toán
            header("Location: checkout.php?order_id=" . $order_id);
            exit;
        }

        // Cập nhật trạng thái (cho trường hợp Hủy)
        if ($new_status) {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sii", $new_status, $order_id, $_SESSION['user_id']);
            $stmt->execute();
            echo "<script>alert('$msg'); window.location.href='order_detail.php?id=$order_id';</script>";
        }
    }
}

// 3. LẤY THÔNG TIN ĐƠN HÀNG
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
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    echo '<div class="container my-5"><div class="alert alert-danger">Không tìm thấy đơn hàng.</div></div>';
    include '../templates/footer.php';
    exit;
}

// Format dữ liệu hiển thị
$booking_date = $order['first_schedule'] ? date('d/m/Y - H:i', strtotime($order['first_schedule'])) : 'N/A';
$price_display = number_format($order['price_at_purchase'], 0, ',', '.') . ' đ';

// Dữ liệu Demo (Dùng khi DB chưa có dữ liệu thật)
$admin_demo_img = !empty($order['admin_feedback_files']) ? $order['admin_feedback_files'] : "https://i.ibb.co/L5Tz5Vd/hero-calendar-img.png";
$admin_message = !empty($order['admin_feedback_content']) ? $order['admin_feedback_content'] : "Team đã thiết kế xong poster demo. Bạn xem qua nhé!";
?>

<div class="container my-5">
    <div class="mb-4">
        <a href="account.php?tab=orders" class="text-decoration-none text-muted"><i class="bi bi-arrow-left"></i> Quay
            lại danh sách</a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="display-6 fw-bold text-dark-blue">Chi tiết đơn hàng #<?php echo $order_id; ?></h1>

        <?php
        $status_labels = [
            'pending' => ['bg-warning text-dark', 'Đang chờ thiết kế'],
            'design_review' => ['bg-info text-white', 'Đã thiết kế xong'],
            'waiting_payment' => ['bg-primary', 'Chờ thanh toán'],
            'paid' => ['bg-success', 'Đã thanh toán'],
            'in_progress' => ['bg-primary', 'Đang đăng bài'],
            'completed' => ['bg-success', 'Hoàn thành'],
            'cancelled' => ['bg-danger', 'Đã hủy']
        ];
        $s = $status_labels[$order['status']] ?? ['bg-secondary', $order['status']];
        ?>
        <span class="badge <?php echo $s[0]; ?> fs-6 px-3 py-2 rounded-pill"><?php echo $s[1]; ?></span>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 schedio-card-bg p-4 h-100">
                <h5 class="fw-bold text-dark-blue mb-3">Thông tin chung</h5>
                <div class="mb-3">
                    <label class="small text-muted fw-bold">GÓI DỊCH VỤ</label>
                    <div class="fw-bold text-primary"><?php echo htmlspecialchars($order['package_name']); ?></div>
                </div>
                <div class="mb-3">
                    <label class="small text-muted fw-bold">KÊNH ĐĂNG</label>
                    <div><?php echo htmlspecialchars($order['platform_name']); ?></div>
                </div>
                <div class="mb-3">
                    <label class="small text-muted fw-bold">LỊCH BẮT ĐẦU</label>
                    <div><?php echo $booking_date; ?></div>
                </div>
                <div class="mb-3">
                    <label class="small text-muted fw-bold">GIÁ TRỊ</label>
                    <div class="fs-5 fw-bold text-dark-blue"><?php echo $price_display; ?></div>
                </div>
                <hr>
                <div class="mb-3">
                    <label class="small text-muted fw-bold">YÊU CẦU CỦA BẠN</label>
                    <p class="small fst-italic mt-1"><?php echo nl2br(htmlspecialchars($order['note'])); ?></p>
                    <a href="<?php echo htmlspecialchars($order['content_url']); ?>" target="_blank"
                        class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-google me-1"></i> Link Drive gốc
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">

            <?php if ($order['status'] == 'pending'): ?>
            <div class="card border-0 bg-light p-5 text-center h-100 d-flex justify-content-center align-items-center">
                <div>
                    <div class="spinner-border text-warning mb-3" style="width: 3rem; height: 3rem;" role="status">
                    </div>
                    <h4 class="fw-bold text-dark-blue">Đội ngũ Admin đang thiết kế...</h4>
                    <p class="text-muted">Chúng tôi đang xử lý yêu cầu của bạn. Vui lòng quay lại sau để xem bản demo.
                    </p>

                    <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này không?');"
                        class="mt-4">
                        <input type="hidden" name="action" value="cancel">
                        <button class="btn btn-outline-danger px-4">Hủy đơn hàng</button>
                    </form>
                </div>
            </div>

            <?php elseif ($order['status'] == 'design_review'): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info text-white fw-bold py-3">
                    <i class="bi bi-stars me-2"></i> BẢN DEMO TỪ ADMIN
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-light border mb-4">
                        <strong>Lời nhắn:</strong> <?php echo nl2br(htmlspecialchars($admin_message)); ?>
                    </div>

                    <div class="mb-4 text-center border rounded p-3 bg-light">
                        <img src="<?php echo $admin_demo_img; ?>" class="img-fluid rounded shadow-sm"
                            style="max-height: 400px;">
                    </div>

                    <div class="d-flex justify-content-end gap-3 border-top pt-4">
                        <form method="POST"
                            onsubmit="return confirm('Bạn có chắc chắn không ưng ý và muốn hủy đơn hàng này?');">
                            <input type="hidden" name="action" value="cancel">
                            <button class="btn btn-outline-danger px-4 py-2">Không ưng ý / Hủy</button>
                        </form>

                        <form method="POST">
                            <input type="hidden" name="action" value="approve_pay">
                            <button class="btn btn-success px-4 py-2 fw-bold shadow-sm">
                                <i class="bi bi-check-lg me-1"></i> Ưng ý & Thanh toán
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <?php elseif ($order['status'] == 'waiting_payment'): ?>
            <div class="card border-0 border-primary border-2 shadow-sm p-5 text-center">
                <i class="bi bi-credit-card-2-front text-primary display-1 mb-3"></i>
                <h3 class="fw-bold text-dark-blue">Đơn hàng đang chờ thanh toán</h3>
                <p class="text-muted mb-4">Bạn đã duyệt thiết kế. Vui lòng hoàn tất thanh toán để chúng tôi đăng bài.
                </p>

                <a href="checkout.php?order_id=<?php echo $order_id; ?>" class="btn btn-schedio-primary btn-lg px-5">
                    Tiếp tục thanh toán
                </a>
            </div>

            <?php elseif ($order['status'] == 'completed' || $order['status'] == 'paid'): ?>
            <div class="card border-0 bg-white shadow-sm p-4">
                <div class="text-center mb-4">
                    <i class="bi bi-check-circle-fill text-success display-4"></i>
                    <h3 class="fw-bold text-success mt-2">Đơn hàng hoàn tất!</h3>
                    <p class="text-muted">Bài viết của bạn đã được đăng tải thành công.</p>
                </div>

                <h5 class="fw-bold text-dark-blue mb-3 border-bottom pb-2">Kết quả bài đăng:</h5>

                <?php if (!empty($order['result_links'])): ?>
                <div class="bg-light p-3 rounded">
                    <?php
                            $links = explode("\n", $order['result_links']);
                            foreach ($links as $link):
                                if (trim($link) == '') continue;
                            ?>
                    <div class="mb-2">
                        <a href="<?php echo trim($link); ?>" target="_blank" class="text-decoration-none fw-bold fs-5">
                            <i class="bi bi-link-45deg me-1"></i> Xem bài viết tại đây
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    Admin đang cập nhật link bài đăng. Vui lòng quay lại sau ít phút.
                </div>
                <?php endif; ?>
            </div>

            <?php elseif ($order['status'] == 'cancelled'): ?>
            <div class="card border-0 bg-light p-5 text-center">
                <i class="bi bi-x-circle text-danger display-4 mb-3"></i>
                <h3 class="fw-bold text-danger">Đơn hàng đã bị hủy</h3>
                <p class="text-muted">Nếu bạn muốn đặt lại, vui lòng tạo đơn hàng mới.</p>
                <a href="../services.php" class="btn btn-outline-dark mt-3">Đặt đơn mới</a>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>