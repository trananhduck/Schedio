-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 11, 2025 at 03:00 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `booking`
--

-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','staff','admin') DEFAULT 'customer',
  `phonenumber` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. TẠO BẢNG PLATFORM (Chỉ 3 kênh)
-- --------------------------------------------------------
CREATE TABLE `platform` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('page','group','tiktok') DEFAULT 'page',
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `platform` (`id`, `name`, `type`, `active`) VALUES
(1, 'PAGE GRAB FAN THÁNG 9', 'page', 1),
(2, 'PAGE RAP FAN THÁM THÍNH', 'page', 1),
(3, 'GROUP CỘNG ĐỒNG GRAB VIỆT UNDERGROUND', 'group', 1);

-- --------------------------------------------------------
-- 4. TẠO BẢNG PACKAGE (Có slug, overview)
-- --------------------------------------------------------
CREATE TABLE `package` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL, -- Dùng cho URL (goi-1a)
  `name` varchar(100) NOT NULL,
  `overview` text DEFAULT NULL, -- Mô tả ngắn
  `description` text DEFAULT NULL, -- Các dòng tính năng (xuống dòng)
  `slot_count` int(11) DEFAULT 1,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `package` (`id`, `slug`, `name`, `overview`, `description`, `slot_count`) VALUES
(1, 'goi-1a', 'Gói 1A', 'Gói cơ bản nhất để ra mắt hình ảnh sản phẩm.', '- 1 poster sản phẩm', 1),
(2, 'goi-1b', 'Gói 1B', 'Gói tập trung vào định dạng video highlight.', '- 1 video highlight', 1),
(3, 'goi-1c', 'Gói 1C', 'Giải pháp chia sẻ link trực tiếp.', '- Share link sản phẩm trực tiếp', 1),
(4, 'goi-2', 'Gói 2', 'Combo hình ảnh và video tiêu chuẩn.', '- 1 poster sản phẩm\r\n- 1 video highlight', 2),
(5, 'goi-3', 'Gói 3', 'Gói phổ biến với đầy đủ định dạng cơ bản.', '- 1 poster sản phẩm\r\n- 1 post trích lyrics highlight\r\n- 1 video highlight', 3),
(6, 'goi-4', 'Gói 4', 'Gói nâng cao với ghim bài và bình luận.', '- 1 poster sản phẩm\r\n- 1 post trích lyrics highlight\r\n- 1 video highlight\r\n- 1 post bình luận về sản phẩm\r\n- 1 tuần ghim bài đăng trên page', 4),
(7, 'goi-5', 'Gói 5', 'Gói toàn diện nhất, bao gồm cả meme và ảnh bìa.', '- 1 poster sản phẩm\r\n- 1 post trích lyrics highlight\r\n- 1 video highlight\r\n- 1 post bình luận về sản phẩm\r\n- 2 bài đăng về tin tức/meme\r\n- 2 tuần ghim bài đăng trên page\r\n- Đặt poster làm ảnh bìa 1 tuần', 6);

-- --------------------------------------------------------
-- 5. TẠO BẢNG SERVICE_OPTION (Giá tiền)
-- --------------------------------------------------------
CREATE TABLE `service_option` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `platform_id` int(11) NOT NULL,
  `price` int(11) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `package_id` (`package_id`),
  KEY `platform_id` (`platform_id`),
  CONSTRAINT `service_option_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `package` (`id`),
  CONSTRAINT `service_option_ibfk_2` FOREIGN KEY (`platform_id`) REFERENCES `platform` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `service_option` (`package_id`, `platform_id`, `price`) VALUES
-- Gói 1A
(1, 1, 180000), (1, 2, 140000), (1, 3, 30000),
-- Gói 1B
(2, 1, 210000), (2, 2, 170000), (2, 3, 30000),
-- Gói 1C
(3, 1, 160000), (3, 2, 100000), (3, 3, 30000),
-- Gói 2
(4, 1, 380000), (4, 2, 250000), (4, 3, 50000),
-- Gói 3
(5, 1, 420000), (5, 2, 300000), (5, 3, 60000),
-- Gói 4
(6, 1, 570000), (6, 2, 450000), (6, 3, 80000),
-- Gói 5
(7, 1, 780000), (7, 2, 640000), (7, 3, 100000);

-- --------------------------------------------------------
-- 6. TẠO BẢNG ORDERS (Cấu trúc workflow mới)
-- --------------------------------------------------------
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `service_option_id` int(11) NOT NULL,
  `price_at_purchase` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `product_link` varchar(255) NOT NULL,
  `content_url` text DEFAULT NULL, -- Link Drive
  `note` text DEFAULT NULL,        -- Ghi chú khách
  
  -- Phần tương tác Admin - Khách
  `admin_feedback_content` text DEFAULT NULL, -- Lời nhắn Admin
  `admin_feedback_files` text DEFAULT NULL,   -- Link ảnh Demo
  `result_links` text DEFAULT NULL,           -- Link bài đăng kết quả
  
  `created_at` datetime DEFAULT current_timestamp(),
  
  -- Trạng thái quy trình
  `status` enum(
      'pending',          -- 1. Mới đặt, chờ thiết kế
      'design_review',    -- 2. Admin gửi demo, chờ khách duyệt
      'waiting_payment',  -- 3. Khách duyệt, chờ thanh toán
      'paid',             -- 4. Đã thanh toán
      'in_progress',      -- 5. Đang đăng bài
      'completed',        -- 6. Hoàn thành
      'cancelled'         -- 0. Hủy
  ) DEFAULT 'pending',
  
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `service_option_id` (`service_option_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`service_option_id`) REFERENCES `service_option` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 7. TẠO BẢNG POST & SCHEDULES
-- --------------------------------------------------------
CREATE TABLE `post` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `status` enum('pending','scheduled','posted','cancelled') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `post_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `platform_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('pending','scheduled','posted','cancelled') DEFAULT 'pending',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_post_slot` (`platform_id`,`start_time`),
  KEY `post_id` (`post_id`),
  KEY `platform_id` (`platform_id`),
  CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`),
  CONSTRAINT `schedules_ibfk_2` FOREIGN KEY (`platform_id`) REFERENCES `platform` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8. TẠO BẢNG PORTFOLIO (Dự án mẫu)
-- --------------------------------------------------------
CREATE TABLE `portfolio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `image_url` varchar(255) NOT NULL,
  `link_url` varchar(255) DEFAULT '#',
  `type` enum('link','pdf') DEFAULT 'link',
  PRIMARY KEY (`id`),
  KEY `package_id` (`package_id`),
  CONSTRAINT `portfolio_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `package` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 9. TẠO BẢNG CONTACTS (Lưu tin nhắn liên hệ)
-- --------------------------------------------------------
CREATE TABLE `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

-- 2. Chèn dữ liệu mẫu
INSERT INTO `portfolio` (`package_id`, `title`, `description`, `image_url`, `link_url`, `type`) VALUES

-- --- Dữ liệu cho Gói 1A (ID: 1) - Thiên về Poster ---
(1, 'Poster MV "Chìm Sâu"', 'Thiết kế Poster Visual Art cho Rapper MCK.', 'https://placehold.co/600x400/191970/ffffff?text=Poster+MCK', '#', 'link'),
(1, 'Banner Sự Kiện Rap Việt', 'Banner truyền thông cho vòng chung kết Rap Việt.', 'https://placehold.co/600x400/fdd03b/333333?text=Banner+Rap+Viet', '#', 'link'),
(1, 'Poster Single "Ghé Qua"', 'Poster phong cách Indie hoài cổ.', 'https://placehold.co/600x400/e74c3c/ffffff?text=Poster+Indie', '#', 'link'),

-- --- Dữ liệu cho Gói 1B (ID: 2) - Thiên về Video ---
(2, 'Video Highlight "Cypher Nhà Làm"', 'Cắt ghép khoảnh khắc ấn tượng nhất của bài nhạc.', 'https://placehold.co/600x400/2c3e50/ffffff?text=Video+Highlight', '#', 'link'),
(2, 'Teaser MV 15s', 'Dựng Teaser ngắn chuẩn format TikTok/Reels.', 'https://placehold.co/600x400/8e44ad/ffffff?text=Teaser+Vertical', '#', 'link'),

-- --- Dữ liệu cho Gói 3 (ID: 5) - Gói phổ biến (Poster + Lyrics + Video) ---
(5, 'Video Lyrics "Mang Tiền Về Cho Mẹ"', 'Video chạy chữ (Kinetic Typography) viral trên Facebook.', 'https://placehold.co/600x400/27ae60/ffffff?text=Video+Lyrics', '#', 'link'),
(5, 'Poster Tour Diễn Xuyên Việt', 'Thiết kế bộ nhận diện cho tour diễn của Đen Vâu.', 'https://placehold.co/600x400/34495e/ffffff?text=Tour+Poster', '#', 'link'),
(5, 'Báo cáo Nghiệm thu Campaign', 'File PDF tổng hợp số liệu tiếp cận sau chiến dịch.', 'https://placehold.co/600x400/bdc3c7/333333?text=Report+PDF', '#', 'pdf'),

-- --- Dữ liệu cho Gói 4 (ID: 6) - Gói nâng cao (Có Seeding/Comment) ---
(6, 'Chiến dịch Seeding "A lôi"', 'Hình ảnh chụp màn hình các thảo luận sôi nổi trong Group.', 'https://placehold.co/600x400/d35400/ffffff?text=Seeding+Proof', '#', 'link'),
(6, 'Video Viral TikTok', 'Video lồng ghép hiệu ứng trending.', 'https://placehold.co/600x400/000000/ffffff?text=TikTok+Viral', '#', 'link'),

-- --- Dữ liệu cho Gói 5 (ID: 7) - Gói VIP (Meme + Cover) ---
(7, 'Meme Chế "Flex đến hơi thở cuối cùng"', 'Content Meme hài hước xoay quanh bài hát mới.', 'https://placehold.co/600x400/f39c12/ffffff?text=Viral+Meme', '#', 'link'),
(7, 'Ảnh Bìa Fanpage Độc Quyền', 'Thiết kế Cover Page đồng bộ với concept MV.', 'https://placehold.co/600x400/16a085/ffffff?text=Page+Cover', '#', 'link'),
(7, 'Báo cáo Tổng kết Chiến dịch VIP', 'Phân tích chuyên sâu hiệu quả truyền thông.', 'https://placehold.co/600x400/95a5a6/ffffff?text=Full+Report', '#', 'pdf');