<?php
include '../common/conn.php';
include '../common/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$po_id = intval($_GET['id'] ?? 0);

if ($po_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid purchase order ID']);
    exit;
}

try {
    // Get purchase order details
    $po_sql = "SELECT po.*, c.company_name, c.address, c.gst_no, c.phone, c.email as vendor_email 
               FROM purchase_orders po 
               LEFT JOIN customers c ON po.vendor_id = c.id 
               WHERE po.id = $po_id";
    
    $po_result = $conn->query($po_sql);
    
    if (!$po_result || $po_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Purchase order not found']);
        exit;
    }
    
    $po = $po_result->fetch_assoc();
    
    // Get purchase order items with consistent naming
    $items_sql = "SELECT poi.*, 
                  CASE 
                    WHEN poi.item_type = 'machine' THEN m.name 
                    WHEN poi.item_type = 'spare' THEN s.part_name 
                    ELSE poi.description 
                  END as display_name,
                  CASE 
                    WHEN poi.item_type = 'machine' THEN m.model 
                    WHEN poi.item_type = 'spare' THEN s.part_code 
                    ELSE '' 
                  END as display_code
                  FROM purchase_order_items poi 
                  LEFT JOIN machines m ON poi.item_type = 'machine' AND poi.item_id = m.id 
                  LEFT JOIN spares s ON poi.item_type = 'spare' AND poi.item_id = s.id 
                  WHERE poi.po_id = $po_id 
                  ORDER BY poi.id";
    
    $items_result = $conn->query($items_sql);
    
    $items = [];
    if ($items_result && $items_result->num_rows > 0) {
        while ($item = $items_result->fetch_assoc()) {
            // Ensure consistent naming - always show machine/spare name
            if (empty($item['item_name']) && !empty($item['display_name'])) {
                $item['item_name'] = $item['display_name'];
            }
            if (empty($item['item_name'])) {
                $item['item_name'] = $item['item_type'] === 'machine' ? 'Machine' : 'Spare Part';
            }
            $items[] = $item;
        }
    }
    
    echo json_encode([
        'success' => true,
        'po' => $po,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>