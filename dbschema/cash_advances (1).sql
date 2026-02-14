-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 14, 2026 at 06:31 AM
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
-- Table structure for table `cash_advances`
--

DROP TABLE IF EXISTS `cash_advances`;
CREATE TABLE IF NOT EXISTS `cash_advances` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `particular` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Cash Advance',
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Pending','Paid') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `approved_date` datetime DEFAULT NULL,
  `paid_date` datetime DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cash_advances`
--

INSERT INTO `cash_advances` (`id`, `employee_id`, `amount`, `particular`, `reason`, `status`, `request_date`, `approved_date`, `paid_date`, `approved_by`) VALUES
(1, 12, 500.00, 'Cash Advance', 'afadsf', '', '2026-02-10 11:29:26', NULL, NULL, NULL),
(2, 12, 250.00, 'Payment', 'retu', '', '2026-02-10 11:36:48', NULL, NULL, NULL),
(3, 61, 1500.00, 'Cash Advance', 'dfgh', '', '2026-02-10 11:38:18', NULL, NULL, NULL),
(4, 61, 141.00, 'Payment', 'xdfghdf', '', '2026-02-10 11:38:30', NULL, NULL, NULL),
(5, 61, 420.00, 'Payment', 'asdf', 'Pending', '2026-02-10 13:43:38', NULL, NULL, NULL),
(6, 12, 500.00, 'Cash Advance', 'jnipji', 'Pending', '2026-02-10 14:35:47', NULL, NULL, NULL),
(7, 12, 500.00, 'Payment', 'hgyiu', 'Pending', '2026-02-10 14:35:59', NULL, NULL, NULL),
(8, 12, 345.00, 'Cash Advance', 'ads', 'Pending', '2026-02-11 09:02:40', NULL, NULL, NULL),
(9, 117, 24.00, 'Payment', 'etyty', 'Pending', '2026-02-11 11:32:34', NULL, NULL, NULL),
(10, 63, 12.00, 'Cash Advance', 'Gamitin para manood ng pallot', 'Pending', '2026-02-14 14:09:23', NULL, NULL, NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
