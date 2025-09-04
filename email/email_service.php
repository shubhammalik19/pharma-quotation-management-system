<?php
// email/email_service.php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private PHPMailer $mailer;
    private array $config;
    private ?string $lastDebug = null;

    public function __construct(?array $override = null) {
        $this->mailer = new PHPMailer(true);
        $this->loadConfig($override);
        $this->setupMailer();
    }

    private function loadConfig(?array $override): void {
        // load file config if present
        $fileCfg = [];
        $config_file = __DIR__ . '/email_settings.php';
        if (is_file($config_file)) {
            $fc = include $config_file;
            if (is_array($fc)) { $fileCfg = $fc; }
        }

        // hard defaults (Gmail example)
        $defaults = [
            'smtp_host'     => 'smtp.gmail.com',
            'smtp_port'     => 587,
            'smtp_secure'   => 'tls', // 'tls' or 'ssl' or PHPMailer::ENCRYPTION_STARTTLS / ENCRYPTION_SMTPS
            'smtp_auth'     => true,
            'smtp_username' => 'you@example.com',
            'smtp_password' => 'app-password-here', // use an App Password, never a real password
            'from_email'    => 'you@example.com',
            'from_name'     => 'Pharma Machinery Systems',
            'smtp_debug'    => 0,    // 0|1|2|3|4 (PHPMailer’s levels) – use 2 for server transcript
            'allow_self_signed' => false, // flip to true on hosts with odd cert stores
            'reply_to'      => null, // e.g. 'sales@example.com'
        ];

        $this->config = array_merge($defaults, $fileCfg, $override ?? []);
    }

    private function setupMailer(): void {
        // Core wire-up
        $this->mailer->isSMTP();
        $this->mailer->Host       = (string)$this->config['smtp_host'];
        $this->mailer->SMTPAuth   = (bool)$this->config['smtp_auth'];
        $this->mailer->Username   = (string)$this->config['smtp_username'];
        $this->mailer->Password   = (string)$this->config['smtp_password'];
        $this->mailer->Port       = (int)$this->config['smtp_port'];

        // TLS / SSL selection
        $sec = $this->config['smtp_secure'];
        if ($sec === 'tls' || $sec === PHPMailer::ENCRYPTION_STARTTLS) {
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($sec === 'ssl' || $sec === PHPMailer::ENCRYPTION_SMTPS) {
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            // If you choose SSL, make sure the port is 465
            if ($this->mailer->Port === 587) { $this->mailer->Port = 465; }
        } else {
            // default to STARTTLS on 587
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            if ($this->mailer->Port !== 465 && $this->mailer->Port !== 25) {
                $this->mailer->Port = 587;
            }
        }

        // Charset (fixes ₹ and emoji)
        $this->mailer->CharSet  = 'UTF-8';
        $this->mailer->Encoding = 'base64';

        // Optional cert relax for quirky shared hosts
        if (!empty($this->config['allow_self_signed'])) {
            $this->mailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ];
        }

        // Debug (to PHP error_log)
        $level = 2;(int)$this->config['smtp_debug']; // 0..4
        $this->mailer->SMTPDebug  = $level;
        if ($level > 0) {
            $this->mailer->Debugoutput = function ($str, $level) {
                $line = "SMTP($level): $str";
                error_log($line);
                // keep last few lines in memory for surfaced error
                $this->lastDebug = isset($this->lastDebug) ? ($this->lastDebug . "\n" . $line) : $line;
            };
        }

        // From / Reply-To
        $this->mailer->setFrom((string)$this->config['from_email'], (string)$this->config['from_name']);
        if (!empty($this->config['reply_to'])) {
            $this->mailer->addReplyTo((string)$this->config['reply_to']);
        }

        // HTML content
        $this->mailer->isHTML(true);
    }

    /** Public toggle for debugging at runtime */
    public function setDebugLevel(int $level = 2): void {
        $this->config['smtp_debug'] = $level;
        $this->setupMailer();
    }

    public function lastDebug(): ?string {
        return $this->lastDebug;
    }

    /** Connectivity smoke test */
    public function testConnection(): array {
        try {
            if ($this->mailer->smtpConnect()) {
                $this->mailer->smtpClose();
                return ['success' => true, 'message' => 'SMTP connection successful'];
            }
            return ['success' => false, 'message' => 'SMTP connection failed'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'SMTP connection failed: ' . $e->getMessage()];
        }
    }

    /** Send the quotation with a PDF attachment */
    public function sendQuotationEmail(array $quotation_data, string $pdf_path, string $recipient_email, string $recipient_name = '', string $custom_message = '', bool $urgent = false, string $custom_subject = ''): array {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            // Recipient
            $this->mailer->addAddress($recipient_email, $recipient_name);

            // Subject
            $subject = $custom_subject ?: ('Quotation #' . ($quotation_data['quotation_number'] ?? '') . ' - ' . $this->config['from_name']);
            if ($urgent) {
                $subject = '[URGENT] ' . $subject;
                $this->mailer->Priority = 1;
            }
            $this->mailer->Subject = $subject;

            // Body
            $body = $this->buildQuotationEmailBody($quotation_data, $custom_message, $urgent);
            $this->mailer->Body    = $body;
            $this->mailer->AltBody = strip_tags($this->htmlToText($body));

            // Main quotation PDF attachment
            if (is_file($pdf_path)) {
                $safeName = 'quotation_' . ($quotation_data['quotation_number'] ?? 'document') . '.pdf';
                $this->mailer->addAttachment($pdf_path, $safeName);
            }

            // Add machine technical specification PDFs
            $machineAttachments = $this->getMachineAttachmentsForQuotation($quotation_data['id'] ?? 0);
            foreach ($machineAttachments as $attachment) {
                if (is_file($attachment['path'])) {
                    $this->mailer->addAttachment($attachment['path'], $attachment['filename']);
                }
            }

            $this->mailer->send();

            $attachmentCount = count($machineAttachments);
            $message = 'Email sent successfully to ' . $recipient_email;
            if ($attachmentCount > 0) {
                $message .= " with {$attachmentCount} technical specification(s)";
            }

            return ['success' => true, 'message' => $message];

        } catch (Exception $e) {
            $reason = trim($this->mailer->ErrorInfo ?: $e->getMessage());
            // surface last SMTP lines if available
            if ($this->lastDebug) {
                $reason .= ' | Debug: ' . $this->tail($this->lastDebug, 6);
            }
            return ['success' => false, 'message' => 'Email sending failed: ' . $reason];
        }
    }

    /** Get machine attachments for a quotation */
    private function getMachineAttachmentsForQuotation(int $quotation_id): array {
        if ($quotation_id <= 0) {
            return [];
        }

        try {
            require_once __DIR__ . '/../common/conn.php';
            global $conn;

            if (!$conn) {
                error_log('Database connection not available in getMachineAttachmentsForQuotation');
                return [];
            }

            // Get all machine items from the quotation with their attachment details
            $sql = "SELECT DISTINCT 
                        m.id,
                        m.name,
                        m.model,
                        m.attachment_filename,
                        m.attachment_path,
                        m.attachment_size,
                        m.attachment_type,
                        qi.quantity
                    FROM quotation_items qi
                    JOIN machines m ON qi.item_id = m.id
                    WHERE qi.quotation_id = ? 
                    AND qi.item_type = 'machine'
                    AND m.attachment_path IS NOT NULL 
                    AND m.attachment_path != ''
                    AND m.is_active = 1";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                error_log('Failed to prepare statement in getMachineAttachmentsForQuotation: ' . $conn->error);
                return [];
            }

            $stmt->bind_param("i", $quotation_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $attachments = [];
            while ($row = $result->fetch_assoc()) {
                // Check if file actually exists
                if (!file_exists($row['attachment_path'])) {
                    error_log("Machine attachment file not found: " . $row['attachment_path']);
                    continue;
                }

                $attachments[] = [
                    'machine_id' => $row['id'],
                    'machine_name' => $row['name'],
                    'machine_model' => $row['model'],
                    'filename' => 'tech_spec_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $row['name']) . 
                                  ($row['model'] ? '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $row['model']) : '') . '.pdf',
                    'original_filename' => $row['attachment_filename'],
                    'path' => $row['attachment_path'],
                    'size' => $row['attachment_size'],
                    'type' => $row['attachment_type'],
                    'quantity' => $row['quantity']
                ];
            }

            $stmt->close();
            
            if (!empty($attachments)) {
                error_log("Found " . count($attachments) . " machine attachments for quotation ID: $quotation_id");
            }
            
            return $attachments;

        } catch (Exception $e) {
            error_log('Error getting machine attachments for quotation ' . $quotation_id . ': ' . $e->getMessage());
            return [];
        }
    }

    /** Generic sender */
    public function sendEmail($recipients, string $subject, string $body, array $attachments = []): array {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            if (is_string($recipients)) { $recipients = [$recipients]; }
            foreach ($recipients as $r) {
                if (filter_var($r, FILTER_VALIDATE_EMAIL)) {
                    $this->mailer->addAddress($r);
                }
            }

            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $body;
            $this->mailer->AltBody = strip_tags($this->htmlToText($body));

            foreach ($attachments as $att) {
                if (is_array($att)) {
                    if (!empty($att['path']) && is_file($att['path'])) {
                        $this->mailer->addAttachment($att['path'], $att['name'] ?? '');
                    }
                } elseif (is_string($att) && is_file($att)) {
                    $this->mailer->addAttachment($att);
                }
            }

            $this->mailer->send();

            return ['success' => true, 'message' => 'Email sent successfully to ' . count($recipients) . ' recipient(s)'];

        } catch (Exception $e) {
            $reason = trim($this->mailer->ErrorInfo ?: $e->getMessage());
            if ($this->lastDebug) {
                $reason .= ' | Debug: ' . $this->tail($this->lastDebug, 6);
            }
            return ['success' => false, 'message' => 'Email sending failed: ' . $reason];
        }
    }

    private function buildQuotationEmailBody(array $q, string $custom_message = '', bool $urgent = false): string {
        // Safer symbols (HTML entity for ₹)
        $rupee = '&#8377;';

        $urgentBanner = $urgent
            ? '<div style="background:#dc3545;color:#fff;padding:10px;text-align:center;margin-bottom:20px;border-radius:5px;"><strong>URGENT - HIGH PRIORITY</strong></div>'
            : '';

        $qno   = htmlspecialchars((string)($q['quotation_number'] ?? ''), ENT_QUOTES, 'UTF-8');
        $qdate = !empty($q['quotation_date']) ? date('d-m-Y', strtotime((string)$q['quotation_date'])) : '';
        $valid = !empty($q['valid_until'])    ? date('d-m-Y', strtotime((string)$q['valid_until']))   : '';
        $cust  = htmlspecialchars((string)($q['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $total = number_format((float)($q['total_amount'] ?? 0), 2);

        $fromName  = htmlspecialchars((string)$this->config['from_name'], ENT_QUOTES, 'UTF-8');
        $fromEmail = htmlspecialchars((string)$this->config['from_email'], ENT_QUOTES, 'UTF-8');

        $customBlock = $custom_message !== ''
            ? '<div style="background:#fff3cd;border-left:4px solid #ffc107;padding:12px;margin:12px 0;">' .
              nl2br(htmlspecialchars($custom_message, ENT_QUOTES, 'UTF-8')) .
              '</div>'
            : '';

        // Get machine attachments info for this quotation
        $machineAttachments = $this->getMachineAttachmentsForQuotation($q['id'] ?? 0);
        $techSpecsBlock = '';
        
        if (!empty($machineAttachments)) {
            $techSpecsBlock = '<div style="background:#e8f5e8;border-left:4px solid #28a745;padding:12px;margin:12px 0;">';
            $techSpecsBlock .= '<strong>Technical Specifications Included:</strong><br>';
            $techSpecsBlock .= 'This quotation includes detailed technical specifications for the following machines:<br>';
            $techSpecsBlock .= '<ul style="margin:8px 0;">';
            foreach ($machineAttachments as $attachment) {
                $machineName = htmlspecialchars($attachment['machine_name'], ENT_QUOTES, 'UTF-8');
                $machineModel = htmlspecialchars($attachment['machine_model'], ENT_QUOTES, 'UTF-8');
                $techSpecsBlock .= "<li>{$machineName}" . ($machineModel ? " ({$machineModel})" : '') . "</li>";
            }
            $techSpecsBlock .= '</ul>';
            $techSpecsBlock .= '</div>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8">
<title>Quotation</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;line-height:1.6;color:#333}
.container{max-width:600px;margin:0 auto;padding:20px}
.header,.footer{background:#f8f9fa;padding:15px;border-radius:5px}
.content{margin:15px 0}
.quotation-info{background:#e9ecef;padding:12px;border-radius:5px;margin:12px 0}
.highlight{color:#007bff;font-weight:bold}
</style>
</head>
<body>
  <div class="container">
    {$urgentBanner}
    <div class="header">
      <h2 style="margin:0;">Quotation from {$fromName}</h2>
    </div>

    <div class="content">
      <p>Dear {$cust},</p>
      {$customBlock}
      <p>Please find attached our quotation for your requirements.</p>

      <div class="quotation-info">
        <strong>Quotation Details:</strong><br>
        <span class="highlight">Quotation No:</span> {$qno}<br>
        <span class="highlight">Date:</span> {$qdate}<br>
        <span class="highlight">Total Amount:</span> {$rupee} {$total}<br>
        <span class="highlight">Valid Until:</span> {$valid}
      </div>

      {$techSpecsBlock}

      <p>Please review the attached quotation and let us know if you have any questions or require changes.</p>
      <p>Thank you for considering us.</p>
    </div>

    <div class="footer">
      <strong>{$fromName}</strong><br>
      Email: {$fromEmail}
    </div>
  </div>
</body>
</html>
HTML;
    }

    /** convert minimal HTML to text for AltBody */
    private function htmlToText(string $html): string {
        $text = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $text = strip_tags((string)$text);
        return trim((string)$text);
    }

    /** last N lines from a string */
    private function tail(string $s, int $lines = 6): string {
        $parts = preg_split('/\r\n|\r|\n/', $s) ?: [];
        $parts = array_slice($parts, -$lines);
        return implode("\n", $parts);
    }

    public function getConfig(): array {
        return $this->config;
    }

    public function updateConfig(array $new_config): void {
        $this->config = array_merge($this->config, $new_config);
        $this->setupMailer();
    }
}
