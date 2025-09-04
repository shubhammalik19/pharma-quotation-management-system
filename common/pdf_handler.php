<?php
// /common/pdf_handler.php
declare(strict_types=1);

// Errors for debugging; you can turn display_errors off in prod
#error_reporting(E_ALL);
#ini_set('display_errors', '1');

require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/pdf_generator.php';


checkLogin();

header_remove('X-Powered-By');

$action        = $_GET['action']        ?? $_POST['action']        ?? '';
$quotation_id  = (int)($_GET['id']      ?? $_POST['quotation_id']  ?? 0);

if ($quotation_id <= 0) {
    http_response_code(400);
    die('Invalid quotation ID');
}

function json_out(array $payload, int $code = 200): void {
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code($code);
    }
    echo json_encode($payload);
    exit;
}

try {
    $pdf = new QuotationPDFGenerator();

    switch ($action) {
        case 'generate': {
            $info = $pdf->generateQuotationPDF($quotation_id);
            $pdf->downloadPDF($info['path'], 'quotation_' . $quotation_id . '.pdf');
            break; // unreachable
        }

        case 'view': {
            $info = $pdf->generateQuotationPDF($quotation_id);
            $pdf->streamPDF($info['path'], 'quotation_' . $quotation_id . '.pdf');
            break; // unreachable
        }

        case 'test': {
            $info = $pdf->generateQuotationPDF($quotation_id);
            json_out(['success' => true, 'message' => 'PDF generated', 'filename' => $info['filename'], 'size' => $info['size']]);
            break;
        }

        case 'email': {
            // Inputs from modal
            $toEmail   = trim((string)($_POST['recipient_email'] ?? ''));
            $toName    = trim((string)($_POST['recipient_name']  ?? ''));
            $customMsg = trim((string)($_POST['custom_message']  ?? ''));

            if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                json_out(['success' => false, 'message' => 'Invalid recipient email'], 422);
            }

            // Generate PDF once
            $info = $pdf->generateQuotationPDF($quotation_id);

            // Load lightweight quotation info (for subject/body)
            $q = $conn->query("SELECT quotation_number, quotation_date FROM quotations WHERE id = " . (int)$quotation_id . " LIMIT 1");
            $qrow = $q && $q->num_rows ? $q->fetch_assoc() : ['quotation_number' => ('#' . $quotation_id), 'quotation_date' => date('Y-m-d')];

            $subject = 'Quotation ' . ($qrow['quotation_number'] ?? ('#' . $quotation_id));
            $body    = "Dear " . ($toName !== '' ? $toName : 'Sir/Madam') . ",\n\nPlease find attached the quotation "
                     . ($qrow['quotation_number'] ?? ('#' . $quotation_id))
                     . " dated " . date('d-m-Y', strtotime((string)($qrow['quotation_date'] ?? 'now'))) . ".\n\n";
            if ($customMsg !== '') {
                $body .= $customMsg . "\n\n";
            }
            $body .= "Regards,\n" . (getCompanyDetails()['name'] ?? 'Our Company');

            $sent = false;
            $err  = '';

            // Try PHPMailer if available
            if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
                try {
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    // If you have SMTP creds, set them here
                    // $mail->isSMTP(); $mail->Host='...'; $mail->SMTPAuth=true; $mail->Username='...'; $mail->Password='...'; $mail->SMTPSecure=PHPMailer::ENCRYPTION_STARTTLS; $mail->Port=587;

                    $mail->setFrom(getCompanyDetails()['email'] ?? 'no-reply@quotation.logisticsoftware.in', getCompanyDetails()['name'] ?? 'Quotations');
                    $mail->addAddress($toEmail, $toName);
                    $mail->Subject = $subject;
                    $mail->Body    = $body;
                    $mail->AltBody = $body;
                    $mail->addAttachment($info['path'], 'quotation_' . $quotation_id . '.pdf');

                    $mail->send();
                    $sent = true;
                } catch (\Throwable $e) {
                    $err = 'PHPMailer error: ' . $e->getMessage();
                }
            }

            // Fallback to native mail() with MIME
            if (!$sent) {
                $boundary = md5(uniqid((string)$quotation_id, true));
                $headers  = [];
                $fromMail = getCompanyDetails()['email'] ?? 'no-reply@quotation.logisticsoftware.in';
                $fromName = getCompanyDetails()['name']  ?? 'Quotations';

                $headers[] = 'From: ' . sprintf('"%s" <%s>', addslashes($fromName), $fromMail);
                $headers[] = 'MIME-Version: 1.0';
                $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

                $attachment = chunk_split(base64_encode((string)file_get_contents($info['path'])));

                $message = "--{$boundary}\r\n";
                $message .= "Content-Type: text/plain; charset=\"utf-8\"\r\n\r\n";
                $message .= $body . "\r\n";
                $message .= "--{$boundary}\r\n";
                $message .= "Content-Type: application/pdf; name=\"quotation_{$quotation_id}.pdf\"\r\n";
                $message .= "Content-Transfer-Encoding: base64\r\n";
                $message .= "Content-Disposition: attachment; filename=\"quotation_{$quotation_id}.pdf\"\r\n\r\n";
                $message .= $attachment . "\r\n";
                $message .= "--{$boundary}--";

                $sent = @mail($toEmail, $subject, $message, implode("\r\n", $headers));
                if (!$sent && $err === '') {
                    $err = 'mail() failed';
                }
            }

            if ($sent) {
                json_out(['success' => true, 'message' => 'Email sent successfully']);
            } else {
                json_out(['success' => false, 'message' => $err ?: 'Failed to send email'], 500);
            }
            break;
        }

        default:
            http_response_code(400);
            die('Invalid action: ' . $action);
    }

    $pdf->cleanup();
} catch (Throwable $e) {
    error_log('PDF Handler Error: ' . $e->getMessage());
    if (stripos($action, 'email') !== false || stripos($action, 'test') !== false) {
        json_out(['success' => false, 'message' => $e->getMessage()], 500);
    }
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
}
