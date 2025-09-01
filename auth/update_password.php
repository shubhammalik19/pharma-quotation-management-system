<?php
require_once 'common/conn.php';

// Update admin password to plain text
$sql = "UPDATE users SET password = 'admin123' WHERE username = 'admin'";
if ($conn->query($sql) === TRUE) {
    echo "Password updated successfully! Now you can login with:<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br><br>";
    echo '<a href="login.php">Go to Login</a>';
} else {
    echo "Error updating password: " . $conn->error;
}

$conn->close();
?>
