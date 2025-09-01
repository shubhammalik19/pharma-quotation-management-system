<?php
session_start();
require_once 'common/conn.php';
require_once 'common/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Access denied');
}

// Get file parameters
$file_type = $_GET['type'] ?? '';
$file_id = (int)($_GET['id'] ?? 0);

if (!$file_type || !$file_id) {
    http_response_code(400);
    die('Invalid parameters');
}

// Handle machine attachments
if ($file_type === 'machine') {
    // Check if user has permission to view machines
    if (!hasPermission('machines', 'view')) {
        http_response_code(403);
        die('Access denied');
    }
    
    // Get machine attachment info
    $stmt = $conn->prepare("SELECT attachment_filename, attachment_path, attachment_type FROM machines WHERE id = ?");
    $stmt->bind_param("i", $file_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $filename = $row['attachment_filename'];
        $filepath = $row['attachment_path'];
        $filetype = $row['attachment_type'];
        
        if ($filename && $filepath && file_exists($filepath)) {
            // Set headers for file download
            header('Content-Type: ' . $filetype);
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            header('Cache-Control: private');
            header('Pragma: private');
            header('Expires: 0');
            
            // Output file
            readfile($filepath);
            exit;
        }
    }
}

// File not found
http_response_code(404);
die('File not found');
?>
