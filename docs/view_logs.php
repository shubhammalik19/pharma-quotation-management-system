<?php
require_once __DIR__ . '/../common/conn.php';
require_once __DIR__ . '/../common/functions.php';
checkLogin();

$log_dir = __DIR__ . '/../storage/logs';
$log_files = [
    'credit_notes.log' => 'Credit Notes',
    'debit_notes.log' => 'Debit Notes', 
    'purchase_orders.log' => 'Purchase Orders',
    'quotations.log' => 'Quotations',
    'sales_invoices.log' => 'Sales Invoices',
    'sales_orders.log' => 'Sales Orders'
];

$selected_log = $_GET['log'] ?? 'credit_notes.log';
$lines = $_GET['lines'] ?? 50;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Logs - Quotation Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .log-content {
            background: #1e1e1e;
            color: #f8f9fa;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 600px;
            overflow-y: auto;
            border-radius: 8px;
            padding: 15px;
        }
        .log-line {
            margin-bottom: 5px;
            padding: 3px 0;
        }
        .log-timestamp {
            color: #6c757d;
        }
        .log-user {
            color: #ffc107;
        }
        .log-id {
            color: #20c997;
        }
        .log-message {
            color: #e9ecef;
        }
        .log-error {
            background-color: rgba(220, 53, 69, 0.1);
            border-left: 3px solid #dc3545;
            padding-left: 10px;
        }
        .log-success {
            background-color: rgba(40, 167, 69, 0.1);
            border-left: 3px solid #28a745;
            padding-left: 10px;
        }
        .navbar-brand {
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="fas fa-file-alt me-2"></i>Document Logs
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-list me-2"></i>Log Files</h6>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($log_files as $file => $name): ?>
                            <a href="?log=<?php echo urlencode($file); ?>&lines=<?php echo $lines; ?>" 
                               class="list-group-item list-group-item-action <?php echo $selected_log === $file ? 'active' : ''; ?>">
                                <i class="fas fa-file-alt me-2"></i><?php echo htmlspecialchars($name); ?>
                                <?php 
                                $log_path = $log_dir . '/' . $file;
                                if (file_exists($log_path)) {
                                    $line_count = count(file($log_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
                                    echo "<span class='badge bg-secondary float-end'>{$line_count}</span>";
                                } else {
                                    echo "<span class='badge bg-warning float-end'>No logs</span>";
                                }
                                ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Controls -->
                <div class="card mt-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="fas fa-cog me-2"></i>Controls</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="mb-3">
                            <input type="hidden" name="log" value="<?php echo htmlspecialchars($selected_log); ?>">
                            <div class="mb-2">
                                <label class="form-label">Lines to show:</label>
                                <select name="lines" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="25" <?php echo $lines == 25 ? 'selected' : ''; ?>>Last 25</option>
                                    <option value="50" <?php echo $lines == 50 ? 'selected' : ''; ?>>Last 50</option>
                                    <option value="100" <?php echo $lines == 100 ? 'selected' : ''; ?>>Last 100</option>
                                    <option value="500" <?php echo $lines == 500 ? 'selected' : ''; ?>>Last 500</option>
                                    <option value="all" <?php echo $lines == 'all' ? 'selected' : ''; ?>>All</option>
                                </select>
                            </div>
                        </form>
                        
                        <div class="d-grid gap-2">
                            <button onclick="location.reload()" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-sync me-1"></i>Refresh
                            </button>
                            
                            <?php if (isset($log_files[$selected_log])): ?>
                            <a href="?log=<?php echo urlencode($selected_log); ?>&action=download" 
                               class="btn btn-outline-success btn-sm">
                                <i class="fas fa-download me-1"></i>Download
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-terminal me-2"></i>
                            <?php echo htmlspecialchars($log_files[$selected_log] ?? 'Unknown Log'); ?> 
                        </h5>
                        <span class="badge bg-light text-dark">
                            Showing <?php echo $lines === 'all' ? 'all' : "last {$lines}"; ?> lines
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="log-content">
                            <?php
                            $log_path = $log_dir . '/' . $selected_log;
                            
                            // Handle download
                            if (isset($_GET['action']) && $_GET['action'] === 'download' && file_exists($log_path)) {
                                header('Content-Type: text/plain');
                                header('Content-Disposition: attachment; filename="' . $selected_log . '"');
                                readfile($log_path);
                                exit;
                            }
                            
                            if (file_exists($log_path)) {
                                $file_lines = file($log_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                                
                                if ($lines !== 'all') {
                                    $file_lines = array_slice($file_lines, -$lines);
                                }
                                
                                if (empty($file_lines)) {
                                    echo '<div class="text-center text-muted py-4">';
                                    echo '<i class="fas fa-file-alt fa-3x mb-3"></i><br>';
                                    echo 'No log entries found';
                                    echo '</div>';
                                } else {
                                    foreach ($file_lines as $line) {
                                        $line = htmlspecialchars($line);
                                        
                                        // Parse log line format: [timestamp] User: user_id | IP: ip | ID: id | message
                                        if (preg_match('/^\[([^\]]+)\]\s+User:\s+([^|]+)\s+\|\s+IP:\s+([^|]+)\s+\|\s+[^:]+:\s+([^|]*)\s+\|\s+(.+)$/', $line, $matches)) {
                                            $timestamp = $matches[1];
                                            $user = trim($matches[2]);
                                            $ip = trim($matches[3]);
                                            $id = trim($matches[4]);
                                            $message = trim($matches[5]);
                                            
                                            $class = '';
                                            if (stripos($message, 'error') !== false || stripos($message, 'failed') !== false || stripos($message, 'not found') !== false) {
                                                $class = 'log-error';
                                            } elseif (stripos($message, 'success') !== false || stripos($message, 'generated') !== false) {
                                                $class = 'log-success';
                                            }
                                            
                                            echo "<div class='log-line {$class}'>";
                                            echo "<span class='log-timestamp'>[{$timestamp}]</span> ";
                                            echo "<span class='log-user'>User: {$user}</span> | ";
                                            echo "<span class='text-info'>IP: {$ip}</span>";
                                            if ($id) {
                                                echo " | <span class='log-id'>ID: {$id}</span>";
                                            }
                                            echo " | <span class='log-message'>{$message}</span>";
                                            echo "</div>";
                                        } else {
                                            echo "<div class='log-line'>{$line}</div>";
                                        }
                                    }
                                }
                            } else {
                                echo '<div class="text-center text-muted py-4">';
                                echo '<i class="fas fa-exclamation-triangle fa-3x mb-3"></i><br>';
                                echo 'Log file not found: ' . htmlspecialchars($selected_log) . '<br>';
                                echo '<small>Logs will appear here when documents are accessed.</small>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Log Statistics -->
                <div class="row mt-3">
                    <?php if (file_exists($log_path)): ?>
                    <div class="col-md-6">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-line text-info fa-2x mb-2"></i>
                                <h6>Total Entries</h6>
                                <h4 class="text-info">
                                    <?php echo count(file($log_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)); ?>
                                </h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <i class="fas fa-weight text-warning fa-2x mb-2"></i>
                                <h6>File Size</h6>
                                <h4 class="text-warning">
                                    <?php echo number_format(filesize($log_path) / 1024, 2); ?> KB
                                </h4>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-scroll to bottom of log content
        document.addEventListener('DOMContentLoaded', function() {
            const logContent = document.querySelector('.log-content');
            if (logContent) {
                logContent.scrollTop = logContent.scrollHeight;
            }
        });
        
        // Auto-refresh every 30 seconds
        setInterval(function() {
            if (document.hidden === false) {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>
