<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Ưu tiên lấy thông số từ Vercel, nếu không có thì dùng mặc định của Docker
define('DB_SERVER',   getenv('DB_SERVER')   ?: 'db');
define('DB_USERNAME', getenv('DB_USERNAME') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '123');
define('DB_NAME',     getenv('DB_NAME')     ?: 'booking');

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
