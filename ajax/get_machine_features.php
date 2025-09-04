<?php
header('Content-Type: application/json');
include '../common/conn.php';

if (!isset($_GET['machine_id']) || !is_numeric($_GET['machine_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid machine ID']);
    exit;
}

$machine_id = intval($_GET['machine_id']);

try {
    $sql = "SELECT id, feature_name FROM machine_features WHERE machine_id = ? ORDER BY feature_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $machine_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $features = [];
    while ($row = $result->fetch_assoc()) {
        $features[] = [
            'id' => $row['id'],
            'feature_name' => htmlspecialchars($row['feature_name'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'features' => $features
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading features: ' . $e->getMessage()
    ]);
}
?>
