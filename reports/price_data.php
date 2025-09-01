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
if (!hasPermission('reports_price', 'view')) {
    header('Location: ../auth/access_denied.php');
    exit();
}

// Get filter parameters
$machine_id = isset($_GET['machine_id']) ? intval($_GET['machine_id']) : 0;
$price_range = isset($_GET['price_range']) ? sanitizeInput($_GET['price_range']) : '';
$validity_status = isset($_GET['validity_status']) ? sanitizeInput($_GET['validity_status']) : '';
$is_active = isset($_GET['is_active']) ? sanitizeInput($_GET['is_active']) : '';
$valid_from = isset($_GET['valid_from']) ? sanitizeInput($_GET['valid_from']) : '';
$valid_to = isset($_GET['valid_to']) ? sanitizeInput($_GET['valid_to']) : '';

// Build WHERE clause
$where_conditions = [];
if ($machine_id > 0) {
    $where_conditions[] = "pm.machine_id = $machine_id";
}

if (!empty($price_range)) {
    switch ($price_range) {
        case 'under_1l':
            $where_conditions[] = "pm.price < 100000";
            break;
        case '1l_5l':
            $where_conditions[] = "pm.price BETWEEN 100000 AND 500000";
            break;
        case '5l_10l':
            $where_conditions[] = "pm.price BETWEEN 500000 AND 1000000";
            break;
        case 'over_10l':
            $where_conditions[] = "pm.price > 1000000";
            break;
    }
}

if (!empty($validity_status)) {
    $today = date('Y-m-d');
    switch ($validity_status) {
        case 'current':
            $where_conditions[] = "pm.valid_from <= '$today' AND pm.valid_to >= '$today'";
            break;
        case 'expired':
            $where_conditions[] = "pm.valid_to < '$today'";
            break;
        case 'future':
            $where_conditions[] = "pm.valid_from > '$today'";
            break;
    }
}

if ($is_active !== '') {
    $where_conditions[] = "pm.is_active = " . intval($is_active);
}

if (!empty($valid_from)) {
    $where_conditions[] = "pm.valid_from >= '$valid_from'";
}

if (!empty($valid_to)) {
    $where_conditions[] = "pm.valid_to <= '$valid_to'";
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
              FROM price_master pm
              LEFT JOIN machines m ON pm.machine_id = m.id
              $where_clause";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Build main query
$sql = "SELECT 
            pm.id,
            pm.price,
            pm.valid_from,
            pm.valid_to,
            pm.is_active,
            pm.created_at,
            m.name as machine_name,
            m.model,
            m.category,
            CASE 
                WHEN pm.valid_to < CURDATE() THEN 'Expired'
                WHEN pm.valid_from > CURDATE() THEN 'Future'
                ELSE 'Current'
            END as validity_status,
            DATEDIFF(pm.valid_to, CURDATE()) as days_to_expiry
        FROM price_master pm
        LEFT JOIN machines m ON pm.machine_id = m.id
        $where_clause
        ORDER BY pm.created_at DESC, m.name ASC
        LIMIT $records_per_page OFFSET $offset";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Price Master Report</title>
    
    <!-- DataTables Plugin -->
    <?php require_once '../common/datatables_plugin.php'; ?>
</head>
<body class="bg-light">

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-currency-rupee"></i> Price Master Report Results</h2>
            <p class="text-muted">Showing <?php echo $total_records; ?> records based on applied filters</p>
            <hr>
        </div>
    </div>

    <!-- Results -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="bi bi-table"></i> Report Results (<?php echo $total_records; ?> records)</h5>
            <div>
                <a href="price_filter.php" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="bi bi-arrow-left"></i> Back to Filters
                </a>
                <?php if (hasPermission('reports_price', 'export')): ?>
                <button class="btn btn-success btn-sm me-2" id="exportExcel">
                    <i class="bi bi-file-excel"></i> Export Excel
                </button>
                <button class="btn btn-danger btn-sm me-2" id="exportPDF">
                    <i class="bi bi-file-pdf"></i> Export PDF
                </button>
                <?php endif; ?>
                <?php if (hasPermission('reports_price', 'print')): ?>
                <button class="btn btn-info btn-sm" id="printReport">
                    <i class="bi bi-printer"></i> Print
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="priceTable">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Machine</th>
                            <th>Price</th>
                            <th>Valid From</th>
                            <th>Valid To</th>
                            <th>Validity Status</th>
                            <th>Days to Expiry</th>
                            <th>Status</th>
                            <th>Created Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($row['machine_name'] ?? 'Unknown Machine'); ?></strong>
                                        <?php if (!empty($row['model'])): ?>
                                            <br><small class="text-muted">Model: <?php echo htmlspecialchars($row['model']); ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($row['category'])): ?>
                                            <br><span class="badge bg-secondary"><?php echo htmlspecialchars($row['category']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <strong class="text-success">₹<?php echo number_format($row['price'], 2); ?></strong>
                                </td>
                                <td><?php echo $row['valid_from'] ? formatDate($row['valid_from']) : '-'; ?></td>
                                <td><?php echo $row['valid_to'] ? formatDate($row['valid_to']) : '-'; ?></td>
                                <td>
                                    <?php
                                    $validity_class = 'secondary';
                                    if ($row['validity_status'] === 'Current') $validity_class = 'success';
                                    elseif ($row['validity_status'] === 'Expired') $validity_class = 'danger';
                                    elseif ($row['validity_status'] === 'Future') $validity_class = 'warning';
                                    ?>
                                    <span class="badge bg-<?php echo $validity_class; ?>"><?php echo htmlspecialchars($row['validity_status']); ?></span>
                                </td>
                                <td>
                                    <?php if ($row['validity_status'] === 'Current'): ?>
                                        <?php if ($row['days_to_expiry'] > 30): ?>
                                            <span class="text-success"><?php echo $row['days_to_expiry']; ?> days</span>
                                        <?php elseif ($row['days_to_expiry'] > 0): ?>
                                            <span class="text-warning"><?php echo $row['days_to_expiry']; ?> days</span>
                                        <?php else: ?>
                                            <span class="text-danger">Expires today</span>
                                        <?php endif; ?>
                                    <?php elseif ($row['validity_status'] === 'Expired'): ?>
                                        <span class="text-danger"><?php echo abs($row['days_to_expiry']); ?> days ago</span>
                                    <?php elseif ($row['validity_status'] === 'Future'): ?>
                                        <span class="text-info">In <?php echo abs($row['days_to_expiry']); ?> days</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $row['is_active'] ? 'success' : 'danger'; ?>">
                                        <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($row['created_at']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="bi bi-currency-rupee display-4 text-muted"></i>
                                    <p class="mt-3">No pricing data found with the current filters.</p>
                                    <a href="price_filter.php" class="btn btn-primary">
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
    const table = initDataTable('#priceTable', {
        paging: false, // We're handling pagination server-side
        searching: false, // We have custom search filters
        info: false, // We show custom info
        columnDefs: [
            { orderable: false, targets: [5, 6] }, // Validity status and days to expiry columns
            { targets: [2], type: 'num-fmt' }, // Price column
            { targets: [3, 4, 8], type: 'date' } // Date columns
        ]
    });
    
    // Add export handlers using the utility function
    addExportHandlers(table);
});
</script>

</body>
</html>
