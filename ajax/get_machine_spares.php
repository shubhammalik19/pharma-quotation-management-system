<?php

include '../common/conn.php';
include '../common/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$machine_id = intval($_GET['machine_id'] ?? 0);

if ($machine_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid machine ID']);
    exit;
}

try {
    // Get machine related spares
    $spares = getMachineRelatedSpares($conn, $machine_id);
    
    echo json_encode([
        'success' => true,
        'spares' => $spares
    ]);
} catch (Exception $e) {
    error_log("Error in get_machine_spares.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
