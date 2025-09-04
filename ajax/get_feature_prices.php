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
    // Get feature prices for the machine
    $sql = "SELECT mfp.*, m.name as machine_name 
            FROM machine_feature_prices mfp 
            LEFT JOIN machines m ON mfp.machine_id = m.id 
            WHERE mfp.machine_id = $machine_id 
            ORDER BY mfp.feature_name, mfp.valid_from";
    
    $result = $conn->query($sql);
    
    $feature_prices = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $today = date('Y-m-d');
            $status = 'expired';
            
            if ($today >= $row['valid_from'] && $today <= $row['valid_to']) {
                $status = 'active';
            } elseif ($today < $row['valid_from']) {
                $status = 'future';
            }
            
            $feature_prices[] = [
                'id' => $row['id'],
                'machine_id' => $row['machine_id'],
                'machine_name' => $row['machine_name'],
                'feature_name' => $row['feature_name'],
                'price' => $row['price'],
                'valid_from' => $row['valid_from'],
                'valid_to' => $row['valid_to'],
                'is_active' => $row['is_active'],
                'status' => $status,
                'created_at' => $row['created_at']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'feature_prices' => $feature_prices,
        'count' => count($feature_prices)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching feature prices: ' . $e->getMessage()
    ]);
}
?>
