<?php
include '../header.php';
require_once 'email_service.php';

$message = '';

// Handle configuration update
if ($_POST) {
    $config = [
        'smtp_host' => $_POST['smtp_host'] ?? '',
        'smtp_port' => (int)($_POST['smtp_port'] ?? 587),
        'smtp_secure' => $_POST['smtp_secure'] ?? 'tls',
        'smtp_username' => $_POST['smtp_username'] ?? '',
        'smtp_password' => $_POST['smtp_password'] ?? '',
        'from_email' => $_POST['from_email'] ?? '',
        'from_name' => $_POST['from_name'] ?? ''
    ];
    
    // Save configuration to file
    $config_content = "<?php\nreturn " . var_export($config, true) . ";\n?>";
    $config_file = 'email_settings.php';
    
    if (!is_dir('config')) {
        mkdir('config', 0755, true);
    }
    
    file_put_contents($config_file, $config_content);
    
    // Test email if requested
    if (isset($_POST['test_email'])) {
        try {
            $email_service = new EmailService();
            $test_result = $email_service->testConnection();
            
            if ($test_result['success']) {
                $message = showSuccess("Configuration saved and email connection tested successfully!");
            } else {
                $message = showError("Configuration saved but email test failed: " . $test_result['message']);
            }
        } catch (Exception $e) {
            $message = showError("Configuration saved but email test failed: " . $e->getMessage());
        }
    } else {
        $message = showSuccess("Email configuration saved successfully!");
    }
}

// Load current configuration
$current_config = [];
$config_file = 'email_settings.php';
if (file_exists($config_file)) {
    $current_config = include $config_file;
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Email Configuration</h1>
            </div>
            
            <?php echo $message; ?>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">SMTP Settings</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="smtp_host" class="form-label">SMTP Host *</label>
                                    <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                           value="<?php echo htmlspecialchars($current_config['smtp_host'] ?? 'smtp.gmail.com'); ?>" 
                                           required>
                                    <div class="form-text">e.g., smtp.gmail.com, smtp.outlook.com</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="smtp_port" class="form-label">SMTP Port *</label>
                                    <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                           value="<?php echo htmlspecialchars($current_config['smtp_port'] ?? 587); ?>" 
                                           required>
                                    <div class="form-text">Usually 587 for TLS or 465 for SSL</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="smtp_secure" class="form-label">Security *</label>
                                    <select class="form-select" id="smtp_secure" name="smtp_secure" required>
                                        <option value="tls" <?php echo ($current_config['smtp_secure'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                        <option value="ssl" <?php echo ($current_config['smtp_secure'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="smtp_username" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="smtp_username" name="smtp_username" 
                                           value="<?php echo htmlspecialchars($current_config['smtp_username'] ?? ''); ?>" 
                                           required>
                                    <div class="form-text">Your email address for SMTP authentication</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="smtp_password" class="form-label">Email Password *</label>
                                    <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                           value="<?php echo htmlspecialchars($current_config['smtp_password'] ?? ''); ?>" 
                                           required>
                                    <div class="form-text">For Gmail, use App Password (not regular password)</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="from_email" class="form-label">From Email *</label>
                                    <input type="email" class="form-control" id="from_email" name="from_email" 
                                           value="<?php echo htmlspecialchars($current_config['from_email'] ?? ''); ?>" 
                                           required>
                                    <div class="form-text">Email address shown as sender</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="from_name" class="form-label">From Name *</label>
                                    <input type="text" class="form-control" id="from_name" name="from_name" 
                                           value="<?php echo htmlspecialchars($current_config['from_name'] ?? ''); ?>" 
                                           required>
                                    <div class="form-text">Company or sender name</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Save Configuration
                            </button>
                            <button type="submit" name="test_email" value="1" class="btn btn-success">
                                <i class="bi bi-envelope-check"></i> Save & Test Email
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">Setup Instructions</h6>
                </div>
                <div class="card-body">
                    <h6>Gmail Setup:</h6>
                    <ol>
                        <li>Enable 2-factor authentication on your Google account</li>
                        <li>Go to Google Account Settings → Security → App passwords</li>
                        <li>Generate an app password for "Mail"</li>
                        <li>Use the generated app password (not your regular password) in the password field</li>
                    </ol>
                    
                    <h6 class="mt-4">Common SMTP Settings:</h6>
                    <ul>
                        <li><strong>Gmail:</strong> smtp.gmail.com, port 587, TLS</li>
                        <li><strong>Outlook:</strong> smtp-mail.outlook.com, port 587, TLS</li>
                        <li><strong>Yahoo:</strong> smtp.mail.yahoo.com, port 587, TLS</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
