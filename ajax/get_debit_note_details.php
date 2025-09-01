<?php
include '../common/conn.php';
include '../common/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Debit note ID is required']);
    exit;
}

$dn_id = intval($_GET['id']);

try {
    // Get debit note details with vendor information
    $sql = "SELECT dn.*, c.email as vendor_email
            FROM debit_notes dn
            LEFT JOIN customers c ON c.company_name = dn.vendor_name AND (c.entity_type = 'vendor' OR c.entity_type = 'both')
            WHERE dn.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $dn_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Debit note not found']);
        exit;
    }
    
    $debit_note = $result->fetch_assoc();
    
    // Format the response
    $response = [
        'success' => true,
        'debit_note' => [
            'id' => $debit_note['id'],
            'debit_note_number' => $debit_note['debit_note_number'],
            'vendor_name' => $debit_note['vendor_name'],
            'vendor_address' => $debit_note['vendor_address'],
            'vendor_gstin' => $debit_note['vendor_gstin'],
            'vendor_email' => $debit_note['vendor_email'],
            'original_invoice' => $debit_note['original_invoice'],
            'debit_date' => $debit_note['debit_date'],
            'total_amount' => $debit_note['total_amount'],
            'reason' => $debit_note['reason'],
            'status' => $debit_note['status'],
            'created_at' => $debit_note['created_at'],
            'updated_at' => $debit_note['updated_at']
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching debit note details: ' . $e->getMessage()]);
}
?>
