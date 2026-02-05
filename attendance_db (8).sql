-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 05, 2026 at 01:53 AM
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
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(66, 6, 'Logged In', 'User Super Adminesu logged in from branch: BCDA', '::1', '2026-02-05 00:15:45');

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
  `is_time_running` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=715 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_id`, `status`, `branch_name`, `attendance_date`, `time_in`, `time_out`, `created_at`, `updated_at`, `is_auto_absent`, `auto_absent_applied`, `is_time_running`) VALUES
(633, 12, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 11:32:05', '2026-02-03 11:32:07', '2026-02-03 03:32:05', NULL, 0, 0, 0),
(634, 12, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 11:32:08', '2026-02-03 11:32:28', '2026-02-03 03:32:08', NULL, 0, 0, 0),
(635, 12, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 11:32:30', '2026-02-03 11:32:31', '2026-02-03 03:32:30', NULL, 0, 0, 0),
(636, 12, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 11:32:32', '2026-02-03 11:32:33', '2026-02-03 03:32:32', NULL, 0, 0, 0),
(662, 12, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 14:41:49', '2026-02-03 14:42:04', '2026-02-03 06:41:49', NULL, 0, 0, 0),
(651, 18, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 14:20:41', '2026-02-03 14:40:38', '2026-02-03 06:20:41', NULL, 0, 0, 0),
(650, 17, NULL, 'Main Branch', '2026-02-03', '2026-02-03 14:20:28', '2026-02-03 14:40:37', '2026-02-03 06:20:28', '2026-02-03 07:50:08', 0, 0, 0),
(649, 63, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 14:00:08', '2026-02-03 14:40:36', '2026-02-03 06:00:08', NULL, 0, 0, 0),
(648, 73, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 13:51:32', '2026-02-03 14:40:35', '2026-02-03 05:51:32', NULL, 0, 0, 0),
(647, 73, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 13:51:17', '2026-02-03 13:51:25', '2026-02-03 05:51:17', NULL, 0, 0, 0),
(646, 73, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 13:51:04', '2026-02-03 13:51:14', '2026-02-03 05:51:04', NULL, 0, 0, 0),
(645, 16, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 13:38:52', '2026-02-03 14:40:36', '2026-02-03 05:38:52', NULL, 0, 0, 0),
(644, 15, NULL, 'Testing', '2026-02-03', '2026-02-03 13:37:10', NULL, '2026-02-03 05:37:10', NULL, 0, 0, 0),
(643, 73, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 13:37:08', '2026-02-03 13:50:56', '2026-02-03 05:37:08', NULL, 0, 0, 0),
(642, 14, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 13:37:00', '2026-02-03 14:40:32', '2026-02-03 05:37:00', NULL, 0, 0, 0),
(641, 13, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 11:41:09', '2026-02-03 13:01:10', '2026-02-03 03:41:09', NULL, 0, 0, 0),
(640, 13, NULL, 'Testing', '2026-02-03', '2026-02-03 11:36:20', '2026-02-03 11:36:26', '2026-02-03 03:36:20', NULL, 0, 0, 0),
(639, 62, NULL, 'Testing', '2026-02-03', '2026-02-03 11:36:10', NULL, '2026-02-03 03:36:10', NULL, 0, 0, 0),
(638, 12, NULL, 'Testing', '2026-02-03', '2026-02-03 11:36:00', '2026-02-03 14:20:57', '2026-02-03 03:36:00', '2026-02-03 06:26:44', 0, 0, 0),
(637, 12, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 11:32:35', '2026-02-03 11:32:43', '2026-02-03 03:32:35', NULL, 0, 0, 0),
(632, 12, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 11:32:00', '2026-02-03 11:32:03', '2026-02-03 03:32:00', NULL, 0, 0, 0),
(631, 12, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 11:29:23', '2026-02-03 11:29:32', '2026-02-03 03:29:23', NULL, 0, 0, 0),
(630, 6, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 11:29:04', '2026-02-03 13:01:09', '2026-02-03 03:29:04', '2026-02-03 08:37:47', 0, 0, 0),
(629, 61, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 11:29:01', '2026-02-03 13:01:09', '2026-02-03 03:29:01', NULL, 0, 0, 0),
(628, 12, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 11:28:58', '2026-02-03 11:29:15', '2026-02-03 03:28:58', NULL, 0, 0, 0),
(654, 61, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 14:41:10', '2026-02-03 14:42:05', '2026-02-03 06:41:10', NULL, 0, 0, 0),
(653, 13, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 14:40:44', '2026-02-03 14:42:06', '2026-02-03 06:40:44', NULL, 0, 0, 0),
(655, 6, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 14:41:11', '2026-02-03 14:42:06', '2026-02-03 06:41:11', '2026-02-03 08:37:47', 0, 0, 0),
(656, 14, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 14:41:13', '2026-02-03 14:42:07', '2026-02-03 06:41:13', NULL, 0, 0, 0),
(657, 73, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 14:41:13', '2026-02-03 14:42:07', '2026-02-03 06:41:13', NULL, 0, 0, 0),
(658, 16, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 14:41:14', '2026-02-03 14:43:33', '2026-02-03 06:41:14', NULL, 0, 0, 0),
(659, 63, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 14:41:14', '2026-02-03 14:42:16', '2026-02-03 06:41:14', NULL, 0, 0, 0),
(660, 18, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 14:41:15', NULL, '2026-02-03 06:41:15', NULL, 0, 0, 1),
(661, 17, NULL, 'Main Branch', '2026-02-03', '2026-02-03 14:41:16', NULL, '2026-02-03 06:41:16', '2026-02-03 07:50:08', 0, 0, 0),
(663, 12, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 14:42:08', '2026-02-03 14:43:28', '2026-02-03 06:42:08', NULL, 0, 0, 0),
(664, 61, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 14:42:09', '2026-02-03 14:43:30', '2026-02-03 06:42:09', NULL, 0, 0, 0),
(665, 13, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 14:42:10', '2026-02-03 14:42:11', '2026-02-03 06:42:10', NULL, 0, 0, 0),
(666, 6, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 14:42:12', '2026-02-03 14:43:30', '2026-02-03 06:42:12', '2026-02-03 08:37:47', 0, 0, 0),
(667, 13, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 14:42:12', '2026-02-03 14:43:31', '2026-02-03 06:42:12', NULL, 0, 0, 0),
(668, 14, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 14:42:14', '2026-02-03 14:43:31', '2026-02-03 06:42:14', NULL, 0, 0, 0),
(669, 73, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 14:42:15', '2026-02-03 14:43:32', '2026-02-03 06:42:15', NULL, 0, 0, 0),
(670, 63, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 14:42:18', '2026-02-03 14:59:27', '2026-02-03 06:42:18', NULL, 0, 0, 0),
(671, 61, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 14:43:34', '2026-02-03 14:43:39', '2026-02-03 06:43:34', NULL, 0, 0, 0),
(672, 12, NULL, 'Testing', '2026-02-03', '2026-02-03 14:43:34', '2026-02-03 14:48:54', '2026-02-03 06:43:34', '2026-02-03 06:48:59', 0, 0, 0),
(673, 12, NULL, 'Testing', '2026-02-03', '2026-02-03 14:49:16', NULL, '2026-02-03 06:49:16', NULL, 0, 0, 1),
(674, 63, 'Present', 'Sto. Rosario', '2026-02-03', '2026-02-03 15:00:35', NULL, '2026-02-03 07:00:35', NULL, 0, 0, 1),
(675, 19, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 15:33:48', NULL, '2026-02-03 07:33:48', NULL, 0, 0, 1),
(676, 61, NULL, 'Sto. Rosario', '2026-02-03', '2026-02-03 15:33:58', NULL, '2026-02-03 07:33:58', '2026-02-03 07:34:02', 0, 0, 1),
(677, 112, 'Present', 'Sto. Rosario', '2026-02-03', NULL, NULL, '2026-02-03 07:49:38', '2026-02-03 08:36:59', 0, 0, 0),
(678, 6, 'Present', 'Sto. Rosario', '2026-02-05', '2026-02-05 08:30:46', NULL, '2026-02-05 00:15:45', NULL, 0, 0, 1),
(679, 12, NULL, 'Testing', '2026-02-05', '2026-02-05 08:20:51', '2026-02-05 08:32:13', '2026-02-05 00:20:51', '2026-02-05 00:42:51', 0, 0, 0),
(680, 61, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 08:30:22', '2026-02-05 08:31:02', '2026-02-05 00:30:22', NULL, 0, 0, 0),
(681, 112, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 08:30:32', '2026-02-05 09:20:58', '2026-02-05 00:30:32', NULL, 0, 0, 0),
(682, 111, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 08:30:39', NULL, '2026-02-05 00:30:39', NULL, 0, 0, 1),
(683, 62, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 08:30:59', NULL, '2026-02-05 00:30:59', NULL, 0, 0, 1),
(684, 61, NULL, 'Testing', '2026-02-05', '2026-02-05 08:31:07', '2026-02-05 09:25:29', '2026-02-05 00:31:07', '2026-02-05 00:42:59', 0, 0, 0),
(685, 13, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 08:31:15', '2026-02-05 09:40:00', '2026-02-05 00:31:15', NULL, 0, 0, 0),
(686, 14, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 08:31:34', '2026-02-05 09:39:58', '2026-02-05 00:31:34', NULL, 0, 0, 0),
(687, 73, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 08:31:56', '2026-02-05 09:39:56', '2026-02-05 00:31:56', NULL, 0, 0, 0),
(688, 15, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 08:32:05', '2026-02-05 09:39:54', '2026-02-05 00:32:05', NULL, 0, 0, 0),
(689, 16, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 08:33:38', NULL, '2026-02-05 00:33:38', NULL, 0, 0, 1),
(690, 63, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 08:33:48', NULL, '2026-02-05 00:33:48', NULL, 0, 0, 1),
(691, 17, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 08:33:49', NULL, '2026-02-05 00:33:49', NULL, 0, 0, 1),
(692, 18, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 08:33:50', NULL, '2026-02-05 00:33:50', NULL, 0, 0, 1),
(693, 19, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 08:33:51', NULL, '2026-02-05 00:33:51', NULL, 0, 0, 1),
(694, 20, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 08:33:52', NULL, '2026-02-05 00:33:52', NULL, 0, 0, 1),
(695, 21, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 09:20:33', NULL, '2026-02-05 01:20:33', NULL, 0, 0, 1),
(696, 22, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 09:20:50', NULL, '2026-02-05 01:20:50', NULL, 0, 0, 1),
(697, 23, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 09:20:55', NULL, '2026-02-05 01:20:55', NULL, 0, 0, 1),
(698, 12, NULL, 'afsdsd', '2026-02-05', '2026-02-05 09:22:01', '2026-02-05 09:22:03', '2026-02-05 01:22:01', NULL, 0, 0, 0),
(699, 12, NULL, 'afsdsd', '2026-02-05', '2026-02-05 09:22:08', '2026-02-05 09:25:36', '2026-02-05 01:22:08', NULL, 0, 0, 0),
(700, 64, NULL, 'afsdsd', '2026-02-05', NULL, NULL, '2026-02-05 01:24:03', NULL, 0, 0, 0),
(701, 24, NULL, 'afsdsd', '2026-02-05', '2026-02-05 09:24:06', NULL, '2026-02-05 01:24:06', NULL, 0, 0, 1),
(702, 25, NULL, 'afsdsd', '2026-02-05', '2026-02-05 09:24:09', NULL, '2026-02-05 01:24:09', NULL, 0, 0, 1),
(703, 27, NULL, 'afsdsd', '2026-02-05', '2026-02-05 09:24:12', NULL, '2026-02-05 01:24:12', NULL, 0, 0, 1),
(704, 112, NULL, 'afsdsd', '2026-02-05', '2026-02-05 09:25:33', '2026-02-05 09:25:34', '2026-02-05 01:24:22', NULL, 0, 0, 0),
(705, 26, NULL, 'afsdsd', '2026-02-05', '2026-02-05 09:25:16', NULL, '2026-02-05 01:25:16', NULL, 0, 0, 1),
(706, 61, NULL, 'afsdsd', '2026-02-05', '2026-02-05 09:25:31', '2026-02-05 09:25:35', '2026-02-05 01:25:31', NULL, 0, 0, 0),
(707, 28, NULL, 'afsdsd', '2026-02-05', '2026-02-05 09:39:26', NULL, '2026-02-05 01:39:26', NULL, 0, 0, 1),
(708, 12, NULL, 'afsdsd', '2026-02-05', '2026-02-05 09:39:33', '2026-02-05 09:39:43', '2026-02-05 01:39:33', NULL, 0, 0, 0),
(709, 61, NULL, 'afsdsd', '2026-02-05', '2026-02-05 09:39:34', '2026-02-05 09:39:36', '2026-02-05 01:39:34', NULL, 0, 0, 0),
(710, 12, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 09:42:00', '2026-02-05 09:42:35', '2026-02-05 01:42:00', NULL, 0, 0, 0),
(711, 61, NULL, 'Sto. Rosario', '2026-02-05', '2026-02-05 09:42:07', '2026-02-05 09:42:41', '2026-02-05 01:42:07', NULL, 0, 0, 0),
(712, 29, NULL, 'Testing', '2026-02-05', '2026-02-05 09:42:22', NULL, '2026-02-05 01:42:22', NULL, 0, 0, 1),
(713, 12, NULL, 'afsdsd', '2026-02-05', '2026-02-05 09:43:02', NULL, '2026-02-05 01:43:02', NULL, 0, 0, 1),
(714, 30, NULL, 'afsdsd', '2026-02-05', '2026-02-05 09:47:38', NULL, '2026-02-05 01:47:38', NULL, 0, 0, 1);

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
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `branch_name`, `created_at`, `is_active`) VALUES
(10, 'Sto. Rosario', '2026-01-29 03:19:23', 1),
(18, 'afsdsd', '2026-02-05 01:21:11', 1),
(17, 'Testing', '2026-01-30 06:46:16', 1);

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
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_code` (`employee_code`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_code`, `first_name`, `middle_name`, `last_name`, `email`, `password_hash`, `position`, `status`, `created_at`, `updated_at`, `profile_image`, `daily_rate`) VALUES
(16, 'E0006', 'ALFREDO', NULL, 'BAGUIO', 'alfredo.baguio@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 550.00),
(17, 'E0007', 'ROLLY', NULL, 'BALTAZAR', 'rolly.baltazar@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 500.00),
(18, 'E0008', 'DONG', NULL, 'BAUTISTA', 'dong.bautista@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(14, 'E0004', 'NOEL', NULL, 'ARIZ', 'noel.ariz@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 550.00),
(6, 'SA001', 'Super', 'Duper', 'Adminesu', 'admin@jajrconstruction.com', '$2y$10$RSHOb3hskFZueMLlCycFuua/4EwcxGmAIzpcl8ixQpEXY3tfu9LYi', 'Super Admin', 'Active', '2026-01-16 02:26:58', '2026-02-03 07:40:49', 'profile_697d9f9a1f47a8.96968556.png', 600.00),
(15, 'E0005', 'DANIEL', NULL, 'BACHILLER', 'daniel.bachiller@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(11, 'E0001', 'AARIZ', NULL, 'MARLOU', 'aariz.marlou@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(12, 'E0002', 'CESAR', NULL, 'ABUBO', 'cesar.abubo@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', 'profile_697d962d450256.84780797.png', 600.00),
(13, 'E0003', 'MARLON', NULL, 'AGUILAR', 'marlon.aguilar@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(19, 'E0009', 'JANLY', NULL, 'BELINO', 'janly.belino@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(20, 'E0010', 'MENUEL', NULL, 'BENITEZ', 'menuel.benitez@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(21, 'E0011', 'GELMAR', NULL, 'BERNACHEA', 'gelmar.bernachea@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(22, 'E0012', 'JOMAR', NULL, 'CABANBAN', 'jomar.cabanban@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(23, 'E0013', 'MARIO', NULL, 'CABANBAN', 'mario.cabanban@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(24, 'E0014', 'KELVIN', NULL, 'CALDERON', 'kelvin.calderon@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 500.00),
(25, 'E0015', 'FLORANTE', NULL, 'CALUZA', 'florante.caluza@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(26, 'E0016', 'MELVIN', NULL, 'CAMPOS', 'melvin.campos@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(27, 'E0017', 'JERWIN', NULL, 'CAMPOS', 'jerwin.campos@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(28, 'E0018', 'BENJIE', NULL, 'CARAS', 'benjie.caras@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 700.00),
(29, 'E0019', 'BONJO', NULL, 'DACUMOS', 'bonjo.dacumos@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(30, 'E0020', 'RYAN', NULL, 'DEOCARIS', 'ryan.deocaris@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 500.00),
(31, 'E0021', 'BEN', NULL, 'ESTEPA', 'ben.estepa@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(32, 'E0022', 'MAR DAVE', NULL, 'FLORES', 'mardave.flores@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 550.00),
(33, 'E0023', 'ALBERT', NULL, 'FONTANILLA', 'albert.fontanilla@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(34, 'E0024', 'JOHN WILSON', NULL, 'FONTANILLA', 'johnwilson.fontanilla@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(35, 'E0025', 'LEO', NULL, 'GURTIZA', 'leo.gurtiza@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(36, 'E0026', 'JOSE', NULL, 'IGLECIAS', 'jose.iglecias@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 500.00),
(37, 'E0027', 'JEFFREY', NULL, 'JIMENEZ', 'jeffrey.jimenez@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 550.00),
(38, 'E0028', 'WILSON', NULL, 'LICTAOA', 'wilson.lictaoa@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 500.00),
(39, 'E0029', 'LORETO', NULL, 'MABALO', 'loreto.mabalo@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(40, 'E0030', 'ROMEL', NULL, 'MALLARE', 'romel.mallare@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(41, 'E0031', 'SAMUEL SR.', NULL, 'MARQUEZ', 'samuel.marquez@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 500.00),
(42, 'E0032', 'ROLLY', NULL, 'MARZAN', 'rolly.marzan@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(43, 'E0033', 'RONALD', NULL, 'MARZAN', 'ronald.marzan@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(44, 'E0034', 'WILSON', NULL, 'MARZAN', 'wilson.marzan@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(45, 'E0035', 'MARVIN', NULL, 'MIRANDA', 'marvin.miranda@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(46, 'E0036', 'JOE', NULL, 'MONTERDE', 'joe.monterde@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 700.00),
(47, 'E0037', 'ALDRED', NULL, 'NATARTE', 'aldred.natarte@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(48, 'E0038', 'ARNOLD', NULL, 'NERIDO', 'arnold.nerido@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(49, 'E0039', 'RONEL', NULL, 'NOSES', 'ronel.noses@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(50, 'E0040', 'DANNY', NULL, 'PADILLA', 'danny.padilla@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 500.00),
(51, 'E0041', 'EDGAR', NULL, 'PANEDA', 'edgar.paneda@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 550.00),
(52, 'E0042', 'JEREMY', NULL, 'PIMENTEL', 'jeremy.pimentel@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 550.00),
(53, 'E0043', 'MIGUEL', NULL, 'PREPOSI', 'miguel.preposi@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(54, 'E0044', 'JUN', NULL, 'ROAQUIN', 'jun.roaquin@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(55, 'E0045', 'RICKMAR', NULL, 'SANTOS', 'rickmar.santos@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(56, 'E0046', 'RIO', NULL, 'SILOY', 'rio.siloy@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(57, 'E0047', 'NORMAN', NULL, 'TARAPE', 'norman.tarape@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(58, 'E0048', 'HILMAR', NULL, 'TATUNAY', 'hilmar.tatunay@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(59, 'E0049', 'KENNETH JOHN', NULL, 'UGAS', 'kennethjohn.ugas@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(60, 'E0050', 'CLYDE JUSTINE', NULL, 'VASADRE', 'clydejustine.vasadre@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 500.00),
(61, 'E0051', 'CARL JHUNELL', NULL, 'ACAS', 'carljhunell.acas@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', 'profile_697d9f9350dad3.88439854.png', 600.00),
(62, 'E0052', 'ELAINE MARICRIS T.', NULL, 'AGUILAR', 'elainemaricris.aguilar@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', 'profile_697c5e5a1459b9.21959357.png', 600.00),
(63, 'E0053', 'JOYLENE F.', NULL, 'BALANON', 'joylene.balanon@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(64, 'E0054', 'AVERIE L.', NULL, 'CABUSORA', 'averie.cabusora@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(65, 'E0055', 'JOHN JULIUS', NULL, 'ECHAGUE', 'johnjulius.echague@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(66, 'E0056', 'JOHN KENEDY', NULL, 'LUCAS', 'johnkenedy.lucas@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(67, 'E0057', 'RONALYN', NULL, 'MALLARE', 'ronalyn.mallare@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(68, 'ENG - 2026 - 0001', 'MICHELLE F.', NULL, 'NORIAL', 'michelle.norial@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(69, 'E0059', 'WINNIELYN KAYE M.', NULL, 'OLARTE', 'winnielynkaye.olarte@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(70, 'E0060', 'FLORIAN JANE', NULL, 'PERALTA', 'florianjane.peralta@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(71, 'E0061', 'DANIEL', NULL, 'RILLERA', 'daniel.rillera@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(72, 'E0062', 'JUNELL', NULL, 'TADINA', 'junell.tadina@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-03 00:48:29', NULL, 600.00),
(73, 'e10001', 'Marc', 'Espanto', 'Arzadon', 'you@gmail.com', 'e10adc3949ba59abbe56e057f20f883e', 'Employee', 'Active', '2026-01-27 03:08:02', '2026-02-03 00:48:29', NULL, 600.00),
(111, 'Supere', 'Admin', 'Admin', 'Admin', 'super@gmail.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Super Admin', 'Active', '0000-00-00 00:00:00', '2026-02-03 07:47:06', '', 0.00),
(112, 'SUPER001', 'Super', 'Admin', 'Account', 'superadmin@example.com', '$2y$10$Pci.6CbsQnCcVA.OxTJSs.Trzw0lFxGNFsLEDYY3hPtKbkOUxYLuC', 'Super Adminn', 'Active', '2026-02-03 07:49:06', '2026-02-03 08:11:09', NULL, 0.00);

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
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(8, 61, 'Sto. Rosario', 'Testing', '2026-02-05 08:42:59', 'completed', '2026-02-05 00:42:59', '2026-02-05 00:42:59');

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
) ENGINE=MyISAM AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rate_limit`
--

INSERT INTO `rate_limit` (`id`, `ip`, `user_id`, `timestamp`) VALUES
(56, '::1', 0, 1770005145),
(55, '::1', 0, 1770005136),
(54, '::1', 0, 1770005134),
(53, '::1', 0, 1770005132),
(52, '::1', 0, 1770005130);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
