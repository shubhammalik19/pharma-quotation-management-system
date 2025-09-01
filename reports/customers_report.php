<?php
include '../header.php';
checkLoginAndPermission('reports_customers', 'view');
include '../menu.php';

// Get filter parameters
$entity_type = isset($_GET['entity_type']) ? sanitizeInput($_GET['entity_type']) : '';
$state = isset($_GET['state']) ? sanitizeInput($_GET['state']) : '';
$city = isset($_GET['city']) ? sanitizeInput($_GET['city']) : '';
$created_from = isset($_GET['created_from']) ? sanitizeInput($_GET['created_from']) : '';
$created_to = isset($_GET['created_to']) ? sanitizeInput($_GET['created_to']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build WHERE clause
$where_conditions = [];
if (!empty($entity_type)) {
    if ($entity_type == 'customer') {
        $where_conditions[] = "(entity_type = 'customer' OR entity_type = 'both')";
    } elseif ($entity_type == 'vendor') {
        $where_conditions[] = "(entity_type = 'vendor' OR entity_type = 'both')";
    } else {
        $where_conditions[] = "entity_type = '$entity_type'";
    }
}
if (!empty($state)) {
    $where_conditions[] = "state LIKE '%$state%'";
}
if (!empty($city)) {
    $where_conditions[] = "city LIKE '%$city%'";
}
if (!empty($created_from)) {
    $where_conditions[] = "DATE(created_at) >= '$created_from'";
}
if (!empty($created_to)) {
    $where_conditions[] = "DATE(created_at) <= '$created_to'";
}
if (!empty($search)) {
    $search_conditions = [
        "company_name LIKE '%$search%'",
        "contact_person LIKE '%$search%'",
        "email LIKE '%$search%'",
        "phone LIKE '%$search%'"
    ];
    $where_conditions[] = "(" . implode(" OR ", $search_conditions) . ")";
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
$count_sql = "SELECT COUNT(*) as total FROM customers $where_clause";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get data
$sql = "SELECT * FROM customers $where_clause ORDER BY created_at DESC LIMIT $records_per_page OFFSET $offset";
$result = $conn->query($sql);

// Get unique states and cities for filter dropdowns
$states_result = $conn->query("SELECT DISTINCT state FROM customers WHERE state IS NOT NULL AND state != '' ORDER BY state");
$cities_result = $conn->query("SELECT DISTINCT city FROM customers WHERE city IS NOT NULL AND city != '' ORDER BY city");
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-people-fill"></i> Customer/Vendor Reports</h2>
            <p class="text-muted">Filter and generate detailed customer and vendor reports</p>
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
                    <label for="entity_type" class="form-label">Entity Type</label>
                    <select class="form-select" id="entity_type" name="entity_type">
                        <option value="">All Types</option>
                        <option value="customer" <?php echo $entity_type == 'customer' ? 'selected' : ''; ?>>Customer Only</option>
                        <option value="vendor" <?php echo $entity_type == 'vendor' ? 'selected' : ''; ?>>Vendor Only</option>
                        <option value="both" <?php echo $entity_type == 'both' ? 'selected' : ''; ?>>Both</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="state" class="form-label">State</label>
                    <select class="form-select" id="state" name="state">
                        <option value="">All States</option>
                        <?php while ($state_row = $states_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($state_row['state']); ?>" 
                                    <?php echo $state == $state_row['state'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($state_row['state']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="city" class="form-label">City</label>
                    <select class="form-select" id="city" name="city">
                        <option value="">All Cities</option>
                        <?php while ($city_row = $cities_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($city_row['city']); ?>" 
                                    <?php echo $city == $city_row['city'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($city_row['city']); ?>
                            </option>
                        <?php endwhile; ?>
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
                           placeholder="Company name, contact person, email, phone..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="customers_report.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="bi bi-table"></i> Customer/Vendor Report Results (<?php echo $total_records; ?> records)</h5>
            <div>
                <?php if (hasPermission('reports_customers', 'export')): ?>
                <button class="btn btn-success btn-sm me-2" onclick="exportToExcel()">
                    <i class="bi bi-file-excel"></i> Export Excel
                </button>
                <button class="btn btn-danger btn-sm me-2" onclick="exportToPDF()">
                    <i class="bi bi-file-pdf"></i> Export PDF
                </button>
                <?php endif; ?>
                <?php if (hasPermission('reports_customers', 'print')): ?>
                <button class="btn btn-info btn-sm" onclick="printReport()">
                    <i class="bi bi-printer"></i> Print
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="customersTable">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Company Name</th>
                            <th>Contact Person</th>
                            <th>Entity Type</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>City</th>
                            <th>State</th>
                            <th>GST Number</th>
                            <th>Created Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['company_name'] ?? ''); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['contact_person'] ?? ''); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $row['entity_type'] == 'customer' ? 'primary' : ($row['entity_type'] == 'vendor' ? 'success' : 'info'); ?>">
                                        <?php echo ucwords($row['entity_type'] ?? ''); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['phone'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['city'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['state'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['gst_no'] ?? ''); ?></td>
                                <td><?php echo formatDate($row['created_at']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <i class="bi bi-inbox display-4 text-muted"></i>
                                    <p class="mt-3">No customers/vendors found with the current filters.</p>
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
    $('#customersTable').DataTable({
        paging: false, // We're handling pagination server-side
        searching: false, // We have custom search filters
        info: false, // We show custom info
        ordering: true,
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="bi bi-file-excel"></i> Excel',
                className: 'btn btn-success btn-sm',
                title: 'Customer_Vendor_Report'
            },
            {
                extend: 'pdf',
                text: '<i class="bi bi-file-pdf"></i> PDF',
                className: 'btn btn-danger btn-sm',
                title: 'Customer_Vendor_Report',
                orientation: 'landscape',
                pageSize: 'A4'
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer"></i> Print',
                className: 'btn btn-info btn-sm',
                title: 'Customer/Vendor Report'
            }
        ],
        columnDefs: [
            { orderable: false, targets: [3, 9] } // Disable sorting for badge columns
        ]
    });
});

function exportToExcel() {
    $('#customersTable').DataTable().button('.buttons-excel').trigger();
}

function exportToPDF() {
    $('#customersTable').DataTable().button('.buttons-pdf').trigger();
}

function printReport() {
    $('#customersTable').DataTable().button('.buttons-print').trigger();
}

// Auto-submit form on dropdown change for better UX
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
