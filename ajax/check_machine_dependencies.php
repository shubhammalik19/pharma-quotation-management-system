<?php
header('Content-Type: application/json');

include_once '../common/conn.php';
include_once '../common/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Machine ID is required']);
    exit();
}

$machine_id = intval($_GET['id']);

try {
    // Get machine name
    $machine_sql = "SELECT name, model, category FROM machines WHERE id = $machine_id";
    $machine_result = $conn->query($machine_sql);
    
    if (!$machine_result || $machine_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Machine not found']);
        exit();
    }
    
    $machine = $machine_result->fetch_assoc();
    $dependencies = checkMachineDependencies($conn, $machine_id);
    
    if (empty($dependencies)) {
        echo json_encode([
            'success' => true,
            'can_delete' => true,
            'machine_name' => $machine['name'],
            'machine_model' => $machine['model'],
            'machine_category' => $machine['category'],
            'message' => 'Machine can be safely deleted'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'can_delete' => false,
            'machine_name' => $machine['name'],
            'machine_model' => $machine['model'],
            'machine_category' => $machine['category'],
            'dependencies' => $dependencies,
            'message' => 'Machine has dependencies and cannot be deleted'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error checking dependencies: ' . $e->getMessage()
    ]);
}
?>
