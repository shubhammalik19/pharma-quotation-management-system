<?php
// ajax/get_sales_invoice_details.php
require_once '../common/conn.php';
require_once '../common/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid invoice ID']);
    exit;
}

$invoice_id = (int)$_GET['id'];

try {
    // Get invoice details
    $invoice_sql = "SELECT si.*, 
                           po.po_number as purchase_order_number,
                           c.email as customer_email,
                           c.phone as customer_phone
                    FROM sales_invoices si
                    LEFT JOIN purchase_orders po ON si.purchase_order_id = po.id
                    LEFT JOIN customers c ON si.customer_id = c.id
                    WHERE si.id = ?";
    
    $stmt = $conn->prepare($invoice_sql);
    $stmt->bind_param('i', $invoice_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Sales invoice not found']);
        exit;
    }
    
    $invoice = $result->fetch_assoc();
    
    // Get invoice items
    $items_sql = "SELECT sii.*, 
                         CASE 
                            WHEN sii.item_type = 'machine' THEN m.name
                            WHEN sii.item_type = 'spare' THEN s.part_name
                            ELSE sii.item_name
                         END as display_name,
                         CASE 
                            WHEN sii.item_type = 'machine' THEN m.model
                            WHEN sii.item_type = 'spare' THEN s.part_code
                            ELSE ''
                         END as display_code
                  FROM sales_invoice_items sii
                  LEFT JOIN machines m ON sii.item_type = 'machine' AND sii.item_id = m.id
                  LEFT JOIN spares s ON sii.item_type = 'spare' AND sii.item_id = s.id
                  WHERE sii.invoice_id = ?
                  ORDER BY sii.id";
    
    $stmt = $conn->prepare($items_sql);
    $stmt->bind_param('i', $invoice_id);
    $stmt->execute();
    $items_result = $stmt->get_result();
    
    $items = [];
    while ($item = $items_result->fetch_assoc()) {
        $items[] = $item;
    }
    
    // Format dates for display
    if ($invoice['invoice_date']) {
        $invoice['invoice_date_formatted'] = date('d/m/Y', strtotime($invoice['invoice_date']));
    }
    if ($invoice['due_date']) {
        $invoice['due_date_formatted'] = date('d/m/Y', strtotime($invoice['due_date']));
    }
    
    echo json_encode([
        'success' => true,
        'invoice' => $invoice,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching sales invoice details: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>