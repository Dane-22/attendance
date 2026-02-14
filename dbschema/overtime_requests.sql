-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 14, 2026 at 07:16 AM
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
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `overtime_requests`
--

INSERT INTO `overtime_requests` (`id`, `employee_id`, `branch_name`, `request_date`, `requested_hours`, `overtime_reason`, `status`, `requested_by`, `requested_by_user_id`, `requested_at`, `approved_by`, `approved_at`, `rejection_reason`, `attendance_id`) VALUES
(7, 24, 'BCDA - Admin', '2026-02-14', 5.00, 'test', 'approved', 'KELVIN CALDERON', 63, '2026-02-14 01:36:43', 'Admin', '2026-02-14 01:37:00', NULL, 1061);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
