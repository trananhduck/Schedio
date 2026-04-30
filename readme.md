# 🎵 Schedio - Automated Social Media Booking System

> Schedio là hệ thống web tự động hóa quy trình đặt lịch truyền thông (Booking) trên các nền tảng mạng xã hội (Fanpage, Group, TikTok). Dự án giúp chuẩn hóa quy trình từ khâu tư vấn, đặt lịch, thanh toán đến nghiệm thu nội dung.

---

## 🌟 Tính năng nổi bật (Key Features)

### 👥 Dành cho Khách hàng (Customer)
- **Visual Booking:** Lựa chọn gói dịch vụ và chọn khung giờ trống trực quan trên lịch theo thời gian thực.
- **Thanh toán tự động:** Tích hợp sinh mã VietQR động theo đơn hàng.
- **Duyệt Demo 2 chiều:** Nhận thông báo duyệt bài mẫu, yêu cầu chỉnh sửa hoặc phê duyệt trực tiếp trên hệ thống.
- **AI Chatbot (Gemini):** Trợ lý ảo AI tư vấn báo giá và gợi ý gói dịch vụ 24/7.
- **Bảo mật cao:** Xác thực phiên làm việc an toàn, chặn đứng các thủ thuật gian lận (Parameter Manipulation).

### 🛡️ Dành cho Quản trị viên (Admin)
- **Dashboard Thống kê:** Theo dõi doanh thu và hiệu suất bài đăng.
- **Quản lý Đơn hàng (Order Processing):** Theo dõi trạng thái đơn từ lúc Pending đến khi Hoàn tất.
- **Quản lý Tài nguyên:** Upload demo, cập nhật link kết quả đăng bài.

---

## 🛠️ Công nghệ sử dụng (Tech Stack)

- **Frontend:** HTML, CSS, JavaScript (Vanilla), UI mang phong cách Hiphop/Underground.
- **Backend:** PHP 8.2 (Native).
- **Database:** MySQL/MariaDB 10.4.
- **Security:** JSON Web Token (JWT) cho API, Server-side Validation (Business Logic Defense).
- **Infrastructure:** Docker & Docker Compose.
- **Third-party Services:** Google Gemini API, VietQR API.

---

## 📁 Cấu trúc thư mục (Directory Structure)

Dự án được phân tách thành các module chức năng độc lập:
```text
Schedio/
├── admin/          # Module xử lý nghiệp vụ cho Quản trị viên (Duyệt đơn, thống kê)
├── api/            # Các endpoint API (Stateless) xác thực qua JWT
├── assets/         # Tài nguyên tĩnh (CSS, JS, Fonts, Images)
├── config/         # Cấu hình hệ thống (Kết nối Database, JWT Secret)
├── customer/       # Module chức năng dành cho khách hàng (Lịch sử đơn, Profile)
├── database/       # Chứa script SQL khởi tạo (11 bảng) và dữ liệu mẫu
├── templates/      # Các thành phần giao diện dùng chung (Header, Footer, Sidebar)
├── uploads/        # Lưu trữ file upload (Avatar, file Demo MP3/MP4, Hình ảnh)
├── index.php       # Trang chủ & Landing Page
├── login.php       # Cổng đăng nhập hệ thống
└── ...
```

--- 

## 🚀 Hướng dẫn Cài đặt & Vận hành bằng Docker
Dự án đã được đóng gói sẵn bằng Docker, giúp quá trình triển khai diễn ra nhanh chóng và đồng nhất trên mọi thiết bị.

**1. Yêu cầu hệ thống (Prerequisites)**
- Máy tính đã cài đặt Docker và Docker Compose.
- Tắt các dịch vụ web/database cục bộ (như XAMPP, WAMP) để tránh xung đột Port (8080, 3306).

**2. Các bước triển khai**
**Bước 1**: Clone dự án về máy

git clone [https://github.com/trananhduck/Schedio.git](https://github.com/trananhduck/Schedio.git)
cd Schedio

**Bước 2:** Build và khởi chạy các Container

**docker-compose up -d --build**

Hệ thống sẽ khởi tạo 3 dịch vụ: Web Server (PHP), Database (MariaDB), và phpMyAdmin.

**Bước 3:** Nạp cơ sở dữ liệu

- Truy cập công cụ quản trị CSDL tại: http://localhost:8081 (User: root / Pass: 123)
Import tệp SQL từ thư mục database/ vào database có tên booking.

**Bước 4:** Truy cập hệ thống

- Trang khách hàng: http://localhost:8080
- Trang quản trị: http://localhost:8080/admin
(Phải đăng ký tài khoản admin bằng cách truy cập http://localhost:8080/admin/register.php)