<?php
include '../header.php';
checkLoginAndPermission('reports_customers', 'view');
include '../menu.php';

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
            <form method="GET" action="customers_data.php" class="row g-3">
                <div class="col-md-3">
                    <label for="entity_type" class="form-label">Entity Type</label>
                    <select class="form-select" id="entity_type" name="entity_type">
                        <option value="">All Types</option>
                        <option value="customer">Customer Only</option>
                        <option value="vendor">Vendor Only</option>
                        <option value="both">Both</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="state" class="form-label">State</label>
                    <select class="form-select" id="state" name="state">
                        <option value="">All States</option>
                        <?php while ($state_row = $states_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($state_row['state']); ?>">
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
                            <option value="<?php echo htmlspecialchars($city_row['city']); ?>">
                                <?php echo htmlspecialchars($city_row['city']); ?>
                            </option>
                        <?php endwhile; ?>
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
                           placeholder="Company name, contact person, email, phone...">
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

<?php include '../footer.php'; ?>
