<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];
$info = $_POST['common_info'];
$packages = $_POST['packages'];

$conn->begin_transaction();

try {
    foreach ($packages as $pkg) {
        $pkg_name = $pkg['name'];
        $plt_name = $pkg['platform'];
        $slots = $pkg['slots'];

        // 1. Tìm ID Package
        $s1 = $conn->prepare("SELECT id FROM package WHERE name = ? LIMIT 1");
        $s1->bind_param("s", $pkg_name);
        $s1->execute();
        $pid = $s1->get_result()->fetch_assoc()['id'];
        $s1->close();

        // 2. Tìm ID Platform
        $s2 = $conn->prepare("SELECT id FROM platform WHERE name = ? LIMIT 1");
        $s2->bind_param("s", $plt_name);
        $s2->execute();
        $plid = $s2->get_result()->fetch_assoc()['id'];
        $s2->close();

        // 3. Lấy Giá
        $s3 = $conn->prepare("SELECT id, price FROM service_option WHERE package_id=? AND platform_id=? LIMIT 1");
        $s3->bind_param("ii", $pid, $plid);
        $s3->execute();
        $opt = $s3->get_result()->fetch_assoc();
        $s3->close();

        // 4. Tạo Order
        $stmt = $conn->prepare("INSERT INTO orders (user_id, service_option_id, price_at_purchase, title, product_link, content_url, note, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("iiissss", $user_id, $opt['id'], $opt['price'], $info['title'], $info['prod'], $info['drive'], $info['note']);
        $stmt->execute();
        $order_id = $conn->insert_id;
        $stmt->close();

        // 5. Tạo Slots
        foreach ($slots as $iso) {
            // Tạo Post
            $conn->query("INSERT INTO post (order_id, status) VALUES ($order_id, 'pending')");
            $post_id = $conn->insert_id;

            // Tạo Schedule
            $start = date('Y-m-d H:i:s', strtotime($iso));
            $end = date('Y-m-d H:i:s', strtotime($iso) + 3600);
            $s4 = $conn->prepare("INSERT INTO schedules (post_id, platform_id, start_time, end_time, status) VALUES (?, ?, ?, ?, 'pending')");
            $s4->bind_param("iiss", $post_id, $plid, $start, $end);
            if (!$s4->execute()) {
                throw new Exception("Trùng lịch tại $start ($plt_name)");
            }
            $s4->close();
        }
    }

    // Xóa giỏ hàng sau khi đặt xong
    unset($_SESSION['cart']);

    $conn->commit();
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}