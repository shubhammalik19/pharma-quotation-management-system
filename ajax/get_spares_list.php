<?php
header('Content-Type: application/json');

include_once '../common/conn.php';
include_once '../common/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // Get all active spare parts
    $sql = "SELECT id, part_name, part_code, price FROM spares WHERE is_active = 1 ORDER BY part_name";
    $result = $conn->query($sql);
    
    $spares = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $spares[] = [
                'id' => $row['id'],
                'part_name' => $row['part_name'],
                'part_code' => $row['part_code'],
                'current_price' => $row['price']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'spares' => $spares,
        'count' => count($spares)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching spare parts: ' . $e->getMessage()
    ]);
}
?>
