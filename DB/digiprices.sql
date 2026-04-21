-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 21, 2026 at 07:30 PM
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
(60, 'Aisle3', '10.185.98.7', 11);

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
(20, 1, 11, NULL, '2026-04-21 17:50:49', 'Product shown changed from (#) to (#11);'),
(21, 1, NULL, NULL, '2026-04-21 18:22:02', 'Deleted display with IP: 10.185.98.7'),
(22, 1, NULL, 60, '2026-04-21 18:24:09', 'Product shown changed from (#) to (#NULL);Section changed fr'),
(23, 1, 10, 60, '2026-04-21 18:24:14', 'Product shown changed from (#) to (#10);'),
(24, 1, 11, 60, '2026-04-21 18:28:43', 'Product shown changed from (#10) to (#11);'),
(25, 1, 10, 60, '2026-04-21 18:29:21', 'Product shown changed from (#11) to (#10);'),
(26, 1, 11, 60, '2026-04-21 18:35:45', 'Product shown changed from (#10) to (#11);'),
(27, 1, NULL, 60, '2026-04-21 18:37:37', 'Product shown changed from (#11) to (#NULL);'),
(28, 1, 11, 60, '2026-04-21 18:37:45', 'Product shown changed from (#) to (#11);'),
(29, 1, 10, 60, '2026-04-21 18:37:50', 'Product shown changed from (#11) to (#10);'),
(30, 1, 11, 60, '2026-04-21 18:43:03', 'Product shown changed from (#10) to (#11);'),
(31, 1, 10, 60, '2026-04-21 18:43:21', 'Product shown changed from (#11) to (#10);'),
(32, 1, 11, 60, '2026-04-21 18:47:28', 'Product shown changed from (#10) to (#11);'),
(33, 1, 10, 60, '2026-04-21 19:22:35', 'Product shown changed from (#11) to (#10);'),
(34, 1, 11, 60, '2026-04-21 19:23:08', 'Product shown changed from (#10) to (#11);');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id_product` int(11) NOT NULL,
  `displaying_name` varchar(30) NOT NULL,
  `name` varchar(40) DEFAULT NULL,
  `descr` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `price_per_kg` decimal(10,2) NOT NULL,
  `currency_code` char(3) NOT NULL DEFAULT 'EUR',
  `barcode` varchar(32) DEFAULT NULL,
  `last_price_change` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `version` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id_product`, `displaying_name`, `name`, `descr`, `price`, `price_per_kg`, `currency_code`, `barcode`, `last_price_change`, `version`) VALUES
(10, 'Kava', 'Kava', 'Nema', 3.75, 8.00, 'EUR', '1147211147211', '2026-04-11 19:53:40', 1),
(11, 'Piletina', 'Piletina', 'Nema', 4.00, 20.00, 'EUR', '0987654321999', '2026-04-11 19:55:38', 1);

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
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id_product`),
  ADD KEY `idx_products_displaying_name` (`displaying_name`),
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
  MODIFY `id_display` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id_log` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id_product` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
