<?php
// Hàm gửi thông báo
function sendNotification($conn, $user_id, $order_id, $title, $message, $type = 'info') {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, order_id, title, message, type) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $user_id, $order_id, $title, $message, $type);
    $stmt->execute();
    $stmt->close();
}
?>