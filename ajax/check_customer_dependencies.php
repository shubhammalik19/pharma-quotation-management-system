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
    echo json_encode(['success' => false, 'message' => 'Customer ID is required']);
    exit();
}

$customer_id = intval($_GET['id']);

try {
    // Get customer name
    $customer_sql = "SELECT company_name, entity_type FROM customers WHERE id = $customer_id";
    $customer_result = $conn->query($customer_sql);
    
    if (!$customer_result || $customer_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
        exit();
    }
    
    $customer = $customer_result->fetch_assoc();
    $dependencies = checkCustomerDependencies($conn, $customer_id);
    
    if (empty($dependencies)) {
        echo json_encode([
            'success' => true,
            'can_delete' => true,
            'customer_name' => $customer['company_name'],
            'entity_type' => $customer['entity_type'],
            'message' => 'Customer can be safely deleted'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'can_delete' => false,
            'customer_name' => $customer['company_name'],
            'entity_type' => $customer['entity_type'],
            'dependencies' => $dependencies,
            'message' => 'Customer has dependencies and cannot be deleted'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error checking dependencies: ' . $e->getMessage()
    ]);
}
?>
