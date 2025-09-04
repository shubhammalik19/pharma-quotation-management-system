<?php
require_once '../common/conn.php';
require_once '../common/functions.php';

// Check if user is logged in and has permissions
checkLoginAndPermission('reports_purchase_invoices', 'view');

// Get filter parameters
$vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$start_date = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : '';
$min_amount = isset($_GET['min_amount']) ? floatval($_GET['min_amount']) : 0;
$max_amount = isset($_GET['max_amount']) ? floatval($_GET['max_amount']) : 0;
$pi_number = isset($_GET['pi_number']) ? sanitizeInput($_GET['pi_number']) : '';
$po_number = isset($_GET['po_number']) ? sanitizeInput($_GET['po_number']) : '';

// Build WHERE clause
$where_conditions = [];

if ($vendor_id > 0) {
    $where_conditions[] = "pi.vendor_id = $vendor_id";
}

if (!empty($status)) {
    $where_conditions[] = "pi.status = '$status'";
}

// Date range filter
if (!empty($start_date) && !empty($end_date)) {
    $where_conditions[] = "pi.pi_date BETWEEN '$start_date' AND '$end_date'";
} elseif (!empty($start_date)) {
    $where_conditions[] = "pi.pi_date >= '$start_date'";
} elseif (!empty($end_date)) {
    $where_conditions[] = "pi.pi_date <= '$end_date'";
}

// Amount range filter
if ($min_amount > 0 && $max_amount > 0) {
    $where_conditions[] = "pi.final_total BETWEEN $min_amount AND $max_amount";
} elseif ($min_amount > 0) {
    $where_conditions[] = "pi.final_total >= $min_amount";
} elseif ($max_amount > 0) {
    $where_conditions[] = "pi.final_total <= $max_amount";
}

// PI Number filter
if (!empty($pi_number)) {
    $where_conditions[] = "pi.pi_number LIKE '%$pi_number%'";
}

// PO Number filter
if (!empty($po_number)) {
    $where_conditions[] = "pi.purchase_order_number LIKE '%$po_number%'";
}

// Construct WHERE clause
$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Main query
$sql = "SELECT 
            pi.*,
            c.company_name as vendor_name,
            c.contact_person as vendor_contact,
            c.phone as vendor_phone,
            c.email as vendor_email,
            (SELECT COUNT(*) FROM purchase_invoice_items pii WHERE pii.pi_id = pi.id) as item_count,
            (SELECT SUM(quantity) FROM purchase_invoice_items pii WHERE pii.pi_id = pi.id) as total_quantity
        FROM purchase_invoices pi
        LEFT JOIN customers c ON pi.vendor_id = c.id
        $where_clause
        ORDER BY pi.pi_date DESC, pi.id DESC";

$result = $conn->query($sql);

// Calculate summary statistics
$summary_sql = "SELECT 
                    COUNT(*) as total_invoices,
                    SUM(pi.final_total) as total_amount,
                    AVG(pi.final_total) as avg_amount,
                    SUM(CASE WHEN pi.status = 'paid' THEN pi.final_total ELSE 0 END) as paid_amount,
                    SUM(CASE WHEN pi.status = 'pending' THEN pi.final_total ELSE 0 END) as pending_amount,
                    SUM(CASE WHEN pi.status = 'overdue' THEN pi.final_total ELSE 0 END) as overdue_amount
                FROM purchase_invoices pi
                LEFT JOIN customers c ON pi.vendor_id = c.id
                $where_clause";

$summary_result = $conn->query($summary_sql);
$summary = $summary_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Invoices Report Data</title>
    
    <!-- DataTables Plugin -->
    <?php require_once '../common/datatables_plugin.php'; ?>
</head>
<body class="bg-light">



<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="bi bi-receipt-cutoff text-dark"></i> Purchase Invoices Report</h2>
                    <p class="text-muted">Detailed analysis of purchase invoice data</p>
                </div>
                <div>
                    <a href="purchase_invoices_filter.php" class="btn btn-outline-secondary">
                        <i class="bi bi-funnel"></i> Change Filters
                    </a>
                    <a href="../reports.php" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left"></i> Back to Reports
                    </a>
                </div>
            </div>
            <hr>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-center bg-primary text-white">
                <div class="card-body">
                    <h5><?php echo number_format($summary['total_invoices']); ?></h5>
                    <small>Total Invoices</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <h5>₹<?php echo number_format($summary['total_amount'], 2); ?></h5>
                    <small>Total Amount</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center bg-info text-white">
                <div class="card-body">
                    <h5>₹<?php echo number_format($summary['avg_amount'], 2); ?></h5>
                    <small>Average Amount</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <h5>₹<?php echo number_format($summary['paid_amount'], 2); ?></h5>
                    <small>Paid Amount</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center bg-warning text-white">
                <div class="card-body">
                    <h5>₹<?php echo number_format($summary['pending_amount'], 2); ?></h5>
                    <small>Pending Amount</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center bg-danger text-white">
                <div class="card-body">
                    <h5>₹<?php echo number_format($summary['overdue_amount'], 2); ?></h5>
                    <small>Overdue Amount</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-download"></i> Export Options</h6>
                        <div>
                            <button class="btn btn-success btn-sm" onclick="exportToExcel()">
                                <i class="bi bi-file-earmark-excel"></i> Excel
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="exportToPDF()">
                                <i class="bi bi-file-earmark-pdf"></i> PDF
                            </button>
                            <button class="btn btn-info btn-sm" onclick="window.print()">
                                <i class="bi bi-printer"></i> Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-table"></i> Purchase Invoices Data</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="purchaseInvoicesTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>PI Number</th>
                            <th>Date</th>
                            <th>Due Date</th>
                            <th>Vendor</th>
                            <th>PO Number</th>
                            <th>Items</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($invoice = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($invoice['pi_number']); ?></strong>
                                    </td>
                                    <td><?php echo formatDate($invoice['pi_date']); ?></td>
                                    <td><?php echo formatDate($invoice['due_date']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($invoice['vendor_name']); ?></strong>
                                        <?php if (!empty($invoice['vendor_contact'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($invoice['vendor_contact']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($invoice['purchase_order_number'] ?: '-'); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $invoice['item_count']; ?> items</span>
                                        <?php if ($invoice['total_quantity']): ?>
                                            <br><small class="text-muted">Qty: <?php echo number_format($invoice['total_quantity']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong>₹<?php echo number_format($invoice['final_total'], 2); ?></strong>
                                        <?php if ($invoice['discount_amount'] > 0): ?>
                                            <br><small class="text-muted">Disc: ₹<?php echo number_format($invoice['discount_amount'], 2); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_colors = [
                                            'draft' => 'secondary',
                                            'pending' => 'warning',
                                            'paid' => 'success',
                                            'partially_paid' => 'info',
                                            'overdue' => 'danger',
                                            'cancelled' => 'dark'
                                        ];
                                        $color = $status_colors[$invoice['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $invoice['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="../docs/print_purchase_invoice.php?id=<?php echo $invoice['id']; ?>" 
                                               target="_blank" class="btn btn-outline-primary" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="../docs/print_purchase_invoice.php?id=<?php echo $invoice['id']; ?>&pdf=1" 
                                               target="_blank" class="btn btn-outline-danger" title="PDF">
                                                <i class="bi bi-file-pdf"></i>
                                            </a>
                                            <?php if (hasPermission('purchase_invoices', 'edit')): ?>
                                                <a href="../sales/purchase_invoices.php?id=<?php echo $invoice['id']; ?>" 
                                                   class="btn btn-outline-warning" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="bi bi-inbox display-4 text-muted"></i>
                                    <h5 class="text-muted mt-3">No purchase invoices found</h5>
                                    <p class="text-muted">Try adjusting your filter criteria</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#purchaseInvoicesTable').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[1, 'desc']],
        columnDefs: [
            {
                targets: [6], // Amount column
                className: 'text-end'
            }
        ],
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
});

function exportToExcel() {
    window.location.href = '<?php echo $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']; ?>&export=excel';
}

function exportToPDF() {
    window.location.href = '<?php echo $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']; ?>&export=pdf';
}
</script>

</body>
</html>
