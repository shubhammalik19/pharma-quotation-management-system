<?php
// Test the overlap validation logic
include_once 'common/conn.php';

echo "<h3>Testing Overlap Validation Logic</h3>";

// Test case 1: Check existing price records
$sql = "SELECT id, machine_id, price, valid_from, valid_to FROM price_master ORDER BY id";
$result = $conn->query($sql);

echo "<h4>Existing Price Records:</h4>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Machine ID</th><th>Price</th><th>Valid From</th><th>Valid To</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['machine_id']}</td>";
    echo "<td>₹{$row['price']}</td>";
    echo "<td>{$row['valid_from']}</td>";
    echo "<td>{$row['valid_to']}</td>";
    echo "</tr>";
}
echo "</table>";

// Test case 2: Simulate overlap check for update
if (isset($_GET['test_update'])) {
    $price_id = intval($_GET['price_id'] ?? 1);
    $machine_id = intval($_GET['machine_id'] ?? 1);
    $valid_from = $_GET['valid_from'] ?? '2024-01-01';
    $valid_to = $_GET['valid_to'] ?? '2024-12-31';
    
    echo "<h4>Testing Update Overlap Check:</h4>";
    echo "<p>Price ID: $price_id, Machine ID: $machine_id</p>";
    echo "<p>New Date Range: $valid_from to $valid_to</p>";
    
    // Get current data
    $currentSql = "SELECT machine_id, price, valid_from, valid_to FROM price_master WHERE id = $price_id";
    $currentResult = $conn->query($currentSql);
    $currentData = $currentResult->fetch_assoc();
    
    echo "<p>Current Data: Machine {$currentData['machine_id']}, ₹{$currentData['price']}, {$currentData['valid_from']} to {$currentData['valid_to']}</p>";
    
    // Check if main data changed
    $mainDataChanged = (
        $currentData['machine_id'] != $machine_id ||
        $currentData['valid_from'] != $valid_from ||
        $currentData['valid_to'] != $valid_to
    );
    
    echo "<p><strong>Main data changed: " . ($mainDataChanged ? 'YES' : 'NO') . "</strong></p>";
    
    if ($mainDataChanged) {
        // Check for overlaps
        $checkSql = "SELECT id FROM price_master 
                    WHERE machine_id = $machine_id 
                    AND id != $price_id
                    AND (
                        ('$valid_from' BETWEEN valid_from AND valid_to) OR 
                        ('$valid_to' BETWEEN valid_from AND valid_to) OR 
                        (valid_from BETWEEN '$valid_from' AND '$valid_to') OR 
                        (valid_to BETWEEN '$valid_from' AND '$valid_to')
                    )";
        
        echo "<p>Overlap check query: <code>$checkSql</code></p>";
        
        $checkResult = $conn->query($checkSql);
        if ($checkResult->num_rows > 0) {
            echo "<p style='color: red;'><strong>OVERLAP DETECTED!</strong></p>";
            while ($overlap = $checkResult->fetch_assoc()) {
                echo "<p>Overlapping record ID: {$overlap['id']}</p>";
            }
        } else {
            echo "<p style='color: green;'><strong>NO OVERLAP - UPDATE ALLOWED</strong></p>";
        }
    } else {
        echo "<p style='color: blue;'><strong>FEATURE-ONLY UPDATE - SKIP OVERLAP CHECK</strong></p>";
    }
}

echo "<br><hr>";
echo "<p><a href='?test_update=1&price_id=1&machine_id=1&valid_from=2024-01-01&valid_to=2024-12-31'>Test Update for Price ID 1</a></p>";
echo "<p><a href='test_overlap_validation.php'>Refresh</a></p>";
?>
