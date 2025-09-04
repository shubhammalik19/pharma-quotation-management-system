<?php
session_start();
include_once 'common/functions.php';

// Test setting session messages
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'success':
            setSuccessMessage('Test success message - Customer created successfully!');
            break;
        case 'error':
            setErrorMessage('Test error message - Something went wrong!');
            break;
    }
    
    // Use JavaScript redirect instead of header redirect
    echo "<script>window.location.href = 'test_messages.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Messages</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-icons.css" rel="stylesheet">
    <script src="assets/js/jquery-3.7.1.min.js"></script>
</head>
<body>
    <div class="container mt-4">
        <h2>Test Session Messages</h2>
        
        <!-- Display session messages -->
        <?php echo getAllMessages(); ?>
        
        <div class="mt-3">
            <a href="?action=success" class="btn btn-success">Test Success Message</a>
            <a href="?action=error" class="btn btn-danger">Test Error Message</a>
        </div>
        
        <div class="mt-4">
            <h4>How it works:</h4>
            <ul>
                <li>Click a button to set a session message</li>
                <li>Page redirects to show the message</li>
                <li>Message is displayed once and then cleared from session</li>
                <li>Page reloads after 2 seconds if there are messages</li>
            </ul>
        </div>
    </div>
    
    <script>
    $(document).ready(function() {
        // Auto-reload if there are session messages
        if ($('.alert-success, .alert-danger').length > 0) {
            setTimeout(function() {
                window.location.href = window.location.pathname;
            }, 2500);
        }
    });
    </script>
</body>
</html>
