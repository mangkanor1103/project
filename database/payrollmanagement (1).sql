-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 19, 2025 at 01:34 AM
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
-- Database: `payrollmanagement`
--

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `dob` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `home_address` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `job_position` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `employee_type` enum('Regular','Probationary','Contractual') NOT NULL,
  `date_hired` date NOT NULL,
  `work_schedule` varchar(255) NOT NULL,
  `sss_number` varchar(50) NOT NULL,
  `philhealth_number` varchar(50) NOT NULL,
  `pagibig_number` varchar(50) NOT NULL,
  `tin` varchar(50) NOT NULL,
  `status` enum('Single','Married','Widowed','Divorced') NOT NULL,
  `salary_type` enum('Fixed','Hourly','Commission') NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `overtime_bonus` tinyint(1) DEFAULT 0,
  `emergency_name` varchar(255) NOT NULL,
  `emergency_relationship` varchar(100) NOT NULL,
  `emergency_contact` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `full_name`, `dob`, `gender`, `contact_number`, `email`, `home_address`, `image`, `job_position`, `department`, `employee_type`, `date_hired`, `work_schedule`, `sss_number`, `philhealth_number`, `pagibig_number`, `tin`, `status`, `salary_type`, `basic_salary`, `overtime_bonus`, `emergency_name`, `emergency_relationship`, `emergency_contact`, `created_at`, `password`) VALUES
(1, 'Rhea M. Melchor', '2025-04-03', 'Male', '09234567654', 'rheamelchor@gmail.com', 'San Vicente', '', 'grgr', 'grdgrr', 'Regular', '2025-05-01', 'dgrg', '353', '3535', '343', '3543', 'Single', 'Fixed', 44.00, 1, '43', '3543', '35345', '2025-04-16 14:22:33', ''),
(2, 'Rhea M. Melchor', '2025-04-03', 'Male', '09234567654', 'rheamelchor@gmail.com', 'San Vicente', '', 'grgr', 'grdgrr', 'Regular', '2025-05-01', 'dgrg', '353', '3535', '343', '3543', 'Single', 'Fixed', 44.00, 1, '43', '3543', '35345', '2025-04-16 14:24:15', ''),
(3, 'Kian A. rodriguez', '2025-05-02', 'Male', '09234567654', 'rheamelchor@gmail.com', 'egegw', '', 'grgr', 'grdgrr', 'Regular', '2025-04-19', 'dgrg', '353', '3535', '343', '3543', 'Married', 'Fixed', 44.00, 1, '43', '3543', '35345', '2025-04-16 14:39:04', ''),
(4, 'Kian A. rodriguez', '2025-04-09', 'Male', '09234567654', 'rheamelchor@gmail.com', 'egegw', '488600066_1207743820874183_7935915282642254921_n.jpg', 'grgr', 'grdgrr', 'Regular', '2025-04-08', 'dgrg', '353', '3535', '343', '3543', 'Single', 'Fixed', 44.00, 1, '43', '3543', '35345', '2025-04-18 23:01:30', '$2y$10$Rtr74GKAe6J0Y2KayYAmd.TUL3V2DvYOZr7o0Sfai4ftCHWsfe04i'),
(5, 'Kian A. rodriguez1', '2025-04-17', 'Male', '09234567654', 'rheamelchor@gmail.com', 'Sagana, Bongabong, Oriental Mindoro', 'a39db249-1746-4664-8946-98d3b2a4e397.jfif', 'grgr', 'grdgrr', 'Regular', '2025-04-10', 'dgrg', '353', '3535', '343', '3543', 'Divorced', 'Fixed', 44.00, 0, '43', '3543', '35345', '2025-04-18 23:16:00', '$2y$10$wU/z2NSskO1gu81swDAxbecWIJqCpTwlXyjjhIjhCTnOlzuJsS9C.'),
(6, 'Kian A. rodriguez1', '2025-04-17', 'Male', '09234567654', 'rheamelchor@gmail.com', 'Sagana, Bongabong, Oriental Mindoro', 'a39db249-1746-4664-8946-98d3b2a4e397.jfif', 'grgr', 'grdgrr', 'Regular', '2025-04-10', 'dgrg', '353', '3535', '343', '3543', 'Divorced', 'Fixed', 44.00, 0, '43', '3543', '35345', '2025-04-18 23:16:09', '$2y$10$f/qmTo5aM3hvmFz3s2iyYuQ6rUh6LF.4FT4BB06vevdZ1Ghc9Te06'),
(7, 'Kian A. rodriguez1', '2025-04-17', 'Male', '09234567654', 'rheamelchor@gmail.com', 'Sagana, Bongabong, Oriental Mindoro', 'a39db249-1746-4664-8946-98d3b2a4e397.jfif', 'grgr', 'grdgrr', 'Regular', '2025-04-10', 'dgrg', '353', '3535', '343', '3543', 'Divorced', 'Fixed', 44.00, 0, '43', '3543', '35345', '2025-04-18 23:17:25', '$2y$10$wJI5.P.8eAI3S1k2xx2Jg.GYZIwZ9KiVCCAEKOT39hsiIZA1zJGe2'),
(8, 'Kian A. rodriguez1', '2025-04-08', 'Male', '09234567654', 'rheamelchor1@gmail.com', 'House of the Family', 'Bunga at Pagkilig sa Araw.png', 'grgr', 'grdgrr', 'Regular', '2025-04-16', 'dgrg', '353', '3535', '343', '3543', 'Married', 'Fixed', 44.00, 0, '43', '3543', '35345', '2025-04-18 23:29:10', '$2y$10$Gcxb.oQ7nehv5QCGx3Nsk.YLBKVTbge3287.SrUrPKebjX64LEED.'),
(9, 'Kian A. rodriguez1', '2025-04-08', 'Male', '09234567654', 'rheamelchor1@gmail.com', 'House of the Family', 'Bunga at Pagkilig sa Araw.png', 'grgr', 'grdgrr', 'Regular', '2025-04-16', 'dgrg', '353', '3535', '343', '3543', 'Married', 'Fixed', 44.00, 0, '43', '3543', '35345', '2025-04-18 23:29:18', '$2y$10$ithgkMhmMFfLRNbfFzM0nOJ9bGfT326IVlDkMMiAgyg/FtWx6te1K');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `name`, `email`, `message`, `created_at`) VALUES
(1, '43', 'rheamelchor@gmail.com', 'dfdd', '2025-04-16 14:52:51'),
(2, '43', 'rheamelchor@gmail.com', 'dfdd', '2025-04-16 14:53:04'),
(3, '43', 'rheamelchor@gmail.com', 'dfdd', '2025-04-16 14:54:41'),
(4, 'gege', 'rheamelchor@gmail.com', 'gerge', '2025-04-16 14:55:06');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
