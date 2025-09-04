<?php

include '../common/conn.php';
include '../common/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!hasPermission('purchase_invoices', 'view')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid purchase invoice ID']);
    exit;
}

try {
    // Get purchase invoice details
    $sql = "SELECT pi.*, c.email as vendor_email 
            FROM purchase_invoices pi 
            LEFT JOIN customers c ON pi.vendor_id = c.id 
            WHERE pi.id = $id";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $pi = $result->fetch_assoc();
        
        // Get purchase invoice items
        $items_sql = "SELECT pii.*, 
                      CASE 
                        WHEN pii.item_type = 'machine' THEN m.name 
                        WHEN pii.item_type = 'spare' THEN s.part_name 
                      END as item_name_full,
                      CASE 
                        WHEN pii.item_type = 'machine' THEN m.model 
                        WHEN pii.item_type = 'spare' THEN s.part_code 
                      END as item_code
                      FROM purchase_invoice_items pii 
                      LEFT JOIN machines m ON pii.item_type = 'machine' AND pii.item_id = m.id 
                      LEFT JOIN spares s ON pii.item_type = 'spare' AND pii.item_id = s.id 
                      WHERE pii.pi_id = $id 
                      ORDER BY pii.id";
        
        $items_result = $conn->query($items_sql);
        $items = [];
        
        if ($items_result && $items_result->num_rows > 0) {
            while ($item = $items_result->fetch_assoc()) {
                $items[] = $item;
            }
        }
        
        echo json_encode([
            'success' => true,
            'pi' => $pi,
            'items' => $items
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Purchase invoice not found']);
    }
} catch (Exception $e) {
    error_log("Error in get_purchase_invoice_details.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
