<?php
require_once '../common/conn.php';
require_once '../common/functions.php';

// Check if user is logged in
checkLogin();

header('Content-Type: application/json');

if (!isset($_GET['quotation_id'])) {
    echo json_encode(['error' => 'Quotation ID is required']);
    exit;
}

$quotation_id = intval($_GET['quotation_id']);

try {
    // Get quotation items with item details
    $sql = "SELECT qi.*, 
                   CASE 
                       WHEN qi.item_type = 'machine' THEN m.name
                       WHEN qi.item_type = 'spare' THEN s.part_name
                       ELSE 'Unknown Item'
                   END as item_name,
                   CASE 
                       WHEN qi.item_type = 'machine' THEN m.model
                       WHEN qi.item_type = 'spare' THEN s.part_code
                       ELSE ''
                   END as item_code
            FROM quotation_items qi
            LEFT JOIN machines m ON qi.item_type = 'machine' AND qi.item_id = m.id
            LEFT JOIN spares s ON qi.item_type = 'spare' AND qi.item_id = s.id
            WHERE qi.quotation_id = $quotation_id
            ORDER BY qi.sl_no";

    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception('Database query failed: ' . $conn->error);
    }

    $items = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'type' => $row['item_type'],
                'id' => $row['item_id'],
                'name' => $row['item_name'],
                'quantity' => intval($row['quantity']),
                'price' => floatval($row['unit_price']),
                'total' => floatval($row['total_price']),
                'sl_no' => intval($row['sl_no']),
                'description' => $row['description'] ?: '',
                'item_code' => $row['item_code'] ?: ''
            ];
        }
    }

    echo json_encode(['success' => true, 'items' => $items]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
