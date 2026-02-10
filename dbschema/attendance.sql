-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 09, 2026 at 06:33 AM
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
) ENGINE=MyISAM AUTO_INCREMENT=899 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_id`, `status`, `branch_name`, `attendance_date`, `time_in`, `time_out`, `created_at`, `updated_at`, `is_auto_absent`, `auto_absent_applied`, `absent_notes`, `is_overtime_running`, `is_time_running`, `total_ot_hrs`) VALUES
(859, 36, NULL, 'BCDA - Admin', '2026-02-09', '2026-02-09 12:05:41', NULL, '2026-02-09 04:05:41', NULL, 0, 0, NULL, 0, 1, '0'),
(860, 38, NULL, 'BCDA - Admin', '2026-02-09', '2026-02-09 12:05:42', NULL, '2026-02-09 04:05:42', NULL, 0, 0, NULL, 0, 1, '0'),
(858, 30, NULL, 'BCDA - Admin', '2026-02-09', '2026-02-09 12:05:40', NULL, '2026-02-09 04:05:40', NULL, 0, 0, NULL, 0, 1, '0'),
(856, 24, NULL, 'BCDA - Admin', '2026-02-09', '2026-02-09 12:05:39', NULL, '2026-02-09 04:05:39', NULL, 0, 0, NULL, 0, 1, '0'),
(857, 26, NULL, 'BCDA - Admin', '2026-02-09', '2026-02-09 12:05:39', NULL, '2026-02-09 04:05:39', NULL, 0, 0, NULL, 0, 1, '0'),
(855, 125, NULL, 'BCDA - Control Tower', '2026-02-09', '2026-02-09 12:05:35', NULL, '2026-02-09 04:05:35', NULL, 0, 0, NULL, 0, 1, '0'),
(854, 123, NULL, 'BCDA - Control Tower', '2026-02-09', '2026-02-09 12:05:35', NULL, '2026-02-09 04:05:35', NULL, 0, 0, NULL, 0, 1, '0'),
(853, 124, NULL, 'BCDA - Control Tower', '2026-02-09', '2026-02-09 12:05:33', NULL, '2026-02-09 04:05:33', NULL, 0, 0, NULL, 0, 1, '0'),
(851, 122, NULL, 'BCDA - Control Tower', '2026-02-09', '2026-02-09 12:05:33', NULL, '2026-02-09 04:05:33', NULL, 0, 0, NULL, 0, 1, '0'),
(852, 45, NULL, 'BCDA - Control Tower', '2026-02-09', '2026-02-09 12:05:33', NULL, '2026-02-09 04:05:33', NULL, 0, 0, NULL, 0, 1, '0'),
(850, 25, NULL, 'BCDA - Control Tower', '2026-02-09', '2026-02-09 12:05:32', NULL, '2026-02-09 04:05:32', NULL, 0, 0, NULL, 0, 1, '0'),
(847, 133, NULL, 'BCDA - CCA', '2026-02-09', '2026-02-09 12:05:29', NULL, '2026-02-09 04:05:29', NULL, 0, 0, NULL, 0, 1, '0'),
(848, 58, NULL, 'BCDA - CCA', '2026-02-09', '2026-02-09 12:05:30', NULL, '2026-02-09 04:05:30', NULL, 0, 0, NULL, 0, 1, '0'),
(849, 22, NULL, 'BCDA - Control Tower', '2026-02-09', '2026-02-09 12:05:32', NULL, '2026-02-09 04:05:32', NULL, 0, 0, NULL, 0, 1, '0'),
(846, 41, NULL, 'BCDA - CCA', '2026-02-09', '2026-02-09 12:05:29', NULL, '2026-02-09 04:05:29', NULL, 0, 0, NULL, 0, 1, '0'),
(845, 37, NULL, 'BCDA - CCA', '2026-02-09', '2026-02-09 12:05:29', NULL, '2026-02-09 04:05:29', NULL, 0, 0, NULL, 0, 1, '0'),
(844, 33, NULL, 'BCDA - CCA', '2026-02-09', '2026-02-09 12:05:29', '2026-02-09 14:28:18', '2026-02-09 04:05:29', NULL, 0, 0, NULL, 0, 0, '0'),
(843, 32, NULL, 'BCDA - CCA', '2026-02-09', '2026-02-09 12:05:29', NULL, '2026-02-09 04:05:29', NULL, 0, 0, NULL, 0, 1, '0'),
(842, 18, NULL, 'BCDA - CCA', '2026-02-09', '2026-02-09 12:05:28', NULL, '2026-02-09 04:05:28', NULL, 0, 0, NULL, 0, 1, '0'),
(841, 15, NULL, 'BCDA - CCA', '2026-02-09', '2026-02-09 12:05:28', NULL, '2026-02-09 04:05:28', NULL, 0, 0, NULL, 0, 1, '0'),
(840, 13, NULL, 'BCDA - CCA', '2026-02-09', '2026-02-09 12:05:28', NULL, '2026-02-09 04:05:28', NULL, 0, 0, NULL, 0, 1, '0'),
(839, 27, 'Absent', 'BCDA - Admin', '2026-02-09', NULL, NULL, '2026-02-09 01:58:49', NULL, 0, 0, '', 0, 0, '0'),
(838, 131, 'Absent', 'BCDA - Admin', '2026-02-09', NULL, NULL, '2026-02-09 01:58:43', NULL, 0, 0, '', 0, 0, '0'),
(837, 11, NULL, 'BCDA - Admin', '2026-02-09', '2026-02-09 09:58:28', NULL, '2026-02-09 01:58:28', NULL, 0, 0, NULL, 0, 1, '0'),
(836, 48, NULL, 'BCDA - Admin', '2026-02-09', '2026-02-09 09:58:24', NULL, '2026-02-09 01:58:24', NULL, 0, 0, NULL, 0, 1, '0'),
(835, 132, NULL, 'BCDA - Admin', '2026-02-09', '2026-02-09 09:58:11', NULL, '2026-02-09 01:58:11', NULL, 0, 0, NULL, 0, 1, '0'),
(834, 132, NULL, 'BCDA - Fence', '2026-02-09', '2026-02-09 09:56:28', '2026-02-09 09:58:10', '2026-02-09 01:56:28', NULL, 0, 0, NULL, 0, 0, '0'),
(833, 132, NULL, 'BCDA - CCA', '2026-02-09', '2026-02-09 09:54:39', '2026-02-09 09:56:22', '2026-02-09 01:54:39', NULL, 0, 0, NULL, 0, 0, '0'),
(832, 132, NULL, 'BCDA - Admin', '2026-02-09', '2026-02-09 09:53:06', '2026-02-09 09:53:11', '2026-02-09 01:53:06', NULL, 0, 0, NULL, 0, 0, '0'),
(831, 132, NULL, 'BCDA - CCA', '2026-02-09', '2026-02-09 09:48:21', '2026-02-09 09:49:37', '2026-02-09 01:48:21', NULL, 0, 0, NULL, 0, 0, '0'),
(830, 17, 'Present', 'Sto. Rosario', '2026-02-09', '2026-02-09 12:05:59', NULL, '2026-02-09 01:46:22', '2026-02-09 02:09:16', 0, 0, NULL, 0, 1, '0'),
(829, 121, NULL, 'BCDA - Control Tower', '2026-02-09', '2026-02-09 09:08:57', NULL, '2026-02-09 01:08:57', NULL, 0, 0, NULL, 0, 1, '0'),
(828, 6, 'Present', 'Main Branch', '2026-02-09', NULL, NULL, '2026-02-09 00:10:59', '2026-02-09 05:51:43', 0, 0, NULL, 0, 0, '0'),
(861, 42, NULL, 'BCDA - Admin', '2026-02-09', '2026-02-09 12:05:42', NULL, '2026-02-09 04:05:42', NULL, 0, 0, NULL, 0, 1, '0'),
(862, 44, NULL, 'BCDA - Admin', '2026-02-09', '2026-02-09 12:05:42', NULL, '2026-02-09 04:05:42', NULL, 0, 0, NULL, 0, 1, '0'),
(863, 130, NULL, 'BCDA - Admin', '2026-02-09', '2026-02-09 12:05:42', NULL, '2026-02-09 04:05:42', NULL, 0, 0, NULL, 0, 1, '0'),
(864, 56, NULL, 'Maintenance', '2026-02-09', '2026-02-09 12:05:49', NULL, '2026-02-09 04:05:49', NULL, 0, 0, NULL, 0, 1, '0'),
(865, 51, NULL, 'Panicsican', '2026-02-09', '2026-02-09 12:05:51', NULL, '2026-02-09 04:05:51', NULL, 0, 0, NULL, 0, 1, '0'),
(866, 54, NULL, 'Panicsican', '2026-02-09', '2026-02-09 12:05:51', NULL, '2026-02-09 04:05:51', NULL, 0, 0, NULL, 0, 1, '0'),
(867, 55, NULL, 'Pias - Sundara', '2026-02-09', '2026-02-09 12:05:54', NULL, '2026-02-09 04:05:54', NULL, 0, 0, NULL, 0, 1, '0'),
(868, 61, NULL, 'Pias - Sundara', '2026-02-09', '2026-02-09 12:05:55', NULL, '2026-02-09 04:05:55', NULL, 0, 0, NULL, 0, 1, '0'),
(869, 60, NULL, 'Pias - Sundara', '2026-02-09', '2026-02-09 12:05:56', NULL, '2026-02-09 04:05:56', NULL, 0, 0, NULL, 0, 1, '0'),
(870, 12, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:05:58', NULL, '2026-02-09 04:05:58', NULL, 0, 0, NULL, 0, 1, '0'),
(871, 14, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:05:58', NULL, '2026-02-09 04:05:58', NULL, 0, 0, NULL, 0, 1, '0'),
(872, 126, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:05:58', NULL, '2026-02-09 04:05:58', NULL, 0, 0, NULL, 0, 1, '0'),
(873, 16, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:05:58', NULL, '2026-02-09 04:05:58', NULL, 0, 0, NULL, 0, 1, '0'),
(874, 19, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:05:59', NULL, '2026-02-09 04:05:59', NULL, 0, 0, NULL, 0, 1, '0'),
(875, 20, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:06:01', NULL, '2026-02-09 04:06:01', NULL, 0, 0, NULL, 0, 1, '0'),
(876, 21, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:06:02', NULL, '2026-02-09 04:06:02', NULL, 0, 0, NULL, 0, 1, '0'),
(877, 23, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:06:04', NULL, '2026-02-09 04:06:04', NULL, 0, 0, NULL, 0, 1, '0'),
(878, 127, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:06:05', NULL, '2026-02-09 04:06:05', NULL, 0, 0, NULL, 0, 1, '0'),
(879, 28, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:06:07', NULL, '2026-02-09 04:06:07', NULL, 0, 0, NULL, 0, 1, '0'),
(880, 29, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:06:08', NULL, '2026-02-09 04:06:08', NULL, 0, 0, NULL, 0, 1, '0'),
(881, 31, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:06:12', NULL, '2026-02-09 04:06:12', NULL, 0, 0, NULL, 0, 1, '0'),
(882, 134, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:06:12', NULL, '2026-02-09 04:06:12', NULL, 0, 0, NULL, 0, 1, '0'),
(883, 35, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:06:13', NULL, '2026-02-09 04:06:13', NULL, 0, 0, NULL, 0, 1, '0'),
(884, 40, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:06:13', NULL, '2026-02-09 04:06:13', NULL, 0, 0, NULL, 0, 1, '0'),
(885, 39, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:06:14', NULL, '2026-02-09 04:06:14', NULL, 0, 0, NULL, 0, 1, '0'),
(886, 43, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:06:15', NULL, '2026-02-09 04:06:15', NULL, 0, 0, NULL, 0, 1, '0'),
(887, 46, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:06:15', NULL, '2026-02-09 04:06:15', NULL, 0, 0, NULL, 0, 1, '0'),
(888, 47, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:06:15', NULL, '2026-02-09 04:06:15', NULL, 0, 0, NULL, 0, 1, '0'),
(889, 49, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:06:16', NULL, '2026-02-09 04:06:16', NULL, 0, 0, NULL, 0, 1, '0'),
(890, 129, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:06:16', NULL, '2026-02-09 04:06:16', NULL, 0, 0, NULL, 0, 1, '0'),
(891, 50, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:06:16', NULL, '2026-02-09 04:06:16', NULL, 0, 0, NULL, 0, 1, '0'),
(892, 52, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:06:16', NULL, '2026-02-09 04:06:16', NULL, 0, 0, NULL, 0, 1, '0'),
(893, 53, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:06:17', NULL, '2026-02-09 04:06:17', NULL, 0, 0, NULL, 0, 1, '0'),
(894, 57, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:06:18', NULL, '2026-02-09 04:06:18', NULL, 0, 0, NULL, 0, 1, '0'),
(895, 59, NULL, 'Sto. Rosario', '2026-02-09', '2026-02-09 12:06:19', NULL, '2026-02-09 04:06:19', NULL, 0, 0, NULL, 0, 1, '0'),
(896, 33, NULL, 'BCDA - CCA', '2026-02-09', '2026-02-09 14:28:21', '2026-02-09 14:28:22', '2026-02-09 06:28:21', NULL, 0, 0, NULL, 0, 0, '0'),
(897, 33, NULL, 'BCDA - CCA', '2026-02-09', '2026-02-09 14:28:27', '2026-02-09 14:28:28', '2026-02-09 06:28:27', NULL, 0, 0, NULL, 0, 0, '0'),
(898, 33, NULL, 'BCDA - CCA', '2026-02-09', '2026-02-09 14:28:31', NULL, '2026-02-09 06:28:31', '2026-02-09 06:31:50', 0, 0, NULL, 0, 1, '5');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
