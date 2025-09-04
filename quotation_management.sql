-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 04, 2025 at 12:13 PM
-- Server version: 10.11.11-MariaDB
-- PHP Version: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `quotation_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `company_info`
--

CREATE TABLE `company_info` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL DEFAULT 'PHARMA MACHINERY COMPANY',
  `tagline` varchar(255) DEFAULT 'TURNKEY PROJECT EXPERT',
  `corporate_office` text DEFAULT NULL,
  `manufacturing_unit` text DEFAULT NULL,
  `cin` varchar(21) DEFAULT NULL,
  `gst` varchar(15) DEFAULT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT 'sales@pharmamachinery.com',
  `website` varchar(100) DEFAULT 'www.pharmamachinery.com',
  `logo_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `company_info`
--

INSERT INTO `company_info` (`id`, `company_name`, `tagline`, `corporate_office`, `manufacturing_unit`, `cin`, `gst`, `contact`, `email`, `website`, `logo_path`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'PHARMA MACHINERY COMPANY', 'TURNKEY PROJECT EXPERT', 'Plot No. 9, Sector 22, IT Park, Panchkula, Haryana – 134109, India', '14/1/B, Shed No. 222/1, Pancharatna Industrial Estate, Ramol Bridge, Phase IV, VATVA GIDC, Ahmedabad, Gujarat – 382445', 'U29100HR2020PTC088944', '06AAACX3387L1ZW', '+91 956023966', 'sales@pharmamachinery.com', 'www.pharmamachinery.com', NULL, 1, '2025-08-28 12:31:21', '2025-08-28 12:31:21');

-- --------------------------------------------------------

--
-- Table structure for table `credit_notes`
--

CREATE TABLE `credit_notes` (
  `id` int(11) NOT NULL,
  `credit_note_number` varchar(50) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_address` text DEFAULT NULL,
  `customer_gstin` varchar(15) DEFAULT NULL,
  `original_invoice` varchar(50) DEFAULT NULL,
  `credit_date` date NOT NULL,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reason` text DEFAULT NULL,
  `status` enum('draft','issued','applied','cancelled') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `credit_notes`
--

INSERT INTO `credit_notes` (`id`, `credit_note_number`, `customer_id`, `customer_name`, `customer_address`, `customer_gstin`, `original_invoice`, `credit_date`, `total_amount`, `reason`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'CN-2025-ABC-3763', 6, 'ABC Chemical Suppliers Pvt Ltd', '123 Industrial Area, Chemical Park', '27ABCCS1234A1Z5', 'ASDAS', '2025-08-31', 10000.00, 'ASDSD', 'draft', 1, '2025-08-31 11:47:11', '2025-09-04 01:15:45');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `entity_type` enum('customer','vendor','both') DEFAULT 'customer',
  `company_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `gst_no` varchar(15) DEFAULT NULL,
  `address` text NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `entity_type`, `company_name`, `contact_person`, `phone`, `email`, `gst_no`, `address`, `city`, `state`, `pincode`, `created_at`, `updated_at`) VALUES
(2, 'both', 'ABC Pharmaceuticals Ltd', 'Dr. Rajesh Kumar', '+91 9876543210', 'info@abcpharma.com', NULL, '683 SANJAY ENACLAVE', 'Mumbai', 'Delhi', '110059', '2025-08-28 12:31:21', '2025-09-01 06:00:16'),
(6, 'both', 'ABC Chemical Suppliers Pvt Ltd', 'Mr. Rajesh Kumar', '+91 9876543210', 'rajesh@abcchemicals.com', '27ABCCS1234A1Z5', '123 Industrial Area, Chemical Park', 'Mumbai', 'Maharashtra', '400001', '2025-08-30 16:01:38', '2025-08-31 10:14:18'),
(7, 'both', 'XYZ Pharmaceuticals Ltd', 'Dr. Priya Sharma', '+91 8765432109', 'priya@xyzpharma.com', '29XYZPL1234B2Z6', '456 Medical District, Pharma Hub', 'Bangalore', 'Karnataka', '560001', '2025-08-30 16:01:48', '2025-08-31 10:14:18');

-- --------------------------------------------------------

--
-- Table structure for table `debit_notes`
--

CREATE TABLE `debit_notes` (
  `id` int(11) NOT NULL,
  `debit_note_number` varchar(50) NOT NULL,
  `vendor_name` varchar(255) NOT NULL,
  `vendor_address` text DEFAULT NULL,
  `vendor_gstin` varchar(15) DEFAULT NULL,
  `original_invoice` varchar(50) DEFAULT NULL,
  `debit_date` date NOT NULL,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reason` text DEFAULT NULL,
  `status` enum('draft','issued','applied','cancelled') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `debit_notes`
--

INSERT INTO `debit_notes` (`id`, `debit_note_number`, `vendor_name`, `vendor_address`, `vendor_gstin`, `original_invoice`, `debit_date`, `total_amount`, `reason`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'DN-2025-ABC-9272', 'ABC Chemical Suppliers Pvt Ltd', '123 Industrial Area, Chemical Park', '27ABCCS1234A1Z5', 'ASDASD', '2025-08-31', 1000.00, 'ASDASD', 'draft', 1, '2025-08-31 11:55:10', '2025-08-31 11:55:10'),
(2, 'DN-2025-ABC-6426', 'ABC Chemical Suppliers Pvt Ltd', '123 Industrial Area, Chemical Park', '27ABCCS1234A1Z5', '11asdas', '2025-09-01', 1000.00, 'adsads', 'draft', 1, '2025-09-01 07:47:23', '2025-09-01 07:47:23'),
(3, 'DN-2025-ABC-7106', 'ABC Pharmaceuticals Ltd', '', '', '', '2025-09-04', 1000.00, 'asdads', 'draft', 1, '2025-09-04 01:20:04', '2025-09-04 01:25:30');

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `entity_type` enum('quotation','sales_order','purchase_order') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `recipient_emails` text NOT NULL,
  `subject` varchar(255) NOT NULL,
  `sent_by` int(11) NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `machines`
--

CREATE TABLE `machines` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `model` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `tech_specs` text DEFAULT NULL,
  `attachment_filename` varchar(255) DEFAULT NULL,
  `attachment_path` varchar(500) DEFAULT NULL,
  `attachment_size` int(11) DEFAULT NULL,
  `attachment_type` varchar(100) DEFAULT NULL,
  `part_code` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `machines`
--

INSERT INTO `machines` (`id`, `name`, `model`, `category`, `description`, `tech_specs`, `attachment_filename`, `attachment_path`, `attachment_size`, `attachment_type`, `part_code`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Rapid Mixer Granulator', 'RMG-500', 'Granulation Equipment', 'High-speed granulation equipment for pharmaceutical industry', 'Three-blade impeller, four-blade chopper, pneumatic top lid &amp;amp;amp;amp;amp;amp;amp;amp;amp; discharge, safety proxies, slow/fast speed, contact parts SS316, non-contact SS304, VFD for impeller &amp;amp;amp;amp;amp;amp;amp;amp;amp; chopper, manual push-button panel, amp meters, air purging housings. Capacity: 500 kg batch', 'RMG-500_Technical_Specification.pdf', 'uploads/machines/rmg_500_specs.pdf', 776, 'application/pdf', 'RMG500', 1, '2025-08-28 12:31:21', '2025-09-04 10:10:48'),
(2, 'Fluid Bed Processor (Top Spray)', 'FBD-500', 'Drying Equipment', 'Fluid bed dryer with top spray coating capability', 'Single piece construction, 2× product containers with trolleys, safe earth mechanism, auto bag up/down &amp; shaking, sampling port, SS316 contact / SS304 non-contact, double-skin AHU with pre+micro filters &amp; steam coil. Operating capacity: 300-400 kg', NULL, NULL, NULL, NULL, 'FBD500', 1, '2025-08-28 12:31:21', '2025-09-03 17:21:19'),
(3, 'Octagonal Blender', 'OCT-2000L', 'Blending Equipment', 'Octagonal blender for homogeneous mixing', 'SS316 contact / SS304 non-contact, internal mirror, external matt. Working volume ~1500 L (~750 kg @ BD 0.5), VFD-driven, 5-12 RPM, 10 HP motor, safety railing. Charging/discharge ports in SS316, 12″ butterfly valve', 'OCT-2000L_Technical_Specification.pdf', 'uploads/machines/oct_2000l_specs.pdf', 824, 'application/pdf', 'OCT2000', 1, '2025-08-28 12:31:21', '2025-09-03 15:03:34'),
(4, 'Vibro Sifter', 'VS-48', 'Sieving Equipment', '48 inch vibro sifter for particle separation', 'GMP design, smooth 180-grit external, 180+ internal finish, food-grade gaskets. Center-flange vibro motor (approx. 2 HP), 12 springs, nylon castors, silicon-moulded screen. Dimensions: Ø1220 mm', NULL, NULL, NULL, NULL, 'VS48', 1, '2025-08-28 12:31:21', '2025-08-28 12:31:21'),
(5, 'Colloid Mill', 'CM-500', 'Size Reduction', 'Colloid mill for particle size reduction', 'SS316 contact / SS304 non-contact, conical stator/rotor with fine gap adjustment, water-jacketed hopper. Vertical belt-driven rotor, 5 HP @ 2880 RPM, outputs ~150–1000 L/h, hopper ~15 L', NULL, NULL, NULL, NULL, 'CM500', 1, '2025-08-28 12:31:21', '2025-08-28 12:31:21'),
(12, 'ASDASDDSA', 'asdasdasdadsads', 'mac', 'asdasd', '', NULL, NULL, NULL, NULL, 'asdasd', 1, '2025-09-04 10:12:28', '2025-09-04 10:21:00');

-- --------------------------------------------------------

--
-- Table structure for table `machine_features`
--

CREATE TABLE `machine_features` (
  `id` int(11) NOT NULL,
  `machine_id` int(11) NOT NULL,
  `feature_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `machine_features`
--

INSERT INTO `machine_features` (`id`, `machine_id`, `feature_name`, `created_at`, `updated_at`) VALUES
(2, 12, 'shubhan', '2025-09-04 10:12:46', '2025-09-04 10:12:46'),
(3, 12, 'MACHIN', '2025-09-04 10:20:55', '2025-09-04 10:20:55'),
(4, 5, 'MACHI_CA', '2025-09-04 11:18:25', '2025-09-04 11:18:25');

-- --------------------------------------------------------

--
-- Table structure for table `machine_feature_prices`
--

CREATE TABLE `machine_feature_prices` (
  `id` int(11) NOT NULL,
  `machine_id` int(11) NOT NULL,
  `feature_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valid_from` date NOT NULL,
  `valid_to` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `price_master_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `machine_feature_prices`
--

INSERT INTO `machine_feature_prices` (`id`, `machine_id`, `feature_name`, `price`, `valid_from`, `valid_to`, `is_active`, `created_at`, `updated_at`, `price_master_id`) VALUES
(7, 5, 'MACHI_CA', 100.00, '2025-08-28', '2026-08-28', 1, '2025-09-04 11:32:19', '2025-09-04 11:32:19', 7),
(10, 12, 'MACHIN', 5205.00, '2025-09-04', '2026-09-04', 1, '2025-09-04 12:11:38', '2025-09-04 12:11:38', 11),
(11, 12, 'shubhan', 45440.00, '2025-09-04', '2026-09-04', 1, '2025-09-04 12:11:38', '2025-09-04 12:11:38', 11);

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `module` varchar(30) NOT NULL,
  `action` varchar(20) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `display_name`, `description`, `module`, `action`, `is_active`, `created_at`) VALUES
(1, 'dashboard.view', 'View Dashboard', 'Access to main dashboard', 'dashboard', 'view', 1, '2025-08-29 08:31:45'),
(2, 'customers.view', 'View Customers', 'View customer list and details', 'customers', 'view', 1, '2025-08-29 08:31:45'),
(3, 'customers.create', 'Create Customers', 'Add new customers', 'customers', 'create', 1, '2025-08-29 08:31:45'),
(4, 'customers.edit', 'Edit Customers', 'Modify existing customer information', 'customers', 'edit', 1, '2025-08-29 08:31:45'),
(5, 'customers.delete', 'Delete Customers', 'Remove customers from system', 'customers', 'delete', 1, '2025-08-29 08:31:45'),
(6, 'machines.view', 'View Machines', 'View machine list and details', 'machines', 'view', 1, '2025-08-29 08:31:45'),
(7, 'machines.create', 'Create Machines', 'Add new machines', 'machines', 'create', 1, '2025-08-29 08:31:45'),
(8, 'machines.edit', 'Edit Machines', 'Modify existing machine information', 'machines', 'edit', 1, '2025-08-29 08:31:45'),
(9, 'machines.delete', 'Delete Machines', 'Remove machines from system', 'machines', 'delete', 1, '2025-08-29 08:31:45'),
(10, 'spares.view', 'View Spares', 'View spare parts list and details', 'spares', 'view', 1, '2025-08-29 08:31:45'),
(11, 'spares.create', 'Create Spares', 'Add new spare parts', 'spares', 'create', 1, '2025-08-29 08:31:45'),
(12, 'spares.edit', 'Edit Spares', 'Modify existing spare part information', 'spares', 'edit', 1, '2025-08-29 08:31:45'),
(13, 'spares.delete', 'Delete Spares', 'Remove spare parts from system', 'spares', 'delete', 1, '2025-08-29 08:31:45'),
(14, 'price_master.view', 'View Price Master', 'View price master list and details', 'price_master', 'view', 1, '2025-08-29 08:31:45'),
(15, 'price_master.create', 'Create Price Master', 'Add new price entries', 'price_master', 'create', 1, '2025-08-29 08:31:45'),
(16, 'price_master.edit', 'Edit Price Master', 'Modify existing price entries', 'price_master', 'edit', 1, '2025-08-29 08:31:45'),
(17, 'price_master.delete', 'Delete Price Master', 'Remove price entries from system', 'price_master', 'delete', 1, '2025-08-29 08:31:45'),
(18, 'quotations.view', 'View Quotations', 'View quotation list and details', 'quotations', 'view', 1, '2025-08-29 08:31:45'),
(19, 'quotations.create', 'Create Quotations', 'Create new quotations', 'quotations', 'create', 1, '2025-08-29 08:31:45'),
(20, 'quotations.edit', 'Edit Quotations', 'Modify existing quotations', 'quotations', 'edit', 1, '2025-08-29 08:31:45'),
(21, 'quotations.delete', 'Delete Quotations', 'Remove quotations from system', 'quotations', 'delete', 1, '2025-08-29 08:31:45'),
(22, 'quotations.send', 'Send Quotations', 'Email quotations to customers', 'quotations', 'send', 1, '2025-08-29 08:31:45'),
(23, 'quotations.print', 'Print Quotations', 'Generate PDF quotations', 'quotations', 'print', 1, '2025-08-29 08:31:45'),
(24, 'reports.view', 'View Reports', 'Access to all reports', 'reports', 'view', 1, '2025-08-29 08:31:45'),
(25, 'reports.export', 'Export Reports', 'Export reports to various formats', 'reports', 'export', 1, '2025-08-29 08:31:45'),
(26, 'users.view', 'View Users', 'View user list and details', 'users', 'view', 1, '2025-08-29 08:31:45'),
(27, 'users.create', 'Create Users', 'Add new users to system', 'users', 'create', 1, '2025-08-29 08:31:45'),
(28, 'users.edit', 'Edit Users', 'Modify user information and permissions', 'users', 'edit', 1, '2025-08-29 08:31:45'),
(29, 'users.delete', 'Delete Users', 'Remove users from system', 'users', 'delete', 1, '2025-08-29 08:31:45'),
(30, 'users.assign_roles', 'Assign User Roles', 'Assign roles to users', 'users', 'assign_roles', 1, '2025-08-29 08:31:45'),
(31, 'settings.view', 'View Settings', 'View system settings', 'settings', 'view', 1, '2025-08-29 08:31:45'),
(32, 'settings.edit', 'Edit Settings', 'Modify system settings', 'settings', 'edit', 1, '2025-08-29 08:31:45'),
(33, 'system.settings', 'System Settings', 'Access system configuration', 'system', 'settings', 1, '2025-08-29 08:58:25'),
(34, 'system.backup', 'System Backup', 'Create system backups', 'system', 'backup', 1, '2025-08-29 08:58:25'),
(35, 'system.logs', 'System Logs', 'View system logs and audit trails', 'system', 'logs', 1, '2025-08-29 08:58:25'),
(67, 'sales_orders_view', 'View Sales Orders', 'View sales orders', 'sales_orders', 'view', 1, '2025-08-30 05:57:27'),
(68, 'sales_orders_create', 'Create Sales Orders', 'Create new sales orders', 'sales_orders', 'create', 1, '2025-08-30 05:57:27'),
(69, 'sales_orders_edit', 'Edit Sales Orders', 'Edit sales orders', 'sales_orders', 'edit', 1, '2025-08-30 05:57:27'),
(70, 'sales_orders_delete', 'Delete Sales Orders', 'Delete sales orders', 'sales_orders', 'delete', 1, '2025-08-30 05:57:27'),
(71, 'purchase_orders_view', 'View Purchase Orders', 'View purchase orders', 'purchase_orders', 'view', 1, '2025-08-30 05:57:27'),
(72, 'purchase_orders_create', 'Create Purchase Orders', 'Create new purchase orders', 'purchase_orders', 'create', 1, '2025-08-30 05:57:27'),
(73, 'purchase_orders_edit', 'Edit Purchase Orders', 'Edit purchase orders', 'purchase_orders', 'edit', 1, '2025-08-30 05:57:27'),
(74, 'purchase_orders_delete', 'Delete Purchase Orders', 'Delete purchase orders', 'purchase_orders', 'delete', 1, '2025-08-30 05:57:27'),
(75, 'sales_invoices_view', 'View Sales Invoices', 'View sales invoices', 'sales_invoices', 'view', 1, '2025-08-30 05:57:27'),
(76, 'sales_invoices_create', 'Create Sales Invoices', 'Create new sales invoices', 'sales_invoices', 'create', 1, '2025-08-30 05:57:27'),
(77, 'sales_invoices_edit', 'Edit Sales Invoices', 'Edit sales invoices', 'sales_invoices', 'edit', 1, '2025-08-30 05:57:27'),
(78, 'sales_invoices_delete', 'Delete Sales Invoices', 'Delete sales invoices', 'sales_invoices', 'delete', 1, '2025-08-30 05:57:27'),
(79, 'credit_notes_view', 'View Credit Notes', 'View credit notes', 'credit_notes', 'view', 1, '2025-08-30 05:57:27'),
(80, 'credit_notes_create', 'Create Credit Notes', 'Create new credit notes', 'credit_notes', 'create', 1, '2025-08-30 05:57:27'),
(81, 'credit_notes_edit', 'Edit Credit Notes', 'Edit credit notes', 'credit_notes', 'edit', 1, '2025-08-30 05:57:27'),
(82, 'credit_notes_delete', 'Delete Credit Notes', 'Delete credit notes', 'credit_notes', 'delete', 1, '2025-08-30 05:57:27'),
(83, 'debit_notes_view', 'View Debit Notes', 'View debit notes', 'debit_notes', 'view', 1, '2025-08-30 05:57:27'),
(84, 'debit_notes_create', 'Create Debit Notes', 'Create new debit notes', 'debit_notes', 'create', 1, '2025-08-30 05:57:27'),
(85, 'debit_notes_edit', 'Edit Debit Notes', 'Edit debit notes', 'debit_notes', 'edit', 1, '2025-08-30 05:57:27'),
(86, 'debit_notes_delete', 'Delete Debit Notes', 'Delete debit notes', 'debit_notes', 'delete', 1, '2025-08-30 05:57:27'),
(87, 'reports_customers.view', 'View Customer/Vendor Reports', 'View customer and vendor master reports', 'reports_customers', 'view', 1, '2025-09-01 09:01:54'),
(88, 'reports_customers.filter', 'Filter Customer/Vendor Reports', 'Apply filters to customer/vendor reports', 'reports_customers', 'filter', 1, '2025-09-01 09:01:54'),
(89, 'reports_customers.export', 'Export Customer/Vendor Reports', 'Export customer/vendor reports to PDF/Excel', 'reports_customers', 'export', 1, '2025-09-01 09:01:54'),
(90, 'reports_customers.print', 'Print Customer/Vendor Reports', 'Print customer/vendor reports', 'reports_customers', 'print', 1, '2025-09-01 09:01:54'),
(91, 'reports_machines.view', 'View Machine Reports', 'View machine master reports', 'reports_machines', 'view', 1, '2025-09-01 09:01:54'),
(92, 'reports_machines.filter', 'Filter Machine Reports', 'Apply filters to machine reports', 'reports_machines', 'filter', 1, '2025-09-01 09:01:54'),
(93, 'reports_machines.export', 'Export Machine Reports', 'Export machine reports to PDF/Excel', 'reports_machines', 'export', 1, '2025-09-01 09:01:54'),
(94, 'reports_machines.print', 'Print Machine Reports', 'Print machine reports', 'reports_machines', 'print', 1, '2025-09-01 09:01:54'),
(95, 'reports_spares.view', 'View Spare Parts Reports', 'View spare parts master reports', 'reports_spares', 'view', 1, '2025-09-01 09:01:54'),
(96, 'reports_spares.filter', 'Filter Spare Parts Reports', 'Apply filters to spare parts reports', 'reports_spares', 'filter', 1, '2025-09-01 09:01:54'),
(97, 'reports_spares.export', 'Export Spare Parts Reports', 'Export spare parts reports to PDF/Excel', 'reports_spares', 'export', 1, '2025-09-01 09:01:54'),
(98, 'reports_spares.print', 'Print Spare Parts Reports', 'Print spare parts reports', 'reports_spares', 'print', 1, '2025-09-01 09:01:54'),
(99, 'reports_price.view', 'View Price Master Reports', 'View price master reports', 'reports_price', 'view', 1, '2025-09-01 09:01:54'),
(100, 'reports_price.filter', 'Filter Price Master Reports', 'Apply filters to price master reports', 'reports_price', 'filter', 1, '2025-09-01 09:01:54'),
(101, 'reports_price.export', 'Export Price Master Reports', 'Export price master reports to PDF/Excel', 'reports_price', 'export', 1, '2025-09-01 09:01:54'),
(102, 'reports_price.print', 'Print Price Master Reports', 'Print price master reports', 'reports_price', 'print', 1, '2025-09-01 09:01:54'),
(103, 'reports_quotations.view', 'View Quotation Reports', 'View quotation transaction reports', 'reports_quotations', 'view', 1, '2025-09-01 09:01:54'),
(104, 'reports_quotations.filter', 'Filter Quotation Reports', 'Apply date/customer/machine filters to quotation reports', 'reports_quotations', 'filter', 1, '2025-09-01 09:01:54'),
(105, 'reports_quotations.export', 'Export Quotation Reports', 'Export quotation reports to PDF/Excel', 'reports_quotations', 'export', 1, '2025-09-01 09:01:54'),
(106, 'reports_quotations.print', 'Print Quotation Reports', 'Print quotation reports', 'reports_quotations', 'print', 1, '2025-09-01 09:01:54'),
(107, 'reports_quotations.search', 'Search Quotation Reports', 'Search quotations by number/customer/machine', 'reports_quotations', 'search', 1, '2025-09-01 09:01:54'),
(108, 'reports_sales_orders.view', 'View Sales Order Reports', 'View sales order transaction reports', 'reports_sales_orders', 'view', 1, '2025-09-01 09:01:54'),
(109, 'reports_sales_orders.filter', 'Filter Sales Order Reports', 'Apply date/customer/machine filters to SO reports', 'reports_sales_orders', 'filter', 1, '2025-09-01 09:01:54'),
(110, 'reports_sales_orders.export', 'Export Sales Order Reports', 'Export sales order reports to PDF/Excel', 'reports_sales_orders', 'export', 1, '2025-09-01 09:01:54'),
(111, 'reports_sales_orders.print', 'Print Sales Order Reports', 'Print sales order reports', 'reports_sales_orders', 'print', 1, '2025-09-01 09:01:54'),
(112, 'reports_sales_orders.search', 'Search Sales Order Reports', 'Search sales orders by number/customer/machine', 'reports_sales_orders', 'search', 1, '2025-09-01 09:01:54'),
(113, 'reports_purchase_orders.view', 'View Purchase Order Reports', 'View purchase order transaction reports', 'reports_purchase_orders', 'view', 1, '2025-09-01 09:01:54'),
(114, 'reports_purchase_orders.filter', 'Filter Purchase Order Reports', 'Apply date/vendor/machine filters to PO reports', 'reports_purchase_orders', 'filter', 1, '2025-09-01 09:01:54'),
(115, 'reports_purchase_orders.export', 'Export Purchase Order Reports', 'Export purchase order reports to PDF/Excel', 'reports_purchase_orders', 'export', 1, '2025-09-01 09:01:54'),
(116, 'reports_purchase_orders.print', 'Print Purchase Order Reports', 'Print purchase order reports', 'reports_purchase_orders', 'print', 1, '2025-09-01 09:01:54'),
(117, 'reports_purchase_orders.search', 'Search Purchase Order Reports', 'Search purchase orders by number/vendor/machine', 'reports_purchase_orders', 'search', 1, '2025-09-01 09:01:54'),
(118, 'reports_sales_invoices.view', 'View Sales Invoice Reports', 'View sales invoice transaction reports', 'reports_sales_invoices', 'view', 1, '2025-09-01 09:01:54'),
(119, 'reports_sales_invoices.filter', 'Filter Sales Invoice Reports', 'Apply date/customer/machine filters to SI reports', 'reports_sales_invoices', 'filter', 1, '2025-09-01 09:01:54'),
(120, 'reports_sales_invoices.export', 'Export Sales Invoice Reports', 'Export sales invoice reports to PDF/Excel', 'reports_sales_invoices', 'export', 1, '2025-09-01 09:01:54'),
(121, 'reports_sales_invoices.print', 'Print Sales Invoice Reports', 'Print sales invoice reports', 'reports_sales_invoices', 'print', 1, '2025-09-01 09:01:54'),
(122, 'reports_sales_invoices.search', 'Search Sales Invoice Reports', 'Search sales invoices by number/customer/machine', 'reports_sales_invoices', 'search', 1, '2025-09-01 09:01:54'),
(123, 'reports_credit_notes.view', 'View Credit Note Reports', 'View credit note transaction reports', 'reports_credit_notes', 'view', 1, '2025-09-01 09:01:54'),
(124, 'reports_credit_notes.filter', 'Filter Credit Note Reports', 'Apply date/customer filters to credit note reports', 'reports_credit_notes', 'filter', 1, '2025-09-01 09:01:54'),
(125, 'reports_credit_notes.export', 'Export Credit Note Reports', 'Export credit note reports to PDF/Excel', 'reports_credit_notes', 'export', 1, '2025-09-01 09:01:54'),
(126, 'reports_credit_notes.print', 'Print Credit Note Reports', 'Print credit note reports', 'reports_credit_notes', 'print', 1, '2025-09-01 09:01:54'),
(127, 'reports_credit_notes.search', 'Search Credit Note Reports', 'Search credit notes by number/customer', 'reports_credit_notes', 'search', 1, '2025-09-01 09:01:54'),
(128, 'reports_debit_notes.view', 'View Debit Note Reports', 'View debit note transaction reports', 'reports_debit_notes', 'view', 1, '2025-09-01 09:01:54'),
(129, 'reports_debit_notes.filter', 'Filter Debit Note Reports', 'Apply date/customer filters to debit note reports', 'reports_debit_notes', 'filter', 1, '2025-09-01 09:01:54'),
(130, 'reports_debit_notes.export', 'Export Debit Note Reports', 'Export debit note reports to PDF/Excel', 'reports_debit_notes', 'export', 1, '2025-09-01 09:01:54'),
(131, 'reports_debit_notes.print', 'Print Debit Note Reports', 'Print debit note reports', 'reports_debit_notes', 'print', 1, '2025-09-01 09:01:54'),
(132, 'reports_debit_notes.search', 'Search Debit Note Reports', 'Search debit notes by number/customer', 'reports_debit_notes', 'search', 1, '2025-09-01 09:01:54'),
(133, 'sales_orders.view', 'View Sales Orders', 'View sales order list and details', 'sales_orders', 'view', 1, '2025-09-01 09:01:54'),
(134, 'sales_orders.create', 'Create Sales Orders', 'Create new sales orders', 'sales_orders', 'create', 1, '2025-09-01 09:01:54'),
(135, 'sales_orders.edit', 'Edit Sales Orders', 'Modify existing sales orders', 'sales_orders', 'edit', 1, '2025-09-01 09:01:54'),
(136, 'sales_orders.delete', 'Delete Sales Orders', 'Remove sales orders from system', 'sales_orders', 'delete', 1, '2025-09-01 09:01:54'),
(137, 'sales_orders.send', 'Send Sales Orders', 'Email sales orders to customers', 'sales_orders', 'send', 1, '2025-09-01 09:01:54'),
(138, 'sales_orders.print', 'Print Sales Orders', 'Generate PDF sales orders', 'sales_orders', 'print', 1, '2025-09-01 09:01:54'),
(139, 'purchase_orders.view', 'View Purchase Orders', 'View purchase order list and details', 'purchase_orders', 'view', 1, '2025-09-01 09:01:54'),
(140, 'purchase_orders.create', 'Create Purchase Orders', 'Create new purchase orders', 'purchase_orders', 'create', 1, '2025-09-01 09:01:54'),
(141, 'purchase_orders.edit', 'Edit Purchase Orders', 'Modify existing purchase orders', 'purchase_orders', 'edit', 1, '2025-09-01 09:01:54'),
(142, 'purchase_orders.delete', 'Delete Purchase Orders', 'Remove purchase orders from system', 'purchase_orders', 'delete', 1, '2025-09-01 09:01:54'),
(143, 'purchase_orders.send', 'Send Purchase Orders', 'Email purchase orders to vendors', 'purchase_orders', 'send', 1, '2025-09-01 09:01:54'),
(144, 'purchase_orders.print', 'Print Purchase Orders', 'Generate PDF purchase orders', 'purchase_orders', 'print', 1, '2025-09-01 09:01:54'),
(145, 'sales_invoices.view', 'View Sales Invoices', 'View sales invoice list and details', 'sales_invoices', 'view', 1, '2025-09-01 09:01:54'),
(146, 'sales_invoices.create', 'Create Sales Invoices', 'Create new sales invoices', 'sales_invoices', 'create', 1, '2025-09-01 09:01:54'),
(147, 'sales_invoices.edit', 'Edit Sales Invoices', 'Modify existing sales invoices', 'sales_invoices', 'edit', 1, '2025-09-01 09:01:54'),
(148, 'sales_invoices.delete', 'Delete Sales Invoices', 'Remove sales invoices from system', 'sales_invoices', 'delete', 1, '2025-09-01 09:01:54'),
(149, 'sales_invoices.send', 'Send Sales Invoices', 'Email sales invoices to customers', 'sales_invoices', 'send', 1, '2025-09-01 09:01:54'),
(150, 'sales_invoices.print', 'Print Sales Invoices', 'Generate PDF sales invoices', 'sales_invoices', 'print', 1, '2025-09-01 09:01:54'),
(151, 'credit_notes.view', 'View Credit Notes', 'View credit note list and details', 'credit_notes', 'view', 1, '2025-09-01 09:01:54'),
(152, 'credit_notes.create', 'Create Credit Notes', 'Create new credit notes', 'credit_notes', 'create', 1, '2025-09-01 09:01:54'),
(153, 'credit_notes.edit', 'Edit Credit Notes', 'Modify existing credit notes', 'credit_notes', 'edit', 1, '2025-09-01 09:01:54'),
(154, 'credit_notes.delete', 'Delete Credit Notes', 'Remove credit notes from system', 'credit_notes', 'delete', 1, '2025-09-01 09:01:54'),
(155, 'credit_notes.send', 'Send Credit Notes', 'Email credit notes to customers', 'credit_notes', 'send', 1, '2025-09-01 09:01:54'),
(156, 'credit_notes.print', 'Print Credit Notes', 'Generate PDF credit notes', 'credit_notes', 'print', 1, '2025-09-01 09:01:54'),
(157, 'debit_notes.view', 'View Debit Notes', 'View debit note list and details', 'debit_notes', 'view', 1, '2025-09-01 09:01:54'),
(158, 'debit_notes.create', 'Create Debit Notes', 'Create new debit notes', 'debit_notes', 'create', 1, '2025-09-01 09:01:54'),
(159, 'debit_notes.edit', 'Edit Debit Notes', 'Modify existing debit notes', 'debit_notes', 'edit', 1, '2025-09-01 09:01:54'),
(160, 'debit_notes.delete', 'Delete Debit Notes', 'Remove debit notes from system', 'debit_notes', 'delete', 1, '2025-09-01 09:01:54'),
(161, 'debit_notes.send', 'Send Debit Notes', 'Email debit notes to customers', 'debit_notes', 'send', 1, '2025-09-01 09:01:54'),
(162, 'debit_notes.print', 'Print Debit Notes', 'Generate PDF debit notes', 'debit_notes', 'print', 1, '2025-09-01 09:01:54'),
(163, '', '', 'View purchase invoices', 'purchase_invoices', 'view', 1, '2025-09-03 16:36:52');

-- --------------------------------------------------------

--
-- Table structure for table `price_master`
--

CREATE TABLE `price_master` (
  `id` int(11) NOT NULL,
  `machine_id` int(11) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `valid_from` date NOT NULL,
  `valid_to` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `price_master`
--

INSERT INTO `price_master` (`id`, `machine_id`, `price`, `valid_from`, `valid_to`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 2772000.00, '2025-01-01', '2025-12-31', 1, '2025-08-28 12:31:21', '2025-08-28 12:31:21'),
(2, 2, 3020000.00, '2025-01-01', '2025-12-31', 1, '2025-08-28 12:31:21', '2025-08-28 12:31:21'),
(3, 3, 890000.00, '2025-01-01', '2025-12-31', 1, '2025-08-28 12:31:21', '2025-08-28 12:31:21'),
(4, 4, 215000.00, '2025-01-01', '2025-12-31', 1, '2025-08-28 12:31:21', '2025-08-28 12:31:21'),
(5, 5, 365000.00, '2025-01-01', '2025-12-31', 1, '2025-08-28 12:31:21', '2025-08-28 12:31:21'),
(7, 5, 1000000.00, '2025-08-28', '2026-08-28', 1, '2025-08-28 17:02:58', '2025-08-28 17:02:58'),
(11, 12, 1000.00, '2025-09-04', '2026-09-04', 1, '2025-09-04 11:32:31', '2025-09-04 11:32:31');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_invoices`
--

CREATE TABLE `purchase_invoices` (
  `id` int(11) NOT NULL,
  `pi_number` varchar(50) NOT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `vendor_name` varchar(255) NOT NULL,
  `purchase_order_id` int(11) DEFAULT NULL,
  `purchase_order_number` varchar(50) DEFAULT NULL,
  `hsn_code` varchar(10) DEFAULT NULL,
  `pi_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('draft','pending','paid','partially_paid','overdue','cancelled') DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `final_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_invoices`
--

INSERT INTO `purchase_invoices` (`id`, `pi_number`, `vendor_id`, `vendor_name`, `purchase_order_id`, `purchase_order_number`, `hsn_code`, `pi_date`, `due_date`, `status`, `notes`, `total_amount`, `discount_percentage`, `discount_amount`, `final_total`, `created_by`, `created_at`, `updated_at`) VALUES
(3, 'PI-2025-00001', 2, 'ABC Pharmaceuticals Ltd', NULL, NULL, NULL, '2025-09-04', '2025-10-04', 'draft', '', 412000.00, 0.00, 0.00, 412000.00, 1, '2025-09-04 11:38:36', '2025-09-04 11:38:36');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_invoice_items`
--

CREATE TABLE `purchase_invoice_items` (
  `id` int(11) NOT NULL,
  `pi_id` int(11) NOT NULL,
  `item_type` enum('machine','spare') NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `hsn_code` varchar(30) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `machine_id` int(11) DEFAULT NULL COMMENT 'For spares attached to machines',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_invoice_items`
--

INSERT INTO `purchase_invoice_items` (`id`, `pi_id`, `item_type`, `item_id`, `item_name`, `description`, `hsn_code`, `quantity`, `unit_price`, `total_price`, `machine_id`, `created_at`, `updated_at`) VALUES
(7, 3, 'machine', 5, 'Colloid Mill', '', NULL, 1, 365000.00, 365000.00, 0, '2025-09-04 12:11:27', '2025-09-04 12:11:27'),
(8, 3, 'spare', 3, 'Sealing Roller', 'Heat sealing roller for blister packing', NULL, 1, 12000.00, 12000.00, 5, '2025-09-04 12:11:27', '2025-09-04 12:11:27'),
(9, 3, 'spare', 8, 'Stator Rotor Set', 'Stator rotor assembly for colloid mill', NULL, 1, 35000.00, 35000.00, 5, '2025-09-04 12:11:27', '2025-09-04 12:11:27');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `vendor_name` varchar(255) NOT NULL,
  `sales_order_id` int(11) DEFAULT NULL,
  `sales_order_number` varchar(50) DEFAULT NULL,
  `quotation_id` int(11) DEFAULT NULL,
  `quotation_number` varchar(50) DEFAULT NULL,
  `hsn_code` varchar(10) DEFAULT NULL,
  `po_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('draft','sent','acknowledged','received','cancelled') DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `final_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `po_number`, `vendor_id`, `vendor_name`, `sales_order_id`, `sales_order_number`, `quotation_id`, `quotation_number`, `hsn_code`, `po_date`, `due_date`, `status`, `notes`, `total_amount`, `discount_percentage`, `discount_amount`, `final_total`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 'PO-2025-00001', 6, 'ABC Chemical Suppliers Pvt Ltd', NULL, NULL, NULL, NULL, NULL, '2025-09-01', '2025-09-16', 'sent', '', 890000.00, 0.00, 0.00, 890000.00, 1, '2025-09-01 07:31:13', '2025-09-01 07:32:10'),
(3, 'PO-2025-00002', 6, 'ABC Chemical Suppliers Pvt Ltd', 3, NULL, NULL, NULL, NULL, '2025-09-03', '2025-09-18', 'draft', '', 890000.00, 0.00, 0.00, 890000.00, 1, '2025-09-03 17:51:36', '2025-09-03 17:51:36');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `item_type` enum('machine','spare') NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `hsn_code` varchar(30) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`id`, `po_id`, `item_type`, `item_id`, `item_name`, `description`, `hsn_code`, `quantity`, `unit_price`, `total_price`, `created_at`, `updated_at`) VALUES
(10, 2, 'machine', 3, 'Octagonal Blender - Machine', 'HSN:', '', 1, 890000.00, 890000.00, '2025-09-03 17:51:22', '2025-09-03 17:51:22'),
(11, 3, 'machine', 3, 'Octagonal Blender - Machine', 'HSN:', '', 1, 890000.00, 890000.00, '2025-09-03 17:51:36', '2025-09-03 17:51:36');

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

CREATE TABLE `quotations` (
  `id` int(11) NOT NULL,
  `prefix` varchar(10) NOT NULL,
  `max_no` int(10) UNSIGNED NOT NULL,
  `quotation_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `quotation_date` date NOT NULL,
  `valid_until` date NOT NULL,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `grand_total` decimal(15,2) DEFAULT 0.00,
  `status` enum('pending','approved','rejected','expired') DEFAULT 'pending',
  `enquiry_ref` varchar(100) DEFAULT NULL,
  `revision_no` int(11) DEFAULT 1,
  `prepared_by` varchar(255) DEFAULT 'Pharma Machinery Company',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `quotations`
--

INSERT INTO `quotations` (`id`, `prefix`, `max_no`, `quotation_number`, `customer_id`, `quotation_date`, `valid_until`, `total_amount`, `discount_percentage`, `discount_amount`, `tax_amount`, `grand_total`, `status`, `enquiry_ref`, `revision_no`, `prepared_by`, `notes`, `created_at`, `updated_at`) VALUES
(3, 'QUO-', 1, 'QUO-2025-00001', 6, '2025-09-01', '2025-10-01', 890000.00, 0.00, 0.00, 0.00, 890000.00, 'pending', '100', 1, 'Sales Department', '', '2025-09-01 06:29:02', '2025-09-01 06:29:02'),
(4, 'QUO-', 2, 'QUO-2025-00002', 2, '2025-09-03', '2025-10-03', 890000.00, 0.00, 0.00, 0.00, 890000.00, 'pending', 'asdasd', 1, 'Sales Department', '', '2025-09-03 17:39:56', '2025-09-03 17:39:56'),
(5, 'QUO-', 3, 'QUO-2025-00003', 6, '2025-09-04', '2025-10-04', 1000.00, 0.00, 0.00, 0.00, 1000.00, 'pending', '', 1, 'Sales Department', '', '2025-09-04 01:10:45', '2025-09-04 11:44:41'),
(7, 'QUO-', 4, 'QUO-2025-00004', 6, '2025-09-04', '2025-10-04', 6205.00, 0.00, 0.00, 0.00, 6205.00, 'pending', 'asdasd', 1, 'Sales Department', '', '2025-09-04 11:49:13', '2025-09-04 12:11:33');

-- --------------------------------------------------------

--
-- Table structure for table `quotation_items`
--

CREATE TABLE `quotation_items` (
  `id` int(11) NOT NULL,
  `quotation_id` int(11) NOT NULL,
  `item_type` enum('machine','spare') NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL,
  `total_price` decimal(15,2) NOT NULL,
  `sl_no` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `quotation_items`
--

INSERT INTO `quotation_items` (`id`, `quotation_id`, `item_type`, `item_id`, `quantity`, `unit_price`, `total_price`, `sl_no`, `description`, `specifications`, `created_at`, `updated_at`) VALUES
(3, 3, 'machine', 3, 1, 890000.00, 890000.00, 1, 'Octagonal Blender - Machine', '', '2025-09-01 06:29:02', '2025-09-01 06:29:02'),
(5, 4, 'machine', 3, 1, 890000.00, 890000.00, 1, 'Octagonal Blender - Machine', '', '2025-09-03 17:41:11', '2025-09-03 17:41:11'),
(11, 5, 'machine', 12, 1, 1000.00, 1000.00, 1, 'ASDASDDSA - Machine', '', '2025-09-04 11:48:43', '2025-09-04 11:48:43'),
(14, 7, 'machine', 12, 1, 1000.00, 1000.00, 1, 'ASDASDDSA - Machine', '', '2025-09-04 12:11:33', '2025-09-04 12:11:33');

-- --------------------------------------------------------

--
-- Table structure for table `quotation_machine_features`
--

CREATE TABLE `quotation_machine_features` (
  `id` int(11) NOT NULL,
  `quotation_item_id` int(11) NOT NULL,
  `feature_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `quotation_machine_features`
--

INSERT INTO `quotation_machine_features` (`id`, `quotation_item_id`, `feature_name`, `price`, `quantity`, `total_price`, `created_at`, `updated_at`) VALUES
(2, 11, 'MACHIN', 5205.00, 1, 5205.00, '2025-09-04 11:48:43', '2025-09-04 11:48:43'),
(5, 14, 'MACHIN', 5205.00, 1, 5205.00, '2025-09-04 12:11:33', '2025-09-04 12:11:33');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'super_admin', 'Super Administrator', 'Full system access with all permissions', 1, '2025-08-29 08:31:45', '2025-08-29 08:31:45'),
(2, 'admin', 'Administrator', 'Administrative access with user management capabilities', 1, '2025-08-29 08:31:45', '2025-08-29 08:31:45'),
(3, 'manager', 'Manager', 'Can view, create, and edit records but cannot delete', 1, '2025-08-29 08:31:45', '2025-08-29 08:31:45'),
(4, 'operator', 'Operator', 'Can view and create records only', 1, '2025-08-29 08:31:45', '2025-08-29 08:31:45'),
(5, 'viewer', 'Viewer', 'Read-only access to all modules', 1, '2025-08-29 08:31:45', '2025-08-29 08:31:45'),
(12, 'cat_killer', 'SHUBHAM', 'BOX', 1, '2025-08-30 05:38:42', '2025-08-30 05:38:42'),
(13, 'catkiller', 'ASDASD', 'ADSSAD', 1, '2025-09-01 05:53:44', '2025-09-01 05:53:44');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`, `created_at`) VALUES
(64, 2, 3, '2025-08-29 08:31:45'),
(65, 2, 5, '2025-08-29 08:31:45'),
(66, 2, 4, '2025-08-29 08:31:45'),
(67, 2, 2, '2025-08-29 08:31:45'),
(68, 2, 1, '2025-08-29 08:31:45'),
(69, 2, 7, '2025-08-29 08:31:45'),
(70, 2, 9, '2025-08-29 08:31:45'),
(71, 2, 8, '2025-08-29 08:31:45'),
(72, 2, 6, '2025-08-29 08:31:45'),
(73, 2, 15, '2025-08-29 08:31:45'),
(74, 2, 17, '2025-08-29 08:31:45'),
(75, 2, 16, '2025-08-29 08:31:45'),
(76, 2, 14, '2025-08-29 08:31:45'),
(77, 2, 19, '2025-08-29 08:31:45'),
(78, 2, 21, '2025-08-29 08:31:45'),
(79, 2, 20, '2025-08-29 08:31:45'),
(80, 2, 23, '2025-08-29 08:31:45'),
(81, 2, 22, '2025-08-29 08:31:45'),
(82, 2, 18, '2025-08-29 08:31:45'),
(83, 2, 25, '2025-08-29 08:31:45'),
(84, 2, 24, '2025-08-29 08:31:45'),
(85, 2, 32, '2025-08-29 08:31:45'),
(86, 2, 31, '2025-08-29 08:31:45'),
(87, 2, 11, '2025-08-29 08:31:45'),
(88, 2, 13, '2025-08-29 08:31:45'),
(89, 2, 12, '2025-08-29 08:31:45'),
(90, 2, 10, '2025-08-29 08:31:45'),
(91, 2, 30, '2025-08-29 08:31:45'),
(92, 2, 27, '2025-08-29 08:31:45'),
(93, 2, 28, '2025-08-29 08:31:45'),
(94, 2, 26, '2025-08-29 08:31:45'),
(95, 3, 1, '2025-08-29 08:31:45'),
(96, 3, 2, '2025-08-29 08:31:45'),
(97, 3, 3, '2025-08-29 08:31:45'),
(98, 3, 4, '2025-08-29 08:31:45'),
(99, 3, 6, '2025-08-29 08:31:45'),
(100, 3, 7, '2025-08-29 08:31:45'),
(101, 3, 8, '2025-08-29 08:31:45'),
(102, 3, 10, '2025-08-29 08:31:45'),
(103, 3, 11, '2025-08-29 08:31:45'),
(104, 3, 12, '2025-08-29 08:31:45'),
(105, 3, 14, '2025-08-29 08:31:45'),
(106, 3, 15, '2025-08-29 08:31:45'),
(107, 3, 16, '2025-08-29 08:31:45'),
(108, 3, 18, '2025-08-29 08:31:45'),
(109, 3, 19, '2025-08-29 08:31:45'),
(110, 3, 20, '2025-08-29 08:31:45'),
(111, 3, 21, '2025-08-29 08:31:45'),
(112, 3, 22, '2025-08-29 08:31:45'),
(113, 3, 23, '2025-08-29 08:31:45'),
(114, 3, 24, '2025-08-29 08:31:45'),
(115, 3, 25, '2025-08-29 08:31:45'),
(116, 3, 26, '2025-08-29 08:31:45'),
(117, 3, 27, '2025-08-29 08:31:45'),
(118, 3, 28, '2025-08-29 08:31:45'),
(119, 3, 31, '2025-08-29 08:31:45'),
(120, 3, 32, '2025-08-29 08:31:45'),
(126, 4, 3, '2025-08-29 08:31:45'),
(127, 4, 7, '2025-08-29 08:31:45'),
(128, 4, 11, '2025-08-29 08:31:45'),
(129, 4, 15, '2025-08-29 08:31:45'),
(130, 4, 19, '2025-08-29 08:31:45'),
(131, 4, 27, '2025-08-29 08:31:45'),
(132, 4, 23, '2025-08-29 08:31:45'),
(133, 4, 1, '2025-08-29 08:31:45'),
(134, 4, 2, '2025-08-29 08:31:45'),
(135, 4, 6, '2025-08-29 08:31:45'),
(136, 4, 10, '2025-08-29 08:31:45'),
(137, 4, 14, '2025-08-29 08:31:45'),
(138, 4, 18, '2025-08-29 08:31:45'),
(139, 4, 24, '2025-08-29 08:31:45'),
(140, 4, 26, '2025-08-29 08:31:45'),
(141, 4, 31, '2025-08-29 08:31:45'),
(157, 5, 1, '2025-08-29 08:31:45'),
(158, 5, 2, '2025-08-29 08:31:45'),
(159, 5, 6, '2025-08-29 08:31:45'),
(160, 5, 10, '2025-08-29 08:31:45'),
(161, 5, 14, '2025-08-29 08:31:45'),
(162, 5, 18, '2025-08-29 08:31:45'),
(163, 5, 24, '2025-08-29 08:31:45'),
(164, 5, 26, '2025-08-29 08:31:45'),
(165, 5, 31, '2025-08-29 08:31:45'),
(169, 2, 34, '2025-08-29 08:58:25'),
(170, 2, 35, '2025-08-29 08:58:25'),
(171, 2, 33, '2025-08-29 08:58:25'),
(184, 12, 3, '2025-08-30 05:38:42'),
(185, 12, 5, '2025-08-30 05:38:42'),
(186, 12, 4, '2025-08-30 05:38:42'),
(187, 12, 2, '2025-08-30 05:38:42'),
(188, 12, 1, '2025-08-30 05:38:42'),
(189, 12, 7, '2025-08-30 05:38:42'),
(190, 12, 9, '2025-08-30 05:38:42'),
(191, 12, 8, '2025-08-30 05:38:42'),
(192, 12, 6, '2025-08-30 05:38:42'),
(193, 12, 15, '2025-08-30 05:38:42'),
(194, 12, 17, '2025-08-30 05:38:42'),
(195, 12, 16, '2025-08-30 05:38:42'),
(196, 12, 14, '2025-08-30 05:38:42'),
(197, 12, 19, '2025-08-30 05:38:42'),
(198, 12, 21, '2025-08-30 05:38:42'),
(199, 12, 20, '2025-08-30 05:38:42'),
(200, 12, 23, '2025-08-30 05:38:42'),
(201, 12, 22, '2025-08-30 05:38:42'),
(202, 12, 18, '2025-08-30 05:38:42'),
(203, 12, 25, '2025-08-30 05:38:42'),
(204, 12, 24, '2025-08-30 05:38:42'),
(205, 12, 32, '2025-08-30 05:38:42'),
(206, 12, 31, '2025-08-30 05:38:42'),
(207, 12, 11, '2025-08-30 05:38:42'),
(208, 12, 13, '2025-08-30 05:38:42'),
(209, 12, 12, '2025-08-30 05:38:42'),
(210, 12, 10, '2025-08-30 05:38:42'),
(211, 12, 34, '2025-08-30 05:38:42'),
(212, 12, 35, '2025-08-30 05:38:42'),
(213, 12, 33, '2025-08-30 05:38:42'),
(214, 12, 30, '2025-08-30 05:38:42'),
(215, 12, 27, '2025-08-30 05:38:42'),
(216, 12, 29, '2025-08-30 05:38:42'),
(217, 12, 28, '2025-08-30 05:38:42'),
(218, 12, 26, '2025-08-30 05:38:42'),
(274, 1, 3, '2025-09-01 05:53:07'),
(275, 1, 5, '2025-09-01 05:53:07'),
(276, 1, 4, '2025-09-01 05:53:07'),
(277, 1, 2, '2025-09-01 05:53:07'),
(278, 1, 1, '2025-09-01 05:53:07'),
(279, 1, 84, '2025-09-01 05:53:07'),
(280, 1, 86, '2025-09-01 05:53:07'),
(281, 1, 85, '2025-09-01 05:53:07'),
(282, 1, 83, '2025-09-01 05:53:07'),
(283, 1, 7, '2025-09-01 05:53:07'),
(284, 1, 9, '2025-09-01 05:53:07'),
(285, 1, 8, '2025-09-01 05:53:07'),
(286, 1, 6, '2025-09-01 05:53:07'),
(287, 1, 15, '2025-09-01 05:53:07'),
(288, 1, 17, '2025-09-01 05:53:07'),
(289, 1, 16, '2025-09-01 05:53:07'),
(290, 1, 14, '2025-09-01 05:53:07'),
(291, 1, 72, '2025-09-01 05:53:07'),
(292, 1, 74, '2025-09-01 05:53:07'),
(293, 1, 73, '2025-09-01 05:53:07'),
(294, 1, 71, '2025-09-01 05:53:07'),
(295, 1, 19, '2025-09-01 05:53:07'),
(296, 1, 21, '2025-09-01 05:53:07'),
(297, 1, 20, '2025-09-01 05:53:07'),
(298, 1, 23, '2025-09-01 05:53:07'),
(299, 1, 22, '2025-09-01 05:53:07'),
(300, 1, 18, '2025-09-01 05:53:07'),
(301, 1, 25, '2025-09-01 05:53:07'),
(302, 1, 24, '2025-09-01 05:53:07'),
(303, 1, 76, '2025-09-01 05:53:07'),
(304, 1, 78, '2025-09-01 05:53:07'),
(305, 1, 77, '2025-09-01 05:53:07'),
(306, 1, 75, '2025-09-01 05:53:07'),
(307, 1, 68, '2025-09-01 05:53:07'),
(308, 1, 70, '2025-09-01 05:53:07'),
(309, 1, 69, '2025-09-01 05:53:07'),
(310, 1, 67, '2025-09-01 05:53:07'),
(311, 1, 32, '2025-09-01 05:53:07'),
(312, 1, 31, '2025-09-01 05:53:07'),
(313, 1, 11, '2025-09-01 05:53:07'),
(314, 1, 13, '2025-09-01 05:53:07'),
(315, 1, 12, '2025-09-01 05:53:07'),
(316, 1, 10, '2025-09-01 05:53:07'),
(317, 1, 34, '2025-09-01 05:53:07'),
(318, 1, 35, '2025-09-01 05:53:07'),
(319, 1, 33, '2025-09-01 05:53:07'),
(320, 1, 30, '2025-09-01 05:53:07'),
(321, 1, 27, '2025-09-01 05:53:07'),
(322, 1, 29, '2025-09-01 05:53:07'),
(323, 1, 28, '2025-09-01 05:53:07'),
(324, 1, 26, '2025-09-01 05:53:07'),
(325, 13, 80, '2025-09-01 05:53:44'),
(326, 13, 82, '2025-09-01 05:53:44'),
(327, 13, 81, '2025-09-01 05:53:44'),
(328, 13, 79, '2025-09-01 05:53:44'),
(329, 13, 3, '2025-09-01 05:53:44'),
(330, 13, 5, '2025-09-01 05:53:44'),
(331, 13, 4, '2025-09-01 05:53:44'),
(332, 13, 2, '2025-09-01 05:53:44'),
(333, 13, 1, '2025-09-01 05:53:44'),
(334, 13, 84, '2025-09-01 05:53:44'),
(335, 13, 86, '2025-09-01 05:53:44'),
(336, 13, 85, '2025-09-01 05:53:44'),
(337, 13, 83, '2025-09-01 05:53:44'),
(338, 13, 7, '2025-09-01 05:53:44'),
(339, 13, 9, '2025-09-01 05:53:44'),
(340, 13, 8, '2025-09-01 05:53:44'),
(341, 13, 6, '2025-09-01 05:53:44'),
(342, 13, 15, '2025-09-01 05:53:44'),
(343, 13, 17, '2025-09-01 05:53:44'),
(344, 13, 16, '2025-09-01 05:53:44'),
(345, 13, 14, '2025-09-01 05:53:44'),
(346, 13, 72, '2025-09-01 05:53:44'),
(347, 13, 74, '2025-09-01 05:53:44'),
(348, 13, 73, '2025-09-01 05:53:44'),
(349, 13, 71, '2025-09-01 05:53:44'),
(350, 13, 19, '2025-09-01 05:53:44'),
(351, 13, 21, '2025-09-01 05:53:44'),
(352, 13, 20, '2025-09-01 05:53:44'),
(353, 13, 23, '2025-09-01 05:53:44'),
(354, 13, 22, '2025-09-01 05:53:44'),
(355, 13, 18, '2025-09-01 05:53:44'),
(356, 13, 25, '2025-09-01 05:53:44'),
(357, 13, 24, '2025-09-01 05:53:44'),
(358, 13, 76, '2025-09-01 05:53:44'),
(359, 13, 78, '2025-09-01 05:53:44'),
(360, 13, 77, '2025-09-01 05:53:44'),
(361, 13, 75, '2025-09-01 05:53:44'),
(362, 13, 68, '2025-09-01 05:53:44'),
(363, 13, 70, '2025-09-01 05:53:44'),
(364, 13, 69, '2025-09-01 05:53:44'),
(365, 13, 67, '2025-09-01 05:53:44'),
(366, 13, 32, '2025-09-01 05:53:44'),
(367, 13, 31, '2025-09-01 05:53:44'),
(368, 13, 11, '2025-09-01 05:53:44'),
(369, 13, 13, '2025-09-01 05:53:44'),
(370, 13, 12, '2025-09-01 05:53:44'),
(371, 13, 10, '2025-09-01 05:53:44'),
(372, 13, 34, '2025-09-01 05:53:44'),
(373, 13, 35, '2025-09-01 05:53:44'),
(374, 13, 33, '2025-09-01 05:53:44'),
(375, 13, 30, '2025-09-01 05:53:44'),
(376, 13, 27, '2025-09-01 05:53:44'),
(377, 13, 29, '2025-09-01 05:53:44'),
(378, 13, 28, '2025-09-01 05:53:44'),
(379, 13, 26, '2025-09-01 05:53:44'),
(380, 1, 87, '2025-09-01 09:01:54'),
(381, 1, 88, '2025-09-01 09:01:54'),
(382, 1, 89, '2025-09-01 09:01:54'),
(383, 1, 90, '2025-09-01 09:01:54'),
(384, 1, 91, '2025-09-01 09:01:54'),
(385, 1, 92, '2025-09-01 09:01:54'),
(386, 1, 93, '2025-09-01 09:01:54'),
(387, 1, 94, '2025-09-01 09:01:54'),
(388, 1, 95, '2025-09-01 09:01:54'),
(389, 1, 96, '2025-09-01 09:01:54'),
(390, 1, 97, '2025-09-01 09:01:54'),
(391, 1, 98, '2025-09-01 09:01:54'),
(392, 1, 99, '2025-09-01 09:01:54'),
(393, 1, 100, '2025-09-01 09:01:54'),
(394, 1, 101, '2025-09-01 09:01:54'),
(395, 1, 102, '2025-09-01 09:01:54'),
(396, 1, 103, '2025-09-01 09:01:54'),
(397, 1, 104, '2025-09-01 09:01:54'),
(398, 1, 105, '2025-09-01 09:01:54'),
(399, 1, 106, '2025-09-01 09:01:54'),
(400, 1, 107, '2025-09-01 09:01:54'),
(401, 1, 108, '2025-09-01 09:01:54'),
(402, 1, 109, '2025-09-01 09:01:54'),
(403, 1, 110, '2025-09-01 09:01:54'),
(404, 1, 111, '2025-09-01 09:01:54'),
(405, 1, 112, '2025-09-01 09:01:54'),
(406, 1, 113, '2025-09-01 09:01:54'),
(407, 1, 114, '2025-09-01 09:01:54'),
(408, 1, 115, '2025-09-01 09:01:54'),
(409, 1, 116, '2025-09-01 09:01:54'),
(410, 1, 117, '2025-09-01 09:01:54'),
(411, 1, 118, '2025-09-01 09:01:54'),
(412, 1, 119, '2025-09-01 09:01:54'),
(413, 1, 120, '2025-09-01 09:01:54'),
(414, 1, 121, '2025-09-01 09:01:54'),
(415, 1, 122, '2025-09-01 09:01:54'),
(416, 1, 123, '2025-09-01 09:01:54'),
(417, 1, 124, '2025-09-01 09:01:54'),
(418, 1, 125, '2025-09-01 09:01:54'),
(419, 1, 126, '2025-09-01 09:01:54'),
(420, 1, 127, '2025-09-01 09:01:54'),
(421, 1, 128, '2025-09-01 09:01:54'),
(422, 1, 129, '2025-09-01 09:01:54'),
(423, 1, 130, '2025-09-01 09:01:54'),
(424, 1, 131, '2025-09-01 09:01:54'),
(425, 1, 132, '2025-09-01 09:01:54'),
(443, 2, 87, '2025-09-01 09:01:54'),
(444, 2, 88, '2025-09-01 09:01:54'),
(445, 2, 89, '2025-09-01 09:01:54'),
(446, 2, 90, '2025-09-01 09:01:54'),
(447, 2, 91, '2025-09-01 09:01:54'),
(448, 2, 92, '2025-09-01 09:01:54'),
(449, 2, 93, '2025-09-01 09:01:54'),
(450, 2, 94, '2025-09-01 09:01:54'),
(451, 2, 95, '2025-09-01 09:01:54'),
(452, 2, 96, '2025-09-01 09:01:54'),
(453, 2, 97, '2025-09-01 09:01:54'),
(454, 2, 98, '2025-09-01 09:01:54'),
(455, 2, 99, '2025-09-01 09:01:54'),
(456, 2, 100, '2025-09-01 09:01:54'),
(457, 2, 101, '2025-09-01 09:01:54'),
(458, 2, 102, '2025-09-01 09:01:54'),
(459, 2, 103, '2025-09-01 09:01:54'),
(460, 2, 104, '2025-09-01 09:01:54'),
(461, 2, 105, '2025-09-01 09:01:54'),
(462, 2, 106, '2025-09-01 09:01:54'),
(463, 2, 107, '2025-09-01 09:01:54'),
(464, 2, 108, '2025-09-01 09:01:54'),
(465, 2, 109, '2025-09-01 09:01:54'),
(466, 2, 110, '2025-09-01 09:01:54'),
(467, 2, 111, '2025-09-01 09:01:54'),
(468, 2, 112, '2025-09-01 09:01:54'),
(469, 2, 113, '2025-09-01 09:01:54'),
(470, 2, 114, '2025-09-01 09:01:54'),
(471, 2, 115, '2025-09-01 09:01:54'),
(472, 2, 116, '2025-09-01 09:01:54'),
(473, 2, 117, '2025-09-01 09:01:54'),
(474, 2, 118, '2025-09-01 09:01:54'),
(475, 2, 119, '2025-09-01 09:01:54'),
(476, 2, 120, '2025-09-01 09:01:54'),
(477, 2, 121, '2025-09-01 09:01:54'),
(478, 2, 122, '2025-09-01 09:01:54'),
(479, 2, 123, '2025-09-01 09:01:54'),
(480, 2, 124, '2025-09-01 09:01:54'),
(481, 2, 125, '2025-09-01 09:01:54'),
(482, 2, 126, '2025-09-01 09:01:54'),
(483, 2, 127, '2025-09-01 09:01:54'),
(484, 2, 128, '2025-09-01 09:01:54'),
(485, 2, 129, '2025-09-01 09:01:54'),
(486, 2, 130, '2025-09-01 09:01:54'),
(487, 2, 131, '2025-09-01 09:01:54'),
(488, 2, 132, '2025-09-01 09:01:54'),
(506, 3, 87, '2025-09-01 09:01:54'),
(507, 3, 88, '2025-09-01 09:01:54'),
(508, 3, 89, '2025-09-01 09:01:54'),
(509, 3, 90, '2025-09-01 09:01:54'),
(510, 3, 91, '2025-09-01 09:01:54'),
(511, 3, 92, '2025-09-01 09:01:54'),
(512, 3, 93, '2025-09-01 09:01:54'),
(513, 3, 94, '2025-09-01 09:01:54'),
(514, 3, 95, '2025-09-01 09:01:54'),
(515, 3, 96, '2025-09-01 09:01:54'),
(516, 3, 97, '2025-09-01 09:01:54'),
(517, 3, 98, '2025-09-01 09:01:54'),
(518, 3, 99, '2025-09-01 09:01:54'),
(519, 3, 100, '2025-09-01 09:01:54'),
(520, 3, 101, '2025-09-01 09:01:54'),
(521, 3, 102, '2025-09-01 09:01:54'),
(522, 3, 103, '2025-09-01 09:01:54'),
(523, 3, 104, '2025-09-01 09:01:54'),
(524, 3, 105, '2025-09-01 09:01:54'),
(525, 3, 106, '2025-09-01 09:01:54'),
(526, 3, 107, '2025-09-01 09:01:54'),
(527, 3, 108, '2025-09-01 09:01:54'),
(528, 3, 109, '2025-09-01 09:01:54'),
(529, 3, 110, '2025-09-01 09:01:54'),
(530, 3, 111, '2025-09-01 09:01:54'),
(531, 3, 112, '2025-09-01 09:01:54'),
(532, 3, 113, '2025-09-01 09:01:54'),
(533, 3, 114, '2025-09-01 09:01:54'),
(534, 3, 115, '2025-09-01 09:01:54'),
(535, 3, 116, '2025-09-01 09:01:54'),
(536, 3, 117, '2025-09-01 09:01:54'),
(537, 3, 118, '2025-09-01 09:01:54'),
(538, 3, 119, '2025-09-01 09:01:54'),
(539, 3, 120, '2025-09-01 09:01:54'),
(540, 3, 121, '2025-09-01 09:01:54'),
(541, 3, 122, '2025-09-01 09:01:54'),
(542, 3, 123, '2025-09-01 09:01:54'),
(543, 3, 124, '2025-09-01 09:01:54'),
(544, 3, 125, '2025-09-01 09:01:54'),
(545, 3, 126, '2025-09-01 09:01:54'),
(546, 3, 127, '2025-09-01 09:01:54'),
(547, 3, 128, '2025-09-01 09:01:54'),
(548, 3, 129, '2025-09-01 09:01:54'),
(549, 3, 130, '2025-09-01 09:01:54'),
(550, 3, 131, '2025-09-01 09:01:54'),
(551, 3, 132, '2025-09-01 09:01:54'),
(569, 4, 87, '2025-09-01 09:01:54'),
(570, 4, 90, '2025-09-01 09:01:54'),
(571, 4, 91, '2025-09-01 09:01:54'),
(572, 4, 94, '2025-09-01 09:01:54'),
(573, 4, 95, '2025-09-01 09:01:54'),
(574, 4, 98, '2025-09-01 09:01:54'),
(575, 4, 99, '2025-09-01 09:01:54'),
(576, 4, 102, '2025-09-01 09:01:54'),
(577, 4, 103, '2025-09-01 09:01:54'),
(578, 4, 106, '2025-09-01 09:01:54'),
(579, 4, 108, '2025-09-01 09:01:54'),
(580, 4, 111, '2025-09-01 09:01:54'),
(581, 4, 113, '2025-09-01 09:01:54'),
(582, 4, 116, '2025-09-01 09:01:54'),
(583, 4, 118, '2025-09-01 09:01:54'),
(584, 4, 121, '2025-09-01 09:01:54'),
(585, 4, 123, '2025-09-01 09:01:54'),
(586, 4, 126, '2025-09-01 09:01:54'),
(587, 4, 128, '2025-09-01 09:01:54'),
(588, 4, 131, '2025-09-01 09:01:54'),
(600, 5, 87, '2025-09-01 09:01:54'),
(601, 5, 91, '2025-09-01 09:01:54'),
(602, 5, 95, '2025-09-01 09:01:54'),
(603, 5, 99, '2025-09-01 09:01:54'),
(604, 5, 103, '2025-09-01 09:01:54'),
(605, 5, 108, '2025-09-01 09:01:54'),
(606, 5, 113, '2025-09-01 09:01:54'),
(607, 5, 118, '2025-09-01 09:01:54'),
(608, 5, 123, '2025-09-01 09:01:54'),
(609, 5, 128, '2025-09-01 09:01:54'),
(615, 1, 79, '2025-09-01 09:01:54'),
(616, 1, 80, '2025-09-01 09:01:54'),
(617, 1, 81, '2025-09-01 09:01:54'),
(618, 1, 82, '2025-09-01 09:01:54'),
(619, 1, 133, '2025-09-01 09:01:54'),
(620, 1, 134, '2025-09-01 09:01:54'),
(621, 1, 135, '2025-09-01 09:01:54'),
(622, 1, 136, '2025-09-01 09:01:54'),
(623, 1, 137, '2025-09-01 09:01:54'),
(624, 1, 138, '2025-09-01 09:01:54'),
(625, 1, 139, '2025-09-01 09:01:54'),
(626, 1, 140, '2025-09-01 09:01:54'),
(627, 1, 141, '2025-09-01 09:01:54'),
(628, 1, 142, '2025-09-01 09:01:54'),
(629, 1, 143, '2025-09-01 09:01:54'),
(630, 1, 144, '2025-09-01 09:01:54'),
(631, 1, 145, '2025-09-01 09:01:54'),
(632, 1, 146, '2025-09-01 09:01:54'),
(633, 1, 147, '2025-09-01 09:01:54'),
(634, 1, 148, '2025-09-01 09:01:54'),
(635, 1, 149, '2025-09-01 09:01:54'),
(636, 1, 150, '2025-09-01 09:01:54'),
(637, 1, 151, '2025-09-01 09:01:54'),
(638, 1, 152, '2025-09-01 09:01:54'),
(639, 1, 153, '2025-09-01 09:01:54'),
(640, 1, 154, '2025-09-01 09:01:54'),
(641, 1, 155, '2025-09-01 09:01:54'),
(642, 1, 156, '2025-09-01 09:01:54'),
(643, 1, 157, '2025-09-01 09:01:54'),
(644, 1, 158, '2025-09-01 09:01:54'),
(645, 1, 159, '2025-09-01 09:01:54'),
(646, 1, 160, '2025-09-01 09:01:54'),
(647, 1, 161, '2025-09-01 09:01:54'),
(648, 1, 162, '2025-09-01 09:01:54'),
(678, 2, 67, '2025-09-01 09:01:54'),
(679, 2, 68, '2025-09-01 09:01:54'),
(680, 2, 69, '2025-09-01 09:01:54'),
(681, 2, 70, '2025-09-01 09:01:54'),
(682, 2, 71, '2025-09-01 09:01:54'),
(683, 2, 72, '2025-09-01 09:01:54'),
(684, 2, 73, '2025-09-01 09:01:54'),
(685, 2, 74, '2025-09-01 09:01:54'),
(686, 2, 75, '2025-09-01 09:01:54'),
(687, 2, 76, '2025-09-01 09:01:54'),
(688, 2, 77, '2025-09-01 09:01:54'),
(689, 2, 78, '2025-09-01 09:01:54'),
(690, 2, 79, '2025-09-01 09:01:54'),
(691, 2, 80, '2025-09-01 09:01:54'),
(692, 2, 81, '2025-09-01 09:01:54'),
(693, 2, 82, '2025-09-01 09:01:54'),
(694, 2, 83, '2025-09-01 09:01:54'),
(695, 2, 84, '2025-09-01 09:01:54'),
(696, 2, 85, '2025-09-01 09:01:54'),
(697, 2, 86, '2025-09-01 09:01:54'),
(698, 2, 133, '2025-09-01 09:01:54'),
(699, 2, 134, '2025-09-01 09:01:54'),
(700, 2, 135, '2025-09-01 09:01:54'),
(701, 2, 137, '2025-09-01 09:01:54'),
(702, 2, 138, '2025-09-01 09:01:54'),
(703, 2, 139, '2025-09-01 09:01:54'),
(704, 2, 140, '2025-09-01 09:01:54'),
(705, 2, 141, '2025-09-01 09:01:54'),
(706, 2, 143, '2025-09-01 09:01:54'),
(707, 2, 144, '2025-09-01 09:01:54'),
(708, 2, 145, '2025-09-01 09:01:54'),
(709, 2, 146, '2025-09-01 09:01:54'),
(710, 2, 147, '2025-09-01 09:01:54'),
(711, 2, 149, '2025-09-01 09:01:54'),
(712, 2, 150, '2025-09-01 09:01:54'),
(713, 2, 151, '2025-09-01 09:01:54'),
(714, 2, 152, '2025-09-01 09:01:54'),
(715, 2, 153, '2025-09-01 09:01:54'),
(716, 2, 155, '2025-09-01 09:01:54'),
(717, 2, 156, '2025-09-01 09:01:54'),
(718, 2, 157, '2025-09-01 09:01:54'),
(719, 2, 158, '2025-09-01 09:01:54'),
(720, 2, 159, '2025-09-01 09:01:54'),
(721, 2, 161, '2025-09-01 09:01:54'),
(722, 2, 162, '2025-09-01 09:01:54'),
(741, 3, 67, '2025-09-01 09:01:54'),
(742, 3, 68, '2025-09-01 09:01:54'),
(743, 3, 69, '2025-09-01 09:01:54'),
(744, 3, 71, '2025-09-01 09:01:54'),
(745, 3, 72, '2025-09-01 09:01:54'),
(746, 3, 73, '2025-09-01 09:01:54'),
(747, 3, 75, '2025-09-01 09:01:54'),
(748, 3, 76, '2025-09-01 09:01:54'),
(749, 3, 77, '2025-09-01 09:01:54'),
(750, 3, 79, '2025-09-01 09:01:54'),
(751, 3, 80, '2025-09-01 09:01:54'),
(752, 3, 81, '2025-09-01 09:01:54'),
(753, 3, 83, '2025-09-01 09:01:54'),
(754, 3, 84, '2025-09-01 09:01:54'),
(755, 3, 85, '2025-09-01 09:01:54'),
(756, 3, 133, '2025-09-01 09:01:54'),
(757, 3, 134, '2025-09-01 09:01:54'),
(758, 3, 135, '2025-09-01 09:01:54'),
(759, 3, 137, '2025-09-01 09:01:54'),
(760, 3, 138, '2025-09-01 09:01:54'),
(761, 3, 139, '2025-09-01 09:01:54'),
(762, 3, 140, '2025-09-01 09:01:54'),
(763, 3, 141, '2025-09-01 09:01:54'),
(764, 3, 143, '2025-09-01 09:01:54'),
(765, 3, 144, '2025-09-01 09:01:54'),
(766, 3, 145, '2025-09-01 09:01:54'),
(767, 3, 146, '2025-09-01 09:01:54'),
(768, 3, 147, '2025-09-01 09:01:54'),
(769, 3, 149, '2025-09-01 09:01:54'),
(770, 3, 150, '2025-09-01 09:01:54'),
(771, 3, 151, '2025-09-01 09:01:54'),
(772, 3, 152, '2025-09-01 09:01:54'),
(773, 3, 153, '2025-09-01 09:01:54'),
(774, 3, 155, '2025-09-01 09:01:54'),
(775, 3, 156, '2025-09-01 09:01:54'),
(776, 3, 157, '2025-09-01 09:01:54'),
(777, 3, 158, '2025-09-01 09:01:54'),
(778, 3, 159, '2025-09-01 09:01:54'),
(779, 3, 161, '2025-09-01 09:01:54'),
(780, 3, 162, '2025-09-01 09:01:54'),
(804, 4, 67, '2025-09-01 09:01:54'),
(805, 4, 68, '2025-09-01 09:01:54'),
(806, 4, 71, '2025-09-01 09:01:54'),
(807, 4, 72, '2025-09-01 09:01:54'),
(808, 4, 75, '2025-09-01 09:01:54'),
(809, 4, 76, '2025-09-01 09:01:54'),
(810, 4, 79, '2025-09-01 09:01:54'),
(811, 4, 80, '2025-09-01 09:01:54'),
(812, 4, 83, '2025-09-01 09:01:54'),
(813, 4, 84, '2025-09-01 09:01:54'),
(814, 4, 133, '2025-09-01 09:01:54'),
(815, 4, 134, '2025-09-01 09:01:54'),
(816, 4, 138, '2025-09-01 09:01:54'),
(817, 4, 139, '2025-09-01 09:01:54'),
(818, 4, 140, '2025-09-01 09:01:54'),
(819, 4, 144, '2025-09-01 09:01:54'),
(820, 4, 145, '2025-09-01 09:01:54'),
(821, 4, 146, '2025-09-01 09:01:54'),
(822, 4, 150, '2025-09-01 09:01:54'),
(823, 4, 151, '2025-09-01 09:01:54'),
(824, 4, 152, '2025-09-01 09:01:54'),
(825, 4, 156, '2025-09-01 09:01:54'),
(826, 4, 157, '2025-09-01 09:01:54'),
(827, 4, 158, '2025-09-01 09:01:54'),
(828, 4, 162, '2025-09-01 09:01:54'),
(835, 5, 67, '2025-09-01 09:01:54'),
(836, 5, 71, '2025-09-01 09:01:54'),
(837, 5, 75, '2025-09-01 09:01:54'),
(838, 5, 79, '2025-09-01 09:01:54'),
(839, 5, 83, '2025-09-01 09:01:54'),
(840, 5, 133, '2025-09-01 09:01:54'),
(841, 5, 139, '2025-09-01 09:01:54'),
(842, 5, 145, '2025-09-01 09:01:54'),
(843, 5, 151, '2025-09-01 09:01:54'),
(844, 5, 157, '2025-09-01 09:01:54');

-- --------------------------------------------------------

--
-- Table structure for table `sales_invoices`
--

CREATE TABLE `sales_invoices` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_address` text DEFAULT NULL,
  `customer_gstin` varchar(15) DEFAULT NULL,
  `customer_contact` varchar(20) DEFAULT NULL,
  `purchase_order_id` int(11) DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `tax_percentage` decimal(5,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `final_total` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','sent','paid','overdue','cancelled') DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `sales_invoices`
--

INSERT INTO `sales_invoices` (`id`, `invoice_number`, `customer_id`, `customer_name`, `customer_address`, `customer_gstin`, `customer_contact`, `purchase_order_id`, `invoice_date`, `due_date`, `subtotal`, `discount_percentage`, `discount_amount`, `tax_percentage`, `tax_amount`, `final_total`, `total_amount`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(3, 'INV-2025-00871', 6, 'ABC Chemical Suppliers Pvt Ltd', '123 Industrial Area, Chemical Park', '27ABCCS1234A1Z5', '+91 9876543210', 3, '2025-08-31', '2025-09-30', 890000.00, 0.00, 0.00, 18.00, 160200.00, 1050200.00, 1050200.00, 'sent', '', 1, '2025-08-31 11:36:07', '2025-09-03 14:52:20'),
(4, 'INV-2025-00872', 6, 'ABC Chemical Suppliers Pvt Ltd', '123 Industrial Area, Chemical Park', '27ABCCS1234A1Z5', '+91 9876543210', 4, '2025-09-03', '2025-10-03', 890000.00, 0.00, 0.00, 18.00, 160200.00, 1050200.00, 1050200.00, 'sent', '', 1, '2025-09-03 17:56:28', '2025-09-04 01:09:41');

-- --------------------------------------------------------

--
-- Table structure for table `sales_invoice_items`
--

CREATE TABLE `sales_invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `item_type` enum('machine','spare') DEFAULT 'machine',
  `item_id` int(11) DEFAULT NULL,
  `item_name` varchar(255) DEFAULT '',
  `description` text NOT NULL,
  `hsn_code` varchar(20) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `gst_rate` decimal(5,2) DEFAULT 18.00,
  `total_price` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `sales_invoice_items`
--

INSERT INTO `sales_invoice_items` (`id`, `invoice_id`, `item_type`, `item_id`, `item_name`, `description`, `hsn_code`, `quantity`, `unit`, `unit_price`, `gst_rate`, `total_price`) VALUES
(7, 3, 'machine', 3, 'Octagonal Blender', 'Octagonal Blender - Machine', NULL, 1.00, 'Nos', 890000.00, 18.00, 890000.00),
(9, 4, 'machine', 3, 'Octagonal Blender', 'Octagonal Blender - Machine', NULL, 1.00, 'Nos', 890000.00, 18.00, 890000.00);

-- --------------------------------------------------------

--
-- Table structure for table `sales_orders`
--

CREATE TABLE `sales_orders` (
  `id` int(11) NOT NULL,
  `so_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_address` text DEFAULT NULL,
  `customer_gstin` varchar(15) DEFAULT NULL,
  `customer_contact` varchar(20) DEFAULT NULL,
  `quotation_id` int(11) DEFAULT NULL,
  `quotation_number` varchar(50) DEFAULT NULL,
  `so_date` date NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `status` enum('draft','confirmed','processing','shipped','delivered') DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `final_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales_orders`
--

INSERT INTO `sales_orders` (`id`, `so_number`, `customer_id`, `customer_name`, `customer_address`, `customer_gstin`, `customer_contact`, `quotation_id`, `quotation_number`, `so_date`, `delivery_date`, `status`, `notes`, `total_amount`, `discount_percentage`, `discount_amount`, `final_total`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 'SO-2025-00001', 6, 'ABC Chemical Suppliers Pvt Ltd', '123 Industrial Area, Chemical Park', '27ABCCS1234A1Z5', '+91 9876543210', 3, 'QUO-2025-00001', '2025-09-01', NULL, 'draft', '', 890000.00, 0.00, 0.00, 890000.00, 1, '2025-09-01 06:52:43', '2025-09-03 17:45:40'),
(3, 'SO-2025-00002', 2, 'ABC Pharmaceuticals Ltd', '683 SANJAY ENACLAVE', '', '+91 9876543210', 4, 'QUO-2025-00002', '2025-09-03', NULL, 'draft', '', 890000.00, 0.00, 0.00, 890000.00, 1, '2025-09-03 17:46:39', '2025-09-03 17:46:39');

-- --------------------------------------------------------

--
-- Table structure for table `sales_order_items`
--

CREATE TABLE `sales_order_items` (
  `id` int(11) NOT NULL,
  `so_id` int(11) NOT NULL,
  `item_type` enum('machine','spare') NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `hsn_code` varchar(20) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit` varchar(20) DEFAULT 'Nos',
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gst_rate` decimal(5,2) DEFAULT 18.00,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sl_no` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales_order_items`
--

INSERT INTO `sales_order_items` (`id`, `so_id`, `item_type`, `item_id`, `item_name`, `description`, `hsn_code`, `quantity`, `unit`, `unit_price`, `rate`, `gst_rate`, `amount`, `total_price`, `sl_no`, `created_at`, `updated_at`) VALUES
(5, 2, 'machine', 3, 'Octagonal Blender - Machine', 'Octagonal Blender - Machine', '', 1, 'Nos', 890000.00, 890000.00, 18.00, 890000.00, 890000.00, 0, '2025-09-03 17:46:28', '2025-09-03 17:46:28'),
(7, 3, 'machine', 3, 'Octagonal Blender - Machine', 'Octagonal Blender - Machine', '', 1, 'Nos', 890000.00, 890000.00, 18.00, 890000.00, 890000.00, 0, '2025-09-03 17:50:34', '2025-09-03 17:50:34');

-- --------------------------------------------------------

--
-- Table structure for table `spares`
--

CREATE TABLE `spares` (
  `id` int(11) NOT NULL,
  `part_name` varchar(255) NOT NULL,
  `part_code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `machine_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `spares`
--

INSERT INTO `spares` (`id`, `part_name`, `part_code`, `description`, `price`, `machine_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Punch &amp;amp;amp;amp;amp;amp; Die Set - 8mm Round', 'PD-8MM', '', 0.00, NULL, 1, '2025-08-28 12:31:21', '2025-09-04 12:11:42'),
(3, 'Sealing Roller', 'SR-001', 'Heat sealing roller for blister packing', 12000.00, NULL, 1, '2025-08-28 12:31:21', '2025-08-28 12:31:21'),
(4, 'Impeller Blade Set', 'IB-RMG500', 'Three-blade impeller for RMG-500', 25000.00, 1, 1, '2025-08-28 12:31:21', '2025-08-28 12:31:21'),
(5, 'Chopper Blade', 'CB-RMG500', 'Four-blade chopper for RMG-500', 18000.00, 1, 1, '2025-08-28 12:31:21', '2025-08-28 12:31:21'),
(6, 'Filter Bag - PTFE', 'FB-FBD500', 'PTFE filter bag for FBD-500', 22000.00, 2, 1, '2025-08-28 12:31:21', '2025-08-28 12:31:21'),
(7, 'Silicon Moulded Sieve', 'SMS-48', 'For 48 inch vibro sifter, food grade', 18500.00, 4, 1, '2025-08-28 12:31:21', '2025-08-28 12:31:21'),
(8, 'Stator Rotor Set', 'SRS-CM500', 'Stator rotor assembly for colloid mill', 35000.00, 5, 1, '2025-08-28 12:31:21', '2025-08-28 12:31:21');

-- --------------------------------------------------------

--
-- Table structure for table `spare_prices`
--

CREATE TABLE `spare_prices` (
  `id` int(11) NOT NULL,
  `spare_id` int(11) NOT NULL,
  `price_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valid_from` date NOT NULL,
  `valid_to` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `price_master_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `full_name`, `email`, `profile_picture`, `is_admin`, `is_active`, `last_login`, `updated_at`, `password`, `created_at`) VALUES
(1, 'admin', 'System Administrator', 'admin@pharmamachinery.com', NULL, 1, 1, '2025-09-04 09:38:39', '2025-09-04 09:38:39', '$2y$10$8cY.HsIujamqhApvjl9RwumZGhUdH0BRtAn8KAwPIx/.Bq33dBvBy', '2025-08-28 12:31:21'),
(3, 'manager1', 'ASD', 'manager@pharmamachinery.com', NULL, 0, 1, NULL, '2025-09-01 05:58:16', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-08-29 08:31:45'),
(4, 'operator1', 'Jane Operator', 'operator@pharmamachinery.com', NULL, 0, 1, NULL, '2025-08-29 08:31:45', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-08-29 08:31:45'),
(5, 'viewer1', 'Bob Viewer', 'viewer@pharmamachinery.com', NULL, 0, 1, NULL, '2025-08-29 08:31:45', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-08-29 08:31:45');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `user_id`, `role_id`, `assigned_by`, `created_at`) VALUES
(1, 1, 1, 1, '2025-08-29 08:31:45'),
(3, 4, 4, 1, '2025-08-29 08:31:45'),
(4, 5, 5, 1, '2025-08-29 08:31:45'),
(17, 3, 4, 1, '2025-09-01 05:58:24');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `company_info`
--
ALTER TABLE `company_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_name` (`company_name`),
  ADD UNIQUE KEY `unique_cin` (`cin`),
  ADD UNIQUE KEY `unique_gst` (`gst`);

--
-- Indexes for table `credit_notes`
--
ALTER TABLE `credit_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `credit_note_number` (`credit_note_number`),
  ADD KEY `idx_credit_notes_customer_id` (`customer_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_name` (`company_name`),
  ADD UNIQUE KEY `unique_gst_no` (`gst_no`);

--
-- Indexes for table `debit_notes`
--
ALTER TABLE `debit_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `debit_note_number` (`debit_note_number`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `machines`
--
ALTER TABLE `machines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `unique_part_code` (`part_code`);

--
-- Indexes for table `machine_features`
--
ALTER TABLE `machine_features`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_machine_feature` (`machine_id`,`feature_name`);

--
-- Indexes for table `machine_feature_prices`
--
ALTER TABLE `machine_feature_prices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_machine_feature_date` (`machine_id`,`feature_name`,`valid_from`,`valid_to`),
  ADD KEY `idx_machine_feature_active` (`machine_id`,`feature_name`,`is_active`),
  ADD KEY `idx_date_range` (`valid_from`,`valid_to`),
  ADD KEY `price_master_id` (`price_master_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_permission_name` (`name`);

--
-- Indexes for table `price_master`
--
ALTER TABLE `price_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_machine_date_range` (`machine_id`,`valid_from`,`valid_to`);

--
-- Indexes for table `purchase_invoices`
--
ALTER TABLE `purchase_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pi_number` (`pi_number`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_purchase_order_id` (`purchase_order_id`),
  ADD KEY `idx_pi_date` (`pi_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `purchase_invoice_items`
--
ALTER TABLE `purchase_invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pi_id` (`pi_id`),
  ADD KEY `idx_item_type` (`item_type`),
  ADD KEY `idx_item_id` (`item_id`),
  ADD KEY `idx_machine_id` (`machine_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_po_number` (`po_number`),
  ADD KEY `fk_purchase_orders_sales_order` (`sales_order_id`),
  ADD KEY `fk_purchase_orders_quotation` (`quotation_id`),
  ADD KEY `fk_purchase_orders_vendor` (`vendor_id`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_po_id` (`po_id`);

--
-- Indexes for table `quotations`
--
ALTER TABLE `quotations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_quotation_number` (`quotation_number`),
  ADD KEY `idx_customer_id` (`customer_id`);

--
-- Indexes for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_quotation_sl_no` (`quotation_id`,`sl_no`);

--
-- Indexes for table `quotation_machine_features`
--
ALTER TABLE `quotation_machine_features`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quotation_item_id` (`quotation_item_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_name` (`name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_permission` (`role_id`,`permission_id`),
  ADD KEY `idx_permission_id` (`permission_id`);

--
-- Indexes for table `sales_invoices`
--
ALTER TABLE `sales_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `fk_sales_invoices_customer` (`customer_id`);

--
-- Indexes for table `sales_invoice_items`
--
ALTER TABLE `sales_invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `sales_orders`
--
ALTER TABLE `sales_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_so_number` (`so_number`),
  ADD KEY `fk_sales_orders_quotation` (`quotation_id`),
  ADD KEY `fk_sales_orders_customer` (`customer_id`);

--
-- Indexes for table `sales_order_items`
--
ALTER TABLE `sales_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_so_id` (`so_id`);

--
-- Indexes for table `spares`
--
ALTER TABLE `spares`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_part_code` (`part_code`),
  ADD KEY `idx_machine_id` (`machine_id`);

--
-- Indexes for table `spare_prices`
--
ALTER TABLE `spare_prices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_spare_dates` (`spare_id`,`valid_from`,`valid_to`),
  ADD KEY `spare_id` (`spare_id`),
  ADD KEY `price_id` (`price_id`),
  ADD KEY `valid_dates` (`valid_from`,`valid_to`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `idx_spare_prices_active_dates` (`spare_id`,`is_active`,`valid_from`,`valid_to`),
  ADD KEY `price_master_id` (`price_master_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `unique_email` (`email`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_role` (`user_id`,`role_id`),
  ADD KEY `idx_role_id` (`role_id`),
  ADD KEY `idx_assigned_by` (`assigned_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `company_info`
--
ALTER TABLE `company_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `credit_notes`
--
ALTER TABLE `credit_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `debit_notes`
--
ALTER TABLE `debit_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `machines`
--
ALTER TABLE `machines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `machine_features`
--
ALTER TABLE `machine_features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `machine_feature_prices`
--
ALTER TABLE `machine_feature_prices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=165;

--
-- AUTO_INCREMENT for table `price_master`
--
ALTER TABLE `price_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_invoices`
--
ALTER TABLE `purchase_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `purchase_invoice_items`
--
ALTER TABLE `purchase_invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotation_items`
--
ALTER TABLE `quotation_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotation_machine_features`
--
ALTER TABLE `quotation_machine_features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=845;

--
-- AUTO_INCREMENT for table `sales_invoices`
--
ALTER TABLE `sales_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sales_invoice_items`
--
ALTER TABLE `sales_invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `sales_orders`
--
ALTER TABLE `sales_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sales_order_items`
--
ALTER TABLE `sales_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `spares`
--
ALTER TABLE `spares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `spare_prices`
--
ALTER TABLE `spare_prices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `credit_notes`
--
ALTER TABLE `credit_notes`
  ADD CONSTRAINT `fk_credit_notes_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `machine_features`
--
ALTER TABLE `machine_features`
  ADD CONSTRAINT `machine_features_ibfk_1` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `machine_feature_prices`
--
ALTER TABLE `machine_feature_prices`
  ADD CONSTRAINT `machine_feature_prices_ibfk_1` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `machine_feature_prices_ibfk_2` FOREIGN KEY (`price_master_id`) REFERENCES `price_master` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `machine_feature_prices_ibfk_3` FOREIGN KEY (`price_master_id`) REFERENCES `price_master` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `price_master`
--
ALTER TABLE `price_master`
  ADD CONSTRAINT `fk_price_master_machine` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `purchase_invoice_items`
--
ALTER TABLE `purchase_invoice_items`
  ADD CONSTRAINT `fk_purchase_invoice_items_pi_id` FOREIGN KEY (`pi_id`) REFERENCES `purchase_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `fk_purchase_orders_quotation` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_purchase_orders_sales_order` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_purchase_orders_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `fk_poi_purchase_order` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quotations`
--
ALTER TABLE `quotations`
  ADD CONSTRAINT `fk_quotations_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD CONSTRAINT `fk_quotation_items_quotation` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quotation_machine_features`
--
ALTER TABLE `quotation_machine_features`
  ADD CONSTRAINT `quotation_machine_features_ibfk_1` FOREIGN KEY (`quotation_item_id`) REFERENCES `quotation_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sales_invoices`
--
ALTER TABLE `sales_invoices`
  ADD CONSTRAINT `fk_sales_invoices_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `sales_invoice_items`
--
ALTER TABLE `sales_invoice_items`
  ADD CONSTRAINT `sales_invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_orders`
--
ALTER TABLE `sales_orders`
  ADD CONSTRAINT `fk_sales_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sales_orders_quotation` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `sales_order_items`
--
ALTER TABLE `sales_order_items`
  ADD CONSTRAINT `fk_soi_sales_order` FOREIGN KEY (`so_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `spares`
--
ALTER TABLE `spares`
  ADD CONSTRAINT `fk_spares_machine` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `spare_prices`
--
ALTER TABLE `spare_prices`
  ADD CONSTRAINT `spare_prices_ibfk_1` FOREIGN KEY (`price_master_id`) REFERENCES `price_master` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `spare_prices_ibfk_2` FOREIGN KEY (`price_master_id`) REFERENCES `price_master` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `spare_prices_price_id_foreign` FOREIGN KEY (`price_id`) REFERENCES `price_master` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `spare_prices_spare_id_foreign` FOREIGN KEY (`spare_id`) REFERENCES `spares` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `fk_user_roles_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
