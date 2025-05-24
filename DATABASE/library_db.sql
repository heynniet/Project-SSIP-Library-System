-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 24, 2025 at 04:37 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `library_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `author` varchar(100) NOT NULL,
  `isbn` varchar(13) NOT NULL,
  `published_year` int(11) NOT NULL,
  `genre_id` int(11) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `title`, `author`, `isbn`, `published_year`, `genre_id`, `cover_image`) VALUES
(1, 'To Kill a Mockingbird', ' Harper Lee', '9780061120084', 1961, NULL, 'Uploads/6829f9a1c97a6.jpg'),
(2, '1984', 'George Orwell', '9780452284234', 1925, NULL, 'Uploads/6829f96c12af3.jpg'),
(3, 'The Hobbit', 'J.R.R. Tolkien', '9780618002213', 1937, NULL, 'Uploads/6829f99a0a566.jpg'),
(4, 'Pride and Prejudice', 'Jane Austen', '9780141439518', 1813, NULL, 'Uploads/6829f9817beab.jpg'),
(5, 'Pusri si Petualang', 'Pusri', '1234567890', 2000, NULL, 'Uploads/6829f991bd41b.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `book_donations`
--

CREATE TABLE `book_donations` (
  `donation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_title` varchar(255) NOT NULL,
  `author` varchar(100) NOT NULL,
  `publisher` varchar(100) DEFAULT NULL,
  `publication_year` year(4) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `book_cover` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `rejection_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `book_donations`
--

INSERT INTO `book_donations` (`donation_id`, `user_id`, `book_title`, `author`, `publisher`, `publication_year`, `description`, `book_cover`, `status`, `created_at`, `updated_at`, `rejection_reason`) VALUES
(10, 2, 'The Pragmatic Programmer', 'Andrew Hunt and David Thomas', 'Addison-Wesley', '1999', 'A practical guide for software developers on writing clean and maintainable code.', 'covers/pragmatic_programmer.jpg', 'Approved', '2025-05-23 21:55:07', '2025-05-23 21:55:07', NULL),
(11, 3, 'Clean Code', 'Robert C. Martin', 'Prentice Hall', '2008', 'This book emphasizes the importance of writing readable and efficient code.', 'covers/clean_code.jpg', 'Pending', '2025-05-23 21:55:07', '2025-05-23 21:55:07', NULL),
(12, 4, 'Introduction to Algorithms', 'Thomas H. Cormen', 'MIT Press', '2009', 'Comprehensive textbook covering a broad range of algorithms in depth.', 'covers/intro_algorithms.jpg', 'Rejected', '2025-05-23 21:55:07', '2025-05-23 21:55:07', 'Book is outdated edition and not suitable for current curriculum.');

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `loan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `loan_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `fine_amount` bigint(20) DEFAULT 0,
  `due_date` datetime NOT NULL,
  `borrower_name` varchar(50) NOT NULL,
  `borrower_email` varchar(50) NOT NULL,
  `notes` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loans`
--

INSERT INTO `loans` (`loan_id`, `user_id`, `book_id`, `loan_date`, `return_date`, `fine_amount`, `due_date`, `borrower_name`, `borrower_email`, `notes`) VALUES
(15, 8, 4, '2025-05-10', '2025-05-18', 0, '2025-05-25 00:00:00', 'daniel', 'daniel@gmail.com', ''),
(16, 8, 3, '2025-05-10', '2025-05-18', 0, '2025-05-25 00:00:00', 'daniel', 'daniel@gmail.com', ''),
(19, 6, 3, '2025-05-17', '2025-05-18', 0, '2025-05-31 00:00:00', 'nietz', 'nietz@gmail.com', ''),
(32, 5, 4, '2025-05-18', NULL, 0, '2025-06-02 00:00:00', 'bie', 'bie@gmail.com', ''),
(33, 5, 2, '2025-05-18', NULL, 0, '2025-06-02 00:00:00', 'bie', 'bie@gmail.com', ''),
(34, 5, 5, '2025-05-18', NULL, 0, '2025-06-02 00:00:00', 'bie', 'bie@gmail.com', ''),
(38, 6, 4, '2025-05-23', NULL, 0, '2025-06-07 00:00:00', 'nietz', 'nietz@gmail.com', ''),
(39, 6, 5, '2025-05-23', '2025-05-23', 0, '2025-06-07 00:00:00', 'nietz', 'nietz@gmail.com', ''),
(40, 6, 2, '2025-05-23', NULL, 0, '2025-06-07 00:00:00', 'nietz', 'nietz@gmail.com', ''),
(41, 6, 1, '2025-05-23', NULL, 0, '2025-06-07 00:00:00', 'nietz', 'nietz@gmail.com', '');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `amount` bigint(20) NOT NULL,
  `payment_date` datetime NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `join_date` date NOT NULL,
  `password` varchar(100) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `phone`, `join_date`, `password`, `role`) VALUES
(2, 'Nabil', 'nabil@gmail.com', '081234567891', '2025-03-11', '', 'admin'),
(3, 'Gabriel', 'gabriel@gmail.com', '081234567892', '2025-04-28', '', 'user'),
(4, 'wilson', 'wilson@gmail.com', '081234567893', '2025-04-18', '', 'user'),
(5, 'bie', 'bie@gmail.com', '081292412117', '2025-05-02', 'bie123', 'user'),
(6, 'nietz', 'nietz@gmail.com', '081298987272', '2025-05-02', 'nietz123', 'user'),
(7, 'pusri', 'pusri@example.com', '081292412117', '2025-05-05', 'pusri123', 'admin'),
(8, 'daniel', 'daniel@gmail.com', '089876547583', '2025-05-06', 'daniel123', 'user'),
(9, 'gery', 'gery@gmail.com', '081298769787', '2025-05-06', 'gery123', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`),
  ADD UNIQUE KEY `isbn` (`isbn`);

--
-- Indexes for table `book_donations`
--
ALTER TABLE `book_donations`
  ADD PRIMARY KEY (`donation_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`loan_id`),
  ADD KEY `member_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `loan_id` (`loan_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `book_donations`
--
ALTER TABLE `book_donations`
  MODIFY `donation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `loan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `book_donations`
--
ALTER TABLE `book_donations`
  ADD CONSTRAINT `book_donations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loans_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`loan_id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
