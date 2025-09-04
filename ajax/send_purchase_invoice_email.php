<?php

include '../common/conn.php';
include '../common/functions.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!hasPermission('purchase_invoices', 'email')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$purchase_invoice_id = intval($_POST['purchase_invoice_id'] ?? 0);
$recipient_email = sanitizeInput($_POST['recipient_email'] ?? '');
$additional_emails = sanitizeInput($_POST['additional_emails'] ?? '');
$custom_message = sanitizeInput($_POST['custom_message'] ?? '');

if ($purchase_invoice_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid purchase invoice ID']);
    exit;
}

if (empty($recipient_email) || !filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Valid recipient email is required']);
    exit;
}

try {
    // Get purchase invoice details
    $sql = "SELECT pi.*, c.company_name as vendor_company 
            FROM purchase_invoices pi 
            LEFT JOIN customers c ON pi.vendor_id = c.id 
            WHERE pi.id = $purchase_invoice_id";
    
    $result = $conn->query($sql);
    
    if (!$result || $result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Purchase invoice not found']);
        exit;
    }
    
    $pi = $result->fetch_assoc();
    
    // Prepare email data
    $email_data = [
        'entity_type' => 'purchase_invoice',
        'entity_id' => $purchase_invoice_id,
        'recipient_email' => $recipient_email,
        'additional_emails' => $additional_emails,
        'custom_message' => $custom_message,
        'subject' => 'Purchase Invoice - ' . $pi['pi_number'],
        'entity_data' => $pi
    ];
    
    // Include email service
    include '../email/email_service.php';
    
    // Send email
    $result = sendEntityEmail($email_data);
    
    if ($result['success']) {
        // Log the email
        $all_recipients = $recipient_email;
        if (!empty($additional_emails)) {
            $all_recipients .= ', ' . $additional_emails;
        }
        
        $log_sql = "INSERT INTO email_logs (entity_type, entity_id, recipient_emails, subject, sent_by) 
                    VALUES ('purchase_invoice', $purchase_invoice_id, '$all_recipients', '{$email_data['subject']}', {$_SESSION['user_id']})";
        $conn->query($log_sql);
        
        echo json_encode(['success' => true, 'message' => 'Purchase invoice email sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Failed to send email']);
    }
    
} catch (Exception $e) {
    error_log("Error in send_purchase_invoice_email.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Email service error occurred']);
}
?>
