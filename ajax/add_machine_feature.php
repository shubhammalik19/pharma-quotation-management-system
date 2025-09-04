<?php
header('Content-Type: application/json');
include '../common/conn.php';
include '../common/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['machine_id']) || !isset($_POST['feature_name'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$machine_id = intval($_POST['machine_id']);
$feature_name = sanitizeInput($_POST['feature_name']);

if (empty($feature_name) || $machine_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please provide valid machine ID and feature name']);
    exit;
}

try {
    // Check if machine exists
    $machine_check = $conn->prepare("SELECT id FROM machines WHERE id = ?");
    $machine_check->bind_param("i", $machine_id);
    $machine_check->execute();
    
    if ($machine_check->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Machine not found']);
        exit;
    }
    
    // Check if feature already exists for this machine
    $feature_check = $conn->prepare("SELECT id FROM machine_features WHERE machine_id = ? AND feature_name = ?");
    $feature_check->bind_param("is", $machine_id, $feature_name);
    $feature_check->execute();
    
    if ($feature_check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => "Feature '$feature_name' already exists for this machine!"]);
        exit;
    }
    
    // Insert new feature
    $insert_stmt = $conn->prepare("INSERT INTO machine_features (machine_id, feature_name) VALUES (?, ?)");
    $insert_stmt->bind_param("is", $machine_id, $feature_name);
    
    if ($insert_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => "Feature '$feature_name' added successfully!"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding feature: ' . $conn->error]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
