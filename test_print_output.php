<?php
// Simple test to capture print page output
ob_start();
$_GET['id'] = 5;
include 'docs/print_quotation.php';
$output = ob_get_clean();

// Look for machine features in the output
if (strpos($output, 'MACHIN') !== false) {
    echo "✓ Machine feature 'MACHIN' found in output!\n";
} else {
    echo "✗ Machine feature 'MACHIN' NOT found in output.\n";
}

// Look for the feature display pattern
if (strpos($output, '—') !== false) {
    echo "✓ Feature indent character '—' found in output!\n";
} else {
    echo "✗ Feature indent character '—' NOT found in output.\n";
}

// Save output to file for inspection
file_put_contents('test_print_output.html', $output);
echo "Full output saved to test_print_output.html\n";
echo "Output length: " . strlen($output) . " characters\n";
?>
