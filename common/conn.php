<?php
# enable error reposting for development
#error_reporting(E_ALL);
#ini_set('display_errors', 1);


/**
 * Database Connection & Global Configuration
 * Simple database configuration with base URL setup
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Base URL Configuration
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$base_url = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);

// Clean up base URL for different directory depths
$current_dir = dirname($_SERVER['SCRIPT_NAME']);
if (strpos($current_dir, '/auth') !== false || 
    strpos($current_dir, '/quotations') !== false || 
    strpos($current_dir, '/email') !== false ||
    strpos($current_dir, '/ajax') !== false) {
    // If in subdirectory, go up one level
    $base_url = $protocol . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['SCRIPT_NAME']));
}

// Remove trailing slash
$base_url = rtrim($base_url, '/');

// Define global constants
define('BASE_URL', $base_url);
define('SITE_NAME', 'Pharma Quotation Management System');
define('VERSION', '2.1.0');

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '12345';
$database = 'quotation_management';

// Create MySQL connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "<br>Please check your database settings.");
}

// Set charset
$conn->set_charset('utf8mb4');

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Helper function for generating URLs
if (!function_exists('url')) {
    function url($path = '') {
        return BASE_URL . '/' . ltrim($path, '/');
    }
}

// Helper function for redirects
if (!function_exists('redirect')) {
    function redirect($path = '') {
        header('Location: ' . url($path));
        exit();
    }
}
?>
