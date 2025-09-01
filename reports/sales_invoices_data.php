<?php
require_once '../common/conn.php';
require_once '../common/functions.php';

// Check if user is logged in and has permissions
checkLoginAndPermission('reports_sales_invoices', 'view');

// Get filter parameters
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : '';
$amount_range = isset($_GET['amount_range']) ? sanitizeInput($_GET['amount_range']) : '';
$overdue_filter = isset($_GET['overdue_filter']) ? sanitizeInput($_GET['overdue_filter']) : '';
$gst_filter = isset($_GET['gst_filter']) ? sanitizeInput($_GET['gst_filter']) : '';
$time_period = isset($_GET['time_period']) ? sanitizeInput($_GET['time_period']) : '';

// Build WHERE clause
$where_conditions = [];

if ($customer_id > 0) {
    $where_conditions[] = "si.customer_id = $customer_id";
}

if (!empty($status) && $status !== '') {
    $where_conditions[] = "si.status = '$status'";
}

// Date range filter
if (!empty($date_from) && !empty($date_to)) {
    $where_conditions[] = "si.invoice_date BETWEEN '$date_from' AND '$date_to'";
} elseif (!empty($date_from)) {
    $where_conditions[] = "si.invoice_date >= '$date_from'";
} elseif (!empty($date_to)) {
    $where_conditions[] = "si.invoice_date <= '$date_to'";
}

// Time period quick filter
if (!empty($time_period)) {
    $current_date = date('Y-m-d');
    switch ($time_period) {
        case 'today':
            $where_conditions[] = "si.invoice_date = '$current_date'";
            break;
        case 'yesterday':
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $where_conditions[] = "si.invoice_date = '$yesterday'";
            break;
        case 'this_week':
            $week_start = date('Y-m-d', strtotime('monday this week'));
            $where_conditions[] = "si.invoice_date BETWEEN '$week_start' AND '$current_date'";
            break;
        case 'last_week':
            $last_week_start = date('Y-m-d', strtotime('monday last week'));
            $last_week_end = date('Y-m-d', strtotime('sunday last week'));
            $where_conditions[] = "si.invoice_date BETWEEN '$last_week_start' AND '$last_week_end'";
            break;
        case 'this_month':
            $month_start = date('Y-m-01');
            $where_conditions[] = "si.invoice_date BETWEEN '$month_start' AND '$current_date'";
            break;
        case 'last_month':
            $last_month_start = date('Y-m-01', strtotime('first day of last month'));
            $last_month_end = date('Y-m-t', strtotime('last day of last month'));
            $where_conditions[] = "si.invoice_date BETWEEN '$last_month_start' AND '$last_month_end'";
            break;
        case 'this_quarter':
            $quarter_start = date('Y-m-01', strtotime(date('Y') . '-' . (floor((date('n') - 1) / 3) * 3 + 1) . '-01'));
            $where_conditions[] = "si.invoice_date BETWEEN '$quarter_start' AND '$current_date'";
            break;
        case 'this_year':
            $year_start = date('Y-01-01');
            $where_conditions[] = "si.invoice_date BETWEEN '$year_start' AND '$current_date'";
            break;
    }
}

// Amount range filter
if (!empty($amount_range)) {
    switch ($amount_range) {
        case 'under_50k':
            $where_conditions[] = "si.final_total < 50000";
            break;
        case '50k_1l':
            $where_conditions[] = "si.final_total BETWEEN 50000 AND 100000";
            break;
        case '1l_5l':
            $where_conditions[] = "si.final_total BETWEEN 100000 AND 500000";
            break;
        case '5l_10l':
            $where_conditions[] = "si.final_total BETWEEN 500000 AND 1000000";
            break;
        case 'over_10l':
            $where_conditions[] = "si.final_total > 1000000";
            break;
    }
}

// Overdue filter
if (!empty($overdue_filter)) {
    switch ($overdue_filter) {
        case 'overdue':
            $where_conditions[] = "si.due_date < CURDATE() AND si.status NOT IN ('paid', 'cancelled')";
            break;
        case 'due_soon':
            $where_conditions[] = "si.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND si.status NOT IN ('paid', 'cancelled')";
            break;
        case 'not_due':
            $where_conditions[] = "(si.due_date > DATE_ADD(CURDATE(), INTERVAL 7 DAY) OR si.status IN ('paid', 'cancelled'))";
            break;
    }
}

// GST filter
if (!empty($gst_filter)) {
    switch ($gst_filter) {
        case 'with_gst':
            $where_conditions[] = "si.tax_amount > 0";
            break;
        case 'without_gst':
            $where_conditions[] = "(si.tax_amount IS NULL OR si.tax_amount = 0)";
            break;
    }
}

// Build the WHERE clause
$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get total count
$count_sql = "SELECT COUNT(*) as total 
              FROM sales_invoices si 
              LEFT JOIN customers c ON si.customer_id = c.id 
              $where_clause";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 20;
$total_pages = ceil($total_records / $records_per_page);
$offset = ($page - 1) * $records_per_page;

// Main query
$sql = "SELECT si.*, 
               c.company_name, c.contact_person, c.email as customer_email,
               CASE 
                   WHEN si.status IN ('paid', 'cancelled') THEN NULL
                   ELSE DATEDIFF(CURDATE(), si.due_date)
               END as days_outstanding
        FROM sales_invoices si 
        LEFT JOIN customers c ON si.customer_id = c.id 
        $where_clause 
        ORDER BY si.invoice_date DESC 
        LIMIT $records_per_page OFFSET $offset";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Invoices Report</title>
    
    <!-- DataTables Plugin -->
    <?php require_once '../common/datatables_plugin.php'; ?>
</head>
<body class="bg-light">

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="bi bi-receipt-cutoff"></i> Sales Invoices Report Results</h2>
                    <p class="text-muted">Showing <?php echo $total_records; ?> records based on applied filters</p>
                </div>
                <a href="sales_invoices_filter.php" class="btn btn-outline-secondary">
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
                <a href="sales_invoices_filter.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-funnel"></i> Change Filters
                </a>
                <a href="../reports.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back to Reports
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table id="salesInvoicesTable" class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Invoice Number</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Due Date</th>
                                <th>Amount</th>
                                <th>Tax</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Outstanding</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['invoice_number']); ?></strong>
                                    </td>
                                    <td><?php echo formatDate($row['invoice_date']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['customer_name'] ?? 'Unknown Customer'); ?></strong>
                                        <?php if (!empty($row['contact_person'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($row['contact_person']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo formatDate($row['due_date']); ?>
                                        <?php if ($row['days_outstanding'] !== null): ?>
                                            <?php if ($row['days_outstanding'] > 0): ?>
                                                <br><small class="text-danger"><?php echo $row['days_outstanding']; ?> days overdue</small>
                                            <?php elseif ($row['days_outstanding'] == 0): ?>
                                                <br><small class="text-warning">Due today</small>
                                            <?php else: ?>
                                                <br><small class="text-success"><?php echo abs($row['days_outstanding']); ?> days remaining</small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">₹<?php echo number_format($row['subtotal'] ?? 0, 2); ?></td>
                                    <td class="text-end">₹<?php echo number_format($row['tax_amount'] ?? 0, 2); ?></td>
                                    <td class="text-end">
                                        <strong>₹<?php echo number_format($row['final_total'] ?? 0, 2); ?></strong>
                                    </td>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        switch($row['status']) {
                                            case 'paid': $status_class = 'bg-success'; break;
                                            case 'sent': $status_class = 'bg-primary'; break;
                                            case 'overdue': $status_class = 'bg-danger'; break;
                                            case 'draft': $status_class = 'bg-secondary'; break;
                                            default: $status_class = 'bg-info';
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo ucwords($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['days_outstanding'] !== null): ?>
                                            <?php if ($row['days_outstanding'] > 0): ?>
                                                <span class="text-danger"><?php echo $row['days_outstanding']; ?> days</span>
                                            <?php elseif ($row['days_outstanding'] == 0): ?>
                                                <span class="text-warning">Due today</span>
                                            <?php else: ?>
                                                <span class="text-success">Not due</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="../sales/sales_invoices.php?edit=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="View/Edit">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="../docs/print_sales_invoice.php?id=<?php echo $row['id']; ?>" 
                                               target="_blank" class="btn btn-sm btn-outline-info" title="Print">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                            <?php if (!empty($row['customer_email'])): ?>
                                                <button type="button" class="btn btn-sm btn-outline-success" 
                                                        title="Email" data-id="<?php echo $row['id']; ?>">
                                                    <i class="bi bi-envelope"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <h4 class="mt-3">No Sales Invoices Found</h4>
                    <p class="text-muted">Try adjusting your filters to see more results.</p>
                    <a href="sales_invoices_filter.php" class="btn btn-primary">
                        <i class="bi bi-funnel"></i> Modify Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Summary Statistics -->
    <?php if ($result && $result->num_rows > 0): ?>
        <?php
        // Calculate summary
        $summary_sql = "SELECT 
                            COUNT(*) as total_invoices,
                            SUM(si.final_total) as total_amount,
                            SUM(CASE WHEN si.status = 'paid' THEN si.final_total ELSE 0 END) as paid_amount,
                            SUM(CASE WHEN si.status != 'paid' AND si.status != 'cancelled' THEN si.final_total ELSE 0 END) as pending_amount,
                            SUM(CASE WHEN si.due_date < CURDATE() AND si.status NOT IN ('paid', 'cancelled') THEN si.final_total ELSE 0 END) as overdue_amount
                        FROM sales_invoices si 
                        $where_clause";
        $summary_result = $conn->query($summary_sql);
        $summary = $summary_result->fetch_assoc();
        ?>
        
        <div class="row mt-4">
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-primary">
                    <div class="card-body text-center">
                        <h4><?php echo $summary['total_invoices']; ?></h4>
                        <p class="card-text">Total Invoices</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-success">
                    <div class="card-body text-center">
                        <h4>₹<?php echo number_format($summary['total_amount'], 0); ?></h4>
                        <p class="card-text">Total Amount</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-info">
                    <div class="card-body text-center">
                        <h4>₹<?php echo number_format($summary['paid_amount'], 0); ?></h4>
                        <p class="card-text">Paid Amount</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-danger">
                    <div class="card-body text-center">
                        <h4>₹<?php echo number_format($summary['overdue_amount'], 0); ?></h4>
                        <p class="card-text">Overdue Amount</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#salesInvoicesTable').DataTable({
        pageLength: 20,
        responsive: true,
        order: [[1, 'desc']], // Sort by date descending
        columnDefs: [
            { targets: [4, 5, 6], className: 'text-end' }, // Right align amount columns
            { targets: [9], orderable: false } // Disable sorting on actions column
        ]
    });
});
</script>

</body>
</html>
