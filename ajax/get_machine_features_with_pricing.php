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
$valid_from = isset($_GET['valid_from']) ? $_GET['valid_from'] : null;
$valid_to = isset($_GET['valid_to']) ? $_GET['valid_to'] : null;

try {
    // Get machine features with their current pricing
    $sql = "SELECT 
                mf.id as feature_id,
                mf.feature_name,
                mfp.id as feature_price_id,
                mfp.price as feature_price,
                mfp.valid_from,
                mfp.valid_to,
                mfp.is_active
            FROM machine_features mf 
            LEFT JOIN machine_feature_prices mfp ON (
                mf.machine_id = mfp.machine_id 
                AND mf.feature_name = mfp.feature_name";
    
    // Add date filtering based on provided parameters
    if ($valid_from && $valid_to) {
        $sql .= " AND mfp.valid_from = '$valid_from' AND mfp.valid_to = '$valid_to'";
    } else {
        $sql .= " AND CURDATE() BETWEEN mfp.valid_from AND mfp.valid_to";
    }
    
    $sql .= ")
            WHERE mf.machine_id = $machine_id 
            ORDER BY mf.feature_name";
    
    $result = $conn->query($sql);
    
    $features = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $features[] = [
                'feature_id' => $row['feature_id'],
                'feature_name' => $row['feature_name'],
                'feature_price_id' => $row['feature_price_id'],
                'feature_price' => $row['feature_price'] ?? 0,
                'valid_from' => $row['valid_from'],
                'valid_to' => $row['valid_to'],
                'is_active' => $row['is_active'],
                'has_price' => !is_null($row['feature_price_id'])
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
