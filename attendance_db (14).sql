-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 06, 2026 at 01:24 AM
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
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(58, 6, 'Logged In', 'User Super Admin logged in from branch: Main Branch', '::1', '2026-01-31 07:57:51'),
(59, 6, 'Logged In', 'User Super Admin logged in from branch: BCDA', '::1', '2026-02-02 00:36:47'),
(60, 6, 'Logged In', 'User Super Admin logged in from branch: Main Branch', '::1', '2026-02-03 00:04:19'),
(61, 112, 'Logged In', 'User Super Account logged in from branch: STO. Rosario', '::1', '2026-02-03 07:49:38'),
(62, 17, 'Logged In', 'User ROLLY BALTAZAR logged in from branch: Main Branch', '::1', '2026-02-03 07:50:08'),
(63, 112, 'Logged In', 'User Super Account logged in from branch: BCDA', '::1', '2026-02-03 07:50:28'),
(64, 112, 'Logged In', 'User Super Account logged in from branch: Main Branch', '::1', '2026-02-03 08:11:09'),
(65, 6, 'Logged In', 'User Super Adminesu logged in from branch: STO. Rosario', '::1', '2026-02-03 08:22:02'),
(66, 6, 'Logged In', 'User Super Adminesu logged in from branch: BCDA', '::1', '2026-02-05 00:15:45'),
(67, 17, 'Logged In', 'User ROLLY BALTAZAR logged in from branch: BCDA', '::1', '2026-02-05 02:11:21'),
(68, 6, 'Logged In', 'User Super Adminesu logged in from branch: BCDA', '::1', '2026-02-05 02:29:25'),
(69, 17, 'Logged In', 'User ROLLY BALTAZAR logged in from branch: BCDA', '::1', '2026-02-05 02:32:56'),
(70, 17, 'Logged In', 'User ROLLY BALTAZAR logged in from branch: STO. Rosario', '::1', '2026-02-06 00:26:16'),
(71, 6, 'Logged In', 'User Super Adminesu logged in from branch: STO. Rosario', '::1', '2026-02-06 01:00:23');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `status` enum('Present','Late','Absent','System') DEFAULT NULL,
  `branch_name` varchar(50) NOT NULL,
  `attendance_date` date NOT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_auto_absent` tinyint(1) DEFAULT '0',
  `auto_absent_applied` tinyint(1) DEFAULT '0',
  `absent_notes` text,
  `is_time_running` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=752 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_id`, `status`, `branch_name`, `attendance_date`, `time_in`, `time_out`, `created_at`, `updated_at`, `is_auto_absent`, `auto_absent_applied`, `absent_notes`, `is_time_running`) VALUES
(751, 6, 'Present', 'STO. Rosario', '2026-02-06', NULL, NULL, '2026-02-06 01:00:23', NULL, 0, 0, NULL, 0),
(734, 12, NULL, 'Testing', '2026-02-05', '2026-02-05 11:52:57', '2026-02-05 15:14:26', '2026-02-05 03:52:57', '2026-02-05 07:13:44', 0, 0, NULL, 0),
(735, 61, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 12:00:17', '2026-02-05 15:50:47', '2026-02-05 04:00:17', NULL, 0, 0, NULL, 0),
(750, 17, 'Present', 'STO. Rosario', '2026-02-06', NULL, NULL, '2026-02-06 00:26:16', NULL, 0, 0, NULL, 0),
(749, 112, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 15:50:14', '2026-02-05 15:56:03', '2026-02-05 07:50:14', NULL, 0, 0, NULL, 0),
(748, 12, NULL, 'Testing', '2026-02-05', '2026-02-05 15:14:30', NULL, '2026-02-05 07:14:30', NULL, 0, 0, NULL, 1),
(747, 19, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 15:11:25', '2026-02-05 15:11:33', '2026-02-05 07:11:25', NULL, 0, 0, NULL, 0),
(746, 112, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 14:11:55', '2026-02-05 14:12:02', '2026-02-05 06:11:55', NULL, 0, 0, NULL, 0),
(745, 63, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 14:11:15', '2026-02-05 14:11:20', '2026-02-05 06:11:15', NULL, 0, 0, NULL, 0),
(744, 73, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 14:03:11', '2026-02-05 14:03:15', '2026-02-05 06:03:11', NULL, 0, 0, NULL, 0),
(743, 14, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 14:02:18', '2026-02-05 14:02:29', '2026-02-05 06:02:18', NULL, 0, 0, NULL, 0),
(742, 13, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 14:01:00', '2026-02-05 14:01:21', '2026-02-05 06:01:00', NULL, 0, 0, NULL, 0),
(741, 13, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 14:00:24', '2026-02-05 14:00:30', '2026-02-05 06:00:24', NULL, 0, 0, NULL, 0),
(740, 62, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 13:59:52', '2026-02-05 13:59:55', '2026-02-05 05:59:52', NULL, 0, 0, NULL, 0),
(739, 111, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 13:51:55', '2026-02-05 13:52:02', '2026-02-05 05:51:55', NULL, 0, 0, NULL, 0),
(738, 112, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 13:59:40', '2026-02-05 13:59:41', '2026-02-05 05:35:55', NULL, 0, 0, NULL, 0),
(737, 16, NULL, 'Testing', '2026-02-05', '2026-02-05 13:23:15', NULL, '2026-02-05 05:23:15', NULL, 0, 0, NULL, 1),
(736, 15, NULL, 'Testing', '2026-02-05', '2026-02-05 12:03:15', NULL, '2026-02-05 04:03:15', NULL, 0, 0, NULL, 1),
(733, 6, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 11:52:09', '2026-02-05 14:11:58', '2026-02-05 03:52:09', '2026-02-05 04:02:44', 0, 0, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
CREATE TABLE IF NOT EXISTS `branches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `branch_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_name` (`branch_name`),
  KEY `idx_branch_name` (`branch_name`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=MyISAM AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `branch_name`, `created_at`, `is_active`) VALUES
(23, 'BCDA - Fence', '2026-02-06 01:01:29', 1),
(22, 'BCDA - Control Tower', '2026-02-06 01:01:11', 1),
(21, 'BCDA - Admin', '2026-02-06 01:00:59', 1),
(10, 'Sto. Rosario', '2026-01-29 03:19:23', 1),
(20, 'BCDA - CCA', '2026-02-06 01:00:44', 1),
(24, 'BCDA - Fire Station', '2026-02-06 01:01:46', 1),
(25, 'BCDA - CCTV', '2026-02-06 01:01:55', 1),
(26, 'Panicsican', '2026-02-06 01:02:07', 1),
(27, 'Dallangayan', '2026-02-06 01:02:16', 1),
(28, 'Pias - Sundara', '2026-02-06 01:02:25', 1),
(29, 'Pias - Office', '2026-02-06 01:02:33', 1),
(30, 'Capitol - Roadwork', '2026-02-06 01:02:59', 1),
(31, 'Capitol - Accounting', '2026-02-06 01:03:08', 1);

-- --------------------------------------------------------

--
-- Table structure for table `branch_reset_log`
--

DROP TABLE IF EXISTS `branch_reset_log`;
CREATE TABLE IF NOT EXISTS `branch_reset_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reset_date` date NOT NULL,
  `employees_affected` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reset_date` (`reset_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
CREATE TABLE IF NOT EXISTS `documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `upload_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_doc_type` (`employee_id`,`document_type`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `employee_id`, `document_name`, `document_type`, `category`, `file_path`, `upload_date`) VALUES
(2, 12, '97db62b8-c665-46f5-b4cb-2bed698b5b25.jpeg', 'tin', 'image', '../uploads/12_20260124020640_97db62b8-c665-46f5-b4cb-2bed698b5b25.jpeg', '2026-01-24 02:06:40'),
(3, 12, '12_20260124020554_97db62b8-c665-46f5-b4cb-2bed698b5b25.jpeg', 'philhealth', 'image', '../uploads/12_20260124035218_12_20260124020554_97db62b8-c665-46f5-b4cb-2bed698b5b25.jpeg', '2026-01-24 03:52:18'),
(4, 14, '615345752_3817950261843285_1368667470747170934_n.jpg', 'employment_certificate', 'image', '../uploads/14_20260124081600_615345752_3817950261843285_1368667470747170934_n.jpg', '2026-01-24 08:16:00'),
(5, 14, '97db62b8-c665-46f5-b4cb-2bed698b5b25.jpeg', 'tin', 'image', '../uploads/14_20260124081609_97db62b8-c665-46f5-b4cb-2bed698b5b25.jpeg', '2026-01-24 08:16:09'),
(6, 61, 'WhatsApp Image 2026-02-02 at 8.14.05 AM.jpeg', 'tin', 'image', '../uploads/61_20260202040545_WhatsAppImage2026-02-02at8.14.05AM.jpeg', '2026-02-02 04:05:45');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
CREATE TABLE IF NOT EXISTS `employees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_code` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `position` varchar(50) DEFAULT 'Employee',
  `status` varchar(50) DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `profile_image` varchar(255) DEFAULT NULL,
  `daily_rate` decimal(10,2) DEFAULT '600.00',
  `branch_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_code` (`employee_code`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_employees_branch` (`branch_id`)
) ENGINE=MyISAM AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_code`, `first_name`, `middle_name`, `last_name`, `email`, `password_hash`, `position`, `status`, `created_at`, `updated_at`, `profile_image`, `daily_rate`, `branch_id`) VALUES
(16, 'E0006', 'ALFREDO', NULL, 'BAGUIO', 'alfredo.baguio@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 550.00, 20),
(17, 'E0007', 'ROLLY', NULL, 'BALTAZAR', 'rolly.baltazar@example.com', '$2y$10$4/nX3PsxAeYnik1fwh7lxO3XJHlW.IiOjK5NZPDCDD9eXoCBMVp8K', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 500.00, 20),
(18, 'E0008', 'DONG', NULL, 'BAUTISTA', 'dong.bautista@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 20),
(14, 'E0004', 'NOEL', NULL, 'ARIZ', 'noel.ariz@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:13:14', NULL, 550.00, 20),
(6, 'SA001', 'Super', 'Duper', 'Adminesu', 'admin@jajrconstruction.com', '$2y$10$RSHOb3hskFZueMLlCycFuua/4EwcxGmAIzpcl8ixQpEXY3tfu9LYi', 'Super Admin', 'Active', '2026-01-16 02:26:58', '2026-02-06 01:17:06', 'profile_697d9f9a1f47a8.96968556.png', 600.00, 31),
(15, 'E0005', 'DANIEL', NULL, 'BACHILLER', 'daniel.bachiller@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 20),
(11, 'E0001', 'AARIZ', NULL, 'MARLOU', 'aariz.marlou@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:17:06', NULL, 600.00, 31),
(12, 'E0002', 'CESAR', NULL, 'ABUBO', 'cesar.abubo@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:17:06', 'profile_697d962d450256.84780797.png', 600.00, 31),
(13, 'E0003', 'MARLON', NULL, 'AGUILAR', 'marlon.aguilar@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:17:06', NULL, 600.00, 31),
(19, 'E0009', 'JANLY', NULL, 'BELINO', 'janly.belino@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 20),
(20, 'E0010', 'MENUEL', NULL, 'BENITEZ', 'menuel.benitez@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 22),
(21, 'E0011', 'GELMAR', NULL, 'BERNACHEA', 'gelmar.bernachea@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:13:14', NULL, 600.00, 22),
(22, 'E0012', 'JOMAR', NULL, 'CABANBAN', 'jomar.cabanban@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 22),
(23, 'E0013', 'MARIO', NULL, 'CABANBAN', 'mario.cabanban@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 22),
(24, 'E0014', 'KELVIN', NULL, 'CALDERON', 'kelvin.calderon@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 500.00, 22),
(25, 'E0015', 'FLORANTE', NULL, 'CALUZA', 'florante.caluza@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 22),
(26, 'E0016', 'MELVIN', NULL, 'CAMPOS', 'melvin.campos@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 23),
(27, 'E0017', 'JERWIN', NULL, 'CAMPOS', 'jerwin.campos@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 23),
(28, 'E0018', 'BENJIE', NULL, 'CARAS', 'benjie.caras@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:13:14', NULL, 700.00, 23),
(29, 'E0019', 'BONJO', NULL, 'DACUMOS', 'bonjo.dacumos@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 23),
(30, 'E0020', 'RYAN', NULL, 'DEOCARIS', 'ryan.deocaris@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 500.00, 23),
(31, 'E0021', 'BEN', NULL, 'ESTEPA', 'ben.estepa@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 23),
(32, 'E0022', 'MAR DAVE', NULL, 'FLORES', 'mardave.flores@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 550.00, 24),
(33, 'E0023', 'ALBERT', NULL, 'FONTANILLA', 'albert.fontanilla@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 24),
(34, 'E0024', 'JOHN WILSON', NULL, 'FONTANILLA', 'johnwilson.fontanilla@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 24),
(35, 'E0025', 'LEO', NULL, 'GURTIZA', 'leo.gurtiza@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:13:14', NULL, 600.00, 24),
(36, 'E0026', 'JOSE', NULL, 'IGLECIAS', 'jose.iglecias@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 500.00, 24),
(37, 'E0027', 'JEFFREY', NULL, 'JIMENEZ', 'jeffrey.jimenez@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 550.00, 24),
(38, 'E0028', 'WILSON', NULL, 'LICTAOA', 'wilson.lictaoa@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 500.00, 25),
(39, 'E0029', 'LORETO', NULL, 'MABALO', 'loreto.mabalo@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 25),
(40, 'E0030', 'ROMEL', NULL, 'MALLARE', 'romel.mallare@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 25),
(41, 'E0031', 'SAMUEL SR.', NULL, 'MARQUEZ', 'samuel.marquez@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 500.00, 25),
(42, 'E0032', 'ROLLY', NULL, 'MARZAN', 'rolly.marzan@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:13:14', NULL, 600.00, 25),
(43, 'E0033', 'RONALD', NULL, 'MARZAN', 'ronald.marzan@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 25),
(44, 'E0034', 'WILSON', NULL, 'MARZAN', 'wilson.marzan@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 10),
(45, 'E0035', 'MARVIN', NULL, 'MIRANDA', 'marvin.miranda@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 10),
(46, 'E0036', 'JOE', NULL, 'MONTERDE', 'joe.monterde@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 700.00, 10),
(47, 'E0037', 'ALDRED', NULL, 'NATARTE', 'aldred.natarte@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 10),
(48, 'E0038', 'ARNOLD', NULL, 'NERIDO', 'arnold.nerido@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 10),
(49, 'E0039', 'RONEL', NULL, 'NOSES', 'ronel.noses@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 10),
(50, 'E0040', 'DANNY', NULL, 'PADILLA', 'danny.padilla@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 500.00, 26),
(51, 'E0041', 'EDGAR', NULL, 'PANEDA', 'edgar.paneda@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 550.00, 26),
(52, 'E0042', 'JEREMY', NULL, 'PIMENTEL', 'jeremy.pimentel@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 550.00, 26),
(53, 'E0043', 'MIGUEL', NULL, 'PREPOSI', 'miguel.preposi@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 26),
(54, 'E0044', 'JUN', NULL, 'ROAQUIN', 'jun.roaquin@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 26),
(55, 'E0045', 'RICKMAR', NULL, 'SANTOS', 'rickmar.santos@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:17:06', NULL, 600.00, 27),
(56, 'E0046', 'RIO', NULL, 'SILOY', 'rio.siloy@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 27),
(57, 'E0047', 'NORMAN', NULL, 'TARAPE', 'norman.tarape@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 27),
(58, 'E0048', 'HILMAR', NULL, 'TATUNAY', 'hilmar.tatunay@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 27),
(59, 'E0049', 'KENNETH JOHN', NULL, 'UGAS', 'kennethjohn.ugas@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 27),
(60, 'E0050', 'CLYDE JUSTINE', NULL, 'VASADRE', 'clydejustine.vasadre@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:17:06', NULL, 500.00, 28),
(61, 'E0051', 'CARL JHUNELL', NULL, 'ACAS', 'carljhunell.acas@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:17:06', 'profile_697d9f9350dad3.88439854.png', 600.00, 28),
(62, 'E0052', 'ELAINE MARICRIS T.', NULL, 'AGUILAR', 'elainemaricris.aguilar@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', 'profile_697c5e5a1459b9.21959357.png', 600.00, 28),
(63, 'E0053', 'JOYLENE F.', NULL, 'BALANON', 'joylene.balanon@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 28),
(64, 'E0054', 'AVERIE L.', NULL, 'CABUSORA', 'averie.cabusora@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 28),
(65, 'E0055', 'JOHN JULIUS', NULL, 'ECHAGUE', 'johnjulius.echague@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:17:06', NULL, 600.00, 29),
(66, 'E0056', 'JOHN KENEDY', NULL, 'LUCAS', 'johnkenedy.lucas@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:17:06', NULL, 600.00, 29),
(67, 'E0057', 'RONALYN', NULL, 'MALLARE', 'ronalyn.mallare@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:17:06', NULL, 600.00, 29),
(68, 'ENG - 2026 - 0001', 'MICHELLE F.', NULL, 'NORIAL', 'michelle.norial@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 29),
(69, 'E0059', 'WINNIELYN KAYE M.', NULL, 'OLARTE', 'winnielynkaye.olarte@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:15:26', NULL, 600.00, 29),
(70, 'E0060', 'FLORIAN JANE', NULL, 'PERALTA', 'florianjane.peralta@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:17:06', NULL, 600.00, 30),
(71, 'E0061', 'DANIEL', NULL, 'RILLERA', 'daniel.rillera@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:17:06', NULL, 600.00, 30),
(72, 'E0062', 'JUNELL', NULL, 'TADINA', 'junell.tadina@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-06 01:17:06', NULL, 600.00, 30),
(73, 'e10001', 'Marc', 'Espanto', 'Arzadon', 'you@gmail.com', 'e10adc3949ba59abbe56e057f20f883e', 'Employee', 'Active', '2026-01-27 03:08:02', '2026-02-06 01:17:06', NULL, 600.00, 30),
(111, 'Supere', 'Admin', 'Admin', 'Admin', 'super@gmail.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Super Admin', 'Active', '0000-00-00 00:00:00', '2026-02-06 01:17:06', '', 0.00, 30),
(112, 'SUPER001', 'Super', 'Admin', 'Account', 'superadmin@example.com', '$2y$10$Pci.6CbsQnCcVA.OxTJSs.Trzw0lFxGNFsLEDYY3hPtKbkOUxYLuC', 'Super Adminn', 'Active', '2026-02-03 07:49:06', '2026-02-06 01:17:06', NULL, 0.00, 31);

-- --------------------------------------------------------

--
-- Table structure for table `employee_transfers`
--

DROP TABLE IF EXISTS `employee_transfers`;
CREATE TABLE IF NOT EXISTS `employee_transfers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `from_branch` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_branch` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transfer_date` datetime NOT NULL,
  `status` enum('pending','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_transfer_date` (`transfer_date`),
  KEY `idx_status` (`status`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_transfers`
--

INSERT INTO `employee_transfers` (`id`, `employee_id`, `from_branch`, `to_branch`, `transfer_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 12, 'Testing', 'Sto. Rosario', '2026-02-03 14:26:37', 'completed', '2026-02-03 06:26:37', '2026-02-03 06:26:37'),
(2, 12, 'Sto. Rosario', 'Testing', '2026-02-03 14:26:44', 'completed', '2026-02-03 06:26:44', '2026-02-03 06:26:44'),
(3, 12, 'Sto. Rosario', 'Testing', '2026-02-03 14:44:02', 'completed', '2026-02-03 06:44:02', '2026-02-03 06:44:02'),
(4, 12, 'Testing', 'Sto. Rosario', '2026-02-03 14:44:14', 'completed', '2026-02-03 06:44:14', '2026-02-03 06:44:14'),
(5, 12, 'Sto. Rosario', 'Testing', '2026-02-03 14:48:59', 'completed', '2026-02-03 06:48:59', '2026-02-03 06:48:59'),
(6, 61, 'Testing', 'Sto. Rosario', '2026-02-03 15:34:02', 'completed', '2026-02-03 07:34:02', '2026-02-03 07:34:02'),
(7, 12, 'Sto. Rosario', 'Testing', '2026-02-05 08:42:51', 'completed', '2026-02-05 00:42:51', '2026-02-05 00:42:51'),
(8, 61, 'Sto. Rosario', 'Testing', '2026-02-05 08:42:59', 'completed', '2026-02-05 00:42:59', '2026-02-05 00:42:59'),
(9, 6, 'Testing', 'Sto. Rosario', '2026-02-05 12:02:44', 'completed', '2026-02-05 04:02:44', '2026-02-05 04:02:44'),
(10, 12, 'Sto. Rosario', 'Testing', '2026-02-05 13:24:47', 'completed', '2026-02-05 05:24:47', '2026-02-05 05:24:47'),
(11, 12, 'Testing', 'Sto. Rosario', '2026-02-05 13:25:00', 'completed', '2026-02-05 05:25:00', '2026-02-05 05:25:00'),
(12, 12, 'Sto. Rosario', 'Testing', '2026-02-05 15:12:58', 'completed', '2026-02-05 07:12:58', '2026-02-05 07:12:58'),
(13, 12, 'Testing', 'Sto. Rosario', '2026-02-05 15:13:16', 'completed', '2026-02-05 07:13:16', '2026-02-05 07:13:16'),
(14, 12, 'Sto. Rosario', 'Testing', '2026-02-05 15:13:44', 'completed', '2026-02-05 07:13:44', '2026-02-05 07:13:44');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `identifier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` int DEFAULT '0',
  `last_attempt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `locked_until` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_identifier` (`identifier`(250))
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `ip_address`, `identifier`, `attempts`, `last_attempt`, `locked_until`) VALUES
(1, '::1', 'E0007', 1, '2026-01-27 03:31:30', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `performance_adjustments`
--

DROP TABLE IF EXISTS `performance_adjustments`;
CREATE TABLE IF NOT EXISTS `performance_adjustments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `performance_score` int DEFAULT '85',
  `bonus_amount` decimal(10,2) DEFAULT '0.00',
  `remarks` text,
  `view_type` enum('daily','weekly','monthly') DEFAULT 'weekly',
  `adjustment_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_date` (`employee_id`,`adjustment_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rate_limit`
--

DROP TABLE IF EXISTS `rate_limit`;
CREATE TABLE IF NOT EXISTS `rate_limit` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `timestamp` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ip_timestamp` (`ip`,`timestamp`)
) ENGINE=MyISAM AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rate_limit`
--

INSERT INTO `rate_limit` (`id`, `ip`, `user_id`, `timestamp`) VALUES
(58, '::1', 0, 1770272446);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
