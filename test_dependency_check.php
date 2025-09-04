<?php
include 'common/conn.php';
include 'common/functions.php';

// Test the fixed checkQuotationDependencies function
$quotation_number = 'QUO-2025-00004';

// Get quotation ID
$sql = "SELECT id FROM quotations WHERE quotation_number = '$quotation_number'";
$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    $quotation_id = $row['id'];
    
    echo "Testing dependency check for $quotation_number (ID: $quotation_id)\n\n";
    
    // Check dependencies using the fixed function
    $dependencies = checkQuotationDependencies($conn, $quotation_id);
    
    if (empty($dependencies)) {
        echo "✅ SUCCESS: No dependencies found. Quotation can be deleted.\n";
    } else {
        echo "❌ FAIL: Dependencies found:\n";
        foreach ($dependencies as $dependency) {
            echo "  - $dependency\n";
        }
    }
} else {
    echo "Quotation $quotation_number not found.\n";
}
?>
