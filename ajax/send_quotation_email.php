<?php
include '../common/conn.php';
include '../common/functions.php';
require_once '../vendor/autoload.php';
require_once '../email/email_service.php';
require_once '../common/pdf_generator.php';

header('Content-Type: application/json');

if (!hasPermission('quotations', 'edit')) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to send quotations.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quotation_id = intval($_POST['quotation_id'] ?? 0);
    $recipient_email = filter_var($_POST['recipient_email'] ?? '', FILTER_SANITIZE_EMAIL);
    $additional_emails = sanitizeInput($_POST['additional_emails'] ?? '');
    $custom_message = sanitizeInput($_POST['custom_message'] ?? '');

    if (!$quotation_id || !filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input. Quotation ID and a valid recipient email are required.']);
        exit;
    }

    $sql = "SELECT q.*, c.company_name, c.email as customer_email 
            FROM quotations q 
            JOIN customers c ON q.customer_id = c.id 
            WHERE q.id = $quotation_id";
    $result = $conn->query($sql);

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Quotation not found.']);
        exit;
    }
    $quotation_data = $result->fetch_assoc();

    $_GET['id'] = $quotation_id;
    ob_start();
    include '../../docs/print_quotation.php';
    $html_content = ob_get_clean();
    
    $pdf_generator = new PdfGenerator();
    $pdf_content = $pdf_generator->generate($html_content);
    
    $upload_dir = __DIR__ . '/../../uploads/quotations/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $pdf_path = $upload_dir . 'quotation_' . $quotation_data['quotation_number'] . '.pdf';
    file_put_contents($pdf_path, $pdf_content);

    $email_service = new EmailService();
    
    $all_recipients = [];
    $all_recipients[] = ['email' => $recipient_email, 'name' => $quotation_data['company_name']];

    if (!empty($additional_emails)) {
        $emails = preg_split('/[,;\\s]+/', $additional_emails);
        foreach ($emails as $email) {
            $email = trim($email);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $all_recipients[] = ['email' => $email, 'name' => ''];
            }
        }
    }
    
    $email_subject = "Quotation " . $quotation_data['quotation_number'] . " from Pharma Machinery Systems";

    $final_result = ['success' => true, 'message' => 'Email(s) sent successfully!'];
    $sent_to = [];
    $failed_to = [];

    foreach ($all_recipients as $recipient) {
        $result = $email_service->sendQuotationEmail(
            $quotation_data,
            $pdf_path,
            $recipient['email'],
            $recipient['name'],
            $custom_message,
            false,
            $email_subject
        );

        if ($result['success']) {
            $sent_to[] = $recipient['email'];
        } else {
            $failed_to[] = $recipient['email'];
        }
    }
    
    if (!empty($sent_to)) {
        $final_result['message'] = 'Successfully sent email to: ' . implode(', ', $sent_to) . '.';
        if(!empty($failed_to)){
            $final_result['message'] .= ' Failed to send to: ' . implode(', ', $failed_to) . '.';
            $final_result['success'] = false;
        }
    } else {
        $final_result['success'] = false;
        $final_result['message'] = 'Failed to send any emails. Please check email settings or recipient addresses.';
    }


    if (file_exists($pdf_path)) {
        unlink($pdf_path);
    }

    echo json_encode($final_result);

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
