<?php
require_once '../common/conn.php';
require_once '../common/functions.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Check permissions
if (!hasPermission('reports_quotations', 'view')) {
    header('Location: ../auth/access_denied.php');
    exit();
}

// Get filter parameters
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$machine_id = isset($_GET['machine_id']) ? intval($_GET['machine_id']) : 0;
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$amount_range = isset($_GET['amount_range']) ? sanitizeInput($_GET['amount_range']) : '';
$date_from = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : '';
$validity_status = isset($_GET['validity_status']) ? sanitizeInput($_GET['validity_status']) : '';

// Build WHERE clause
$where_conditions = [];
if ($customer_id > 0) {
    $where_conditions[] = "q.customer_id = $customer_id";
}

if (!empty($status) && $status !== '') {
    $where_conditions[] = "q.status = '$status'";
}

if (!empty($amount_range) && $amount_range !== '') {
    switch ($amount_range) {
        case 'under_1l':
            $where_conditions[] = "q.grand_total < 100000";
            break;
        case '1l_5l':
            $where_conditions[] = "q.grand_total BETWEEN 100000 AND 500000";
            break;
        case '5l_10l':
            $where_conditions[] = "q.grand_total BETWEEN 500000 AND 1000000";
            break;
        case '10l_25l':
            $where_conditions[] = "q.grand_total BETWEEN 1000000 AND 2500000";
            break;
        case 'over_25l':
            $where_conditions[] = "q.grand_total > 2500000";
            break;
    }
}

if (!empty($date_from) && $date_from !== '') {
    $where_conditions[] = "q.quotation_date >= '$date_from'";
}

if (!empty($date_to) && $date_to !== '') {
    $where_conditions[] = "q.quotation_date <= '$date_to'";
}

if (!empty($validity_status) && $validity_status !== '') {
    $today = date('Y-m-d');
    switch ($validity_status) {
        case 'valid':
            $where_conditions[] = "q.valid_until >= '$today'";
            break;
        case 'expired':
            $where_conditions[] = "q.valid_until < '$today'";
            break;
        case 'expiring_soon':
            $expiry_date = date('Y-m-d', strtotime('+30 days'));
            $where_conditions[] = "q.valid_until BETWEEN '$today' AND '$expiry_date'";
            break;
    }
}

// Filter by machine if specified (through quotation items)
if ($machine_id > 0) {
    $where_conditions[] = "EXISTS (SELECT 1 FROM quotation_items qi WHERE qi.quotation_id = q.id AND qi.item_type = 'machine' AND qi.item_id = $machine_id)";
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 25;
$offset = ($page - 1) * $records_per_page;

// Get total count
$count_sql = "SELECT COUNT(*) as total 
              FROM quotations q
              LEFT JOIN customers c ON q.customer_id = c.id
              $where_clause";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Build main query
$sql = "SELECT 
            q.id,
            q.quotation_number,
            q.quotation_date,
            q.valid_until,
            q.total_amount,
            q.discount_amount,
            q.grand_total,
            q.status,
            q.enquiry_ref,
            q.prepared_by,
            q.created_at,
            c.company_name as customer_name,
            c.contact_person,
            CASE 
                WHEN q.valid_until < CURDATE() THEN 'Expired'
                WHEN q.valid_until < DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Expiring Soon'
                ELSE 'Valid'
            END as validity_status,
            DATEDIFF(q.valid_until, CURDATE()) as days_to_expiry
        FROM quotations q
        LEFT JOIN customers c ON q.customer_id = c.id
        $where_clause
        ORDER BY q.created_at DESC
        LIMIT $records_per_page OFFSET $offset";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation Report</title>
    
    <!-- DataTables Plugin -->
    <?php require_once '../common/datatables_plugin.php'; ?>
</head>
<body class="bg-light">

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-file-text"></i> Quotation Report Results</h2>
            <p class="text-muted">Showing <?php echo $total_records; ?> records based on applied filters</p>
            <hr>
        </div>
    </div>

    <!-- Results -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="bi bi-table"></i> Report Results (<?php echo $total_records; ?> records)</h5>
            <div>
                <a href="quotations_filter.php" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="bi bi-arrow-left"></i> Back to Filters
                </a>
                <?php if (hasPermission('reports_quotations', 'export')): ?>
                <button class="btn btn-success btn-sm me-2" id="exportExcel">
                    <i class="bi bi-file-excel"></i> Export Excel
                </button>
                <button class="btn btn-danger btn-sm me-2" id="exportPDF">
                    <i class="bi bi-file-pdf"></i> Export PDF
                </button>
                <?php endif; ?>
                <?php if (hasPermission('reports_quotations', 'print')): ?>
                <button class="btn btn-info btn-sm" id="printReport">
                    <i class="bi bi-printer"></i> Print
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="quotationsTable">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Quotation No.</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Valid Until</th>
                            <th>Amount</th>
                            <th>Discount</th>
                            <th>Grand Total</th>
                            <th>Status</th>
                            <th>Validity</th>
                            <th>Enquiry Ref</th>
                            <th>Created Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['quotation_number']); ?></strong>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($row['customer_name'] ?? 'Unknown Customer'); ?></strong>
                                        <?php if (!empty($row['contact_person'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($row['contact_person']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo formatDate($row['quotation_date']); ?></td>
                                <td><?php echo formatDate($row['valid_until']); ?></td>
                                <td>
                                    <strong class="text-primary">₹<?php echo number_format($row['total_amount'], 2); ?></strong>
                                </td>
                                <td>
                                    <?php if ($row['discount_amount'] > 0): ?>
                                        <span class="text-warning">₹<?php echo number_format($row['discount_amount'], 2); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong class="text-success">₹<?php echo number_format($row['grand_total'], 2); ?></strong>
                                </td>
                                <td>
                                    <?php
                                    $status_class = 'secondary';
                                    switch(strtolower($row['status'])) {
                                        case 'pending': $status_class = 'warning'; break;
                                        case 'sent': $status_class = 'info'; break;
                                        case 'approved': $status_class = 'success'; break;
                                        case 'rejected': $status_class = 'danger'; break;
                                        case 'expired': $status_class = 'dark'; break;
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $status_class; ?>">
                                        <?php echo ucwords($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $validity_class = 'secondary';
                                    if ($row['validity_status'] === 'Valid') $validity_class = 'success';
                                    elseif ($row['validity_status'] === 'Expired') $validity_class = 'danger';
                                    elseif ($row['validity_status'] === 'Expiring Soon') $validity_class = 'warning';
                                    ?>
                                    <span class="badge bg-<?php echo $validity_class; ?>"><?php echo $row['validity_status']; ?></span>
                                    <?php if ($row['validity_status'] === 'Valid' && $row['days_to_expiry'] <= 30): ?>
                                        <br><small class="text-muted"><?php echo $row['days_to_expiry']; ?> days left</small>
                                    <?php elseif ($row['validity_status'] === 'Expired'): ?>
                                        <br><small class="text-muted"><?php echo abs($row['days_to_expiry']); ?> days ago</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $enquiry = htmlspecialchars($row['enquiry_ref'] ?? '');
                                    echo !empty($enquiry) ? $enquiry : '<span class="text-muted">-</span>';
                                    ?>
                                </td>
                                <td><?php echo formatDate($row['created_at']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="12" class="text-center py-4">
                                    <i class="bi bi-file-text display-4 text-muted"></i>
                                    <p class="mt-3">No quotations found with the current filters.</p>
                                    <a href="quotations_filter.php" class="btn btn-primary">
                                        <i class="bi bi-arrow-left"></i> Back to Filters
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Report pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php 
                        $query_params = $_GET;
                        unset($query_params['page']);
                        $base_url = '?' . http_build_query($query_params);
                        ?>
                        
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $base_url; ?>&page=<?php echo ($page - 1); ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo $base_url; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $base_url; ?>&page=<?php echo ($page + 1); ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    
                    <div class="text-center">
                        <small class="text-muted">
                            Showing <?php echo (($page - 1) * $records_per_page) + 1; ?> to 
                            <?php echo min($page * $records_per_page, $total_records); ?> of 
                            <?php echo $total_records; ?> entries
                        </small>
                    </div>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable using the utility function from datatables_plugin.php
    const table = initDataTable('#quotationsTable', {
        paging: false, // We're handling pagination server-side
        searching: false, // We have custom search filters
        info: false, // We show custom info
        columnDefs: [
            { orderable: false, targets: [8, 9] }, // Status and validity columns
            { targets: [5, 6, 7], type: 'num-fmt' }, // Amount columns
            { targets: [3, 4, 11], type: 'date' } // Date columns
        ]
    });
    
    // Add export handlers using the utility function
    addExportHandlers(table);
});
</script>

</body>
</html>
