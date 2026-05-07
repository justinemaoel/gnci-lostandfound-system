-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 26, 2026 at 11:52 AM
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
  `post_type` enum('i found something','i lost something') NOT NULL,
  `upload_status` enum('approved','pending','rejected') DEFAULT 'pending',
  `item_resolved_status` enum('resolved','not resolved') DEFAULT 'not resolved',
  `contact_email` varchar(150) DEFAULT NULL,
  `contact_num` varchar(20) DEFAULT NULL,
  `date_reported` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `user_id`, `category_id`, `item_name`, `location_text`, `description`, `notes`, `item_img`, `time_last_seen`, `status`, `post_type`, `upload_status`, `item_resolved_status`, `contact_email`, `contact_num`, `date_reported`, `created_at`) VALUES
(1, 2, 4, 'Blue Umbrella', 'test', 'test', 'test', '1777196542_Image.png', '17:45:00', 'available', '', 'pending', 'not resolved', 'test@gmail.com', '12345', '2026-04-08', '2026-04-26 09:42:22');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','faculty','staff','admin') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `role`) VALUES
(1, 'System', 'Admin', 'admin@gmail.com', '$2y$10$7zR68n6w7fFvG.r9kZ9k7uGv.Wv6U9u1mB0z9z9z9z9z9z9z9z9z', 'admin'),
(2, 'Justine Maoel', 'Figura', 'maoeljustine@gmail.com', '$2y$10$kndavFWGiSufzUvndtDX0.IOoqbtg7gfoG/7wxMXdzfCL1rewPKJm', 'student');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
