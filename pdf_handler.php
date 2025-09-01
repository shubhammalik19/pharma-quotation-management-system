<?php
// Enable error reporting for debugging



require_once __DIR__ . '/common/conn.php';
require_once __DIR__ . '/common/pdf_generator.php';
require_once __DIR__ . '/email/email_service.php';

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $action = $_REQUEST['action'] ?? '';
    
    switch ($action) {
        case 'generate':
            handlePDFGeneration();
            break;
            
        case 'email':
            handleEmailSending();
            break;
            
        case 'bulk_email':
            handleBulkEmailSending();
            break;
            
        case 'download':
            handlePDFDownload();
            break;
            
        case 'test':
            handleTest();
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    if ($action === 'generate' || $action === 'download') {
        // For PDF generation/download, show error page
        echo "<html><body><h1>Error</h1><p>" . htmlspecialchars($e->getMessage()) . "</p></body></html>";
    } else {
        // For AJAX requests, return JSON error
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Handle PDF Generation
 */
function handlePDFGeneration() {
    $quotation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$quotation_id) {
        throw new Exception('Invalid quotation ID');
    }
    
    // Get quotation info for filename
    global $conn;
    $quotation_sql = "SELECT quotation_number FROM quotations WHERE id = $quotation_id";
    $result = $conn->query($quotation_sql);
    
    if ($result->num_rows === 0) {
        throw new Exception('Quotation not found');
    }
    
    $quotation = $result->fetch_assoc();
    
    // Generate PDF
    $pdf_generator = new QuotationPDFGenerator();
    $pdf_info = $pdf_generator->generateQuotationPDF($quotation_id);
    
    // Set download filename
    $download_filename = 'Quotation_' . $quotation['quotation_number'] . '_' . date('Y-m-d') . '.pdf';
    
    // Download the PDF
    $pdf_generator->downloadPDF($pdf_info['path'], $download_filename);
}

/**
 * Handle Bulk Email Sending
 */
function handleBulkEmailSending() {
    $quotation_id = isset($_POST['quotation_id']) ? (int)$_POST['quotation_id'] : 0;
    $customer_email = $_POST['customer_email'] ?? '';
    $customer_name = $_POST['customer_name'] ?? '';
    $additional_emails = $_POST['additional_emails'] ?? [];
    $additional_names = $_POST['additional_names'] ?? [];
    $custom_message = $_POST['custom_message'] ?? '';
    $email_subject = $_POST['email_subject'] ?? '';
    $send_to_self = isset($_POST['send_to_self']);
    $urgent_flag = isset($_POST['urgent_flag']);
    
    if (!$quotation_id) {
        throw new Exception('Invalid quotation ID');
    }
    
    // Build recipients list
    $recipients = [];
    
    // Add customer email if provided
    if ($customer_email && filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        $recipients[] = [
            'email' => $customer_email,
            'name' => $customer_name,
            'type' => 'customer'
        ];
    }
    
    // Add additional emails
    if (is_array($additional_emails)) {
        foreach ($additional_emails as $index => $email) {
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = [
                    'email' => $email,
                    'name' => $additional_names[$index] ?? '',
                    'type' => 'additional'
                ];
            }
        }
    }
    
    // Add self if requested
    if ($send_to_self) {
        $recipients[] = [
            'email' => 'shubhammalik19@gmail.com',
            'name' => 'Self Copy',
            'type' => 'self'
        ];
    }
    
    if (empty($recipients)) {
        throw new Exception('At least one valid email recipient is required');
    }
    
    // Get quotation details
    global $conn;
    $quotation_sql = "SELECT q.*, c.company_name, c.contact_person, c.phone, c.email, c.address 
                      FROM quotations q 
                      LEFT JOIN customers c ON q.customer_id = c.id 
                      WHERE q.id = $quotation_id";
    $result = $conn->query($quotation_sql);
    
    if ($result->num_rows === 0) {
        throw new Exception('Quotation not found');
    }
    
    $quotation_data = $result->fetch_assoc();
    $quotation_data['customer_name'] = $quotation_data['contact_person'] ?: $quotation_data['company_name'];
    
    // Generate PDF once
    $pdf_generator = new QuotationPDFGenerator();
    $pdf_info = $pdf_generator->generateQuotationPDF($quotation_id);
    
    // Initialize email service
    $email_service = new EmailService();
    
    // Override subject if provided
    if ($email_subject) {
        $email_service->updateConfig(['email_subject' => $email_subject]);
    }
    
    $sent_count = 0;
    $send_results = [];
    $errors = [];
    
    // Send emails to all recipients
    foreach ($recipients as $recipient) {
        try {
            // Customize message based on recipient type
            $personalized_message = $custom_message;
            if ($recipient['type'] === 'self') {
                $personalized_message = "[COPY] This is a copy of the quotation sent to the customer.\n\n" . $custom_message;
            }
            
            $result = $email_service->sendQuotationEmail(
                $quotation_data, 
                $pdf_info['path'], 
                $recipient['email'], 
                $recipient['name'], 
                $personalized_message,
                $urgent_flag
            );
            
            if ($result['success']) {
                $sent_count++;
                $send_results[] = "✓ {$recipient['email']} ({$recipient['type']})";
            } else {
                $errors[] = "✗ {$recipient['email']}: {$result['message']}";
            }
            
            // Small delay between emails to avoid rate limiting
            usleep(500000); // 0.5 second delay
            
        } catch (Exception $e) {
            $errors[] = "✗ {$recipient['email']}: {$e->getMessage()}";
        }
    }
    
    // Clean up PDF file
    if (file_exists($pdf_info['path'])) {
        unlink($pdf_info['path']);
    }
    
    // Prepare response
    $response = [
        'success' => $sent_count > 0,
        'sent_count' => $sent_count,
        'total_recipients' => count($recipients),
        'details' => array_merge($send_results, $errors)
    ];
    
    if ($sent_count === 0) {
        $response['message'] = 'No emails were sent successfully';
    } elseif (count($errors) > 0) {
        $response['message'] = "Partial success: $sent_count emails sent, " . count($errors) . " failed";
    } else {
        $response['message'] = "All $sent_count emails sent successfully";
    }
    
    echo json_encode($response);
}

/**
 * Handle Email Sending
 */
function handleEmailSending() {
    $quotation_id = isset($_POST['quotation_id']) ? (int)$_POST['quotation_id'] : 0;
    $recipient_email = $_POST['recipient_email'] ?? '';
    $recipient_name = $_POST['recipient_name'] ?? '';
    $custom_message = $_POST['custom_message'] ?? '';
    
    if (!$quotation_id) {
        throw new Exception('Invalid quotation ID');
    }
    
    if (!$recipient_email || !filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Valid recipient email is required');
    }
    
    // Get quotation details
    global $conn;
    $quotation_sql = "SELECT q.*, c.company_name, c.contact_person, c.phone, c.email, c.address 
                      FROM quotations q 
                      LEFT JOIN customers c ON q.customer_id = c.id 
                      WHERE q.id = $quotation_id";
    $result = $conn->query($quotation_sql);
    
    if ($result->num_rows === 0) {
        throw new Exception('Quotation not found');
    }
    
    $quotation_data = $result->fetch_assoc();
    
    // Add customer name for email template
    $quotation_data['customer_name'] = $quotation_data['contact_person'] ?: $quotation_data['company_name'];
    
    // Generate PDF
    $pdf_generator = new QuotationPDFGenerator();
    $pdf_info = $pdf_generator->generateQuotationPDF($quotation_id);
    
    // Send email
    $email_service = new EmailService();
    $result = $email_service->sendQuotationEmail(
        $quotation_data, 
        $pdf_info['path'], 
        $recipient_email, 
        $recipient_name, 
        $custom_message,
        false, // not urgent
        '' // no custom subject
    );
    
    // Clean up PDF file after sending
    if (file_exists($pdf_info['path'])) {
        unlink($pdf_info['path']);
    }
    
    echo json_encode($result);
}

/**
 * Handle PDF Download (alternative method)
 */
function handlePDFDownload() {
    $quotation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$quotation_id) {
        throw new Exception('Invalid quotation ID');
    }
    
    // Get quotation info for filename
    global $conn;
    $quotation_sql = "SELECT quotation_number FROM quotations WHERE id = $quotation_id";
    $result = $conn->query($quotation_sql);
    
    if ($result->num_rows === 0) {
        throw new Exception('Quotation not found');
    }
    
    $quotation = $result->fetch_assoc();
    
    // Generate PDF
    $pdf_generator = new QuotationPDFGenerator();
    $pdf_info = $pdf_generator->generateQuotationPDF($quotation_id);
    
    // Stream PDF to browser
    $download_filename = 'Quotation_' . $quotation['quotation_number'] . '_' . date('Y-m-d') . '.pdf';
    $pdf_generator->streamPDF($pdf_info['path'], $download_filename);
}

/**
 * Handle Test Request
 */
function handleTest() {
    echo json_encode([
        'success' => true,
        'message' => 'PDF Handler is working correctly',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'extensions' => [
            'curl' => extension_loaded('curl'),
            'openssl' => extension_loaded('openssl'),
            'mbstring' => extension_loaded('mbstring')
        ]
    ]);
}
?>
