-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 02, 2026 at 07:45 AM
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
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(58, 6, 'Logged In', 'User Super Admin logged in from branch: Main Branch', '::1', '2026-01-31 07:57:51'),
(59, 6, 'Logged In', 'User Super Admin logged in from branch: BCDA', '::1', '2026-02-02 00:36:47');

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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_auto_absent` tinyint(1) DEFAULT '0',
  `auto_absent_applied` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=424 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_id`, `status`, `branch_name`, `attendance_date`, `created_at`, `updated_at`, `is_auto_absent`, `auto_absent_applied`) VALUES
(423, 1, 'Absent', 'Main', '2026-02-02', '2026-02-02 02:08:38', NULL, 0, 0),
(421, 73, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(419, 71, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(418, 70, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(417, 69, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(415, 67, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(408, 60, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(404, 56, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(402, 54, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(400, 52, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(399, 51, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(396, 48, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(395, 47, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(394, 46, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(392, 44, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(391, 43, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(390, 42, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(389, 41, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(388, 40, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(387, 39, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(382, 34, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(381, 33, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(379, 31, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(378, 30, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(377, 29, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(366, 13, 'Present', 'Testing', '2026-02-02', '2026-02-02 01:10:15', '2026-02-02 03:03:30', 0, 0),
(365, 12, 'Present', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', '2026-02-02 01:36:52', 0, 0),
(364, 11, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(363, 15, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(362, 14, 'Present', 'Testing', '2026-02-02', '2026-02-02 01:10:15', '2026-02-02 03:03:56', 0, 0),
(361, 18, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(360, 17, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(359, 16, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(358, 6, 'Present', 'BCDA', '2026-02-02', '2026-02-02 00:36:47', NULL, 0, 0),
(357, 0, '', 'System', '2026-01-31', '2026-01-31 01:00:15', NULL, 0, 1),
(356, 73, 'Present', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(355, 72, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(354, 71, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(422, 0, '', 'System', '2026-02-02', '2026-02-02 01:10:15', NULL, 0, 1),
(420, 72, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(416, 68, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(414, 66, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(413, 65, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(412, 64, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(411, 63, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(410, 62, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(409, 61, 'Present', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', '2026-02-02 05:32:12', 0, 0),
(407, 59, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(406, 58, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(405, 57, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(403, 55, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(401, 53, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(398, 50, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(397, 49, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(393, 45, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(386, 38, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(385, 37, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(384, 36, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(383, 35, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(380, 32, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(376, 28, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(375, 27, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(374, 26, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(373, 25, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(372, 24, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(371, 23, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(370, 22, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(369, 21, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(368, 20, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(367, 19, 'Absent', 'Sto. Rosario', '2026-02-02', '2026-02-02 01:10:15', NULL, 1, 0),
(353, 70, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(352, 69, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(351, 68, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(350, 67, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(349, 66, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(348, 65, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(347, 64, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(346, 63, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(345, 60, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(344, 59, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(343, 58, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(342, 57, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(341, 56, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(340, 55, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(339, 54, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(338, 53, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(337, 52, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(336, 51, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(335, 50, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(334, 49, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(333, 48, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(332, 47, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(331, 46, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(330, 45, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(329, 44, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(328, 43, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(327, 42, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(326, 41, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(325, 40, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(324, 39, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(323, 38, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(322, 37, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(321, 36, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(320, 35, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(319, 34, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(318, 33, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(317, 32, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(316, 31, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(315, 30, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(314, 29, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(313, 28, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(312, 27, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(311, 26, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(310, 25, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(309, 24, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(308, 23, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(307, 22, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(306, 21, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(305, 20, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(304, 19, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(303, 12, 'Present', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', '2026-01-31 03:49:39', 0, 0),
(302, 11, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(301, 15, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(300, 18, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(299, 17, 'Absent', 'Sto. Rosario', '2026-01-31', '2026-01-31 01:00:15', NULL, 1, 0),
(298, 16, 'Present', 'Sto. Rosario', '2026-01-31', '2026-01-31 00:59:18', NULL, 0, 0),
(297, 61, 'Present', 'Sto. Rosario', '2026-01-31', '2026-01-31 00:30:26', NULL, 0, 0),
(296, 13, 'Present', 'Sto. Rosario', '2026-01-31', '2026-01-31 00:30:23', NULL, 0, 0),
(295, 62, 'Present', 'Testing', '2026-01-31', '2026-01-31 00:30:21', '2026-01-31 00:59:22', 0, 0),
(294, 14, 'Present', 'Sto. Rosario', '2026-01-31', '2026-01-31 00:30:18', NULL, 0, 0),
(293, 6, 'Present', 'Main Branch', '2026-01-31', '2026-01-31 00:29:57', '2026-01-31 07:57:51', 0, 0),
(292, 0, '', 'System', '2026-01-30', '2026-01-30 07:00:45', NULL, 0, 1),
(291, 73, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(290, 72, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(289, 71, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(288, 70, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(287, 69, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(286, 68, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(285, 67, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(284, 66, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(283, 65, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(282, 64, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(281, 63, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(280, 62, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(279, 61, 'Present', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', '2026-01-30 07:32:53', 0, 0),
(278, 60, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(277, 59, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(276, 58, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(275, 57, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(274, 56, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(273, 55, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(272, 54, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(271, 53, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(270, 52, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(269, 51, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(268, 50, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(267, 49, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(266, 48, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(265, 47, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(264, 46, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(263, 45, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(262, 44, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(261, 43, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(260, 42, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(259, 41, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(258, 40, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(257, 39, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(256, 38, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(255, 37, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(254, 36, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(253, 35, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(252, 34, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(251, 33, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(250, 32, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(249, 31, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(248, 30, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(247, 29, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(246, 28, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(245, 27, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(244, 26, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(243, 25, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(242, 24, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(241, 23, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(240, 22, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(239, 21, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(238, 20, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(237, 19, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(236, 13, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(235, 12, 'Present', 'Testing', '2026-01-30', '2026-01-30 07:00:45', '2026-01-30 07:01:44', 0, 0),
(234, 11, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(233, 15, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(232, 6, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(231, 14, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(230, 18, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(229, 17, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0),
(228, 16, 'Absent', 'Sto. Rosario', '2026-01-30', '2026-01-30 07:00:45', NULL, 1, 0);

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
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `branch_name`, `created_at`, `is_active`) VALUES
(10, 'Sto. Rosario', '2026-01-29 03:19:23', 1),
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

--
-- Dumping data for table `branch_reset_log`
--

INSERT INTO `branch_reset_log` (`id`, `reset_date`, `employees_affected`, `created_at`) VALUES
(1, '2026-02-03', 0, '2026-02-03 00:00:00');

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
) ENGINE=MyISAM AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_code`, `first_name`, `middle_name`, `last_name`, `email`, `password_hash`, `position`, `status`, `created_at`, `updated_at`, `profile_image`, `daily_rate`) VALUES
(16, 'E0006', 'ALFREDO', NULL, 'BAGUIO', 'alfredo.baguio@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 550.00),
(17, 'E0007', 'ROLLY', NULL, 'BALTAZAR', 'rolly.baltazar@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 500.00),
(18, 'E0008', 'DONG', NULL, 'BAUTISTA', 'dong.bautista@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(14, 'E0004', 'NOEL', NULL, 'ARIZ', 'noel.ariz@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 550.00),
(6, 'SA001', 'Super', NULL, 'Admin', 'admin@jajrconstruction.com', '1c7185fa20725ab79607c05a7a8d0c5f', 'Admin', 'Active', '2026-01-16 02:26:58', '2026-01-31 06:22:18', 'profile_697d9f9a1f47a8.96968556.png', 600.00),
(15, 'E0005', 'DANIEL', NULL, 'BACHILLER', 'daniel.bachiller@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(11, 'E0001', 'AARIZ', NULL, 'MARLOU', 'aariz.marlou@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(12, 'E0002', 'CESAR', NULL, 'ABUBO', 'cesar.abubo@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-31 05:42:05', 'profile_697d962d450256.84780797.png', 600.00),
(13, 'E0003', 'MARLON', NULL, 'AGUILAR', 'marlon.aguilar@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(19, 'E0009', 'JANLY', NULL, 'BELINO', 'janly.belino@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(20, 'E0010', 'MENUEL', NULL, 'BENITEZ', 'menuel.benitez@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(21, 'E0011', 'GELMAR', NULL, 'BERNACHEA', 'gelmar.bernachea@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(22, 'E0012', 'JOMAR', NULL, 'CABANBAN', 'jomar.cabanban@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(23, 'E0013', 'MARIO', NULL, 'CABANBAN', 'mario.cabanban@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(24, 'E0014', 'KELVIN', NULL, 'CALDERON', 'kelvin.calderon@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 500.00),
(25, 'E0015', 'FLORANTE', NULL, 'CALUZA', 'florante.caluza@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(26, 'E0016', 'MELVIN', NULL, 'CAMPOS', 'melvin.campos@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(27, 'E0017', 'JERWIN', NULL, 'CAMPOS', 'jerwin.campos@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(28, 'E0018', 'BENJIE', NULL, 'CARAS', 'benjie.caras@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 700.00),
(29, 'E0019', 'BONJO', NULL, 'DACUMOS', 'bonjo.dacumos@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(30, 'E0020', 'RYAN', NULL, 'DEOCARIS', 'ryan.deocaris@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 500.00),
(31, 'E0021', 'BEN', NULL, 'ESTEPA', 'ben.estepa@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(32, 'E0022', 'MAR DAVE', NULL, 'FLORES', 'mardave.flores@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 550.00),
(33, 'E0023', 'ALBERT', NULL, 'FONTANILLA', 'albert.fontanilla@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(34, 'E0024', 'JOHN WILSON', NULL, 'FONTANILLA', 'johnwilson.fontanilla@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(35, 'E0025', 'LEO', NULL, 'GURTIZA', 'leo.gurtiza@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(36, 'E0026', 'JOSE', NULL, 'IGLECIAS', 'jose.iglecias@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 500.00),
(37, 'E0027', 'JEFFREY', NULL, 'JIMENEZ', 'jeffrey.jimenez@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 550.00),
(38, 'E0028', 'WILSON', NULL, 'LICTAOA', 'wilson.lictaoa@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 500.00),
(39, 'E0029', 'LORETO', NULL, 'MABALO', 'loreto.mabalo@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(40, 'E0030', 'ROMEL', NULL, 'MALLARE', 'romel.mallare@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(41, 'E0031', 'SAMUEL SR.', NULL, 'MARQUEZ', 'samuel.marquez@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 500.00),
(42, 'E0032', 'ROLLY', NULL, 'MARZAN', 'rolly.marzan@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(43, 'E0033', 'RONALD', NULL, 'MARZAN', 'ronald.marzan@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(44, 'E0034', 'WILSON', NULL, 'MARZAN', 'wilson.marzan@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(45, 'E0035', 'MARVIN', NULL, 'MIRANDA', 'marvin.miranda@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(46, 'E0036', 'JOE', NULL, 'MONTERDE', 'joe.monterde@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 700.00),
(47, 'E0037', 'ALDRED', NULL, 'NATARTE', 'aldred.natarte@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(48, 'E0038', 'ARNOLD', NULL, 'NERIDO', 'arnold.nerido@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(49, 'E0039', 'RONEL', NULL, 'NOSES', 'ronel.noses@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(50, 'E0040', 'DANNY', NULL, 'PADILLA', 'danny.padilla@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 500.00),
(51, 'E0041', 'EDGAR', NULL, 'PANEDA', 'edgar.paneda@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 550.00),
(52, 'E0042', 'JEREMY', NULL, 'PIMENTEL', 'jeremy.pimentel@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 550.00),
(53, 'E0043', 'MIGUEL', NULL, 'PREPOSI', 'miguel.preposi@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(54, 'E0044', 'JUN', NULL, 'ROAQUIN', 'jun.roaquin@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(55, 'E0045', 'RICKMAR', NULL, 'SANTOS', 'rickmar.santos@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(56, 'E0046', 'RIO', NULL, 'SILOY', 'rio.siloy@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(57, 'E0047', 'NORMAN', NULL, 'TARAPE', 'norman.tarape@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(58, 'E0048', 'HILMAR', NULL, 'TATUNAY', 'hilmar.tatunay@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(59, 'E0049', 'KENNETH JOHN', NULL, 'UGAS', 'kennethjohn.ugas@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(60, 'E0050', 'CLYDE JUSTINE', NULL, 'VASADRE', 'clydejustine.vasadre@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 500.00),
(61, 'E0051', 'CARL JHUNELL', NULL, 'ACAS', 'carljhunell.acas@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-31 06:22:11', 'profile_697d9f9350dad3.88439854.png', 600.00),
(62, 'E0052', 'ELAINE MARICRIS T.', NULL, 'AGUILAR', 'elainemaricris.aguilar@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-30 07:31:38', 'profile_697c5e5a1459b9.21959357.png', 600.00),
(63, 'E0053', 'JOYLENE F.', NULL, 'BALANON', 'joylene.balanon@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(64, 'E0054', 'AVERIE L.', NULL, 'CABUSORA', 'averie.cabusora@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(65, 'E0055', 'JOHN JULIUS', NULL, 'ECHAGUE', 'johnjulius.echague@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(66, 'E0056', 'JOHN KENEDY', NULL, 'LUCAS', 'johnkenedy.lucas@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(67, 'E0057', 'RONALYN', NULL, 'MALLARE', 'ronalyn.mallare@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(68, 'ENG - 2026 - 0001', 'MICHELLE F.', NULL, 'NORIAL', 'michelle.norial@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-02-02 03:43:02', NULL, 600.00),
(69, 'E0059', 'WINNIELYN KAYE M.', NULL, 'OLARTE', 'winnielynkaye.olarte@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(70, 'E0060', 'FLORIAN JANE', NULL, 'PERALTA', 'florianjane.peralta@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(71, 'E0061', 'DANIEL', NULL, 'RILLERA', 'daniel.rillera@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(72, 'E0062', 'JUNELL', NULL, 'TADINA', 'junell.tadina@example.com', 'Sto. Rosario', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Employee', 'Active', '2026-01-22 07:58:04', '2026-01-29 03:16:06', NULL, 600.00),
(73, 'e10001', 'Marc', 'Espanto', 'Arzadon', 'you@gmail.com', 'Sto. Rosario', 'e10adc3949ba59abbe56e057f20f883e', 'Employee', 'Active', '2026-01-27 03:08:02', '2026-01-29 03:16:06', NULL, 600.00);

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
