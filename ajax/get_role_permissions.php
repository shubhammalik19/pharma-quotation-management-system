<?php
include '../common/conn.php';
include '../common/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check permissions
if (!hasPermission('users', 'view')) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$role_id = isset($_GET['role_id']) ? intval($_GET['role_id']) : 0;

if ($role_id <= 0) {
    echo json_encode([]);
    exit;
}

// Get role permissions
$sql = "SELECT permission_id FROM role_permissions WHERE role_id = $role_id";
$result = $conn->query($sql);

$permissions = array();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row['permission_id'];
    }
}

header('Content-Type: application/json');
echo json_encode($permissions);
?>
