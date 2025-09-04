<?php
header('Content-Type: application/json');

include_once '../common/conn.php';
include_once '../common/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if machine_id is provided
if (!isset($_GET['machine_id']) || empty($_GET['machine_id'])) {
    echo json_encode(['success' => false, 'message' => 'Machine ID is required']);
    exit();
}

$machine_id = intval($_GET['machine_id']);

try {
    // Get machine features with their current pricing from price master
    $sql = "SELECT 
                mf.id as feature_id,
                mf.feature_name,
                mfp.price as feature_price
            FROM machine_features mf 
            LEFT JOIN machine_feature_prices mfp ON (
                mf.machine_id = mfp.machine_id 
                AND mf.feature_name = mfp.feature_name
                AND mfp.is_active = 1
                AND CURDATE() BETWEEN mfp.valid_from AND mfp.valid_to
            )
            WHERE mf.machine_id = $machine_id 
            ORDER BY mf.feature_name";
    
    $result = $conn->query($sql);
    
    $features = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $features[] = [
                'feature_id' => $row['feature_id'],
                'feature_name' => $row['feature_name'],
                'feature_price' => $row['feature_price'] ?? 0,
                'has_price' => !is_null($row['feature_price']) && $row['feature_price'] > 0
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'features' => $features,
        'count' => count($features)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching machine features: ' . $e->getMessage()
    ]);
}
?>
