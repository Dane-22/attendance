-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 09, 2026 at 01:04 AM
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
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(71, 6, 'Logged In', 'User Super Adminesu logged in from branch: STO. Rosario', '::1', '2026-02-06 01:00:23'),
(72, 17, 'Logged In', 'User ROLLY BALTAZAR logged in from branch: Panicsican', '::1', '2026-02-06 02:48:29'),
(73, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-06 05:24:08'),
(74, 17, 'Logged In', 'User ROLLY BALTAZAR logged in from branch: STO. Rosario', '::1', '2026-02-07 00:40:30'),
(75, 17, 'Logged In', 'User ROLLY BALTAZAR logged in from branch: BCDA', '::1', '2026-02-07 00:42:39'),
(76, 68, 'Logged In', 'User MICHELLE F. NORIAL logged in from branch: STO. Rosario', '::1', '2026-02-07 01:01:57'),
(77, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '192.168.100.21', '2026-02-07 01:44:17'),
(78, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '192.168.100.21', '2026-02-07 02:00:47'),
(79, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-07 07:00:28'),
(80, 120, 'Logged In', 'User Marc Arzadon logged in from branch: Main Branch', '::1', '2026-02-07 07:33:06'),
(81, 119, 'Logged In', 'User Lee Aldrich Rimando logged in from branch: Main Branch', '::1', '2026-02-07 07:33:18'),
(82, 118, 'Logged In', 'User Jason Wong logged in from branch: Main Branch', '::1', '2026-02-07 07:33:29'),
(83, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-02-07 07:33:40'),
(84, 67, 'Logged In', 'User RONALYN MALLARE logged in from branch: Main Branch', '::1', '2026-02-07 07:33:53'),
(85, 63, 'Logged In', 'User JOYLENE F. BALANON logged in from branch: Main Branch', '::1', '2026-02-07 07:34:04'),
(86, 68, 'Logged In', 'User MICHELLE F. NORIAL logged in from branch: Main Branch', '::1', '2026-02-07 07:34:17'),
(87, 115, 'Logged In', 'User Junell Tadina logged in from branch: Main Branch', '::1', '2026-02-07 07:34:28'),
(88, 114, 'Logged In', 'User Julius John Echague logged in from branch: Main Branch', '::1', '2026-02-07 07:34:38'),
(89, 113, 'Logged In', 'User John Kennedy Lucas logged in from branch: Main Branch', '::1', '2026-02-07 07:34:49'),
(90, 116, 'Logged In', 'User Winnielyn Kaye Olarte logged in from branch: Main Branch', '::1', '2026-02-07 07:35:05'),
(91, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-09 00:10:59'),
(92, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-09 00:50:09');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `status` enum('Present','Late','Absent','System') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `branch_name` varchar(50) NOT NULL,
  `attendance_date` date NOT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_auto_absent` tinyint(1) DEFAULT '0',
  `auto_absent_applied` tinyint(1) DEFAULT '0',
  `absent_notes` text,
  `is_overtime_running` tinyint(1) NOT NULL,
  `is_time_running` tinyint(1) NOT NULL,
  `total_ot_hrs` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=829 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_id`, `status`, `branch_name`, `attendance_date`, `time_in`, `time_out`, `created_at`, `updated_at`, `is_auto_absent`, `auto_absent_applied`, `absent_notes`, `is_overtime_running`, `is_time_running`, `total_ot_hrs`) VALUES
(828, 6, 'Present', 'Main Branch', '2026-02-09', NULL, NULL, '2026-02-09 00:10:59', '2026-02-09 00:50:09', 0, 0, NULL, 0, 0, '0');

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
) ENGINE=MyISAM AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `branch_name`, `created_at`, `is_active`) VALUES
(23, 'BCDA - Fence', '2026-02-06 01:01:29', 1),
(22, 'BCDA - Control Tower', '2026-02-06 01:01:11', 1),
(21, 'BCDA - Admin', '2026-02-06 01:00:59', 1),
(10, 'Sto. Rosario', '2026-01-29 03:19:23', 1),
(20, 'BCDA - CCA', '2026-02-06 01:00:44', 1),
(32, 'Maintenance', '2026-02-06 01:03:08', 1),
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
) ENGINE=MyISAM AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_code`, `first_name`, `middle_name`, `last_name`, `email`, `password_hash`, `position`, `status`, `created_at`, `updated_at`, `profile_image`, `daily_rate`, `branch_id`) VALUES
(16, 'E0006', 'ALFREDO', NULL, 'BAGUIO', 'alfredo.baguio@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:22:15', NULL, 550.00, 10),
(17, 'E0007', 'ROLLY', NULL, 'BALTAZAR', 'rolly.baltazar@example.com', '$2y$10$4/nX3PsxAeYnik1fwh7lxO3XJHlW.IiOjK5NZPDCDD9eXoCBMVp8K', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:22:23', NULL, 500.00, 10),
(18, 'E0008', 'DONG', NULL, 'BAUTISTA', 'dong.bautista@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 07:01:04', NULL, 600.00, 20),
(14, 'E0004', 'NOEL', NULL, 'ARIZ', 'noel.ariz@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:21:52', NULL, 550.00, 10),
(6, 'SA001', 'Super', 'Duper', 'Adminesu', 'admin@jajrconstruction.com', '$2y$10$RSHOb3hskFZueMLlCycFuua/4EwcxGmAIzpcl8ixQpEXY3tfu9LYi', 'Super Admin', 'Active', '2026-01-16 02:26:58', '2026-02-06 01:17:06', 'profile_697d9f9a1f47a8.96968556.png', 600.00, 31),
(15, 'E0005', 'DANIEL', NULL, 'BACHILLER', 'daniel.bachiller@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:27:53', NULL, 600.00, 20),
(11, 'E0001', 'AARIZ', NULL, 'MARLOU', 'aariz.marlou@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:30:24', NULL, 700.00, 21),
(12, 'E0002', 'CESAR', NULL, 'ABUBO', 'cesar.abubo@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:12:46', 'profile_697d962d450256.84780797.png', 550.00, 10),
(13, 'E0003', 'MARLON', NULL, 'AGUILAR', 'marlon.aguilar@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:25:45', NULL, 600.00, 21),
(19, 'E0009', 'JANLY', NULL, 'BELINO', 'janly.belino@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:24:27', NULL, 650.00, 10),
(20, 'E0010', 'MENUEL', NULL, 'BENITEZ', 'menuel.benitez@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:23:00', NULL, 600.00, 10),
(21, 'E0011', 'GELMAR', NULL, 'BERNACHEA', 'gelmar.bernachea@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:24:13', NULL, 500.00, 10),
(22, 'E0012', 'JOMAR', NULL, 'CABANBAN', 'jomar.cabanban@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 07:01:04', NULL, 600.00, 22),
(23, 'E0013', 'MARIO', NULL, 'CABANBAN', 'mario.cabanban@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:23:08', NULL, 600.00, 10),
(24, 'E0014', 'KELVIN', NULL, 'CALDERON', 'kelvin.calderon@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:25:57', NULL, 500.00, 21),
(25, 'E0015', 'FLORANTE', NULL, 'CALUZA', 'florante.caluza@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 07:01:04', NULL, 600.00, 22),
(26, 'E0016', 'MELVIN', NULL, 'CAMPOS', 'melvin.campos@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:26:08', NULL, 600.00, 21),
(27, 'E0017', 'JERWIN', NULL, 'CAMPOS', 'jerwin.campos@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:22:14', NULL, 550.00, 21),
(28, 'E0018', 'BENJIE', NULL, 'CARAS', 'benjie.caras@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:23:27', NULL, 700.00, 10),
(29, 'E0019', 'BONJO', NULL, 'DACUMOS', 'bonjo.dacumos@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:25:08', NULL, 500.00, 10),
(30, 'E0020', 'RYAN', NULL, 'DEOCARIS', 'ryan.deocaris@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:26:16', NULL, 500.00, 21),
(31, 'E0021', 'BEN', NULL, 'ESTEPA', 'ben.estepa@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:23:41', NULL, 600.00, 10),
(32, 'E0022', 'MAR DAVE', NULL, 'FLORES', 'mardave.flores@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:28:21', NULL, 550.00, 20),
(33, 'E0023', 'ALBERT', NULL, 'FONTANILLA', 'albert.fontanilla@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:22:14', NULL, 550.00, 20),
(34, 'E0024', 'JOHN WILSON', NULL, 'FONTANILLA', 'johnwilson.fontanilla@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 07:01:04', NULL, 600.00, 24),
(35, 'E0025', 'LEO', NULL, 'GURTIZA', 'leo.gurtiza@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:23:47', NULL, 600.00, 10),
(36, 'E0026', 'JOSE', NULL, 'IGLECIAS', 'jose.iglecias@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:26:21', NULL, 500.00, 21),
(37, 'E0027', 'JEFFREY', NULL, 'JIMENEZ', 'jeffrey.jimenez@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:28:37', NULL, 550.00, 20),
(38, 'E0028', 'WILSON', NULL, 'LICTAOA', 'wilson.lictaoa@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:26:29', NULL, 500.00, 21),
(39, 'E0029', 'LORETO', NULL, 'MABALO', 'loreto.mabalo@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:24:06', NULL, 600.00, 10),
(40, 'E0030', 'ROMEL', NULL, 'MALLARE', 'romel.mallare@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:26:06', NULL, 800.00, 10),
(41, 'E0031', 'SAMUEL SR.', NULL, 'MARQUEZ', 'samuel.marquez@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:28:57', NULL, 500.00, 20),
(42, 'E0032', 'ROLLY', NULL, 'MARZAN', 'rolly.marzan@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:26:46', NULL, 600.00, 21),
(43, 'E0033', 'RONALD', NULL, 'MARZAN', 'ronald.marzan@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:24:22', NULL, 600.00, 10),
(44, 'E0034', 'WILSON', NULL, 'MARZAN', 'wilson.marzan@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:26:38', NULL, 600.00, 21),
(45, 'E0035', 'MARVIN', NULL, 'MIRANDA', 'marvin.miranda@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:21:12', NULL, 600.00, 22),
(46, 'E0036', 'JOE', NULL, 'MONTERDE', 'joe.monterde@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 07:01:04', NULL, 700.00, 10),
(47, 'E0037', 'ALDRED', NULL, 'NATARTE', 'aldred.natarte@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 07:01:04', NULL, 600.00, 10),
(48, 'E0038', 'ARNOLD', NULL, 'NERIDO', 'arnold.nerido@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:27:03', NULL, 600.00, 21),
(49, 'E0039', 'RONEL', NULL, 'NOSES', 'ronel.noses@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:26:40', NULL, 500.00, 10),
(50, 'E0040', 'DANNY', NULL, 'PADILLA', 'danny.padilla@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:25:01', NULL, 500.00, 10),
(51, 'E0041', 'EDGAR', NULL, 'PANEDA', 'edgar.paneda@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 07:01:04', NULL, 550.00, 26),
(52, 'E0042', 'JEREMY', NULL, 'PIMENTEL', 'jeremy.pimentel@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:25:09', NULL, 550.00, 10),
(53, 'E0043', 'MIGUEL', NULL, 'PREPOSI', 'miguel.preposi@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:25:16', NULL, 600.00, 10),
(54, 'E0044', 'JUN', NULL, 'ROAQUIN', 'jun.roaquin@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 07:01:04', NULL, 600.00, 26),
(55, 'E0045', 'RICKMAR', NULL, 'SANTOS', 'rickmar.santos@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:34:19', NULL, 500.00, 28),
(56, 'E0046', 'RIO', NULL, 'SILOY', 'rio.siloy@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:34:48', NULL, 750.00, 32),
(57, 'E0047', 'NORMAN', NULL, 'TARAPE', 'norman.tarape@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:29:59', NULL, 500.00, 10),
(58, 'E0048', 'HILMAR', NULL, 'TATUNAY', 'hilmar.tatunay@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:33:51', NULL, 500.00, 20),
(59, 'E0049', 'KENNETH JOHN', NULL, 'UGAS', 'kennethjohn.ugas@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:25:30', NULL, 600.00, 10),
(60, 'E0050', 'CLYDE JUSTINE', NULL, 'VASADRE', 'clydejustine.vasadre@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 07:01:04', NULL, 500.00, 28),
(61, 'E0051', 'CARL JHUNELL', NULL, 'ACAS', 'carljhunell.acas@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 07:01:04', 'profile_697d9f9350dad3.88439854.png', 600.00, 28),
(63, 'ENG-2026-0005', 'JOYLENE F.', NULL, 'BALANON', 'joylene.balanon@example.com', '$2y$10$6sbxv2qIU8i/2KUOVDrUZOLBIHTOvRoI9ApBOwLtYPXN60w8jx4mm', 'Engineer', 'Active', '2026-01-22 07:58:04', '2026-02-07 07:34:04', NULL, 600.00, 28),
(122, 'E0053', 'VERGEL', NULL, 'DACUMOS', 'vergel.dacumos@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:22', '2026-02-06 08:48:24', NULL, 600.00, 22),
(123, 'E0054', 'REAL RAIN', NULL, 'IVERSON', 'realrain.iverson@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:22', '2026-02-06 08:48:38', NULL, 600.00, 22),
(67, 'ADMIN-2026-0002', 'RONALYN', NULL, 'MALLARE', 'ronalyn.mallare@example.com', '$2y$10$s7xQ8p1U.l28nDSgbhYG/uLSvLFL5CA1Weyn0APXBa93lnoX7eANK', 'Admin', 'Active', '2026-01-22 07:58:04', '2026-02-07 07:33:53', NULL, 600.00, 29),
(68, 'ENG-2026-0001', 'MICHELLE F.', NULL, 'NORIAL', 'michelle.norial@example.com', '$2y$10$S4M8tSg0lZoPpy7Nq1jthOM.uVyphIDJYYb1GPjiqAdar0Q9VCFm.', 'Engineer', 'Active', '2026-01-22 07:58:04', '2026-02-07 07:30:24', NULL, 600.00, 29),
(127, 'E0058', 'JHUNEL', NULL, 'CANCHO', 'jhunel.cancho@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:34', '2026-02-09 00:24:48', NULL, 500.00, 10),
(124, 'E0055', 'VOHANN', NULL, 'MIRANDA', 'vohann.miranda@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:22', '2026-02-06 08:48:48', NULL, 600.00, 22),
(125, 'E0056', 'SONNY', NULL, 'OCCIANO', 'sonny.occiano@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:22', '2026-02-09 00:23:00', NULL, 1400.00, 22),
(126, 'E0065', 'RANDY', NULL, 'ATON', 'randy.aton@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:34', '2026-02-06 08:47:34', NULL, 600.00, 10),
(120, 'SA-2026-004', 'Marc', '', 'Arzadon', 'arzadon@gmail.com', '$2y$10$qSf327Nylr1l.TkboICD6ujkKmYGEaiTvixotQ.Jh/XP.MYOZsJIe', 'Super Admin', 'Active', '2026-02-06 07:18:15', '2026-02-07 07:33:06', NULL, 600.00, NULL),
(111, 'Supere', 'Admin', 'Admin', 'Admin', 'super@gmail.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Super Admin', 'Active', '0000-00-00 00:00:00', '2026-02-06 01:17:06', '', 0.00, 30),
(112, 'SUPER001', 'Super', 'Admin', 'Account', 'superadmin@example.com', '$2y$10$Pci.6CbsQnCcVA.OxTJSs.Trzw0lFxGNFsLEDYY3hPtKbkOUxYLuC', 'Super Adminn', 'Active', '2026-02-03 07:49:06', '2026-02-06 01:17:06', NULL, 0.00, 31),
(115, 'ENG-2026-0004', 'Junell', '', 'Tadina', 'tadina@gmail.com', '$2y$10$Nc0l0GkWV9crcUj7dc1vie4ry1up7kwrYBJGeH5oDSvJlhKCOgUt6', 'Engineer', 'Active', '2026-02-06 07:12:32', '2026-02-07 07:34:28', NULL, 600.00, NULL),
(114, 'ENG-2026-0003', 'Julius John', '', 'Echague', 'echague@gmail.com', '$2y$10$5vYYVwzl3qRA1ClmqUBjJu/YM8SrszeIhO6oEtaoFXcuVxIpmvrV2', 'Engineer', 'Active', '2026-02-06 07:12:00', '2026-02-07 07:34:38', NULL, 600.00, NULL),
(121, 'E0052', 'JOSHUA', NULL, 'ARQUITOLA', 'joshua.arquitola@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:22', '2026-02-06 08:48:07', NULL, 600.00, 22),
(113, 'ENG-2026-0002', 'John Kennedy', '', 'Lucas', 'lucas@gmail.com', '$2y$10$p.ERk7.PwModiMwq61au.ufymZHF/jRpMffS3dQBobbFwEmADEUT.', 'Engineer', 'Active', '2026-02-06 07:11:15', '2026-02-07 07:34:49', NULL, 600.00, NULL),
(116, 'ENG-2026-0006', 'Winnielyn Kaye', '', 'Olarte', 'olarte@gmail.com', '$2y$10$1NUUvvknY0mWhdfHYYygheh6Kj1zoCTQSQcxOzPUKNyR28/S4cj7G', 'Engineer', 'Active', '2026-02-06 07:14:59', '2026-02-07 07:35:05', NULL, 600.00, NULL),
(117, 'ADMIN-2026-0001', 'ELAINE', 'Torres', 'Aguilar', 'aguilar@gmail.com', '$2y$10$Q0GiyO/e43xHBEwRHNAmvOoh7pu9TEiN3t1Jl1mL39UuhHsv6k8Wq', 'Admin', 'Active', '2026-02-06 07:15:51', '2026-02-07 07:33:40', NULL, 600.00, NULL),
(118, 'SA-2026-002', 'Jason', 'Larkin', 'Wong', 'wong@gmail.com', '$2y$10$TWT37ldw/9w1nEBDLtVgvOS/6gEEM1IJSbthCB/9vHmaeJ7FYuGbC', 'Super Admin', 'Active', '2026-02-06 07:16:34', '2026-02-07 07:33:29', NULL, 600.00, NULL),
(119, 'SA-2026-003', 'Lee Aldrich', '', 'Rimando', 'rimando@gmail.com', '$2y$10$BeFRm.XDlPuyZJHLC4Qhw.WZuxW8biClIxAAILz9PEzVaO9gEo92G', 'Super Admin', 'Active', '2026-02-06 07:17:12', '2026-02-07 07:33:18', NULL, 600.00, NULL),
(129, 'E0060', 'HECTOR', NULL, 'PADICLAS', 'hector.padiclas@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:34', '2026-02-06 08:47:34', NULL, 600.00, 10),
(130, 'E0061', 'MARIANO', NULL, 'NERIDO', 'mariano.nerido@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:34', '2026-02-06 08:51:31', NULL, 600.00, 21),
(131, 'E0062', 'JAYSON KENNETH', NULL, 'PADILLA', 'jaysonkenneth.padilla@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:34', '2026-02-09 00:31:58', NULL, 500.00, 21),
(132, 'E0063', 'JEFFREY', NULL, 'ZAMORA', 'jeffrey.zamora@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:34', '2026-02-06 08:52:04', NULL, 600.00, 21),
(133, 'E0064', 'FRANKIE', NULL, 'PADILLA', 'frankie.padilla@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:34', '2026-02-09 00:33:37', NULL, 500.00, 20),
(134, 'E0066', 'ROMEO', NULL, 'GURION', 'romeo.gurion@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:50:56', '2026-02-09 00:22:14', NULL, 550.00, 10);

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
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(14, 12, 'Sto. Rosario', 'Testing', '2026-02-05 15:13:44', 'completed', '2026-02-05 07:13:44', '2026-02-05 07:13:44'),
(15, 15, 'BCDA - CCA', 'BCDA - Control Tower', '2026-02-06 11:47:40', 'completed', '2026-02-06 03:47:40', '2026-02-06 03:47:40'),
(16, 14, 'BCDA - CCA', 'BCDA - Admin', '2026-02-06 11:47:48', 'completed', '2026-02-06 03:47:48', '2026-02-06 03:47:48'),
(17, 18, 'BCDA - CCA', 'BCDA - Admin', '2026-02-06 11:50:19', 'completed', '2026-02-06 03:50:19', '2026-02-06 03:50:19'),
(18, 19, 'BCDA - CCA', 'BCDA - Admin', '2026-02-06 11:50:33', 'completed', '2026-02-06 03:50:33', '2026-02-06 03:50:33'),
(19, 19, 'BCDA - Admin', 'BCDA - Fence', '2026-02-06 11:51:04', 'completed', '2026-02-06 03:51:04', '2026-02-06 03:51:04'),
(20, 14, 'BCDA - Admin', 'BCDA - CCA', '2026-02-06 11:51:08', 'completed', '2026-02-06 03:51:08', '2026-02-06 03:51:08'),
(21, 17, 'BCDA - CCA', 'BCDA - Admin', '2026-02-06 11:51:15', 'completed', '2026-02-06 03:51:15', '2026-02-06 03:51:15'),
(22, 14, 'BCDA - CCA', 'BCDA - Admin', '2026-02-06 13:15:47', 'completed', '2026-02-06 05:15:47', '2026-02-06 05:15:47'),
(23, 32, 'BCDA - Fire Station', 'BCDA - Admin', '2026-02-06 13:39:52', 'completed', '2026-02-06 05:39:52', '2026-02-06 05:39:52'),
(24, 12, 'Capitol - Accounting', 'BCDA - Admin', '2026-02-06 13:40:01', 'completed', '2026-02-06 05:40:01', '2026-02-06 05:40:01');

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
) ENGINE=MyISAM AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rate_limit`
--

INSERT INTO `rate_limit` (`id`, `ip`, `user_id`, `timestamp`) VALUES
(59, '::1', 0, 1770597485);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
