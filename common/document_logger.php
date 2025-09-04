<?php
/**
 * Centralized Document Logging System
 * 
 * This file provides centralized logging functionality for all document operations
 * in the quotation management system.
 */

class DocumentLogger {
    private static $log_dir = null;
    private static $initialized = false;
    
    /**
     * Initialize the logging system
     */
    public static function init() {
        if (self::$initialized) return;
        
        self::$log_dir = __DIR__ . '/../storage/logs';
        if (!file_exists(self::$log_dir)) {
            mkdir(self::$log_dir, 0755, true);
        }
        self::$initialized = true;
    }
    
    /**
     * Log a document activity
     * 
     * @param string $document_type The type of document (quotation, sales_order, etc.)
     * @param string $message The log message
     * @param int|null $document_id The document ID (optional)
     * @param string $level The log level (info, warning, error)
     */
    public static function log($document_type, $message, $document_id = null, $level = 'info') {
        self::init();
        
        $log_file = self::$log_dir . "/{$document_type}.log";
        $timestamp = date('Y-m-d H:i:s');
        $user_id = $_SESSION['user_id'] ?? 'guest';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        // Format document ID display
        $doc_type_upper = strtoupper(str_replace('_', ' ', $document_type));
        $doc_id_str = $document_id ? $document_id : '';
        
        // Create log entry
        $log_entry = "[{$timestamp}] User: {$user_id} | IP: {$ip} | {$doc_type_upper} ID: {$doc_id_str} | [{$level}] {$message}" . PHP_EOL;
        
        // Add detailed entry for debug log
        $debug_entry = "[{$timestamp}] User: {$user_id} | IP: {$ip} | UA: {$user_agent} | Doc: {$document_type} | ID: {$doc_id_str} | Level: {$level} | Message: {$message}" . PHP_EOL;
        $debug_log = self::$log_dir . '/debug.log';
        
        // Write to both specific and debug logs
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        file_put_contents($debug_log, $debug_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log info level message
     */
    public static function info($document_type, $message, $document_id = null) {
        self::log($document_type, $message, $document_id, 'info');
    }
    
    /**
     * Log warning level message
     */
    public static function warning($document_type, $message, $document_id = null) {
        self::log($document_type, $message, $document_id, 'warning');
    }
    
    /**
     * Log error level message
     */
    public static function error($document_type, $message, $document_id = null) {
        self::log($document_type, $message, $document_id, 'error');
    }
    
    /**
     * Get available log files
     */
    public static function getLogFiles() {
        self::init();
        $files = [];
        if (is_dir(self::$log_dir)) {
            foreach (glob(self::$log_dir . '/*.log') as $file) {
                $filename = basename($file);
                $files[$filename] = [
                    'path' => $file,
                    'size' => filesize($file),
                    'modified' => filemtime($file),
                    'lines' => count(file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))
                ];
            }
        }
        return $files;
    }
    
    /**
     * Clear a specific log file
     */
    public static function clearLog($document_type) {
        self::init();
        $log_file = self::$log_dir . "/{$document_type}.log";
        if (file_exists($log_file)) {
            file_put_contents($log_file, '');
            return true;
        }
        return false;
    }
    
    /**
     * Get log entries for a specific document type
     */
    public static function getLogEntries($document_type, $limit = null) {
        self::init();
        $log_file = self::$log_dir . "/{$document_type}.log";
        
        if (!file_exists($log_file)) {
            return [];
        }
        
        $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if ($limit && is_numeric($limit)) {
            $lines = array_slice($lines, -$limit);
        }
        
        return $lines;
    }
    
    /**
     * Archive old logs (for maintenance)
     */
    public static function archiveLogs($days_old = 30) {
        self::init();
        $archive_dir = self::$log_dir . '/archive';
        if (!file_exists($archive_dir)) {
            mkdir($archive_dir, 0755, true);
        }
        
        $cutoff_date = time() - ($days_old * 24 * 60 * 60);
        $archived = 0;
        
        foreach (glob(self::$log_dir . '/*.log') as $file) {
            if (filemtime($file) < $cutoff_date) {
                $archive_file = $archive_dir . '/' . date('Y-m-d_') . basename($file);
                if (rename($file, $archive_file)) {
                    $archived++;
                }
            }
        }
        
        return $archived;
    }
}

// Convenience functions for backward compatibility
function logCreditNoteActivity($message, $cn_id = null) {
    DocumentLogger::info('credit_notes', $message, $cn_id);
}

function logDebitNoteActivity($message, $dn_id = null) {
    DocumentLogger::info('debit_notes', $message, $dn_id);
}

function logPurchaseOrderActivity($message, $po_id = null) {
    DocumentLogger::info('purchase_orders', $message, $po_id);
}

function logQuotationActivity($message, $quotation_id = null) {
    DocumentLogger::info('quotations', $message, $quotation_id);
}

function logSalesInvoiceActivity($message, $invoice_id = null) {
    DocumentLogger::info('sales_invoices', $message, $invoice_id);
}

function logSalesOrderActivity($message, $so_id = null) {
    DocumentLogger::info('sales_orders', $message, $so_id);
}
?>
