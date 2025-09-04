<?php
require_once __DIR__ . '/common/conn.php';
require_once __DIR__ . '/common/functions.php';

$quotation_id = 5;

// Test if the features are displaying correctly
echo "<h2>Testing Machine Features Display for Quotation ID: $quotation_id</h2>";

// Load items (simplified)
$items_sql = "SELECT qi.*, 'Test Machine' as item_name, qi.item_type
              FROM quotation_items qi
              WHERE qi.quotation_id = $quotation_id
              ORDER BY qi.sl_no";
$items_result = $conn->query($items_sql);
$items = [];
if ($items_result) {
    while ($row = $items_result->fetch_assoc()) {
        $items[] = $row;
    }
}

// Load machine features
$machine_features = [];
$features_sql = "SELECT qmf.quotation_item_id, qmf.feature_name, qmf.price, qmf.quantity, qmf.total_price
                 FROM quotation_machine_features qmf
                 INNER JOIN quotation_items qi ON qmf.quotation_item_id = qi.id
                 WHERE qi.quotation_id = $quotation_id
                 ORDER BY qmf.id";
$features_result = $conn->query($features_sql);
if ($features_result) {
    while ($feature = $features_result->fetch_assoc()) {
        $item_id = $feature['quotation_item_id'];
        if (!isset($machine_features[$item_id])) {
            $machine_features[$item_id] = [];
        }
        $machine_features[$item_id][] = $feature;
    }
}

echo "<table border='1'>";
echo "<tr><th>Item</th><th>Price</th><th>Qty</th><th>Total</th></tr>";

foreach ($items as $it) {
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($it['item_name']) . "</strong></td>";
    echo "<td>" . ($it['unit_price'] ?? 0) . "</td>";
    echo "<td>" . ($it['quantity'] ?? 0) . "</td>";
    echo "<td>" . ($it['total_price'] ?? 0) . "</td>";
    echo "</tr>";
    
    // Display machine features
    if ($it['item_type'] === 'machine' && isset($machine_features[$it['id']])) {
        foreach ($machine_features[$it['id']] as $feature) {
            echo "<tr style='background-color: #f8f9fa;'>";
            echo "<td style='padding-left: 20px;'><em>— " . htmlspecialchars($feature['feature_name']) . "</em></td>";
            echo "<td>" . $feature['price'] . "</td>";
            echo "<td>" . $feature['quantity'] . "</td>";
            echo "<td>" . $feature['total_price'] . "</td>";
            echo "</tr>";
        }
    }
}

echo "</table>";

echo "<h3>Debug Info:</h3>";
echo "Items count: " . count($items) . "<br>";
echo "Machine features count: " . count($machine_features) . "<br>";
foreach ($machine_features as $item_id => $features) {
    echo "Item $item_id has " . count($features) . " features<br>";
}
?>
