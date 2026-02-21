-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 21, 2026 at 01:43 AM
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
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
CREATE TABLE IF NOT EXISTS `branches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_number` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branch_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_address` varchar(55) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_name` (`branch_name`),
  KEY `idx_branch_name` (`branch_name`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=MyISAM AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `order_number`, `branch_name`, `branch_address`, `created_at`, `is_active`) VALUES
(23, '393859493', 'BCDA - Fence', 'Poro point, San Fernando City, La Union', '2026-02-06 01:01:29', 1),
(22, '393859493', 'BCDA - Control Tower', 'Poro point, San Fernando City, La Union', '2026-02-06 01:01:11', 1),
(21, '393859493', 'BCDA - Admin', 'Poro point, San Fernando City, La Union', '2026-02-06 01:00:59', 1),
(10, '299269388', 'Sto. Rosario', 'Sto. Rosario, San Juan, La Union', '2026-01-29 03:19:23', 1),
(20, '393859493', 'BCDA - CCA', 'Poro point, San Fernando City, La Union', '2026-02-06 01:00:44', 1),
(32, '488809024', 'Maintenance', NULL, '2026-02-06 01:03:08', 1),
(24, '393859493', 'BCDA - Fire Station', 'Poro point, San Fernando City, La Union', '2026-02-06 01:01:46', 1),
(25, '393859493', 'BCDA - CCTV', 'Poro point, San Fernando City, La Union', '2026-02-06 01:01:55', 1),
(26, '159166591', 'Panicsican', 'Panicsican, San Juan, La Union', '2026-02-06 01:02:07', 1),
(27, '149744923', 'Dallangayan', NULL, '2026-02-06 01:02:16', 1),
(28, '228984422', 'Pias - Sundara', NULL, '2026-02-06 01:02:25', 1),
(29, '228984422', 'Pias - Office', NULL, '2026-02-06 01:02:33', 1),
(30, '473768962', 'Capitol - Roadwork', NULL, '2026-02-06 01:02:59', 1),
(31, '473768962', 'Capitol - Accounting', NULL, '2026-02-06 01:03:08', 1),
(33, '458762594', 'MAIN OFFICE', NULL, '2026-02-10 08:10:39', 1);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
