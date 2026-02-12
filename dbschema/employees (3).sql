-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 12, 2026 at 01:05 AM
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
(14, 'E0004', 'NOEL', NULL, 'ARIZ', 'noel.ariz@example.com', '9f0c3c0c2aef2cfafc8e5ed4b1fed480', 'Worker', 'Active', '2026-01-22 07:58:04', '2026-02-06 08:21:52', NULL, 550.00, 10),
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
(63, 'ENG-2026-0005', 'JOYLENE F.', NULL, 'BALANON', 'joylene.balanon@example.com', '$2y$10$6sbxv2qIU8i/2KUOVDrUZOLBIHTOvRoI9ApBOwLtYPXN60w8jx4mm', 'Engineer', 'Active', '2026-01-22 07:58:04', '2026-02-07 07:34:04', NULL, 600.00, 28),
(122, 'E0053', 'VERGEL', NULL, 'DACUMOS', 'vergel.dacumos@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:22', '2026-02-06 08:48:24', NULL, 600.00, 22),
(123, 'E0054', 'REAL RAIN', NULL, 'IVERSON', 'realrain.iverson@example.com', 'df0156a0e0f8f16e44f3878b6be24a0d', 'Worker', 'Active', '2026-02-06 08:47:22', '2026-02-06 08:48:38', NULL, 600.00, 22),
(67, 'ADMIN-2026-0002', 'RONALYN', NULL, 'MALLARE', 'ronalyn.mallare@example.com', '$2y$10$s7xQ8p1U.l28nDSgbhYG/uLSvLFL5CA1Weyn0APXBa93lnoX7eANK', 'Admin', 'Active', '2026-01-22 07:58:04', '2026-02-10 08:14:16', NULL, 600.00, 33),
(68, 'ENG-2026-0001', 'MICHELLE F.', NULL, 'NORIAL', 'michelle.norial@example.com', '$2y$10$S4M8tSg0lZoPpy7Nq1jthOM.uVyphIDJYYb1GPjiqAdar0Q9VCFm.', 'Engineer', 'Active', '2026-01-22 07:58:04', '2026-02-07 07:30:24', NULL, 600.00, 29),
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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
