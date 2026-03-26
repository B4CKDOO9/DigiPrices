-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 26, 2026 at 05:14 PM
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
  `mac` varchar(32) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `fw_version` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `displays`
--

INSERT INTO `displays` (`id_display`, `section`, `mac`, `ip`, `product_id`, `fw_version`) VALUES
(49, 'Aisle3', 'AA:BB:CC:DD:EE:FF', '192.168.10.154', 5, 'v1.0.0'),
(50, 'Aisle3', 'AA:BB:CC:DD:EE:BB', '192.168.10.238', 6, 'v1.0.0');

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id_log` bigint(20) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `display_id` int(11) DEFAULT NULL,
  `changed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `what_changed` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(5, 'jogurt', 'Full Fat milk', 'mlijeko', 2.00, 5.00, 'EUR', '123456789102', '2026-03-24 19:31:22', 1),
(6, 'Nigga', 'Niga', 'Nigga', 5.00, 3.00, 'EUR', '1234567891027', '2026-03-24 21:07:37', 1);

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
  ADD UNIQUE KEY `mac` (`mac`),
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
  MODIFY `id_display` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id_log` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id_product` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  ADD CONSTRAINT `fk_logs_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id_product`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
