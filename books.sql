-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 20 أكتوبر 2026 الساعة 10:39
-- إصدار الخادم: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `books`
--

-- --------------------------------------------------------

--
-- بنية الجدول `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `author` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `rating` decimal(2,1) NOT NULL DEFAULT 0.0,
  `purchase_location` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `publisher_id` int(10) UNSIGNED DEFAULT NULL,
  `stock` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `description`, `price`, `rating`, `purchase_location`, `image`, `category_id`, `publisher_id`, `stock`) VALUES
(2, 'في قلبي أنثى عبرية', 'خولة حمدي', 'رواية اجتماعية إنسانية.', 180.00, 0.0, NULL, NULL, 10, NULL, 12),
(3, 'أرض زيكولا', 'عمرو عبد الحميد', 'رواية خيالية عن عالم مختلف.', 150.00, 0.0, NULL, NULL, 10, NULL, 8),
(4, 'تعلم PHP من البداية', 'أحمد محمد', 'مقدمة عملية لتعلم لغة PHP.', 220.00, 0.0, NULL, NULL, 3, NULL, 10),
(5, 'أساسيات قواعد البيانات', 'سارة علي', 'شرح مبسط لقواعد بيانات MySQL.', 200.00, 0.0, NULL, NULL, 3, NULL, 7),
(6, 'العادات الذرية', 'جيمس كلير', 'خطوات عملية لبناء عادات أفضل.', 250.00, 0.0, NULL, NULL, 4, NULL, 15),
(7, 'فن اللامبالاة', 'مارك مانسون', 'نظرة مختلفة للتعامل مع تحديات الحياة.', 210.00, 0.0, NULL, NULL, 4, NULL, 7),
(8, 'قصص من التاريخ الإسلامي', 'علي الطنطاوي', 'مجموعة مختارة من القصص التاريخية.', 175.00, 0.0, NULL, NULL, 9, NULL, 6),
(9, 'عالمنا من حولنا', 'مصطفى محمود', 'رحلة مبسطة في العلوم والمعرفة.', 190.00, 0.0, NULL, NULL, 6, NULL, 10);

-- --------------------------------------------------------

--
-- بنية الجدول `book_access`
--

CREATE TABLE `book_access` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `book_id` int(10) UNSIGNED NOT NULL,
  `granted_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `book_access`
--

INSERT INTO `book_access` (`id`, `user_id`, `book_id`, `granted_by`, `created_at`) VALUES
(1, 4, 3, 1, '2026-09-23 19:00:59'),
(3, 8, 3, 1, '2026-09-25 09:40:14');

-- --------------------------------------------------------

--
-- بنية الجدول `book_comments`
--

CREATE TABLE `book_comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `book_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `book_comments`
--

INSERT INTO `book_comments` (`id`, `book_id`, `user_id`, `body`, `created_at`) VALUES
(1, 9, 1, 'رائع', '2026-09-23 19:35:37'),
(2, 8, 1, 'fgsd', '2026-09-23 19:42:24'),
(3, 3, 8, 'رائع جدا', '2026-09-25 09:41:10'),
(4, 8, 10, 'رائع', '2026-09-25 09:47:29');

-- --------------------------------------------------------

--
-- بنية الجدول `book_ratings`
--

CREATE TABLE `book_ratings` (
  `id` int(10) UNSIGNED NOT NULL,
  `book_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `book_ratings`
--

INSERT INTO `book_ratings` (`id`, `book_id`, `user_id`, `rating`, `created_at`) VALUES
(1, 8, 1, 5, '2026-09-23 19:42:18'),
(2, 9, 4, 5, '2026-09-23 19:44:42'),
(3, 3, 4, 5, '2026-09-23 20:44:11'),
(4, 9, 8, 5, '2026-09-25 09:38:31'),
(5, 3, 8, 5, '2026-09-25 09:40:48'),
(6, 8, 10, 5, '2026-09-25 09:47:50');

-- --------------------------------------------------------

--
-- بنية الجدول `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(3, 'برمجة وتطوير'),
(4, 'تطوير الذات'),
(6, 'علوم ومعرفة'),
(9, 'تاريخ'),
(10, 'روايات');

-- --------------------------------------------------------

--
-- بنية الجدول `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `customer_name` varchar(160) NOT NULL,
  `customer_email` varchar(190) NOT NULL,
  `whatsapp` varchar(30) NOT NULL,
  `address` text NOT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(40) NOT NULL DEFAULT 'instapay',
  `payment_reference` varchar(120) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `customer_name`, `customer_email`, `whatsapp`, `address`, `total`, `payment_method`, `payment_reference`, `status`, `created_at`) VALUES
(6, 4, 'youssef yaser', 'youssefyaser030@gmail.com', '01121771193', 'gh sdfasd', 210.00, 'instapay', NULL, 'paid', '2026-09-23 19:00:05'),
(7, 8, 'youssef yaser', 'youssefyaser030@gmail.com', '01121771193', 'qubaa', 190.00, 'instapay', NULL, 'paid', '2026-09-25 09:39:00'),
(8, 10, 'youssef yaser', 'jyossif07@gmail.com', '01121771193', 'cairo', 210.00, 'instapay', NULL, 'pending', '2026-09-25 09:47:01');

-- --------------------------------------------------------

--
-- بنية الجدول `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `book_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `book_id`, `title`, `price`, `quantity`) VALUES
(6, 6, 7, 'فن اللامبالاة', 210.00, 1),
(7, 7, 9, 'عالمنا من حولنا', 190.00, 1),
(8, 8, 7, 'فن اللامبالاة', 210.00, 1);

-- --------------------------------------------------------

--
-- بنية الجدول `points_ledger`
--

CREATE TABLE `points_ledger` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `event_key` varchar(120) NOT NULL,
  `points` int(11) NOT NULL,
  `label` varchar(160) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `points_ledger`
--

INSERT INTO `points_ledger` (`id`, `user_id`, `event_key`, `points`, `label`, `created_at`) VALUES
(1, 8, 'rating:9', 15, 'تقييم كتاب', '2026-09-25 09:38:31'),
(2, 8, 'order:7', 50, 'إنشاء طلب شراء', '2026-09-25 09:39:00'),
(3, 8, 'rating:3', 15, 'تقييم كتاب', '2026-09-25 09:40:48'),
(4, 8, 'comment:3', 10, 'نشر تعليق', '2026-09-25 09:41:10'),
(5, 9, 'registration', 100, 'إنشاء حساب جديد', '2026-09-25 09:45:30'),
(6, 10, 'registration', 100, 'إنشاء حساب جديد', '2026-09-25 09:46:19'),
(7, 10, 'order:8', 50, 'إنشاء طلب شراء', '2026-09-25 09:47:01'),
(8, 10, 'comment:4', 10, 'نشر تعليق', '2026-09-25 09:47:29'),
(9, 10, 'rating:8', 15, 'تقييم كتاب', '2026-09-25 09:47:50');

-- --------------------------------------------------------

--
-- بنية الجدول `publishers`
--

CREATE TABLE `publishers` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(190) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`) VALUES
(8, 'Yossif Yaser', 'youssefyaser030@gmail.com', '$2y$10$/CiknECUnLwT0PMJ6hEh6eFymNMRhj9y.sNJYDMrC/GSXoL2F1rCq', 'admin'),
(9, 'youssef yaser', 'youssefyaser819@gmail.com', '$2y$10$35sCqxf3Taqb4EwVjwZeMOEG9t/nmbmQxDu3P/6oxDrGjF6ge114G', 'user'),
(10, 'youssef yaser', 'jyossif07@gmail.com', '$2y$10$MQwTJ.5ovXe4QGGMy2R.WeGGCUbyzmuprGFRHR/XS3ATBksT5mcBC', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `book_access`
--
ALTER TABLE `book_access`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_book` (`user_id`,`book_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `book_comments`
--
ALTER TABLE `book_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `book_ratings`
--
ALTER TABLE `book_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_book_rating` (`book_id`,`user_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `points_ledger`
--
ALTER TABLE `points_ledger`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_event` (`user_id`,`event_key`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `points` (`points`);

--
-- Indexes for table `publishers`
--
ALTER TABLE `publishers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `book_access`
--
ALTER TABLE `book_access`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `book_comments`
--
ALTER TABLE `book_comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `book_ratings`
--
ALTER TABLE `book_ratings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `points_ledger`
--
ALTER TABLE `points_ledger`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `publishers`
--
ALTER TABLE `publishers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- قيود الجداول المُلقاة.
--

--
-- قيود الجداول `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
