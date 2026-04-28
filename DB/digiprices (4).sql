-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 28, 2026 at 09:48 AM
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
-- Database: `digiprices`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id_admin` int(11) NOT NULL,
  `username` varchar(32) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(30) NOT NULL,
  `surname` varchar(30) NOT NULL,
  `OIB` char(11) NOT NULL,
  `DOB` date NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(50) NOT NULL,
  `picture` longblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id_admin`, `username`, `password`, `name`, `surname`, `OIB`, `DOB`, `phone`, `email`, `picture`) VALUES
(1, 'admin', 'admin123', 'Admin', 'User', '', '0000-00-00', '', 'admin@digiprices.com', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `displays`
--

CREATE TABLE `displays` (
  `id_display` int(11) NOT NULL,
  `section` varchar(20) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `displays`
--

INSERT INTO `displays` (`id_display`, `section`, `ip`, `product_id`) VALUES
(68, 'cars', '10.185.98.7', 27);

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id_log` bigint(20) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `display_id` int(11) DEFAULT NULL,
  `changed_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `what_changed` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`id_log`, `admin_id`, `product_id`, `display_id`, `changed_at`, `what_changed`) VALUES
(16, 1, NULL, NULL, '2026-04-21 17:41:30', 'Deleted product MLEKO with price: 17.99'),
(17, 1, NULL, NULL, '2026-04-21 17:43:44', 'Deleted product MLEKO with price: 17.99'),
(18, 1, NULL, NULL, '2026-04-21 17:44:07', 'Added display with IP: 10.185.98.7'),
(19, 1, NULL, NULL, '2026-04-21 17:45:05', 'Deleted product Thommy Majoneza with price: 5.75'),
(20, 1, NULL, NULL, '2026-04-21 17:50:49', 'Product shown changed from (#) to (#11);'),
(21, 1, NULL, NULL, '2026-04-21 18:22:02', 'Deleted display with IP: 10.185.98.7'),
(22, 1, NULL, NULL, '2026-04-21 18:24:09', 'Product shown changed from (#) to (#NULL);Section changed fr'),
(23, 1, NULL, NULL, '2026-04-21 18:24:14', 'Product shown changed from (#) to (#10);'),
(24, 1, NULL, NULL, '2026-04-21 18:28:43', 'Product shown changed from (#10) to (#11);'),
(25, 1, NULL, NULL, '2026-04-21 18:29:21', 'Product shown changed from (#11) to (#10);'),
(26, 1, NULL, NULL, '2026-04-21 18:35:45', 'Product shown changed from (#10) to (#11);'),
(27, 1, NULL, NULL, '2026-04-21 18:37:37', 'Product shown changed from (#11) to (#NULL);'),
(28, 1, NULL, NULL, '2026-04-21 18:37:45', 'Product shown changed from (#) to (#11);'),
(29, 1, NULL, NULL, '2026-04-21 18:37:50', 'Product shown changed from (#11) to (#10);'),
(30, 1, NULL, NULL, '2026-04-21 18:43:03', 'Product shown changed from (#10) to (#11);'),
(31, 1, NULL, NULL, '2026-04-21 18:43:21', 'Product shown changed from (#11) to (#10);'),
(32, 1, NULL, NULL, '2026-04-21 18:47:28', 'Product shown changed from (#10) to (#11);'),
(33, 1, NULL, NULL, '2026-04-21 19:22:35', 'Product shown changed from (#11) to (#10);'),
(34, 1, NULL, NULL, '2026-04-21 19:23:08', 'Product shown changed from (#10) to (#11);'),
(35, 1, NULL, NULL, '2026-04-25 15:33:57', 'Product shown changed from (#11) to (#10);'),
(36, 1, NULL, NULL, '2026-04-25 15:34:34', 'Product name: Piletina -> Piletinaaa; '),
(37, 1, NULL, NULL, '2026-04-26 00:54:13', 'Deleted display with IP: 10.185.98.7'),
(38, 1, NULL, NULL, '2026-04-26 00:57:46', 'Product shown changed from (#) to (#10);Section changed from'),
(39, 1, NULL, NULL, '2026-04-26 00:58:12', 'Product shown changed from (#10) to (#NULL);'),
(40, 1, NULL, NULL, '2026-04-26 00:58:19', 'Product shown changed from (#) to (#10);'),
(41, 1, NULL, NULL, '2026-04-26 00:58:32', 'Product shown changed from (#10) to (#11);'),
(42, 1, NULL, NULL, '2026-04-26 01:00:15', 'Product shown changed from (#11) to (#10);'),
(43, 1, NULL, NULL, '2026-04-26 01:10:01', 'Product shown changed from (#10) to (#11);'),
(44, 1, NULL, NULL, '2026-04-26 01:10:11', 'Product shown changed from (#11) to (#10);'),
(45, 1, NULL, NULL, '2026-04-26 13:57:03', 'Product shown changed from (#10) to (#11);'),
(46, 1, NULL, NULL, '2026-04-26 13:58:29', 'Deleted display with IP: 10.185.98.7'),
(47, 1, NULL, NULL, '2026-04-26 13:59:28', 'Added display with IP: 192.168.21.233'),
(48, 1, NULL, NULL, '2026-04-26 13:59:32', 'Product shown changed from (#) to (#10);'),
(49, 1, NULL, NULL, '2026-04-26 13:59:38', 'Product shown changed from (#10) to (#11);'),
(50, 1, NULL, NULL, '2026-04-26 14:00:04', 'Deleted display with IP: 192.168.21.233'),
(51, 1, NULL, NULL, '2026-04-26 15:43:26', 'Added display with IP: 192.168.21.233'),
(52, 1, NULL, NULL, '2026-04-26 15:43:32', 'Product shown changed from (#) to (#10);'),
(53, 1, NULL, NULL, '2026-04-26 15:44:11', 'Product shown changed from (#10) to (#11);'),
(54, 1, NULL, NULL, '2026-04-26 15:45:45', 'Product shown changed from (#11) to (#10);'),
(55, 1, NULL, NULL, '2026-04-26 16:00:17', 'Deleted display with IP: 192.168.21.233'),
(56, 1, NULL, NULL, '2026-04-26 16:11:31', 'IP changed from  to 10.185.98.7; Product shown changed from '),
(57, 1, NULL, NULL, '2026-04-26 16:17:11', 'Price: 3.75 -> 2.5; '),
(58, 1, NULL, NULL, '2026-04-26 16:20:04', 'Product shown changed from (#10) to (#11);'),
(59, 1, NULL, NULL, '2026-04-26 16:20:27', 'Product shown changed from (#11) to (#10);'),
(60, 1, NULL, NULL, '2026-04-26 16:29:16', 'Product shown changed from (#10) to (#11);'),
(61, 1, NULL, NULL, '2026-04-26 16:32:39', 'Price: 4.00 -> 5; '),
(62, 1, NULL, NULL, '2026-04-26 16:40:48', 'Product shown changed from (#11) to (#10);'),
(63, 1, NULL, NULL, '2026-04-26 16:41:04', 'Product shown changed from (#10) to (#11);'),
(64, 1, NULL, NULL, '2026-04-26 16:42:22', 'Product shown changed from (#11) to (#10);'),
(65, 1, NULL, NULL, '2026-04-26 16:42:29', 'Product shown changed from (#10) to (#11);'),
(66, 1, NULL, NULL, '2026-04-26 16:42:37', 'Deleted product Piletinaaa with price: 5.00'),
(67, 1, NULL, NULL, '2026-04-26 16:42:47', 'Deleted product Kava with price: 2.50'),
(68, 1, NULL, NULL, '2026-04-26 19:10:05', 'Deleted product Piletinaaa with price: 5.00'),
(69, 1, NULL, NULL, '2026-04-26 19:10:18', 'Deleted display with IP: 10.185.98.7'),
(70, 1, NULL, NULL, '2026-04-26 19:14:23', 'Created product Nigger with price: 3'),
(71, 1, NULL, NULL, '2026-04-26 19:14:35', 'Deleted product Nigger with price: 3.00'),
(72, 1, NULL, NULL, '2026-04-26 19:29:41', 'Product shown changed from (#) to (#10);Section changed from'),
(73, 1, NULL, NULL, '2026-04-26 19:30:37', 'Product shown changed from (#10) to (#11);'),
(74, 1, NULL, NULL, '2026-04-26 19:30:49', 'Deleted display with IP: '),
(75, 1, NULL, NULL, '2026-04-26 19:39:50', 'Deleted display with IP: 10.185.98.7'),
(76, 1, NULL, NULL, '2026-04-26 19:41:39', 'Added display with IP: 10.185.98.7'),
(77, 1, NULL, NULL, '2026-04-26 19:41:43', 'Product shown changed from (#) to (#11);'),
(80, 1, NULL, NULL, '2026-04-27 11:19:41', 'Deleted product Piletinaaa with price: 5.00'),
(81, 1, NULL, NULL, '2026-04-27 11:19:50', 'Product shown changed from (#) to (#10);'),
(82, 1, NULL, NULL, '2026-04-27 11:22:53', 'Product shown changed from (#10) to (#NULL);'),
(83, 1, NULL, NULL, '2026-04-27 11:22:56', 'Product shown changed from (#) to (#10);'),
(84, 1, NULL, NULL, '2026-04-27 11:24:44', 'Product name: Kava -> Kavica; '),
(85, 1, NULL, NULL, '2026-04-27 15:28:51', 'Price: 2.50 -> 2; '),
(86, 1, NULL, NULL, '2026-04-27 15:29:51', 'Price: 2.00 -> 1.99; '),
(87, 1, NULL, NULL, '2026-04-27 15:30:00', 'Price: 1.99 -> 3; '),
(88, 1, NULL, NULL, '2026-04-27 15:30:11', 'Price: 3.00 -> 4; '),
(89, 1, NULL, NULL, '2026-04-27 15:30:36', 'Price: 4.00 -> 0.99; '),
(90, 1, NULL, NULL, '2026-04-27 15:30:46', 'Price: 0.99 -> 1; '),
(91, 1, NULL, NULL, '2026-04-27 15:32:23', 'Price: 1.00 -> -2; '),
(92, 1, NULL, NULL, '2026-04-27 15:32:33', 'Price: -2.00 -> -3; '),
(93, 1, NULL, NULL, '2026-04-27 15:32:43', 'Price: -3.00 -> -3.1; '),
(94, 1, NULL, NULL, '2026-04-27 15:32:53', 'Price: -3.10 -> 2; '),
(95, 1, NULL, NULL, '2026-04-27 15:34:16', 'Price: 2.00 -> -2; '),
(96, 1, NULL, NULL, '2026-04-27 15:35:40', 'Price: -2.00 -> 3; '),
(97, 1, NULL, NULL, '2026-04-27 15:48:12', 'Created product  with price: 0'),
(98, 1, NULL, NULL, '2026-04-27 15:48:20', 'Deleted product  with price: 0.00'),
(99, 1, NULL, NULL, '2026-04-27 16:17:58', 'Created product Milk with price: 0'),
(100, 1, NULL, NULL, '2026-04-27 16:19:00', 'Deleted product Milk with price: 0.00'),
(101, 1, NULL, NULL, '2026-04-27 16:19:11', 'Created product Milk with price: 0'),
(102, 1, NULL, NULL, '2026-04-27 16:25:42', 'Deleted product Milk with price: 0.00'),
(103, 1, NULL, NULL, '2026-04-27 16:26:10', 'Created product Nigger with price: 2'),
(104, 1, NULL, NULL, '2026-04-27 16:26:47', 'Deleted product Nigger with price: 2.00'),
(105, 1, NULL, NULL, '2026-04-27 16:28:10', 'Created product Nigger with price: 2'),
(106, 1, NULL, NULL, '2026-04-27 16:29:09', 'Deleted product Nigger with price: 2.00'),
(107, 1, NULL, NULL, '2026-04-27 16:29:50', 'Created product N with price: 2'),
(108, 1, NULL, NULL, '2026-04-27 16:29:54', 'Deleted product N with price: 2.00'),
(109, 1, NULL, NULL, '2026-04-27 16:59:03', 'Product name: Kavica -> Drito; '),
(110, 1, NULL, NULL, '2026-04-27 17:00:17', 'Product name: Drito -> Durum; '),
(111, 1, NULL, NULL, '2026-04-27 17:04:50', 'Product name: Durum -> drink; '),
(112, 1, NULL, NULL, '2026-04-27 17:05:44', 'Price: 3.00 -> 5; '),
(132, 1, NULL, NULL, '2026-04-27 22:14:15', 'Deleted display with IP: 10.182.133.251'),
(133, 1, NULL, NULL, '2026-04-27 22:14:18', 'Deleted product Numer with price: 3.00'),
(134, 1, NULL, NULL, '2026-04-27 22:22:18', 'Created product BMW with price: 25000'),
(135, 1, NULL, NULL, '2026-04-27 22:22:20', 'Created product BMW with price: 25000'),
(136, 1, NULL, NULL, '2026-04-27 22:22:25', 'Created product BMW with price: 25000'),
(137, 1, NULL, NULL, '2026-04-27 22:22:26', 'Created product BMW with price: 25000'),
(138, 1, NULL, NULL, '2026-04-27 22:22:28', 'Created product BMW with price: 25000'),
(139, 1, NULL, NULL, '2026-04-27 22:22:39', 'Created product BMW with price: 25000'),
(140, 1, NULL, NULL, '2026-04-27 22:24:01', 'Deleted product BMW with price: 25000.00'),
(141, 1, NULL, 68, '2026-04-27 22:25:49', 'Product shown changed from (#) to (#20);Section changed from'),
(142, 1, NULL, NULL, '2026-04-27 22:28:18', 'Price: 25000.00 -> 24000; '),
(143, 1, NULL, NULL, '2026-04-28 00:01:35', 'Created product Nigger with price: 2'),
(144, 1, NULL, 68, '2026-04-28 00:01:45', 'Product shown changed from (#) to (#26);'),
(145, 1, NULL, NULL, '2026-04-28 00:02:03', 'Deleted product Nigger with price: 2.00'),
(146, 1, 27, NULL, '2026-04-28 00:12:11', 'Created product Nigger with price: 3'),
(147, 1, 27, 68, '2026-04-28 00:12:15', 'Product shown changed from (#) to (#27);');

-- --------------------------------------------------------

--
-- Table structure for table `price_history`
--

CREATE TABLE `price_history` (
  `id_history` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `changed_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id_product` int(11) NOT NULL,
  `name` varchar(40) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `price_per_kg` decimal(10,2) NOT NULL,
  `currency_code` char(3) NOT NULL DEFAULT 'EUR',
  `barcode` varchar(32) DEFAULT NULL,
  `last_price_change` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `version` int(11) NOT NULL DEFAULT 1,
  `discount_per` decimal(3,0) DEFAULT NULL,
  `discount_end` datetime DEFAULT NULL,
  `unit` enum('KG','L','KOM') NOT NULL DEFAULT 'KOM',
  `quantity` decimal(10,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id_product`, `name`, `price`, `price_per_kg`, `currency_code`, `barcode`, `last_price_change`, `version`, `discount_per`, `discount_end`, `unit`, `quantity`) VALUES
(27, 'Nigger', 3.00, 3.00, 'EUR', '1234567890123', '2026-04-28 09:47:32', 1, NULL, NULL, 'KOM', 1.000);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `uq_admin_oib` (`OIB`),
  ADD UNIQUE KEY `uq_admin_email` (`email`);

--
-- Indexes for table `displays`
--
ALTER TABLE `displays`
  ADD PRIMARY KEY (`id_display`),
  ADD KEY `idx_displays_section` (`section`),
  ADD KEY `idx_displays_product_id` (`product_id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `idx_logs_admin` (`admin_id`),
  ADD KEY `idx_logs_product` (`product_id`),
  ADD KEY `idx_logs_display` (`display_id`),
  ADD KEY `idx_logs_changed_at` (`changed_at`);

--
-- Indexes for table `price_history`
--
ALTER TABLE `price_history`
  ADD PRIMARY KEY (`id_history`),
  ADD KEY `fk_product` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id_product`),
  ADD KEY `idx_products_barcode` (`barcode`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `displays`
--
ALTER TABLE `displays`
  MODIFY `id_display` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id_log` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=148;

--
-- AUTO_INCREMENT for table `price_history`
--
ALTER TABLE `price_history`
  MODIFY `id_history` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id_product` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `displays`
--
ALTER TABLE `displays`
  ADD CONSTRAINT `fk_displays_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id_product`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `fk_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id_admin`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_logs_display` FOREIGN KEY (`display_id`) REFERENCES `displays` (`id_display`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_logs_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id_product`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `price_history`
--
ALTER TABLE `price_history`
  ADD CONSTRAINT `fk_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id_product`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
