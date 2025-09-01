<?php
// ajax/get_quotation_details.php
include '../common/conn.php';
include '../common/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
    exit;
}

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
            $items[] = $item;
        }
    }
    
    echo json_encode([
        'success' => true, 
        'quotation' => $quotation,
        'items' => $items
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Quotation not found.']);
}
?>
