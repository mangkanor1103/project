-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 19, 2025 at 03:49 AM
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
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `hours_worked` decimal(5,2) DEFAULT NULL,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `is_absent` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_id`, `date`, `time_in`, `time_out`, `hours_worked`, `overtime_hours`, `is_absent`) VALUES
(1, 8, '2025-04-19', '03:10:16', '03:10:20', NULL, 0.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `bonuses`
--

CREATE TABLE `bonuses` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `bonus_type` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deductions`
--

CREATE TABLE `deductions` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `deduction_type` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(8, 'Kian A. rodriguez1', '2025-04-08', 'Male', '09234567654', 'rheamelchor1@gmail.com', 'House of the Family', 'Bunga at Pagkilig sa Araw.png', 'grgr', 'grdgrr', 'Regular', '2025-04-16', 'dgrg', '353', '3535', '343', '3543', 'Married', 'Fixed', 44.00, 0, '43', '3543', '35345', '2025-04-18 23:29:10', '$2y$10$dE9WWPqojlW1sdb9asQlouWABPlNBaysnHtvdSvtHJ3sr9Fl.48Ky'),
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

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `employee_id`, `leave_type`, `start_date`, `end_date`, `reason`, `status`, `created_at`) VALUES
(1, 8, 'Sick Leave', '2025-04-10', '2025-04-26', 'hthfh', 'Approved', '2025-04-19 01:19:14'),
(2, 8, 'Sick Leave', '2025-04-10', '2025-04-26', 'hthfh', 'Rejected', '2025-04-19 01:21:47'),
(3, 8, 'Sick Leave', '2025-04-10', '2025-04-26', 'hthfh', 'Rejected', '2025-04-19 01:21:51'),
(4, 8, 'sad', '2025-04-25', '2025-04-26', 'wdaw', 'Rejected', '2025-04-19 01:22:14'),
(5, 8, 'sad', '2025-04-25', '2025-04-26', 'wdaw', 'Rejected', '2025-04-19 01:23:27'),
(6, 8, 'gdrgd', '2025-04-20', '2025-04-21', 'drgdgdg', 'Rejected', '2025-04-19 01:23:45'),
(7, 8, 'csacc', '2025-04-27', '2025-04-28', 'scacsacac', 'Rejected', '2025-04-19 01:24:50'),
(8, 8, 'csacc', '2025-04-27', '2025-04-28', 'scacsacac', 'Rejected', '2025-04-19 01:25:49'),
(9, 8, 'csacc', '2025-04-27', '2025-04-28', 'scacsacac', 'Rejected', '2025-04-19 01:25:52'),
(10, 8, 'yryr', '2025-04-20', '2025-04-21', '5ydyd', 'Rejected', '2025-04-19 01:26:04'),
(11, 8, 'Vacation Leave', '2025-04-09', '2025-05-02', 'cssc', 'Rejected', '2025-04-19 01:27:29'),
(12, 8, 'Vacation Leave', '2025-04-09', '2025-05-02', 'cssc', 'Rejected', '2025-04-19 01:29:03'),
(13, 8, 'Sick Leave', '2025-04-20', '2025-04-21', 'sdff', 'Rejected', '2025-04-19 01:29:23'),
(14, 8, 'Sick Leave', '2025-04-21', '2025-04-22', 'wdwf', 'Rejected', '2025-04-19 01:30:40'),
(15, 8, 'Sick Leave', '2025-04-26', '2025-04-27', 'ewgwgw', 'Pending', '2025-04-19 01:44:21');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `payroll_date` date NOT NULL,
  `basic_salary` decimal(10,2) DEFAULT NULL,
  `overtime_pay` decimal(10,2) DEFAULT NULL,
  `bonuses` decimal(10,2) DEFAULT 0.00,
  `deductions` decimal(10,2) DEFAULT 0.00,
  `net_salary` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `bonuses`
--
ALTER TABLE `bonuses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `deductions`
--
ALTER TABLE `deductions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

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
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bonuses`
--
ALTER TABLE `bonuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deductions`
--
ALTER TABLE `deductions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `bonuses`
--
ALTER TABLE `bonuses`
  ADD CONSTRAINT `bonuses_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `deductions`
--
ALTER TABLE `deductions`
  ADD CONSTRAINT `deductions_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
