-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 14, 2025 at 04:58 AM
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
-- Database: `capstone`
--

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

CREATE TABLE `lessons` (
  `lesson_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `lesson_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lessons`
--

INSERT INTO `lessons` (`lesson_id`, `module_id`, `title`, `content`, `lesson_order`, `created_at`) VALUES
(1, 1, 'Introduction to Soil Health', '<h2>Understanding Soil Health</h2><p>Soil health is the foundation of successful farming. Healthy soil provides plants with essential nutrients, proper drainage, and a stable growing environment.</p><h3>Key Components of Healthy Soil</h3><ul><li><strong>Organic Matter:</strong> Decomposed plant and animal materials that enrich the soil</li><li><strong>Proper pH Levels:</strong> Most crops thrive in slightly acidic to neutral soil (6.0-7.0 pH)</li><li><strong>Good Drainage:</strong> Prevents waterlogging and root rot</li><li><strong>Beneficial Microorganisms:</strong> Help break down nutrients for plant absorption</li></ul><div class=\"highlight\"><p><strong>Pro Tip:</strong> Test your soil annually to monitor its health and make necessary adjustments for optimal crop growth.</p></div>', 1, '2025-09-12 04:17:14'),
(2, 1, 'Soil Testing and Analysis', '<h2>Soil Testing and Analysis</h2><p>Regular soil testing is crucial for maintaining optimal growing conditions and maximizing crop yields.</p><h3>What to Test For</h3><ul><li><strong>pH Level:</strong> Determines nutrient availability</li><li><strong>Nutrient Content:</strong> NPK (Nitrogen, Phosphorus, Potassium) levels</li><li><strong>Organic Matter Percentage:</strong> Indicates soil fertility</li><li><strong>Soil Texture:</strong> Sand, silt, and clay composition</li></ul><h3>How to Collect Soil Samples</h3><ol><li>Use a clean spade or soil probe</li><li>Take samples from multiple locations (5-10 spots)</li><li>Collect soil from 6-8 inches deep</li><li>Mix samples in a clean container</li><li>Send to a certified lab for analysis</li></ol><div class=\"highlight\"><p><strong>Remember:</strong> Different crops may require different soil conditions, so test areas separately if you plan to grow various plants.</p></div>', 2, '2025-09-12 04:17:14'),
(3, 1, 'Soil Preparation Techniques', '<h2>Soil Preparation Techniques</h2><p>Proper soil preparation sets the stage for successful planting and healthy crop development.</p><h3>Essential Preparation Steps</h3><ol><li><strong>Clear the Area:</strong> Remove weeds, rocks, and debris</li><li><strong>Till the Soil:</strong> Break up compacted earth to 8-12 inches deep</li><li><strong>Add Organic Matter:</strong> Incorporate compost or well-aged manure</li><li><strong>Level the Surface:</strong> Create uniform planting beds</li><li>Final Cultivation:</strong> Break up any remaining clods</li></ol><h3>Timing is Everything</h3><p>Prepare soil when it has proper moisture content - not too wet (muddy) or too dry (dusty). The soil should crumble in your hand but not stick together when squeezed.</p><h3>Tools You\'ll Need</h3><ul><li>Spade or rototiller for breaking ground</li><li>Rake for leveling and smoothing</li><li>Hoe for creating planting rows</li><li>Wheelbarrow for moving materials</li></ul>', 3, '2025-09-12 04:17:14');

-- --------------------------------------------------------

--
-- Table structure for table `lesson_progress`
--

CREATE TABLE `lesson_progress` (
  `progress_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `module_id` int(100) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `image_path` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`module_id`, `title`, `description`, `image_path`, `created_at`, `updated_at`) VALUES
(1, 'Introduction to Urban Agriculture', 'This module provides an overview of urban agriculture, its benefits, and its potential to address food security, environmental sustainability, and community resilience. It sets the foundation for understanding how farming can thrive in urban spaces.', 'https://unity.edu/wp-content/uploads/2025/04/Urban-Farming-scaled-1-1024x683.jpg.webp', '2024-12-01 13:48:43', '2025-07-06 11:35:39');

-- --------------------------------------------------------

--
-- Table structure for table `plant`
--

CREATE TABLE `plant` (
  `plant_id` int(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image` varchar(255) NOT NULL,
  `container_soil` text DEFAULT NULL,
  `watering` text DEFAULT NULL,
  `sunlight` text DEFAULT NULL,
  `tips` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plant`
--

INSERT INTO `plant` (`plant_id`, `name`, `description`, `image`, `container_soil`, `watering`, `sunlight`, `tips`) VALUES
(3, 'Eggplant', 'A versatile plant for urban farming, eggplants can be grown in large pots or vertical gardens. They are heat-tolerant and produce an abundant harvest in limited spaces. Perfect for homegrown meals, eggplants bring a fresh, rich flavor to dishes like stir-fries and pasta.', '../images/eggplant.jpg', 'Large pot, nutrient-rich soil', 'Water evenly, avoid overwatering', '6-8 hours sunlight', 'Use stakes or cages for support'),
(4, 'Lettuce', 'A staple for urban farming beginners, lettuce is easy to grow in small containers or hydroponic systems. With its quick growth cycle, it provides a continuous harvest of fresh greens for salads and wraps. Lettuce thrives in partial sunlight, making it ideal for balcony or rooftop gardens.\r\n\r\n', '../images/lettuce.jpg', 'Shallow containers, loose soil', 'Water lightly and frequently', 'Partial sunlight', 'Harvest outer leaves first'),
(6, 'Tomato', 'A favorite for urban farmers, tomatoes grow well in containers, hanging baskets, or vertical trellises. They love sunlight and adapt easily to urban spaces, producing vibrant, juicy fruits. Whether cherry or beefsteak, fresh tomatoes are a rewarding addition to any urban garden.\r\n\r\n', '../images/tomato.jpg', 'Large container, loose nutrient-rich soil', 'Water deeply and consistently', '6-8 hours sunlight', 'Use stakes, cages, or trellises for support');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `question_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reply`
--

CREATE TABLE `reply` (
  `reply_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `body` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suggestions`
--

CREATE TABLE `suggestions` (
  `suggestion_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suggestions`
--

INSERT INTO `suggestions` (`suggestion_id`, `message`, `created_at`, `status`) VALUES
(3, 'Dagdag Modules', '2024-12-04 05:05:12', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','admin','agriculturist','new user') NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(10) DEFAULT 'active',
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `username`, `password`, `role`, `date_created`, `status`, `profile_picture`) VALUES
(1, 'admin', 'admin', '$2y$10$B.jf5ql45Tg/wj5YijIaZuANWPNmTtc0GGh.ZfTSckvnl.SCITgmq', 'admin', '2024-12-01 06:06:20', 'approved', NULL),
(22, 'Alexander F. Lavarias', 'alex', '$2y$10$hZuqccRaPWGGsdOEcRbte.YjPm6fnrsJiZECLU8Q//582pMOhPjN2', 'student', '2025-07-01 16:09:16', 'active', 'user_22_1751642780.jpg'),
(30, 'Stewie Griffin', 'new', '$2y$10$P3SZALltL.mZxQk.AsFMFuyddwyuy/ihrEPq6haFNS8Qm3V55qJF.', 'student', '2025-07-05 18:59:49', 'active', 'user_30_1751745029.jpg'),
(33, 'haha', 'haha', '$2y$10$TtHNLWXDNxBe1mnd5E247.y3rXMXb2dmRirgB6P1Cs47TILOjzQjS', 'student', '2025-09-06 01:47:06', 'active', NULL),
(34, 'hehe', 'hehe', '$2y$10$vX8dwyI6kp8BSIdnE9BCMOW1/eaTW/hObVmVJGLhLNtnPewhJMOSO', 'student', '2025-09-11 04:20:25', 'active', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`lesson_id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD PRIMARY KEY (`progress_id`),
  ADD UNIQUE KEY `unique_user_lesson` (`user_id`,`lesson_id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`module_id`);

--
-- Indexes for table `plant`
--
ALTER TABLE `plant`
  ADD PRIMARY KEY (`plant_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reply`
--
ALTER TABLE `reply`
  ADD PRIMARY KEY (`reply_id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `suggestions`
--
ALTER TABLE `suggestions`
  ADD PRIMARY KEY (`suggestion_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `lesson_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  MODIFY `progress_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `module_id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `plant`
--
ALTER TABLE `plant`
  MODIFY `plant_id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `reply`
--
ALTER TABLE `reply`
  MODIFY `reply_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `suggestions`
--
ALTER TABLE `suggestions`
  MODIFY `suggestion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `lessons_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`);

--
-- Constraints for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD CONSTRAINT `lesson_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `lesson_progress_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`lesson_id`);

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `reply`
--
ALTER TABLE `reply`
  ADD CONSTRAINT `reply_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reply_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
