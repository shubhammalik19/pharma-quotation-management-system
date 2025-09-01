<?php
require_once '../common/conn.php';
require_once '../common/functions.php';

// Check if user is logged in and has permissions
checkLoginAndPermission('reports_purchase_orders', 'view');

// Get filter parameters
$vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;
$machine_id = isset($_GET['machine_id']) ? intval($_GET['machine_id']) : 0;
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$amount_range = isset($_GET['amount_range']) ? sanitizeInput($_GET['amount_range']) : '';
$sales_order_id = isset($_GET['sales_order_id']) ? sanitizeInput($_GET['sales_order_id']) : '';
$due_status = isset($_GET['due_status']) ? sanitizeInput($_GET['due_status']) : '';
$date_from = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : '';
$due_date_from = isset($_GET['due_date_from']) ? sanitizeInput($_GET['due_date_from']) : '';
$due_date_to = isset($_GET['due_date_to']) ? sanitizeInput($_GET['due_date_to']) : '';
$value_analysis = isset($_GET['value_analysis']) ? sanitizeInput($_GET['value_analysis']) : '';
$time_period = isset($_GET['time_period']) ? sanitizeInput($_GET['time_period']) : '';
$include_cancelled = isset($_GET['include_cancelled']) ? sanitizeInput($_GET['include_cancelled']) : 'yes';

// Build WHERE clause
$where_conditions = [];

if ($vendor_id > 0) {
    $where_conditions[] = "po.vendor_id = $vendor_id";
}

if (!empty($status) && $status !== '') {
    $where_conditions[] = "po.status = '$status'";
}

if (!empty($amount_range) && $amount_range !== '') {
    switch ($amount_range) {
        case 'under_1l':
            $where_conditions[] = "po.final_total < 100000";
            break;
        case '1l_5l':
            $where_conditions[] = "po.final_total BETWEEN 100000 AND 500000";
            break;
        case '5l_10l':
            $where_conditions[] = "po.final_total BETWEEN 500000 AND 1000000";
            break;
        case '10l_25l':
            $where_conditions[] = "po.final_total BETWEEN 1000000 AND 2500000";
            break;
        case '25l_50l':
            $where_conditions[] = "po.final_total BETWEEN 2500000 AND 5000000";
            break;
        case 'over_50l':
            $where_conditions[] = "po.final_total > 5000000";
            break;
    }
}

if (!empty($sales_order_id) && $sales_order_id !== '') {
    if ($sales_order_id === '0') {
        $where_conditions[] = "po.sales_order_id IS NULL";
    } else {
        $where_conditions[] = "po.sales_order_id = " . intval($sales_order_id);
    }
}

if (!empty($date_from) && $date_from !== '') {
    $where_conditions[] = "po.po_date >= '$date_from'";
}

if (!empty($date_to) && $date_to !== '') {
    $where_conditions[] = "po.po_date <= '$date_to'";
}

if (!empty($due_date_from) && $due_date_from !== '') {
    $where_conditions[] = "po.due_date >= '$due_date_from'";
}

if (!empty($due_date_to) && $due_date_to !== '') {
    $where_conditions[] = "po.due_date <= '$due_date_to'";
}

if (!empty($due_status) && $due_status !== '') {
    $today = date('Y-m-d');
    switch ($due_status) {
        case 'no_due_date':
            $where_conditions[] = "po.due_date IS NULL";
            break;
        case 'not_due':
            $where_conditions[] = "po.status NOT IN ('received') AND po.due_date > '$today'";
            break;
        case 'due_today':
            $where_conditions[] = "po.status NOT IN ('received') AND po.due_date = '$today'";
            break;
        case 'overdue':
            $where_conditions[] = "po.status NOT IN ('received') AND po.due_date < '$today'";
            break;
        case 'received_ontime':
            $where_conditions[] = "po.status = 'received' AND po.due_date >= po.po_date";
            break;
        case 'received_late':
            $where_conditions[] = "po.status = 'received' AND po.due_date < po.po_date";
            break;
    }
}

// Handle cancelled orders
if ($include_cancelled === 'no') {
    $where_conditions[] = "po.status != 'cancelled'";
} elseif ($include_cancelled === 'only') {
    $where_conditions[] = "po.status = 'cancelled'";
}

// Filter by machine if specified (through purchase order items)
if ($machine_id > 0) {
    $where_conditions[] = "EXISTS (SELECT 1 FROM purchase_order_items poi WHERE poi.po_id = po.id AND poi.item_type = 'machine' AND poi.item_id = $machine_id)";
}

// Handle time period
if (!empty($time_period) && $time_period !== '') {
    $today = date('Y-m-d');
    switch ($time_period) {
        case 'today':
            $where_conditions[] = "po.po_date = '$today'";
            break;
        case 'yesterday':
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $where_conditions[] = "po.po_date = '$yesterday'";
            break;
        case 'this_week':
            $start_of_week = date('Y-m-d', strtotime('monday this week'));
            $where_conditions[] = "po.po_date >= '$start_of_week' AND po.po_date <= '$today'";
            break;
        case 'last_week':
            $start_last_week = date('Y-m-d', strtotime('monday last week'));
            $end_last_week = date('Y-m-d', strtotime('sunday last week'));
            $where_conditions[] = "po.po_date >= '$start_last_week' AND po.po_date <= '$end_last_week'";
            break;
        case 'this_month':
            $start_month = date('Y-m-01');
            $where_conditions[] = "po.po_date >= '$start_month' AND po.po_date <= '$today'";
            break;
        case 'last_month':
            $start_last_month = date('Y-m-01', strtotime('last month'));
            $end_last_month = date('Y-m-t', strtotime('last month'));
            $where_conditions[] = "po.po_date >= '$start_last_month' AND po.po_date <= '$end_last_month'";
            break;
        case 'this_quarter':
            $current_month = date('n');
            $quarter_start_month = (floor(($current_month - 1) / 3) * 3) + 1;
            $quarter_start = date('Y-' . sprintf('%02d', $quarter_start_month) . '-01');
            $where_conditions[] = "po.po_date >= '$quarter_start' AND po.po_date <= '$today'";
            break;
        case 'this_year':
            $start_year = date('Y-01-01');
            $where_conditions[] = "po.po_date >= '$start_year' AND po.po_date <= '$today'";
            break;
        case 'last_year':
            $last_year = date('Y', strtotime('-1 year'));
            $where_conditions[] = "YEAR(po.po_date) = $last_year";
            break;
    }
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
              FROM purchase_orders po
              LEFT JOIN customers c ON po.vendor_id = c.id
              $where_clause";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Build main query
$sql = "SELECT 
            po.id,
            po.po_number,
            po.po_date,
            po.due_date,
            po.total_amount,
            po.discount_amount,
            po.final_total,
            po.status,
            po.vendor_name,
            po.sales_order_number,
            po.quotation_number,
            po.notes,
            po.created_at,
            c.company_name as vendor_company_name,
            c.contact_person as vendor_contact_person,
            c.gst_no as vendor_gst_no,
            CASE 
                WHEN po.due_date IS NULL THEN 'No Due Date'
                WHEN po.due_date < CURDATE() AND po.status NOT IN ('received') THEN 'Overdue'
                WHEN po.due_date = CURDATE() AND po.status NOT IN ('received') THEN 'Due Today'
                WHEN po.due_date > CURDATE() AND po.status NOT IN ('received') THEN 'Not Due Yet'
                WHEN po.status = 'received' THEN 'Received'
                ELSE 'Unknown'
            END as due_status,
            CASE 
                WHEN po.due_date IS NOT NULL THEN DATEDIFF(po.due_date, CURDATE())
                ELSE NULL
            END as days_to_due
        FROM purchase_orders po
        LEFT JOIN customers c ON po.vendor_id = c.id
        $where_clause
        ORDER BY po.created_at DESC
        LIMIT $records_per_page OFFSET $offset";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders Report</title>
    
    <!-- DataTables Plugin -->
    <?php require_once '../common/datatables_plugin.php'; ?>
</head>
<body class="bg-light">

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="bi bi-cart-plus-fill"></i> Purchase Orders Report Results</h2>
                    <p class="text-muted">Showing <?php echo $total_records; ?> records based on applied filters</p>
                </div>
                <a href="purchase_orders_filter.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Filters
                </a>
            </div>
            <hr>
        </div>
    </div>

    <!-- Results -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="bi bi-table"></i> Report Results (<?php echo $total_records; ?> records)</h5>
            <div>
                <a href="purchase_orders_filter.php" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="bi bi-arrow-left"></i> Back to Filters
                </a>
                <?php if (hasPermission('reports_purchase_orders', 'export')): ?>
                <button class="btn btn-success btn-sm me-2" id="exportExcel">
                    <i class="bi bi-file-excel"></i> Export Excel
                </button>
                <button class="btn btn-danger btn-sm me-2" id="exportPDF">
                    <i class="bi bi-file-pdf"></i> Export PDF
                </button>
                <?php endif; ?>
                <?php if (hasPermission('reports_purchase_orders', 'print')): ?>
                <button class="btn btn-info btn-sm" id="printReport">
                    <i class="bi bi-printer"></i> Print
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="purchaseOrdersTable">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>PO Number</th>
                            <th>Vendor</th>
                            <th>PO Date</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Discount</th>
                            <th>Final Total</th>
                            <th>Status</th>
                            <th>Due Status</th>
                            <th>SO Reference</th>
                            <th>Created Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['po_number']); ?></strong>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($row['vendor_company_name'] ?? $row['vendor_name'] ?? 'Unknown Vendor'); ?></strong>
                                        <?php if (!empty($row['vendor_contact_person'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($row['vendor_contact_person']); ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($row['vendor_gst_no'])): ?>
                                            <br><small class="text-info">GST: <?php echo htmlspecialchars($row['vendor_gst_no']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo formatDate($row['po_date']); ?></td>
                                <td>
                                    <?php if (!empty($row['due_date'])): ?>
                                        <?php echo formatDate($row['due_date']); ?>
                                        <?php if ($row['days_to_due'] !== null): ?>
                                            <br><small class="text-muted">
                                                <?php 
                                                if ($row['days_to_due'] > 0) {
                                                    echo $row['days_to_due'] . ' days left';
                                                } elseif ($row['days_to_due'] < 0) {
                                                    echo abs($row['days_to_due']) . ' days overdue';
                                                } else {
                                                    echo 'Due today';
                                                }
                                                ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not set</span>
                                    <?php endif; ?>
                                </td>
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
                                    <strong class="text-success">₹<?php echo number_format($row['final_total'], 2); ?></strong>
                                </td>
                                <td>
                                    <?php
                                    $status_class = 'secondary';
                                    switch(strtolower($row['status'])) {
                                        case 'draft': $status_class = 'secondary'; break;
                                        case 'sent': $status_class = 'primary'; break;
                                        case 'acknowledged': $status_class = 'warning'; break;
                                        case 'received': $status_class = 'success'; break;
                                        case 'cancelled': $status_class = 'danger'; break;
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $status_class; ?>">
                                        <?php echo ucwords($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $due_class = 'secondary';
                                    switch($row['due_status']) {
                                        case 'Not Due Yet': $due_class = 'success'; break;
                                        case 'Due Today': $due_class = 'warning'; break;
                                        case 'Overdue': $due_class = 'danger'; break;
                                        case 'Received': $due_class = 'info'; break;
                                        case 'No Due Date': $due_class = 'secondary'; break;
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $due_class; ?>"><?php echo $row['due_status']; ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $so_ref = htmlspecialchars($row['sales_order_number'] ?? '');
                                    echo !empty($so_ref) ? $so_ref : '<span class="text-muted">-</span>';
                                    ?>
                                </td>
                                <td><?php echo formatDate($row['created_at']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="12" class="text-center py-4">
                                    <i class="bi bi-cart-x display-4 text-muted"></i>
                                    <p class="mt-3">No purchase orders found with the current filters.</p>
                                    <a href="purchase_orders_filter.php" class="btn btn-primary">
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
    const table = initDataTable('#purchaseOrdersTable', {
        paging: false, // We're handling pagination server-side
        searching: false, // We have custom search filters
        info: false, // We show custom info
        columnDefs: [
            { orderable: false, targets: [8, 9] }, // Status columns
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
