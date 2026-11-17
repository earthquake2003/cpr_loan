-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 17, 2026 at 09:45 AM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cpr_loan_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cpr_loans`
--

CREATE TABLE `cpr_loans` (
  `id` int(11) NOT NULL,
  `ref_code` varchar(8) NOT NULL,
  `loan_date` date NOT NULL,
  `borrower_name` varchar(150) NOT NULL,
  `borrower_phone` varchar(30) NOT NULL,
  `department` varchar(150) DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `total_days` int(11) DEFAULT NULL,
  `cpr_qty` int(11) NOT NULL DEFAULT 1,
  `due_date` date DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `lender_name` varchar(150) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `reject_reason` varchar(255) DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `returner_name` varchar(150) DEFAULT NULL,
  `receiver_name` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cpr_loans`
--

INSERT INTO `cpr_loans` (`id`, `ref_code`, `loan_date`, `borrower_name`, `borrower_phone`, `department`, `purpose`, `date_from`, `date_to`, `total_days`, `cpr_qty`, `due_date`, `status`, `lender_name`, `approved_at`, `reject_reason`, `return_date`, `returner_name`, `receiver_name`, `created_at`) VALUES
(1, 'ABEBSN', '2026-11-13', 'นายชนพัฒน์ บุญเอือ', '0822036365', 'สำนักงานวิชาการ', 'ฝึกการ cpr เบื้องต้น', '2026-11-13', '2026-11-19', 7, 2, '2026-11-19', 2, 'admin', '2026-11-13 09:56:42', NULL, '2026-11-17', 'นายชนพัฒน์ บุญเอือ', 'admin', '2026-11-13 02:50:50'),
(2, 'ENN4UB', '2026-11-13', 'นายปรีชา ใจอารัก', '0946326598', 'GP', 'ฝึกการ cpr เบื้องต้น', '2026-11-02', '2026-11-13', 12, 1, '2026-11-13', 2, 'admin', '2026-11-13 09:58:51', NULL, '2026-11-12', 'นายปรีชา ใจอารัก', 'admin', '2026-11-13 02:58:51'),
(3, 'ZJDHBP', '2026-11-13', 'พลตรีชัยนรงค์ คำรงภักดี', '0846593562', 'ทหารบก', 'ฝึกแพทย์ใหม่', '2026-11-13', '2026-11-13', 1, 1, '2026-11-13', 2, 'admin', '2026-11-17 08:18:47', NULL, '2026-11-17', 'พลตรีชัยนรงค์ คำรงภักดี', 'admin', '2026-11-13 03:11:11'),
(4, 'UNJZ3N', '2026-11-17', 'นายปรีชา ใจอารัก', '0846593562', 'ทหารบก', 'ฝึกการ cpr เบื้องต้น', '2026-11-17', '2026-11-19', 3, 4, '2026-11-19', 1, 'admin', '2026-11-17 08:42:50', NULL, NULL, NULL, NULL, '2026-11-17 01:42:29'),
(5, 'KBL7DG', '2026-11-17', 'พลตรีชัยนรงค์ คำรงภักดี', '0846593562', 'สำนักงานวิชาการ', 'ฝึกการ cpr เบื้องต้น', '2026-11-17', '2026-11-17', 1, 2, '2026-11-17', 1, 'admin', '2026-11-17 15:00:00', NULL, NULL, NULL, NULL, '2026-11-17 08:00:00'),
(6, 'DKNSFG', '2026-11-17', 'นายปรีชา ใจอารัก', '0822036365', 'GP', 'ฝึกการ cpr เบื้องต้น', '2026-11-17', '2026-11-17', 1, 2, '2026-11-17', 1, 'admin', '2026-11-17 15:37:36', NULL, NULL, NULL, NULL, '2026-11-17 08:37:36');

-- --------------------------------------------------------

--
-- Table structure for table `cpr_loan_items`
--

CREATE TABLE `cpr_loan_items` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cpr_loan_items`
--

INSERT INTO `cpr_loan_items` (`id`, `loan_id`, `item_name`, `qty`) VALUES
(1, 6, 'หนังสือคู่มือการ cpr', 1);

-- --------------------------------------------------------

--
-- Table structure for table `cpr_loan_manikins`
--

CREATE TABLE `cpr_loan_manikins` (
  `loan_id` int(11) NOT NULL,
  `manikin_id` int(11) NOT NULL,
  `manikin_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cpr_loan_manikins`
--

INSERT INTO `cpr_loan_manikins` (`loan_id`, `manikin_id`, `manikin_name`) VALUES
(4, 1, 'Tommy 1'),
(4, 2, 'Tommy 2'),
(4, 3, 'Tommy 3'),
(4, 4, 'Tommy 4'),
(5, 7, 'Jacob'),
(5, 11, 'Kimberly'),
(6, 14, 'Sarah'),
(6, 15, 'Morgan');

-- --------------------------------------------------------

--
-- Table structure for table `cpr_manikins`
--

CREATE TABLE `cpr_manikins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cpr_manikins`
--

INSERT INTO `cpr_manikins` (`id`, `name`, `is_active`, `created_at`) VALUES
(1, 'Tommy 1', 1, '2026-11-17 01:17:45'),
(2, 'Tommy 2', 1, '2026-11-17 01:17:45'),
(3, 'Tommy 3', 1, '2026-11-17 01:17:45'),
(4, 'Tommy 4', 1, '2026-11-17 01:17:45'),
(5, 'Taylor', 1, '2026-11-17 01:17:45'),
(6, 'Davin', 1, '2026-11-17 01:17:45'),
(7, 'Jacob', 1, '2026-11-17 01:17:45'),
(8, 'Edward', 1, '2026-11-17 01:17:45'),
(9, 'Madison', 1, '2026-11-17 01:17:45'),
(10, 'Angelina', 1, '2026-11-17 01:17:45'),
(11, 'Kimberly', 1, '2026-11-17 01:17:45'),
(12, 'Jasmine', 1, '2026-11-17 01:17:45'),
(13, 'Alexandra', 1, '2026-11-17 01:17:45'),
(14, 'Sarah', 1, '2026-11-17 01:17:45'),
(15, 'Morgan', 1, '2026-11-17 01:17:45');

-- --------------------------------------------------------

--
-- Table structure for table `cpr_return_items`
--

CREATE TABLE `cpr_return_items` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 0,
  `item_cond` tinyint(4) NOT NULL DEFAULT 1,
  `detail` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cpr_return_items`
--

INSERT INTO `cpr_return_items` (`id`, `loan_id`, `item_name`, `qty`, `item_cond`, `detail`) VALUES
(2, 2, 'หุ่น CPR', 1, 1, ''),
(4, 3, 'หุ่น CPR (ไม่ระบุชื่อ)', 1, 1, ''),
(6, 1, 'หุ่น CPR (ไม่ระบุชื่อ)', 2, 1, '');

-- --------------------------------------------------------

--
-- Table structure for table `cpr_settings`
--

CREATE TABLE `cpr_settings` (
  `skey` varchar(50) NOT NULL,
  `svalue` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cpr_settings`
--

INSERT INTO `cpr_settings` (`skey`, `svalue`) VALUES
('total_cpr', '5');

-- --------------------------------------------------------

--
-- Table structure for table `cpr_users`
--

CREATE TABLE `cpr_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cpr_users`
--

INSERT INTO `cpr_users` (`id`, `username`, `password_hash`, `full_name`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$12$zy9CHPxk/w9e5eL93x5ct.Lx7UGpW87vthBQCYvsRNuFg7msr6UP.', 'admin', 'admin', '2026-11-13 02:43:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cpr_loans`
--
ALTER TABLE `cpr_loans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ref_code` (`ref_code`),
  ADD KEY `status` (`status`),
  ADD KEY `date_from` (`date_from`,`date_to`);

--
-- Indexes for table `cpr_loan_items`
--
ALTER TABLE `cpr_loan_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indexes for table `cpr_loan_manikins`
--
ALTER TABLE `cpr_loan_manikins`
  ADD PRIMARY KEY (`loan_id`,`manikin_id`),
  ADD KEY `manikin_id` (`manikin_id`);

--
-- Indexes for table `cpr_manikins`
--
ALTER TABLE `cpr_manikins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `cpr_return_items`
--
ALTER TABLE `cpr_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indexes for table `cpr_settings`
--
ALTER TABLE `cpr_settings`
  ADD PRIMARY KEY (`skey`);

--
-- Indexes for table `cpr_users`
--
ALTER TABLE `cpr_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cpr_loans`
--
ALTER TABLE `cpr_loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cpr_loan_items`
--
ALTER TABLE `cpr_loan_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cpr_manikins`
--
ALTER TABLE `cpr_manikins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `cpr_return_items`
--
ALTER TABLE `cpr_return_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cpr_users`
--
ALTER TABLE `cpr_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cpr_loan_items`
--
ALTER TABLE `cpr_loan_items`
  ADD CONSTRAINT `cpr_loan_items_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `cpr_loans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cpr_loan_manikins`
--
ALTER TABLE `cpr_loan_manikins`
  ADD CONSTRAINT `cpr_loan_manikins_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `cpr_loans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cpr_loan_manikins_ibfk_2` FOREIGN KEY (`manikin_id`) REFERENCES `cpr_manikins` (`id`);

--
-- Constraints for table `cpr_return_items`
--
ALTER TABLE `cpr_return_items`
  ADD CONSTRAINT `cpr_return_items_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `cpr_loans` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
