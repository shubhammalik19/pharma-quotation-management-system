<?php
require_once '../common/conn.php';
require_once '../common/functions.php';

// Check if user is logged in and has permissions
checkLoginAndPermission('reports_debit_notes', 'view');

// Get vendors for dropdown
$vendors_sql = "SELECT id, company_name, contact_person FROM customers WHERE entity_type IN ('vendor', 'both') ORDER BY company_name";
$vendors = $conn->query($vendors_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debit Notes Report Filter</title>
    
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
                    <h2><i class="bi bi-plus-circle text-primary"></i> Debit Notes Report</h2>
                    <p class="text-muted">Filter and analyze debit note data with comprehensive reporting options</p>
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
            <form method="GET" action="debit_notes_data.php" id="filterForm">
                <div class="row g-3">
                    <!-- Vendor Filter -->
                    <div class="col-md-4">
                        <label for="vendor_id" class="form-label">
                            <i class="bi bi-building"></i> Vendor
                        </label>
                        <select class="form-select" id="vendor_id" name="vendor_id">
                            <option value="">All Vendors</option>
                            <?php if ($vendors && $vendors->num_rows > 0): ?>
                                <?php while ($vendor = $vendors->fetch_assoc()): ?>
                                    <option value="<?php echo $vendor['id']; ?>">
                                        <?php echo htmlspecialchars($vendor['company_name']); ?>
                                        <?php if (!empty($vendor['contact_person'])): ?>
                                            (<?php echo htmlspecialchars($vendor['contact_person']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="col-md-4">
                        <label for="status" class="form-label">
                            <i class="bi bi-flag"></i> Debit Note Status
                        </label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="acknowledged">Acknowledged</option>
                            <option value="settled">Settled</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <!-- Amount Range Filter -->
                    <div class="col-md-4">
                        <label for="amount_range" class="form-label">
                            <i class="bi bi-currency-rupee"></i> Amount Range
                        </label>
                        <select class="form-select" id="amount_range" name="amount_range">
                            <option value="">All Amounts</option>
                            <option value="under_10k">Under ₹10k</option>
                            <option value="10k_50k">₹10k - ₹50k</option>
                            <option value="50k_1l">₹50k - ₹1L</option>
                            <option value="1l_5l">₹1L - ₹5L</option>
                            <option value="over_5l">Over ₹5 Lakh</option>
                        </select>
                    </div>

                    <!-- Reason Category Filter -->
                    <div class="col-md-4">
                        <label for="reason_category" class="form-label">
                            <i class="bi bi-tags"></i> Reason Category
                        </label>
                        <select class="form-select" id="reason_category" name="reason_category">
                            <option value="">All Reasons</option>
                            <option value="quality_issue">Quality Issue</option>
                            <option value="delayed_delivery">Delayed Delivery</option>
                            <option value="quantity_shortage">Quantity Shortage</option>
                            <option value="pricing_error">Pricing Error</option>
                            <option value="damage_claim">Damage Claim</option>
                            <option value="penalty">Penalty Charges</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <!-- Original Invoice Filter -->
                    <div class="col-md-4">
                        <label for="original_invoice" class="form-label">
                            <i class="bi bi-receipt"></i> Original Invoice
                        </label>
                        <input type="text" class="form-control" id="original_invoice" name="original_invoice" 
                               placeholder="Search by invoice number">
                    </div>

                    <!-- GST Filter -->
                    <div class="col-md-4">
                        <label for="gst_filter" class="form-label">
                            <i class="bi bi-percent"></i> GST Status
                        </label>
                        <select class="form-select" id="gst_filter" name="gst_filter">
                            <option value="">All Debit Notes</option>
                            <option value="with_gst">With GST Vendor</option>
                            <option value="without_gst">Without GST Vendor</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mt-3">
                    <!-- Debit Date From -->
                    <div class="col-md-3">
                        <label for="date_from" class="form-label">
                            <i class="bi bi-calendar"></i> Debit Date From
                        </label>
                        <input type="date" class="form-control" id="date_from" name="date_from">
                    </div>

                    <!-- Debit Date To -->
                    <div class="col-md-3">
                        <label for="date_to" class="form-label">Debit Date To</label>
                        <input type="date" class="form-control" id="date_to" name="date_to">
                    </div>

                    <!-- Min Amount -->
                    <div class="col-md-3">
                        <label for="min_amount" class="form-label">
                            <i class="bi bi-currency-rupee"></i> Min Amount
                        </label>
                        <input type="number" class="form-control" id="min_amount" name="min_amount" 
                               placeholder="0.00" step="0.01" min="0">
                    </div>

                    <!-- Max Amount -->
                    <div class="col-md-3">
                        <label for="max_amount" class="form-label">Max Amount</label>
                        <input type="number" class="form-control" id="max_amount" name="max_amount" 
                               placeholder="999999.99" step="0.01" min="0">
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
                                    <!-- Debit Note Value Analysis -->
                                    <div class="col-md-4">
                                        <label for="value_analysis" class="form-label">Value Analysis</label>
                                        <select class="form-select" id="value_analysis" name="value_analysis">
                                            <option value="">No specific analysis</option>
                                            <option value="high_value">High Value Debits (Top 20%)</option>
                                            <option value="medium_value">Medium Value Debits</option>
                                            <option value="low_value">Low Value Debits</option>
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
                                <li><strong>Vendor:</strong> Filter by specific vendor company</li>
                                <li><strong>Status:</strong> Filter by debit note status</li>
                                <li><strong>Amount Range:</strong> Filter by total debit amount</li>
                                <li><strong>Reason Category:</strong> Filter by reason for debit</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="mb-0">
                                <li><strong>Original Invoice:</strong> Filter by original invoice number</li>
                                <li><strong>GST Status:</strong> Filter by vendor GST registration</li>
                                <li><strong>Date Ranges:</strong> Filter by debit note issue date</li>
                                <li><strong>Value Analysis:</strong> Analyze debits by value brackets</li>
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
            case 'last_year':
                fromDate = new Date(today.getFullYear() - 1, 0, 1).toISOString().split('T')[0];
                toDate = new Date(today.getFullYear() - 1, 11, 31).toISOString().split('T')[0];
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
            alert('Debit Date From cannot be later than Debit Date To');
            return false;
        }

        const minAmount = parseFloat($('#min_amount').val()) || 0;
        const maxAmount = parseFloat($('#max_amount').val()) || 0;
        
        if (minAmount > 0 && maxAmount > 0 && minAmount > maxAmount) {
            e.preventDefault();
            alert('Min Amount cannot be greater than Max Amount');
            return false;
        }
    });
});
</script>

<?php include '../footer.php'; ?>
</body>
</html>
