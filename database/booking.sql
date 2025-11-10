-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 03, 2025 at 06:40 PM
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
-- Database: `tkht`
--

-- --------------------------------------------------------
-- Table structure for table `orders`

-- --------------------------------------------------------

CREATE TABLE `orders` (
  `id` varchar(10) NOT NULL,
  `user_id` varchar(10) NOT NULL,
  `service_option_id` varchar(10) NOT NULL,
  `title` varchar(255) NOT NULL,
  `product_link` varchar(255) NOT NULL,
  `content_url` varchar(255) NOT NULL, 
  `created_at` datetime DEFAULT current_timestamp(),
  `status` enum('pending','paid','in_progress','completed','cancelled') DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`service_option_id`) REFERENCES `service_option` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Table structure for table `package`
-- --------------------------------------------------------

CREATE TABLE `package` (
  `id` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `slot_count` int(11) DEFAULT 1,
  `pin_duration_days` int(11) DEFAULT 0,
  `cover_duration_days` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `payment`
-- --------------------------------------------------------

CREATE TABLE `payment` (
  `id` varchar(10) NOT NULL,
  `order_id` varchar(10) NOT NULL,
  `method` enum('bank_transfer','momo','vnpay') DEFAULT 'bank_transfer',
  `amount` int(11) NOT NULL,
  `payment_date` datetime DEFAULT current_timestamp(),
  `status` enum('unpaid','paid','failed','refunded') DEFAULT 'unpaid',
  `transaction_ref` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `platform`
-- --------------------------------------------------------

CREATE TABLE `platform` (
  `id` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('page','group') DEFAULT 'page',
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `post`
-- Các post thuộc order_option (không còn thuộc order trực tiếp)
-- --------------------------------------------------------

CREATE TABLE `post` (
  `id` varchar(10) NOT NULL,
  `order_id` varchar(10) NOT NULL,
  `status` enum('pending','scheduled','posted','cancelled') DEFAULT 'pending',
  `is_pinned` tinyint(1) DEFAULT 0,
  `is_cover` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `post_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `promotion` chứa lịch của các pin post và cover
-- --------------------------------------------------------

CREATE TABLE `promotion` (
  `id` varchar(10) NOT NULL,
  `post_id` varchar(10) NOT NULL,
  `platform_id` varchar(10) NOT NULL,
  `promo_type` enum('pinned','cover') NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('active','expired') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_active_cover` (`platform_id`,`promo_type`,`status`),
  KEY `post_id` (`post_id`),
  CONSTRAINT `promotion_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`),
  CONSTRAINT `promotion_ibfk_2` FOREIGN KEY (`platform_id`) REFERENCES `platform` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `schedule`
-- --------------------------------------------------------

CREATE TABLE `schedules` (
  `id` varchar(10) NOT NULL,
  `post_id` varchar(10) NOT NULL,
  `platform_id` varchar(10) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('pending','scheduled','posted','cancelled') DEFAULT 'pending',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_post_slot` (`platform_id`,`start_time`),
  KEY `post_id` (`post_id`),
  CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`),
  CONSTRAINT `schedules_ibfk_2` FOREIGN KEY (`platform_id`) REFERENCES `platform` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `service_option`
-- --------------------------------------------------------

CREATE TABLE `service_option` (
  `id` varchar(10) NOT NULL,
  `package_id` varchar(10) NOT NULL,
  `platform_id` varchar(10) NOT NULL,
  `price` int(11) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `package_id` (`package_id`),
  KEY `platform_id` (`platform_id`),
  CONSTRAINT `service_option_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `package` (`id`),
  CONSTRAINT `service_option_ibfk_2` FOREIGN KEY (`platform_id`) REFERENCES `platform` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

CREATE TABLE `users` (
  `id` varchar(10) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `phonenumber` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
 /*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
 /*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
