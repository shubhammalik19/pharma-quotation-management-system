<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Email Service Class using PHPMailer
 */
class EmailService {
    private $mailer;
    private $config;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->loadConfig();
        $this->setupMailer();
    }
    
    /**
     * Load email configuration
     */
    private function loadConfig() {
        // Load from config file if it exists
        $config_file = __DIR__ . '/email_settings.php';
        if (file_exists($config_file)) {
            $file_config = include $config_file;
            $this->config = $file_config;
        } else {
            // Default configuration
            $this->config = [
                'smtp_host' => 'smtp.gmail.com',
                'smtp_port' => 587,
                'smtp_secure' => PHPMailer::ENCRYPTION_STARTTLS,
                'smtp_auth' => true,
                'smtp_username' => 'shubhammalik19@gmail.com',
                'smtp_password' => 'arecyblvlxaodipp',
                'from_email' => 'shubhammalik19@gmail.com',
                'from_name' => 'Pharma Machinery Systems',
                'smtp_debug' => 0
            ];
        }
    }
    
    /**
     * Setup PHPMailer with configuration
     */
    private function setupMailer() {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['smtp_host'];
            $this->mailer->SMTPAuth = $this->config['smtp_auth'];
            $this->mailer->Username = $this->config['smtp_username'];
            $this->mailer->Password = $this->config['smtp_password'];
            
            // Handle different secure types
            if ($this->config['smtp_secure'] === 'tls') {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($this->config['smtp_secure'] === 'ssl') {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $this->mailer->SMTPSecure = $this->config['smtp_secure'];
            }
            
            $this->mailer->Port = $this->config['smtp_port'];
            $this->mailer->SMTPDebug = $this->config['smtp_debug'];
            
            // Default from address
            $this->mailer->setFrom($this->config['from_email'], $this->config['from_name']);
            
            // Content type
            $this->mailer->isHTML(true);
            
        } catch (Exception $e) {
            throw new Exception("Mailer setup failed: {$this->mailer->ErrorInfo}");
        }
    }
    
    /**
     * Send quotation email with PDF attachment
     */
    public function sendQuotationEmail($quotation_data, $pdf_path, $recipient_email, $recipient_name = '', $custom_message = '', $urgent = false, $custom_subject = '') {
        try {
            // Clear previous recipients and attachments
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Recipients
            $this->mailer->addAddress($recipient_email, $recipient_name);
            
            // Subject
            $subject = $custom_subject ?: ('Quotation #' . $quotation_data['quotation_number'] . ' - ' . $this->config['from_name']);
            if ($urgent) {
                $subject = '[URGENT] ' . $subject;
                $this->mailer->Priority = 1; // High priority
            }
            $this->mailer->Subject = $subject;
            
            // Email body
            $body = $this->buildQuotationEmailBody($quotation_data, $custom_message, $urgent);
            $this->mailer->Body = $body;
            
            // Plain text version
            $this->mailer->AltBody = strip_tags($body);
            
            // Attach PDF
            if (file_exists($pdf_path)) {
                $this->mailer->addAttachment($pdf_path, 'quotation_' . $quotation_data['quotation_number'] . '.pdf');
            }
            
            // Send email
            $result = $this->mailer->send();
            
            return [
                'success' => true,
                'message' => 'Email sent successfully to ' . $recipient_email
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Email sending failed: ' . $this->mailer->ErrorInfo
            ];
        }
    }
    
    /**
     * Send generic email
     */
    public function sendEmail($recipients, $subject, $body, $attachments = []) {
        try {
            // Clear previous recipients and attachments
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Add recipients
            if (is_string($recipients)) {
                $recipients = [$recipients];
            }
            
            foreach ($recipients as $recipient) {
                if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                    $this->mailer->addAddress($recipient);
                }
            }
            
            // Subject and body
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body);
            
            // Add attachments
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    if (is_array($attachment)) {
                        $this->mailer->addAttachment($attachment['path'], $attachment['name'] ?? '');
                    } else {
                        $this->mailer->addAttachment($attachment);
                    }
                }
            }
            
            // Send email
            $result = $this->mailer->send();
            
            return [
                'success' => true,
                'message' => 'Email sent successfully to ' . count($recipients) . ' recipient(s)'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Email sending failed: ' . $this->mailer->ErrorInfo
            ];
        }
    }
    
    /**
     * Build email body for quotation
     */
    private function buildQuotationEmailBody($quotation_data, $custom_message = '', $urgent = false) {
        $urgentBanner = $urgent ? '<div style="background-color: #dc3545; color: white; padding: 10px; text-align: center; margin-bottom: 20px; border-radius: 5px;"><strong>🚨 URGENT - HIGH PRIORITY</strong></div>' : '';
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Quotation</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
                .content { margin-bottom: 20px; }
                .footer { background-color: #f8f9fa; padding: 15px; border-radius: 5px; font-size: 12px; }
                .quotation-info { background-color: #e9ecef; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .highlight { color: #007bff; font-weight: bold; }
                .custom-message { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                ' . $urgentBanner . '
                <div class="header">
                    <h2>Quotation from ' . htmlspecialchars($this->config['from_name']) . '</h2>
                </div>
                
                <div class="content">
                    <p>Dear ' . htmlspecialchars($quotation_data['customer_name']) . ',</p>
                    
                    ' . ($custom_message ? '<div class="custom-message">' . nl2br(htmlspecialchars($custom_message)) . '</div>' : '') . '
                    
                    <p>Please find attached our quotation for your requirements.</p>
                    
                    <div class="quotation-info">
                        <strong>Quotation Details:</strong><br>
                        <span class="highlight">Quotation No:</span> ' . htmlspecialchars($quotation_data['quotation_number']) . '<br>
                        <span class="highlight">Date:</span> ' . date('d-m-Y', strtotime($quotation_data['quotation_date'])) . '<br>
                        <span class="highlight">Total Amount:</span> ₹ ' . number_format($quotation_data['total_amount'], 2) . '<br>
                        <span class="highlight">Valid Until:</span> ' . date('d-m-Y', strtotime($quotation_data['valid_until'])) . '
                    </div>
                    
                    <p>Please review the attached quotation and feel free to contact us if you have any questions or require any modifications.</p>
                    
                    <p>We look forward to your positive response.</p>
                    
                    <p>Thank you for considering our services.</p>
                </div>
                
                <div class="footer">
                    <p><strong>' . htmlspecialchars($this->config['from_name']) . '</strong><br>
                    Email: ' . htmlspecialchars($this->config['from_email']) . '</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    public function getConfig() {
        return $this->config;
    }
    
    /**
     * Test email configuration
     */
    public function testConnection() {
        try {
            $this->mailer->smtpConnect();
            $this->mailer->smtpClose();
            return ['success' => true, 'message' => 'SMTP connection successful'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'SMTP connection failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update email configuration
     */
    public function updateConfig($new_config) {
        $this->config = array_merge($this->config, $new_config);
        $this->setupMailer();
    }
}
?>
