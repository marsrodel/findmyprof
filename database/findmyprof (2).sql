-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 20, 2025 at 06:09 AM
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
(4, 'TECH', 'Technovation Building', 'Techno', '2025-10-17 10:59:18', '2025-10-17 10:59:18');

-- --------------------------------------------------------

--
-- Table structure for table `instructor_logs`
--

CREATE TABLE `instructor_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `faculty_user_id` bigint(20) UNSIGNED NOT NULL,
  `action` enum('status_update','checkin','checkout','schedule_auto') NOT NULL,
  `status` enum('available','in_class','meeting','break','out','dnd') DEFAULT NULL,
  `room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `source` enum('manual','qr','schedule') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `presence`
--

CREATE TABLE `presence` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `faculty_user_id` bigint(20) UNSIGNED NOT NULL,
  `room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('available','in_class','meeting','break','out','dnd') NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `source` enum('manual','qr','schedule') NOT NULL DEFAULT 'manual',
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(13, 4, '102', 'TECH', '{\"building\":\"Technovation Building\",\"room_name\":\"TECH\",\"room_number\":\"102\",\"rid\":13}', '2025-10-17 11:52:27', '2025-10-17 11:52:27');

-- --------------------------------------------------------

--
-- Table structure for table `room_logs`
--

CREATE TABLE `room_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `room_id` bigint(20) UNSIGNED NOT NULL,
  `faculty_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` enum('checkin','checkout') NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(3, 'Rodel James G. Maraon', 'rodeljames.maraon@csucc.edu.ph', '$2y$10$mz9wgo0xjcVDT8LAo8c.Y.bZr4fEeeaRhUWEh3eiWxPMEE2EdCOIm', 'Instructor', 'CEIT', '', '2025-10-17 10:48:57', '2025-10-17 10:48:57'),
(4, 'Sponge BOB', 'spongebob@gmail.com', '$2y$10$8..kXNjJWB1M2hqiBIz8Q.zB264hNXqkNHAxju.T4dV6frA4aN35m', 'Executive', '', '', '2025-10-20 03:55:30', '2025-10-20 03:55:30');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_current_presence`
-- (See below for the actual view)
--
CREATE TABLE `v_current_presence` (
`id` bigint(20) unsigned
,`faculty_user_id` bigint(20) unsigned
,`room_id` bigint(20) unsigned
,`status` enum('available','in_class','meeting','break','out','dnd')
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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `instructor_logs`
--
ALTER TABLE `instructor_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `presence`
--
ALTER TABLE `presence`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `room_logs`
--
ALTER TABLE `room_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
