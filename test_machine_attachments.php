<?php
// Test script to verify machine attachments functionality
require_once 'common/conn.php';
require_once 'common/functions.php';
require_once 'email/email_service.php';

// Function to test machine attachments retrieval
function testMachineAttachments() {
    global $conn;
    
    echo "<h1>Testing Machine Attachments for Quotations</h1>\n";
    
    // Get all quotations
    $sql = "SELECT id, quotation_number FROM quotations ORDER BY id DESC LIMIT 5";
    $result = $conn->query($sql);
    
    if ($result->num_rows === 0) {
        echo "<p>No quotations found in database.</p>\n";
        return;
    }
    
    echo "<h2>Recent Quotations:</h2>\n";
    while ($row = $result->fetch_assoc()) {
        $quotation_id = $row['id'];
        $quotation_number = $row['quotation_number'];
        
        echo "<h3>Quotation ID: {$quotation_id} (Number: {$quotation_number})</h3>\n";
        
        // Get quotation items
        $items_sql = "SELECT qi.*, m.name as machine_name, m.model, m.attachment_path, m.attachment_filename
                      FROM quotation_items qi
                      LEFT JOIN machines m ON qi.item_id = m.id AND qi.item_type = 'machine'
                      WHERE qi.quotation_id = $quotation_id";
        $items_result = $conn->query($items_sql);
        
        if ($items_result->num_rows === 0) {
            echo "<p>No items found for this quotation.</p>\n";
            continue;
        }
        
        echo "<h4>Quotation Items:</h4>\n";
        echo "<ul>\n";
        while ($item = $items_result->fetch_assoc()) {
            echo "<li>";
            echo "Type: {$item['item_type']}, ";
            if ($item['item_type'] === 'machine') {
                echo "Machine: {$item['machine_name']} ({$item['model']}), ";
                echo "Has Attachment: " . (!empty($item['attachment_path']) ? 'Yes' : 'No');
                if (!empty($item['attachment_path'])) {
                    echo " - File: {$item['attachment_filename']}";
                    echo " - Exists: " . (file_exists($item['attachment_path']) ? 'Yes' : 'No');
                }
            } else {
                echo "Item ID: {$item['item_id']}";
            }
            echo "</li>\n";
        }
        echo "</ul>\n";
        
        // Test the EmailService method
        echo "<h4>Testing EmailService getMachineAttachmentsForQuotation():</h4>\n";
        $emailService = new EmailService();
        
        // Use reflection to access private method for testing
        $reflection = new ReflectionClass($emailService);
        $method = $reflection->getMethod('getMachineAttachmentsForQuotation');
        $method->setAccessible(true);
        
        $attachments = $method->invoke($emailService, $quotation_id);
        
        if (empty($attachments)) {
            echo "<p>No machine attachments found for this quotation.</p>\n";
        } else {
            echo "<p>Found " . count($attachments) . " machine attachment(s):</p>\n";
            echo "<ul>\n";
            foreach ($attachments as $attachment) {
                echo "<li>";
                echo "Machine: {$attachment['machine_name']} ({$attachment['machine_model']})<br>";
                echo "File: {$attachment['filename']}<br>";
                echo "Path: {$attachment['path']}<br>";
                echo "Size: " . formatFileSize($attachment['size']) . "<br>";
                echo "File exists: " . (file_exists($attachment['path']) ? 'Yes' : 'No');
                echo "</li>\n";
            }
            echo "</ul>\n";
        }
        
        echo "<hr>\n";
    }
}

// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Run the test
echo "<!DOCTYPE html><html><head><title>Machine Attachments Test</title></head><body>";
testMachineAttachments();
echo "</body></html>";
?>
