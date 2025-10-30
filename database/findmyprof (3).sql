-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 30, 2025 at 04:20 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `findmyprof`
--

-- --------------------------------------------------------

--
-- Table structure for table `buildings`
--

CREATE TABLE `buildings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `buildings`
--

INSERT INTO `buildings` (`id`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES
(3, 'ACAD', 'Academic Building', '', '2025-10-17 10:38:55', '2025-10-17 10:38:55'),
(4, 'TECH', 'Technovation Building', 'Techno', '2025-10-17 10:59:18', '2025-10-17 10:59:18'),
(5, 'ELEX', 'CITTE Building', '', '2025-10-28 09:23:48', '2025-10-28 09:23:48'),
(6, 'COLB', 'Comlab', '', '2025-10-28 09:58:15', '2025-10-28 09:58:15');

-- --------------------------------------------------------

--
-- Table structure for table `instructor_logs`
--

CREATE TABLE `instructor_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `faculty_user_id` bigint(20) UNSIGNED NOT NULL,
  `action` enum('status_update','checkin','checkout','schedule_auto') NOT NULL,
  `status` varchar(32) NOT NULL,
  `room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `source` enum('manual','qr','schedule') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `instructor_logs`
--

INSERT INTO `instructor_logs` (`id`, `faculty_user_id`, `action`, `status`, `room_id`, `note`, `source`, `created_at`) VALUES
(2, 3, 'checkin', 'Available', 7, NULL, 'qr', '2025-10-28 02:29:35'),
(3, 3, '', '', NULL, '', 'manual', '2025-10-28 06:30:36'),
(4, 3, '', 'Meeting', NULL, 'building=Academic Building', 'manual', '2025-10-28 06:31:22'),
(5, 5, 'checkin', 'Available', 11, NULL, 'qr', '2025-10-28 06:50:08'),
(6, 3, 'checkin', 'Available', 11, NULL, 'qr', '2025-10-28 09:26:29'),
(7, 3, 'checkin', 'Available', 7, NULL, 'qr', '2025-10-28 09:41:14'),
(8, 3, 'checkout', 'Out', 7, NULL, 'qr', '2025-10-28 10:01:56'),
(9, 3, 'checkin', 'Available', 7, NULL, 'qr', '2025-10-30 12:21:30'),
(10, 3, 'checkout', 'Out', 7, NULL, 'qr', '2025-10-30 12:21:59'),
(11, 3, 'checkin', 'Available', 7, NULL, 'qr', '2025-10-30 12:31:48'),
(12, 3, 'checkout', 'Out', 7, NULL, 'qr', '2025-10-30 12:32:15'),
(13, 3, 'checkin', 'Available', 11, NULL, 'qr', '2025-10-30 12:32:15'),
(14, 3, 'checkin', 'Available', 7, NULL, 'qr', '2025-10-30 12:37:33'),
(15, 3, 'checkout', 'Out', 7, NULL, 'qr', '2025-10-30 12:37:43'),
(16, 3, 'checkout', 'Out', 7, NULL, 'qr', '2025-10-30 12:37:43'),
(17, 3, 'checkin', 'Available', 7, NULL, 'qr', '2025-10-30 13:12:29'),
(18, 3, 'checkout', 'Out', 7, NULL, 'qr', '2025-10-30 13:12:42'),
(19, 3, 'checkin', 'Available', 11, NULL, 'qr', '2025-10-30 13:12:42'),
(20, 3, 'checkout', 'Out', 11, NULL, 'qr', '2025-10-30 14:10:38'),
(21, 3, 'checkin', 'Available', 7, NULL, 'qr', '2025-10-30 14:18:03'),
(22, 3, '', '', 7, NULL, 'manual', '2025-10-30 14:18:26'),
(23, 3, 'checkout', 'Out', 7, NULL, 'qr', '2025-10-30 14:26:00'),
(24, 3, 'checkin', 'Available', 11, NULL, 'qr', '2025-10-30 14:26:12'),
(25, 3, '', '', 11, NULL, 'manual', '2025-10-30 14:26:22'),
(26, 3, 'checkout', 'Out', 11, NULL, 'qr', '2025-10-30 14:45:51'),
(27, 3, 'checkin', 'Available', 7, NULL, 'qr', '2025-10-30 14:46:00'),
(28, 3, '', '', 7, NULL, 'manual', '2025-10-30 14:46:06'),
(29, 3, 'checkout', 'Out', 7, NULL, 'qr', '2025-10-30 14:46:20'),
(30, 3, 'checkin', 'Available', 11, NULL, 'qr', '2025-10-30 14:46:28'),
(31, 3, '', '', 11, NULL, 'manual', '2025-10-30 14:46:48'),
(32, 3, 'checkout', 'Out', 11, NULL, 'qr', '2025-10-30 14:52:05'),
(33, 3, 'checkin', 'Available', 7, NULL, 'qr', '2025-10-30 14:52:14'),
(34, 3, '', 'Meeting', 7, NULL, 'manual', '2025-10-30 14:52:20'),
(35, 3, '', 'In_Class', 7, NULL, 'manual', '2025-10-30 14:55:32'),
(36, 3, '', 'in class', 7, NULL, 'manual', '2025-10-30 15:14:30'),
(37, 3, '', 'dnd', 7, NULL, 'manual', '2025-10-30 15:15:20'),
(38, 3, '', 'available', 7, NULL, 'manual', '2025-10-30 15:16:02'),
(39, 3, '', 'in class', 7, NULL, 'manual', '2025-10-30 15:19:03'),
(40, 3, '', 'dnd', 7, NULL, 'manual', '2025-10-30 15:19:28');

-- --------------------------------------------------------

--
-- Table structure for table `presence`
--

CREATE TABLE `presence` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `faculty_user_id` bigint(20) UNSIGNED NOT NULL,
  `room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` varchar(32) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `source` enum('manual','qr','schedule') NOT NULL DEFAULT 'manual',
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `presence`
--

INSERT INTO `presence` (`id`, `faculty_user_id`, `room_id`, `status`, `note`, `source`, `expires_at`, `created_at`, `updated_at`) VALUES
(2, 3, 7, 'Available', NULL, 'qr', NULL, '2025-10-28 02:29:35', '2025-10-30 15:08:48'),
(3, 3, NULL, '', '', 'manual', NULL, '2025-10-28 06:30:36', '2025-10-28 06:30:36'),
(4, 3, NULL, 'Meeting', '', 'manual', NULL, '2025-10-28 06:31:22', '2025-10-30 15:08:48'),
(5, 5, 11, 'Available', NULL, 'qr', NULL, '2025-10-28 06:50:08', '2025-10-30 15:08:48'),
(6, 3, 11, 'Available', NULL, 'qr', NULL, '2025-10-28 09:26:29', '2025-10-30 15:08:48'),
(7, 3, 7, 'Available', NULL, 'qr', NULL, '2025-10-28 09:41:14', '2025-10-30 15:08:48'),
(8, 3, 7, 'Out', NULL, 'qr', NULL, '2025-10-28 10:01:56', '2025-10-30 15:08:48'),
(9, 3, 7, 'Available', NULL, 'qr', NULL, '2025-10-30 12:21:30', '2025-10-30 15:08:48'),
(10, 3, 7, 'Out', NULL, 'qr', NULL, '2025-10-30 12:21:59', '2025-10-30 15:08:48'),
(11, 3, 7, 'Available', NULL, 'qr', NULL, '2025-10-30 12:31:48', '2025-10-30 15:08:48'),
(12, 3, 7, 'Out', NULL, 'qr', NULL, '2025-10-30 12:32:15', '2025-10-30 15:08:48'),
(13, 3, 11, 'Available', NULL, 'qr', NULL, '2025-10-30 12:32:15', '2025-10-30 15:08:48'),
(14, 3, 7, 'Available', NULL, 'qr', NULL, '2025-10-30 12:37:33', '2025-10-30 15:08:48'),
(15, 3, 7, 'Out', NULL, 'qr', NULL, '2025-10-30 12:37:43', '2025-10-30 15:08:48'),
(16, 3, 7, 'Out', NULL, 'qr', NULL, '2025-10-30 12:37:43', '2025-10-30 15:08:48'),
(17, 3, 7, 'Available', NULL, 'qr', NULL, '2025-10-30 13:12:29', '2025-10-30 15:08:48'),
(18, 3, 7, 'Out', NULL, 'qr', NULL, '2025-10-30 13:12:42', '2025-10-30 15:08:48'),
(19, 3, 11, 'Available', NULL, 'qr', NULL, '2025-10-30 13:12:42', '2025-10-30 15:08:48'),
(20, 3, 11, 'Out', NULL, 'qr', NULL, '2025-10-30 14:10:38', '2025-10-30 15:08:48'),
(21, 3, 7, 'Available', NULL, 'qr', NULL, '2025-10-30 14:18:03', '2025-10-30 15:08:48'),
(22, 3, 7, '', NULL, 'manual', NULL, '2025-10-30 14:18:26', '2025-10-30 14:18:26'),
(23, 3, 7, 'Out', NULL, 'qr', NULL, '2025-10-30 14:26:00', '2025-10-30 15:08:48'),
(24, 3, 11, 'Available', NULL, 'qr', NULL, '2025-10-30 14:26:12', '2025-10-30 15:08:48'),
(25, 3, 11, '', NULL, 'manual', NULL, '2025-10-30 14:26:22', '2025-10-30 14:26:22'),
(26, 3, 11, 'Out', NULL, 'qr', NULL, '2025-10-30 14:45:51', '2025-10-30 15:08:48'),
(27, 3, 7, 'Available', NULL, 'qr', NULL, '2025-10-30 14:46:00', '2025-10-30 15:08:48'),
(28, 3, 7, '', NULL, 'manual', NULL, '2025-10-30 14:46:06', '2025-10-30 14:46:06'),
(29, 3, 7, 'Out', NULL, 'qr', NULL, '2025-10-30 14:46:20', '2025-10-30 15:08:48'),
(30, 3, 11, 'Available', NULL, 'qr', NULL, '2025-10-30 14:46:28', '2025-10-30 15:08:48'),
(31, 3, 11, '', NULL, 'manual', NULL, '2025-10-30 14:46:48', '2025-10-30 14:46:48'),
(32, 3, 11, 'Out', NULL, 'qr', NULL, '2025-10-30 14:52:05', '2025-10-30 15:08:48'),
(33, 3, 7, 'Available', NULL, 'qr', NULL, '2025-10-30 14:52:14', '2025-10-30 15:08:48'),
(34, 3, 7, 'Meeting', NULL, 'manual', NULL, '2025-10-30 14:52:20', '2025-10-30 15:08:48'),
(35, 3, 7, 'In_Class', NULL, 'manual', NULL, '2025-10-30 14:55:32', '2025-10-30 15:08:48'),
(36, 3, 7, 'in class', NULL, 'manual', NULL, '2025-10-30 15:14:30', '2025-10-30 15:14:30'),
(37, 3, 7, 'dnd', NULL, 'manual', NULL, '2025-10-30 15:15:20', '2025-10-30 15:15:20'),
(38, 3, 7, 'available', NULL, 'manual', NULL, '2025-10-30 15:16:02', '2025-10-30 15:16:02'),
(39, 3, 7, 'in class', NULL, 'manual', NULL, '2025-10-30 15:19:03', '2025-10-30 15:19:03'),
(40, 3, 7, 'dnd', NULL, 'manual', NULL, '2025-10-30 15:19:28', '2025-10-30 15:19:28');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `building_id` bigint(20) UNSIGNED NOT NULL,
  `room_number` varchar(60) NOT NULL,
  `room_name` varchar(180) NOT NULL,
  `qr_payload` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `building_id`, `room_number`, `room_name`, `qr_payload`, `created_at`, `updated_at`) VALUES
(7, 3, '101', 'ACAD', '{\"building\":\"Academic Building\",\"room_name\":\"ACAD\",\"room_number\":\"101\",\"rid\":7}', '2025-10-17 10:38:55', '2025-10-17 10:38:55'),
(8, 3, '102', 'ACAD', '{\"building\":\"Academic Building\",\"room_name\":\"ACAD\",\"room_number\":\"102\",\"rid\":8}', '2025-10-17 10:38:57', '2025-10-17 10:38:57'),
(9, 3, '103', 'ACAD', '{\"building\":\"Academic Building\",\"room_name\":\"ACAD\",\"room_number\":\"103\",\"rid\":9}', '2025-10-17 10:38:59', '2025-10-17 10:38:59'),
(10, 4, '101', 'TECH', '{\"building\":\"Technovation Building\",\"room_name\":\"TECH\",\"room_number\":\"101\",\"rid\":10}', '2025-10-17 10:59:18', '2025-10-17 10:59:18'),
(11, 4, '107', 'TECHLa', '{\"building\":\"Technovation Building\",\"room_name\":\"TECHLa\",\"room_number\":\"107\",\"rid\":11}', '2025-10-17 10:59:20', '2025-10-17 11:43:39'),
(12, 4, '103', 'TECH', '{\"building\":\"Technovation Building\",\"room_name\":\"TECH\",\"room_number\":\"103\",\"rid\":12}', '2025-10-17 10:59:22', '2025-10-17 10:59:22'),
(13, 4, '102', 'TECH', '{\"building\":\"Technovation Building\",\"room_name\":\"TECH\",\"room_number\":\"102\",\"rid\":13}', '2025-10-17 11:52:27', '2025-10-17 11:52:27'),
(14, 5, '201', 'ELEX', '{\"building\":\"CITTE Building\",\"room_name\":\"ELEX\",\"room_number\":\"201\",\"rid\":14}', '2025-10-28 09:23:48', '2025-10-28 09:23:48'),
(15, 5, '202', 'ELEX', '{\"building\":\"CITTE Building\",\"room_name\":\"ELEX\",\"room_number\":\"202\",\"rid\":15}', '2025-10-28 09:23:48', '2025-10-28 09:23:48'),
(16, 5, '203', 'ELEX', '{\"building\":\"CITTE Building\",\"room_name\":\"ELEX\",\"room_number\":\"203\",\"rid\":16}', '2025-10-28 09:23:48', '2025-10-28 09:23:48'),
(17, 6, '101', 'COLB', '{\"building\":\"Comlab\",\"room_name\":\"COLB\",\"room_number\":\"101\",\"rid\":17}', '2025-10-28 09:58:15', '2025-10-28 09:58:15'),
(18, 6, '102', 'COLB', '{\"building\":\"Comlab\",\"room_name\":\"COLB\",\"room_number\":\"102\",\"rid\":18}', '2025-10-28 09:58:20', '2025-10-28 09:58:20');

-- --------------------------------------------------------

--
-- Table structure for table `room_logs`
--

CREATE TABLE `room_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `room_id` bigint(20) UNSIGNED NOT NULL,
  `faculty_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` enum('checkin','checkout') NOT NULL,
  `status` varchar(32) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `room_logs`
--

INSERT INTO `room_logs` (`id`, `room_id`, `faculty_user_id`, `action`, `status`, `note`, `created_at`) VALUES
(2, 7, 3, 'checkin', NULL, NULL, '2025-10-28 02:29:35'),
(3, 11, 5, 'checkin', NULL, NULL, '2025-10-28 06:50:08'),
(4, 11, 3, 'checkin', NULL, NULL, '2025-10-28 09:26:29'),
(5, 7, 3, 'checkin', NULL, NULL, '2025-10-28 09:41:14'),
(6, 7, 3, 'checkout', NULL, NULL, '2025-10-28 10:01:56'),
(7, 7, 3, 'checkin', NULL, NULL, '2025-10-30 12:21:30'),
(8, 7, 3, 'checkout', NULL, NULL, '2025-10-30 12:21:59'),
(9, 7, 3, 'checkin', NULL, NULL, '2025-10-30 12:31:48'),
(10, 7, 3, 'checkout', NULL, NULL, '2025-10-30 12:32:15'),
(11, 11, 3, 'checkin', NULL, NULL, '2025-10-30 12:32:15'),
(12, 7, 3, 'checkin', NULL, NULL, '2025-10-30 12:37:33'),
(13, 7, 3, 'checkout', NULL, NULL, '2025-10-30 12:37:43'),
(14, 7, 3, 'checkout', NULL, NULL, '2025-10-30 12:37:43'),
(15, 7, 3, 'checkin', NULL, NULL, '2025-10-30 13:12:29'),
(16, 7, 3, 'checkout', NULL, NULL, '2025-10-30 13:12:42'),
(17, 11, 3, 'checkin', NULL, NULL, '2025-10-30 13:12:42'),
(18, 11, 3, 'checkout', NULL, NULL, '2025-10-30 14:10:38'),
(19, 7, 3, 'checkin', NULL, NULL, '2025-10-30 14:18:03'),
(20, 7, 3, 'checkout', NULL, NULL, '2025-10-30 14:26:00'),
(21, 11, 3, 'checkin', NULL, NULL, '2025-10-30 14:26:12'),
(22, 11, 3, 'checkout', NULL, NULL, '2025-10-30 14:45:51'),
(23, 7, 3, 'checkin', NULL, NULL, '2025-10-30 14:46:00'),
(24, 7, 3, 'checkout', NULL, NULL, '2025-10-30 14:46:20'),
(25, 11, 3, 'checkin', NULL, NULL, '2025-10-30 14:46:28'),
(26, 11, 3, 'checkout', NULL, NULL, '2025-10-30 14:52:05'),
(27, 7, 3, 'checkin', NULL, NULL, '2025-10-30 14:52:14');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `faculty_user_id` bigint(20) UNSIGNED NOT NULL,
  `course_code` varchar(40) DEFAULT NULL,
  `room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `day_of_week` tinyint(3) UNSIGNED NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Executive','Instructor','Staff') NOT NULL DEFAULT 'Instructor',
  `department` enum('CBA','CEIT','CTHM','CITTE','DLHS') DEFAULT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `department`, `contact`, `created_at`, `updated_at`) VALUES
(1, 'Admin User', 'admin@csucc.edu.ph', '1234', 'Admin', NULL, NULL, '2025-10-20 03:26:29', '2025-10-20 03:26:29'),
(3, 'Rodel James G. Maraon', 'rodeljames.maraon@csucc.edu.ph', '$2y$10$mz9wgo0xjcVDT8LAo8c.Y.bZr4fEeeaRhUWEh3eiWxPMEE2EdCOIm', 'Instructor', 'CEIT', '09811537756', '2025-10-17 10:48:57', '2025-10-28 06:33:21'),
(4, 'Sponge BOB', 'spongebob@gmail.com', '$2y$10$8..kXNjJWB1M2hqiBIz8Q.zB264hNXqkNHAxju.T4dV6frA4aN35m', 'Executive', '', '', '2025-10-20 03:55:30', '2025-10-20 03:55:30'),
(5, 'Dwaine Roxette Balite', 'dwaine@gmail.com', '$2y$10$Wwp7A5B0a.nA6gSB194G.u3I1V7Rv8bXzNbsV2RDdM6mY/DjTEXUa', 'Instructor', 'CBA', '', '2025-10-28 05:55:50', '2025-10-28 05:55:50'),
(6, 'Nathaniel G. Palco', 'palco@gmail.com', '$2y$10$6Fth1rZEfOPohIlI7wSeY.YDWvBgTi8.86x5eUJvl8ncw23TO44Cu', 'Executive', '', '', '2025-10-28 06:44:53', '2025-10-28 06:44:53');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_current_presence`
-- (See below for the actual view)
--
CREATE TABLE `v_current_presence` (
`id` bigint(20) unsigned
,`faculty_user_id` bigint(20) unsigned
,`room_id` bigint(20) unsigned
,`status` varchar(32)
,`note` varchar(255)
,`source` enum('manual','qr','schedule')
,`expires_at` datetime
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Structure for view `v_current_presence`
--
DROP TABLE IF EXISTS `v_current_presence`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_current_presence`  AS SELECT `p1`.`id` AS `id`, `p1`.`faculty_user_id` AS `faculty_user_id`, `p1`.`room_id` AS `room_id`, `p1`.`status` AS `status`, `p1`.`note` AS `note`, `p1`.`source` AS `source`, `p1`.`expires_at` AS `expires_at`, `p1`.`created_at` AS `created_at`, `p1`.`updated_at` AS `updated_at` FROM (`presence` `p1` join (select `presence`.`faculty_user_id` AS `faculty_user_id`,max(`presence`.`created_at`) AS `max_created` from `presence` group by `presence`.`faculty_user_id`) `latest` on(`latest`.`faculty_user_id` = `p1`.`faculty_user_id` and `latest`.`max_created` = `p1`.`created_at`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buildings`
--
ALTER TABLE `buildings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_buildings_code` (`code`);

--
-- Indexes for table `instructor_logs`
--
ALTER TABLE `instructor_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_il_room` (`room_id`),
  ADD KEY `idx_il_user_time` (`faculty_user_id`,`created_at`);

--
-- Indexes for table `presence`
--
ALTER TABLE `presence`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_presence_user_created` (`faculty_user_id`,`created_at`),
  ADD KEY `idx_presence_room_created` (`room_id`,`created_at`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_room_in_building` (`building_id`,`room_number`),
  ADD UNIQUE KEY `uk_rooms_qr_payload` (`qr_payload`);

--
-- Indexes for table `room_logs`
--
ALTER TABLE `room_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rl_user` (`faculty_user_id`),
  ADD KEY `idx_rl_room_time` (`room_id`,`created_at`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sched_user_day` (`faculty_user_id`,`day_of_week`),
  ADD KEY `idx_sched_room_day` (`room_id`,`day_of_week`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_department` (`department`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buildings`
--
ALTER TABLE `buildings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `instructor_logs`
--
ALTER TABLE `instructor_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `presence`
--
ALTER TABLE `presence`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `room_logs`
--
ALTER TABLE `room_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `instructor_logs`
--
ALTER TABLE `instructor_logs`
  ADD CONSTRAINT `fk_il_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_il_user` FOREIGN KEY (`faculty_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `presence`
--
ALTER TABLE `presence`
  ADD CONSTRAINT `fk_presence_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_presence_user` FOREIGN KEY (`faculty_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `fk_rooms_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `room_logs`
--
ALTER TABLE `room_logs`
  ADD CONSTRAINT `fk_rl_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rl_user` FOREIGN KEY (`faculty_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `fk_sched_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sched_user` FOREIGN KEY (`faculty_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
