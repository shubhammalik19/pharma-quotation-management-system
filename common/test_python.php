<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing Python script execution from PHP...\n";

// Test HTML content
$test_html = "<html><body><h1>PHP Test</h1><p>Generated from PHP</p></body></html>";
$html_file = '/home/logisticsoftware/public_html/quotation.logisticsoftware.in/storage/temp/php_test.html';
$pdf_file = '/home/logisticsoftware/public_html/quotation.logisticsoftware.in/storage/temp/php_test.pdf';

// Save test HTML
file_put_contents($html_file, $test_html);

// Build Python command
$python_script = '/home/logisticsoftware/public_html/quotation.logisticsoftware.in/common/generate_pdf.py';
$command = sprintf('python3 %s %s %s portrait 2>&1', 
    escapeshellarg($python_script),
    escapeshellarg($html_file),
    escapeshellarg($pdf_file)
);

echo "Command: " . $command . "\n";

// Execute command
if (function_exists('shell_exec')) {
    echo "Using shell_exec...\n";
    $output = shell_exec($command);
    echo "Output: " . $output . "\n";
} else {
    echo "shell_exec is disabled\n";
}

// Check if PDF was created
if (file_exists($pdf_file)) {
    echo "SUCCESS: PDF created with size: " . filesize($pdf_file) . " bytes\n";
    unlink($pdf_file);
} else {
    echo "ERROR: PDF was not created\n";
}

// Cleanup
if (file_exists($html_file)) {
    unlink($html_file);
}
?>