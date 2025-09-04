<?php
// Test file to debug purchase invoice details

include 'common/conn.php';
include 'common/functions.php';

// Test getting purchase invoice details
$pi_id = 3; // Change this to an existing PI ID

echo "<h3>Testing Purchase Invoice Details (ID: $pi_id)</h3>";

// Test the API endpoint
$url = "http://localhost/ajax/get_purchase_invoice_details.php?id=$pi_id";
echo "<h4>API Response:</h4>";
echo "<pre>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
curl_close($ch);

echo htmlspecialchars($response);
echo "</pre>";

// Also test direct database query
echo "<h4>Direct Database Query:</h4>";

$sql = "SELECT pi.*, c.email as vendor_email, po.po_number as purchase_order_number
        FROM purchase_invoices pi 
        LEFT JOIN customers c ON pi.vendor_id = c.id 
        LEFT JOIN purchase_orders po ON pi.purchase_order_id = po.id
        WHERE pi.id = $pi_id";

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $pi = $result->fetch_assoc();
    echo "<pre>";
    print_r($pi);
    echo "</pre>";
    
    // Get items
    echo "<h4>Purchase Invoice Items:</h4>";
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
                  WHERE pii.pi_id = $pi_id 
                  ORDER BY pii.id";
    
    $items_result = $conn->query($items_sql);
    if ($items_result && $items_result->num_rows > 0) {
        echo "<pre>";
        while ($item = $items_result->fetch_assoc()) {
            print_r($item);
        }
        echo "</pre>";
    } else {
        echo "<p>No items found for this PI.</p>";
    }
} else {
    echo "<p>No purchase invoice found with ID $pi_id</p>";
}

// List all PIs to see what IDs are available
echo "<h4>Available Purchase Invoices:</h4>";
$list_sql = "SELECT id, pi_number, vendor_name FROM purchase_invoices ORDER BY id DESC LIMIT 10";
$list_result = $conn->query($list_sql);
if ($list_result && $list_result->num_rows > 0) {
    echo "<ul>";
    while ($row = $list_result->fetch_assoc()) {
        echo "<li>ID: {$row['id']}, PI Number: {$row['pi_number']}, Vendor: {$row['vendor_name']}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No purchase invoices found.</p>";
}
?>
