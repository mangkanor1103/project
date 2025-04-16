-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 17, 2025 at 01:00 AM
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `full_name`, `dob`, `gender`, `contact_number`, `email`, `home_address`, `image`, `job_position`, `department`, `employee_type`, `date_hired`, `work_schedule`, `sss_number`, `philhealth_number`, `pagibig_number`, `tin`, `status`, `salary_type`, `basic_salary`, `overtime_bonus`, `emergency_name`, `emergency_relationship`, `emergency_contact`, `created_at`) VALUES
(1, 'Rhea M. Melchor', '2025-04-03', 'Male', '09234567654', 'rheamelchor@gmail.com', 'San Vicente', '', 'grgr', 'grdgrr', 'Regular', '2025-05-01', 'dgrg', '353', '3535', '343', '3543', 'Single', 'Fixed', 44.00, 1, '43', '3543', '35345', '2025-04-16 14:22:33'),
(2, 'Rhea M. Melchor', '2025-04-03', 'Male', '09234567654', 'rheamelchor@gmail.com', 'San Vicente', '', 'grgr', 'grdgrr', 'Regular', '2025-05-01', 'dgrg', '353', '3535', '343', '3543', 'Single', 'Fixed', 44.00, 1, '43', '3543', '35345', '2025-04-16 14:24:15'),
(3, 'Kian A. rodriguez', '2025-05-02', 'Male', '09234567654', 'rheamelchor@gmail.com', 'egegw', '', 'grgr', 'grdgrr', 'Regular', '2025-04-19', 'dgrg', '353', '3535', '343', '3543', 'Married', 'Fixed', 44.00, 1, '43', '3543', '35345', '2025-04-16 14:39:04');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
