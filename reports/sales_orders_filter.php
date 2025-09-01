<?php
require_once '../common/conn.php';
require_once '../common/functions.php';

// Check if user is logged in and has permissions
checkLoginAndPermission('reports_sales_orders', 'view');

// Get customers for dropdown
$customers_sql = "SELECT id, company_name, contact_person FROM customers WHERE entity_type IN ('customer', 'both') ORDER BY company_name";
$customers = $conn->query($customers_sql);

// Get machines for dropdown
$machines_sql = "SELECT id, name, model, category FROM machines WHERE is_active = 1 ORDER BY name";
$machines = $conn->query($machines_sql);

// Get quotations for dropdown
$quotations_sql = "SELECT id, quotation_number, customer_id FROM quotations ORDER BY quotation_number DESC LIMIT 100";
$quotations = $conn->query($quotations_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Orders Report Filter</title>
    
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
                    <h2><i class="bi bi-cart-check-fill text-success"></i> Sales Orders Report</h2>
                    <p class="text-muted">Filter and analyze sales order data with comprehensive reporting options</p>
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
            <form method="GET" action="sales_orders_data.php" id="filterForm">
                <div class="row g-3">
                    <!-- Customer Filter -->
                    <div class="col-md-4">
                        <label for="customer_id" class="form-label">
                            <i class="bi bi-person-fill"></i> Customer
                        </label>
                        <select class="form-select" id="customer_id" name="customer_id">
                            <option value="">All Customers</option>
                            <?php if ($customers && $customers->num_rows > 0): ?>
                                <?php while ($customer = $customers->fetch_assoc()): ?>
                                    <option value="<?php echo $customer['id']; ?>">
                                        <?php echo htmlspecialchars($customer['company_name']); ?>
                                        <?php if (!empty($customer['contact_person'])): ?>
                                            (<?php echo htmlspecialchars($customer['contact_person']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Machine Filter -->
                    <div class="col-md-4">
                        <label for="machine_id" class="form-label">
                            <i class="bi bi-gear-wide-connected"></i> Machine
                        </label>
                        <select class="form-select" id="machine_id" name="machine_id">
                            <option value="">All Machines</option>
                            <?php if ($machines && $machines->num_rows > 0): ?>
                                <?php while ($machine = $machines->fetch_assoc()): ?>
                                    <option value="<?php echo $machine['id']; ?>">
                                        <?php echo htmlspecialchars($machine['name']); ?>
                                        <?php if (!empty($machine['model'])): ?>
                                            - <?php echo htmlspecialchars($machine['model']); ?>
                                        <?php endif; ?>
                                        <?php if (!empty($machine['category'])): ?>
                                            (<?php echo htmlspecialchars($machine['category']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="col-md-4">
                        <label for="status" class="form-label">
                            <i class="bi bi-flag"></i> Status
                        </label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                        </select>
                    </div>

                    <!-- Amount Range Filter -->
                    <div class="col-md-4">
                        <label for="amount_range" class="form-label">
                            <i class="bi bi-currency-rupee"></i> Amount Range
                        </label>
                        <select class="form-select" id="amount_range" name="amount_range">
                            <option value="">All Amounts</option>
                            <option value="under_1l">Under ₹1 Lakh</option>
                            <option value="1l_5l">₹1L - ₹5L</option>
                            <option value="5l_10l">₹5L - ₹10L</option>
                            <option value="10l_25l">₹10L - ₹25L</option>
                            <option value="25l_50l">₹25L - ₹50L</option>
                            <option value="over_50l">Over ₹50 Lakh</option>
                        </select>
                    </div>

                    <!-- Quotation Filter -->
                    <div class="col-md-4">
                        <label for="quotation_id" class="form-label">
                            <i class="bi bi-file-text"></i> Based on Quotation
                        </label>
                        <select class="form-select" id="quotation_id" name="quotation_id">
                            <option value="">All (with/without quotation)</option>
                            <option value="0">No Quotation Reference</option>
                            <?php if ($quotations && $quotations->num_rows > 0): ?>
                                <?php while ($quotation = $quotations->fetch_assoc()): ?>
                                    <option value="<?php echo $quotation['id']; ?>">
                                        <?php echo htmlspecialchars($quotation['quotation_number']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Delivery Status Filter -->
                    <div class="col-md-4">
                        <label for="delivery_status" class="form-label">
                            <i class="bi bi-truck"></i> Delivery Status
                        </label>
                        <select class="form-select" id="delivery_status" name="delivery_status">
                            <option value="">All Deliveries</option>
                            <option value="pending">Pending</option>
                            <option value="overdue">Overdue</option>
                            <option value="delivered_ontime">Delivered On Time</option>
                            <option value="delivered_late">Delivered Late</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mt-3">
                    <!-- SO Date From -->
                    <div class="col-md-3">
                        <label for="date_from" class="form-label">
                            <i class="bi bi-calendar"></i> SO Date From
                        </label>
                        <input type="date" class="form-control" id="date_from" name="date_from">
                    </div>

                    <!-- SO Date To -->
                    <div class="col-md-3">
                        <label for="date_to" class="form-label">
                            <i class="bi bi-calendar-check"></i> SO Date To
                        </label>
                        <input type="date" class="form-control" id="date_to" name="date_to">
                    </div>

                    <!-- Delivery Date From -->
                    <div class="col-md-3">
                        <label for="delivery_date_from" class="form-label">
                            <i class="bi bi-truck"></i> Delivery Date From
                        </label>
                        <input type="date" class="form-control" id="delivery_date_from" name="delivery_date_from">
                    </div>

                    <!-- Delivery Date To -->
                    <div class="col-md-3">
                        <label for="delivery_date_to" class="form-label">
                            <i class="bi bi-truck"></i> Delivery Date To
                        </label>
                        <input type="date" class="form-control" id="delivery_date_to" name="delivery_date_to">
                    </div>
                </div>

                <!-- Advanced Options -->
                <div class="row g-3 mt-3">
                    <div class="col-12">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="bi bi-gear"></i> Advanced Options
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <!-- Order Value Analysis -->
                                    <div class="col-md-4">
                                        <label for="value_analysis" class="form-label">Value Analysis</label>
                                        <select class="form-select" id="value_analysis" name="value_analysis">
                                            <option value="">No specific analysis</option>
                                            <option value="high_value">High Value Orders (Top 20%)</option>
                                            <option value="medium_value">Medium Value Orders</option>
                                            <option value="low_value">Low Value Orders</option>
                                        </select>
                                    </div>

                                    <!-- Time Period Analysis -->
                                    <div class="col-md-4">
                                        <label for="time_period" class="form-label">Time Period</label>
                                        <select class="form-select" id="time_period" name="time_period">
                                            <option value="">Custom Date Range</option>
                                            <option value="today">Today</option>
                                            <option value="yesterday">Yesterday</option>
                                            <option value="this_week">This Week</option>
                                            <option value="last_week">Last Week</option>
                                            <option value="this_month">This Month</option>
                                            <option value="last_month">Last Month</option>
                                            <option value="this_quarter">This Quarter</option>
                                            <option value="last_quarter">Last Quarter</option>
                                            <option value="this_year">This Year</option>
                                            <option value="last_year">Last Year</option>
                                        </select>
                                    </div>

                                    <!-- Include Cancelled -->
                                    <div class="col-md-4">
                                        <label for="include_cancelled" class="form-label">Include Cancelled</label>
                                        <select class="form-select" id="include_cancelled" name="include_cancelled">
                                            <option value="no">No</option>
                                            <option value="yes">Yes</option>
                                            <option value="only">Only Cancelled</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                            <ul class="mb-0">
                                <li><strong>Customer:</strong> Filter by specific customer company</li>
                                <li><strong>Machine:</strong> Filter orders containing specific machines</li>
                                <li><strong>Status:</strong> Filter by order processing status</li>
                                <li><strong>Amount Range:</strong> Filter by total order value</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="mb-0">
                                <li><strong>Quotation:</strong> Filter by referenced quotation</li>
                                <li><strong>Delivery Status:</strong> Filter by delivery timeline status</li>
                                <li><strong>Date Ranges:</strong> Filter by SO date or delivery date</li>
                                <li><strong>Advanced Options:</strong> Additional analysis parameters</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Clear filters functionality
    $('#clearFilters').click(function() {
        $('#filterForm')[0].reset();
    });

    // Time period quick selection
    $('#time_period').change(function() {
        const period = $(this).val();
        const today = new Date();
        let fromDate = '';
        let toDate = '';

        switch(period) {
            case 'today':
                fromDate = toDate = today.toISOString().split('T')[0];
                break;
            case 'yesterday':
                const yesterday = new Date(today);
                yesterday.setDate(today.getDate() - 1);
                fromDate = toDate = yesterday.toISOString().split('T')[0];
                break;
            case 'this_week':
                const startOfWeek = new Date(today);
                startOfWeek.setDate(today.getDate() - today.getDay());
                fromDate = startOfWeek.toISOString().split('T')[0];
                toDate = today.toISOString().split('T')[0];
                break;
            case 'last_week':
                const lastWeekEnd = new Date(today);
                lastWeekEnd.setDate(today.getDate() - today.getDay() - 1);
                const lastWeekStart = new Date(lastWeekEnd);
                lastWeekStart.setDate(lastWeekEnd.getDate() - 6);
                fromDate = lastWeekStart.toISOString().split('T')[0];
                toDate = lastWeekEnd.toISOString().split('T')[0];
                break;
            case 'this_month':
                fromDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                toDate = today.toISOString().split('T')[0];
                break;
            case 'last_month':
                const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
                fromDate = lastMonth.toISOString().split('T')[0];
                toDate = lastMonthEnd.toISOString().split('T')[0];
                break;
            case 'this_quarter':
                const quarterStart = new Date(today.getFullYear(), Math.floor(today.getMonth() / 3) * 3, 1);
                fromDate = quarterStart.toISOString().split('T')[0];
                toDate = today.toISOString().split('T')[0];
                break;
            case 'this_year':
                fromDate = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
                toDate = today.toISOString().split('T')[0];
                break;
        }

        if (fromDate && toDate) {
            $('#date_from').val(fromDate);
            $('#date_to').val(toDate);
        }
    });

    // Form validation
    $('#filterForm').submit(function(e) {
        const dateFrom = $('#date_from').val();
        const dateTo = $('#date_to').val();
        
        if (dateFrom && dateTo && dateFrom > dateTo) {
            e.preventDefault();
            alert('SO Date From cannot be later than SO Date To');
            return false;
        }

        const deliveryDateFrom = $('#delivery_date_from').val();
        const deliveryDateTo = $('#delivery_date_to').val();
        
        if (deliveryDateFrom && deliveryDateTo && deliveryDateFrom > deliveryDateTo) {
            e.preventDefault();
            alert('Delivery Date From cannot be later than Delivery Date To');
            return false;
        }
    });
});
</script>

<?php include '../footer.php'; ?>
</body>
</html>
