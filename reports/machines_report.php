<?php
include '../header.php';
checkLoginAndPermission('reports_machines', 'view');
include '../menu.php';

// Get filter parameters
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$manufacturer = isset($_GET['manufacturer']) ? sanitizeInput($_GET['manufacturer']) : '';
$price_range = isset($_GET['price_range']) ? sanitizeInput($_GET['price_range']) : '';
$is_active = isset($_GET['is_active']) ? sanitizeInput($_GET['is_active']) : '';
$created_from = isset($_GET['created_from']) ? sanitizeInput($_GET['created_from']) : '';
$created_to = isset($_GET['created_to']) ? sanitizeInput($_GET['created_to']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build WHERE clause
$where_conditions = [];
if (!empty($category)) {
    $where_conditions[] = "m.category LIKE '%$category%'";
}
if (!empty($manufacturer)) {
    $where_conditions[] = "m.manufacturer LIKE '%$manufacturer%'";
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
        "m.description LIKE '%$search%'"
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

// Get unique categories and manufacturers for filter dropdowns
$categories_result = $conn->query("SELECT DISTINCT category FROM machines WHERE category IS NOT NULL AND category != '' ORDER BY category");
$manufacturers_result = $conn->query("SELECT DISTINCT manufacturer FROM machines WHERE manufacturer IS NOT NULL AND manufacturer != '' ORDER BY manufacturer");
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-gear-wide-connected"></i> Machine Reports</h2>
            <p class="text-muted">Filter and generate detailed machine inventory and specifications reports</p>
            <hr>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="bi bi-funnel"></i> Report Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All Categories</option>
                        <?php while ($cat_row = $categories_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($cat_row['category']); ?>" 
                                    <?php echo $category == $cat_row['category'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat_row['category']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="manufacturer" class="form-label">Manufacturer</label>
                    <select class="form-select" id="manufacturer" name="manufacturer">
                        <option value="">All Manufacturers</option>
                        <?php while ($mfg_row = $manufacturers_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($mfg_row['manufacturer']); ?>" 
                                    <?php echo $manufacturer == $mfg_row['manufacturer'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($mfg_row['manufacturer']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="price_range" class="form-label">Price Range</label>
                    <select class="form-select" id="price_range" name="price_range">
                        <option value="">All Prices</option>
                        <option value="under_1l" <?php echo $price_range == 'under_1l' ? 'selected' : ''; ?>>Under ₹1 Lakh</option>
                        <option value="1l_5l" <?php echo $price_range == '1l_5l' ? 'selected' : ''; ?>>₹1L - ₹5L</option>
                        <option value="5l_10l" <?php echo $price_range == '5l_10l' ? 'selected' : ''; ?>>₹5L - ₹10L</option>
                        <option value="over_10l" <?php echo $price_range == 'over_10l' ? 'selected' : ''; ?>>Over ₹10 Lakh</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="is_active" class="form-label">Status</label>
                    <select class="form-select" id="is_active" name="is_active">
                        <option value="">All Status</option>
                        <option value="1" <?php echo $is_active === '1' ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo $is_active === '0' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="created_from" class="form-label">Created From</label>
                    <input type="date" class="form-control" id="created_from" name="created_from" value="<?php echo $created_from; ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="created_to" class="form-label">Created To</label>
                    <input type="date" class="form-control" id="created_to" name="created_to" value="<?php echo $created_to; ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Machine name, model, description..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="machines_report.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="bi bi-table"></i> Machine Report Results (<?php echo $total_records; ?> records)</h5>
            <div>
                <?php if (hasPermission('reports_machines', 'export')): ?>
                <button class="btn btn-success btn-sm me-2" onclick="exportToExcel()">
                    <i class="bi bi-file-excel"></i> Export Excel
                </button>
                <button class="btn btn-danger btn-sm me-2" onclick="exportToPDF()">
                    <i class="bi bi-file-pdf"></i> Export PDF
                </button>
                <?php endif; ?>
                <?php if (hasPermission('reports_machines', 'print')): ?>
                <button class="btn btn-info btn-sm" onclick="printReport()">
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
                            <th>Manufacturer</th>
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
                                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['model']); ?></td>
                                <td>
                                    <span class="badge bg-primary">
                                        <?php echo htmlspecialchars($row['category']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['manufacturer']); ?></td>
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
                                <a class="page-link" href="<?php echo $base_url; ?>&page=<?php echo ($page - 1); ?>">
                                    <i class="bi bi-chevron-left"></i> Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo $base_url; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $base_url; ?>&page=<?php echo ($page + 1); ?>">
                                    Next <i class="bi bi-chevron-right"></i>
                                </a>
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

<!-- DataTables CSS and JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    $('#machinesTable').DataTable({
        paging: false,
        searching: false,
        info: false,
        ordering: true,
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="bi bi-file-excel"></i> Excel',
                className: 'btn btn-success btn-sm',
                title: 'Machine_Report'
            },
            {
                extend: 'pdf',
                text: '<i class="bi bi-file-pdf"></i> PDF',
                className: 'btn btn-danger btn-sm',
                title: 'Machine_Report',
                orientation: 'landscape',
                pageSize: 'A4'
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer"></i> Print',
                className: 'btn btn-info btn-sm',
                title: 'Machine Report'
            }
        ],
        columnDefs: [
            { orderable: false, targets: [3, 8] }
        ]
    });
});

function exportToExcel() {
    $('#machinesTable').DataTable().button('.buttons-excel').trigger();
}

function exportToPDF() {
    $('#machinesTable').DataTable().button('.buttons-pdf').trigger();
}

function printReport() {
    $('#machinesTable').DataTable().button('.buttons-print').trigger();
}

$('select').on('change', function() {
    $(this).closest('form').submit();
});
</script>

<style>
.table th {
    white-space: nowrap;
}
.btn-group-sm > .btn, .btn-sm {
    margin-right: 5px;
}
.card-header .btn {
    margin-left: 5px;
}
@media print {
    .card-header .btn,
    .pagination,
    .filter-form {
        display: none !important;
    }
}
</style>

<?php include '../footer.php'; ?>
