<?php
include '../common/conn.php';
include '../common/functions.php';
include '../email/email_service.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$po_id = intval($_POST['purchase_order_id'] ?? 0);
$recipient_email = trim($_POST['recipient_email'] ?? '');
$additional_emails = trim($_POST['additional_emails'] ?? '');
$custom_message = trim($_POST['custom_message'] ?? '');

if ($po_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid purchase order ID']);
    exit;
}

if (empty($recipient_email)) {
    echo json_encode(['success' => false, 'message' => 'Recipient email is required']);
    exit;
}

if (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid recipient email address']);
    exit;
}

try {
    // Get purchase order details
    $po_sql = "SELECT po.*, c.company_name as vendor_company, c.email as vendor_email,
               u.full_name as created_by_name, u.email as created_by_email
               FROM purchase_orders po 
               LEFT JOIN customers c ON po.vendor_id = c.id 
               LEFT JOIN users u ON po.created_by = u.id
               WHERE po.id = ?";
    
    $stmt = $conn->prepare($po_sql);
    $stmt->bind_param("i", $po_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Purchase Order not found']);
        exit;
    }
    
    $po = $result->fetch_assoc();
    
    // Prepare email addresses
    $to_emails = [$recipient_email];
    
    if (!empty($additional_emails)) {
        $additional_array = array_map('trim', explode(',', $additional_emails));
        foreach ($additional_array as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $to_emails[] = $email;
            }
        }
    }
    
    // Email subject
    $subject = "Purchase Order: " . $po['po_number'];
    
    // Email body
    $message = "<html><body>";
    $message .= "<h2>Purchase Order: " . htmlspecialchars($po['po_number']) . "</h2>";
    $message .= "<p><strong>Date:</strong> " . date('F j, Y', strtotime($po['po_date'])) . "</p>";
    $message .= "<p><strong>Vendor:</strong> " . htmlspecialchars($po['vendor_name']) . "</p>";
    
    if (!empty($po['due_date'])) {
        $message .= "<p><strong>Due Date:</strong> " . date('F j, Y', strtotime($po['due_date'])) . "</p>";
    }
    
    $message .= "<p><strong>Status:</strong> " . htmlspecialchars(ucwords($po['status'])) . "</p>";
    $message .= "<p><strong>Total Amount:</strong> ₹" . number_format($po['final_total'], 2) . "</p>";
    
    if (!empty($custom_message)) {
        $message .= "<div style='margin: 20px 0; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #007bff;'>";
        $message .= "<h4>Message:</h4>";
        $message .= "<p>" . nl2br(htmlspecialchars($custom_message)) . "</p>";
        $message .= "</div>";
    }
    
    if (!empty($po['notes'])) {
        $message .= "<div style='margin: 20px 0;'>";
        $message .= "<h4>Notes:</h4>";
        $message .= "<p>" . nl2br(htmlspecialchars($po['notes'])) . "</p>";
        $message .= "</div>";
    }
    
    $message .= "<p>Please find the purchase order details above. For any queries, please contact us.</p>";
    $message .= "<p>Best regards,<br>";
    $message .= htmlspecialchars($po['created_by_name'] ?? 'Purchase Team') . "</p>";
    $message .= "</body></html>";
    
    // Send email using the email service
    $email_service = new EmailService();
    $email_result = $email_service->sendEmail($to_emails, $subject, $message);
    
    if ($email_result['success']) {
        // Log the email sending
        $log_sql = "INSERT INTO email_logs (entity_type, entity_id, recipient_emails, subject, sent_by, sent_at) 
                    VALUES ('purchase_order', ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($log_sql);
        $recipient_list = implode(', ', $to_emails);
        $stmt->bind_param("issi", $po_id, $recipient_list, $subject, $_SESSION['user_id']);
        $stmt->execute();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Purchase Order email sent successfully to ' . count($to_emails) . ' recipient(s)!'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to send email: ' . $email_result['message']
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
