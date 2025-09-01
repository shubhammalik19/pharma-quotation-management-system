<?php
include '../header.php';
checkLoginAndPermission('reports_machines', 'view');
include '../menu.php';

// Get unique categories for filter dropdowns
$categories_result = $conn->query("SELECT DISTINCT category FROM machines WHERE category IS NOT NULL AND category != '' ORDER BY category");
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-gear-wide-connected"></i> Machine Reports</h2>
            <p class="text-muted">Filter and generate detailed machine inventory reports</p>
            <hr>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="bi bi-funnel"></i> Report Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="machines_data.php" class="row g-3">
                <div class="col-md-3">
                    <label for="category" class="form-label">Machine Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All Categories</option>
                        <?php while ($cat_row = $categories_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($cat_row['category'] ?? ''); ?>">
                                <?php echo htmlspecialchars($cat_row['category'] ?? ''); ?>
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
                           placeholder="Machine name, model, description, part code...">
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
