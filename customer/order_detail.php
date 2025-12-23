<?php
session_start();
require_once '../config/db.php';

// 1. KIỂM TRA ĐĂNG NHẬP & QUYỀN CUSTOMER (Đã sửa)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'customer') {
    // Redirect ra ngoài thư mục customer để về trang login gốc
    header("Location: ../login.php"); 
    exit;
}

$user_id = $_SESSION['user_id'];

// 2. KIỂM TRA ID ĐƠN HÀNG
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Không tìm thấy ID đơn hàng.");
}
$order_id = intval($_GET['id']);

// 3. TRUY VẤN CHI TIẾT ĐƠN HÀNG (Bảo mật: Chỉ lấy đơn của user hiện tại)
$sql = "
    SELECT o.*, 
           p.name AS package_name, 
           pl.name AS platform_name,
           (SELECT start_time FROM schedules WHERE post_id IN (SELECT id FROM post WHERE order_id = o.id) ORDER BY start_time ASC LIMIT 1) as schedule_time
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
    // Nếu không tìm thấy hoặc đơn này không phải của khách này
    echo "<script>alert('Đơn hàng không tồn tại hoặc bạn không có quyền xem!'); window.location.href='account.php?tab=orders';</script>";
    exit;
}

// 4. XỬ LÝ: KHÁCH HÀNG PHẢN HỒI (DUYỆT DEMO / YÊU CẦU SỬA)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['customer_action'])) {
    $action = $_POST['customer_action'];
    
    if ($action == 'approve_demo') {
        // Khách duyệt -> Chuyển sang chờ thanh toán
        $stmt = $conn->prepare("UPDATE orders SET status = 'waiting_payment' WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        echo "<script>alert('Đã xác nhận Demo! Vui lòng tiến hành thanh toán.'); window.location.reload();</script>";
    } 
    elseif ($action == 'request_fix') {
        // Khách yêu cầu sửa -> Quay lại Pending (hoặc trạng thái design)
        $fix_note = trim($_POST['fix_note']);
        // Lưu ý: Logic thực tế có thể cần thêm bảng log feedback, ở đây demo cập nhật vào note
        $new_note = $order['note'] . "\n[Khách yêu cầu sửa " . date('d/m') . "]: " . $fix_note;
        
        $stmt = $conn->prepare("UPDATE orders SET status = 'pending', note = ? WHERE id = ?");
        $stmt->bind_param("si", $new_note, $order_id);
        $stmt->execute();
        echo "<script>alert('Đã gửi yêu cầu chỉnh sửa cho Admin.'); window.location.reload();</script>";
    }
}

// Format dữ liệu hiển thị
$order_code = 'SCD-' . str_pad($order['id'], 3, '0', STR_PAD_LEFT);
$formatted_price = number_format($order['price_at_purchase'], 0, ',', '.') . ' đ';
$formatted_date = date('d/m/Y H:i', strtotime($order['created_at']));
$formatted_schedule = $order['schedule_time'] ? date('d/m/Y H:i', strtotime($order['schedule_time'])) : '<span class="text-muted">Chưa xếp lịch</span>';

include '../templates/header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="account.php?tab=orders" class="text-decoration-none text-muted small"><i
                    class="bi bi-arrow-left"></i> Quay lại danh sách</a>
            <h2 class="text-dark-blue fw-bold mb-0 mt-1">Chi tiết đơn hàng #<?php echo $order_code; ?></h2>
        </div>

        <?php
        $status_labels = [
            'pending' => ['Chờ xử lý', 'warning'],
            'design_review' => ['Duyệt Demo', 'info'],
            'waiting_payment' => ['Chờ thanh toán', 'primary'],
            'paid' => ['Đã thanh toán', 'success'],
            'in_progress' => ['Đang thực hiện', 'primary'],
            'completed' => ['Hoàn thành', 'success'],
            'cancelled' => ['Đã hủy', 'danger']
        ];
        $stt = $status_labels[$order['status']] ?? ['Không xác định', 'secondary'];
        ?>
        <span class="badge bg-<?php echo $stt[1]; ?> fs-5 px-4 py-2 rounded-pill"><?php echo $stt[0]; ?></span>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold py-3">Thông tin dịch vụ</div>
                <div class="card-body">
                    <p><strong>Gói dịch vụ:</strong> <?php echo htmlspecialchars($order['package_name']); ?></p>
                    <p><strong>Kênh truyền thông:</strong> <?php echo htmlspecialchars($order['platform_name']); ?></p>
                    <p><strong>Giá tiền:</strong> <span
                            class="fw-bold text-primary"><?php echo $formatted_price; ?></span></p>
                    <p><strong>Lịch đăng (dự kiến):</strong>
                        <span class="fw-bold text-danger"><?php echo $formatted_schedule; ?></span>
                    </p>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold py-3">Nội dung yêu cầu</div>
                <div class="card-body">
                    <p><strong>Tiêu đề:</strong> <?php echo htmlspecialchars($order['title']); ?></p>
                    <p><strong>Link Source (Drive):</strong> <a href="<?php echo $order['content_url']; ?>"
                            target="_blank" class="text-break"><?php echo $order['content_url']; ?></a></p>
                    <p><strong>Link Sản phẩm:</strong> <a href="<?php echo $order['product_link']; ?>" target="_blank"
                            class="text-break"><?php echo $order['product_link']; ?></a></p>
                    <p><strong>Ghi chú:</strong> <br>
                        <?php echo !empty($order['note']) ? nl2br(htmlspecialchars($order['note'])) : '<span class="text-muted fst-italic">Không có</span>'; ?>
                    </p>
                </div>
            </div>

            <?php if($order['status'] == 'design_review'): ?>
            <div class="card border-0 shadow-sm mb-4 border-info">
                <div class="card-header bg-info text-white fw-bold py-3">
                    <i class="bi bi-palette-fill me-2"></i> Duyệt thiết kế (Demo)
                </div>
                <div class="card-body text-center">
                    <h5 class="fw-bold mb-3">Admin đã gửi bản demo cho bạn:</h5>

                    <?php if(!empty($order['admin_feedback_files'])): ?>
                    <div class="mb-3">
                        <img src="<?php echo $order['admin_feedback_files']; ?>"
                            class="img-fluid rounded shadow-sm border" style="max-height: 400px;">
                    </div>
                    <?php endif; ?>

                    <p class="fst-italic text-muted">"<?php echo htmlspecialchars($order['admin_feedback_content']); ?>"
                    </p>

                    <hr>
                    <div class="d-flex justify-content-center gap-3 mt-4">
                        <button class="btn btn-outline-danger px-4" type="button" data-bs-toggle="collapse"
                            data-bs-target="#fixForm">
                            <i class="bi bi-pencil"></i> Yêu cầu sửa
                        </button>
                        <form method="POST">
                            <input type="hidden" name="customer_action" value="approve_demo">
                            <button type="submit" class="btn btn-success px-4"
                                onclick="return confirm('Bạn xác nhận ưng ý với bản thiết kế này?');">
                                <i class="bi bi-check-lg"></i> Duyệt & Thanh toán
                            </button>
                        </form>
                    </div>

                    <div class="collapse mt-3" id="fixForm">
                        <form method="POST" class="text-start bg-light p-3 rounded">
                            <input type="hidden" name="customer_action" value="request_fix">
                            <label class="form-label fw-bold">Nhập yêu cầu chỉnh sửa:</label>
                            <textarea name="fix_note" class="form-control mb-2" rows="3" required
                                placeholder="Ví dụ: Đổi màu chữ sang màu đỏ, làm logo to hơn..."></textarea>
                            <button type="submit" class="btn btn-primary btn-sm">Gửi yêu cầu</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if($order['status'] == 'completed'): ?>
            <div class="card border-0 shadow-sm mb-4 border-success">
                <div class="card-header bg-success text-white fw-bold py-3">
                    <i class="bi bi-star-fill me-2"></i> Kết quả nghiệm thu
                </div>
                <div class="card-body text-center p-5">
                    <h4 class="text-success fw-bold">Chiến dịch đã hoàn thành!</h4>
                    <p>Bài viết của bạn đã được đăng tải thành công.</p>
                    <a href="<?php echo htmlspecialchars($order['result_links']); ?>" target="_blank"
                        class="btn btn-outline-success btn-lg mt-2">
                        <i class="bi bi-facebook me-2"></i> Xem bài đăng kết quả
                    </a>

                    <?php if(isset($order['fb_likes'])): ?>
                    <div class="row mt-4 pt-3 border-top">
                        <div class="col-4"><strong><?php echo number_format($order['fb_likes']); ?></strong> <br><small
                                class="text-muted">Likes</small></div>
                        <div class="col-4"><strong><?php echo number_format($order['fb_comments']); ?></strong>
                            <br><small class="text-muted">Comments</small>
                        </div>
                        <div class="col-4"><strong><?php echo number_format($order['fb_shares']); ?></strong> <br><small
                                class="text-muted">Shares</small></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <div class="col-lg-4">
            <?php if($order['status'] == 'waiting_payment'): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white fw-bold py-3 text-center">THANH TOÁN</div>
                <div class="card-body text-center">
                    <p class="text-muted small">Vui lòng quét mã QR bên dưới để thanh toán.</p>
                    <?php 
                        $bank_id = 'MB'; 
                        $account_no = '0344377104';
                        $account_name = 'TRAN ANH DUC';
                        $amount = $order['price_at_purchase'];
                        $memo = "SCD" . $order['id']; // Nội dung CK ngắn gọn
                        // API VietQR
                        $qr_url = "https://img.vietqr.io/image/$bank_id-$account_no-compact2.png?amount=$amount&addInfo=$memo&accountName=".urlencode($account_name);
                    ?>
                    <img src="<?php echo $qr_url; ?>" class="img-fluid mb-3 border rounded" style="width: 200px;">

                    <h5 class="fw-bold text-primary"><?php echo $formatted_price; ?></h5>
                    <div class="alert alert-warning small text-start mt-3">
                        <i class="bi bi-info-circle"></i> <strong>Lưu ý:</strong>
                        <br>- Nội dung CK: <b><?php echo $memo; ?></b>
                        <br>- Hệ thống sẽ tự động xác nhận sau khi Admin kiểm tra biến động số dư.
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold">Cần hỗ trợ?</h6>
                    <p class="small text-muted mb-3">Nếu có vấn đề gì về đơn hàng, vui lòng liên hệ hotline.</p>
                    <a href="tel:084123456789" class="btn btn-light w-100 border fw-bold"><i
                            class="bi bi-telephone-fill me-2"></i> 084 123 456 789</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>