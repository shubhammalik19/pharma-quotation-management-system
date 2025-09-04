<?php
// ajax/send_sales_order_email.php
declare(strict_types=1);

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../common/conn.php';
    require_once __DIR__ . '/../common/functions.php';
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../email/email_service.php';
    require_once __DIR__ . '/../common/pdf_generator.php'; // defines QuotationPDFGenerator (can be reused)

    // --- permissions ---
    if (!hasPermission('sales_orders', 'edit')) {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to send sales orders.']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        exit;
    }

    // --- inputs ---
    $so_id            = (int)($_POST['sales_order_id'] ?? 0);
    $recipient_email  = filter_var((string)($_POST['recipient_email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $additional_emails = (string)($_POST['additional_emails'] ?? '');
    $custom_message   = sanitizeInput((string)($_POST['custom_message'] ?? ''));
    $orientation      = (string)($_POST['orientation'] ?? 'Portrait');

    if ($so_id <= 0 || !filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input. Sales Order ID and a valid recipient email are required.']);
        exit;
    }

    // --- fetch sales order ---
    $sql = "SELECT so.*, c.company_name, c.email AS customer_email
            FROM sales_orders so
            JOIN customers c ON so.customer_id = c.id
            WHERE so.id = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $so_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if (!$res || $res->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Sales order not found.']);
        exit;
    }
    $sales_order = $res->fetch_assoc();

    // --- generate PDF using QuotationPDFGenerator (reuse for consistency) ---
    $generator = new QuotationPDFGenerator();
    $genInfo   = $generator->generateQuotationPDF($so_id, ['orientation' => $orientation]); 
    // 👉 You should create docs/print_sales_order.php similar to print_quotation.php
    // For now, it assumes that file exists

    $pdf_path = $genInfo['path'];
    $filename = $genInfo['filename'];

    if (!is_file($pdf_path) || filesize($pdf_path) <= 0) {
        throw new RuntimeException('PDF was not created or is empty.');
    }

    // --- recipients ---
    $recipients = [];
    $recipients[] = ['email' => $recipient_email, 'name' => (string)($sales_order['company_name'] ?? '')];

    if ($additional_emails !== '') {
        $parts = preg_split('/[,;\s]+/', $additional_emails) ?: [];
        foreach ($parts as $addr) {
            $addr = trim($addr);
            if ($addr !== '' && filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = ['email' => $addr, 'name' => ''];
            }
        }
    }

    if (empty($recipients)) {
        throw new InvalidArgumentException('No valid recipients to send.');
    }

    // --- subject ---
    $company       = getCompanyDetails();
    $company_name  = $company['name'] ?? 'Pharma Machinery Systems';
    $so_number     = $sales_order['so_number'] ?? ('SO-' . $so_id);
    $email_subject = "Sales Order {$so_number} from {$company_name}";

    // --- send ---
    $email_service = new EmailService();
    
    $sent_to   = [];
    $failed_to = [];

    foreach ($recipients as $rcpt) {
        $ok = $email_service->sendEmail(
            $rcpt['email'],
            $email_subject,
            "<p>Dear " . htmlspecialchars($sales_order['company_name']) . ",</p>
             <p>Please find attached your Sales Order <strong>{$so_number}</strong>.</p>
             <p>" . nl2br(htmlspecialchars($custom_message)) . "</p>
             <p>Regards,<br>{$company_name}</p>",
            [ $pdf_path ]
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

    // --- log into email_logs ---
    if (!empty($sent_to) || !empty($failed_to)) {
        $log_sql = "INSERT INTO email_logs (entity_type, entity_id, recipient_emails, subject, sent_by, sent_at)
                    VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($log_sql);
        $all_recipients = implode(', ', array_merge($sent_to, $failed_to));
        $entity_type = 'sales_order';
        $stmt->bind_param(
            "sissi",
            $entity_type,
            $so_id,
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
    error_log('[send_sales_order_email] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Error while sending sales order: ' . $e->getMessage(),
    ]);
    exit;
}
