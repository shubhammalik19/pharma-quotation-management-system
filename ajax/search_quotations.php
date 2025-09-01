<?php
include '../common/conn.php';
include '../common/functions.php';

// Check if user is logged in
checkLogin();

header('Content-Type: application/json');

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Build WHERE clause for search - Enhanced to search multiple fields
    $whereClause = '';
    if (!empty($search)) {
        $searchTerm = $conn->real_escape_string($search);
        $whereClause = "WHERE q.quotation_number LIKE '%$searchTerm%' 
                          OR c.company_name LIKE '%$searchTerm%' 
                          OR q.status LIKE '%$searchTerm%' 
                          OR q.enquiry_ref LIKE '%$searchTerm%'
                          OR q.prepared_by LIKE '%$searchTerm%'";
    }
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total 
                 FROM quotations q 
                 LEFT JOIN customers c ON q.customer_id = c.id 
                 $whereClause";
    $countResult = $conn->query($countSql);
    
    if (!$countResult) {
        throw new Exception('Count query failed: ' . $conn->error);
    }
    
    $totalRecords = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $limit);
    
    // Get quotations with enhanced fields
    $quotations_sql = "SELECT q.id, q.quotation_number, q.quotation_date, q.valid_until,
                              q.total_amount, q.grand_total, q.discount_percentage, q.discount_amount,
                              q.status, q.enquiry_ref, q.prepared_by, q.notes, q.created_at,
                              q.customer_id, c.company_name, c.contact_person, c.phone, c.email
                       FROM quotations q
                       LEFT JOIN customers c ON q.customer_id = c.id
                       $whereClause
                       ORDER BY q.created_at DESC, q.id DESC
                       LIMIT $limit OFFSET $offset";
    
    $quotations_result = $conn->query($quotations_sql);
    
    if (!$quotations_result) {
        throw new Exception('Quotations query failed: ' . $conn->error);
    }
    
    $quotations = [];
    if ($quotations_result->num_rows > 0) {
        while ($row = $quotations_result->fetch_assoc()) {
            // Format dates for better display
            $row['quotation_date_formatted'] = date('d/m/Y', strtotime($row['quotation_date']));
            $row['valid_until_formatted'] = $row['valid_until'] ? date('d/m/Y', strtotime($row['valid_until'])) : '';
            $row['created_at_formatted'] = date('d/m/Y H:i', strtotime($row['created_at']));
            
            // Calculate final amount
            $row['final_amount'] = $row['grand_total'] ?: $row['total_amount'];
            
            $quotations[] = $row;
        }
    }
    
    echo json_encode([
        'success' => true,
        'quotations' => $quotations,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'limit' => $limit
        ],
        'search_term' => $search
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>