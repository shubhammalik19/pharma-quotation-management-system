<?php
// Simulate a logged-in session
session_start();
$_SESSION['user_id'] = 1; // Simulate logged in user
$_GET['id'] = 3; // Simulate the request parameter

// Change to ajax directory to get the correct relative paths
chdir('ajax');
include 'get_purchase_invoice_details.php';
?>
