<?php
include '../header.php';
checkLoginAndPermission('reports_quotations', 'view');
include '../menu.php';

// Get unique customers for filter dropdown (using correct column name)
$customers_result = $conn->query("SELECT DISTINCT id, company_name FROM customers WHERE entity_type IN ('customer', 'both') ORDER BY company_name");

// Get unique machines for filter dropdown (using correct column name)
$machines_result = $conn->query("SELECT DISTINCT id, name FROM machines ORDER BY name");
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-file-text"></i> Quotation Reports</h2>
            <p class="text-muted">Filter and generate detailed quotation analysis reports</p>
            <hr>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="bi bi-funnel"></i> Report Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="quotations_data.php" class="row g-3">
                <div class="col-md-3">
                    <label for="customer_id" class="form-label">Customer</label>
                    <select class="form-select" id="customer_id" name="customer_id">
                        <option value="">All Customers</option>
                        <?php while ($customer = $customers_result->fetch_assoc()): ?>
                            <option value="<?php echo $customer['id']; ?>">
                                <?php echo htmlspecialchars($customer['company_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="machine_id" class="form-label">Machine</label>
                    <select class="form-select" id="machine_id" name="machine_id">
                        <option value="">All Machines</option>
                        <?php while ($machine = $machines_result->fetch_assoc()): ?>
                            <option value="<?php echo $machine['id']; ?>">
                                <?php echo htmlspecialchars($machine['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="sent">Sent</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="amount_range" class="form-label">Amount Range</label>
                    <select class="form-select" id="amount_range" name="amount_range">
                        <option value="">All Amount Ranges</option>
                        <option value="under_1l">Under ₹1 Lakh</option>
                        <option value="1l_5l">₹1 Lakh - ₹5 Lakh</option>
                        <option value="5l_10l">₹5 Lakh - ₹10 Lakh</option>
                        <option value="10l_25l">₹10 Lakh - ₹25 Lakh</option>
                        <option value="over_25l">Over ₹25 Lakh</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from">
                </div>
                
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to">
                </div>
                
                <div class="col-md-4">
                    <label for="validity_status" class="form-label">Validity Status</label>
                    <select class="form-select" id="validity_status" name="validity_status">
                        <option value="">All Validity Status</option>
                        <option value="valid">Currently Valid</option>
                        <option value="expired">Expired</option>
                        <option value="expiring_soon">Expiring Soon (30 days)</option>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Clear
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Auto-set today's date as default 'to' date when 'from' date is selected
    $('#date_from').on('change', function() {
        const fromDate = $(this).val();
        const $toDate = $('#date_to');
        if (fromDate && !$toDate.val()) {
            const today = new Date().toISOString().split('T')[0];
            $toDate.val(today);
        }
    });
    
    // Validate date range on form submission
    $('form').on('submit', function(e) {
        const fromDate = $('#date_from').val();
        const toDate = $('#date_to').val();
        
        if (fromDate && toDate && fromDate > toDate) {
            e.preventDefault();
            alert('From date cannot be later than To date.');
            return false;
        }
    });
    
    // Clear form functionality
    $('button[type="reset"]').on('click', function() {
        // Reset all form fields
        $(this).closest('form')[0].reset();
    });
});
</script>

<?php include '../footer.php'; ?>
