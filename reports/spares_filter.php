<?php
include '../header.php';
checkLoginAndPermission('reports_spares', 'view');
include '../menu.php';

// Get unique machine names for filter dropdown
$machines_result = $conn->query("SELECT DISTINCT m.name, m.id FROM machines m ORDER BY m.name");
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-tools"></i> Spare Parts Reports</h2>
            <p class="text-muted">Filter and generate detailed spare parts inventory reports</p>
            <hr>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="bi bi-funnel"></i> Report Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="spares_data.php" class="row g-3">
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
                        <option value="under_1k">Under ₹1,000</option>
                        <option value="1k_5k">₹1,000 - ₹5,000</option>
                        <option value="5k_10k">₹5,000 - ₹10,000</option>
                        <option value="over_10k">Over ₹10,000</option>
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
                    <label for="created_from" class="form-label">Created From</label>
                    <input type="date" class="form-control" id="created_from" name="created_from">
                </div>
                
                <div class="col-md-3">
                    <label for="created_to" class="form-label">Created To</label>
                    <input type="date" class="form-control" id="created_to" name="created_to">
                </div>
                
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Part name, part code, description...">
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
    $('#created_from').on('change', function() {
        const fromDate = $(this).val();
        const $toDate = $('#created_to');
        if (fromDate && !$toDate.val()) {
            const today = new Date().toISOString().split('T')[0];
            $toDate.val(today);
        }
    });
    
    // Validate date range on form submission
    $('form').on('submit', function(e) {
        const fromDate = $('#created_from').val();
        const toDate = $('#created_to').val();
        
        if (fromDate && toDate && fromDate > toDate) {
            e.preventDefault();
            alert('Created From date cannot be later than Created To date.');
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
