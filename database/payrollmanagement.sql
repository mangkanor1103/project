-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 25, 2025 at 07:30 AM
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
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','superadmin') NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `role`, `created_at`, `updated_at`) VALUES
(5, 'superadmin@gmail.com', '$2y$10$ZQ.088evUsprn/KVYNWV3eOVEUYrEj6CRrN5IrcUNGtAkE0k20Z.m', 'superadmin', '2025-04-19 05:49:36', '2025-04-20 06:09:46'),
(6, 'admin@gmail.com', '$2y$10$H9YfOITw1cIyfLCTVSMs8.x6USDuU9oVhWPrB99ODChynqnlj.7E6', 'admin', '2025-04-19 05:59:26', '2025-04-19 05:59:35'),
(7, 'admin1@gmail.com', '$2y$10$1E.V2MdbzSyEr3dwF8s2reUd9Cw1Rfn7t6yzdheQ7IMqqfYQn7M8C', 'admin', '2025-04-20 05:43:29', '2025-04-20 05:43:29');

-- --------------------------------------------------------

--
-- Table structure for table `admin_preferences`
--

CREATE TABLE `admin_preferences` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `dashboard_layout` varchar(20) DEFAULT 'cards',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_preferences`
--

INSERT INTO `admin_preferences` (`id`, `admin_id`, `dashboard_layout`, `created_at`, `updated_at`) VALUES
(1, 6, 'sidebar', '2025-04-24 01:29:51', '2025-04-24 01:30:02');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `created_at`) VALUES
(1, 'Behavioral analysis of adolescent’s students addicted to Facebook and its impact on performance and mental health', 'hradmin', '2025-04-20 09:10:25');

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
  `hours_worked` float DEFAULT NULL,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `night_hours` decimal(5,2) DEFAULT 0.00,
  `night_overtime_hours` decimal(5,2) DEFAULT 0.00,
  `holiday_hours` decimal(5,2) DEFAULT 0.00,
  `restday_hours` decimal(5,2) DEFAULT 0.00,
  `special_holiday_hours` decimal(5,2) DEFAULT 0.00,
  `legal_holiday_hours` decimal(5,2) DEFAULT 0.00,
  `is_absent` tinyint(1) DEFAULT 0,
  `is_holiday` tinyint(1) DEFAULT 0,
  `is_special_event` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_id`, `date`, `time_in`, `time_out`, `hours_worked`, `overtime_hours`, `night_hours`, `night_overtime_hours`, `holiday_hours`, `restday_hours`, `special_holiday_hours`, `legal_holiday_hours`, `is_absent`, `is_holiday`, `is_special_event`) VALUES
(1, 8, '2025-04-19', '03:10:16', '03:10:20', NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0),
(2, 8, '2025-04-20', '02:07:52', '02:08:54', 0.02, 0.00, 0.02, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0),
(3, 10, '2025-04-20', '02:13:06', '02:52:08', 0.650556, 0.00, 0.65, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0),
(4, 11, '2025-04-20', '03:00:18', '03:04:01', 0.0619444, 0.00, 0.06, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0),
(5, 8, '2025-04-21', '11:51:40', NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 1),
(6, 11, '2025-04-21', '12:21:17', '12:24:22', 0.0513889, 0.00, 0.05, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 1),
(7, 7, '2025-04-21', NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, 0, 0),
(8, 9, '2025-04-21', NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, 0, 0);

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
-- Table structure for table `calendar_events`
--

CREATE TABLE `calendar_events` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_type` enum('Holiday','Event') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `calendar_events`
--

INSERT INTO `calendar_events` (`id`, `date`, `event_name`, `event_type`, `created_at`) VALUES
(1, '2025-04-21', 'birthdayko', 'Event', '2025-04-20 01:16:24');

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
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `description`, `manager_id`, `created_at`) VALUES
(2, 'Marketing', '', 13, '2025-04-25 04:32:22'),
(3, 'Sales', '', 11, '2025-04-25 04:32:38');

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
(3, 'Kian A. rodriguez', '2025-05-02', 'Male', '09234567654', 'rheamelchor@gmail.com', 'egegw', '', 'grgr', 'grdgrr', 'Regular', '2025-04-19', 'dgrg', '353', '3535', '343', '3543', 'Married', 'Fixed', 20500.00, 1, '43', '3543', '35345', '2025-04-16 14:39:04', ''),
(4, 'Kian A. rodriguez', '2025-04-09', 'Male', '09234567654', 'rheamelchor@gmail.com', 'egegw', '488600066_1207743820874183_7935915282642254921_n.jpg', 'grgr', 'grdgrr', 'Regular', '2025-04-08', 'dgrg', '353', '3535', '343', '3543', 'Single', 'Fixed', 44.00, 1, '43', '3543', '35345', '2025-04-18 23:01:30', '$2y$10$Rtr74GKAe6J0Y2KayYAmd.TUL3V2DvYOZr7o0Sfai4ftCHWsfe04i'),
(5, 'Kian A. rodriguez1', '2025-04-17', 'Male', '09234567654', 'rheamelchor@gmail.com', 'Sagana, Bongabong, Oriental Mindoro', 'a39db249-1746-4664-8946-98d3b2a4e397.jfif', 'grgr', 'grdgrr', 'Regular', '2025-04-10', 'dgrg', '353', '3535', '343', '3543', 'Divorced', 'Fixed', 44.00, 0, '43', '3543', '35345', '2025-04-18 23:16:00', '$2y$10$wU/z2NSskO1gu81swDAxbecWIJqCpTwlXyjjhIjhCTnOlzuJsS9C.'),
(6, 'Kian A. rodriguez1', '2025-04-17', 'Male', '09234567654', 'rheamelchor@gmail.com', 'Sagana, Bongabong, Oriental Mindoro', 'a39db249-1746-4664-8946-98d3b2a4e397.jfif', 'grgr', 'grdgrr', 'Regular', '2025-04-10', 'dgrg', '353', '3535', '343', '3543', 'Divorced', 'Fixed', 44.00, 0, '43', '3543', '35345', '2025-04-18 23:16:09', '$2y$10$f/qmTo5aM3hvmFz3s2iyYuQ6rUh6LF.4FT4BB06vevdZ1Ghc9Te06'),
(7, 'Kian A. rodriguez1', '2025-04-17', 'Male', '09234567654', 'rheamelchor@gmail.com', 'Sagana, Bongabong, Oriental Mindoro', 'a39db249-1746-4664-8946-98d3b2a4e397.jfif', 'grgr', 'hr', 'Regular', '2025-04-10', 'dgrg', '353', '3535', '343', '3543', 'Divorced', 'Fixed', 44.00, 0, '43', '3543', '35345', '2025-04-18 23:17:25', '$2y$10$wJI5.P.8eAI3S1k2xx2Jg.GYZIwZ9KiVCCAEKOT39hsiIZA1zJGe2'),
(8, 'Kian A. rodriguez1', '2025-04-08', 'Male', '09234567654', 'rheamelchor1@gmail.com', 'House of the Family', 'Bunga at Pagkilig sa Araw.png', 'grgr', 'hr', 'Regular', '2025-04-16', 'dgrg', '353', '3535', '343', '3543', 'Married', 'Fixed', 20500.00, 0, '43', '3543', '35345', '2025-04-18 23:29:10', '$2y$10$dE9WWPqojlW1sdb9asQlouWABPlNBaysnHtvdSvtHJ3sr9Fl.48Ky'),
(9, 'Kian A. rodriguez1', '2025-04-08', 'Male', '09234567654', 'rheamelchor1@gmail.com', 'House of the Family', 'Bunga at Pagkilig sa Araw.png', 'grgr', 'hr', 'Regular', '2025-04-16', 'dgrg', '353', '3535', '343', '3543', 'Married', 'Fixed', 20500.00, 0, '43', '3543', '35345', '2025-04-18 23:29:18', '$2y$10$ithgkMhmMFfLRNbfFzM0nOJ9bGfT326IVlDkMMiAgyg/FtWx6te1K'),
(10, 'Rhea M. Melchor', '2025-04-15', 'Female', '09234567654', 'rheamelchor2@gmail.com', 'Sagana, Bongabong, Oriental Mindoro', 'ChatGPT Image Apr 7, 2025, 08_16_06 PM.png', 'grgr', 'grdgrr', 'Regular', '2025-04-16', 'dgrg', '353', '3535', '343', '3543', 'Married', 'Fixed', 20500.00, 1, 'Rhea M. Melchor', '3543', '09234567654', '2025-04-20 00:12:26', '$2y$10$T6zN7.1Z.7PJ/g1NnC/gqOJ2MPlsLV98Uf8OZ0qNveIK0..vqe8zC'),
(11, 'Rhea M. Melchor', '2025-05-07', 'Female', '09234567654', 'rheamelchor3@gmail.com', 'Sagana, Bongabong, Oriental Mindoro', '488600066_1207743820874183_7935915282642254921_n.jpg', 'Manager', 'Sales', 'Regular', '2025-04-21', 'dgrg', '353', '3535', '343', '3543', 'Married', 'Fixed', 20500.00, 1, 'Rhea M. Melchor', '3543', '09234567654', '2025-04-20 00:59:37', '$2y$10$eY7MopnTiZy7FsppJWu3QeEm7LiE3o9TEjho8nfPyqGTu1Wzx64Y.'),
(13, 'Joruel Calingasan', '2002-01-04', 'Male', '9171234567', 'joruel@gmail.com', '1234 Mango St., Makati City, Philippines', NULL, 'Director', 'Marketing', 'Regular', '2025-01-05', 'dgrg', '353', '3535', '343', '4354', 'Single', 'Fixed', 20500.00, 1, 'Winlyn', 'Wife', '2134324', '2025-04-23 14:10:23', '$2y$10$qpAGKMs35Ow58iEktbs8Nuxfpo107.YLH7DX6nwEJ4J7b8WceLLpy');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `expense_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `receipt_file` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Reimbursed') NOT NULL DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_date` datetime DEFAULT NULL,
  `reimbursed_date` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `employee_id`, `expense_date`, `amount`, `expense_type`, `description`, `receipt_file`, `status`, `approved_by`, `approved_date`, `reimbursed_date`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(1, 8, '2025-04-10', 23.00, 'Office Supplies', '3r2er23', 'receipt_680644446e090_20250421.png', 'Approved', 6, '2025-04-21 21:38:10', NULL, 'gdrrd', '2025-04-21 21:12:36', '2025-04-21 21:38:10');

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
-- Table structure for table `job_positions`
--

CREATE TABLE `job_positions` (
  `id` int(11) NOT NULL,
  `position_name` varchar(100) NOT NULL,
  `position_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_positions`
--

INSERT INTO `job_positions` (`id`, `position_name`, `position_description`, `created_at`, `updated_at`) VALUES
(1, 'Staff', 'Entry-level employee position', '2025-04-25 03:57:10', '2025-04-25 03:57:10'),
(2, 'Team Lead', 'Leads a small team of staff members', '2025-04-25 03:57:10', '2025-04-25 03:57:10'),
(3, 'Supervisor', 'Oversees operations and staff in a department', '2025-04-25 03:57:10', '2025-04-25 03:57:10'),
(4, 'Manager', 'Manages department operations and personnel', '2025-04-25 03:57:10', '2025-04-25 03:57:10'),
(5, 'Senior Manager', 'Oversees multiple managers or complex departments', '2025-04-25 03:57:10', '2025-04-25 03:57:10'),
(6, 'Director', 'Directs overall strategy for a business unit', '2025-04-25 03:57:10', '2025-04-25 03:57:10'),
(7, 'Executive', 'Senior leadership role with company-wide authority', '2025-04-25 03:57:10', '2025-04-25 03:57:10');

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
(15, 8, 'Sick Leave', '2025-04-26', '2025-04-27', 'ewgwgw', 'Approved', '2025-04-19 01:44:21');

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
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `admin_preferences`
--
ALTER TABLE `admin_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_id` (`admin_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_date` (`employee_id`,`date`),
  ADD KEY `idx_date` (`date`);

--
-- Indexes for table `bonuses`
--
ALTER TABLE `bonuses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deductions`
--
ALTER TABLE `deductions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_department` (`department`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_positions`
--
ALTER TABLE `job_positions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `position_name` (`position_name`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_dates` (`employee_id`,`start_date`,`end_date`),
  ADD KEY `idx_status` (`status`);

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
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `admin_preferences`
--
ALTER TABLE `admin_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `bonuses`
--
ALTER TABLE `bonuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `deductions`
--
ALTER TABLE `deductions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `job_positions`
--
ALTER TABLE `job_positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

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
