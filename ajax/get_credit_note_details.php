<?php
require_once '../common/conn.php';
require_once '../common/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

try {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'Credit Note ID is required']);
        exit;
    }

    $cn_id = intval($_GET['id']);

    // Get credit note details
    $cn_sql = "SELECT cn.*, 
                      c.email as customer_email
               FROM credit_notes cn 
               LEFT JOIN customers c ON cn.customer_id = c.id 
               WHERE cn.id = $cn_id";
    
    $cn_result = $conn->query($cn_sql);
    
    if (!$cn_result || $cn_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Credit Note not found']);
        exit;
    }

    $credit_note = $cn_result->fetch_assoc();

    // Return the credit note data
    echo json_encode([
        'success' => true,
        'credit_note' => $credit_note
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error retrieving credit note details: ' . $e->getMessage()]);
}
?>
