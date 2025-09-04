<?php
require_once '../common/conn.php';
require_once '../common/functions.php';

// Check if user is logged in and has permissions
checkLoginAndPermission('reports_purchase_invoices', 'view');

// Get vendors for dropdown
$vendors_sql = "SELECT id, company_name, contact_person FROM customers WHERE entity_type IN ('vendor', 'both') ORDER BY company_name";
$vendors = $conn->query($vendors_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Invoices Report Filter</title>
    
    <!-- DataTables Plugin -->
    <?php require_once '../common/datatables_plugin.php'; ?>
</head>
<body class="bg-light">

<?php include '../header.php'; ?>
<?php include '../menu.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="bi bi-receipt-cutoff text-dark"></i> Purchase Invoices Report</h2>
                    <p class="text-muted">Filter and analyze purchase invoice data with comprehensive reporting options</p>
                </div>
                <a href="../reports.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Reports
                </a>
            </div>
            <hr>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-funnel"></i> Report Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="purchase_invoices_data.php" id="filterForm">
                <div class="row g-3">
                    <!-- Date Range -->
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?php echo date('Y-m-01'); ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="<?php echo date('Y-m-t'); ?>">
                    </div>
                    
                    <!-- Vendor Filter -->
                    <div class="col-md-3">
                        <label for="vendor_id" class="form-label">Vendor</label>
                        <select class="form-select" id="vendor_id" name="vendor_id">
                            <option value="">All Vendors</option>
                            <?php while ($vendor = $vendors->fetch_assoc()): ?>
                                <option value="<?php echo $vendor['id']; ?>">
                                    <?php echo htmlspecialchars($vendor['company_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <!-- Status Filter -->
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="partially_paid">Partially Paid</option>
                            <option value="overdue">Overdue</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    
                    <!-- Amount Range -->
                    <div class="col-md-3">
                        <label for="min_amount" class="form-label">Min Amount (₹)</label>
                        <input type="number" class="form-control" id="min_amount" name="min_amount" 
                               step="0.01" placeholder="0.00">
                    </div>
                    
                    <div class="col-md-3">
                        <label for="max_amount" class="form-label">Max Amount (₹)</label>
                        <input type="number" class="form-control" id="max_amount" name="max_amount" 
                               step="0.01" placeholder="999999.99">
                    </div>
                    
                    <!-- PI Number Search -->
                    <div class="col-md-3">
                        <label for="pi_number" class="form-label">PI Number</label>
                        <input type="text" class="form-control" id="pi_number" name="pi_number" 
                               placeholder="PI-2025-00001">
                    </div>
                    
                    <!-- Purchase Order Filter -->
                    <div class="col-md-3">
                        <label for="po_number" class="form-label">PO Number</label>
                        <input type="text" class="form-control" id="po_number" name="po_number" 
                               placeholder="PO-2025-00001">
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="d-flex gap-3 justify-content-center">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-bar-chart"></i> Generate Report
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-lg" id="clearFilters">
                                <i class="bi bi-x-circle"></i> Clear Filters
                            </button>
                            <a href="../reports.php" class="btn btn-outline-primary btn-lg">
                                <i class="bi bi-arrow-left"></i> Back to Reports
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Help Information -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6><i class="bi bi-info-circle"></i> Filter Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled mb-0">
                                <li><i class="bi bi-check"></i> Use date ranges to filter by invoice creation date</li>
                                <li><i class="bi bi-check"></i> Filter by vendor to see specific vendor invoices</li>
                                <li><i class="bi bi-check"></i> Use status filter to track payment progress</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled mb-0">
                                <li><i class="bi bi-check"></i> Amount filters help identify high-value transactions</li>
                                <li><i class="bi bi-check"></i> PI/PO number search for specific invoice lookup</li>
                                <li><i class="bi bi-check"></i> Export options available in report view</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Clear filters function
document.getElementById('clearFilters').addEventListener('click', function() {
    document.getElementById('filterForm').reset();
});

// Form validation
document.getElementById('filterForm').addEventListener('submit', function(e) {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    
    if (startDate && endDate && startDate > endDate) {
        e.preventDefault();
        alert('Start Date cannot be later than End Date');
        return false;
    }
    
    const minAmount = document.getElementById('min_amount').value;
    const maxAmount = document.getElementById('max_amount').value;
    
    if (minAmount && maxAmount && parseFloat(minAmount) > parseFloat(maxAmount)) {
        e.preventDefault();
        alert('Min Amount cannot be greater than Max Amount');
        return false;
    }
});
</script>

<?php include '../footer.php'; ?>
</body>
</html>
