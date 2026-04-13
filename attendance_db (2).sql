-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 13, 2026 at 05:54 AM
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
) ENGINE=InnoDB AUTO_INCREMENT=965 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(610, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-23 02:57:42'),
(611, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-23 03:09:16'),
(612, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-02-24 06:24:00'),
(613, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-02-24 06:34:37'),
(614, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-02-24 06:42:43'),
(615, 68, 'Logged In', 'User MICHELLE F. NORIAL logged in from branch: Main Branch', '::1', '2026-02-24 07:13:55'),
(616, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-02-24 07:16:50'),
(617, 117, 'Overtime Pre-Approved', 'Admin Admin pre-approved overtime #10 for 5.00 hours on 2026-02-24', '::1', '2026-02-24 07:18:32'),
(618, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-24 07:18:46'),
(619, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-02-24 08:43:59'),
(620, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-24 08:44:16'),
(621, 68, 'Logged In', 'User MICHELLE F. NORIAL logged in from branch: Main Branch', '::1', '2026-02-24 08:54:50'),
(622, 68, 'Clocked In', 'Employee #24 clocked in at BCDA - Admin', '::1', '2026-02-24 08:54:58'),
(623, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-02-24 08:55:12'),
(624, 117, 'Overtime Pre-Approved', 'Admin Admin pre-approved overtime #11 for 4.00 hours on 2026-02-24', '::1', '2026-02-24 08:55:17'),
(625, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-24 08:55:26'),
(626, 68, 'Logged In', 'User MICHELLE F. NORIAL logged in from branch: Main Branch', '::1', '2026-02-24 08:58:40'),
(627, 68, 'Clocked Out', 'Employee #24 clocked out, worked 0.07 hours', '::1', '2026-02-24 08:58:52'),
(628, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-02-24 08:59:04'),
(629, 117, 'Overtime Pre-Approved', 'Admin Admin pre-approved overtime #12 for 45.00 hours on 2026-02-24', '::1', '2026-02-24 08:59:07'),
(630, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-24 08:59:24'),
(631, 68, 'Logged In', 'User MICHELLE F. NORIAL logged in from branch: Main Branch', '::1', '2026-02-24 23:49:05'),
(632, 68, 'Clocked In', 'Employee #24 clocked in at BCDA - Admin', '::1', '2026-02-24 23:49:14'),
(633, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-02-24 23:49:32'),
(634, 117, 'Overtime Pre-Approved', 'Admin Admin pre-approved overtime #13 for 2.00 hours on 2026-02-25', '::1', '2026-02-24 23:49:43'),
(635, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-24 23:49:52'),
(636, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-24 23:57:08'),
(637, 68, 'Logged In', 'User MICHELLE F. NORIAL logged in from branch: Main Branch', '::1', '2026-02-25 00:02:47'),
(638, 68, 'Clocked In', 'Employee #27 clocked in at BCDA - Admin', '::1', '2026-02-25 00:03:07'),
(639, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-02-25 00:03:27'),
(640, 117, 'Overtime Pre-Approved', 'Admin Admin pre-approved overtime #14 for 2.00 hours on 2026-02-25', '::1', '2026-02-25 00:03:31'),
(641, 117, 'Overtime Pre-Approved', 'Admin Admin pre-approved overtime #13 for 2.00 hours on 2026-02-25', '::1', '2026-02-25 00:03:33'),
(642, 117, 'Overtime Pre-Approved', 'Admin Admin pre-approved overtime #12 for 45.00 hours on 2026-02-24', '::1', '2026-02-25 00:03:34'),
(643, 117, 'Overtime Pre-Approved', 'Admin Admin pre-approved overtime #11 for 4.00 hours on 2026-02-24', '::1', '2026-02-25 00:03:35'),
(644, 117, 'Overtime Pre-Approved', 'Admin Admin pre-approved overtime #10 for 5.00 hours on 2026-02-24', '::1', '2026-02-25 00:03:35'),
(645, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-25 00:03:54'),
(646, 6, 'Overtime Approved', 'Super Admin approved overtime #14 for 2.00 hours on 2026-02-25', '::1', '2026-02-25 00:10:11'),
(647, 6, 'Overtime Approved', 'Super Admin approved overtime #13 for 2.00 hours on 2026-02-25', '::1', '2026-02-25 00:10:14'),
(648, 6, 'Overtime Approved', 'Super Admin approved overtime #12 for 45.00 hours on 2026-02-24', '::1', '2026-02-25 00:10:15'),
(649, 6, 'Overtime Approved', 'Super Admin approved overtime #11 for 4.00 hours on 2026-02-24', '::1', '2026-02-25 00:10:15'),
(650, 6, 'Overtime Approved', 'Super Admin approved overtime #10 for 5.00 hours on 2026-02-24', '::1', '2026-02-25 00:10:15'),
(651, 68, 'Logged In', 'User MICHELLE F. NORIAL logged in from branch: Main Branch', '::1', '2026-02-25 00:10:25'),
(652, 68, 'Notification Marked Read', 'User marked notification #71 as read', '::1', '2026-02-25 00:10:29'),
(653, 68, 'Notification Marked Read', 'User marked notification #66 as read', '::1', '2026-02-25 00:10:32'),
(654, 68, 'Notification Marked Read', 'User marked notification #61 as read', '::1', '2026-02-25 00:10:33'),
(655, 68, 'Notification Marked Read', 'User marked notification #82 as read', '::1', '2026-02-25 00:10:35'),
(656, 68, 'Notification Marked Read', 'User marked notification #86 as read', '::1', '2026-02-25 00:10:36'),
(657, 68, 'Notification Marked Read', 'User marked notification #83 as read', '::1', '2026-02-25 00:10:37'),
(658, 68, 'Notification Marked Read', 'User marked notification #85 as read', '::1', '2026-02-25 00:10:38'),
(659, 68, 'Notification Marked Read', 'User marked notification #84 as read', '::1', '2026-02-25 00:10:38'),
(660, 68, 'Notification Marked Read', 'User marked notification #76 as read', '::1', '2026-02-25 00:10:39'),
(661, 68, 'Notification Marked Read', 'User marked notification #25 as read', '::1', '2026-02-25 00:10:41'),
(662, 68, 'Notification Marked Read', 'User marked notification #34 as read', '::1', '2026-02-25 00:10:42'),
(663, 68, 'Notification Marked Read', 'User marked notification #43 as read', '::1', '2026-02-25 00:10:43'),
(664, 68, 'Notification Marked Read', 'User marked notification #52 as read', '::1', '2026-02-25 00:10:44'),
(665, 68, 'Notification Marked Read', 'User marked notification #81 as read', '::1', '2026-02-25 00:10:45'),
(666, 68, 'Overtime Requested', 'Engineer requested 1 hours overtime on 2026-02-26 at BCDA - Admin', '::1', '2026-02-25 00:10:55'),
(667, 68, 'Logged In', 'User MICHELLE F. NORIAL logged in from branch: Main Branch', '::1', '2026-02-25 00:20:17'),
(668, 68, 'Notification Marked Read', 'User marked notification #87 as read', '::1', '2026-02-25 00:20:21'),
(669, 68, 'Overtime Requested', 'Engineer requested 1 hours overtime on 2026-02-26 at BCDA - CCTV', '::1', '2026-02-25 00:20:31'),
(670, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-02-25 00:20:51'),
(671, 117, 'Overtime Pre-Approved', 'Admin Admin pre-approved overtime #16 for 1.00 hours on 2026-02-26', '::1', '2026-02-25 00:21:05'),
(672, 117, 'Overtime Pre-Approved', 'Admin Admin pre-approved overtime #15 for 1.00 hours on 2026-02-26', '::1', '2026-02-25 00:21:07'),
(673, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-25 00:21:23'),
(674, 6, 'Overtime Rejected', 'Super Admin rejected overtime #16. Reason: dehrst', '::1', '2026-02-25 00:21:33'),
(675, 68, 'Logged In', 'User MICHELLE F. NORIAL logged in from branch: Main Branch', '::1', '2026-02-25 00:22:05'),
(676, 68, 'Notification Marked Read', 'User marked notification #107 as read', '::1', '2026-02-25 00:22:08'),
(677, 68, 'Notification Marked Read', 'User marked notification #106 as read', '::1', '2026-02-25 00:22:09'),
(678, 68, 'Notification Deleted', 'User deleted notification #107', '::1', '2026-02-25 00:22:11'),
(679, 68, 'Notification Deleted', 'User deleted notification #106', '::1', '2026-02-25 00:22:12'),
(680, 68, 'Notification Deleted', 'User deleted notification #101', '::1', '2026-02-25 00:22:12'),
(681, 68, 'Notification Deleted', 'User deleted notification #92', '::1', '2026-02-25 00:22:12'),
(682, 68, 'Notification Deleted', 'User deleted notification #87', '::1', '2026-02-25 00:22:13'),
(683, 68, 'Notification Deleted', 'User deleted notification #84', '::1', '2026-02-25 00:22:13'),
(684, 68, 'Notification Deleted', 'User deleted notification #85', '::1', '2026-02-25 00:22:14'),
(685, 68, 'Notification Deleted', 'User deleted notification #86', '::1', '2026-02-25 00:22:14'),
(686, 68, 'Notification Deleted', 'User deleted notification #83', '::1', '2026-02-25 00:22:14'),
(687, 68, 'Notification Deleted', 'User deleted notification #82', '::1', '2026-02-25 00:22:15'),
(688, 68, 'Notification Deleted', 'User deleted notification #76', '::1', '2026-02-25 00:22:15'),
(689, 68, 'Notification Deleted', 'User deleted notification #81', '::1', '2026-02-25 00:22:15'),
(690, 68, 'Notification Deleted', 'User deleted notification #71', '::1', '2026-02-25 00:22:16'),
(691, 68, 'Notification Deleted', 'User deleted notification #66', '::1', '2026-02-25 00:22:16'),
(692, 68, 'Notification Deleted', 'User deleted notification #61', '::1', '2026-02-25 00:22:16'),
(693, 68, 'Notification Deleted', 'User deleted notification #52', '::1', '2026-02-25 00:22:17'),
(694, 68, 'Notification Deleted', 'User deleted notification #43', '::1', '2026-02-25 00:22:17'),
(695, 68, 'Notification Deleted', 'User deleted notification #34', '::1', '2026-02-25 00:22:18'),
(696, 68, 'Notification Deleted', 'User deleted notification #25', '::1', '2026-02-25 00:22:18'),
(697, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-25 01:28:42'),
(698, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-25 05:58:43'),
(699, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-25 07:23:20'),
(700, 6, 'Clocked Out', 'Employee #24 clocked out, worked 7.68 hours', '::1', '2026-02-25 07:29:48'),
(701, 6, 'Clocked Out', 'Employee #27 clocked out, worked 7.45 hours', '::1', '2026-02-25 07:29:49'),
(702, 6, 'Overtime Approved', 'Super Admin approved overtime #17 for 5.00 hours on 2026-02-25', '::1', '2026-02-25 08:50:01'),
(703, 6, 'Overtime Approved', 'Super Admin approved overtime #15 for 1.00 hours on 2026-02-26', '::1', '2026-02-25 08:50:04'),
(704, 6, 'Overtime Approved', 'Super Admin approved overtime #18 for 5.00 hours on 2026-02-25', '::1', '2026-02-25 08:50:44'),
(705, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-26 00:06:00'),
(706, 6, 'Clocked In', 'Employee #24 clocked in at BCDA - Admin', '::1', '2026-02-27 04:47:40'),
(707, 6, 'Clocked In', 'Employee #27 clocked in at BCDA - Admin', '::1', '2026-02-27 04:47:41'),
(708, 6, 'Clocked In', 'Employee #26 clocked in at BCDA - Admin', '::1', '2026-02-27 04:47:42'),
(709, 6, 'Clocked In', 'Employee #36 clocked in at BCDA - Admin', '::1', '2026-02-27 04:47:42'),
(710, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-28 00:46:55'),
(711, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-28 01:11:21'),
(712, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-02-28 02:11:44'),
(713, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-03-05 03:22:45'),
(714, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-03-13 00:42:31'),
(715, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-03-13 00:54:33'),
(716, 137, 'Logged In', 'User Daniel Rillera logged in from branch: Main Branch', '::1', '2026-03-13 00:58:48'),
(717, 137, 'Logged In', 'User Daniel Rillera logged in from branch: Main Branch', '::1', '2026-03-13 00:59:12'),
(718, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-03-13 02:39:53'),
(719, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-03-13 02:40:27'),
(720, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-03-13 02:40:55'),
(721, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-03-13 02:47:52'),
(722, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-03-13 02:48:22'),
(723, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-03-13 02:50:11'),
(724, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-03-13 02:51:01'),
(725, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-03-13 02:51:22'),
(726, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-03-13 02:52:32'),
(727, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-03-13 02:53:00'),
(728, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-03-13 02:54:39'),
(729, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-03-13 02:55:41'),
(730, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-03-13 03:02:13'),
(731, 137, 'Logged In', 'User Daniel Rillera logged in from branch: Main Branch', '::1', '2026-03-13 04:28:16'),
(732, 137, 'Clocked In', 'Employee #137 clocked in at MAIN OFFICE', '::1', '2026-03-13 04:31:10'),
(733, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-03-13 06:17:35'),
(734, 137, 'Logged In', 'User Daniel Rillera logged in from branch: Main Branch', '127.0.0.1', '2026-03-14 01:21:39'),
(735, 137, 'Logged In', 'User Daniel Rillera logged in from branch: Main Branch', '::1', '2026-03-14 02:02:15'),
(736, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-03-14 02:02:42'),
(737, 137, 'Logged In', 'User Daniel Rillera logged in from branch: Main Branch', '::1', '2026-03-14 03:03:36'),
(738, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-03-14 03:04:15'),
(739, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-03-14 03:17:20'),
(740, 68, 'Logged In', 'User MICHELLE F. NORIAL logged in from branch: Main Branch', '::1', '2026-03-14 05:41:24'),
(741, 68, 'Notification Marked Read', 'User marked notification #113 as read', '::1', '2026-03-14 05:41:30'),
(742, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-03-20 00:35:39'),
(743, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-03-24 01:57:32'),
(744, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-03-25 02:07:43'),
(745, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-03-27 02:03:27'),
(746, 6, 'Clocked In', 'Employee #12 clocked in at Sto. Rosario', '::1', '2026-03-27 03:21:16'),
(747, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-03-28 00:33:51'),
(748, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-03-28 05:56:38'),
(749, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-03-28 05:57:02'),
(750, 63, 'Logged In', 'User JOYLENE F. BALANON logged in from branch: Main Branch', '::1', '2026-03-28 06:16:25'),
(751, 63, 'Leave Requested', 'Engineer requested 1 day(s) leave on 2026-03-30', '::1', '2026-03-28 06:16:51'),
(752, 63, 'Leave Requested', 'Engineer requested 1 day(s) leave on 2026-03-31', '::1', '2026-03-28 06:17:44'),
(753, 117, 'Leave Rejected', 'Admin Admin rejected leave #2. Reason: pasensya kan ta madi', '::1', '2026-03-28 06:18:03'),
(754, 63, 'Notification Marked Read', 'User marked notification #138 as read', '::1', '2026-03-28 06:18:10'),
(755, 63, 'Notification Marked Read', 'User marked notification #129 as read', '::1', '2026-03-28 06:18:12'),
(756, 63, 'Notification Marked Read', 'User marked notification #128 as read', '::1', '2026-03-28 06:18:12'),
(757, 63, 'Notification Marked Read', 'User marked notification #119 as read', '::1', '2026-03-28 06:18:13'),
(758, 63, 'Leave Requested', 'Engineer requested 1 day(s) leave on 2026-04-02', '::1', '2026-03-28 06:18:52'),
(759, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-03-29 23:59:59'),
(760, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-03-31 06:03:40'),
(761, 1, 'Geofence QA Test', 'Geofence Test: CASE 1: Inside Geofence (Worker) | Employee: 1 | Location: 14.60955, 121.0223 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:17:46'),
(762, 1, 'Geofence QA Test', 'Geofence Test: CASE 2: Outside Geofence (Worker) - Should BLOCK | Employee: 1 | Location: 14.6111, 121.0243 | Result: PASSED | Action: block', '127.0.0.1', '2026-03-31 06:17:46'),
(763, 2, 'Geofence QA Test', 'Geofence Test: CASE 3: Outside Geofence (Manager) - Should allow OVERRIDE | Employee: 2 | Location: 14.6111, 121.0243 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:17:46'),
(764, 1, 'Geofence QA Test', 'Geofence Test: CASE 4: Spoofed Timestamp (2 hours ago) - Should SECURITY BLOCK | Employee: 1 | Location: 14.60955, 121.0223 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:17:46'),
(765, 1, 'Geofence QA Test', 'Geofence Test: CASE 5: Low Accuracy (>500m) - Should ACCURACY BLOCK | Employee: 1 | Location: 14.60955, 121.0223 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:17:46'),
(766, 1, 'Geofence QA Test', 'Geofence Test: CASE 1: Inside Geofence (Worker) | Employee: 1 | Location: 14.60955, 121.0223 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:19:12'),
(767, 1, 'Geofence QA Test', 'Geofence Test: CASE 2: Outside Geofence (Worker) - Should BLOCK | Employee: 1 | Location: 14.6111, 121.0243 | Result: PASSED | Action: block', '127.0.0.1', '2026-03-31 06:19:12'),
(768, 2, 'Geofence QA Test', 'Geofence Test: CASE 3: Outside Geofence (Manager) - Should allow OVERRIDE | Employee: 2 | Location: 14.6111, 121.0243 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:19:12'),
(769, 1, 'Geofence QA Test', 'Geofence Test: CASE 4: Spoofed Timestamp (2 hours ago) - Should SECURITY BLOCK | Employee: 1 | Location: 14.60955, 121.0223 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:19:12'),
(770, 1, 'Geofence QA Test', 'Geofence Test: CASE 5: Low Accuracy (>500m) - Should ACCURACY BLOCK | Employee: 1 | Location: 14.60955, 121.0223 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:19:12'),
(771, 1, 'Geofence QA Test', 'Geofence Test: CASE 1: Inside Geofence (Worker) | Employee: 1 | Location: 14.60955, 121.0223 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:21:11'),
(772, 1, 'Geofence QA Test', 'Geofence Test: CASE 2: Outside Geofence (Worker) - Should BLOCK | Employee: 1 | Location: 14.6111, 121.0243 | Result: PASSED | Action: block', '127.0.0.1', '2026-03-31 06:21:11'),
(773, 2, 'Geofence QA Test', 'Geofence Test: CASE 3: Outside Geofence (Manager) - Should allow OVERRIDE | Employee: 2 | Location: 14.6111, 121.0243 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:21:11'),
(774, 1, 'Geofence QA Test', 'Geofence Test: CASE 4: Spoofed Timestamp (2 hours ago) - Should SECURITY BLOCK | Employee: 1 | Location: 14.60955, 121.0223 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:21:11'),
(775, 1, 'Geofence QA Test', 'Geofence Test: CASE 5: Low Accuracy (>500m) - Should ACCURACY BLOCK | Employee: 1 | Location: 14.60955, 121.0223 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:21:11'),
(776, 1, 'Geofence QA Test', 'Geofence Test: CASE 1: Inside Geofence (Worker) | Employee: 1 | Location: 14.60955, 121.0223 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:21:14'),
(777, 1, 'Geofence QA Test', 'Geofence Test: CASE 2: Outside Geofence (Worker) - Should BLOCK | Employee: 1 | Location: 14.6111, 121.0243 | Result: PASSED | Action: block', '127.0.0.1', '2026-03-31 06:21:14'),
(778, 2, 'Geofence QA Test', 'Geofence Test: CASE 3: Outside Geofence (Manager) - Should allow OVERRIDE | Employee: 2 | Location: 14.6111, 121.0243 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:21:14'),
(779, 1, 'Geofence QA Test', 'Geofence Test: CASE 4: Spoofed Timestamp (2 hours ago) - Should SECURITY BLOCK | Employee: 1 | Location: 14.60955, 121.0223 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:21:14'),
(780, 1, 'Geofence QA Test', 'Geofence Test: CASE 5: Low Accuracy (>500m) - Should ACCURACY BLOCK | Employee: 1 | Location: 14.60955, 121.0223 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:21:14'),
(781, 1, 'Geofence QA Test', 'Geofence Test: CASE 1: Inside Geofence (Worker) | Employee: 1 | Location: 16.5974275, 120.3077657 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:24:39'),
(782, 1, 'Geofence QA Test', 'Geofence Test: CASE 2: Outside Geofence (Worker) - Should BLOCK | Employee: 1 | Location: 16.5989775, 120.3097657 | Result: PASSED | Action: block', '127.0.0.1', '2026-03-31 06:24:39'),
(783, 2, 'Geofence QA Test', 'Geofence Test: CASE 3: Outside Geofence (Manager) - Should allow OVERRIDE | Employee: 2 | Location: 16.5989775, 120.3097657 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:24:39'),
(784, 1, 'Geofence QA Test', 'Geofence Test: CASE 4: Spoofed Timestamp (2 hours ago) - Should SECURITY BLOCK | Employee: 1 | Location: 16.5974275, 120.3077657 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:24:39'),
(785, 1, 'Geofence QA Test', 'Geofence Test: CASE 5: Low Accuracy (>500m) - Should ACCURACY BLOCK | Employee: 1 | Location: 16.5974275, 120.3077657 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:24:39'),
(786, 1, 'Geofence QA Test', 'Geofence Test: CASE 1: Inside Geofence (Worker) | Employee: 1 | Location: 16.5974275, 120.3077657 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:24:42'),
(787, 1, 'Geofence QA Test', 'Geofence Test: CASE 2: Outside Geofence (Worker) - Should BLOCK | Employee: 1 | Location: 16.5989775, 120.3097657 | Result: PASSED | Action: block', '127.0.0.1', '2026-03-31 06:24:42'),
(788, 2, 'Geofence QA Test', 'Geofence Test: CASE 3: Outside Geofence (Manager) - Should allow OVERRIDE | Employee: 2 | Location: 16.5989775, 120.3097657 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:24:42'),
(789, 1, 'Geofence QA Test', 'Geofence Test: CASE 4: Spoofed Timestamp (2 hours ago) - Should SECURITY BLOCK | Employee: 1 | Location: 16.5974275, 120.3077657 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:24:42'),
(790, 1, 'Geofence QA Test', 'Geofence Test: CASE 5: Low Accuracy (>500m) - Should ACCURACY BLOCK | Employee: 1 | Location: 16.5974275, 120.3077657 | Result: FAILED | Action: block', '127.0.0.1', '2026-03-31 06:24:42'),
(791, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-04-01 07:19:51'),
(792, 68, 'Logged In', 'User MICHELLE F. NORIAL logged in from branch: Main Branch', '::1', '2026-04-01 07:20:09'),
(793, 68, 'Overtime Requested', 'Engineer requested 1 hours overtime on 2026-04-01 at MAIN OFFICE', '::1', '2026-04-01 07:20:36'),
(794, 68, 'Cash Advance Requested', 'Engineer requested ₱1234 cash advance - asdf', '::1', '2026-04-01 07:20:37'),
(795, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-04-01 07:20:57'),
(796, 117, 'Overtime Pre-Approved', 'Admin Admin pre-approved overtime #19 for 1.00 hours on 2026-04-01', '::1', '2026-04-01 07:21:18'),
(797, 117, 'Cash Advance Pre-Approved', 'Admin Admin pre-approved cash advance #15', '::1', '2026-04-01 07:21:22'),
(798, 68, 'Logged In', 'User MICHELLE F. NORIAL logged in from branch: Main Branch', '::1', '2026-04-01 07:21:38'),
(799, 68, 'Overtime Requested', 'Engineer requested 3 hours overtime on 2026-04-02 at BCDA - CCA', '::1', '2026-04-01 07:21:48'),
(800, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-04-01 07:21:58'),
(801, 117, 'Overtime Approved', 'Admin Admin approved overtime #20 for 3.00 hours', '::1', '2026-04-01 07:24:59'),
(802, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-04-06 00:03:12'),
(803, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-04-07 03:08:00'),
(804, 6, 'Clocked In', 'Employee #24 clocked in at BCDA - Admin', '::1', '2026-04-07 03:12:49'),
(805, 6, 'Clocked In', 'Employee #27 clocked in at BCDA - Admin', '::1', '2026-04-07 03:12:50'),
(806, 6, 'Clocked In', 'Employee #26 clocked in at BCDA - Admin', '::1', '2026-04-07 03:12:52'),
(807, 6, 'Clocked In', 'Employee #30 clocked in at BCDA - Admin', '::1', '2026-04-07 03:12:52'),
(808, 6, 'Clocked In', 'Employee #36 clocked in at BCDA - Admin', '::1', '2026-04-07 03:12:53'),
(809, 6, 'Clocked In', 'Employee #38 clocked in at BCDA - Admin', '::1', '2026-04-07 03:12:54'),
(810, 6, 'Clocked In', 'Employee #11 clocked in at BCDA - Admin', '::1', '2026-04-07 03:12:54'),
(811, 6, 'Clocked In', 'Employee #13 clocked in at BCDA - CCA', '::1', '2026-04-07 03:12:56'),
(812, 6, 'Clocked In', 'Employee #15 clocked in at BCDA - CCA', '::1', '2026-04-07 03:12:57'),
(813, 6, 'Clocked In', 'Employee #18 clocked in at BCDA - CCA', '::1', '2026-04-07 03:12:59'),
(814, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-04-08 01:35:34'),
(815, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '127.0.0.1', '2026-04-08 03:10:51'),
(816, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-04-09 00:22:48'),
(817, 6, 'Clocked In', 'Employee #63 clocked in at BCDA - Admin', '::1', '2026-04-09 00:23:08'),
(818, 6, 'Clocked In', 'Employee #27 clocked in at BCDA - Admin', '::1', '2026-04-09 00:23:09'),
(819, 6, 'Clocked In', 'Employee #30 clocked in at BCDA - Admin', '::1', '2026-04-09 00:23:10'),
(820, 6, 'Clocked In', 'Employee #42 clocked in at BCDA - Admin', '::1', '2026-04-09 00:23:12'),
(821, 6, 'Clocked In', 'Employee #44 clocked in at BCDA - Admin', '::1', '2026-04-09 00:23:12'),
(822, 6, 'Clocked Out', 'Employee #27 clocked out, worked 0 hours', '::1', '2026-04-09 00:23:18'),
(823, 6, 'Clocked Out', 'Employee #63 clocked out, worked 0 hours', '::1', '2026-04-09 00:23:18'),
(824, 6, 'Clocked In', 'Employee #24 clocked in at BCDA - Admin', '::1', '2026-04-09 01:54:19'),
(825, 6, 'Clocked In', 'Employee #11 clocked in at BCDA - Admin', '::1', '2026-04-09 01:54:19'),
(826, 6, 'Clocked In', 'Employee #27 clocked in at BCDA - Admin', '::1', '2026-04-09 01:54:20'),
(827, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '127.0.0.1', '2026-04-09 02:15:36'),
(828, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-04-09 02:40:31'),
(829, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-04-09 02:41:44'),
(830, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-04-09 02:45:46'),
(831, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-04-09 03:06:25'),
(832, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-04-09 03:06:29'),
(833, 68, 'Logged In', 'User MICHELLE F. NORIAL logged in from branch: Main Branch', '::1', '2026-04-09 03:06:56'),
(834, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-04-09 03:08:00'),
(835, 117, 'Clocked In', 'Employee #26 clocked in at BCDA - Admin', '::1', '2026-04-09 03:09:06'),
(836, 117, 'Clocked In', 'Employee #63 clocked in at BCDA - Admin', '::1', '2026-04-09 03:09:06'),
(837, 117, 'Overtime Requested', 'Engineer requested 2 hours overtime on 2026-04-08 at BCDA - Admin', '::1', '2026-04-09 03:11:23'),
(838, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-04-09 03:37:09'),
(839, 117, 'Overtime Requested', 'Engineer requested 2 hours overtime on 2026-04-10 at BCDA - CCTV', '::1', '2026-04-09 03:42:45'),
(840, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-04-09 03:46:48'),
(841, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-04-09 03:48:35'),
(842, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-04-09 03:52:50'),
(843, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-04-09 07:22:07'),
(844, 6, 'Clocked In', 'Employee #132 clocked in at BCDA - Admin', '::1', '2026-04-09 07:56:31'),
(845, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-04-10 00:21:27'),
(846, 6, 'Clocked In', 'Employee #63 clocked in at BCDA - Admin', '::1', '2026-04-10 00:21:33'),
(847, 6, 'Clocked In', 'Employee #26 clocked in at BCDA - Admin', '::1', '2026-04-10 00:21:36'),
(848, 6, 'Clocked In', 'Employee #30 clocked in at BCDA - Admin', '::1', '2026-04-10 00:21:37'),
(849, 6, 'Clocked Out', 'Employee #63 clocked out, worked 0 hours', '::1', '2026-04-10 00:21:40'),
(850, 6, 'Clocked Out', 'Employee #26 clocked out, worked 0 hours', '::1', '2026-04-10 00:21:41'),
(851, 6, 'Clocked Out', 'Employee #30 clocked out, worked 0 hours', '::1', '2026-04-10 00:21:42'),
(852, 6, 'Clocked In', 'Employee #63 clocked in at BCDA - Admin', '::1', '2026-04-10 00:21:46'),
(853, 6, 'Clocked In', 'Employee #26 clocked in at BCDA - Admin', '::1', '2026-04-10 00:21:47'),
(854, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-04-10 00:58:49'),
(855, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-04-10 01:18:49'),
(856, 117, 'Logged In', 'User ELAINE Aguilar logged in from branch: Main Branch', '::1', '2026-04-10 01:23:43'),
(857, 117, 'Clocked Out', 'Employee #63 clocked out, worked 1.37 hours', '::1', '2026-04-10 01:43:41'),
(858, 117, 'Clocked In', 'Employee #63 clocked in at BCDA - CCA', '::1', '2026-04-10 01:43:43'),
(859, 117, 'Clocked In', 'Employee #30 clocked in at BCDA - Admin', '::1', '2026-04-10 01:44:03'),
(860, 117, 'Clocked In', 'Employee #36 clocked in at BCDA - Admin', '::1', '2026-04-10 01:44:04'),
(861, 117, 'Clocked Out', 'Employee #26 clocked out, worked 1.37 hours', '::1', '2026-04-10 01:44:07'),
(862, 117, 'Clocked Out', 'Employee #30 clocked out, worked 0 hours', '::1', '2026-04-10 01:44:09'),
(863, 117, 'Clocked In', 'Employee #26 clocked in at BCDA - Admin', '::1', '2026-04-10 01:44:11'),
(864, 117, 'Clocked In', 'Employee #30 clocked in at BCDA - Admin', '::1', '2026-04-10 01:44:23'),
(865, 117, 'Overtime Approved', 'Admin Admin approved overtime #22 for 2.00 hours', '::1', '2026-04-10 05:34:45'),
(866, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-04-10 08:25:56'),
(867, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '127.0.0.1', '2026-04-11 00:01:59'),
(868, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-04-11 02:01:57'),
(869, 6, 'Overtime Approved', 'Super Admin approved overtime #21 for 2.00 hours on 2026-04-08', '::1', '2026-04-11 02:03:54'),
(870, 6, 'Clocked In', 'Employee #24 clocked in at BCDA - Admin', '::1', '2026-04-11 02:03:58'),
(871, 6, 'Overtime Approved', 'Super Admin approved overtime #23 for 3.00 hours on 2026-04-11', '::1', '2026-04-11 02:04:14'),
(872, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee ALFREDO BAGUIO (E0006)', 'Unknown', '2026-04-11 03:58:35'),
(873, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee ROLLY BALTAZAR (E0007)', 'Unknown', '2026-04-11 03:58:35'),
(874, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee DONG BAUTISTA (E0008)', 'Unknown', '2026-04-11 03:58:35'),
(875, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee NOEL ARIZ (E0004)', 'Unknown', '2026-04-11 03:58:35'),
(876, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee Super Adminesu (SA001)', 'Unknown', '2026-04-11 03:58:35'),
(877, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee DANIEL BACHILLER (E0005)', 'Unknown', '2026-04-11 03:58:35'),
(878, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee AARIZ MARLOU (E0001)', 'Unknown', '2026-04-11 03:58:35'),
(879, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee CESAR ABUBO (E0002)', 'Unknown', '2026-04-11 03:58:35'),
(880, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee MARLON AGUILAR (E0003)', 'Unknown', '2026-04-11 03:58:35'),
(881, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee JANLY BELINO (E0009)', 'Unknown', '2026-04-11 03:58:35'),
(882, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee MENUEL BENITEZ (E0010)', 'Unknown', '2026-04-11 03:58:35'),
(883, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee GELMAR BERNACHEA (E0011)', 'Unknown', '2026-04-11 03:58:35'),
(884, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee JOMAR CABANBAN (E0012)', 'Unknown', '2026-04-11 03:58:35'),
(885, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee MARIO CABANBAN (E0013)', 'Unknown', '2026-04-11 03:58:35'),
(886, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee KELVIN CALDERON (E0014)', 'Unknown', '2026-04-11 03:58:35'),
(887, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee FLORANTE CALUZA (E0015)', 'Unknown', '2026-04-11 03:58:35'),
(888, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee MELVIN CAMPOS (E0016)', 'Unknown', '2026-04-11 03:58:35'),
(889, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee JERWIN CAMPOS (E0017)', 'Unknown', '2026-04-11 03:58:35'),
(890, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee BENJIE CARAS (E0018)', 'Unknown', '2026-04-11 03:58:35'),
(891, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee BONJO DACUMOS (E0019)', 'Unknown', '2026-04-11 03:58:35'),
(892, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee RYAN DEOCARIS (E0020)', 'Unknown', '2026-04-11 03:58:35'),
(893, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee BEN ESTEPA (E0021)', 'Unknown', '2026-04-11 03:58:35'),
(894, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee MAR DAVE FLORES (E0022)', 'Unknown', '2026-04-11 03:58:35'),
(895, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee ALBERT FONTANILLA (E0023)', 'Unknown', '2026-04-11 03:58:35'),
(896, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee JOHN WILSON FONTANILLA (E0024)', 'Unknown', '2026-04-11 03:58:35'),
(897, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee LEO GURTIZA (E0025)', 'Unknown', '2026-04-11 03:58:35'),
(898, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee JOSE IGLECIAS (E0026)', 'Unknown', '2026-04-11 03:58:35'),
(899, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee JEFFREY JIMENEZ (E0027)', 'Unknown', '2026-04-11 03:58:35'),
(900, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee WILSON LICTAOA (E0028)', 'Unknown', '2026-04-11 03:58:35'),
(901, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee LORETO MABALO (E0029)', 'Unknown', '2026-04-11 03:58:35'),
(902, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee ROMEL MALLARE (E0030)', 'Unknown', '2026-04-11 03:58:35'),
(903, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee SAMUEL SR. MARQUEZ (E0031)', 'Unknown', '2026-04-11 03:58:35'),
(904, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee ROLLY MARZAN (E0032)', 'Unknown', '2026-04-11 03:58:35'),
(905, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee RONALD MARZAN (E0033)', 'Unknown', '2026-04-11 03:58:35'),
(906, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee WILSON MARZAN (E0034)', 'Unknown', '2026-04-11 03:58:35'),
(907, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee MARVIN MIRANDA (E0035)', 'Unknown', '2026-04-11 03:58:35'),
(908, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee JOE MONTERDE (E0036)', 'Unknown', '2026-04-11 03:58:35'),
(909, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee ALDRED NATARTE (E0037)', 'Unknown', '2026-04-11 03:58:35'),
(910, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee ARNOLD NERIDO (E0038)', 'Unknown', '2026-04-11 03:58:35'),
(911, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee RONEL NOSES (E0039)', 'Unknown', '2026-04-11 03:58:35'),
(912, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee DANNY PADILLA (E0040)', 'Unknown', '2026-04-11 03:58:35'),
(913, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee EDGAR PANEDA (E0041)', 'Unknown', '2026-04-11 03:58:35'),
(914, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee JEREMY PIMENTEL (E0042)', 'Unknown', '2026-04-11 03:58:35'),
(915, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee MIGUEL PREPOSI (E0043)', 'Unknown', '2026-04-11 03:58:35'),
(916, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee JUN ROAQUIN (E0044)', 'Unknown', '2026-04-11 03:58:35'),
(917, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee RICKMAR SANTOS (E0045)', 'Unknown', '2026-04-11 03:58:35'),
(918, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee RIO SILOY (E0046)', 'Unknown', '2026-04-11 03:58:35'),
(919, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee NORMAN TARAPE (E0047)', 'Unknown', '2026-04-11 03:58:35'),
(920, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee HILMAR TATUNAY (E0048)', 'Unknown', '2026-04-11 03:58:35'),
(921, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee KENNETH JOHN UGAS (E0049)', 'Unknown', '2026-04-11 03:58:35'),
(922, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee CLYDE JUSTINE VASADRE (E0050)', 'Unknown', '2026-04-11 03:58:35'),
(923, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee JOYLENE F. BALANON (ENG-2026-0005)', 'Unknown', '2026-04-11 03:58:35'),
(924, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee VERGEL DACUMOS (E0053)', 'Unknown', '2026-04-11 03:58:35'),
(925, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee REAL RAIN IVERSON (E0054)', 'Unknown', '2026-04-11 03:58:35'),
(926, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee RONALYN MALLARE (ADMIN-2026-0002)', 'Unknown', '2026-04-11 03:58:35'),
(927, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee MICHELLE F. NORIAL (ENG-2026-0001)', 'Unknown', '2026-04-11 03:58:35'),
(928, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee JHUNEL CANCHO (E0058)', 'Unknown', '2026-04-11 03:58:35'),
(929, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee VOHANN MIRANDA (E0055)', 'Unknown', '2026-04-11 03:58:35'),
(930, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee SONNY OCCIANO (E0056)', 'Unknown', '2026-04-11 03:58:35'),
(931, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee RANDY ATON (E0065)', 'Unknown', '2026-04-11 03:58:35'),
(932, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee Marc Arzadon (SA-2026-004)', 'Unknown', '2026-04-11 03:58:35'),
(933, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee Daniel Rillera (IT-2026-001)', 'Unknown', '2026-04-11 03:58:35'),
(934, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee Junell Tadina (PRO-2026-0001)', 'Unknown', '2026-04-11 03:58:35'),
(935, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee Julius John Echague (ENG-2026-0003)', 'Unknown', '2026-04-11 03:58:35'),
(936, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee JOSHUA ARQUITOLA (E0052)', 'Unknown', '2026-04-11 03:58:35'),
(937, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee John Kennedy Lucas (ENG-2026-0002)', 'Unknown', '2026-04-11 03:58:35'),
(938, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee Winnielyn Kaye Olarte (ENG-2026-0006)', 'Unknown', '2026-04-11 03:58:35'),
(939, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee ELAINE Aguilar (ADMIN-2026-0001)', 'Unknown', '2026-04-11 03:58:35'),
(940, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee Jason Wong (SA-2026-002)', 'Unknown', '2026-04-11 03:58:35'),
(941, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee Lee Aldrich Rimando (SA-2026-003)', 'Unknown', '2026-04-11 03:58:35'),
(942, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee Admin Charisse (ADMIN-2026-0003)', 'Unknown', '2026-04-11 03:58:35'),
(943, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee HECTOR PADICLAS (E0060)', 'Unknown', '2026-04-11 03:58:35'),
(944, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee MARIANO NERIDO (E0061)', 'Unknown', '2026-04-11 03:58:35'),
(945, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee JAYSON KENNETH PADILLA (E0062)', 'Unknown', '2026-04-11 03:58:35'),
(946, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee JEFFREY ZAMORA (E0063)', 'Unknown', '2026-04-11 03:58:35'),
(947, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee FRANKIE PADILLA (E0064)', 'Unknown', '2026-04-11 03:58:35'),
(948, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee ROMEO GURION (E0066)', 'Unknown', '2026-04-11 03:58:35'),
(949, NULL, 'Leave Credit', 'Monthly leave credit of 1.00 day added for employee Marjorie Garcia (ADMIN-2026-0004)', 'Unknown', '2026-04-11 03:58:35'),
(950, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-04-11 07:03:33'),
(951, 6, 'Overtime Approved', 'Super Admin approved overtime #24 for 2.00 hours on 2026-04-11', '::1', '2026-04-11 08:34:12'),
(952, 6, 'Logged In', 'User Super Adminesu logged in from branch: Main Branch', '::1', '2026-04-12 23:18:41'),
(953, 6, 'Clocked In', 'Employee #24 clocked in at BCDA - Admin', '::1', '2026-04-13 01:08:57'),
(954, 6, 'Clocked In', 'Employee #27 clocked in at BCDA - Admin', '::1', '2026-04-13 01:08:58'),
(955, 6, 'Clocked In', 'Employee #26 clocked in at BCDA - Admin', '::1', '2026-04-13 01:08:59'),
(956, 6, 'Clocked In', 'Employee #30 clocked in at BCDA - Admin', '::1', '2026-04-13 01:08:59'),
(957, 6, 'Clocked Out', 'Employee #24 clocked out, worked 0 hours', '::1', '2026-04-13 01:09:02'),
(958, 6, 'Clocked Out', 'Employee #27 clocked out, worked 0 hours', '::1', '2026-04-13 01:09:02'),
(959, 6, 'Clocked Out', 'Employee #30 clocked out, worked 0 hours', '::1', '2026-04-13 01:09:03'),
(960, 6, 'Clocked Out', 'Employee #26 clocked out, worked 0 hours', '::1', '2026-04-13 01:09:03'),
(961, 6, 'Clocked In', 'Employee #36 clocked in at BCDA - Admin', '::1', '2026-04-13 01:18:30'),
(962, 6, 'Clocked In', 'Employee #38 clocked in at BCDA - Admin', '::1', '2026-04-13 01:18:31'),
(963, 6, 'Clocked Out', 'Employee #36 clocked out, worked 0 hours', '::1', '2026-04-13 01:18:33'),
(964, 6, 'Clocked Out', 'Employee #38 clocked out, worked 0 hours', '::1', '2026-04-13 01:18:34');

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
  `clock_in_lat` decimal(10,8) DEFAULT NULL,
  `clock_in_lng` decimal(11,8) DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `clock_out_lat` decimal(10,8) DEFAULT NULL,
  `clock_out_lng` decimal(11,8) DEFAULT NULL,
  `location_verified` tinyint(1) DEFAULT '0',
  `location_accuracy` float DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_auto_absent` tinyint(1) DEFAULT '0',
  `auto_absent_applied` tinyint(1) DEFAULT '0',
  `absent_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_overtime_running` tinyint(1) NOT NULL,
  `is_time_running` tinyint(1) NOT NULL,
  `total_ot_hrs` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_voided` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Flag indicating if record is voided (0=active, 1=voided)',
  `void_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Reason for voiding the record',
  `voided_by` int DEFAULT NULL COMMENT 'User ID of admin who voided the record',
  `voided_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp when record was voided',
  PRIMARY KEY (`id`),
  KEY `idx_attendance_employee_date` (`employee_id`,`attendance_date`),
  KEY `idx_attendance_location` (`clock_in_lat`,`clock_in_lng`),
  KEY `idx_voided` (`is_voided`) COMMENT 'Index for filtering voided records'
) ENGINE=MyISAM AUTO_INCREMENT=1203 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_id`, `status`, `branch_name`, `attendance_date`, `time_in`, `clock_in_lat`, `clock_in_lng`, `time_out`, `clock_out_lat`, `clock_out_lng`, `location_verified`, `location_accuracy`, `created_at`, `updated_at`, `is_auto_absent`, `auto_absent_applied`, `absent_notes`, `is_overtime_running`, `is_time_running`, `total_ot_hrs`, `is_voided`, `void_reason`, `voided_by`, `voided_at`) VALUES
(1108, 63, 'Present', 'Main Branch', '2026-03-28', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-03-28 06:16:25', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1107, 117, 'Present', 'Main Branch', '2026-03-28', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-03-28 05:57:02', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1106, 6, 'Present', 'Main Branch', '2026-03-28', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-03-28 00:33:51', '2026-03-28 05:56:38', 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1105, 14, 'Absent', 'Sto. Rosario', '2026-03-27', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-03-27 03:21:25', NULL, 0, 0, '', 0, 0, '0', 0, NULL, NULL, NULL),
(1145, 12, 'Late', 'Main Office', '2026-04-03', '0000-00-00 00:00:00', NULL, NULL, '2026-04-03 23:59:59', NULL, NULL, 0, NULL, '2026-04-06 01:47:10', NULL, 0, 0, NULL, 0, 0, '', 0, NULL, NULL, NULL),
(1103, 6, 'Present', 'Main Branch', '2026-03-27', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-03-27 02:03:27', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1102, 6, 'Present', 'Main Branch', '2026-03-25', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-03-25 02:07:43', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1101, 6, 'Present', 'Main Branch', '2026-03-24', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-03-24 01:57:32', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1100, 6, 'Present', 'Main Branch', '2026-03-20', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-03-20 00:35:39', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1099, 68, 'Present', 'Main Branch', '2026-03-14', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-03-14 05:41:24', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1098, 117, 'Present', 'Main Branch', '2026-03-14', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-03-14 03:04:15', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1097, 6, 'Present', 'Main Branch', '2026-03-14', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-03-14 02:02:42', '2026-03-14 03:17:20', 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1096, 137, 'Present', 'Main Branch', '2026-03-14', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-03-14 01:21:39', '2026-03-14 03:03:36', 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1095, 137, 'Present', 'MAIN OFFICE', '2026-03-13', '2026-03-13 12:31:10', NULL, NULL, '2026-03-13 23:59:59', NULL, NULL, 0, NULL, '2026-03-13 04:31:10', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1094, 117, 'Present', 'Main Branch', '2026-03-13', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-03-13 02:39:53', '2026-03-13 02:55:41', 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1093, 137, 'Present', 'MAIN OFFICE', '2026-03-13', '2026-03-13 07:28:30', NULL, NULL, '0000-00-00 00:00:00', NULL, NULL, 0, NULL, '2026-03-13 00:58:48', '2026-03-13 04:28:16', 0, 0, '', 0, 1, '0', 0, NULL, NULL, NULL),
(1092, 6, 'Present', 'Main Branch', '2026-03-13', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-03-13 00:42:31', '2026-03-13 06:17:35', 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1091, 6, 'Present', 'Main Branch', '2026-03-05', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-03-05 03:22:45', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1090, 6, 'Present', 'Main Branch', '2026-02-28', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-02-28 00:46:55', '2026-02-28 02:11:44', 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1089, 36, 'Present', 'BCDA - Admin', '2026-02-27', '2026-02-27 12:47:42', NULL, NULL, '2026-02-27 23:59:59', NULL, NULL, 0, NULL, '2026-02-27 04:47:42', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1088, 26, 'Present', 'BCDA - Admin', '2026-02-27', '2026-02-27 12:47:42', NULL, NULL, '2026-02-27 23:59:59', NULL, NULL, 0, NULL, '2026-02-27 04:47:42', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1087, 27, 'Present', 'BCDA - Admin', '2026-02-27', '2026-02-27 12:47:41', NULL, NULL, '2026-02-27 23:59:59', NULL, NULL, 0, NULL, '2026-02-27 04:47:41', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1086, 24, 'Present', 'BCDA - Admin', '2026-02-27', '2026-02-27 12:47:40', NULL, NULL, '2026-02-27 23:59:59', NULL, NULL, 0, NULL, '2026-02-27 04:47:40', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1085, 6, 'Present', 'Main Branch', '2026-02-26', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-02-26 00:06:00', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1084, 27, NULL, 'BCDA - Admin', '2026-02-25', '2026-02-25 08:03:07', NULL, NULL, '2026-02-25 15:29:49', NULL, NULL, 0, NULL, '2026-02-25 00:03:07', NULL, 0, 0, NULL, 0, 0, '2.00', 0, NULL, NULL, NULL),
(1083, 6, 'Present', 'Main Branch', '2026-02-25', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-02-24 23:49:52', '2026-02-25 07:23:20', 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1082, 117, 'Present', 'Main Branch', '2026-02-25', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-02-24 23:49:32', '2026-02-25 00:20:51', 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1079, 24, NULL, 'BCDA - Admin', '2026-02-24', '2026-02-24 16:54:58', NULL, NULL, '2026-02-24 16:58:52', NULL, NULL, 0, NULL, '2026-02-24 08:54:58', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1078, 6, 'Present', 'Main Branch', '2026-02-24', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-02-24 08:44:16', '2026-02-24 08:59:24', 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1080, 68, 'Present', 'Main Branch', '2026-02-25', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-02-24 23:49:05', '2026-02-25 00:22:05', 0, 0, NULL, 0, 0, '1.00', 0, NULL, NULL, NULL),
(1081, 24, NULL, 'BCDA - Admin', '2026-02-25', '2026-02-25 07:49:14', NULL, NULL, '2026-02-25 15:29:48', NULL, NULL, 0, NULL, '2026-02-24 23:49:14', NULL, 0, 0, NULL, 0, 0, '5.00', 0, NULL, NULL, NULL),
(1075, 6, 'Present', 'Main Branch', '2026-02-23', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-02-23 02:57:42', '2026-02-23 03:09:16', 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1076, 117, 'Present', 'Main Branch', '2026-02-24', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-02-24 06:24:00', '2026-02-24 08:59:04', 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1077, 68, 'Present', 'Main Branch', '2026-02-24', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-02-24 07:13:55', '2026-02-24 08:58:40', 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1109, 6, 'Present', 'Main Branch', '2026-03-30', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-03-29 23:59:59', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1110, 6, 'Present', 'Main Branch', '2026-03-31', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-03-31 06:03:40', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1111, 117, 'Present', 'Main Branch', '2026-04-01', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-01 07:19:51', '2026-04-01 07:21:58', 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1112, 68, 'Present', 'Main Branch', '2026-04-01', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-01 07:20:09', '2026-04-01 07:21:38', 0, 0, NULL, 0, 0, '3.00', 0, NULL, NULL, NULL),
(1113, 6, 'Present', 'Main Branch', '2026-04-06', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-06 00:03:12', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1151, 30, 'Present', 'BCDA - Admin', '2026-04-07', '2026-04-07 11:12:52', NULL, NULL, '2026-04-07 23:59:59', NULL, NULL, 0, NULL, '2026-04-07 03:12:52', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1150, 26, 'Present', 'BCDA - Admin', '2026-04-07', '2026-04-07 11:12:52', NULL, NULL, '2026-04-07 23:59:59', NULL, NULL, 0, NULL, '2026-04-07 03:12:52', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1148, 24, 'Present', 'BCDA - Admin', '2026-04-07', '2026-04-07 11:12:49', NULL, NULL, '2026-04-07 23:59:59', NULL, NULL, 0, NULL, '2026-04-07 03:12:49', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1149, 27, 'Present', 'BCDA - Admin', '2026-04-07', '2026-04-07 11:12:50', NULL, NULL, '2026-04-07 23:59:59', NULL, NULL, 0, NULL, '2026-04-07 03:12:50', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1147, 6, 'Present', 'Main Branch', '2026-04-07', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-07 03:08:00', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1146, 12, 'Late', 'Main Office', '2026-04-03', '0000-00-00 00:00:00', NULL, NULL, '2026-04-03 23:59:59', NULL, NULL, 0, NULL, '2026-04-06 01:47:42', NULL, 0, 0, NULL, 0, 0, '', 0, NULL, NULL, NULL),
(1144, 12, 'Late', 'Main Office', '2026-04-03', '0000-00-00 00:00:00', NULL, NULL, '2026-04-03 23:59:59', NULL, NULL, 0, NULL, '2026-04-06 01:47:06', NULL, 0, 0, NULL, 0, 0, '', 0, NULL, NULL, NULL),
(1143, 12, 'Late', 'Main Office', '2026-04-06', '2009-00-00 00:00:00', NULL, NULL, '2026-04-06 23:59:59', NULL, NULL, 0, NULL, '2026-04-06 01:39:23', NULL, 0, 0, NULL, 0, 0, '', 0, NULL, NULL, NULL),
(1141, 12, 'Late', 'Main Office', '2026-04-04', '0000-00-00 00:00:00', NULL, NULL, '2026-04-04 23:59:59', NULL, NULL, 0, NULL, '2026-04-06 01:39:23', NULL, 0, 0, NULL, 0, 0, '', 0, NULL, NULL, NULL),
(1142, 12, 'Late', 'Main Office', '2026-04-05', '0000-00-00 00:00:00', NULL, NULL, '2026-04-05 23:59:59', NULL, NULL, 0, NULL, '2026-04-06 01:39:23', NULL, 0, 0, NULL, 0, 0, '', 0, NULL, NULL, NULL),
(1152, 36, 'Present', 'BCDA - Admin', '2026-04-07', '2026-04-07 11:12:53', NULL, NULL, '2026-04-07 23:59:59', NULL, NULL, 0, NULL, '2026-04-07 03:12:53', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1153, 38, 'Present', 'BCDA - Admin', '2026-04-07', '2026-04-07 11:12:54', NULL, NULL, '2026-04-07 23:59:59', NULL, NULL, 0, NULL, '2026-04-07 03:12:54', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1154, 11, 'Present', 'BCDA - Admin', '2026-04-07', '2026-04-07 11:12:54', NULL, NULL, '2026-04-07 23:59:59', NULL, NULL, 0, NULL, '2026-04-07 03:12:54', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1155, 13, 'Present', 'BCDA - CCA', '2026-04-07', '2026-04-07 11:12:56', NULL, NULL, '2026-04-07 23:59:59', NULL, NULL, 0, NULL, '2026-04-07 03:12:56', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1156, 15, 'Present', 'BCDA - CCA', '2026-04-07', '2026-04-07 11:12:57', NULL, NULL, '2026-04-07 23:59:59', NULL, NULL, 0, NULL, '2026-04-07 03:12:57', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1157, 18, 'Present', 'BCDA - CCA', '2026-04-07', '2026-04-07 11:12:59', NULL, NULL, '2026-04-07 23:59:59', NULL, NULL, 0, NULL, '2026-04-07 03:12:59', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1158, 6, 'Present', 'Main Branch', '2026-04-08', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-08 01:35:34', '2026-04-08 03:10:51', 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1159, 6, 'Present', 'Main Branch', '2026-04-09', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-09 00:22:48', '2026-04-09 07:22:07', 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1160, 63, NULL, 'BCDA - Admin', '2026-04-09', '2026-04-09 08:23:08', NULL, NULL, '2026-04-09 08:23:18', NULL, NULL, 0, NULL, '2026-04-09 00:23:08', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1161, 27, NULL, 'BCDA - Admin', '2026-04-09', '2026-04-09 08:23:09', NULL, NULL, '2026-04-09 08:23:18', NULL, NULL, 0, NULL, '2026-04-09 00:23:09', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1162, 30, 'Present', 'BCDA - Admin', '2026-04-09', '2026-04-09 08:23:10', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-09 00:23:10', NULL, 0, 0, NULL, 0, 1, '0', 0, NULL, NULL, NULL),
(1163, 36, 'Absent', 'BCDA - Admin', '2026-04-09', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-09 00:23:11', NULL, 0, 0, '', 0, 0, '0', 0, NULL, NULL, NULL),
(1164, 38, 'Absent', 'BCDA - Admin', '2026-04-09', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-09 00:23:11', NULL, 0, 0, '', 0, 0, '0', 0, NULL, NULL, NULL),
(1165, 42, 'Present', 'BCDA - Admin', '2026-04-09', '2026-04-09 08:23:12', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-09 00:23:12', NULL, 0, 0, NULL, 0, 1, '0', 0, NULL, NULL, NULL),
(1166, 44, 'Present', 'BCDA - Admin', '2026-04-09', '2026-04-09 08:23:12', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-09 00:23:12', NULL, 0, 0, NULL, 0, 1, '0', 0, NULL, NULL, NULL),
(1167, 48, 'Absent', 'BCDA - Admin', '2026-04-09', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-09 00:23:13', NULL, 0, 0, '', 0, 0, '0', 0, NULL, NULL, NULL),
(1168, 131, 'Absent', 'BCDA - Admin', '2026-04-09', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-09 00:23:14', NULL, 0, 0, '', 0, 0, '0', 0, NULL, NULL, NULL),
(1169, 24, 'Present', 'BCDA - Admin', '2026-04-09', '2026-04-09 09:54:19', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-09 01:54:19', NULL, 0, 0, NULL, 0, 1, '0', 0, NULL, NULL, NULL),
(1170, 11, 'Present', 'BCDA - Admin', '2026-04-09', '2026-04-09 09:54:19', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-09 01:54:19', NULL, 0, 0, NULL, 0, 1, '0', 0, NULL, NULL, NULL),
(1171, 27, 'Present', 'BCDA - Admin', '2026-04-09', '2026-04-09 09:54:20', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-09 01:54:20', NULL, 0, 0, NULL, 0, 1, '0', 0, NULL, NULL, NULL),
(1172, 130, 'Absent', 'BCDA - Admin', '2026-04-09', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-09 01:54:21', NULL, 0, 0, '', 0, 0, '0', 0, NULL, NULL, NULL),
(1173, 117, 'Present', 'Main Branch', '2026-04-09', '2026-04-09 11:08:39', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-09 02:40:31', '2026-04-09 03:48:35', 0, 0, NULL, 0, 1, '0', 0, NULL, NULL, NULL),
(1174, 68, 'Present', 'Main Branch', '2026-04-09', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-09 03:06:56', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1175, 26, 'Present', 'BCDA - Admin', '2026-04-09', '2026-04-09 11:09:06', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-09 03:09:06', NULL, 0, 0, NULL, 0, 1, '0', 0, NULL, NULL, NULL),
(1176, 63, 'Present', 'BCDA - Admin', '2026-04-09', '2026-04-09 11:09:06', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-09 03:09:06', NULL, 0, 0, NULL, 0, 1, '0', 0, NULL, NULL, NULL),
(1177, 132, 'Present', 'BCDA - Admin', '2026-04-09', '2026-04-09 15:56:31', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-09 07:56:31', NULL, 0, 0, NULL, 0, 1, '0', 0, NULL, NULL, NULL),
(1178, 6, 'Present', 'Main Branch', '2026-04-10', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-10 00:21:27', '2026-04-10 08:25:56', 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1179, 63, NULL, 'BCDA - Admin', '2026-04-10', '2026-04-10 08:21:33', NULL, NULL, '2026-04-10 08:21:40', NULL, NULL, 0, NULL, '2026-04-10 00:21:33', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1180, 24, 'Absent', 'BCDA - Admin', '2026-04-10', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-10 00:21:35', NULL, 0, 0, '', 0, 0, '0', 0, NULL, NULL, NULL),
(1181, 27, 'Absent', 'BCDA - Admin', '2026-04-10', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-10 00:21:36', NULL, 0, 0, '', 0, 0, '0', 0, NULL, NULL, NULL),
(1182, 26, NULL, 'BCDA - Admin', '2026-04-10', '2026-04-10 08:21:36', NULL, NULL, '2026-04-10 08:21:41', NULL, NULL, 0, NULL, '2026-04-10 00:21:36', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1183, 30, NULL, 'BCDA - Admin', '2026-04-10', '2026-04-10 08:21:37', NULL, NULL, '2026-04-10 08:21:42', NULL, NULL, 0, NULL, '2026-04-10 00:21:37', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1184, 63, NULL, 'BCDA - Admin', '2026-04-10', '2026-04-10 08:21:46', NULL, NULL, '2026-04-10 09:43:41', NULL, NULL, 0, NULL, '2026-04-10 00:21:46', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1185, 26, NULL, 'BCDA - Admin', '2026-04-10', '2026-04-10 08:21:47', NULL, NULL, '2026-04-10 09:44:07', NULL, NULL, 0, NULL, '2026-04-10 00:21:47', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1186, 117, 'Present', 'Main Branch', '2026-04-10', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-10 01:18:49', '2026-04-10 01:23:43', 0, 0, NULL, 0, 0, '2.00', 0, NULL, NULL, NULL),
(1187, 63, 'Present', 'BCDA - CCA', '2026-04-10', '2026-04-10 09:43:43', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-10 01:43:43', NULL, 0, 0, NULL, 0, 1, '0', 0, NULL, NULL, NULL),
(1188, 30, NULL, 'BCDA - Admin', '2026-04-10', '2026-04-10 09:44:03', NULL, NULL, '2026-04-10 09:44:09', NULL, NULL, 0, NULL, '2026-04-10 01:44:03', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1189, 36, 'Present', 'BCDA - Admin', '2026-04-10', '2026-04-10 09:44:04', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-10 01:44:04', NULL, 0, 0, NULL, 0, 1, '0', 0, NULL, NULL, NULL),
(1190, 26, 'Present', 'BCDA - Admin', '2026-04-10', '2026-04-10 09:44:11', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-10 01:44:11', NULL, 0, 0, NULL, 0, 1, '0', 0, NULL, NULL, NULL),
(1191, 30, 'Present', 'BCDA - Admin', '2026-04-10', '2026-04-10 09:44:23', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-10 01:44:23', NULL, 0, 0, NULL, 0, 1, '0', 0, NULL, NULL, NULL),
(1192, 6, 'Present', 'Main Branch', '2026-04-11', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-11 00:01:59', '2026-04-11 07:03:33', 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1193, 117, 'Present', 'BCDA - Admin', '2026-04-11', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-11 02:03:50', NULL, 0, 0, NULL, 0, 0, '2.00', 0, NULL, NULL, NULL),
(1194, 24, 'Present', 'BCDA - Admin', '2026-04-11', '2026-04-11 10:03:58', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-11 02:03:58', NULL, 0, 0, NULL, 0, 1, '3.00', 0, NULL, NULL, NULL),
(1195, 37, 'Present', 'BCDA - Admin', '2026-04-11', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-11 08:34:11', NULL, 0, 0, NULL, 0, 0, '2.00', 0, NULL, NULL, NULL),
(1196, 6, 'Present', 'Main Branch', '2026-04-13', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-12 23:18:41', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1197, 24, NULL, 'BCDA - Admin', '2026-04-13', '2026-04-13 09:08:57', NULL, NULL, '2026-04-13 09:09:02', NULL, NULL, 0, NULL, '2026-04-13 01:08:57', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1198, 27, NULL, 'BCDA - Admin', '2026-04-13', '2026-04-13 09:08:58', NULL, NULL, '2026-04-13 09:09:02', NULL, NULL, 0, NULL, '2026-04-13 01:08:58', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1199, 26, NULL, 'BCDA - Admin', '2026-04-13', '2026-04-13 09:08:59', NULL, NULL, '2026-04-13 09:09:03', NULL, NULL, 0, NULL, '2026-04-13 01:08:59', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1200, 30, NULL, 'BCDA - Admin', '2026-04-13', '2026-04-13 09:08:59', NULL, NULL, '2026-04-13 09:09:03', NULL, NULL, 0, NULL, '2026-04-13 01:08:59', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1201, 36, NULL, 'BCDA - Admin', '2026-04-13', '2026-04-13 09:18:30', NULL, NULL, '2026-04-13 09:18:33', NULL, NULL, 0, NULL, '2026-04-13 01:18:30', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL),
(1202, 38, NULL, 'BCDA - Admin', '2026-04-13', '2026-04-13 09:18:31', NULL, NULL, '2026-04-13 09:18:34', NULL, NULL, 0, NULL, '2026-04-13 01:18:31', NULL, 0, 0, NULL, 0, 0, '0', 0, NULL, NULL, NULL);

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
  `lat` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Latitude',
  `long` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Longitude',
  `geofence_radius_meters` int DEFAULT '200',
  `location_verified` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_name` (`branch_name`),
  KEY `idx_branch_name` (`branch_name`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_branch_location` (`lat`,`long`)
) ENGINE=MyISAM AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `order_number`, `branch_name`, `branch_address`, `created_at`, `is_active`, `lat`, `long`, `geofence_radius_meters`, `location_verified`) VALUES
(23, '393859493', 'BCDA - Fence', 'Poro point, San Fernando City, La Union', '2026-02-06 01:01:29', 0, '16.609022838414607', '120.30142068898999', 250, 1),
(22, '393859493', 'BCDA - Control Tower', 'Poro point, San Fernando City, La Union', '2026-02-06 01:01:11', 1, '16.5969775', '120.3077657', 250, 1),
(21, '393859493', 'BCDA - Admin', 'Poro point, San Fernando City, La Union', '2026-02-06 01:00:59', 1, '16.5969775', '120.3077657', 250, 1),
(10, '299269388', 'Sto. Rosario', 'Sto. Rosario, San Juan, La Union', '2026-01-29 03:19:23', 1, '16.6849388', '120.3522885', 250, 1),
(20, '393859493', 'BCDA - CCA', 'Poro point, San Fernando City, La Union', '2026-02-06 01:00:44', 1, '16.6076835', '120.293763', 250, 1),
(32, '488809024', 'Maintenance', NULL, '2026-02-06 01:03:08', 1, '16.61376', '120.3429949', 250, 1),
(24, '393859493', 'BCDA - Fire Station', 'Poro point, San Fernando City, La Union', '2026-02-06 01:01:46', 1, '16.5969775', '120.3077657', 250, 1),
(25, '393859493', 'BCDA - CCTV', 'Poro point, San Fernando City, La Union', '2026-02-06 01:01:55', 1, '16.609022838414607', '120.30142068898999', 250, 0),
(26, '159166591', 'Panicsican', 'Panicsican, San Juan, La Union', '2026-02-06 01:02:07', 1, '16.6625973', '120.3322308', 250, 1),
(27, '149744923', 'Dallangayan', NULL, '2026-02-06 01:02:16', 1, '16.62798076575308', '120.34489528617755', 200, 1),
(28, '228984422', 'Pias - Sundara', NULL, '2026-02-06 01:02:25', 1, '16.6141212', '120.3529197', 200, 0),
(30, '473768962', 'Capitol - Roadwork', NULL, '2026-02-06 01:02:59', 1, '16.61397741592491', '120.31865176981339', 200, 0),
(31, '473768962', 'Capitol - Accounting', NULL, '2026-02-06 01:03:08', 1, '16.61397741592491', '120.31865176981339', 150, 1),
(33, '458762594', 'MAIN OFFICE', NULL, '2026-02-10 08:10:39', 1, '16.6147478', '120.3535912', 300, 0);

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
  `status` enum('Pending','Approved','Rejected','Pre-Approved') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `request_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `approved_date` datetime DEFAULT NULL,
  `paid_date` datetime DEFAULT NULL,
  `approved_by` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cash_advances`
--

INSERT INTO `cash_advances` (`id`, `employee_id`, `amount`, `particular`, `reason`, `status`, `request_date`, `approved_date`, `paid_date`, `approved_by`, `approved_at`, `rejection_reason`) VALUES
(15, 68, 1234.00, 'Cash Advance', 'asdf', 'Pre-Approved', '2026-04-01 15:20:37', NULL, NULL, 'Admin', '2026-04-01 15:21:22', NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `daily_payroll_reports`
--

INSERT INTO `daily_payroll_reports` (`id`, `employee_id`, `report_date`, `report_year`, `report_month`, `report_day`, `week_number`, `branch_id`, `days_worked`, `total_hours`, `daily_rate`, `basic_pay`, `ot_hours`, `ot_rate`, `ot_amount`, `performance_allowance`, `gross_pay`, `gross_plus_allowance`, `ca_deduction`, `sss_deduction`, `philhealth_deduction`, `pagibig_deduction`, `sss_loan`, `total_deductions`, `take_home_pay`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 24, '2026-02-27', 2026, 2, 27, 4, 21, 1.0, 0.00, 500.00, 500.00, 0.00, 62.50, 0.00, 0.00, 500.00, 500.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 500.00, 'Pending', 1, '2026-02-28 02:01:31', '2026-02-28 02:01:31'),
(2, 26, '2026-02-27', 2026, 2, 27, 4, 21, 1.0, 0.00, 600.00, 600.00, 0.00, 75.00, 0.00, 0.00, 600.00, 600.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 600.00, 'Pending', 1, '2026-02-28 02:01:31', '2026-02-28 02:01:31'),
(3, 27, '2026-02-27', 2026, 2, 27, 4, 21, 1.0, 0.00, 550.00, 550.00, 0.00, 68.75, 0.00, 0.00, 550.00, 550.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 550.00, 'Pending', 1, '2026-02-28 02:01:31', '2026-02-28 02:01:31'),
(4, 36, '2026-02-27', 2026, 2, 27, 4, 21, 1.0, 0.00, 500.00, 500.00, 0.00, 62.50, 0.00, 0.00, 500.00, 500.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 500.00, 'Pending', 1, '2026-02-28 02:01:31', '2026-02-28 02:01:31'),
(5, 24, '2026-02-24', 2026, 2, 24, 4, 21, 1.0, 0.07, 500.00, 500.00, 0.00, 62.50, 0.00, 0.00, 500.00, 500.00, 0.00, 16.07, 8.93, 7.14, 0.00, 32.14, 467.86, 'Pending', NULL, '2026-03-03 01:07:58', '2026-03-03 01:07:58'),
(6, 24, '2026-02-25', 2026, 2, 25, 4, 21, 1.0, 7.68, 500.00, 500.00, 5.00, 62.50, 312.50, 0.00, 812.50, 812.50, 0.00, 16.07, 8.93, 7.14, 0.00, 32.14, 780.36, 'Pending', NULL, '2026-03-03 01:07:58', '2026-03-03 01:07:58'),
(7, 27, '2026-02-25', 2026, 2, 25, 4, 21, 1.0, 7.45, 550.00, 550.00, 2.00, 68.75, 137.50, 0.00, 687.50, 687.50, 0.00, 16.07, 8.93, 7.14, 0.00, 32.14, 655.36, 'Pending', NULL, '2026-03-03 01:07:58', '2026-03-03 01:07:58'),
(8, 12, '2026-04-03', 2026, 4, 3, 1, 33, 1.0, 999999.99, 550.00, 550.00, 0.00, 68.75, 0.00, 0.00, 550.00, 550.00, 0.00, 15.00, 8.33, 6.67, 0.00, 30.00, 520.00, 'Pending', NULL, '2026-04-10 02:13:47', '2026-04-10 02:13:47'),
(9, 12, '2026-04-04', 2026, 4, 4, 1, 33, 1.0, 999999.99, 550.00, 550.00, 0.00, 68.75, 0.00, 0.00, 550.00, 550.00, 0.00, 15.00, 8.33, 6.67, 0.00, 30.00, 520.00, 'Pending', NULL, '2026-04-10 02:13:47', '2026-04-10 02:13:47'),
(10, 12, '2026-04-05', 2026, 4, 5, 1, 33, 1.0, 999999.99, 550.00, 550.00, 0.00, 68.75, 0.00, 0.00, 550.00, 550.00, 0.00, 15.00, 8.33, 6.67, 0.00, 30.00, 520.00, 'Pending', NULL, '2026-04-10 02:13:47', '2026-04-10 02:13:47'),
(11, 12, '2026-04-06', 2026, 4, 6, 1, 33, 1.0, 152088.00, 550.00, 550.00, 0.00, 68.75, 0.00, 0.00, 550.00, 550.00, 0.00, 15.00, 8.33, 6.67, 0.00, 30.00, 520.00, 'Pending', NULL, '2026-04-10 02:13:47', '2026-04-10 02:13:47'),
(12, 11, '2026-04-07', 2026, 4, 7, 1, 21, 1.0, 12.78, 700.00, 700.00, 0.00, 87.50, 0.00, 0.00, 700.00, 700.00, 0.00, 15.00, 8.33, 6.67, 0.00, 30.00, 670.00, 'Pending', NULL, '2026-04-10 02:13:47', '2026-04-10 02:13:47'),
(13, 13, '2026-04-07', 2026, 4, 7, 1, 20, 1.0, 12.78, 600.00, 600.00, 0.00, 75.00, 0.00, 0.00, 600.00, 600.00, 0.00, 15.00, 8.33, 6.67, 0.00, 30.00, 570.00, 'Pending', NULL, '2026-04-10 02:13:47', '2026-04-10 02:13:47'),
(14, 15, '2026-04-07', 2026, 4, 7, 1, 20, 1.0, 12.78, 600.00, 600.00, 0.00, 75.00, 0.00, 0.00, 600.00, 600.00, 0.00, 15.00, 8.33, 6.67, 0.00, 30.00, 570.00, 'Pending', NULL, '2026-04-10 02:13:47', '2026-04-10 02:13:47'),
(15, 18, '2026-04-07', 2026, 4, 7, 1, 20, 1.0, 12.78, 600.00, 600.00, 0.00, 75.00, 0.00, 0.00, 600.00, 600.00, 0.00, 15.00, 8.33, 6.67, 0.00, 30.00, 570.00, 'Pending', NULL, '2026-04-10 02:13:47', '2026-04-10 02:13:47'),
(16, 24, '2026-04-07', 2026, 4, 7, 1, 21, 1.0, 12.79, 500.00, 500.00, 0.00, 62.50, 0.00, 0.00, 500.00, 500.00, 0.00, 15.00, 8.33, 6.67, 0.00, 30.00, 470.00, 'Pending', NULL, '2026-04-10 02:13:47', '2026-04-10 02:13:47'),
(17, 26, '2026-04-07', 2026, 4, 7, 1, 21, 1.0, 12.79, 600.00, 600.00, 0.00, 75.00, 0.00, 0.00, 600.00, 600.00, 0.00, 15.00, 8.33, 6.67, 0.00, 30.00, 570.00, 'Pending', NULL, '2026-04-10 02:13:47', '2026-04-10 02:13:47'),
(18, 27, '2026-04-07', 2026, 4, 7, 1, 21, 1.0, 12.79, 550.00, 550.00, 0.00, 68.75, 0.00, 0.00, 550.00, 550.00, 0.00, 15.00, 8.33, 6.67, 0.00, 30.00, 520.00, 'Pending', NULL, '2026-04-10 02:13:47', '2026-04-10 02:13:47'),
(19, 30, '2026-04-07', 2026, 4, 7, 1, 21, 1.0, 12.79, 500.00, 500.00, 0.00, 62.50, 0.00, 0.00, 500.00, 500.00, 0.00, 15.00, 8.33, 6.67, 0.00, 30.00, 470.00, 'Pending', NULL, '2026-04-10 02:13:47', '2026-04-10 02:13:47'),
(20, 36, '2026-04-07', 2026, 4, 7, 1, 21, 1.0, 12.79, 500.00, 500.00, 0.00, 62.50, 0.00, 0.00, 500.00, 500.00, 0.00, 15.00, 8.33, 6.67, 0.00, 30.00, 470.00, 'Pending', NULL, '2026-04-10 02:13:47', '2026-04-10 02:13:47'),
(21, 38, '2026-04-07', 2026, 4, 7, 1, 21, 1.0, 12.78, 500.00, 500.00, 0.00, 62.50, 0.00, 0.00, 500.00, 500.00, 0.00, 15.00, 8.33, 6.67, 0.00, 30.00, 470.00, 'Pending', NULL, '2026-04-10 02:13:47', '2026-04-10 02:13:47'),
(22, 27, '2026-04-09', 2026, 4, 9, 2, 21, 1.0, 0.00, 550.00, 550.00, 0.00, 68.75, 0.00, 0.00, 550.00, 550.00, 0.00, 15.00, 8.33, 6.67, 0.00, 30.00, 520.00, 'Pending', NULL, '2026-04-10 02:13:47', '2026-04-10 02:13:47'),
(23, 26, '2026-04-10', 2026, 4, 10, 2, 21, 1.0, 0.00, 600.00, 600.00, 0.00, 75.00, 0.00, 0.00, 600.00, 600.00, 0.00, 15.00, 8.33, 6.67, 0.00, 30.00, 570.00, 'Pending', NULL, '2026-04-10 02:13:47', '2026-04-10 02:13:47'),
(24, 30, '2026-04-10', 2026, 4, 10, 2, 21, 1.0, 0.00, 500.00, 500.00, 0.00, 62.50, 0.00, 0.00, 500.00, 500.00, 0.00, 15.00, 8.33, 6.67, 0.00, 30.00, 470.00, 'Pending', NULL, '2026-04-10 02:13:47', '2026-04-10 02:13:47');

--
-- Triggers `daily_payroll_reports`
--
DROP TRIGGER IF EXISTS `trg_validate_attendance_before_dpr_insert`;
DELIMITER $$
CREATE TRIGGER `trg_validate_attendance_before_dpr_insert` BEFORE INSERT ON `daily_payroll_reports` FOR EACH ROW BEGIN
    DECLARE v_attendance_count INT DEFAULT 0;
    
    SELECT COUNT(*) INTO v_attendance_count
    FROM attendance a
    WHERE a.employee_id = NEW.employee_id
      AND a.attendance_date = NEW.report_date
      AND a.time_in IS NOT NULL
      AND a.time_out IS NOT NULL;
    
    IF v_attendance_count = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot insert daily_payroll_reports record without valid attendance record with time_in and time_out';
    END IF;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_validate_attendance_before_dpr_update`;
DELIMITER $$
CREATE TRIGGER `trg_validate_attendance_before_dpr_update` BEFORE UPDATE ON `daily_payroll_reports` FOR EACH ROW BEGIN
    DECLARE v_attendance_count INT DEFAULT 0;
    
    -- Only validate if key fields changed (employee_id or report_date)
    IF OLD.employee_id != NEW.employee_id OR OLD.report_date != NEW.report_date THEN
        SELECT COUNT(*) INTO v_attendance_count
        FROM attendance a
        WHERE a.employee_id = NEW.employee_id
          AND a.attendance_date = NEW.report_date
          AND a.time_in IS NOT NULL
          AND a.time_out IS NOT NULL;
        
        IF v_attendance_count = 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot update daily_payroll_reports record without valid attendance record with time_in and time_out';
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `daily_payroll_reports_backup_2026_04_10_101347`
--

DROP TABLE IF EXISTS `daily_payroll_reports_backup_2026_04_10_101347`;
CREATE TABLE IF NOT EXISTS `daily_payroll_reports_backup_2026_04_10_101347` (
  `id` int NOT NULL DEFAULT '0',
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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `daily_payroll_reports_backup_2026_04_10_101347`
--

INSERT INTO `daily_payroll_reports_backup_2026_04_10_101347` (`id`, `employee_id`, `report_date`, `report_year`, `report_month`, `report_day`, `week_number`, `branch_id`, `days_worked`, `total_hours`, `daily_rate`, `basic_pay`, `ot_hours`, `ot_rate`, `ot_amount`, `performance_allowance`, `gross_pay`, `gross_plus_allowance`, `ca_deduction`, `sss_deduction`, `philhealth_deduction`, `pagibig_deduction`, `sss_loan`, `total_deductions`, `take_home_pay`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 24, '2026-02-27', 2026, 2, 27, 4, 21, 1.0, 0.00, 500.00, 500.00, 0.00, 62.50, 0.00, 0.00, 500.00, 500.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 500.00, 'Pending', 1, '2026-02-28 02:01:31', '2026-02-28 02:01:31'),
(2, 26, '2026-02-27', 2026, 2, 27, 4, 21, 1.0, 0.00, 600.00, 600.00, 0.00, 75.00, 0.00, 0.00, 600.00, 600.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 600.00, 'Pending', 1, '2026-02-28 02:01:31', '2026-02-28 02:01:31'),
(3, 27, '2026-02-27', 2026, 2, 27, 4, 21, 1.0, 0.00, 550.00, 550.00, 0.00, 68.75, 0.00, 0.00, 550.00, 550.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 550.00, 'Pending', 1, '2026-02-28 02:01:31', '2026-02-28 02:01:31'),
(4, 36, '2026-02-27', 2026, 2, 27, 4, 21, 1.0, 0.00, 500.00, 500.00, 0.00, 62.50, 0.00, 0.00, 500.00, 500.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 500.00, 'Pending', 1, '2026-02-28 02:01:31', '2026-02-28 02:01:31'),
(5, 24, '2026-02-24', 2026, 2, 24, 4, 21, 1.0, 0.07, 500.00, 500.00, 0.00, 62.50, 0.00, 0.00, 500.00, 500.00, 0.00, 16.07, 8.93, 7.14, 0.00, 32.14, 467.86, 'Pending', NULL, '2026-03-03 01:07:58', '2026-03-03 01:07:58'),
(6, 24, '2026-02-25', 2026, 2, 25, 4, 21, 1.0, 7.68, 500.00, 500.00, 5.00, 62.50, 312.50, 0.00, 812.50, 812.50, 0.00, 16.07, 8.93, 7.14, 0.00, 32.14, 780.36, 'Pending', NULL, '2026-03-03 01:07:58', '2026-03-03 01:07:58'),
(7, 27, '2026-02-25', 2026, 2, 25, 4, 21, 1.0, 7.45, 550.00, 550.00, 2.00, 68.75, 137.50, 0.00, 687.50, 687.50, 0.00, 16.07, 8.93, 7.14, 0.00, 32.14, 655.36, 'Pending', NULL, '2026-03-03 01:07:58', '2026-03-03 01:07:58');

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
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `has_deduction` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Whether employee is subject to SSS/PhilHealth/PagIBIG deduction ) (1=yes, 0-no)',
  `sss_loan` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_code` (`employee_code`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_employees_branch` (`branch_id`),
  KEY `idx_has_deduction` (`has_deduction`)
) ENGINE=MyISAM AUTO_INCREMENT=140 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_code`, `first_name`, `middle_name`, `last_name`, `email`, `password_hash`, `position`, `status`, `created_at`, `updated_at`, `profile_image`, `daily_rate`, `branch_id`, `has_deduction`, `sss_loan`) VALUES
(16, 'E0006', 'ALFREDO', NULL, 'BAGUIO', 'alfredo.baguio@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:22:15', NULL, 550.00, 10, 1, 0.00),
(17, 'E0007', 'ROLLY', NULL, 'BALTAZAR', 'rolly.baltazar@example.com', '$2y$10$4/nX3PsxAeYnik1fwh7lxO3XJHlW.IiOjK5NZPDCDD9eXoCBMVp8K', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:22:23', NULL, 500.00, 10, 1, 0.00),
(18, 'E0008', 'DONG', NULL, 'BAUTISTA', 'dong.bautista@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 07:01:04', NULL, 600.00, 20, 1, 0.00),
(14, 'E0004', 'NOEL', NULL, 'ARIZ', 'noel.ariz@example.com', '$2y$10$2Iq/E7PtLMHHBwAjTl.q5OthGTKYXQf5Bx/Q/SXpsmeyQ5VJKcnnO', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-04-08 01:50:26', NULL, 550.00, 10, 0, 0.00),
(6, 'SA001', 'Super', 'Torres', 'Adminesu', 'admin@jajrconstruction.com', '$2y$10$RSHOb3hskFZueMLlCycFuua/4EwcxGmAIzpcl8ixQpEXY3tfu9LYi', 'Super Admin', 'Active', '2026-01-16 02:26:58', '2026-02-19 05:51:54', 'uploads/profile_images/profile_6_1771480314.png', 600.00, 31, 1, 0.00),
(15, 'E0005', 'DANIEL', NULL, 'BACHILLER', 'daniel.bachiller@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:27:53', NULL, 600.00, 20, 1, 0.00),
(11, 'E0001', 'AARIZ', NULL, 'MARLOU', 'aariz.marlou@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 01:58:28', NULL, 700.00, 21, 1, 0.00),
(12, 'E0002', 'CESAR', '', 'ABUBO', 'cesar.abubo@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-04-11 08:48:02', 'profile_697d962d450256.84780797.png', 550.00, 10, 1, 110.00),
(13, 'E0003', 'MARLON', '', 'AGUILAR', 'marlon.aguilar@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-04-12 23:21:17', 'profile_6996a4ed2ef972.23487330.png', 600.00, 20, 0, 10.00),
(19, 'E0009', 'JANLY', NULL, 'BELINO', 'janly.belino@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:24:27', NULL, 650.00, 10, 1, 0.00),
(20, 'E0010', 'MENUEL', NULL, 'BENITEZ', 'menuel.benitez@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-04-08 01:50:48', NULL, 600.00, 10, 0, 0.00),
(21, 'E0011', 'GELMAR', NULL, 'BERNACHEA', 'gelmar.bernachea@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:24:13', NULL, 500.00, 10, 1, 0.00),
(22, 'E0012', 'JOMAR', NULL, 'CABANBAN', 'jomar.cabanban@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 07:01:04', NULL, 600.00, 22, 1, 0.00),
(23, 'E0013', 'MARIO', NULL, 'CABANBAN', 'mario.cabanban@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:23:08', NULL, 600.00, 10, 1, 0.00),
(24, 'E0014', 'KELVIN', NULL, 'CALDERON', 'kelvin.calderon@example.com', '$2y$10$d7rLs2lPiCob5CCSgaZVqO3w9jDwWaFIIsH7eqpaZ1/7myUv319q2', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-13 08:26:11', NULL, 500.00, 21, 1, 0.00),
(25, 'E0015', 'FLORANTE', NULL, 'CALUZA', 'florante.caluza@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 07:01:04', NULL, 600.00, 22, 1, 0.00),
(26, 'E0016', 'MELVIN', NULL, 'CAMPOS', 'melvin.campos@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:26:08', NULL, 600.00, 21, 1, 0.00),
(27, 'E0017', 'JERWIN', NULL, 'CAMPOS', 'jerwin.campos@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:22:14', NULL, 550.00, 21, 1, 0.00),
(28, 'E0018', 'BENJIE', NULL, 'CARAS', 'benjie.caras@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:23:27', NULL, 700.00, 10, 1, 0.00),
(29, 'E0019', 'BONJO', NULL, 'DACUMOS', 'bonjo.dacumos@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:25:08', NULL, 500.00, 10, 1, 0.00),
(30, 'E0020', 'RYAN', NULL, 'DEOCARIS', 'ryan.deocaris@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:26:16', NULL, 500.00, 21, 1, 0.00),
(31, 'E0021', 'BEN', NULL, 'ESTEPA', 'ben.estepa@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:23:41', NULL, 600.00, 10, 1, 0.00),
(32, 'E0022', 'MAR DAVE', NULL, 'FLORES', 'mardave.flores@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:28:21', NULL, 550.00, 20, 1, 0.00),
(33, 'E0023', 'ALBERT', NULL, 'FONTANILLA', 'albert.fontanilla@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:22:14', NULL, 550.00, 20, 1, 0.00),
(34, 'E0024', 'JOHN WILSON', NULL, 'FONTANILLA', 'johnwilson.fontanilla@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 07:51:18', NULL, 600.00, 20, 1, 0.00),
(35, 'E0025', 'LEO', NULL, 'GURTIZA', 'leo.gurtiza@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:23:47', NULL, 600.00, 10, 1, 0.00),
(36, 'E0026', 'JOSE', NULL, 'IGLECIAS', 'jose.iglecias@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:26:21', NULL, 500.00, 21, 1, 0.00),
(37, 'E0027', 'JEFFREY', NULL, 'JIMENEZ', 'jeffrey.jimenez@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:28:37', NULL, 550.00, 20, 1, 0.00),
(38, 'E0028', 'WILSON', NULL, 'LICTAOA', 'wilson.lictaoa@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:26:29', NULL, 500.00, 21, 1, 0.00),
(39, 'E0029', 'LORETO', NULL, 'MABALO', 'loreto.mabalo@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:24:06', NULL, 600.00, 10, 1, 0.00),
(40, 'E0030', 'ROMEL', NULL, 'MALLARE', 'romel.mallare@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:26:06', NULL, 800.00, 10, 1, 0.00),
(41, 'E0031', 'SAMUEL SR.', NULL, 'MARQUEZ', 'samuel.marquez@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:28:57', NULL, 500.00, 20, 1, 0.00),
(42, 'E0032', 'ROLLY', NULL, 'MARZAN', 'rolly.marzan@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:26:46', NULL, 600.00, 21, 1, 0.00),
(43, 'E0033', 'RONALD', NULL, 'MARZAN', 'ronald.marzan@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:24:22', NULL, 600.00, 10, 1, 0.00),
(44, 'E0034', 'WILSON', NULL, 'MARZAN', 'wilson.marzan@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:26:38', NULL, 600.00, 21, 1, 0.00),
(45, 'E0035', 'MARVIN', NULL, 'MIRANDA', 'marvin.miranda@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:21:12', NULL, 600.00, 22, 1, 0.00),
(46, 'E0036', 'JOE', NULL, 'MONTERDE', 'joe.monterde@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 07:01:04', NULL, 700.00, 10, 1, 0.00),
(47, 'E0037', 'ALDRED', NULL, 'NATARTE', 'aldred.natarte@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 07:01:04', NULL, 600.00, 10, 1, 0.00),
(48, 'E0038', 'ARNOLD', NULL, 'NERIDO', 'arnold.nerido@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 01:58:24', NULL, 600.00, 21, 1, 0.00),
(49, 'E0039', 'RONEL', NULL, 'NOSES', 'ronel.noses@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:26:40', NULL, 500.00, 10, 1, 0.00),
(50, 'E0040', 'DANNY', NULL, 'PADILLA', 'danny.padilla@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:25:01', NULL, 500.00, 10, 1, 0.00),
(51, 'E0041', 'EDGAR', NULL, 'PANEDA', 'edgar.paneda@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 07:01:04', NULL, 550.00, 26, 1, 0.00),
(52, 'E0042', 'JEREMY', NULL, 'PIMENTEL', 'jeremy.pimentel@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:25:09', NULL, 550.00, 10, 1, 0.00),
(53, 'E0043', 'MIGUEL', NULL, 'PREPOSI', 'miguel.preposi@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:25:16', NULL, 600.00, 10, 1, 0.00),
(54, 'E0044', 'JUN', NULL, 'ROAQUIN', 'jun.roaquin@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 07:01:04', NULL, 600.00, 26, 1, 0.00),
(55, 'E0045', 'RICKMAR', NULL, 'SANTOS', 'rickmar.santos@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:34:19', NULL, 500.00, 28, 1, 0.00),
(56, 'E0046', 'RIO', NULL, 'SILOY', 'rio.siloy@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:34:48', NULL, 750.00, 32, 1, 0.00),
(57, 'E0047', 'NORMAN', NULL, 'TARAPE', 'norman.tarape@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:29:59', NULL, 500.00, 10, 1, 0.00),
(58, 'E0048', 'HILMAR', NULL, 'TATUNAY', 'hilmar.tatunay@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-09 00:33:51', NULL, 500.00, 20, 1, 0.00),
(59, 'E0049', 'KENNETH JOHN', NULL, 'UGAS', 'kennethjohn.ugas@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:25:30', NULL, 600.00, 10, 1, 0.00),
(60, 'E0050', 'CLYDE JUSTINE', NULL, 'VASADRE', 'clydejustine.vasadre@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 07:01:04', NULL, 500.00, 28, 1, 0.00),
(63, 'ENG-2026-0005', 'JOYLENE F.', NULL, 'BALANON', 'joylene.balanon@example.com', '$2y$10$6sbxv2qIU8i/2KUOVDrUZOLBIHTOvRoI9ApBOwLtYPXN60w8jx4mm', 'Engineer', 'Active', '2026-01-22 07:58:04', '2026-04-10 03:59:02', NULL, 600.00, 20, 0, 0.00),
(122, 'E0053', 'VERGEL', NULL, 'DACUMOS', 'vergel.dacumos@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:22', '2026-02-06 08:48:24', NULL, 600.00, 22, 1, 0.00),
(123, 'E0054', 'REAL RAIN', NULL, 'IVERSON', 'realrain.iverson@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:22', '2026-02-06 08:48:38', NULL, 600.00, 22, 1, 0.00),
(67, 'ADMIN-2026-0002', 'RONALYN', NULL, 'MALLARE', 'ronalyn.mallare@example.com', '$2y$10$s7xQ8p1U.l28nDSgbhYG/uLSvLFL5CA1Weyn0APXBa93lnoX7eANK', 'Admin', 'Active', '2026-01-22 07:58:04', '2026-02-10 08:14:16', NULL, 600.00, 33, 1, 0.00),
(68, 'ENG-2026-0001', 'MICHELLE F.', NULL, 'NORIAL', 'michelle.norial@example.com', '$2y$10$uIk2ehlCc6dssBZzLVITSOucNq/LPXCv2a7cZi5MDquTH7pmmN94O', 'Engineer', 'Active', '2026-01-22 07:58:04', '2026-02-12 02:09:06', NULL, 600.00, 29, 1, 0.00),
(127, 'E0058', 'JHUNEL', NULL, 'CANCHO', 'jhunel.cancho@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:34', '2026-02-09 00:24:48', NULL, 500.00, 10, 1, 0.00),
(124, 'E0055', 'VOHANN', NULL, 'MIRANDA', 'vohann.miranda@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:22', '2026-02-06 08:48:48', NULL, 600.00, 22, 1, 0.00),
(125, 'E0056', 'SONNY', NULL, 'OCCIANO', 'sonny.occiano@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:22', '2026-02-09 00:23:00', NULL, 1400.00, 22, 1, 0.00),
(126, 'E0065', 'RANDY', NULL, 'ATON', 'randy.aton@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:34', '2026-02-06 08:47:34', NULL, 600.00, 10, 1, 0.00),
(120, 'SA-2026-004', 'Marc', '', 'Arzadon', 'arzadon@gmail.com', '$2y$10$qSf327Nylr1l.TkboICD6ujkKmYGEaiTvixotQ.Jh/XP.MYOZsJIe', 'Super Admin', 'Active', '2026-02-06 07:18:15', '2026-04-10 03:58:56', NULL, 600.00, NULL, 0, 0.00),
(137, 'IT-2026-001', 'Daniel', 'Obaldo', 'Rillera', 'danrillera.va@gmail.com', '$2y$10$mOaX9fFaC31bF29mfysmQeb8GgcPjIcxGXmyRHlkgPDS9XJPjLjsu', 'Developer', 'active', '2026-03-12 16:00:00', '2026-03-13 04:28:30', '', 0.00, 33, 1, 0.00),
(115, 'PRO-2026-0001', 'Junell', '', 'Tadina', 'tadina@gmail.com', '$2y$10$Nc0l0GkWV9crcUj7dc1vie4ry1up7kwrYBJGeH5oDSvJlhKCOgUt6', 'Engineer', 'Active', '2026-02-06 07:12:32', '2026-02-10 02:31:56', NULL, 600.00, NULL, 1, 0.00),
(114, 'ENG-2026-0003', 'Julius John', '', 'Echague', 'echague@gmail.com', '$2y$10$5vYYVwzl3qRA1ClmqUBjJu/YM8SrszeIhO6oEtaoFXcuVxIpmvrV2', 'Engineer', 'Active', '2026-02-06 07:12:00', '2026-02-07 07:34:38', NULL, 600.00, NULL, 1, 0.00),
(121, 'E0052', 'JOSHUA', NULL, 'ARQUITOLA', 'joshua.arquitola@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:22', '2026-04-08 01:50:32', NULL, 600.00, 22, 0, 0.00),
(113, 'ENG-2026-0002', 'John Kennedy', '', 'Lucas', 'lucas@gmail.com', '$2y$10$p.ERk7.PwModiMwq61au.ufymZHF/jRpMffS3dQBobbFwEmADEUT.', 'Engineer', 'Active', '2026-02-06 07:11:15', '2026-02-07 07:34:49', NULL, 600.00, NULL, 1, 0.00),
(116, 'ENG-2026-0006', 'Winnielyn Kaye', '', 'Olarte', 'olarte@gmail.com', '$2y$10$1NUUvvknY0mWhdfHYYygheh6Kj1zoCTQSQcxOzPUKNyR28/S4cj7G', 'Engineer', 'Active', '2026-02-06 07:14:59', '2026-02-07 07:35:05', NULL, 600.00, NULL, 1, 0.00),
(117, 'ADMIN-2026-0001', 'ELAINE', 'Torres', 'Aguilar', 'aguilar@gmail.com', '$2y$10$Q0GiyO/e43xHBEwRHNAmvOoh7pu9TEiN3t1Jl1mL39UuhHsv6k8Wq', 'Admin', 'Active', '2026-02-06 07:15:51', '2026-04-10 01:19:50', 'profile_6996a4f55d7335.10207456.png', 600.00, 33, 0, 0.00),
(118, 'SA-2026-002', 'Jason', 'Larkin', 'Wong', 'wong@gmail.com', '$2y$10$TWT37ldw/9w1nEBDLtVgvOS/6gEEM1IJSbthCB/9vHmaeJ7FYuGbC', 'Super Admin', 'Active', '2026-02-06 07:16:34', '2026-02-07 07:33:29', NULL, 600.00, NULL, 1, 0.00),
(119, 'SA-2026-003', 'Lee Aldrich', '', 'Rimando', 'rimando@gmail.com', '$2y$10$BeFRm.XDlPuyZJHLC4Qhw.WZuxW8biClIxAAILz9PEzVaO9gEo92G', 'Super Admin', 'Active', '2026-02-06 07:17:12', '2026-02-07 07:33:18', NULL, 600.00, NULL, 1, 0.00),
(135, 'ADMIN-2026-0003', 'Admin', '', 'Charisse', 'charisse@gmail.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'ADMIN', 'Active', '2026-02-10 07:55:32', '2026-02-10 08:13:48', NULL, 600.00, 33, 1, 0.00),
(129, 'E0060', 'HECTOR', NULL, 'PADICLAS', 'hector.padiclas@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:34', '2026-02-06 08:47:34', NULL, 600.00, 10, 1, 0.00),
(130, 'E0061', 'MARIANO', NULL, 'NERIDO', 'mariano.nerido@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:34', '2026-02-06 08:51:31', NULL, 600.00, 21, 1, 0.00),
(131, 'E0062', 'JAYSON KENNETH', NULL, 'PADILLA', 'jaysonkenneth.padilla@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:34', '2026-02-09 00:31:58', NULL, 500.00, 21, 1, 0.00),
(132, 'E0063', 'JEFFREY', NULL, 'ZAMORA', 'jeffrey.zamora@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:34', '2026-02-09 01:58:11', NULL, 600.00, 21, 1, 0.00),
(133, 'E0064', 'FRANKIE', NULL, 'PADILLA', 'frankie.padilla@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:34', '2026-02-09 00:33:37', NULL, 500.00, 20, 1, 0.00),
(134, 'E0066', 'ROMEO', NULL, 'GURION', 'romeo.gurion@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:50:56', '2026-02-09 00:22:14', NULL, 550.00, 10, 1, 0.00),
(136, 'ADMIN-2026-0004', 'Marjorie', '', 'Garcia', 'garcia@gmail.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'ADMIN', 'Active', '2026-02-10 07:56:55', '2026-02-10 08:19:47', NULL, 600.00, 33, 1, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `employee_leaves`
--

DROP TABLE IF EXISTS `employee_leaves`;
CREATE TABLE IF NOT EXISTS `employee_leaves` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `total_leaves` decimal(5,2) NOT NULL DEFAULT '0.00',
  `used_leaves` decimal(5,2) NOT NULL DEFAULT '0.00',
  `remaining_leaves` decimal(5,2) NOT NULL DEFAULT '0.00',
  `last_credited_month` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_leaves`
--

INSERT INTO `employee_leaves` (`id`, `employee_id`, `total_leaves`, `used_leaves`, `remaining_leaves`, `last_credited_month`, `created_at`, `updated_at`) VALUES
(1, 16, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(2, 17, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(3, 18, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(4, 14, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(5, 6, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(6, 15, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(7, 11, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(8, 12, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(9, 13, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(10, 19, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(11, 20, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(12, 21, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(13, 22, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(14, 23, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(15, 24, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(16, 25, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(17, 26, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(18, 27, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(19, 28, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(20, 29, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(21, 30, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(22, 31, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(23, 32, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(24, 33, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(25, 34, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(26, 35, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(27, 36, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(28, 37, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(29, 38, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(30, 39, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(31, 40, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(32, 41, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(33, 42, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(34, 43, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(35, 44, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(36, 45, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(37, 46, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(38, 47, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(39, 48, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(40, 49, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(41, 50, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(42, 51, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(43, 52, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(44, 53, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(45, 54, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(46, 55, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(47, 56, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(48, 57, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(49, 58, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(50, 59, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(51, 60, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(52, 63, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(53, 122, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(54, 123, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(55, 67, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(56, 68, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(57, 127, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(58, 124, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(59, 125, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(60, 126, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(61, 120, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(62, 137, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(63, 115, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(64, 114, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(65, 121, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(66, 113, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(67, 116, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(68, 117, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(69, 118, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(70, 119, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(71, 135, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(72, 129, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(73, 130, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(74, 131, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(75, 132, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(76, 133, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(77, 134, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35'),
(78, 136, 1.00, 0.00, 1.00, '2026-04-01', '2026-04-11 03:57:29', '2026-04-11 03:58:35');

-- --------------------------------------------------------

--
-- Table structure for table `employee_location_consent`
--

DROP TABLE IF EXISTS `employee_location_consent`;
CREATE TABLE IF NOT EXISTS `employee_location_consent` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `consent_given` tinyint(1) DEFAULT '0',
  `consent_date` timestamp NULL DEFAULT NULL,
  `consent_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  KEY `idx_employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Track employee consent for location tracking';

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
  `leave_request_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_employee_read` (`employee_id`,`is_read`),
  KEY `idx_created` (`created_at` DESC),
  KEY `overtime_request_id` (`overtime_request_id`),
  KEY `cash_advance_id` (`cash_advance_id`),
  KEY `leave_request_id` (`leave_request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=227 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_notifications`
--

INSERT INTO `employee_notifications` (`id`, `employee_id`, `overtime_request_id`, `notification_type`, `title`, `message`, `is_read`, `created_at`, `read_at`, `cash_advance_id`, `leave_request_id`) VALUES
(17, 67, 10, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 5 hours overtime for BCDA - Admin. Reason: asdf', 0, '2026-02-24 07:16:36', NULL, NULL, NULL),
(18, 117, 10, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 5 hours overtime for BCDA - Admin. Reason: asdf', 0, '2026-02-24 07:16:36', NULL, NULL, NULL),
(19, 135, 10, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 5 hours overtime for BCDA - Admin. Reason: asdf', 0, '2026-02-24 07:16:36', NULL, NULL, NULL),
(20, 136, 10, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 5 hours overtime for BCDA - Admin. Reason: asdf', 0, '2026-02-24 07:16:36', NULL, NULL, NULL),
(21, 6, 10, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 5.00 hours on 2026-02-24. Awaiting final approval.', 1, '2026-02-24 07:18:32', '2026-04-06 02:42:51', NULL, NULL),
(22, 120, 10, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 5.00 hours on 2026-02-24. Awaiting final approval.', 0, '2026-02-24 07:18:32', NULL, NULL, NULL),
(23, 118, 10, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 5.00 hours on 2026-02-24. Awaiting final approval.', 0, '2026-02-24 07:18:32', NULL, NULL, NULL),
(24, 119, 10, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 5.00 hours on 2026-02-24. Awaiting final approval.', 0, '2026-02-24 07:18:32', NULL, NULL, NULL),
(26, 67, 11, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 4 hours overtime for BCDA - Admin. Reason: ftyughjmn', 0, '2026-02-24 08:55:04', NULL, NULL, NULL),
(27, 117, 11, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 4 hours overtime for BCDA - Admin. Reason: ftyughjmn', 0, '2026-02-24 08:55:04', NULL, NULL, NULL),
(28, 135, 11, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 4 hours overtime for BCDA - Admin. Reason: ftyughjmn', 0, '2026-02-24 08:55:04', NULL, NULL, NULL),
(29, 136, 11, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 4 hours overtime for BCDA - Admin. Reason: ftyughjmn', 0, '2026-02-24 08:55:04', NULL, NULL, NULL),
(30, 6, 11, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 4.00 hours on 2026-02-24. Awaiting final approval.', 1, '2026-02-24 08:55:17', '2026-04-06 02:42:51', NULL, NULL),
(31, 120, 11, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 4.00 hours on 2026-02-24. Awaiting final approval.', 0, '2026-02-24 08:55:17', NULL, NULL, NULL),
(32, 118, 11, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 4.00 hours on 2026-02-24. Awaiting final approval.', 0, '2026-02-24 08:55:17', NULL, NULL, NULL),
(33, 119, 11, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 4.00 hours on 2026-02-24. Awaiting final approval.', 0, '2026-02-24 08:55:17', NULL, NULL, NULL),
(35, 67, 12, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 45 hours overtime for BCDA - Admin. Reason: yuhvjkm', 0, '2026-02-24 08:58:51', NULL, NULL, NULL),
(36, 117, 12, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 45 hours overtime for BCDA - Admin. Reason: yuhvjkm', 0, '2026-02-24 08:58:51', NULL, NULL, NULL),
(37, 135, 12, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 45 hours overtime for BCDA - Admin. Reason: yuhvjkm', 0, '2026-02-24 08:58:51', NULL, NULL, NULL),
(38, 136, 12, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 45 hours overtime for BCDA - Admin. Reason: yuhvjkm', 0, '2026-02-24 08:58:51', NULL, NULL, NULL),
(39, 6, 12, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 45.00 hours on 2026-02-24. Awaiting final approval.', 1, '2026-02-24 08:59:07', '2026-04-06 02:42:51', NULL, NULL),
(40, 120, 12, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 45.00 hours on 2026-02-24. Awaiting final approval.', 0, '2026-02-24 08:59:07', NULL, NULL, NULL),
(41, 118, 12, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 45.00 hours on 2026-02-24. Awaiting final approval.', 0, '2026-02-24 08:59:07', NULL, NULL, NULL),
(42, 119, 12, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 45.00 hours on 2026-02-24. Awaiting final approval.', 0, '2026-02-24 08:59:07', NULL, NULL, NULL),
(44, 67, 13, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 2 hours overtime for BCDA - Admin. Reason: adsgf', 0, '2026-02-24 23:49:21', NULL, NULL, NULL),
(45, 117, 13, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 2 hours overtime for BCDA - Admin. Reason: adsgf', 0, '2026-02-24 23:49:21', NULL, NULL, NULL),
(46, 135, 13, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 2 hours overtime for BCDA - Admin. Reason: adsgf', 0, '2026-02-24 23:49:21', NULL, NULL, NULL),
(47, 136, 13, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 2 hours overtime for BCDA - Admin. Reason: adsgf', 0, '2026-02-24 23:49:21', NULL, NULL, NULL),
(48, 6, 13, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 2.00 hours on 2026-02-25. Awaiting final approval.', 1, '2026-02-24 23:49:43', '2026-04-06 02:42:51', NULL, NULL),
(49, 120, 13, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 2.00 hours on 2026-02-25. Awaiting final approval.', 0, '2026-02-24 23:49:43', NULL, NULL, NULL),
(50, 118, 13, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 2.00 hours on 2026-02-25. Awaiting final approval.', 0, '2026-02-24 23:49:43', NULL, NULL, NULL),
(51, 119, 13, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 2.00 hours on 2026-02-25. Awaiting final approval.', 0, '2026-02-24 23:49:43', NULL, NULL, NULL),
(53, 67, 14, 'overtime_request', 'New Overtime Request', 'JERWIN CAMPOS requested 2 hours overtime for BCDA - Admin. Reason: asdf', 0, '2026-02-25 00:03:17', NULL, NULL, NULL),
(54, 117, 14, 'overtime_request', 'New Overtime Request', 'JERWIN CAMPOS requested 2 hours overtime for BCDA - Admin. Reason: asdf', 0, '2026-02-25 00:03:17', NULL, NULL, NULL),
(55, 135, 14, 'overtime_request', 'New Overtime Request', 'JERWIN CAMPOS requested 2 hours overtime for BCDA - Admin. Reason: asdf', 0, '2026-02-25 00:03:17', NULL, NULL, NULL),
(56, 136, 14, 'overtime_request', 'New Overtime Request', 'JERWIN CAMPOS requested 2 hours overtime for BCDA - Admin. Reason: asdf', 0, '2026-02-25 00:03:17', NULL, NULL, NULL),
(57, 6, 14, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for JERWIN CAMPOS - 2.00 hours on 2026-02-25. Awaiting final approval.', 1, '2026-02-25 00:03:31', '2026-04-06 02:42:51', NULL, NULL),
(58, 120, 14, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for JERWIN CAMPOS - 2.00 hours on 2026-02-25. Awaiting final approval.', 0, '2026-02-25 00:03:31', NULL, NULL, NULL),
(59, 118, 14, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for JERWIN CAMPOS - 2.00 hours on 2026-02-25. Awaiting final approval.', 0, '2026-02-25 00:03:31', NULL, NULL, NULL),
(60, 119, 14, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for JERWIN CAMPOS - 2.00 hours on 2026-02-25. Awaiting final approval.', 0, '2026-02-25 00:03:31', NULL, NULL, NULL),
(62, 6, 13, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 2.00 hours on 2026-02-25. Awaiting final approval.', 1, '2026-02-25 00:03:33', '2026-04-06 02:42:51', NULL, NULL),
(63, 120, 13, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 2.00 hours on 2026-02-25. Awaiting final approval.', 0, '2026-02-25 00:03:33', NULL, NULL, NULL),
(64, 118, 13, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 2.00 hours on 2026-02-25. Awaiting final approval.', 0, '2026-02-25 00:03:33', NULL, NULL, NULL),
(65, 119, 13, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 2.00 hours on 2026-02-25. Awaiting final approval.', 0, '2026-02-25 00:03:33', NULL, NULL, NULL),
(67, 6, 12, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 45.00 hours on 2026-02-24. Awaiting final approval.', 1, '2026-02-25 00:03:34', '2026-04-06 02:42:51', NULL, NULL),
(68, 120, 12, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 45.00 hours on 2026-02-24. Awaiting final approval.', 0, '2026-02-25 00:03:34', NULL, NULL, NULL),
(69, 118, 12, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 45.00 hours on 2026-02-24. Awaiting final approval.', 0, '2026-02-25 00:03:34', NULL, NULL, NULL),
(70, 119, 12, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 45.00 hours on 2026-02-24. Awaiting final approval.', 0, '2026-02-25 00:03:34', NULL, NULL, NULL),
(72, 6, 11, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 4.00 hours on 2026-02-24. Awaiting final approval.', 1, '2026-02-25 00:03:35', '2026-04-06 02:42:51', NULL, NULL),
(73, 120, 11, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 4.00 hours on 2026-02-24. Awaiting final approval.', 0, '2026-02-25 00:03:35', NULL, NULL, NULL),
(74, 118, 11, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 4.00 hours on 2026-02-24. Awaiting final approval.', 0, '2026-02-25 00:03:35', NULL, NULL, NULL),
(75, 119, 11, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 4.00 hours on 2026-02-24. Awaiting final approval.', 0, '2026-02-25 00:03:35', NULL, NULL, NULL),
(77, 6, 10, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 5.00 hours on 2026-02-24. Awaiting final approval.', 1, '2026-02-25 00:03:35', '2026-04-06 02:42:51', NULL, NULL),
(78, 120, 10, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 5.00 hours on 2026-02-24. Awaiting final approval.', 0, '2026-02-25 00:03:35', NULL, NULL, NULL),
(79, 118, 10, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 5.00 hours on 2026-02-24. Awaiting final approval.', 0, '2026-02-25 00:03:35', NULL, NULL, NULL),
(80, 119, 10, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for KELVIN CALDERON - 5.00 hours on 2026-02-24. Awaiting final approval.', 0, '2026-02-25 00:03:35', NULL, NULL, NULL),
(88, 67, 15, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 1 hours overtime for BCDA - Admin on 2026-02-26. Reason: ASD', 0, '2026-02-25 00:10:55', NULL, NULL, NULL),
(89, 117, 15, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 1 hours overtime for BCDA - Admin on 2026-02-26. Reason: ASD', 0, '2026-02-25 00:10:55', NULL, NULL, NULL),
(90, 135, 15, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 1 hours overtime for BCDA - Admin on 2026-02-26. Reason: ASD', 0, '2026-02-25 00:10:55', NULL, NULL, NULL),
(91, 136, 15, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 1 hours overtime for BCDA - Admin on 2026-02-26. Reason: ASD', 0, '2026-02-25 00:10:55', NULL, NULL, NULL),
(93, 67, 16, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 1 hours overtime for BCDA - CCTV on 2026-02-26. Reason: tygjh', 0, '2026-02-25 00:20:31', NULL, NULL, NULL),
(94, 117, 16, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 1 hours overtime for BCDA - CCTV on 2026-02-26. Reason: tygjh', 0, '2026-02-25 00:20:31', NULL, NULL, NULL),
(95, 135, 16, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 1 hours overtime for BCDA - CCTV on 2026-02-26. Reason: tygjh', 0, '2026-02-25 00:20:31', NULL, NULL, NULL),
(96, 136, 16, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 1 hours overtime for BCDA - CCTV on 2026-02-26. Reason: tygjh', 0, '2026-02-25 00:20:31', NULL, NULL, NULL),
(97, 6, 16, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for MICHELLE F. NORIAL - 1.00 hours on 2026-02-26. Awaiting final approval.', 1, '2026-02-25 00:21:05', '2026-04-06 02:42:51', NULL, NULL),
(98, 120, 16, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for MICHELLE F. NORIAL - 1.00 hours on 2026-02-26. Awaiting final approval.', 0, '2026-02-25 00:21:05', NULL, NULL, NULL),
(99, 118, 16, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for MICHELLE F. NORIAL - 1.00 hours on 2026-02-26. Awaiting final approval.', 0, '2026-02-25 00:21:05', NULL, NULL, NULL),
(100, 119, 16, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for MICHELLE F. NORIAL - 1.00 hours on 2026-02-26. Awaiting final approval.', 0, '2026-02-25 00:21:05', NULL, NULL, NULL),
(102, 6, 15, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for MICHELLE F. NORIAL - 1.00 hours on 2026-02-26. Awaiting final approval.', 1, '2026-02-25 00:21:07', '2026-04-06 02:42:51', NULL, NULL),
(103, 120, 15, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for MICHELLE F. NORIAL - 1.00 hours on 2026-02-26. Awaiting final approval.', 0, '2026-02-25 00:21:07', NULL, NULL, NULL),
(104, 118, 15, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for MICHELLE F. NORIAL - 1.00 hours on 2026-02-26. Awaiting final approval.', 0, '2026-02-25 00:21:07', NULL, NULL, NULL),
(105, 119, 15, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for MICHELLE F. NORIAL - 1.00 hours on 2026-02-26. Awaiting final approval.', 0, '2026-02-25 00:21:07', NULL, NULL, NULL),
(108, 67, 17, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 5.00 hours overtime for BCDA - Admin. Reason: drthgh', 0, '2026-02-25 08:49:58', NULL, NULL, NULL),
(109, 117, 17, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 5.00 hours overtime for BCDA - Admin. Reason: drthgh', 0, '2026-02-25 08:49:58', NULL, NULL, NULL),
(110, 135, 17, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 5.00 hours overtime for BCDA - Admin. Reason: drthgh', 0, '2026-02-25 08:49:58', NULL, NULL, NULL),
(111, 136, 17, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 5.00 hours overtime for BCDA - Admin. Reason: drthgh', 0, '2026-02-25 08:49:58', NULL, NULL, NULL),
(112, 6, 17, 'overtime_approved', 'Overtime Approved', 'Your overtime request for KELVIN CALDERON on 2026-02-25 has been approved. Hours: 5.00, Project: BCDA - Admin', 1, '2026-02-25 08:50:01', '2026-04-06 02:42:51', NULL, NULL),
(113, 68, 15, 'overtime_approved', 'Overtime Approved', 'Your overtime request for MICHELLE F. NORIAL on 2026-02-26 has been approved. Hours: 1.00, Project: BCDA - Admin', 1, '2026-02-25 08:50:04', '2026-03-14 05:41:30', NULL, NULL),
(114, 67, 18, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 5.00 hours overtime for BCDA - Admin. Reason: dchnbnvdgth', 0, '2026-02-25 08:50:39', NULL, NULL, NULL),
(115, 117, 18, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 5.00 hours overtime for BCDA - Admin. Reason: dchnbnvdgth', 0, '2026-02-25 08:50:39', NULL, NULL, NULL),
(116, 135, 18, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 5.00 hours overtime for BCDA - Admin. Reason: dchnbnvdgth', 0, '2026-02-25 08:50:39', NULL, NULL, NULL),
(117, 136, 18, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 5.00 hours overtime for BCDA - Admin. Reason: dchnbnvdgth', 0, '2026-02-25 08:50:39', NULL, NULL, NULL),
(118, 6, 18, 'overtime_approved', 'Overtime Approved', 'Your overtime request for KELVIN CALDERON on 2026-02-25 has been approved. Hours: 5.00, Project: BCDA - Admin', 1, '2026-02-25 08:50:44', '2026-04-06 02:42:51', NULL, NULL),
(119, 63, NULL, 'leave_submitted', 'Leave Request Submitted', 'Your leave request for 1 day(s) on 2026-03-30 (Sick) has been submitted and is pending approval.', 1, '2026-03-28 06:16:49', '2026-03-28 06:18:13', NULL, 1),
(120, 6, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-03-30 (Sick). Reason: Agtakki ak mam hanak makatakaw hoolow blocks', 1, '2026-03-28 06:16:49', '2026-04-06 02:42:51', NULL, 1),
(121, 67, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-03-30 (Sick). Reason: Agtakki ak mam hanak makatakaw hoolow blocks', 0, '2026-03-28 06:16:51', NULL, NULL, 1),
(122, 120, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-03-30 (Sick). Reason: Agtakki ak mam hanak makatakaw hoolow blocks', 0, '2026-03-28 06:16:51', NULL, NULL, 1),
(123, 117, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-03-30 (Sick). Reason: Agtakki ak mam hanak makatakaw hoolow blocks', 0, '2026-03-28 06:16:51', NULL, NULL, 1),
(124, 118, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-03-30 (Sick). Reason: Agtakki ak mam hanak makatakaw hoolow blocks', 0, '2026-03-28 06:16:51', NULL, NULL, 1),
(125, 119, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-03-30 (Sick). Reason: Agtakki ak mam hanak makatakaw hoolow blocks', 0, '2026-03-28 06:16:51', NULL, NULL, 1),
(126, 135, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-03-30 (Sick). Reason: Agtakki ak mam hanak makatakaw hoolow blocks', 0, '2026-03-28 06:16:51', NULL, NULL, 1),
(127, 136, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-03-30 (Sick). Reason: Agtakki ak mam hanak makatakaw hoolow blocks', 0, '2026-03-28 06:16:51', NULL, NULL, 1),
(128, 63, NULL, 'leave_approved', 'Leave Request Approved', 'Your leave request for 1.0 day(s) on 2026-03-30 (Sick) has been approved.', 1, '2026-03-28 06:16:59', '2026-03-28 06:18:12', NULL, 1),
(129, 63, NULL, 'leave_submitted', 'Leave Request Submitted', 'Your leave request for 1 day(s) on 2026-03-31 (Personal) has been submitted and is pending approval.', 1, '2026-03-28 06:17:43', '2026-03-28 06:18:11', NULL, 2),
(130, 6, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-03-31 (Personal). Reason: Apanak agtakaw hollow blocks', 1, '2026-03-28 06:17:43', '2026-04-06 02:42:51', NULL, 2),
(131, 67, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-03-31 (Personal). Reason: Apanak agtakaw hollow blocks', 0, '2026-03-28 06:17:44', NULL, NULL, 2),
(132, 120, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-03-31 (Personal). Reason: Apanak agtakaw hollow blocks', 0, '2026-03-28 06:17:44', NULL, NULL, 2),
(133, 117, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-03-31 (Personal). Reason: Apanak agtakaw hollow blocks', 0, '2026-03-28 06:17:44', NULL, NULL, 2),
(134, 118, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-03-31 (Personal). Reason: Apanak agtakaw hollow blocks', 0, '2026-03-28 06:17:44', NULL, NULL, 2),
(135, 119, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-03-31 (Personal). Reason: Apanak agtakaw hollow blocks', 0, '2026-03-28 06:17:44', NULL, NULL, 2),
(136, 135, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-03-31 (Personal). Reason: Apanak agtakaw hollow blocks', 0, '2026-03-28 06:17:44', NULL, NULL, 2),
(137, 136, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-03-31 (Personal). Reason: Apanak agtakaw hollow blocks', 0, '2026-03-28 06:17:44', NULL, NULL, 2),
(138, 63, NULL, 'leave_rejected', 'Leave Request Rejected', 'Your leave request for 1.0 day(s) on 2026-03-31 (Personal) was rejected. Reason: pasensya kan ta madi', 1, '2026-03-28 06:18:03', '2026-03-28 06:18:10', NULL, 2),
(139, 63, NULL, 'leave_submitted', 'Leave Request Submitted', 'Your leave request for 1 day(s) on 2026-04-02 (Sick) has been submitted and is pending approval.', 0, '2026-03-28 06:18:52', NULL, NULL, 3),
(140, 6, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-04-02 (Sick). Reason: Nothing personal', 1, '2026-03-28 06:18:52', '2026-04-06 02:42:51', NULL, 3),
(141, 67, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-04-02 (Sick). Reason: Nothing personal', 0, '2026-03-28 06:18:52', NULL, NULL, 3),
(142, 120, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-04-02 (Sick). Reason: Nothing personal', 0, '2026-03-28 06:18:52', NULL, NULL, 3),
(143, 117, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-04-02 (Sick). Reason: Nothing personal', 0, '2026-03-28 06:18:52', NULL, NULL, 3),
(144, 118, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-04-02 (Sick). Reason: Nothing personal', 0, '2026-03-28 06:18:52', NULL, NULL, 3),
(145, 119, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-04-02 (Sick). Reason: Nothing personal', 0, '2026-03-28 06:18:52', NULL, NULL, 3),
(146, 135, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-04-02 (Sick). Reason: Nothing personal', 0, '2026-03-28 06:18:52', NULL, NULL, 3),
(147, 136, NULL, 'leave_request', 'New Leave Request', 'JOYLENE F. BALANON requested 1 day(s) leave for 2026-04-02 (Sick). Reason: Nothing personal', 0, '2026-03-28 06:18:52', NULL, NULL, 3),
(148, 63, NULL, 'leave_approved', 'Leave Request Approved', 'Your leave request for 1.0 day(s) on 2026-04-02 (Sick) has been approved.', 0, '2026-03-28 06:19:01', NULL, NULL, 3),
(149, 68, 19, 'overtime_submitted', 'Overtime Request Submitted', 'Your overtime request for 1 hours on 2026-04-01 at MAIN OFFICE has been submitted and is pending approval.', 0, '2026-04-01 07:20:35', NULL, NULL, NULL),
(150, 6, 19, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 1 hours overtime for MAIN OFFICE on 2026-04-01. Reason: asdfasdf', 1, '2026-04-01 07:20:35', '2026-04-06 02:42:51', NULL, NULL),
(151, 67, 19, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 1 hours overtime for MAIN OFFICE on 2026-04-01. Reason: asdfasdf', 0, '2026-04-01 07:20:36', NULL, NULL, NULL),
(152, 120, 19, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 1 hours overtime for MAIN OFFICE on 2026-04-01. Reason: asdfasdf', 0, '2026-04-01 07:20:36', NULL, NULL, NULL),
(153, 117, 19, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 1 hours overtime for MAIN OFFICE on 2026-04-01. Reason: asdfasdf', 0, '2026-04-01 07:20:36', NULL, NULL, NULL),
(154, 118, 19, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 1 hours overtime for MAIN OFFICE on 2026-04-01. Reason: asdfasdf', 0, '2026-04-01 07:20:36', NULL, NULL, NULL),
(155, 119, 19, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 1 hours overtime for MAIN OFFICE on 2026-04-01. Reason: asdfasdf', 0, '2026-04-01 07:20:36', NULL, NULL, NULL),
(156, 135, 19, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 1 hours overtime for MAIN OFFICE on 2026-04-01. Reason: asdfasdf', 0, '2026-04-01 07:20:36', NULL, NULL, NULL),
(157, 136, 19, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 1 hours overtime for MAIN OFFICE on 2026-04-01. Reason: asdfasdf', 0, '2026-04-01 07:20:36', NULL, NULL, NULL),
(158, 137, 19, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 1 hours overtime for MAIN OFFICE on 2026-04-01. Reason: asdfasdf', 0, '2026-04-01 07:20:36', NULL, NULL, NULL),
(159, 68, NULL, 'cash_advance_pending', 'Cash Advance Submitted', 'Your cash advance request for ₱1,234.00 has been submitted and is pending approval.', 0, '2026-04-01 07:20:37', NULL, 15, NULL),
(160, 6, NULL, 'cash_advance_request', 'New Cash Advance Request', 'MICHELLE F. NORIAL requested ₱1,234.00 cash advance. Reason: asdf', 1, '2026-04-01 07:20:37', '2026-04-06 02:42:51', 15, NULL),
(161, 120, NULL, 'cash_advance_request', 'New Cash Advance Request', 'MICHELLE F. NORIAL requested ₱1,234.00 cash advance. Reason: asdf', 0, '2026-04-01 07:20:37', NULL, 15, NULL),
(162, 137, NULL, 'cash_advance_request', 'New Cash Advance Request', 'MICHELLE F. NORIAL requested ₱1,234.00 cash advance. Reason: asdf', 0, '2026-04-01 07:20:37', NULL, 15, NULL),
(163, 118, NULL, 'cash_advance_request', 'New Cash Advance Request', 'MICHELLE F. NORIAL requested ₱1,234.00 cash advance. Reason: asdf', 0, '2026-04-01 07:20:37', NULL, 15, NULL),
(164, 119, NULL, 'cash_advance_request', 'New Cash Advance Request', 'MICHELLE F. NORIAL requested ₱1,234.00 cash advance. Reason: asdf', 0, '2026-04-01 07:20:37', NULL, 15, NULL),
(165, 6, 19, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for MICHELLE F. NORIAL - 1.00 hours on 2026-04-01. Awaiting final approval.', 1, '2026-04-01 07:21:17', '2026-04-06 02:42:51', NULL, NULL),
(166, 120, 19, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for MICHELLE F. NORIAL - 1.00 hours on 2026-04-01. Awaiting final approval.', 0, '2026-04-01 07:21:18', NULL, NULL, NULL),
(167, 137, 19, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for MICHELLE F. NORIAL - 1.00 hours on 2026-04-01. Awaiting final approval.', 0, '2026-04-01 07:21:18', NULL, NULL, NULL),
(168, 118, 19, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for MICHELLE F. NORIAL - 1.00 hours on 2026-04-01. Awaiting final approval.', 0, '2026-04-01 07:21:18', NULL, NULL, NULL),
(169, 119, 19, 'overtime_pre_approved', 'Overtime Pre-Approved by Admin', 'Admin Admin pre-approved overtime request for MICHELLE F. NORIAL - 1.00 hours on 2026-04-01. Awaiting final approval.', 0, '2026-04-01 07:21:18', NULL, NULL, NULL),
(170, 68, 19, 'overtime_pre_approved', 'Overtime Pre-Approved', 'Your overtime request for 1.00 hours on 2026-04-01 has been pre-approved by Admin Admin and is now awaiting final approval from Super Admin.', 0, '2026-04-01 07:21:18', NULL, NULL, NULL),
(171, 6, NULL, 'cash_advance_pre_approved', 'Cash Advance Pre-Approved by Admin', 'Admin Admin pre-approved cash advance request for ₱1,234.00 - Awaiting final approval.', 1, '2026-04-01 07:21:22', '2026-04-06 02:42:51', 15, NULL),
(172, 120, NULL, 'cash_advance_pre_approved', 'Cash Advance Pre-Approved by Admin', 'Admin Admin pre-approved cash advance request for ₱1,234.00 - Awaiting final approval.', 0, '2026-04-01 07:21:22', NULL, 15, NULL),
(173, 137, NULL, 'cash_advance_pre_approved', 'Cash Advance Pre-Approved by Admin', 'Admin Admin pre-approved cash advance request for ₱1,234.00 - Awaiting final approval.', 0, '2026-04-01 07:21:22', NULL, 15, NULL),
(174, 118, NULL, 'cash_advance_pre_approved', 'Cash Advance Pre-Approved by Admin', 'Admin Admin pre-approved cash advance request for ₱1,234.00 - Awaiting final approval.', 0, '2026-04-01 07:21:22', NULL, 15, NULL),
(175, 119, NULL, 'cash_advance_pre_approved', 'Cash Advance Pre-Approved by Admin', 'Admin Admin pre-approved cash advance request for ₱1,234.00 - Awaiting final approval.', 0, '2026-04-01 07:21:22', NULL, 15, NULL),
(176, 68, 20, 'overtime_submitted', 'Overtime Request Submitted', 'Your overtime request for 3 hours on 2026-04-02 at BCDA - CCA has been submitted and is pending approval.', 0, '2026-04-01 07:21:47', NULL, NULL, NULL),
(177, 6, 20, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 3 hours overtime for BCDA - CCA on 2026-04-02. Reason: adsfs', 1, '2026-04-01 07:21:47', '2026-04-06 02:42:51', NULL, NULL),
(178, 67, 20, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 3 hours overtime for BCDA - CCA on 2026-04-02. Reason: adsfs', 0, '2026-04-01 07:21:48', NULL, NULL, NULL),
(179, 120, 20, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 3 hours overtime for BCDA - CCA on 2026-04-02. Reason: adsfs', 0, '2026-04-01 07:21:48', NULL, NULL, NULL),
(180, 117, 20, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 3 hours overtime for BCDA - CCA on 2026-04-02. Reason: adsfs', 0, '2026-04-01 07:21:48', NULL, NULL, NULL),
(181, 118, 20, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 3 hours overtime for BCDA - CCA on 2026-04-02. Reason: adsfs', 0, '2026-04-01 07:21:48', NULL, NULL, NULL),
(182, 119, 20, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 3 hours overtime for BCDA - CCA on 2026-04-02. Reason: adsfs', 0, '2026-04-01 07:21:48', NULL, NULL, NULL),
(183, 135, 20, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 3 hours overtime for BCDA - CCA on 2026-04-02. Reason: adsfs', 0, '2026-04-01 07:21:48', NULL, NULL, NULL),
(184, 136, 20, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 3 hours overtime for BCDA - CCA on 2026-04-02. Reason: adsfs', 0, '2026-04-01 07:21:48', NULL, NULL, NULL),
(185, 137, 20, 'overtime_request', 'New Overtime Request', 'MICHELLE F. NORIAL requested 3 hours overtime for BCDA - CCA on 2026-04-02. Reason: adsfs', 0, '2026-04-01 07:21:48', NULL, NULL, NULL),
(186, 68, 20, 'overtime_approved', 'Overtime Approved', 'Your overtime request for 3.00 hours on 2026-04-02 has been approved.', 0, '2026-04-01 07:24:59', NULL, NULL, NULL),
(187, 117, 21, 'overtime_submitted', 'Overtime Request Submitted', 'Your overtime request for 2 hours on 2026-04-08 at BCDA - Admin has been submitted and is pending approval.', 0, '2026-04-09 03:11:22', NULL, NULL, NULL),
(188, 6, 21, 'overtime_request', 'New Overtime Request', 'ELAINE Aguilar requested 2 hours overtime for BCDA - Admin on 2026-04-08. Reason: awet', 0, '2026-04-09 03:11:22', NULL, NULL, NULL),
(189, 67, 21, 'overtime_request', 'New Overtime Request', 'ELAINE Aguilar requested 2 hours overtime for BCDA - Admin on 2026-04-08. Reason: awet', 0, '2026-04-09 03:11:23', NULL, NULL, NULL),
(190, 120, 21, 'overtime_request', 'New Overtime Request', 'ELAINE Aguilar requested 2 hours overtime for BCDA - Admin on 2026-04-08. Reason: awet', 0, '2026-04-09 03:11:23', NULL, NULL, NULL),
(191, 117, 21, 'overtime_request', 'New Overtime Request', 'ELAINE Aguilar requested 2 hours overtime for BCDA - Admin on 2026-04-08. Reason: awet', 0, '2026-04-09 03:11:23', NULL, NULL, NULL),
(192, 118, 21, 'overtime_request', 'New Overtime Request', 'ELAINE Aguilar requested 2 hours overtime for BCDA - Admin on 2026-04-08. Reason: awet', 0, '2026-04-09 03:11:23', NULL, NULL, NULL),
(193, 119, 21, 'overtime_request', 'New Overtime Request', 'ELAINE Aguilar requested 2 hours overtime for BCDA - Admin on 2026-04-08. Reason: awet', 0, '2026-04-09 03:11:23', NULL, NULL, NULL),
(194, 135, 21, 'overtime_request', 'New Overtime Request', 'ELAINE Aguilar requested 2 hours overtime for BCDA - Admin on 2026-04-08. Reason: awet', 0, '2026-04-09 03:11:23', NULL, NULL, NULL),
(195, 136, 21, 'overtime_request', 'New Overtime Request', 'ELAINE Aguilar requested 2 hours overtime for BCDA - Admin on 2026-04-08. Reason: awet', 0, '2026-04-09 03:11:23', NULL, NULL, NULL),
(196, 137, 21, 'overtime_request', 'New Overtime Request', 'ELAINE Aguilar requested 2 hours overtime for BCDA - Admin on 2026-04-08. Reason: awet', 0, '2026-04-09 03:11:23', NULL, NULL, NULL),
(197, 117, 22, 'overtime_submitted', 'Overtime Request Submitted', 'Your overtime request for 2 hours on 2026-04-10 at BCDA - CCTV has been submitted and is pending approval.', 0, '2026-04-09 03:42:44', NULL, NULL, NULL),
(198, 6, 22, 'overtime_request', 'New Overtime Request', 'ELAINE Aguilar requested 2 hours overtime for BCDA - CCTV on 2026-04-10. Reason: asdfasdfddgfdfgadfgadsfssssssssssssssssssssssssssssssssssssssssssssssssssssssss', 0, '2026-04-09 03:42:44', NULL, NULL, NULL),
(199, 67, 22, 'overtime_request', 'New Overtime Request', 'ELAINE Aguilar requested 2 hours overtime for BCDA - CCTV on 2026-04-10. Reason: asdfasdfddgfdfgadfgadsfssssssssssssssssssssssssssssssssssssssssssssssssssssssss', 0, '2026-04-09 03:42:45', NULL, NULL, NULL),
(200, 120, 22, 'overtime_request', 'New Overtime Request', 'ELAINE Aguilar requested 2 hours overtime for BCDA - CCTV on 2026-04-10. Reason: asdfasdfddgfdfgadfgadsfssssssssssssssssssssssssssssssssssssssssssssssssssssssss', 0, '2026-04-09 03:42:45', NULL, NULL, NULL),
(201, 117, 22, 'overtime_request', 'New Overtime Request', 'ELAINE Aguilar requested 2 hours overtime for BCDA - CCTV on 2026-04-10. Reason: asdfasdfddgfdfgadfgadsfssssssssssssssssssssssssssssssssssssssssssssssssssssssss', 0, '2026-04-09 03:42:45', NULL, NULL, NULL),
(202, 118, 22, 'overtime_request', 'New Overtime Request', 'ELAINE Aguilar requested 2 hours overtime for BCDA - CCTV on 2026-04-10. Reason: asdfasdfddgfdfgadfgadsfssssssssssssssssssssssssssssssssssssssssssssssssssssssss', 0, '2026-04-09 03:42:45', NULL, NULL, NULL),
(203, 119, 22, 'overtime_request', 'New Overtime Request', 'ELAINE Aguilar requested 2 hours overtime for BCDA - CCTV on 2026-04-10. Reason: asdfasdfddgfdfgadfgadsfssssssssssssssssssssssssssssssssssssssssssssssssssssssss', 0, '2026-04-09 03:42:45', NULL, NULL, NULL),
(204, 135, 22, 'overtime_request', 'New Overtime Request', 'ELAINE Aguilar requested 2 hours overtime for BCDA - CCTV on 2026-04-10. Reason: asdfasdfddgfdfgadfgadsfssssssssssssssssssssssssssssssssssssssssssssssssssssssss', 0, '2026-04-09 03:42:45', NULL, NULL, NULL),
(205, 136, 22, 'overtime_request', 'New Overtime Request', 'ELAINE Aguilar requested 2 hours overtime for BCDA - CCTV on 2026-04-10. Reason: asdfasdfddgfdfgadfgadsfssssssssssssssssssssssssssssssssssssssssssssssssssssssss', 0, '2026-04-09 03:42:45', NULL, NULL, NULL),
(206, 137, 22, 'overtime_request', 'New Overtime Request', 'ELAINE Aguilar requested 2 hours overtime for BCDA - CCTV on 2026-04-10. Reason: asdfasdfddgfdfgadfgadsfssssssssssssssssssssssssssssssssssssssssssssssssssssssss', 0, '2026-04-09 03:42:45', NULL, NULL, NULL),
(207, 117, 22, 'overtime_approved', 'Overtime Approved', 'Your overtime request for 2.00 hours on 2026-04-10 has been approved.', 0, '2026-04-10 05:34:43', NULL, NULL, NULL),
(208, 117, 21, 'overtime_approved', 'Overtime Approved', 'Your overtime request for ELAINE Aguilar on 2026-04-08 has been approved. Hours: 2.00, Project: BCDA - Admin', 0, '2026-04-11 02:03:50', NULL, NULL, NULL),
(209, 6, 23, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 03 hours overtime for BCDA - Admin. Reason: test', 0, '2026-04-11 02:04:08', NULL, NULL, NULL),
(210, 67, 23, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 03 hours overtime for BCDA - Admin. Reason: test', 0, '2026-04-11 02:04:08', NULL, NULL, NULL),
(211, 120, 23, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 03 hours overtime for BCDA - Admin. Reason: test', 0, '2026-04-11 02:04:08', NULL, NULL, NULL),
(212, 117, 23, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 03 hours overtime for BCDA - Admin. Reason: test', 0, '2026-04-11 02:04:08', NULL, NULL, NULL),
(213, 118, 23, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 03 hours overtime for BCDA - Admin. Reason: test', 0, '2026-04-11 02:04:08', NULL, NULL, NULL),
(214, 119, 23, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 03 hours overtime for BCDA - Admin. Reason: test', 0, '2026-04-11 02:04:08', NULL, NULL, NULL),
(215, 135, 23, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 03 hours overtime for BCDA - Admin. Reason: test', 0, '2026-04-11 02:04:08', NULL, NULL, NULL),
(216, 136, 23, 'overtime_request', 'New Overtime Request', 'KELVIN CALDERON requested 03 hours overtime for BCDA - Admin. Reason: test', 0, '2026-04-11 02:04:08', NULL, NULL, NULL),
(217, 6, 23, 'overtime_approved', 'Overtime Approved', 'Your overtime request for KELVIN CALDERON on 2026-04-11 has been approved. Hours: 3.00, Project: BCDA - Admin', 0, '2026-04-11 02:04:12', NULL, NULL, NULL),
(218, 6, 24, 'overtime_request', 'New Overtime Request', 'JEFFREY JIMENEZ requested 2 hours overtime for BCDA - Admin. Reason: April 10 2026', 0, '2026-04-11 08:34:07', NULL, NULL, NULL),
(219, 67, 24, 'overtime_request', 'New Overtime Request', 'JEFFREY JIMENEZ requested 2 hours overtime for BCDA - Admin. Reason: April 10 2026', 0, '2026-04-11 08:34:07', NULL, NULL, NULL),
(220, 120, 24, 'overtime_request', 'New Overtime Request', 'JEFFREY JIMENEZ requested 2 hours overtime for BCDA - Admin. Reason: April 10 2026', 0, '2026-04-11 08:34:07', NULL, NULL, NULL),
(221, 117, 24, 'overtime_request', 'New Overtime Request', 'JEFFREY JIMENEZ requested 2 hours overtime for BCDA - Admin. Reason: April 10 2026', 0, '2026-04-11 08:34:07', NULL, NULL, NULL),
(222, 118, 24, 'overtime_request', 'New Overtime Request', 'JEFFREY JIMENEZ requested 2 hours overtime for BCDA - Admin. Reason: April 10 2026', 0, '2026-04-11 08:34:07', NULL, NULL, NULL),
(223, 119, 24, 'overtime_request', 'New Overtime Request', 'JEFFREY JIMENEZ requested 2 hours overtime for BCDA - Admin. Reason: April 10 2026', 0, '2026-04-11 08:34:07', NULL, NULL, NULL),
(224, 135, 24, 'overtime_request', 'New Overtime Request', 'JEFFREY JIMENEZ requested 2 hours overtime for BCDA - Admin. Reason: April 10 2026', 0, '2026-04-11 08:34:07', NULL, NULL, NULL),
(225, 136, 24, 'overtime_request', 'New Overtime Request', 'JEFFREY JIMENEZ requested 2 hours overtime for BCDA - Admin. Reason: April 10 2026', 0, '2026-04-11 08:34:07', NULL, NULL, NULL),
(226, 6, 24, 'overtime_approved', 'Overtime Approved', 'Your overtime request for JEFFREY JIMENEZ on 2026-04-11 has been approved. Hours: 2.00, Project: BCDA - Admin', 0, '2026-04-11 08:34:11', NULL, NULL, NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `e_signatures`
--

INSERT INTO `e_signatures` (`id`, `employee_id`, `signature_type`, `signature_image`, `signature_data`, `created_at`, `updated_at`, `is_active`) VALUES
(11, 12, 'employee', 'uploads/signatures/sig_12_employee_1771392496.png', 'data:image/png;base64,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', '2026-02-18 05:28:16', '2026-02-18 05:28:16', 1);

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
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
CREATE TABLE IF NOT EXISTS `leave_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `leave_date` date NOT NULL,
  `leave_type` enum('Sick','Vacation','Personal','Emergency') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Personal',
  `days_requested` decimal(3,1) NOT NULL DEFAULT '1.0',
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `requested_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requested_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_leave_date` (`leave_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `employee_id`, `leave_date`, `leave_type`, `days_requested`, `reason`, `status`, `requested_by`, `requested_at`, `approved_by`, `approved_at`, `rejection_reason`) VALUES
(1, 63, '2026-03-30', 'Sick', 1.0, 'Agtakki ak mam hanak makatakaw hoolow blocks', 'approved', 'JOYLENE F. BALANON', '2026-03-28 06:16:49', 0, '2026-03-28 14:16:59', NULL),
(2, 63, '2026-03-31', 'Personal', 1.0, 'Apanak agtakaw hollow blocks', 'rejected', 'JOYLENE F. BALANON', '2026-03-28 06:17:43', 0, '2026-03-28 14:18:03', 'pasensya kan ta madi'),
(3, 63, '2026-04-02', 'Sick', 1.0, 'Nothing personal', 'approved', 'JOYLENE F. BALANON', '2026-03-28 06:18:52', 0, '2026-03-28 14:19:01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `leave_transactions`
--

DROP TABLE IF EXISTS `leave_transactions`;
CREATE TABLE IF NOT EXISTS `leave_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `transaction_type` enum('credit','debit','adjustment') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(5,2) NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_date` date NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_date` (`employee_id`,`reference_date`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=157 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_transactions`
--

INSERT INTO `leave_transactions` (`id`, `employee_id`, `transaction_type`, `amount`, `description`, `reference_date`, `created_by`, `created_at`) VALUES
(1, 16, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(2, 17, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(3, 18, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(4, 14, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(5, 6, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(6, 15, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(7, 11, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(8, 12, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(9, 13, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(10, 19, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(11, 20, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(12, 21, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(13, 22, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(14, 23, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(15, 24, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(16, 25, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(17, 26, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(18, 27, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(19, 28, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(20, 29, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(21, 30, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(22, 31, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(23, 32, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(24, 33, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(25, 34, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(26, 35, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(27, 36, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(28, 37, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(29, 38, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(30, 39, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(31, 40, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(32, 41, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(33, 42, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(34, 43, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(35, 44, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(36, 45, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(37, 46, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(38, 47, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(39, 48, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(40, 49, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(41, 50, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(42, 51, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(43, 52, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(44, 53, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(45, 54, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(46, 55, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(47, 56, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(48, 57, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(49, 58, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(50, 59, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(51, 60, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(52, 63, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(53, 122, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(54, 123, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(55, 67, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(56, 68, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(57, 127, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(58, 124, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(59, 125, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(60, 126, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(61, 120, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(62, 137, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(63, 115, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(64, 114, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(65, 121, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(66, 113, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(67, 116, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(68, 117, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(69, 118, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(70, 119, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(71, 135, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(72, 129, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(73, 130, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(74, 131, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(75, 132, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(76, 133, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(77, 134, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(78, 136, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 03:58:35'),
(79, 16, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(80, 17, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(81, 18, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(82, 14, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(83, 6, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(84, 15, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(85, 11, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(86, 12, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(87, 13, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(88, 19, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(89, 20, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(90, 21, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(91, 22, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(92, 23, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(93, 24, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(94, 25, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(95, 26, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(96, 27, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(97, 28, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(98, 29, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(99, 30, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(100, 31, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(101, 32, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(102, 33, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(103, 34, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(104, 35, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(105, 36, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(106, 37, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(107, 38, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(108, 39, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(109, 40, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(110, 41, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(111, 42, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(112, 43, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(113, 44, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(114, 45, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(115, 46, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(116, 47, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(117, 48, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(118, 49, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(119, 50, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(120, 51, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(121, 52, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(122, 53, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(123, 54, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(124, 55, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(125, 56, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(126, 57, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(127, 58, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(128, 59, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(129, 60, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(130, 63, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(131, 122, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(132, 123, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(133, 67, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(134, 68, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(135, 127, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(136, 124, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(137, 125, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(138, 126, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(139, 120, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(140, 137, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(141, 115, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(142, 114, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(143, 121, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(144, 113, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(145, 116, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(146, 117, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(147, 118, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(148, 119, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(149, 135, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(150, 129, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(151, 130, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(152, 131, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(153, 132, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(154, 133, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(155, 134, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27'),
(156, 136, 'credit', 1.00, 'Monthly leave credit', '2026-04-01', 0, '2026-04-11 04:41:27');

-- --------------------------------------------------------

--
-- Table structure for table `location_logs`
--

DROP TABLE IF EXISTS `location_logs`;
CREATE TABLE IF NOT EXISTS `location_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `attendance_id` int DEFAULT NULL,
  `action_type` enum('clock_in','clock_out','qr_scan','manual_override') COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `accuracy_meters` float DEFAULT NULL,
  `branch_id` int DEFAULT NULL,
  `distance_from_branch_meters` int DEFAULT NULL,
  `device_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_validated` tinyint(1) DEFAULT '0',
  `validation_failure_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_date` (`employee_id`,`created_at`),
  KEY `idx_location` (`latitude`,`longitude`),
  KEY `idx_attendance_id` (`attendance_id`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail for geolocation data - 90 day retention';

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
  `status` enum('pending','approved','rejected','pre-approved') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `overtime_requests`
--

INSERT INTO `overtime_requests` (`id`, `employee_id`, `branch_name`, `request_date`, `requested_hours`, `overtime_reason`, `status`, `requested_by`, `requested_by_user_id`, `requested_at`, `approved_by`, `approved_at`, `rejection_reason`, `attendance_id`) VALUES
(10, 24, 'BCDA - Admin', '2026-02-24', 5.00, 'asdf', 'approved', 'KELVIN CALDERON', 68, '2026-02-24 07:16:36', 'Admin', '2026-02-25 00:10:15', NULL, 1081),
(11, 24, 'BCDA - Admin', '2026-02-24', 4.00, 'ftyughjmn', 'approved', 'KELVIN CALDERON', 68, '2026-02-24 08:55:04', 'Admin', '2026-02-25 00:10:15', NULL, 1081),
(12, 24, 'BCDA - Admin', '2026-02-24', 45.00, 'yuhvjkm', 'approved', 'KELVIN CALDERON', 68, '2026-02-24 08:58:51', 'Admin', '2026-02-25 00:10:15', NULL, 1081),
(13, 24, 'BCDA - Admin', '2026-02-25', 2.00, 'adsgf', 'approved', 'KELVIN CALDERON', 68, '2026-02-24 23:49:21', 'Admin', '2026-02-25 00:10:14', NULL, 1081),
(14, 27, 'BCDA - Admin', '2026-02-25', 2.00, 'asdf', 'approved', 'JERWIN CAMPOS', 68, '2026-02-25 00:03:17', 'Admin', '2026-02-25 00:10:11', NULL, 1084),
(15, 68, 'BCDA - Admin', '2026-02-26', 1.00, 'ASD', 'approved', 'MICHELLE F. NORIAL', 68, '2026-02-25 00:10:55', 'Admin', '2026-02-25 08:50:04', NULL, 1080),
(16, 68, 'BCDA - CCTV', '2026-02-26', 1.00, 'tygjh', 'rejected', 'MICHELLE F. NORIAL', 68, '2026-02-25 00:20:31', 'Admin', '2026-02-25 00:21:33', 'dehrst', NULL),
(17, 24, 'BCDA - Admin', '2026-02-25', 5.00, 'drthgh', 'approved', 'KELVIN CALDERON', 6, '2026-02-25 08:49:58', 'Admin', '2026-02-25 08:50:01', NULL, 1081),
(18, 24, 'BCDA - Admin', '2026-02-25', 5.00, 'dchnbnvdgth', 'approved', 'KELVIN CALDERON', 6, '2026-02-25 08:50:39', 'Admin', '2026-02-25 08:50:44', NULL, 1081),
(19, 68, 'MAIN OFFICE', '2026-04-01', 1.00, 'asdfasdf', 'pre-approved', 'MICHELLE F. NORIAL', 68, '2026-04-01 07:20:35', 'Admin', '2026-04-01 07:21:17', NULL, NULL),
(20, 68, 'BCDA - CCA', '2026-04-02', 3.00, 'adsfs', 'approved', 'MICHELLE F. NORIAL', 68, '2026-04-01 07:21:47', 'Admin', '2026-04-01 07:24:59', NULL, 1112),
(21, 117, 'BCDA - Admin', '2026-04-08', 2.00, 'awet', 'approved', 'ELAINE Aguilar', 117, '2026-04-09 03:11:22', 'Admin', '2026-04-11 02:03:50', NULL, 1193),
(22, 117, 'BCDA - CCTV', '2026-04-10', 2.00, 'asdfasdfddgfdfgadfgadsfssssssssssssssssssssssssssssssssssssssssssssssssssssssss', 'approved', 'ELAINE Aguilar', 117, '2026-04-09 03:42:44', 'Admin', '2026-04-10 05:34:43', NULL, 1186),
(23, 24, 'BCDA - Admin', '2026-04-11', 3.00, 'test', 'approved', 'KELVIN CALDERON', 6, '2026-04-11 02:04:08', 'Admin', '2026-04-11 02:04:12', NULL, 1194),
(24, 37, 'BCDA - Admin', '2026-04-11', 2.00, 'April 10 2026', 'approved', 'JEFFREY JIMENEZ', 6, '2026-04-11 08:34:07', 'Admin', '2026-04-11 08:34:11', NULL, 1195);

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
-- Table structure for table `push_subscriptions`
--

DROP TABLE IF EXISTS `push_subscriptions`;
CREATE TABLE IF NOT EXISTS `push_subscriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `endpoint` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `p256dh` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `auth` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_endpoint` (`user_id`,`endpoint`(255)),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores Web Push API subscription data for Super Admin notifications';

--
-- Dumping data for table `push_subscriptions`
--

INSERT INTO `push_subscriptions` (`id`, `user_id`, `endpoint`, `p256dh`, `auth`, `created_at`, `updated_at`) VALUES
(1, 6, 'https://fcm.googleapis.com/fcm/send/eSsiEJvZFyY:APA91bHpYkLFR4K_spdzizdib2VM11w2ZQYWbjmRZMo4WU5pWc80NXGoG3dvw4iygij16tlNO9FYmPvVD2Jl7y8EJUaEERZOyeylHhagCI5YmkmfATSwj7iHbZOuo1Ab9BDEcew0o83K', 'BF/aXWwD0VsQUJLhIpZhhLx3IPNBZYUKqdyHSkIevkFkZSP2XvlBQyTpl9Og67nqh5+6Z5rmsTmnMPaT2bmcLBI=', 'jm0m7ZYliMxxEAfCk8c2BA==', '2026-03-14 02:08:10', '2026-03-27 07:21:35'),
(2, 68, 'https://fcm.googleapis.com/fcm/send/eSsiEJvZFyY:APA91bHpYkLFR4K_spdzizdib2VM11w2ZQYWbjmRZMo4WU5pWc80NXGoG3dvw4iygij16tlNO9FYmPvVD2Jl7y8EJUaEERZOyeylHhagCI5YmkmfATSwj7iHbZOuo1Ab9BDEcew0o83K', 'BF/aXWwD0VsQUJLhIpZhhLx3IPNBZYUKqdyHSkIevkFkZSP2XvlBQyTpl9Og67nqh5+6Z5rmsTmnMPaT2bmcLBI=', 'jm0m7ZYliMxxEAfCk8c2BA==', '2026-03-14 05:41:26', '2026-04-01 07:21:49'),
(3, 63, 'https://fcm.googleapis.com/fcm/send/dkIyNvXSWd8:APA91bEf3s3JoT4yn5_YwUOLfNXjMC5pbD5Km8Nw19vJ_iAzUygZR11wasHATXaOvvrFpPpr2ZXt_ebY7ptYM3DnvdOheKQu9d41Ky3dMbOoHDfkPd3EGbfZSJ_7fXWju7gQtLixQPWE', 'BJM7GHcDQWrGqxQITWQnBBAGi+erzQMkPgDLmkqFCRiZQElBHb7bxi1MqTxVkjMXErebd5kqb2h6bVnjVG9sXas=', 'dn1qlzX/oTI+rEc8B/TzuQ==', '2026-03-28 06:27:00', '2026-03-28 06:44:19'),
(4, 6, 'https://fcm.googleapis.com/fcm/send/ewdi7IfCAO8:APA91bEs6Mk7KfyvzEWd9ALRDSpY3ViZ_Gcxh_TpppR2Xo3XeuY7mWzmaDosS3O7sPZzeIiXpoz9l1lDWcs7WyiLTuze5izIlQ1DDTsKSkK94-4Nqw1JusyCZtBCIamzutGXGJpSOvp1', 'BM3KXa4OhVNoJcBzKY7EARkgPk3eB5qt+84XuPjzJvj3hJEG/H7W1qXvQ155sSs4auIzWQ5MPt7SHqdU1cedapQ=', 'CkDGg1Vp9o8RUcXO+GGQTQ==', '2026-04-09 03:53:54', '2026-04-13 02:15:41'),
(5, 117, 'https://fcm.googleapis.com/fcm/send/ewdi7IfCAO8:APA91bEs6Mk7KfyvzEWd9ALRDSpY3ViZ_Gcxh_TpppR2Xo3XeuY7mWzmaDosS3O7sPZzeIiXpoz9l1lDWcs7WyiLTuze5izIlQ1DDTsKSkK94-4Nqw1JusyCZtBCIamzutGXGJpSOvp1', 'BM3KXa4OhVNoJcBzKY7EARkgPk3eB5qt+84XuPjzJvj3hJEG/H7W1qXvQ155sSs4auIzWQ5MPt7SHqdU1cedapQ=', 'CkDGg1Vp9o8RUcXO+GGQTQ==', '2026-04-10 01:43:25', '2026-04-10 02:04:23'),
(6, 6, 'https://fcm.googleapis.com/fcm/send/fa6KUQ5QIFs:APA91bGXshXQuVgNIDv0H8MNPKfRQtlsoNl-FpbSfqddYdudJFGEr0b6NDy62GlOGTGE7nNzDulaC1mOq4zxTXU63z9VqN4IGi1hxl532rlHzjuPz9aokkFJ3lVzoYBQk38N_QHbBHwo', 'BN4wRtbjnJwEHYDQ/Dc/2CkXOQVyfnyisZ4YWHTGA4bdQwPf3/fp67ch/+wjmmL0H7+Vrx/xRk+IeViGahVM3eY=', 'JjyWDC9KwXduzWQ1rYjElQ==', '2026-04-13 05:45:04', '2026-04-13 05:45:04');

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
) ENGINE=MyISAM AUTO_INCREMENT=203 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rate_limit`
--

INSERT INTO `rate_limit` (`id`, `ip`, `user_id`, `timestamp`) VALUES
(202, '::1', 0, 1775780523);

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
  `view_type` enum('weekly','monthly','range') COLLATE utf8mb4_unicode_ci DEFAULT 'weekly',
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
) ENGINE=InnoDB AUTO_INCREMENT=15966 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `weekly_payroll_reports`
--

INSERT INTO `weekly_payroll_reports` (`id`, `employee_id`, `report_year`, `report_month`, `week_number`, `view_type`, `branch_id`, `days_worked`, `total_hours`, `daily_rate`, `basic_pay`, `ot_hours`, `ot_rate`, `ot_amount`, `performance_allowance`, `gross_pay`, `gross_plus_allowance`, `ca_deduction`, `sss_deduction`, `philhealth_deduction`, `pagibig_deduction`, `sss_loan`, `total_deductions`, `take_home_pay`, `status`, `payment_status`, `created_by`, `finalized_by`, `finalized_at`, `created_at`, `updated_at`) VALUES
(15941, 24, 2026, 2, 4, 'weekly', 21, 1, 0, 500.00, 500.00, 0.00, 62.50, 0.00, 0.00, 500.00, 500.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 500.00, '', 'Not Paid', 1, NULL, NULL, '2026-02-28 02:10:24', '2026-02-28 02:10:24'),
(15942, 26, 2026, 2, 4, 'weekly', 21, 1, 0, 600.00, 600.00, 0.00, 75.00, 0.00, 0.00, 600.00, 600.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 600.00, '', 'Not Paid', 1, NULL, NULL, '2026-02-28 02:10:24', '2026-02-28 02:10:24'),
(15943, 27, 2026, 2, 4, 'weekly', 21, 1, 0, 550.00, 550.00, 0.00, 68.75, 0.00, 0.00, 550.00, 550.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 550.00, '', 'Not Paid', 1, NULL, NULL, '2026-02-28 02:10:24', '2026-02-28 02:10:24'),
(15944, 36, 2026, 2, 4, 'weekly', 21, 1, 0, 500.00, 500.00, 0.00, 62.50, 0.00, 0.00, 500.00, 500.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 500.00, '', 'Not Paid', 1, NULL, NULL, '2026-02-28 02:10:24', '2026-02-28 02:10:24'),
(15945, 12, 2026, 3, 1, 'weekly', NULL, 0, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 100.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Draft', 'Not Paid', NULL, NULL, NULL, '2026-03-13 02:47:38', '2026-03-13 02:47:38'),
(15946, 12, 2026, 3, 1, 'weekly', NULL, 0, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 100.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Draft', 'Not Paid', NULL, NULL, NULL, '2026-03-13 02:48:08', '2026-03-13 02:48:08'),
(15947, 12, 2026, 3, 2, 'weekly', NULL, 0, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 100.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Draft', 'Not Paid', NULL, NULL, NULL, '2026-03-13 02:50:02', '2026-03-13 02:50:02'),
(15948, 12, 2026, 3, 1, 'weekly', NULL, 0, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 100.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Draft', 'Not Paid', NULL, NULL, NULL, '2026-03-13 02:51:14', '2026-03-13 02:51:14'),
(15949, 12, 2026, 3, 2, 'weekly', NULL, 0, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 100.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Draft', 'Not Paid', NULL, NULL, NULL, '2026-03-13 02:52:36', '2026-03-13 02:52:36'),
(15950, 12, 2026, 3, 1, 'weekly', NULL, 0, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 100.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Draft', 'Not Paid', NULL, NULL, NULL, '2026-03-13 02:54:24', '2026-03-13 02:54:24'),
(15951, 12, 2026, 3, 2, 'weekly', NULL, 0, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 100.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Draft', 'Not Paid', NULL, NULL, NULL, '2026-03-13 02:57:14', '2026-03-13 02:57:14'),
(15952, 13, 2026, 3, 2, 'weekly', NULL, 0, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 100.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Draft', 'Not Paid', NULL, NULL, NULL, '2026-03-13 02:57:24', '2026-03-13 02:57:24'),
(15953, 12, 2026, 3, 2, 'weekly', NULL, 0, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 100.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Draft', 'Not Paid', NULL, NULL, NULL, '2026-03-13 02:58:11', '2026-03-13 02:58:11'),
(15954, 12, 2026, 3, 2, 'weekly', NULL, 0, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 100.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Draft', 'Not Paid', NULL, NULL, NULL, '2026-03-13 03:02:59', '2026-03-13 03:02:59'),
(15955, 12, 2026, 3, 2, 'weekly', NULL, 0, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 100.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Draft', 'Not Paid', NULL, NULL, NULL, '2026-03-13 03:06:05', '2026-03-13 03:06:05'),
(15956, 12, 2026, 3, 2, 'weekly', NULL, 0, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 200.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Draft', 'Not Paid', NULL, NULL, NULL, '2026-03-13 03:36:05', '2026-03-13 03:36:05'),
(15957, 12, 2026, 3, 2, 'weekly', NULL, 0, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Draft', 'Not Paid', NULL, NULL, NULL, '2026-03-13 06:56:38', '2026-03-13 06:56:38'),
(15958, 13, 2026, 3, 2, 'weekly', NULL, 0, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'Draft', 'Not Paid', NULL, NULL, NULL, '2026-03-13 06:56:48', '2026-03-13 06:56:48'),
(15959, 12, 2026, 4, 2, 'weekly', 10, 0, 0, 550.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 500.00, 500.00, -500.00, 'Draft', 'Not Paid', NULL, NULL, NULL, '2026-04-08 03:31:34', '2026-04-08 03:31:42'),
(15960, 12, 2026, 4, 2, '', 10, 0, 0, 550.00, 0.00, 0.00, 0.00, 0.00, 10.00, 0.00, 10.00, 0.00, 0.00, 0.00, 0.00, 10.00, 10.00, 0.00, 'Draft', 'Not Paid', NULL, NULL, NULL, '2026-04-11 08:29:27', '2026-04-11 08:37:48'),
(15961, 13, 2026, 4, 2, 'range', 20, 0, 0, 600.00, 0.00, 0.00, 0.00, 0.00, 10.00, 0.00, 10.00, 0.00, 0.00, 0.00, 0.00, 10.00, 10.00, 0.00, 'Draft', 'Not Paid', NULL, NULL, NULL, '2026-04-11 08:40:36', '2026-04-12 23:21:21');

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
