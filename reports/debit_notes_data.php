<?php
require_once '../common/conn.php';
require_once '../common/functions.php';

// Check if user is logged in and has permissions
checkLoginAndPermission('reports_debit_notes', 'view');

// Get filter parameters
$vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : '';
$amount_range = isset($_GET['amount_range']) ? sanitizeInput($_GET['amount_range']) : '';
$reason_category = isset($_GET['reason_category']) ? sanitizeInput($_GET['reason_category']) : '';
$original_invoice = isset($_GET['original_invoice']) ? sanitizeInput($_GET['original_invoice']) : '';
$gst_filter = isset($_GET['gst_filter']) ? sanitizeInput($_GET['gst_filter']) : '';
$min_amount = isset($_GET['min_amount']) ? floatval($_GET['min_amount']) : 0;
$max_amount = isset($_GET['max_amount']) ? floatval($_GET['max_amount']) : 0;
$value_analysis = isset($_GET['value_analysis']) ? sanitizeInput($_GET['value_analysis']) : '';
$time_period = isset($_GET['time_period']) ? sanitizeInput($_GET['time_period']) : '';
$include_cancelled = isset($_GET['include_cancelled']) ? sanitizeInput($_GET['include_cancelled']) : 'no';

// Build WHERE clause
$where_conditions = [];

if ($vendor_id > 0) {
    $where_conditions[] = "c.id = $vendor_id";
}

if (!empty($status) && $status !== '') {
    $where_conditions[] = "dn.status = '$status'";
}

// Date range filter
if (!empty($date_from) && !empty($date_to)) {
    $where_conditions[] = "dn.debit_date BETWEEN '$date_from' AND '$date_to'";
} elseif (!empty($date_from)) {
    $where_conditions[] = "dn.debit_date >= '$date_from'";
} elseif (!empty($date_to)) {
    $where_conditions[] = "dn.debit_date <= '$date_to'";
}

// Time period quick filter
if (!empty($time_period)) {
    $current_date = date('Y-m-d');
    switch ($time_period) {
        case 'today':
            $where_conditions[] = "dn.debit_date = '$current_date'";
            break;
        case 'yesterday':
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $where_conditions[] = "dn.debit_date = '$yesterday'";
            break;
        case 'this_week':
            $week_start = date('Y-m-d', strtotime('monday this week'));
            $where_conditions[] = "dn.debit_date BETWEEN '$week_start' AND '$current_date'";
            break;
        case 'last_week':
            $last_week_start = date('Y-m-d', strtotime('monday last week'));
            $last_week_end = date('Y-m-d', strtotime('sunday last week'));
            $where_conditions[] = "dn.debit_date BETWEEN '$last_week_start' AND '$last_week_end'";
            break;
        case 'this_month':
            $month_start = date('Y-m-01');
            $where_conditions[] = "dn.debit_date BETWEEN '$month_start' AND '$current_date'";
            break;
        case 'last_month':
            $last_month_start = date('Y-m-01', strtotime('first day of last month'));
            $last_month_end = date('Y-m-t', strtotime('last day of last month'));
            $where_conditions[] = "dn.debit_date BETWEEN '$last_month_start' AND '$last_month_end'";
            break;
        case 'this_quarter':
            $quarter_start = date('Y-m-01', strtotime(date('Y') . '-' . (floor((date('n') - 1) / 3) * 3 + 1) . '-01'));
            $where_conditions[] = "dn.debit_date BETWEEN '$quarter_start' AND '$current_date'";
            break;
        case 'this_year':
            $year_start = date('Y-01-01');
            $where_conditions[] = "dn.debit_date BETWEEN '$year_start' AND '$current_date'";
            break;
    }
}

// Amount range filter
if (!empty($amount_range)) {
    switch ($amount_range) {
        case 'under_10k':
            $where_conditions[] = "dn.total_amount < 10000";
            break;
        case '10k_50k':
            $where_conditions[] = "dn.total_amount BETWEEN 10000 AND 50000";
            break;
        case '50k_1l':
            $where_conditions[] = "dn.total_amount BETWEEN 50000 AND 100000";
            break;
        case '1l_5l':
            $where_conditions[] = "dn.total_amount BETWEEN 100000 AND 500000";
            break;
        case 'over_5l':
            $where_conditions[] = "dn.total_amount > 500000";
            break;
    }
}

// Min/Max amount filter
if ($min_amount > 0) {
    $where_conditions[] = "dn.total_amount >= $min_amount";
}
if ($max_amount > 0) {
    $where_conditions[] = "dn.total_amount <= $max_amount";
}

// Reason category filter
if (!empty($reason_category)) {
    $where_conditions[] = "dn.reason LIKE '%$reason_category%'";
}

// Original invoice filter
if (!empty($original_invoice)) {
    $where_conditions[] = "dn.original_invoice LIKE '%$original_invoice%'";
}

// GST filter
if (!empty($gst_filter)) {
    switch ($gst_filter) {
        case 'with_gst':
            $where_conditions[] = "dn.vendor_gstin IS NOT NULL AND dn.vendor_gstin != ''";
            break;
        case 'without_gst':
            $where_conditions[] = "(dn.vendor_gstin IS NULL OR dn.vendor_gstin = '')";
            break;
    }
}

// Handle cancelled debit notes
if ($include_cancelled === 'no') {
    $where_conditions[] = "dn.status != 'cancelled'";
} elseif ($include_cancelled === 'only') {
    $where_conditions[] = "dn.status = 'cancelled'";
}

// Build the WHERE clause
$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get total count
$count_sql = "SELECT COUNT(*) as total 
              FROM debit_notes dn 
              LEFT JOIN customers c ON (c.company_name = dn.vendor_name AND (c.entity_type = 'vendor' OR c.entity_type = 'both'))
              $where_clause";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 20;
$total_pages = ceil($total_records / $records_per_page);
$offset = ($page - 1) * $records_per_page;

// Main query
$sql = "SELECT dn.*, 
               c.company_name, c.contact_person, c.email as vendor_email, c.gst_no as vendor_gst_no,
               u.full_name as created_by_name
        FROM debit_notes dn 
        LEFT JOIN customers c ON (c.company_name = dn.vendor_name AND (c.entity_type = 'vendor' OR c.entity_type = 'both'))
        LEFT JOIN users u ON dn.created_by = u.id
        $where_clause 
        ORDER BY dn.debit_date DESC, dn.created_at DESC
        LIMIT $records_per_page OFFSET $offset";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debit Notes Report</title>
    
    <!-- DataTables Plugin -->
    <?php require_once '../common/datatables_plugin.php'; ?>
</head>
<body class="bg-light">

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="bi bi-plus-circle"></i> Debit Notes Report Results</h2>
                    <p class="text-muted">Showing <?php echo $total_records; ?> records based on applied filters</p>
                </div>
                <a href="debit_notes_filter.php" class="btn btn-outline-secondary">
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
                <a href="debit_notes_filter.php" class="btn btn-outline-secondary btn-sm">
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
                    <table id="debitNotesTable" class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Debit Note No.</th>
                                <th>Date</th>
                                <th>Vendor</th>
                                <th>Original Invoice</th>
                                <th>Amount</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['debit_note_number']); ?></strong>
                                    </td>
                                    <td><?php echo formatDate($row['debit_date']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['vendor_name']); ?></strong>
                                        <?php if (!empty($row['contact_person'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($row['contact_person']); ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($row['vendor_gstin'])): ?>
                                            <br><small class="text-info">GST: <?php echo htmlspecialchars($row['vendor_gstin']); ?></small>
                                        <?php elseif (!empty($row['vendor_gst_no'])): ?>
                                            <br><small class="text-info">GST: <?php echo htmlspecialchars($row['vendor_gst_no']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['original_invoice'])): ?>
                                            <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($row['original_invoice']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Not specified</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-primary">₹<?php echo number_format($row['total_amount'] ?? 0, 2); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo ucwords(htmlspecialchars($row['reason'] ?? 'Not specified')); ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        switch($row['status']) {
                                            case 'settled': $status_class = 'bg-success'; break;
                                            case 'acknowledged': $status_class = 'bg-warning'; break;
                                            case 'sent': $status_class = 'bg-primary'; break;
                                            case 'cancelled': $status_class = 'bg-danger'; break;
                                            case 'draft': $status_class = 'bg-secondary'; break;
                                            default: $status_class = 'bg-info';
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo ucwords($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['created_by_name'])): ?>
                                            <small><?php echo htmlspecialchars($row['created_by_name']); ?></small><br>
                                        <?php endif; ?>
                                        <small class="text-muted"><?php echo formatDate($row['created_at']); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="../sales/debit_notes.php?edit=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="View/Edit">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="../docs/print_debit_note.php?id=<?php echo $row['id']; ?>" 
                                               target="_blank" class="btn btn-sm btn-outline-info" title="Print">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                            <?php if (!empty($row['vendor_email'])): ?>
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
                    <i class="bi bi-plus-circle display-1 text-muted"></i>
                    <h4 class="mt-3">No Debit Notes Found</h4>
                    <p class="text-muted">Try adjusting your filters to see more results.</p>
                    <a href="debit_notes_filter.php" class="btn btn-primary">
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
                            COUNT(*) as total_debit_notes,
                            SUM(dn.total_amount) as total_amount,
                            SUM(CASE WHEN dn.status = 'settled' THEN dn.total_amount ELSE 0 END) as settled_amount,
                            SUM(CASE WHEN dn.status = 'acknowledged' THEN dn.total_amount ELSE 0 END) as acknowledged_amount,
                            SUM(CASE WHEN dn.status = 'draft' THEN dn.total_amount ELSE 0 END) as draft_amount,
                            SUM(CASE WHEN dn.status = 'cancelled' THEN dn.total_amount ELSE 0 END) as cancelled_amount,
                            AVG(dn.total_amount) as average_amount
                        FROM debit_notes dn 
                        LEFT JOIN customers c ON (c.company_name = dn.vendor_name AND (c.entity_type = 'vendor' OR c.entity_type = 'both'))
                        $where_clause";
        $summary_result = $conn->query($summary_sql);
        $summary = $summary_result->fetch_assoc();
        ?>
        
        <div class="row mt-4">
            <div class="col-md-2 mb-3">
                <div class="card text-white bg-primary">
                    <div class="card-body text-center">
                        <h4><?php echo $summary['total_debit_notes']; ?></h4>
                        <p class="card-text">Total Debit Notes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card text-white bg-info">
                    <div class="card-body text-center">
                        <h4>₹<?php echo number_format($summary['total_amount'], 0); ?></h4>
                        <p class="card-text">Total Amount</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card text-white bg-success">
                    <div class="card-body text-center">
                        <h4>₹<?php echo number_format($summary['settled_amount'], 0); ?></h4>
                        <p class="card-text">Settled Amount</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card text-white bg-warning">
                    <div class="card-body text-center">
                        <h4>₹<?php echo number_format($summary['acknowledged_amount'], 0); ?></h4>
                        <p class="card-text">Acknowledged Amount</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card text-white bg-secondary">
                    <div class="card-body text-center">
                        <h4>₹<?php echo number_format($summary['draft_amount'], 0); ?></h4>
                        <p class="card-text">Draft Amount</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card text-white bg-dark">
                    <div class="card-body text-center">
                        <h4>₹<?php echo number_format($summary['average_amount'], 0); ?></h4>
                        <p class="card-text">Average Amount</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#debitNotesTable').DataTable({
        pageLength: 20,
        responsive: true,
        order: [[1, 'desc']], // Sort by date descending
        columnDefs: [
            { targets: [4], className: 'text-end' }, // Right align amount column
            { targets: [8], orderable: false } // Disable sorting on actions column
        ]
    });
});
</script>

</body>
</html>
