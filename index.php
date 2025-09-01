<?php
require_once 'common/conn.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
} else {
    redirect('auth/login.php');
}
?>
