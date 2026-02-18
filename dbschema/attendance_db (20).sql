-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 18, 2026 at 02:35 AM
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
) ENGINE=InnoDB AUTO_INCREMENT=582 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(578, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-16 00:27:36'),
(579, 63, 'Logged In', 'User JOYLENE F. BALANON logged in from branch: Main Branch', '::1', '2026-02-16 03:08:47'),
(580, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-16 03:41:18'),
(581, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-18 01:56:51');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `status` enum('Present','Late','Absent','System') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branch_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attendance_date` date NOT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_auto_absent` tinyint(1) DEFAULT '0',
  `auto_absent_applied` tinyint(1) DEFAULT '0',
  `absent_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_overtime_running` tinyint(1) NOT NULL,
  `is_time_running` tinyint(1) NOT NULL,
  `total_ot_hrs` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_attendance_employee_date` (`employee_id`,`attendance_date`)
) ENGINE=MyISAM AUTO_INCREMENT=1067 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_id`, `status`, `branch_name`, `attendance_date`, `time_in`, `time_out`, `created_at`, `updated_at`, `is_auto_absent`, `auto_absent_applied`, `absent_notes`, `is_overtime_running`, `is_time_running`, `total_ot_hrs`) VALUES
(1066, 6, 'Present', 'Main Branch', '2026-02-18', NULL, NULL, '2026-02-18 01:56:51', NULL, 0, 0, NULL, 0, 0, '0'),
(1064, 6, 'Present', 'Main Branch', '2026-02-16', NULL, NULL, '2026-02-16 00:27:36', '2026-02-16 03:41:18', 0, 0, NULL, 0, 0, '0'),
(1065, 63, 'Present', 'Main Branch', '2026-02-16', NULL, NULL, '2026-02-16 03:08:47', NULL, 0, 0, NULL, 0, 0, '0');

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
CREATE TABLE IF NOT EXISTS `branches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `branch_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_address` varchar(55) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_name` (`branch_name`),
  KEY `idx_branch_name` (`branch_name`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=MyISAM AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `branch_name`, `branch_address`, `created_at`, `is_active`) VALUES
(23, 'BCDA - Fence', NULL, '2026-02-06 01:01:29', 1),
(22, 'BCDA - Control Tower', NULL, '2026-02-06 01:01:11', 1),
(21, 'BCDA - Admin', 'Taguig City, Metro Manila', '2026-02-06 01:00:59', 1),
(10, 'Sto. Rosario', NULL, '2026-01-29 03:19:23', 1),
(20, 'BCDA - CCA', NULL, '2026-02-06 01:00:44', 1),
(32, 'Maintenance', NULL, '2026-02-06 01:03:08', 1),
(24, 'BCDA - Fire Station', NULL, '2026-02-06 01:01:46', 1),
(25, 'BCDA - CCTV', NULL, '2026-02-06 01:01:55', 1),
(26, 'Panicsican', NULL, '2026-02-06 01:02:07', 1),
(27, 'Dallangayan', NULL, '2026-02-06 01:02:16', 1),
(28, 'Pias - Sundara', NULL, '2026-02-06 01:02:25', 1),
(29, 'Pias - Office', NULL, '2026-02-06 01:02:33', 1),
(30, 'Capitol - Roadwork', NULL, '2026-02-06 01:02:59', 1),
(31, 'Capitol - Accounting', NULL, '2026-02-06 01:03:08', 1),
(33, 'MAIN OFFICE', NULL, '2026-02-10 08:10:39', 1);

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
-- Table structure for table `cash_advances`
--

DROP TABLE IF EXISTS `cash_advances`;
CREATE TABLE IF NOT EXISTS `cash_advances` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `particular` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Cash Advance',
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `request_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `approved_date` datetime DEFAULT NULL,
  `paid_date` datetime DEFAULT NULL,
  `approved_by` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cash_advances`
--

INSERT INTO `cash_advances` (`id`, `employee_id`, `amount`, `particular`, `reason`, `status`, `request_date`, `approved_date`, `paid_date`, `approved_by`, `approved_at`, `rejection_reason`) VALUES
(14, 63, 1.05, 'Cash Advance', 'agrtfdx', 'approved', '2026-02-16 11:40:17', NULL, NULL, 'Admin', '2026-02-16 11:41:22', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Active','Inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_name` (`category_name`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_payroll_reports`
--

DROP TABLE IF EXISTS `daily_payroll_reports`;
CREATE TABLE IF NOT EXISTS `daily_payroll_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `report_date` date NOT NULL,
  `report_year` int NOT NULL,
  `report_month` int NOT NULL,
  `report_day` int NOT NULL,
  `week_number` int NOT NULL DEFAULT '1',
  `branch_id` int DEFAULT NULL,
  `days_worked` decimal(4,1) DEFAULT '0.0',
  `total_hours` decimal(8,2) DEFAULT '0.00',
  `daily_rate` decimal(10,2) DEFAULT '0.00',
  `basic_pay` decimal(10,2) DEFAULT '0.00',
  `ot_hours` decimal(6,2) DEFAULT '0.00',
  `ot_rate` decimal(10,2) DEFAULT '0.00',
  `ot_amount` decimal(10,2) DEFAULT '0.00',
  `performance_allowance` decimal(10,2) DEFAULT '0.00',
  `gross_pay` decimal(10,2) DEFAULT '0.00',
  `gross_plus_allowance` decimal(10,2) DEFAULT '0.00',
  `ca_deduction` decimal(10,2) DEFAULT '0.00',
  `sss_deduction` decimal(10,2) DEFAULT '0.00',
  `philhealth_deduction` decimal(10,2) DEFAULT '0.00',
  `pagibig_deduction` decimal(10,2) DEFAULT '0.00',
  `sss_loan` decimal(10,2) DEFAULT '0.00',
  `total_deductions` decimal(10,2) DEFAULT '0.00',
  `take_home_pay` decimal(10,2) DEFAULT '0.00',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_emp_date_branch` (`employee_id`,`report_date`,`branch_id`),
  KEY `idx_report_date` (`report_date`),
  KEY `idx_employee` (`employee_id`),
  KEY `idx_year_month` (`report_year`,`report_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
CREATE TABLE IF NOT EXISTS `documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `document_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `upload_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_doc_type` (`employee_id`,`document_type`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
CREATE TABLE IF NOT EXISTS `employees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Employee',
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `profile_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `daily_rate` decimal(10,2) DEFAULT '600.00',
  `branch_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_code` (`employee_code`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_employees_branch` (`branch_id`)
) ENGINE=MyISAM AUTO_INCREMENT=137 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_code`, `first_name`, `middle_name`, `last_name`, `email`, `password_hash`, `position`, `status`, `created_at`, `updated_at`, `profile_image`, `daily_rate`, `branch_id`) VALUES
(16, 'E0006', 'ALFREDO', NULL, 'BAGUIO', 'alfredo.baguio@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:22:15', NULL, 550.00, 10),
(17, 'E0007', 'ROLLY', NULL, 'BALTAZAR', 'rolly.baltazar@example.com', '$2y$10$4/nX3PsxAeYnik1fwh7lxO3XJHlW.IiOjK5NZPDCDD9eXoCBMVp8K', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:22:23', NULL, 500.00, 10),
(18, 'E0008', 'DONG', NULL, 'BAUTISTA', 'dong.bautista@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 07:01:04', NULL, 600.00, 20),
(14, 'E0004', 'NOEL', NULL, 'ARIZ', 'noel.ariz@example.com', '$2y$10$2Iq/E7PtLMHHBwAjTl.q5OthGTKYXQf5Bx/Q/SXpsmeyQ5VJKcnnO', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-13 08:25:50', NULL, 550.00, 10),
(6, 'SA001', 'Super', '', 'Adminesu', 'admin@jajrconstruction.com', '$2y$10$RSHOb3hskFZueMLlCycFuua/4EwcxGmAIzpcl8ixQpEXY3tfu9LYi', 'Super Admin', 'Active', '2026-01-16 02:26:58', '2026-02-11 01:28:51', 'profile_697d9f9a1f47a8.96968556.png', 600.00, 31),
(15, 'E0005', 'DANIEL', NULL, 'BACHILLER', 'daniel.bachiller@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:27:53', NULL, 600.00, 20),
(11, 'E0001', 'AARIZ', NULL, 'MARLOU', 'aariz.marlou@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 01:58:28', NULL, 700.00, 21),
(12, 'E0002', 'CESAR', NULL, 'ABUBO', 'cesar.abubo@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:12:46', 'profile_697d962d450256.84780797.png', 550.00, 10),
(13, 'E0003', 'MARLON', NULL, 'AGUILAR', 'marlon.aguilar@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-11 05:02:02', NULL, 600.00, 20),
(19, 'E0009', 'JANLY', NULL, 'BELINO', 'janly.belino@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:24:27', NULL, 650.00, 10),
(20, 'E0010', 'MENUEL', NULL, 'BENITEZ', 'menuel.benitez@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:23:00', NULL, 600.00, 10),
(21, 'E0011', 'GELMAR', NULL, 'BERNACHEA', 'gelmar.bernachea@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:24:13', NULL, 500.00, 10),
(22, 'E0012', 'JOMAR', NULL, 'CABANBAN', 'jomar.cabanban@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 07:01:04', NULL, 600.00, 22),
(23, 'E0013', 'MARIO', NULL, 'CABANBAN', 'mario.cabanban@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:23:08', NULL, 600.00, 10),
(24, 'E0014', 'KELVIN', NULL, 'CALDERON', 'kelvin.calderon@example.com', '$2y$10$d7rLs2lPiCob5CCSgaZVqO3w9jDwWaFIIsH7eqpaZ1/7myUv319q2', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-13 08:26:11', NULL, 500.00, 21),
(25, 'E0015', 'FLORANTE', NULL, 'CALUZA', 'florante.caluza@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 07:01:04', NULL, 600.00, 22),
(26, 'E0016', 'MELVIN', NULL, 'CAMPOS', 'melvin.campos@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:26:08', NULL, 600.00, 21),
(27, 'E0017', 'JERWIN', NULL, 'CAMPOS', 'jerwin.campos@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:22:14', NULL, 550.00, 21),
(28, 'E0018', 'BENJIE', NULL, 'CARAS', 'benjie.caras@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:23:27', NULL, 700.00, 10),
(29, 'E0019', 'BONJO', NULL, 'DACUMOS', 'bonjo.dacumos@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:25:08', NULL, 500.00, 10),
(30, 'E0020', 'RYAN', NULL, 'DEOCARIS', 'ryan.deocaris@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:26:16', NULL, 500.00, 21),
(31, 'E0021', 'BEN', NULL, 'ESTEPA', 'ben.estepa@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:23:41', NULL, 600.00, 10),
(32, 'E0022', 'MAR DAVE', NULL, 'FLORES', 'mardave.flores@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:28:21', NULL, 550.00, 20),
(33, 'E0023', 'ALBERT', NULL, 'FONTANILLA', 'albert.fontanilla@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:22:14', NULL, 550.00, 20),
(34, 'E0024', 'JOHN WILSON', NULL, 'FONTANILLA', 'johnwilson.fontanilla@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 07:51:18', NULL, 600.00, 20),
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
(48, 'E0038', 'ARNOLD', NULL, 'NERIDO', 'arnold.nerido@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 01:58:24', NULL, 600.00, 21),
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
(63, 'ENG-2026-0005', 'JOYLENE F.', NULL, 'BALANON', 'joylene.balanon@example.com', '$2y$10$6sbxv2qIU8i/2KUOVDrUZOLBIHTOvRoI9ApBOwLtYPXN60w8jx4mm', 'Engineer', 'Active', '2026-01-22 07:58:04', '2026-02-14 06:07:08', NULL, 600.00, 21),
(122, 'E0053', 'VERGEL', NULL, 'DACUMOS', 'vergel.dacumos@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:22', '2026-02-06 08:48:24', NULL, 600.00, 22),
(123, 'E0054', 'REAL RAIN', NULL, 'IVERSON', 'realrain.iverson@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:22', '2026-02-06 08:48:38', NULL, 600.00, 22),
(67, 'ADMIN-2026-0002', 'RONALYN', NULL, 'MALLARE', 'ronalyn.mallare@example.com', '$2y$10$s7xQ8p1U.l28nDSgbhYG/uLSvLFL5CA1Weyn0APXBa93lnoX7eANK', 'Admin', 'Active', '2026-01-22 07:58:04', '2026-02-10 08:14:16', NULL, 600.00, 33),
(68, 'ENG-2026-0001', 'MICHELLE F.', NULL, 'NORIAL', 'michelle.norial@example.com', '$2y$10$uIk2ehlCc6dssBZzLVITSOucNq/LPXCv2a7cZi5MDquTH7pmmN94O', 'Engineer', 'Active', '2026-01-22 07:58:04', '2026-02-12 02:09:06', NULL, 600.00, 29),
(127, 'E0058', 'JHUNEL', NULL, 'CANCHO', 'jhunel.cancho@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:34', '2026-02-09 00:24:48', NULL, 500.00, 10),
(124, 'E0055', 'VOHANN', NULL, 'MIRANDA', 'vohann.miranda@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:22', '2026-02-06 08:48:48', NULL, 600.00, 22),
(125, 'E0056', 'SONNY', NULL, 'OCCIANO', 'sonny.occiano@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:22', '2026-02-09 00:23:00', NULL, 1400.00, 22),
(126, 'E0065', 'RANDY', NULL, 'ATON', 'randy.aton@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:34', '2026-02-06 08:47:34', NULL, 600.00, 10),
(120, 'SA-2026-004', 'Marc', '', 'Arzadon', 'arzadon@gmail.com', '$2y$10$qSf327Nylr1l.TkboICD6ujkKmYGEaiTvixotQ.Jh/XP.MYOZsJIe', 'Super Admin', 'Active', '2026-02-06 07:18:15', '2026-02-07 07:33:06', NULL, 600.00, NULL),
(111, 'Supere', 'Admin', 'Admin', 'Admin', 'super@gmail.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Super Admin', 'Active', '0000-00-00 00:00:00', '2026-02-06 01:17:06', '', 0.00, 30),
(112, 'SUPER001', 'Super', 'Admin', 'Account', 'superadmin@example.com', '$2y$10$Pci.6CbsQnCcVA.OxTJSs.Trzw0lFxGNFsLEDYY3hPtKbkOUxYLuC', 'Super Adminn', 'Active', '2026-02-03 07:49:06', '2026-02-06 01:17:06', NULL, 0.00, 31),
(115, 'PRO-2026-0001', 'Junell', '', 'Tadina', 'tadina@gmail.com', '$2y$10$Nc0l0GkWV9crcUj7dc1vie4ry1up7kwrYBJGeH5oDSvJlhKCOgUt6', 'Engineer', 'Active', '2026-02-06 07:12:32', '2026-02-10 02:31:56', NULL, 600.00, NULL),
(114, 'ENG-2026-0003', 'Julius John', '', 'Echague', 'echague@gmail.com', '$2y$10$5vYYVwzl3qRA1ClmqUBjJu/YM8SrszeIhO6oEtaoFXcuVxIpmvrV2', 'Engineer', 'Active', '2026-02-06 07:12:00', '2026-02-07 07:34:38', NULL, 600.00, NULL),
(121, 'E0052', 'JOSHUA', NULL, 'ARQUITOLA', 'joshua.arquitola@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:22', '2026-02-06 08:48:07', NULL, 600.00, 22),
(113, 'ENG-2026-0002', 'John Kennedy', '', 'Lucas', 'lucas@gmail.com', '$2y$10$p.ERk7.PwModiMwq61au.ufymZHF/jRpMffS3dQBobbFwEmADEUT.', 'Engineer', 'Active', '2026-02-06 07:11:15', '2026-02-07 07:34:49', NULL, 600.00, NULL),
(116, 'ENG-2026-0006', 'Winnielyn Kaye', '', 'Olarte', 'olarte@gmail.com', '$2y$10$1NUUvvknY0mWhdfHYYygheh6Kj1zoCTQSQcxOzPUKNyR28/S4cj7G', 'Engineer', 'Active', '2026-02-06 07:14:59', '2026-02-07 07:35:05', NULL, 600.00, NULL),
(117, 'ADMIN-2026-0001', 'ELAINE', 'Torres', 'Aguilar', 'aguilar@gmail.com', '$2y$10$Q0GiyO/e43xHBEwRHNAmvOoh7pu9TEiN3t1Jl1mL39UuhHsv6k8Wq', 'Admin', 'Active', '2026-02-06 07:15:51', '2026-02-10 08:13:37', NULL, 600.00, 33),
(118, 'SA-2026-002', 'Jason', 'Larkin', 'Wong', 'wong@gmail.com', '$2y$10$TWT37ldw/9w1nEBDLtVgvOS/6gEEM1IJSbthCB/9vHmaeJ7FYuGbC', 'Super Admin', 'Active', '2026-02-06 07:16:34', '2026-02-07 07:33:29', NULL, 600.00, NULL),
(119, 'SA-2026-003', 'Lee Aldrich', '', 'Rimando', 'rimando@gmail.com', '$2y$10$BeFRm.XDlPuyZJHLC4Qhw.WZuxW8biClIxAAILz9PEzVaO9gEo92G', 'Super Admin', 'Active', '2026-02-06 07:17:12', '2026-02-07 07:33:18', NULL, 600.00, NULL),
(135, 'ADMIN-2026-0003', 'Admin', '', 'Charisse', 'charisse@gmail.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'ADMIN', 'Active', '2026-02-10 07:55:32', '2026-02-10 08:13:48', NULL, 600.00, 33),
(129, 'E0060', 'HECTOR', NULL, 'PADICLAS', 'hector.padiclas@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:34', '2026-02-06 08:47:34', NULL, 600.00, 10),
(130, 'E0061', 'MARIANO', NULL, 'NERIDO', 'mariano.nerido@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:34', '2026-02-06 08:51:31', NULL, 600.00, 21),
(131, 'E0062', 'JAYSON KENNETH', NULL, 'PADILLA', 'jaysonkenneth.padilla@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:34', '2026-02-09 00:31:58', NULL, 500.00, 21),
(132, 'E0063', 'JEFFREY', NULL, 'ZAMORA', 'jeffrey.zamora@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:34', '2026-02-09 01:58:11', NULL, 600.00, 21),
(133, 'E0064', 'FRANKIE', NULL, 'PADILLA', 'frankie.padilla@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:34', '2026-02-09 00:33:37', NULL, 500.00, 20),
(134, 'E0066', 'ROMEO', NULL, 'GURION', 'romeo.gurion@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:50:56', '2026-02-09 00:22:14', NULL, 550.00, 10),
(136, 'ADMIN-2026-0004', 'Marjorie', '', 'Garcia', 'garcia@gmail.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'ADMIN', 'Active', '2026-02-10 07:56:55', '2026-02-10 08:19:47', NULL, 600.00, 33);

-- --------------------------------------------------------

--
-- Table structure for table `employee_notifications`
--

DROP TABLE IF EXISTS `employee_notifications`;
CREATE TABLE IF NOT EXISTS `employee_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `overtime_request_id` int DEFAULT NULL,
  `notification_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL,
  `cash_advance_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_employee_read` (`employee_id`,`is_read`),
  KEY `idx_created` (`created_at` DESC),
  KEY `overtime_request_id` (`overtime_request_id`),
  KEY `cash_advance_id` (`cash_advance_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_notifications`
--

INSERT INTO `employee_notifications` (`id`, `employee_id`, `overtime_request_id`, `notification_type`, `title`, `message`, `is_read`, `created_at`, `read_at`, `cash_advance_id`) VALUES
(15, 63, NULL, 'cash_advance_pending', 'Cash Advance Submitted', 'Your cash advance request for ₱1.05 has been submitted and is pending approval.', 1, '2026-02-16 03:40:18', '2026-02-16 03:41:03', 14),
(16, 63, NULL, 'cash_advance_approved', 'Cash Advance Approved', 'Your cash advance request for ₱1.05 has been approved.', 0, '2026-02-16 03:41:22', NULL, 14);

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
) ENGINE=MyISAM AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `e_signatures`
--

DROP TABLE IF EXISTS `e_signatures`;
CREATE TABLE IF NOT EXISTS `e_signatures` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `signature_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'employee',
  `signature_image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `signature_data` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_type` (`employee_id`,`signature_type`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_signature_type` (`signature_type`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
CREATE TABLE IF NOT EXISTS `items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category_id` int DEFAULT NULL,
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int DEFAULT NULL,
  `status` enum('Active','Inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_code` (`item_code`),
  UNIQUE KEY `item_name` (`item_name`),
  KEY `category_id` (`category_id`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `recipient_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('PR Created','PR Approved','PR Rejected','PO Created','Item Received','System') COLLATE utf8mb4_unicode_ci DEFAULT 'System',
  `related_id` int DEFAULT NULL COMMENT 'ID of related record (PR, PO, etc.)',
  `related_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Type of related record',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `recipient_id` (`recipient_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_payments`
--

DROP TABLE IF EXISTS `payroll_payments`;
CREATE TABLE IF NOT EXISTS `payroll_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `payroll_week` int NOT NULL,
  `payroll_year` int NOT NULL,
  `payroll_start_date` date NOT NULL,
  `payroll_end_date` date NOT NULL,
  `gross_pay` decimal(10,2) NOT NULL,
  `net_pay` decimal(10,2) NOT NULL,
  `status` enum('Pending','Paid') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `paid_at` datetime DEFAULT NULL,
  `paid_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_payroll` (`employee_id`,`payroll_week`,`payroll_year`,`payroll_start_date`),
  KEY `paid_by` (`paid_by`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_records`
--

DROP TABLE IF EXISTS `payroll_records`;
CREATE TABLE IF NOT EXISTS `payroll_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `pay_period_start` date NOT NULL,
  `pay_period_end` date NOT NULL,
  `days_present` int DEFAULT '0',
  `days_absent` int DEFAULT '0',
  `days_late` int DEFAULT '0',
  `daily_rate` decimal(10,2) DEFAULT '0.00',
  `basic_pay` decimal(10,2) DEFAULT '0.00',
  `ot_hours` decimal(5,2) DEFAULT '0.00',
  `ot_rate` decimal(10,2) DEFAULT '0.00',
  `ot_pay` decimal(10,2) DEFAULT '0.00',
  `performance_bonus` decimal(10,2) DEFAULT '0.00',
  `gross_pay` decimal(10,2) DEFAULT '0.00',
  `sss_deduction` decimal(10,2) DEFAULT '0.00',
  `philhealth_deduction` decimal(10,2) DEFAULT '0.00',
  `pagibig_deduction` decimal(10,2) DEFAULT '0.00',
  `tax_deduction` decimal(10,2) DEFAULT '0.00',
  `other_deductions` decimal(10,2) DEFAULT '0.00',
  `total_deductions` decimal(10,2) DEFAULT '0.00',
  `net_pay` decimal(10,2) DEFAULT '0.00',
  `status` enum('Draft','Processed','Paid','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Draft',
  `processed_by` int DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_period` (`employee_id`,`pay_period_start`,`pay_period_end`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_pay_period` (`pay_period_start`,`pay_period_end`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `view_type` enum('daily','weekly','monthly') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'weekly',
  `adjustment_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_date` (`employee_id`,`adjustment_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `po_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `purchase_request_id` int NOT NULL,
  `supplier_id` int NOT NULL,
  `prepared_by` int NOT NULL,
  `total_amount` decimal(10,2) DEFAULT '0.00',
  `po_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `actual_delivery_date` date DEFAULT NULL,
  `status` enum('Draft','Ordered','Delivered','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_number` (`po_number`),
  KEY `purchase_request_id` (`purchase_request_id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `prepared_by` (`prepared_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
CREATE TABLE IF NOT EXISTS `purchase_order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int NOT NULL,
  `purchase_request_item_id` int NOT NULL,
  `item_id` int NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `purchase_order_id` (`purchase_order_id`),
  KEY `purchase_request_item_id` (`purchase_request_item_id`),
  KEY `item_id` (`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_requests`
--

DROP TABLE IF EXISTS `purchase_requests`;
CREATE TABLE IF NOT EXISTS `purchase_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pr_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `requested_by` int NOT NULL,
  `purpose` text COLLATE utf8mb4_unicode_ci,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Pending','Approved','Rejected','For Purchase','Completed','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pr_number` (`pr_number`),
  KEY `requested_by` (`requested_by`),
  KEY `approved_by` (`approved_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_request_items`
--

DROP TABLE IF EXISTS `purchase_request_items`;
CREATE TABLE IF NOT EXISTS `purchase_request_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `purchase_request_id` int NOT NULL,
  `item_id` int NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) DEFAULT '0.00',
  `total_price` decimal(10,2) DEFAULT '0.00',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Pending','For Purchase','Purchased','Received') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `received_by` int DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `purchase_request_id` (`purchase_request_id`),
  KEY `item_id` (`item_id`),
  KEY `received_by` (`received_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=MyISAM AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rate_limit`
--

INSERT INTO `rate_limit` (`id`, `ip`, `user_id`, `timestamp`) VALUES
(93, '::1', 0, 1771046748);

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `supplier_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Active','Inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_code` (`supplier_code`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_items`
--

DROP TABLE IF EXISTS `supplier_items`;
CREATE TABLE IF NOT EXISTS `supplier_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `supplier_id` int NOT NULL,
  `item_id` int NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `lead_time_days` int DEFAULT NULL COMMENT 'Estimated delivery time in days',
  `is_preferred` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_supplier_item` (`supplier_id`,`item_id`),
  KEY `item_id` (`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `weekly_payroll_reports`
--

DROP TABLE IF EXISTS `weekly_payroll_reports`;
CREATE TABLE IF NOT EXISTS `weekly_payroll_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `report_year` int NOT NULL,
  `report_month` int NOT NULL,
  `week_number` int NOT NULL COMMENT 'Week 1-5',
  `view_type` enum('weekly','monthly') COLLATE utf8mb4_unicode_ci DEFAULT 'weekly',
  `branch_id` int DEFAULT NULL COMMENT 'Filtered branch, NULL for all',
  `days_worked` int DEFAULT '0',
  `total_hours` int DEFAULT '0',
  `daily_rate` decimal(10,2) DEFAULT '0.00',
  `basic_pay` decimal(10,2) DEFAULT '0.00',
  `ot_hours` decimal(5,2) DEFAULT '0.00',
  `ot_rate` decimal(10,2) DEFAULT '0.00',
  `ot_amount` decimal(10,2) DEFAULT '0.00',
  `performance_allowance` decimal(10,2) DEFAULT '0.00',
  `gross_pay` decimal(10,2) DEFAULT '0.00',
  `gross_plus_allowance` decimal(10,2) DEFAULT '0.00',
  `ca_deduction` decimal(10,2) DEFAULT '0.00' COMMENT 'Cash Advance - Fillable',
  `sss_deduction` decimal(10,2) DEFAULT '0.00',
  `philhealth_deduction` decimal(10,2) DEFAULT '0.00',
  `pagibig_deduction` decimal(10,2) DEFAULT '0.00',
  `sss_loan` decimal(10,2) DEFAULT '0.00',
  `total_deductions` decimal(10,2) DEFAULT '0.00',
  `take_home_pay` decimal(10,2) DEFAULT '0.00',
  `status` enum('Draft','Finalized','Processed') COLLATE utf8mb4_unicode_ci DEFAULT 'Draft',
  `payment_status` enum('Paid','Not Paid') COLLATE utf8mb4_unicode_ci DEFAULT 'Not Paid' COMMENT 'Employee salary payment status',
  `created_by` int DEFAULT NULL,
  `finalized_by` int DEFAULT NULL,
  `finalized_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_period_week` (`employee_id`,`report_year`,`report_month`,`week_number`,`view_type`,`branch_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_year_month_week` (`report_year`,`report_month`,`week_number`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_status` (`status`),
  KEY `idx_view_type` (`view_type`)
) ENGINE=InnoDB AUTO_INCREMENT=15940 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `weekly_report_audit_log`
--

DROP TABLE IF EXISTS `weekly_report_audit_log`;
CREATE TABLE IF NOT EXISTS `weekly_report_audit_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `weekly_report_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `field_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_value` decimal(10,2) DEFAULT NULL,
  `new_value` decimal(10,2) DEFAULT NULL,
  `changed_by` int DEFAULT NULL,
  `changed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `change_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_weekly_report_id` (`weekly_report_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_changed_at` (`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `weekly_report_summaries`
--

DROP TABLE IF EXISTS `weekly_report_summaries`;
CREATE TABLE IF NOT EXISTS `weekly_report_summaries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `report_year` int NOT NULL,
  `report_month` int NOT NULL,
  `week_number` int NOT NULL,
  `view_type` enum('weekly','monthly') COLLATE utf8mb4_unicode_ci DEFAULT 'weekly',
  `branch_id` int DEFAULT NULL,
  `branch_filter_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_employees` int DEFAULT '0',
  `total_days_worked` int DEFAULT '0',
  `total_basic_pay` decimal(12,2) DEFAULT '0.00',
  `total_ot_amount` decimal(12,2) DEFAULT '0.00',
  `total_allowances` decimal(12,2) DEFAULT '0.00',
  `total_gross_pay` decimal(12,2) DEFAULT '0.00',
  `total_ca_deductions` decimal(12,2) DEFAULT '0.00',
  `total_sss_deductions` decimal(12,2) DEFAULT '0.00',
  `total_philhealth_deductions` decimal(12,2) DEFAULT '0.00',
  `total_pagibig_deductions` decimal(12,2) DEFAULT '0.00',
  `total_sss_loans` decimal(12,2) DEFAULT '0.00',
  `total_deductions` decimal(12,2) DEFAULT '0.00',
  `total_take_home_pay` decimal(12,2) DEFAULT '0.00',
  `status` enum('Draft','Finalized','Exported') COLLATE utf8mb4_unicode_ci DEFAULT 'Draft',
  `exported_at` timestamp NULL DEFAULT NULL,
  `exported_by` int DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_period_view_branch` (`report_year`,`report_month`,`week_number`,`view_type`,`branch_id`),
  KEY `idx_year_month_week` (`report_year`,`report_month`,`week_number`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employee_notifications`
--
ALTER TABLE `employee_notifications`
  ADD CONSTRAINT `employee_notifications_ibfk_1` FOREIGN KEY (`overtime_request_id`) REFERENCES `overtime_requests` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
