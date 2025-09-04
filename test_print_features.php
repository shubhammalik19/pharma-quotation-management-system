<?php
require_once __DIR__ . '/common/conn.php';
require_once __DIR__ . '/common/functions.php';

$quotation_id = 5; // Testing with quotation ID 5

echo "=== Testing Machine Features Display ===\n\n";

// Load items
$items_sql = "SELECT qi.*,
              CASE WHEN qi.item_type = 'machine' THEN m.name
                   WHEN qi.item_type = 'spare'   THEN s.part_name END AS item_name,
              CASE WHEN qi.item_type = 'machine' THEN m.model
                   WHEN qi.item_type = 'spare'   THEN s.part_code END AS item_code
              FROM quotation_items qi
              LEFT JOIN machines m ON qi.item_type='machine' AND qi.item_id=m.id
              LEFT JOIN spares   s ON qi.item_type='spare'   AND qi.item_id=s.id
              WHERE qi.quotation_id = $quotation_id
              ORDER BY qi.sl_no";

echo "Items SQL: $items_sql\n\n";

$items_result = $conn->query($items_sql);
if (!$items_result) {
    echo "Error in items query: " . $conn->error . "\n";
    exit;
}

$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}

echo "Found " . count($items) . " items:\n";
foreach ($items as $item) {
    echo "- Item ID: {$item['id']}, Type: {$item['item_type']}, Name: {$item['item_name']}\n";
}
echo "\n";

// Load machine features
$machine_features = [];
$features_sql = "SELECT qmf.quotation_item_id, qmf.feature_name, qmf.price, qmf.quantity, qmf.total_price
                 FROM quotation_machine_features qmf
                 INNER JOIN quotation_items qi ON qmf.quotation_item_id = qi.id
                 WHERE qi.quotation_id = $quotation_id
                 ORDER BY qmf.id";

echo "Features SQL: $features_sql\n\n";

$features_result = $conn->query($features_sql);
if (!$features_result) {
    echo "Error in features query: " . $conn->error . "\n";
    exit;
}

while ($feature = $features_result->fetch_assoc()) {
    $item_id = $feature['quotation_item_id'];
    if (!isset($machine_features[$item_id])) {
        $machine_features[$item_id] = [];
    }
    $machine_features[$item_id][] = $feature;
}

echo "Found machine features for " . count($machine_features) . " items:\n";
foreach ($machine_features as $item_id => $features) {
    echo "- Item ID $item_id has " . count($features) . " features:\n";
    foreach ($features as $feature) {
        echo "  * {$feature['feature_name']} - Price: {$feature['price']}, Qty: {$feature['quantity']}, Total: {$feature['total_price']}\n";
    }
}
echo "\n";

// Test display logic
echo "=== Display Test ===\n";
foreach ($items as $it) {
    echo "Item: {$it['item_name']} (Type: {$it['item_type']}, ID: {$it['id']})\n";
    
    if ($it['item_type'] === 'machine' && isset($machine_features[$it['id']])) {
        echo "  This machine has features:\n";
        foreach ($machine_features[$it['id']] as $feature) {
            echo "  — {$feature['feature_name']} - {$feature['price']} x {$feature['quantity']} = {$feature['total_price']}\n";
        }
    } else {
        echo "  No features for this item.\n";
    }
    echo "\n";
}
?>
