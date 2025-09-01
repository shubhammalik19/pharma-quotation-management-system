<?php
header('Content-Type: application/json');

include '../common/conn.php';
include '../common/functions.php';

// Check if user is logged in
checkLogin();

$response = array('success' => false, 'customer' => null);

if (isset($_GET['id'])) {
    $customer_id = intval($_GET['id']);
    
    $sql = "SELECT * FROM customers WHERE id = $customer_id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $customer = $result->fetch_assoc();
        
        // Convert NULL values to empty strings for frontend
        foreach ($customer as $key => $value) {
            if ($value === null) {
                $customer[$key] = '';
            }
        }
        
        $response['success'] = true;
        $response['customer'] = $customer;
    }
}

echo json_encode($response);
?>
