-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 25, 2026 at 12:00 AM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `attendance_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `overtime_requests`
--

DROP TABLE IF EXISTS `overtime_requests`;
CREATE TABLE IF NOT EXISTS `overtime_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `branch_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_date` date NOT NULL,
  `requested_hours` decimal(5,2) NOT NULL,
  `overtime_reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved','rejected','pre-approved') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `requested_by` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `requested_by_user_id` int DEFAULT NULL,
  `requested_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `attendance_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_employee_date` (`employee_id`,`request_date`),
  KEY `idx_status` (`status`),
  KEY `idx_requested_at` (`requested_at`),
  KEY `idx_requested_by_user` (`requested_by_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `overtime_requests`
--

INSERT INTO `overtime_requests` (`id`, `employee_id`, `branch_name`, `request_date`, `requested_hours`, `overtime_reason`, `status`, `requested_by`, `requested_by_user_id`, `requested_at`, `approved_by`, `approved_at`, `rejection_reason`, `attendance_id`) VALUES
(10, 24, 'BCDA - Admin', '2026-02-24', 5.00, 'asdf', 'pending', 'KELVIN CALDERON', 68, '2026-02-24 07:16:36', 'Admin', '2026-02-24 07:18:32', NULL, NULL),
(11, 24, 'BCDA - Admin', '2026-02-24', 4.00, 'ftyughjmn', 'pending', 'KELVIN CALDERON', 68, '2026-02-24 08:55:04', 'Admin', '2026-02-24 08:55:17', NULL, NULL),
(12, 24, 'BCDA - Admin', '2026-02-24', 45.00, 'yuhvjkm', 'pending', 'KELVIN CALDERON', 68, '2026-02-24 08:58:51', 'Admin', '2026-02-24 08:59:07', NULL, NULL),
(13, 24, 'BCDA - Admin', '2026-02-25', 2.00, 'adsgf', 'pending', 'KELVIN CALDERON', 68, '2026-02-24 23:49:21', 'Admin', '2026-02-24 23:49:43', NULL, NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
