<?php
header('Content-Type: application/json');
include '../common/conn.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid feature ID']);
    exit;
}

$feature_id = intval($_GET['id']);

try {
    // Get feature name for confirmation message
    $feature_stmt = $conn->prepare("SELECT feature_name FROM machine_features WHERE id = ?");
    $feature_stmt->bind_param("i", $feature_id);
    $feature_stmt->execute();
    $result = $feature_stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Feature not found']);
        exit;
    }
    
    $feature_name = $result->fetch_assoc()['feature_name'];
    
    // Delete feature
    $delete_stmt = $conn->prepare("DELETE FROM machine_features WHERE id = ?");
    $delete_stmt->bind_param("i", $feature_id);
    
    if ($delete_stmt->execute()) {
        if ($conn->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => "Feature '$feature_name' deleted successfully!"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Feature not found or already deleted']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting feature: ' . $conn->error]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
