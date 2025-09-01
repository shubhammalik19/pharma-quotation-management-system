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
if (!hasPermission('reports_machines', 'view')) {
    header('Location: ../auth/access_denied.php');
    exit();
}

// Get filter parameters
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$price_range = isset($_GET['price_range']) ? sanitizeInput($_GET['price_range']) : '';
$is_active = isset($_GET['is_active']) ? sanitizeInput($_GET['is_active']) : '';
$created_from = isset($_GET['created_from']) ? sanitizeInput($_GET['created_from']) : '';
$created_to = isset($_GET['created_to']) ? sanitizeInput($_GET['created_to']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
?>

<?php
// Build WHERE clause
$where_conditions = [];
if (!empty($category)) {
    $where_conditions[] = "m.category LIKE '%$category%'";
}
if ($is_active !== '') {
    $where_conditions[] = "m.is_active = " . intval($is_active);
}
if (!empty($created_from)) {
    $where_conditions[] = "DATE(m.created_at) >= '$created_from'";
}
if (!empty($created_to)) {
    $where_conditions[] = "DATE(m.created_at) <= '$created_to'";
}
if (!empty($search)) {
    $search_conditions = [
        "m.name LIKE '%$search%'",
        "m.model LIKE '%$search%'",
        "m.description LIKE '%$search%'",
        "m.part_code LIKE '%$search%'"
    ];
    $where_conditions[] = "(" . implode(" OR ", $search_conditions) . ")";
}

// Price range filter
if (!empty($price_range)) {
    switch ($price_range) {
        case 'under_1l':
            $where_conditions[] = "COALESCE(pm.price, 0) < 100000";
            break;
        case '1l_5l':
            $where_conditions[] = "COALESCE(pm.price, 0) BETWEEN 100000 AND 500000";
            break;
        case '5l_10l':
            $where_conditions[] = "COALESCE(pm.price, 0) BETWEEN 500000 AND 1000000";
            break;
        case 'over_10l':
            $where_conditions[] = "COALESCE(pm.price, 0) > 1000000";
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
              FROM machines m 
              LEFT JOIN price_master pm ON m.id = pm.machine_id AND pm.is_active = 1 
              AND CURDATE() BETWEEN pm.valid_from AND pm.valid_to
              $where_clause";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get data
$sql = "SELECT m.*, 
               COALESCE(pm.price, 0) as current_price,
               pm.valid_from,
               pm.valid_to
        FROM machines m 
        LEFT JOIN price_master pm ON m.id = pm.machine_id AND pm.is_active = 1 
        AND CURDATE() BETWEEN pm.valid_from AND pm.valid_to
        $where_clause 
        ORDER BY m.created_at DESC 
        LIMIT $records_per_page OFFSET $offset";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Machine Report</title>
    
    <!-- DataTables Plugin -->
    <?php require_once '../common/datatables_plugin.php'; ?>
</head>
<body class="bg-light">

<div class="container-fluid mt-4">

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-gear-wide-connected"></i> Machine Report Results</h2>
            <p class="text-muted">Showing <?php echo $total_records; ?> records based on applied filters</p>
            <hr>
        </div>
    </div>

    <!-- Results -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="bi bi-table"></i> Report Results (<?php echo $total_records; ?> records)</h5>
            <div>
                <a href="machines_filter.php" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="bi bi-arrow-left"></i> Back to Filters
                </a>
                <?php if (hasPermission('reports_machines', 'export')): ?>
                <button class="btn btn-success btn-sm me-2" id="exportExcel">
                    <i class="bi bi-file-excel"></i> Export Excel
                </button>
                <button class="btn btn-danger btn-sm me-2" id="exportPDF">
                    <i class="bi bi-file-pdf"></i> Export PDF
                </button>
                <?php endif; ?>
                <?php if (hasPermission('reports_machines', 'print')): ?>
                <button class="btn btn-info btn-sm" id="printReport">
                    <i class="bi bi-printer"></i> Print
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="machinesTable">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Machine Name</th>
                            <th>Model</th>
                            <th>Category</th>
                            <th>Part Code</th>
                            <th>Current Price</th>
                            <th>Price Valid From</th>
                            <th>Price Valid To</th>
                            <th>Status</th>
                            <th>Created Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['name'] ?? ''); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['model'] ?? ''); ?></td>
                                <td>
                                    <span class="badge bg-primary">
                                        <?php echo htmlspecialchars($row['category'] ?? ''); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['part_code'] ?? ''); ?></td>
                                <td>
                                    <?php if ($row['current_price'] > 0): ?>
                                        <strong class="text-success">₹<?php echo number_format($row['current_price'], 2); ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">Not Set</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row['valid_from'] ? formatDate($row['valid_from']) : '-'; ?></td>
                                <td><?php echo $row['valid_to'] ? formatDate($row['valid_to']) : '-'; ?></td>
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
                                <td colspan="10" class="text-center py-4">
                                    <i class="bi bi-gear display-4 text-muted"></i>
                                    <p class="mt-3">No machines found with the current filters.</p>
                                    <a href="machines_filter.php" class="btn btn-primary">
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
    const table = initDataTable('#machinesTable', {
        paging: false, // We're handling pagination server-side
        searching: false, // We have custom search filters
        info: false, // We show custom info
        columnDefs: [
            { orderable: false, targets: [3, 8] } // Category and status columns
        ]
    });
    
    // Add export handlers using the utility function
    addExportHandlers(table);
});
</script>

</body>
</html>
