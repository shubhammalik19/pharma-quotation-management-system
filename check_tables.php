<?php
include_once 'common/conn.php';

echo "<h3>Database Structure Test</h3>";

// Check if machine_feature_prices table exists
$check_table = "SHOW TABLES LIKE 'machine_feature_prices'";
$result = $conn->query($check_table);
if ($result->num_rows > 0) {
    echo "<p>✅ machine_feature_prices table exists</p>";
    
    // Get table structure
    $describe = "DESCRIBE machine_feature_prices";
    $result = $conn->query($describe);
    echo "<h4>Table Structure:</h4>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ machine_feature_prices table does not exist</p>";
}

// Check machine_features table structure too
$check_table2 = "SHOW TABLES LIKE 'machine_features'";
$result2 = $conn->query($check_table2);
if ($result2->num_rows > 0) {
    echo "<p>✅ machine_features table exists</p>";
    
    $describe2 = "DESCRIBE machine_features";
    $result2 = $conn->query($describe2);
    echo "<h4>Machine Features Structure:</h4>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result2->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Show all tables for context
echo "<h4>All Tables:</h4>";
$show_tables = "SHOW TABLES";
$result = $conn->query($show_tables);
while ($row = $result->fetch_array()) {
    echo "<p>{$row[0]}</p>";
}
?>
