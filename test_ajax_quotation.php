<?php
// Test the AJAX endpoint directly with correct paths
$_GET['id'] = 5;

// Simulate the session for authentication
session_start();
$_SESSION['user_id'] = 1; // Mock user session

// Include from correct directory
include 'common/conn.php';
include 'common/functions.php';

// Now call the endpoint logic
$quotation_id = intval($_GET['id']);

// Get quotation details with customer information
$sql = "SELECT q.*, c.company_name as customer_name, c.email as customer_email 
        FROM quotations q 
        LEFT JOIN customers c ON q.customer_id = c.id 
        WHERE q.id = $quotation_id";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $quotation = $result->fetch_assoc();
    
    // Get quotation items
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
    $items = [];
    
    if ($items_result && $items_result->num_rows > 0) {
        while ($item = $items_result->fetch_assoc()) {
            // Get machine features if this is a machine item
            $features = [];
            if ($item['item_type'] === 'machine') {
                $features_sql = "SELECT feature_name, price, quantity, total_price 
                                FROM quotation_machine_features 
                                WHERE quotation_item_id = " . $item['id'];
                $features_result = $conn->query($features_sql);
                
                if ($features_result && $features_result->num_rows > 0) {
                    while ($feature = $features_result->fetch_assoc()) {
                        $features[] = [
                            'name' => $feature['feature_name'],
                            'price' => floatval($feature['price']),
                            'quantity' => intval($feature['quantity']),
                            'total_price' => floatval($feature['total_price'])
                        ];
                    }
                }
            }
            
            $item['features'] = $features;
            $items[] = $item;
        }
    }
    
    $response = [
        'success' => true, 
        'quotation' => $quotation,
        'items' => $items
    ];
    
    echo "JSON Response:\n";
    echo json_encode($response, JSON_PRETTY_PRINT);
} else {
    echo json_encode(['success' => false, 'message' => 'Quotation not found.']);
}
?>
