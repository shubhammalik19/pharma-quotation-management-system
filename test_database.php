<?php
include_once 'common/conn.php';

echo "<h3>Database Test</h3>";

// Check machines
$machines_query = "SELECT COUNT(*) as count FROM machines";
$result = $conn->query($machines_query);
$machine_count = $result->fetch_assoc()['count'];
echo "<p>Total machines: $machine_count</p>";

if ($machine_count > 0) {
    echo "<h4>Machines:</h4>";
    $machines_list = "SELECT id, name, model FROM machines LIMIT 5";
    $result = $conn->query($machines_list);
    while ($row = $result->fetch_assoc()) {
        echo "<p>ID: {$row['id']}, Name: {$row['name']}, Model: {$row['model']}</p>";
    }
}

// Check machine features
$features_query = "SELECT COUNT(*) as count FROM machine_features";
$result = $conn->query($features_query);
$feature_count = $result->fetch_assoc()['count'];
echo "<p>Total machine features: $feature_count</p>";

if ($feature_count > 0) {
    echo "<h4>Machine Features (sample):</h4>";
    $features_list = "SELECT mf.machine_id, mf.feature_name, m.name as machine_name 
                      FROM machine_features mf 
                      LEFT JOIN machines m ON mf.machine_id = m.id 
                      LIMIT 10";
    $result = $conn->query($features_list);
    while ($row = $result->fetch_assoc()) {
        echo "<p>Machine: {$row['machine_name']} (ID: {$row['machine_id']}), Feature: {$row['feature_name']}</p>";
    }
}

// Test AJAX endpoint directly
if (isset($_GET['test_ajax']) && $_GET['test_ajax'] == '1') {
    echo "<h4>Testing AJAX endpoint:</h4>";
    $machine_id = isset($_GET['machine_id']) ? intval($_GET['machine_id']) : 1;
    
    $sql = "SELECT 
                mf.id as feature_id,
                mf.feature_name,
                mfp.id as feature_price_id,
                mfp.price as feature_price,
                mfp.price_id,
                mfp.valid_from,
                mfp.valid_to,
                mfp.is_active
            FROM machine_features mf 
            LEFT JOIN machine_feature_prices mfp ON (
                mf.machine_id = mfp.machine_id 
                AND mf.feature_name = mfp.feature_name
                AND CURDATE() BETWEEN mfp.valid_from AND mfp.valid_to
            )
            WHERE mf.machine_id = $machine_id 
            ORDER BY mf.feature_name";
    
    $result = $conn->query($sql);
    $features = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $features[] = $row;
        }
    }
    
    echo "<pre>";
    echo "Query: $sql\n";
    echo "Result for machine ID $machine_id:\n";
    print_r($features);
    echo "</pre>";
}
?>
