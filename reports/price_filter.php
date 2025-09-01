<?php
include '../header.php';
checkLoginAndPermission('reports_price', 'view');
include '../menu.php';

// Get unique machine names for filter dropdown
$machines_result = $conn->query("SELECT DISTINCT m.name, m.id FROM machines m ORDER BY m.name");
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-currency-rupee"></i> Price Master Reports</h2>
            <p class="text-muted">Filter and generate detailed pricing structure reports</p>
            <hr>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="bi bi-funnel"></i> Report Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="price_data.php" class="row g-3">
                <div class="col-md-3">
                    <label for="machine_id" class="form-label">Machine</label>
                    <select class="form-select" id="machine_id" name="machine_id">
                        <option value="">All Machines</option>
                        <?php while ($machine_row = $machines_result->fetch_assoc()): ?>
                            <option value="<?php echo $machine_row['id']; ?>">
                                <?php echo htmlspecialchars($machine_row['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="price_range" class="form-label">Price Range</label>
                    <select class="form-select" id="price_range" name="price_range">
                        <option value="">All Price Ranges</option>
                        <option value="under_1l">Under ₹1 Lakh</option>
                        <option value="1l_5l">₹1 Lakh - ₹5 Lakh</option>
                        <option value="5l_10l">₹5 Lakh - ₹10 Lakh</option>
                        <option value="over_10l">Over ₹10 Lakh</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="validity_status" class="form-label">Validity Status</label>
                    <select class="form-select" id="validity_status" name="validity_status">
                        <option value="">All Validity Status</option>
                        <option value="current">Currently Valid</option>
                        <option value="expired">Expired</option>
                        <option value="future">Future Valid</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="is_active" class="form-label">Status</label>
                    <select class="form-select" id="is_active" name="is_active">
                        <option value="">All Status</option>
                        <option value="1">Active Only</option>
                        <option value="0">Inactive Only</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="valid_from" class="form-label">Valid From</label>
                    <input type="date" class="form-control" id="valid_from" name="valid_from">
                </div>
                
                <div class="col-md-4">
                    <label for="valid_to" class="form-label">Valid To</label>
                    <input type="date" class="form-control" id="valid_to" name="valid_to">
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
    $('#valid_from').on('change', function() {
        const fromDate = $(this).val();
        const $toDate = $('#valid_to');
        if (fromDate && !$toDate.val()) {
            const today = new Date().toISOString().split('T')[0];
            $toDate.val(today);
        }
    });
    
    // Validate date range on form submission
    $('form').on('submit', function(e) {
        const fromDate = $('#valid_from').val();
        const toDate = $('#valid_to').val();
        
        if (fromDate && toDate && fromDate > toDate) {
            e.preventDefault();
            alert('Valid From date cannot be later than Valid To date.');
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
