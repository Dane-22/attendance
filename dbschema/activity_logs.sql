-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 05, 2026 at 06:17 AM
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
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(69, 17, 'Logged In', 'User ROLLY BALTAZAR logged in from branch: BCDA', '::1', '2026-02-05 02:32:56');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
