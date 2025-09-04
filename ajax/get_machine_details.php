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
    $sql = "SELECT * FROM machines WHERE id = $machine_id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $machine = $result->fetch_assoc();
        
        // Convert NULL values to empty strings for frontend
        foreach ($machine as $key => $value) {
            if ($value === null) {
                $machine[$key] = '';
            }
        }
        
        echo json_encode([
            'success' => true,
            'machine' => $machine
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Machine not found'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching machine details: ' . $e->getMessage()
    ]);
}
?>
