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
if (!hasPermission('reports_spares', 'view')) {
    header('Location: ../auth/access_denied.php');
    exit();
}

// Get filter parameters
$machine_id = isset($_GET['machine_id']) ? intval($_GET['machine_id']) : 0;
$price_range = isset($_GET['price_range']) ? sanitizeInput($_GET['price_range']) : '';
$is_active = isset($_GET['is_active']) ? sanitizeInput($_GET['is_active']) : '';
$created_from = isset($_GET['created_from']) ? sanitizeInput($_GET['created_from']) : '';
$created_to = isset($_GET['created_to']) ? sanitizeInput($_GET['created_to']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build WHERE clause based on spares table structure
$where_conditions = [];
if ($machine_id > 0) {
    $where_conditions[] = "s.machine_id = $machine_id";
}
if ($is_active !== '') {
    $where_conditions[] = "s.is_active = " . intval($is_active);
}
if (!empty($created_from)) {
    $where_conditions[] = "DATE(s.created_at) >= '$created_from'";
}
if (!empty($created_to)) {
    $where_conditions[] = "DATE(s.created_at) <= '$created_to'";
}
if (!empty($search)) {
    $search_conditions = [
        "s.part_name LIKE '%$search%'",
        "s.part_code LIKE '%$search%'",
        "s.description LIKE '%$search%'"
    ];
    $where_conditions[] = "(" . implode(" OR ", $search_conditions) . ")";
}

// Price range filter
if (!empty($price_range)) {
    switch ($price_range) {
        case 'under_1k':
            $where_conditions[] = "s.price < 1000";
            break;
        case '1k_5k':
            $where_conditions[] = "s.price BETWEEN 1000 AND 5000";
            break;
        case '5k_10k':
            $where_conditions[] = "s.price BETWEEN 5000 AND 10000";
            break;
        case 'over_10k':
            $where_conditions[] = "s.price > 10000";
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
              FROM spares s 
              LEFT JOIN machines m ON s.machine_id = m.id
              $where_clause";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get data from spares table with machine info
$sql = "SELECT s.id, s.part_name, s.part_code, s.description, s.price, 
               s.is_active, s.created_at,
               m.name as machine_name
        FROM spares s 
        LEFT JOIN machines m ON s.machine_id = m.id
        $where_clause 
        ORDER BY s.created_at DESC 
        LIMIT $records_per_page OFFSET $offset";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spare Parts Report</title>
    
    <!-- DataTables Plugin -->
    <?php require_once '../common/datatables_plugin.php'; ?>
</head>
<body class="bg-light">

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-tools"></i> Spare Parts Report Results</h2>
            <p class="text-muted">Showing <?php echo $total_records; ?> records based on applied filters</p>
            <hr>
        </div>
    </div>

    <!-- Results -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="bi bi-table"></i> Report Results (<?php echo $total_records; ?> records)</h5>
            <div>
                <a href="spares_filter.php" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="bi bi-arrow-left"></i> Back to Filters
                </a>
                <?php if (hasPermission('reports_spares', 'export')): ?>
                <button class="btn btn-success btn-sm me-2" id="exportExcel">
                    <i class="bi bi-file-excel"></i> Export Excel
                </button>
                <button class="btn btn-danger btn-sm me-2" id="exportPDF">
                    <i class="bi bi-file-pdf"></i> Export PDF
                </button>
                <?php endif; ?>
                <?php if (hasPermission('reports_spares', 'print')): ?>
                <button class="btn btn-info btn-sm" id="printReport">
                    <i class="bi bi-printer"></i> Print
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="sparesTable">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Part Name</th>
                            <th>Part Code</th>
                            <th>Machine</th>
                            <th>Price</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Created Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['part_name'] ?? ''); ?></strong></td>
                                <td>
                                    <?php if (!empty($row['part_code'])): ?>
                                        <code><?php echo htmlspecialchars($row['part_code']); ?></code>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['machine_name'])): ?>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($row['machine_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['price'] > 0): ?>
                                        <strong class="text-success">₹<?php echo number_format($row['price'], 2); ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">Not Set</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $desc = htmlspecialchars($row['description'] ?? '');
                                    echo !empty($desc) ? $desc : '<span class="text-muted">-</span>';
                                    ?>
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
                                <td colspan="8" class="text-center py-4">
                                    <i class="bi bi-tools display-4 text-muted"></i>
                                    <p class="mt-3">No spare parts found with the current filters.</p>
                                    <a href="spares_filter.php" class="btn btn-primary">
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
    const table = initDataTable('#sparesTable', {
        paging: false, // We're handling pagination server-side
        searching: false, // We have custom search filters
        info: false, // We show custom info
        columnDefs: [
            { orderable: false, targets: [3, 6] } // Machine and status columns
        ]
    });
    
    // Add export handlers using the utility function
    addExportHandlers(table);
});
</script>

</body>
</html>
