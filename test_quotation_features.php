<?php
include 'common/conn.php';

// Test the quotation details query
$quotation_id = 5; // Using quotation ID 5 from the results above

echo "Testing Quotation Details with Features for ID: $quotation_id\n\n";

// Get quotation details
$sql = "SELECT q.*, c.company_name as customer_name, c.email as customer_email 
        FROM quotations q 
        LEFT JOIN customers c ON q.customer_id = c.id 
        WHERE q.id = $quotation_id";

$result = $conn->query($sql);
$quotation = $result->fetch_assoc();

echo "Quotation: " . $quotation['quotation_number'] . "\n";
echo "Customer: " . $quotation['customer_name'] . "\n\n";

// Get quotation items with features
$items_sql = "SELECT qi.*, 
              CASE 
                WHEN qi.item_type = 'machine' THEN m.name 
                WHEN qi.item_type = 'spare' THEN s.part_name 
                ELSE qi.description
              END as item_name
              FROM quotation_items qi 
              LEFT JOIN machines m ON qi.item_type = 'machine' AND qi.item_id = m.id
              LEFT JOIN spares s ON qi.item_type = 'spare' AND qi.item_id = s.id
              WHERE qi.quotation_id = $quotation_id 
              ORDER BY qi.sl_no";

$items_result = $conn->query($items_sql);

echo "Items:\n";
while ($item = $items_result->fetch_assoc()) {
    echo "- " . $item['item_name'] . " (Type: " . $item['item_type'] . ")\n";
    echo "  Qty: " . $item['quantity'] . " × ₹" . $item['unit_price'] . " = ₹" . $item['total_price'] . "\n";
    
    // Get features for machine items
    if ($item['item_type'] === 'machine') {
        $features_sql = "SELECT feature_name, price, quantity, total_price 
                        FROM quotation_machine_features 
                        WHERE quotation_item_id = " . $item['id'];
        $features_result = $conn->query($features_sql);
        
        if ($features_result && $features_result->num_rows > 0) {
            echo "  Features:\n";
            while ($feature = $features_result->fetch_assoc()) {
                echo "    • " . $feature['feature_name'] . " (Qty: " . $feature['quantity'] . " × ₹" . $feature['price'] . " = ₹" . $feature['total_price'] . ")\n";
            }
        }
    }
    echo "\n";
}
?>
