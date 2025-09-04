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
    // Get machine features
    $sql = "SELECT DISTINCT feature_name FROM machine_features WHERE machine_id = $machine_id ORDER BY feature_name";
    $result = $conn->query($sql);
    
    $features = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $features[] = [
                'feature_name' => $row['feature_name']
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
