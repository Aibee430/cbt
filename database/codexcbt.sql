-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 04, 2026 at 05:17 PM
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
-- Database: `codexcbt`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('super_admin','exam_manager','result_manager','viewer') NOT NULL DEFAULT 'super_admin',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `name`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'Admin User', 'admin@codexcbt.local', '$2y$10$nfF6/ATPVeBVWseoh/WC1eCh5g.XjU9XamU0vNK1nNheJhw6XEG6m', 'super_admin', '2026-01-09 14:29:40');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `name`, `created_at`) VALUES
(1, 'Class A', '2026-01-09 14:29:40'),
(2, 'Class B', '2026-01-09 15:17:30');

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `instructions` text DEFAULT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `duration_minutes` int(11) NOT NULL,
  `question_count` int(11) NOT NULL,
  `randomize` tinyint(1) NOT NULL DEFAULT 1,
  `allow_multiple_attempts` tinyint(1) NOT NULL DEFAULT 0,
  `max_attempts` int(11) NOT NULL DEFAULT 1,
  `show_result` enum('immediate','after_release') NOT NULL DEFAULT 'after_release',
  `result_release_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`id`, `title`, `subject_id`, `instructions`, `start_at`, `end_at`, `duration_minutes`, `question_count`, `randomize`, `allow_multiple_attempts`, `max_attempts`, `show_result`, `result_release_at`, `created_at`) VALUES
(1, 'Sample CBT Test', 1, 'Answer all questions. Essay will be graded later.', '2026-01-09 14:29:41', '2026-01-16 14:29:41', 20, 3, 1, 0, 1, 'immediate', NULL, '2026-01-09 14:29:41'),
(2, 'PHYSICS FIRST TEST FOR CLASS B', 2, 'This is the instruction for the examination', '2026-01-09 18:37:00', '2026-01-14 21:40:00', 10, 10, 1, 1, 3, 'immediate', NULL, '2026-01-09 15:37:10');

-- --------------------------------------------------------

--
-- Table structure for table `exam_answers`
--

CREATE TABLE `exam_answers` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_option_id` int(11) DEFAULT NULL,
  `answer_text` text DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `marks_awarded` decimal(8,2) DEFAULT NULL,
  `graded_by` int(11) DEFAULT NULL,
  `graded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_answers`
--

INSERT INTO `exam_answers` (`id`, `attempt_id`, `question_id`, `selected_option_id`, `answer_text`, `is_correct`, `marks_awarded`, `graded_by`, `graded_at`) VALUES
(1, 1, 4, 5, 'Newton', 1, 1.00, NULL, NULL),
(2, 1, 6, 13, '9.8 m/s^2', 1, 1.00, NULL, NULL),
(3, 1, 9, 26, 'Kinetic energy', 1, 1.00, NULL, NULL),
(4, 1, 10, 31, 'Watt', 1, 1.00, NULL, NULL),
(5, 1, 15, NULL, 'time', 1, 1.00, NULL, NULL),
(6, 1, 16, NULL, 'friction', 1, 1.00, NULL, NULL),
(7, 1, 18, NULL, 'acceleration', 1, 1.00, NULL, NULL),
(8, 1, 19, NULL, 'a', 0, 0.00, NULL, NULL),
(9, 1, 20, NULL, 'we', 0, 0.00, NULL, NULL),
(10, 1, 23, NULL, 'distant', 0, 0.00, NULL, NULL),
(11, 2, 1, 2, 'Green', 0, 0.00, NULL, NULL),
(12, 2, 2, NULL, 'Abuja', 1, 2.00, NULL, NULL),
(13, 2, 3, NULL, '', NULL, NULL, NULL, NULL),
(14, 3, 4, 5, 'Newton', 1, 1.00, NULL, NULL),
(15, 3, 5, 10, 'Velocity', 1, 1.00, NULL, NULL),
(16, 3, 6, 13, '9.8 m/s^2', 1, 1.00, NULL, NULL),
(17, 3, 8, 22, 'Ammeter', 1, 1.00, NULL, NULL),
(18, 3, 10, 31, 'Watt', 1, 1.00, NULL, NULL),
(19, 3, 11, 35, 'Electromagnetic wave', 1, 1.00, NULL, NULL),
(20, 3, 12, 39, 'Remains at rest or moves at constant velocity', 1, 1.00, NULL, NULL),
(21, 3, 19, NULL, 'Amp', 0, 0.00, NULL, NULL),
(22, 3, 20, NULL, 'pak', 0, 0.00, NULL, NULL),
(23, 3, 21, NULL, 'Potential', 0, 0.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `exam_assignments`
--

CREATE TABLE `exam_assignments` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_assignments`
--

INSERT INTO `exam_assignments` (`id`, `exam_id`, `class_id`) VALUES
(1, 1, 1),
(2, 2, 2);

-- --------------------------------------------------------

--
-- Table structure for table `exam_attempts`
--

CREATE TABLE `exam_attempts` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `attempt_number` int(11) NOT NULL DEFAULT 1,
  `started_at` datetime NOT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `status` enum('in_progress','submitted','graded') NOT NULL DEFAULT 'in_progress',
  `score` decimal(8,2) NOT NULL DEFAULT 0.00,
  `total_marks` decimal(8,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_attempts`
--

INSERT INTO `exam_attempts` (`id`, `exam_id`, `student_id`, `attempt_number`, `started_at`, `submitted_at`, `status`, `score`, `total_marks`) VALUES
(1, 2, 2, 1, '2026-01-09 21:10:11', '2026-01-09 21:19:18', 'graded', 7.00, 10.00),
(2, 1, 1, 1, '2026-01-12 23:44:50', '2026-01-13 00:05:29', 'submitted', 2.00, 8.00),
(3, 2, 2, 2, '2026-01-13 00:07:34', '2026-01-13 00:16:11', 'graded', 7.00, 10.00);

-- --------------------------------------------------------

--
-- Table structure for table `exam_attempt_questions`
--

CREATE TABLE `exam_attempt_questions` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_attempt_questions`
--

INSERT INTO `exam_attempt_questions` (`id`, `attempt_id`, `question_id`) VALUES
(1, 1, 4),
(6, 1, 6),
(2, 1, 9),
(3, 1, 10),
(8, 1, 15),
(4, 1, 16),
(5, 1, 18),
(9, 1, 19),
(7, 1, 20),
(10, 1, 23),
(12, 2, 1),
(13, 2, 2),
(11, 2, 3),
(18, 3, 4),
(22, 3, 5),
(14, 3, 6),
(23, 3, 8),
(20, 3, 10),
(16, 3, 11),
(19, 3, 12),
(21, 3, 19),
(17, 3, 20),
(15, 3, 21);

-- --------------------------------------------------------

--
-- Table structure for table `exam_questions`
--

CREATE TABLE `exam_questions` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_questions`
--

INSERT INTO `exam_questions` (`id`, `exam_id`, `question_id`) VALUES
(1, 2, 4),
(2, 2, 5),
(3, 2, 6),
(4, 2, 7),
(5, 2, 8),
(6, 2, 9),
(7, 2, 10),
(8, 2, 11),
(9, 2, 12),
(10, 2, 13),
(11, 2, 14),
(12, 2, 15),
(13, 2, 16),
(14, 2, 17),
(15, 2, 18);

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('mcq','fill','essay') NOT NULL,
  `correct_answer` text DEFAULT NULL,
  `marks` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `subject_id`, `question_text`, `question_type`, `correct_answer`, `marks`, `created_at`) VALUES
(1, 1, 'Which color is the sky on a clear day?', 'mcq', NULL, 1, '2026-01-09 14:29:40'),
(2, 1, 'The capital of Nigeria is _____.', 'fill', 'Abuja', 2, '2026-01-09 14:29:40'),
(3, 1, 'Explain why time management matters during exams.', 'essay', NULL, 5, '2026-01-09 14:29:40'),
(4, 2, 'What is the SI unit of force?', 'mcq', NULL, 1, '2026-01-09 15:34:15'),
(5, 2, 'Which quantity is a vector?', 'mcq', NULL, 1, '2026-01-09 15:34:15'),
(6, 2, 'What is the acceleration due to gravity on Earth (approx.)?', 'mcq', NULL, 1, '2026-01-09 15:34:15'),
(7, 2, 'Which law states that every action has an equal and opposite reaction?', 'mcq', NULL, 1, '2026-01-09 15:34:15'),
(8, 2, 'Which device is used to measure electric current?', 'mcq', NULL, 1, '2026-01-09 15:34:15'),
(9, 2, 'What is the energy of a moving object called?', 'mcq', NULL, 1, '2026-01-09 15:34:15'),
(10, 2, 'What is the SI unit of power?', 'mcq', NULL, 1, '2026-01-09 15:34:15'),
(11, 2, 'Which wave does not require a medium to travel?', 'mcq', NULL, 1, '2026-01-09 15:34:15'),
(12, 2, 'What does an object do if the net force on it is zero?', 'mcq', NULL, 1, '2026-01-09 15:34:15'),
(13, 2, 'What is the unit of electrical resistance?', 'mcq', NULL, 1, '2026-01-09 15:34:15'),
(14, 2, 'The SI unit of energy is the _____.', 'fill', 'Joule', 1, '2026-01-09 15:34:15'),
(15, 2, 'Velocity is displacement divided by _____.', 'fill', 'time', 1, '2026-01-09 15:34:15'),
(16, 2, 'The force that opposes motion between surfaces is called _____.', 'fill', 'friction', 1, '2026-01-09 15:34:15'),
(17, 2, 'The device used to measure temperature is a _____.', 'fill', 'thermometer', 1, '2026-01-09 15:34:15'),
(18, 2, 'The rate of change of velocity is called _____.', 'fill', 'acceleration', 1, '2026-01-09 15:34:15'),
(19, 2, 'The SI unit of electric current is the _____.', 'fill', 'ampere', 1, '2026-01-09 15:34:15'),
(20, 2, 'The path followed by a projectile is called a _____.', 'fill', 'parabola', 1, '2026-01-09 15:34:15'),
(21, 2, 'The energy stored in a stretched spring is _____ energy.', 'fill', 'elastic potential', 1, '2026-01-09 15:34:15'),
(23, 2, 'Work equals force times _____.', 'fill', 'displacement', 1, '2026-01-09 15:34:15');

-- --------------------------------------------------------

--
-- Table structure for table `question_options`
--

CREATE TABLE `question_options` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `option_text` varchar(255) NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `question_options`
--

INSERT INTO `question_options` (`id`, `question_id`, `option_text`, `is_correct`) VALUES
(1, 1, 'Blue', 1),
(2, 1, 'Green', 0),
(3, 1, 'Red', 0),
(4, 1, 'Yellow', 0),
(5, 4, 'Newton', 1),
(6, 4, 'Joule', 0),
(7, 4, 'Watt', 0),
(8, 4, 'Pascal', 0),
(9, 5, 'Speed', 0),
(10, 5, 'Velocity', 1),
(11, 5, 'Mass', 0),
(12, 5, 'Energy', 0),
(13, 6, '9.8 m/s^2', 1),
(14, 6, '3.0 m/s^2', 0),
(15, 6, '1.6 m/s^2', 0),
(16, 6, '98 m/s^2', 0),
(17, 7, 'Newton\'s First Law', 0),
(18, 7, 'Newton\'s Second Law', 0),
(19, 7, 'Newton\'s Third Law', 1),
(20, 7, 'Law of Gravitation', 0),
(21, 8, 'Voltmeter', 0),
(22, 8, 'Ammeter', 1),
(23, 8, 'Barometer', 0),
(24, 8, 'Thermometer', 0),
(25, 9, 'Potential energy', 0),
(26, 9, 'Kinetic energy', 1),
(27, 9, 'Thermal energy', 0),
(28, 9, 'Chemical energy', 0),
(29, 10, 'Joule', 0),
(30, 10, 'Newton', 0),
(31, 10, 'Watt', 1),
(32, 10, 'Coulomb', 0),
(33, 11, 'Sound wave', 0),
(34, 11, 'Water wave', 0),
(35, 11, 'Electromagnetic wave', 1),
(36, 11, 'Seismic wave', 0),
(37, 12, 'Accelerates', 0),
(38, 12, 'Changes direction', 0),
(39, 12, 'Remains at rest or moves at constant velocity', 1),
(40, 12, 'Stops immediately', 0),
(41, 13, 'Ohm', 1),
(42, 13, 'Volt', 0),
(43, 13, 'Ampere', 0),
(44, 13, 'Farad', 0);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `reg_no` varchar(60) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `class_id`, `full_name`, `reg_no`, `email`, `password_hash`, `status`, `created_at`) VALUES
(1, 1, 'Student One', 'CBT001', 'student@codexcbt.local', '$2y$10$tJar65QqAMYZSwCnKRB65em.2fw2GSSIoFBNd5nb9X.P.WcYgFkny', 'active', '2026-01-09 14:29:40'),
(2, 2, 'Ibeh', 'CBT002', 'ibeh@codexcbt.com', '$2y$10$tJar65QqAMYZSwCnKRB65em.2fw2GSSIoFBNd5nb9X.P.WcYgFkny', 'active', '2026-01-09 15:18:47');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `code` varchar(40) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `code`, `created_at`) VALUES
(1, 'General Knowledge', 'GK', '2026-01-09 14:29:40'),
(2, 'Physics', 'PHY', '2026-01-09 15:31:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_exams_subject` (`subject_id`);

--
-- Indexes for table `exam_answers`
--
ALTER TABLE `exam_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_answers_attempt` (`attempt_id`),
  ADD KEY `fk_answers_question` (`question_id`);

--
-- Indexes for table `exam_assignments`
--
ALTER TABLE `exam_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_exam_class` (`exam_id`,`class_id`),
  ADD KEY `fk_exam_assign_class` (`class_id`);

--
-- Indexes for table `exam_attempts`
--
ALTER TABLE `exam_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_attempts_exam` (`exam_id`),
  ADD KEY `fk_attempts_student` (`student_id`);

--
-- Indexes for table `exam_attempt_questions`
--
ALTER TABLE `exam_attempt_questions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_attempt_question` (`attempt_id`,`question_id`),
  ADD KEY `fk_attempt_questions_question` (`question_id`);

--
-- Indexes for table `exam_questions`
--
ALTER TABLE `exam_questions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_exam_question` (`exam_id`,`question_id`),
  ADD KEY `fk_exam_questions_question` (`question_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_questions_subject` (`subject_id`);

--
-- Indexes for table `question_options`
--
ALTER TABLE `question_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_options_question` (`question_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reg_no` (`reg_no`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_students_class` (`class_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `code` (`code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `exam_answers`
--
ALTER TABLE `exam_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `exam_assignments`
--
ALTER TABLE `exam_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `exam_attempts`
--
ALTER TABLE `exam_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `exam_attempt_questions`
--
ALTER TABLE `exam_attempt_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `exam_questions`
--
ALTER TABLE `exam_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `question_options`
--
ALTER TABLE `question_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `fk_exams_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`);

--
-- Constraints for table `exam_answers`
--
ALTER TABLE `exam_answers`
  ADD CONSTRAINT `fk_answers_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `exam_attempts` (`id`),
  ADD CONSTRAINT `fk_answers_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`);

--
-- Constraints for table `exam_assignments`
--
ALTER TABLE `exam_assignments`
  ADD CONSTRAINT `fk_exam_assign_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  ADD CONSTRAINT `fk_exam_assign_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`);

--
-- Constraints for table `exam_attempts`
--
ALTER TABLE `exam_attempts`
  ADD CONSTRAINT `fk_attempts_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`),
  ADD CONSTRAINT `fk_attempts_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`);

--
-- Constraints for table `exam_attempt_questions`
--
ALTER TABLE `exam_attempt_questions`
  ADD CONSTRAINT `fk_attempt_questions_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `exam_attempts` (`id`),
  ADD CONSTRAINT `fk_attempt_questions_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`);

--
-- Constraints for table `exam_questions`
--
ALTER TABLE `exam_questions`
  ADD CONSTRAINT `fk_exam_questions_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`),
  ADD CONSTRAINT `fk_exam_questions_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`);

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `fk_questions_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`);

--
-- Constraints for table `question_options`
--
ALTER TABLE `question_options`
  ADD CONSTRAINT `fk_options_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
