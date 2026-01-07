-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 07, 2026 at 04:39 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ld_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `billing_plans`
--

CREATE TABLE `billing_plans` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing_plans`
--

INSERT INTO `billing_plans` (`id`, `name`, `price`, `duration`, `status`, `created_at`, `updated_at`) VALUES
(10, 'ซักดีๆ', 1000.00, 0, 'active', '2025-12-17 12:39:53', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` char(36) NOT NULL,
  `customer_id` char(36) DEFAULT NULL,
  `store_id` char(36) DEFAULT NULL,
  `order_number` varchar(100) NOT NULL,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `pickup_time` datetime DEFAULT NULL,
  `delivery_time` datetime DEFAULT NULL,
  `status` enum('created','picked_up','in_process','ready','out_for_delivery','completed','cancelled') DEFAULT 'created',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `machine_id` char(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `store_id`, `order_number`, `total_amount`, `payment_status`, `pickup_time`, `delivery_time`, `status`, `notes`, `created_at`, `updated_at`, `machine_id`) VALUES
('174f3920-a70f-4323-bd16-c3061a68dcc0', '770cea2b-ac93-4c64-af20-b24313a57554', 'a66db143-5400-4ab4-ac68-07b065d5a387', 'LD-260105-7242', 0.00, 'failed', NULL, NULL, 'completed', '', '2026-01-05 13:18:34', '2026-01-05 18:21:31', NULL),
('275ee78d-5186-47c4-b9f9-965ee13a36a8', '770cea2b-ac93-4c64-af20-b24313a57554', 'a66db143-5400-4ab4-ac68-07b065d5a387', 'LD-260105-1064', 0.00, 'paid', NULL, NULL, 'completed', '', '2026-01-05 18:22:37', '2026-01-07 14:57:01', NULL),
('31f6d560-4edb-427c-98b0-02ad73a02b2e', '770cea2b-ac93-4c64-af20-b24313a57554', 'a66db143-5400-4ab4-ac68-07b065d5a387', 'LD-260105-2011', 0.00, 'paid', NULL, NULL, 'completed', 'ผ้าขาวแต่อยากซัก', '2026-01-05 17:58:14', '2026-01-07 15:03:17', NULL),
('46179f5e-9427-4b4e-978c-e54a59f75425', '770cea2b-ac93-4c64-af20-b24313a57554', 'a66db143-5400-4ab4-ac68-07b065d5a387', 'LD-260103-4327', 1000.00, 'paid', NULL, NULL, 'completed', 'ผ้าขาวล้ำ', '2026-01-03 09:56:45', '2026-01-05 13:14:13', NULL),
('4e5c56b8-c75d-47b7-8525-b150d225301b', '770cea2b-ac93-4c64-af20-b24313a57554', 'a66db143-5400-4ab4-ac68-07b065d5a387', 'LD-260105-9647', 0.00, 'paid', NULL, NULL, 'completed', '', '2026-01-05 13:18:06', '2026-01-07 15:04:24', NULL),
('70d28895-0225-4517-8015-4fd8de276c50', '770cea2b-ac93-4c64-af20-b24313a57554', 'a66db143-5400-4ab4-ac68-07b065d5a387', 'LD-260105-6307', 0.00, 'paid', NULL, NULL, 'completed', '3213214124132', '2026-01-05 13:58:45', '2026-01-07 15:12:25', NULL),
('7274dd8c-dcca-4aff-a723-1419b2b633eb', '770cea2b-ac93-4c64-af20-b24313a57554', 'a66db143-5400-4ab4-ac68-07b065d5a387', 'LD-260105-3022', 0.00, 'paid', NULL, NULL, 'completed', '131', '2026-01-05 13:16:52', '2026-01-07 15:04:21', NULL),
('7a34fd3b-27b8-4a32-a493-429bd412f1e7', '770cea2b-ac93-4c64-af20-b24313a57554', 'a66db143-5400-4ab4-ac68-07b065d5a387', 'LD-260105-5221', 0.00, 'paid', NULL, NULL, 'completed', '', '2026-01-05 13:19:09', '2026-01-07 15:04:13', NULL),
('b0ef427c-f2f4-4bbc-bef4-c732fd3ab271', '770cea2b-ac93-4c64-af20-b24313a57554', 'a66db143-5400-4ab4-ac68-07b065d5a387', 'LD-260105-7817', 0.00, 'pending', NULL, NULL, 'picked_up', '', '2026-01-05 13:20:05', '2026-01-07 15:02:23', NULL),
('b5579e72-2b89-4409-b673-f30583fd4743', '770cea2b-ac93-4c64-af20-b24313a57554', 'a66db143-5400-4ab4-ac68-07b065d5a387', 'LD-260105-7790', 0.00, 'pending', NULL, NULL, 'picked_up', '', '2026-01-05 13:19:52', '2026-01-07 15:03:05', NULL),
('bc84bfc5-a3cb-484f-a211-95f41e8e446b', '770cea2b-ac93-4c64-af20-b24313a57554', 'a66db143-5400-4ab4-ac68-07b065d5a387', 'LD-260105-3305', 0.00, 'pending', NULL, NULL, 'picked_up', '', '2026-01-05 13:19:55', '2026-01-07 15:02:52', NULL),
('facaabbf-2d78-4d7d-afd3-87431d2d1b76', '770cea2b-ac93-4c64-af20-b24313a57554', 'a66db143-5400-4ab4-ac68-07b065d5a387', 'LD-260105-7718', 0.00, 'paid', NULL, NULL, 'completed', '', '2026-01-05 13:18:50', '2026-01-07 15:04:09', NULL),
('fd78e649-5f78-4ebc-9e15-c0b05edd920f', '770cea2b-ac93-4c64-af20-b24313a57554', 'a66db143-5400-4ab4-ac68-07b065d5a387', 'LD-260105-4088', 0.00, 'pending', NULL, NULL, 'completed', '', '2026-01-05 13:19:23', '2026-01-05 13:57:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_status_logs`
--

CREATE TABLE `order_status_logs` (
  `id` char(36) NOT NULL,
  `order_id` char(36) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `changed_by` char(36) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `tag_id` char(36) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_status_logs`
--

INSERT INTO `order_status_logs` (`id`, `order_id`, `status`, `changed_by`, `note`, `tag_id`, `created_at`) VALUES
('1f29aeb2-ebd9-11f0-af0b-02501e7028bd', '275ee78d-5186-47c4-b9f9-965ee13a36a8', 'in_process', 'cfc47b5b-540c-40b7-a362-80ea230256d6', NULL, NULL, '2026-01-07 14:57:01'),
('1f42951b-ebd9-11f0-af0b-02501e7028bd', '275ee78d-5186-47c4-b9f9-965ee13a36a8', 'ready', 'cfc47b5b-540c-40b7-a362-80ea230256d6', NULL, NULL, '2026-01-07 14:57:01'),
('1f6c50d6-ebd9-11f0-af0b-02501e7028bd', '275ee78d-5186-47c4-b9f9-965ee13a36a8', 'out_for_delivery', 'cfc47b5b-540c-40b7-a362-80ea230256d6', NULL, NULL, '2026-01-07 14:57:01'),
('1f7cf4ed-ebd9-11f0-af0b-02501e7028bd', '275ee78d-5186-47c4-b9f9-965ee13a36a8', 'completed', 'cfc47b5b-540c-40b7-a362-80ea230256d6', NULL, NULL, '2026-01-07 14:57:01'),
('256e43d8-ebdb-11f0-af0b-02501e7028bd', '70d28895-0225-4517-8015-4fd8de276c50', 'in_process', '85c07057-5d99-42c5-9673-05bff964bb65', NULL, NULL, '2026-01-07 15:11:30'),
('25a6b7c0-ebdb-11f0-af0b-02501e7028bd', '70d28895-0225-4517-8015-4fd8de276c50', 'ready', '85c07057-5d99-42c5-9673-05bff964bb65', NULL, NULL, '2026-01-07 15:11:31'),
('45adc81a-ebdb-11f0-af0b-02501e7028bd', '70d28895-0225-4517-8015-4fd8de276c50', 'out_for_delivery', '85c07057-5d99-42c5-9673-05bff964bb65', NULL, NULL, '2026-01-07 15:12:24'),
('45ecc7fa-ebdb-11f0-af0b-02501e7028bd', '70d28895-0225-4517-8015-4fd8de276c50', 'completed', '85c07057-5d99-42c5-9673-05bff964bb65', NULL, NULL, '2026-01-07 15:12:25'),
('fd457c1c-ebd9-11f0-af0b-02501e7028bd', '31f6d560-4edb-427c-98b0-02ad73a02b2e', 'in_process', '85c07057-5d99-42c5-9673-05bff964bb65', NULL, NULL, '2026-01-07 15:03:13'),
('fd59f930-ebd9-11f0-af0b-02501e7028bd', '31f6d560-4edb-427c-98b0-02ad73a02b2e', 'ready', '85c07057-5d99-42c5-9673-05bff964bb65', NULL, NULL, '2026-01-07 15:03:13'),
('ff80a40a-ebd9-11f0-af0b-02501e7028bd', '31f6d560-4edb-427c-98b0-02ad73a02b2e', 'out_for_delivery', '85c07057-5d99-42c5-9673-05bff964bb65', NULL, NULL, '2026-01-07 15:03:17'),
('ffc1e771-ebd9-11f0-af0b-02501e7028bd', '31f6d560-4edb-427c-98b0-02ad73a02b2e', 'completed', '85c07057-5d99-42c5-9673-05bff964bb65', NULL, NULL, '2026-01-07 15:03:17');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` char(36) NOT NULL,
  `order_id` char(36) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` enum('cash','transfer','promptpay') NOT NULL DEFAULT 'cash',
  `status` enum('pending','confirmed','rejected') NOT NULL DEFAULT 'pending',
  `confirmed_by` char(36) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `provider` varchar(100) DEFAULT NULL,
  `provider_txn_id` varchar(255) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `amount`, `method`, `status`, `confirmed_by`, `confirmed_at`, `note`, `provider`, `provider_txn_id`, `paid_at`, `created_at`) VALUES
('1e51471d-ebda-11f0-af0b-02501e7028bd', 'facaabbf-2d78-4d7d-afd3-87431d2d1b76', 0.00, 'cash', 'confirmed', '85c07057-5d99-42c5-9673-05bff964bb65', '2026-01-07 22:04:09', NULL, 'cash', NULL, NULL, '2026-01-07 15:04:09'),
('20fcf06c-ebda-11f0-af0b-02501e7028bd', '7a34fd3b-27b8-4a32-a493-429bd412f1e7', 0.00, 'cash', 'confirmed', '85c07057-5d99-42c5-9673-05bff964bb65', '2026-01-07 22:04:13', NULL, 'cash', NULL, NULL, '2026-01-07 15:04:13'),
('25a44640-ebda-11f0-af0b-02501e7028bd', '7274dd8c-dcca-4aff-a723-1419b2b633eb', 0.00, 'cash', 'confirmed', '85c07057-5d99-42c5-9673-05bff964bb65', '2026-01-07 22:04:21', NULL, 'cash', NULL, NULL, '2026-01-07 15:04:21'),
('2736e8f2-ebda-11f0-af0b-02501e7028bd', '4e5c56b8-c75d-47b7-8525-b150d225301b', 0.00, 'cash', 'confirmed', '85c07057-5d99-42c5-9673-05bff964bb65', '2026-01-07 22:04:24', NULL, 'cash', NULL, NULL, '2026-01-07 15:04:24'),
('450ab38b-ebdb-11f0-af0b-02501e7028bd', '70d28895-0225-4517-8015-4fd8de276c50', 100.00, 'cash', 'confirmed', '85c07057-5d99-42c5-9673-05bff964bb65', '2026-01-07 22:12:23', NULL, 'cash', NULL, NULL, '2026-01-07 15:12:23'),
('ff20b265-ebd9-11f0-af0b-02501e7028bd', '31f6d560-4edb-427c-98b0-02ad73a02b2e', 0.00, 'cash', 'confirmed', '85c07057-5d99-42c5-9673-05bff964bb65', '2026-01-07 22:03:16', NULL, 'cash', NULL, NULL, '2026-01-07 15:03:16');

-- --------------------------------------------------------

--
-- Table structure for table `pickups`
--

CREATE TABLE `pickups` (
  `id` char(36) NOT NULL,
  `order_id` char(36) DEFAULT NULL,
  `assigned_to` char(36) DEFAULT NULL,
  `pickup_address` text DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `lat` decimal(9,6) DEFAULT NULL,
  `lng` decimal(9,6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pickups`
--

INSERT INTO `pickups` (`id`, `order_id`, `assigned_to`, `pickup_address`, `scheduled_at`, `completed_at`, `status`, `created_at`, `lat`, `lng`) VALUES
('0f86b849-ebd9-11f0-af0b-02501e7028bd', '275ee78d-5186-47c4-b9f9-965ee13a36a8', NULL, 'ที่อยู่จากลูกค้า', NULL, NULL, 'completed', '2026-01-07 14:56:34', NULL, NULL),
('ccad9493-ebd9-11f0-af0b-02501e7028bd', '31f6d560-4edb-427c-98b0-02ad73a02b2e', NULL, 'ที่อยู่จากลูกค้า', NULL, NULL, 'completed', '2026-01-07 15:01:52', NULL, NULL),
('d358b821-ebd9-11f0-af0b-02501e7028bd', '70d28895-0225-4517-8015-4fd8de276c50', NULL, 'ที่อยู่จากลูกค้า', NULL, NULL, 'completed', '2026-01-07 15:02:03', NULL, NULL),
('df0d8f30-ebd9-11f0-af0b-02501e7028bd', 'b0ef427c-f2f4-4bbc-bef4-c732fd3ab271', NULL, 'ที่อยู่จากลูกค้า', NULL, NULL, 'scheduled', '2026-01-07 15:02:23', NULL, NULL),
('f05add3d-ebd9-11f0-af0b-02501e7028bd', 'bc84bfc5-a3cb-484f-a211-95f41e8e446b', NULL, 'ที่อยู่จากลูกค้า', NULL, NULL, 'scheduled', '2026-01-07 15:02:52', NULL, NULL),
('f81255e2-ebd9-11f0-af0b-02501e7028bd', 'b5579e72-2b89-4409-b673-f30583fd4743', NULL, 'ที่อยู่จากลูกค้า', NULL, NULL, 'scheduled', '2026-01-07 15:03:05', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `id` char(36) NOT NULL,
  `created_by` char(36) DEFAULT NULL,
  `store_id` char(36) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `discount` int(11) DEFAULT 0,
  `summary` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `image` varchar(1000) DEFAULT NULL,
  `start_date` datetime NOT NULL DEFAULT current_timestamp(),
  `end_date` datetime DEFAULT current_timestamp(),
  `status` enum('active','inactive','draft') DEFAULT 'active',
  `audience` enum('all','stores','customers','store_specific') DEFAULT 'all',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`id`, `created_by`, `store_id`, `title`, `discount`, `summary`, `message`, `image`, `start_date`, `end_date`, `status`, `audience`, `metadata`, `created_at`, `updated_at`) VALUES
('3fdd904118f752ac73abdc0226012de9', NULL, NULL, '13', 321, NULL, NULL, '1767799455_2.png', '2026-01-07 00:00:00', '2026-01-15 00:00:00', 'active', 'all', NULL, '2026-01-07 15:24:15', '2026-01-07 15:24:15'),
('b7262692e6a824b5996adf6c167290eb', NULL, NULL, '11', 100, NULL, NULL, '1767791103_1.png', '2026-01-07 00:00:00', '2026-01-08 00:00:00', 'active', 'all', NULL, '2026-01-07 13:05:03', '2026-01-07 13:05:03');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `store_id` char(36) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('new','in_progress','resolved') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stores`
--

CREATE TABLE `stores` (
  `id` char(36) NOT NULL,
  `owner_id` char(36) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `lat` decimal(9,6) DEFAULT NULL,
  `lng` decimal(9,6) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `open_time` time DEFAULT NULL,
  `close_time` time DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `delivery_available` tinyint(1) DEFAULT 0,
  `status` enum('pending','active','disabled') DEFAULT 'pending',
  `timezone` varchar(50) DEFAULT 'Asia/Bangkok',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `billing_plan_id` int(11) DEFAULT NULL,
  `billing_start` date DEFAULT NULL,
  `billing_end` date DEFAULT NULL,
  `promptpay_qr` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stores`
--

INSERT INTO `stores` (`id`, `owner_id`, `name`, `address`, `description`, `lat`, `lng`, `phone`, `open_time`, `close_time`, `logo`, `cover_image`, `delivery_available`, `status`, `timezone`, `created_at`, `updated_at`, `billing_plan_id`, `billing_start`, `billing_end`, `promptpay_qr`) VALUES
('a66db143-5400-4ab4-ac68-07b065d5a387', '85c07057-5d99-42c5-9673-05bff964bb65', 'TAEEEEE', 'sdadsa', NULL, NULL, NULL, '1', NULL, NULL, NULL, NULL, 0, 'active', 'Asia/Bangkok', '2026-01-02 12:19:34', '2026-01-05 17:46:26', 10, NULL, NULL, 'uploads/promptpay/695bf8f222b67_promptpay.jpg'),
('ea0152ee-6de6-434e-8af9-6288b01464f1', '3f6e5117-c831-49b5-a1c7-1f70d6b8f034', 'ThippawanWongrang', 'บ้านต๋อม เมืองพะเยา 56000', NULL, NULL, NULL, '1', NULL, NULL, NULL, NULL, 0, 'active', 'Asia/Bangkok', '2026-01-07 14:17:34', '2026-01-07 14:17:34', 10, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `store_staff`
--

CREATE TABLE `store_staff` (
  `id` char(36) NOT NULL,
  `store_id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `role` enum('staff','store_owner') DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `store_staff`
--

INSERT INTO `store_staff` (`id`, `store_id`, `user_id`, `role`, `created_at`) VALUES
('35146f37-ea47-11f0-adff-02501e7028bd', 'a66db143-5400-4ab4-ac68-07b065d5a387', 'cfc47b5b-540c-40b7-a362-80ea230256d6', 'staff', '2026-01-05 14:59:59'),
('39163f78-1b33-4217-866b-df3db435ab06', 'ea0152ee-6de6-434e-8af9-6288b01464f1', '3f6e5117-c831-49b5-a1c7-1f70d6b8f034', 'store_owner', '2026-01-07 14:17:34'),
('6957c7f499c79', 'a66db143-5400-4ab4-ac68-07b065d5a387', 'b362adf6-3310-48ae-a338-ee6594129f3a', 'staff', '2026-01-02 13:28:20'),
('e54d93d7-e88c-11f0-adff-02501e7028bd', 'a66db143-5400-4ab4-ac68-07b065d5a387', '85c07057-5d99-42c5-9673-05bff964bb65', 'store_owner', '2026-01-03 10:13:46');

-- --------------------------------------------------------

--
-- Table structure for table `store_subscriptions`
--

CREATE TABLE `store_subscriptions` (
  `id` char(36) NOT NULL,
  `store_id` char(36) DEFAULT NULL,
  `plan` varchar(50) DEFAULT NULL,
  `monthly_fee` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','waiting_approve','active','expired','rejected') NOT NULL DEFAULT 'pending',
  `slip_image` varchar(255) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `approved_by` char(36) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` char(36) NOT NULL,
  `email` varchar(30) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` text DEFAULT NULL,
  `display_name` varchar(30) DEFAULT NULL,
  `role` enum('customer','platform_admin','store_owner','staff','rider') DEFAULT 'customer',
  `detail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_image` varchar(100) DEFAULT NULL,
  `status` enum('active','disabled') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `phone`, `password_hash`, `display_name`, `role`, `detail`, `created_at`, `updated_at`, `profile_image`, `status`) VALUES
('3f6e5117-c831-49b5-a1c7-1f70d6b8f034', '1@1.1', '1', '$2y$10$lXGcPLsUC9ylwtB5XgTg7u818Slkc21h4VEl1H/pYNV01IC5F7rbK', 'Awang', 'store_owner', '', '2026-01-07 13:20:15', '2026-01-07 13:20:15', 'uploads/profile/f78329a9-b825-44c4-b760-b55cff54cd1d.jpg', 'active'),
('42e1766f-19c3-40a4-be22-7a7c63379363', '4@4.4', '1', '$2y$10$6SXN3sxz2oxpcozozdrD9OGGOrvWxlPUwxi8YPf3cCsfdH6Gt.ceK', 'โปเต้', 'platform_admin', NULL, '2026-01-01 05:19:24', '2026-01-01 09:58:01', 'uploads/profile/03289dbc-b945-475d-a772-432e4744eba4.png', 'active'),
('770cea2b-ac93-4c64-af20-b24313a57554', '7@7.7', '1', '$2y$10$8Ouos6m9qas1Wbts8ZasC.2xFye6aRUG7qs/nnKE8HhwJDbFmlVbS', 'ลูกค้า1', 'customer', 'บ้านร่องห้า', '2026-01-02 13:49:41', '2026-01-05 18:22:08', 'uploads/profile/9803ffbf-7d33-4029-aae5-00ccc76dfd32.png', 'active'),
('85c07057-5d99-42c5-9673-05bff964bb65', '3@3.3', '1', '$2y$10$gDCD2cJCGKQBYh2APaZyDuG/dbl3MS6oum6du26pUPFON8LJUCbNW', 'โปเต้1111', 'store_owner', '{\"detail\":\"อะไรไม่รู้\"}', '2026-01-01 05:29:16', '2026-01-07 15:19:36', 'uploads/profile/695e786225a7d_profile.jpg', 'active'),
('b362adf6-3310-48ae-a338-ee6594129f3a', '6@6.6', '1', '$2y$10$U7RKrhnap895mAlLugl1dezaT67rG4Z9SVm3ABB9F6dSrW5/npSMG', 'โปเต้(พนักงาน)d', 'staff', 'dsfdsfsdfdsgdsgdfgdf', '2026-01-01 05:41:08', '2026-01-05 14:33:34', 'uploads/profile/e0b14be9-c08a-4a32-9348-844929017987.png', 'active'),
('cfc47b5b-540c-40b7-a362-80ea230256d6', '10@10.10', '1', '$2y$10$lvwPhsNsGbwb.4sUjnNwQ.rhYCcm6AsxARjTqlYiev8GCpLgBmXh2', 'พนักงาน2', 'staff', '', '2026-01-05 14:57:39', '2026-01-05 14:57:39', 'uploads/profile/ca53f013-a0cf-4cac-9e02-3987bfc17e80.png', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `billing_plans`
--
ALTER TABLE `billing_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `store_id` (`store_id`);

--
-- Indexes for table `order_status_logs`
--
ALTER TABLE `order_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `pickups`
--
ALTER TABLE `pickups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_promotions_created_by` (`created_by`),
  ADD KEY `idx_promotions_store` (`store_id`),
  ADD KEY `idx_promotions_status` (`status`),
  ADD KEY `idx_promotions_dates` (`start_date`,`end_date`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stores`
--
ALTER TABLE `stores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `fk_store_plan` (`billing_plan_id`);

--
-- Indexes for table `store_staff`
--
ALTER TABLE `store_staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_store_user` (`store_id`,`user_id`),
  ADD KEY `store_id` (`store_id`),
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
-- AUTO_INCREMENT for table `billing_plans`
--
ALTER TABLE `billing_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`);

--
-- Constraints for table `order_status_logs`
--
ALTER TABLE `order_status_logs`
  ADD CONSTRAINT `order_status_logs_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_status_logs_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `order_status_logs_ibfk_3` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `pickups`
--
ALTER TABLE `pickups`
  ADD CONSTRAINT `pickups_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `pickups_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`);

--
-- Constraints for table `promotions`
--
ALTER TABLE `promotions`
  ADD CONSTRAINT `fk_promotions_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_promotions_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stores`
--
ALTER TABLE `stores`
  ADD CONSTRAINT `fk_store_plan` FOREIGN KEY (`billing_plan_id`) REFERENCES `billing_plans` (`id`),
  ADD CONSTRAINT `stores_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `store_staff`
--
ALTER TABLE `store_staff`
  ADD CONSTRAINT `store_staff_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`),
  ADD CONSTRAINT `store_staff_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
