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
if (!hasPermission('reports_customers', 'view')) {
    header('Location: ../auth/access_denied.php');
    exit();
}

// Get filter parameters
$entity_type = isset($_GET['entity_type']) ? sanitizeInput($_GET['entity_type']) : '';
$state = isset($_GET['state']) ? sanitizeInput($_GET['state']) : '';
$city = isset($_GET['city']) ? sanitizeInput($_GET['city']) : '';
$created_from = isset($_GET['created_from']) ? sanitizeInput($_GET['created_from']) : '';
$created_to = isset($_GET['created_to']) ? sanitizeInput($_GET['created_to']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build WHERE clause based on customers table structure
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

// Get data from customers table
$sql = "SELECT id, entity_type, company_name, contact_person, phone, email, gst_no, 
               city, state, pincode, created_at 
        FROM customers $where_clause 
        ORDER BY created_at DESC 
        LIMIT $records_per_page OFFSET $offset";
$result = $conn->query($sql);

// Get unique states and cities for filter dropdowns
$states_result = $conn->query("SELECT DISTINCT state FROM customers WHERE state IS NOT NULL AND state != '' ORDER BY state");
$cities_result = $conn->query("SELECT DISTINCT city FROM customers WHERE city IS NOT NULL AND city != '' ORDER BY city");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer/Vendor Report</title>
    
    <!-- DataTables Plugin -->
    <?php require_once '../common/datatables_plugin.php'; ?>
</head>
<body class="bg-light">

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-people-fill"></i> Customer/Vendor Report Results</h2>
            <p class="text-muted">Showing <?php echo $total_records; ?> records based on applied filters</p>
            <hr>
        </div>
    </div>

    <!-- Results -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="bi bi-table"></i> Report Results (<?php echo $total_records; ?> records)</h5>
            <div>
                <a href="customers_filter.php" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="bi bi-arrow-left"></i> Back to Filters
                </a>
                <?php if (hasPermission('reports_customers', 'export')): ?>
                <button class="btn btn-success btn-sm me-2" id="exportExcel">
                    <i class="bi bi-file-excel"></i> Export Excel
                </button>
                <button class="btn btn-danger btn-sm me-2" id="exportPDF">
                    <i class="bi bi-file-pdf"></i> Export PDF
                </button>
                <?php endif; ?>
                <?php if (hasPermission('reports_customers', 'print')): ?>
                <button class="btn btn-info btn-sm" id="printReport">
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
                                    <a href="customers_filter.php" class="btn btn-primary">
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

<!-- DataTables CSS and JS -->
<?php require_once '../common/datatables_plugin.php'; ?>
<script>
$(document).ready(function() {
    // Initialize DataTable using the utility function from datatables_plugin.php
    const table = initDataTable('#customersTable', {
        paging: false, // We're handling pagination server-side
        searching: false, // We have custom search filters
        info: false, // We show custom info
        columnDefs: [
            { orderable: false, targets: [3] } // Disable sorting for badge columns
        ]
    });
    
    // Add export handlers using the utility function
    addExportHandlers(table);
});
</script>

</body>
</html>