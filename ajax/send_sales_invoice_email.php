<?php
// ajax/send_sales_invoice_email.php
require_once '../common/conn.php';
require_once '../common/functions.php';
require_once '../email/email_service.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

if (!hasPermission('sales_invoices', 'read')) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to send sales invoices']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit;
}

$required_fields = ['sales_invoice_id', 'recipient_email'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "Field $field is required"]);
        exit;
    }
}

$invoice_id = (int)$_POST['sales_invoice_id'];
$recipient_email = sanitizeInput($_POST['recipient_email']);
$additional_emails = sanitizeInput($_POST['additional_emails'] ?? '');
$custom_message = sanitizeInput($_POST['custom_message'] ?? '');

// Validate email format
if (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid recipient email format']);
    exit;
}

try {
    // Get invoice details
    $invoice_sql = "SELECT si.*, c.email as customer_email, c.phone as customer_phone
                    FROM sales_invoices si
                    LEFT JOIN customers c ON si.customer_id = c.id
                    WHERE si.id = ?";
    
    $stmt = $conn->prepare($invoice_sql);
    $stmt->bind_param('i', $invoice_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Sales invoice not found']);
        exit;
    }
    
    $invoice = $result->fetch_assoc();
    
    // Generate PDF (assuming you have a PDF generation endpoint)
    $pdf_url = "../docs/print_sales_invoice.php?id=" . $invoice_id;
    $pdf_content = file_get_contents($pdf_url);
    
    if ($pdf_content === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to generate PDF']);
        exit;
    }
    
    // Prepare email recipients
    $recipients = [$recipient_email];
    if (!empty($additional_emails)) {
        $additional_array = array_map('trim', explode(',', $additional_emails));
        $additional_array = array_filter($additional_array, function($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        });
        $recipients = array_merge($recipients, $additional_array);
    }
    
    // Email subject and body
    $subject = "Sales Invoice - " . $invoice['invoice_number'];
    
    $email_body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;'>
            <h2 style='color: #333; margin: 0;'>Sales Invoice</h2>
            <p style='color: #666; margin: 5px 0 0 0;'>Invoice Number: <strong>{$invoice['invoice_number']}</strong></p>
        </div>
        
        <div style='margin-bottom: 20px;'>
            <h3 style='color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px;'>Invoice Details</h3>
            <table style='width: 100%; border-collapse: collapse;'>
                <tr>
                    <td style='padding: 8px 0; border-bottom: 1px solid #eee;'><strong>Customer:</strong></td>
                    <td style='padding: 8px 0; border-bottom: 1px solid #eee;'>{$invoice['customer_name']}</td>
                </tr>
                <tr>
                    <td style='padding: 8px 0; border-bottom: 1px solid #eee;'><strong>Invoice Date:</strong></td>
                    <td style='padding: 8px 0; border-bottom: 1px solid #eee;'>" . formatDate($invoice['invoice_date']) . "</td>
                </tr>
                <tr>
                    <td style='padding: 8px 0; border-bottom: 1px solid #eee;'><strong>Due Date:</strong></td>
                    <td style='padding: 8px 0; border-bottom: 1px solid #eee;'>" . formatDate($invoice['due_date']) . "</td>
                </tr>
                <tr>
                    <td style='padding: 8px 0; border-bottom: 1px solid #eee;'><strong>Total Amount:</strong></td>
                    <td style='padding: 8px 0; border-bottom: 1px solid #eee; color: #28a745; font-weight: bold;'>₹" . number_format($invoice['final_total'] ?? $invoice['total_amount'], 2) . "</td>
                </tr>
                <tr>
                    <td style='padding: 8px 0;'><strong>Status:</strong></td>
                    <td style='padding: 8px 0;'><span style='background-color: #007bff; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;'>" . ucwords($invoice['status']) . "</span></td>
                </tr>
            </table>
        </div>";
    
    if (!empty($custom_message)) {
        $email_body .= "
        <div style='margin-bottom: 20px;'>
            <h3 style='color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px;'>Message</h3>
            <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff;'>
                " . nl2br(htmlspecialchars($custom_message)) . "
            </div>
        </div>";
    }
    
    $email_body .= "
        <div style='margin-bottom: 20px;'>
            <p style='color: #666;'>Please find the detailed sales invoice attached as a PDF document.</p>
        </div>
        
        <div style='border-top: 1px solid #eee; padding-top: 20px; text-align: center; color: #888; font-size: 14px;'>
            <p>This is an automated email from our Sales Invoice Management System.</p>
            <p>If you have any questions, please contact us directly.</p>
        </div>
    </div>";
    
    // Initialize email service
    $emailService = new EmailService();
    
    // Create temporary PDF file
    $temp_dir = '../common/temp';
    if (!is_dir($temp_dir)) {
        mkdir($temp_dir, 0777, true);
    }
    
    $pdf_filename = "Sales_Invoice_" . $invoice['invoice_number'] . ".pdf";
    $pdf_path = $temp_dir . '/' . $pdf_filename;
    
    if (file_put_contents($pdf_path, $pdf_content) === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to create PDF file']);
        exit;
    }
    
    // Send email to all recipients
    $email_sent = false;
    $errors = [];
    
    foreach ($recipients as $email) {
        try {
            $result = $emailService->sendEmail(
                $email,
                $subject,
                $email_body,
                [$pdf_path => $pdf_filename]
            );
            
            if ($result) {
                $email_sent = true;
            } else {
                $errors[] = "Failed to send to: $email";
            }
        } catch (Exception $e) {
            $errors[] = "Error sending to $email: " . $e->getMessage();
        }
    }
    
    // Clean up temporary PDF file
    if (file_exists($pdf_path)) {
        unlink($pdf_path);
    }
    
    if ($email_sent) {
        // Log the email sending activity
        $log_sql = "INSERT INTO email_logs (entity_type, entity_id, recipient_emails, subject, sent_at, sent_by) 
                    VALUES ('sales_invoice', ?, ?, ?, NOW(), ?)";
        $stmt = $conn->prepare($log_sql);
        $recipients_string = implode(', ', $recipients);
        $stmt->bind_param('issi', $invoice_id, $recipients_string, $subject, $_SESSION['user_id']);
        $stmt->execute();
        
        $success_message = "Sales invoice emailed successfully to " . count($recipients) . " recipient(s)";
        if (!empty($errors)) {
            $success_message .= ". Some emails failed: " . implode(', ', $errors);
        }
        
        echo json_encode(['success' => true, 'message' => $success_message]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send emails: ' . implode(', ', $errors)]);
    }
    
} catch (Exception $e) {
    // Clean up temporary PDF file if it exists
    if (isset($pdf_path) && file_exists($pdf_path)) {
        unlink($pdf_path);
    }
    
    error_log("Error sending sales invoice email: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while sending the email']);
}
?>