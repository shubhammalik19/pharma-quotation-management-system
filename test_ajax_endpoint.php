<?php
// Test the AJAX endpoint directly
include_once 'common/conn.php';

// Simulate session for testing
$_SESSION['user_id'] = 1;

// Set machine ID for testing
$_GET['machine_id'] = 12; // We know this machine has features

// Include the AJAX file
include 'ajax/get_machine_features_with_pricing.php';
?>
