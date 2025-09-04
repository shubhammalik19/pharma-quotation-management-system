<?php
// ajax/send_quotation_email.php
declare(strict_types=1);

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../common/conn.php';
    require_once __DIR__ . '/../common/functions.php';
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../email/email_service.php';
    require_once __DIR__ . '/../common/pdf_generator.php'; // defines QuotationPDFGenerator

    if (!hasPermission('quotations', 'edit')) {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to send quotations.']);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        exit;
    }

    $quotation_id      = (int)($_POST['quotation_id'] ?? 0);
    $recipient_email   = filter_var((string)($_POST['recipient_email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $additional_emails = (string)($_POST['additional_emails'] ?? '');
    $custom_message    = sanitizeInput((string)($_POST['custom_message'] ?? ''));
    $orientation       = (string)($_POST['orientation'] ?? 'Portrait');

    if ($quotation_id <= 0 || !filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input. Quotation ID and a valid recipient email are required.']);
        exit;
    }

    $sql = "SELECT q.*, c.company_name, c.email AS customer_email
            FROM quotations q
            JOIN customers c ON q.customer_id = c.id
            WHERE q.id = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $quotation_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Quotation not found.']);
        exit;
    }
    $quotation = $res->fetch_assoc();

    $generator = new QuotationPDFGenerator();
    $genInfo   = $generator->generateQuotationPDF($quotation_id, ['orientation' => $orientation]);
    $pdf_path  = $genInfo['path'];
    $filename  = $genInfo['filename'];

    if (!is_file($pdf_path) || filesize($pdf_path) <= 0) {
        throw new RuntimeException('PDF was not created or is empty.');
    }

    $recipients = [];
    $recipients[] = ['email' => $recipient_email, 'name' => (string)($quotation['company_name'] ?? '')];
    if ($additional_emails !== '') {
        $parts = preg_split('/[,;\s]+/', $additional_emails) ?: [];
        foreach ($parts as $addr) {
            $addr = trim($addr);
            if ($addr !== '' && filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = ['email' => $addr, 'name' => ''];
            }
        }
    }

    $company       = getCompanyDetails();
    $company_name  = $company['name'] ?? 'Pharma Machinery Systems';
    $quote_number  = $quotation['quotation_number'] ?? ('Q-' . $quotation_id);
    $email_subject = "Quotation {$quote_number} from {$company_name}";

    $email_service = new EmailService();
    $sent_to   = [];
    $failed_to = [];

    foreach ($recipients as $rcpt) {
        $ok = $email_service->sendQuotationEmail(
            $quotation,
            $pdf_path,
            $rcpt['email'],
            $rcpt['name'],
            $custom_message,
            false,
            $email_subject
        );

        if (is_array($ok) && !empty($ok['success'])) {
            $sent_to[] = $rcpt['email'];
        } else {
            $failed_to[] = $rcpt['email'];
        }
    }

    $success = !empty($sent_to);
    $msgParts = [];
    if (!empty($sent_to)) {
        $msgParts[] = 'Successfully sent to: ' . implode(', ', $sent_to) . '.';
    }
    if (!empty($failed_to)) {
        $msgParts[] = 'Failed to send to: ' . implode(', ', $failed_to) . '.';
    }
    if (!$success && empty($msgParts)) {
        $msgParts[] = 'Failed to send any emails. Please check SMTP settings or email addresses.';
    }

    // --- log to email_logs ---
    if (!empty($sent_to) || !empty($failed_to)) {
        $log_sql = "INSERT INTO email_logs (entity_type, entity_id, recipient_emails, subject, sent_by, sent_at)
                    VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($log_sql);
        $all_recipients = implode(', ', array_merge($sent_to, $failed_to));
        $entity_type = 'quotation';
        $stmt->bind_param(
            "sissi",
            $entity_type,
            $quotation_id,
            $all_recipients,
            $email_subject,
            $_SESSION['user_id']
        );
        $stmt->execute();
    }

    if (is_file($pdf_path)) {
        @unlink($pdf_path);
    }
    $generator->cleanup(24);

    echo json_encode([
        'success' => $success,
        'message' => implode(' ', $msgParts),
    ]);
    exit;

} catch (Throwable $e) {
    error_log('[send_quotation_email] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Error while sending quotation: ' . $e->getMessage(),
    ]);
    exit;
}
