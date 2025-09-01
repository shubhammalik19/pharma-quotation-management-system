<?php
require '../common/conn.php';
require '../common/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

try {
    // Get search parameters
    $search = sanitizeInput($_GET['search'] ?? '');
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Build search where clause
    $whereClause = '';
    if (!empty($search)) {
        $searchTerm = "%" . $conn->real_escape_string($search) . "%";
        $whereClause = "WHERE (so.so_number LIKE '$searchTerm' 
                           OR so.customer_name LIKE '$searchTerm' 
                           OR so.quotation_number LIKE '$searchTerm' 
                           OR so.status LIKE '$searchTerm' 
                           OR so.notes LIKE '$searchTerm')";
    }

    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM sales_orders so $whereClause";
    $countResult = $conn->query($countSql);
    if (!$countResult) {
        throw new Exception('Database error: ' . $conn->error);
    }
    $totalRecords = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $limit);

    // Get sales orders with pagination
    $sql = "SELECT so.id, so.so_number, so.customer_name, so.customer_id, 
                   so.so_date, so.delivery_date, so.total_amount, so.status, 
                   so.notes, so.quotation_number, so.quotation_id,
                   so.discount_percentage, so.discount_amount, so.created_at,
                   u.full_name as created_by_name
            FROM sales_orders so 
            LEFT JOIN users u ON so.created_by = u.id 
            $whereClause
            ORDER BY so.created_at DESC
            LIMIT $limit OFFSET $offset";

    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $sales_orders = [];
    while ($row = $result->fetch_assoc()) {
        // Format the date for display
        $row['so_date_formatted'] = !empty($row['so_date']) ? date('d/m/Y', strtotime($row['so_date'])) : '';
        $row['delivery_date_formatted'] = !empty($row['delivery_date']) ? date('d/m/Y', strtotime($row['delivery_date'])) : '';
        $row['created_at_formatted'] = !empty($row['created_at']) ? date('d/m/Y H:i', strtotime($row['created_at'])) : '';
        
        // Ensure numeric fields are properly formatted
        $row['total_amount'] = floatval($row['total_amount'] ?? 0);
        $row['discount_percentage'] = floatval($row['discount_percentage'] ?? 0);
        $row['discount_amount'] = floatval($row['discount_amount'] ?? 0);
        
        $sales_orders[] = $row;
    }

    // Prepare pagination data
    $pagination = [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_records' => $totalRecords,
        'per_page' => $limit,
        'has_next' => $page < $totalPages,
        'has_prev' => $page > 1
    ];

    echo json_encode([
        'success' => true,
        'sales_orders' => $sales_orders,
        'pagination' => $pagination
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Search failed: ' . $e->getMessage()
    ]);
}
?>
