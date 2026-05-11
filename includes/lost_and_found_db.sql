-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2026 at 10:01 AM
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
-- Database: `lost_and_found_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_name`) VALUES
(1, 'Electronics & Gadgets'),
(2, 'Academics Supplies'),
(3, 'Personal Documents & Essentials'),
(4, 'Fashion & Accessories'),
(5, 'Clothing & Bags'),
(6, 'Health & Lifestyle'),
(7, 'Miscellaneous');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `location_text` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `notes` text DEFAULT NULL,
  `item_img` varchar(255) DEFAULT NULL,
  `time_last_seen` time DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'available',
  `post_type` enum('Lost','Found') NOT NULL,
  `upload_status` enum('approved','pending','rejected') DEFAULT 'pending',
  `item_resolved_status` enum('resolved','not resolved') DEFAULT 'not resolved',
  `contact_email` varchar(150) DEFAULT NULL,
  `contact_num` varchar(20) DEFAULT NULL,
  `date_reported` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `submitted_to_office` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `user_id`, `category_id`, `item_name`, `location_text`, `description`, `notes`, `item_img`, `time_last_seen`, `status`, `post_type`, `upload_status`, `item_resolved_status`, `contact_email`, `contact_num`, `date_reported`, `created_at`, `submitted_to_office`) VALUES
(40, 2, 3, 'Brown Wallet', 'College Library', 'test', 'test', '1778480976_wallet.png', '15:31:00', 'approved', 'Found', 'approved', 'not resolved', 'test@gmail.com', '12345', '2026-05-12', '2026-05-11 06:29:36', 1),
(41, 2, 4, 'Blue Umbrella', 'PH102', 'test', 'test', '1778481017_umbrella.png', '14:33:00', 'approved', 'Found', 'approved', 'not resolved', 'test@gmail.com', '12345', '2026-05-10', '2026-05-11 06:30:17', 0),
(42, 2, 3, 'Student Id', 'School Canteen', 'test', 'test', '1778481049_student id.png', '14:32:00', 'approved', 'Lost', 'approved', 'not resolved', 'test@gmail.com', '12345', '2026-05-14', '2026-05-11 06:30:49', 0),
(43, 2, 1, 'Earphones', 'High School Library', 'test', 'test', '1778481145_earphones.png', '14:34:00', 'approved', 'Lost', 'approved', 'not resolved', 'test@gmail.com', '12345', '2026-05-13', '2026-05-11 06:32:25', 0),
(44, 2, 6, 'Aqua Flask Red Tumbler', 'GH108', 'TEST', 'TEST', '1778481234_tumbler.png', '15:35:00', 'approved', 'Found', 'approved', 'not resolved', 'test@gmail.com', '12345', '2026-05-11', '2026-05-11 06:33:54', 1),
(45, 2, 1, 'Black Samsung', 'School Canteen', 'test', 'test', '1778481291_samsung.png', '08:34:00', 'approved', 'Found', 'approved', 'not resolved', 'test@gmail.com', '12345', '2026-05-07', '2026-05-11 06:34:51', 1),
(46, 2, 6, 'Stethoscope', 'LH309', 'test', 'test', '1778481374_stethoscope.png', '14:38:00', 'approved', 'Lost', 'approved', 'not resolved', 'test@gmail.com', '12345', '2026-05-01', '2026-05-11 06:36:14', 0),
(47, 2, 1, 'test', 'test', 'test', 'test', '1778484424_kit 1.png', '15:28:00', 'approved', 'Found', 'approved', 'not resolved', 'test@gmail.com', '12345', '2026-05-08', '2026-05-11 07:27:04', 0),
(48, 2, 1, 'test', 'test', 'test', 'test', '1778485579_ChatGPT Image Apr 18, 2026, 03_08_20 PM.png', '15:48:00', 'approved', 'Lost', 'approved', 'not resolved', 'test@gmail.com', '12345', '2026-05-13', '2026-05-11 07:46:19', 0),
(49, 2, 2, 'test', 'tes', 'test', 'test', '1778486479_871a45db-a49c-42f9-ac24-a0e7e5eb8bc9.jpg', '18:00:00', 'available', 'Found', 'pending', 'not resolved', 'test@gmail.com', '12345', '2026-04-29', '2026-05-11 08:01:19', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('student','faculty','staff','admin') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `role`) VALUES
(2, 'Justine Maoel', 'Figura', 'maoeljustine@gmail.com', '$2y$10$kndavFWGiSufzUvndtDX0.IOoqbtg7gfoG/7wxMXdzfCL1rewPKJm', 'student'),
(7, 'GNC', 'Admin', 'admin.lostandfound@gmail.com', '$2y$10$ySRjo/DmQeL7SCW3fP7upuxcbXNpx.UXB03FXNvbUMNA19E23De.y', 'admin'),
(8, 'Justine', 'Figura', 'justinefigura@gmail.com', '$2y$10$IeRl.dhgkomtGZ2f6K.EKeCuWnRN3fpMkvmmuEXXe0xJ2fpSwkNta', 'student'),
(9, 'maoel', 'figura', 'maoelfigura@gmail.com', '$2y$10$Mqd7lHy2xN2Mpzi8/bXvU.SsDAgWlLIBPQWAJOam3r/DQrskb2BxG', 'student'),
(10, 'Justine', 'Figura', 'justine@gmail.com', '$2y$10$CssEfebRLPpiD7WH8AVxou5NKZ12t/lJ/LZBDRuZ1ZwR5nVdqY/9m', 'student');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
