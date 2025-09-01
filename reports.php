<?php
include 'header.php';
checkLoginAndPermission('reports', 'view');
include 'menu.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-graph-up-arrow"></i> Reports & Analytics Dashboard</h2>
            <p class="text-muted">Comprehensive reporting system for all modules</p>
            <hr>
        </div>
    </div>
    
    <!-- MODULE MASTERS Section -->
    <div class="row mb-5">
        <div class="col-12">
            <h4 class="mb-4">
                <i class="bi bi-database-fill text-primary"></i> 
                MODULE MASTERS
            </h4>
        </div>
        
        <div class="row">
            <!-- Customer/Vendor Reports -->
            <?php if (hasModuleAccess('reports_customers')): ?>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card report-card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="report-icon mb-3">
                            <i class="bi bi-people-fill display-4 text-primary"></i>
                        </div>
                        <h5 class="card-title">Customer/Vendor</h5>
                        <p class="card-text text-muted">Comprehensive customer and vendor master data reports</p>
                        <a href="reports/customers_filter.php" class="btn btn-primary">
                            <i class="bi bi-bar-chart"></i> View Reports
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Machine Reports -->
            <?php if (hasModuleAccess('reports_machines')): ?>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card report-card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="report-icon mb-3">
                            <i class="bi bi-gear-wide-connected display-4 text-success"></i>
                        </div>
                        <h5 class="card-title">Machines</h5>
                        <p class="card-text text-muted">Machine master data and specifications reports</p>
                        <a href="reports/machines_filter.php" class="btn btn-success">
                            <i class="bi bi-bar-chart"></i> View Reports
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Spare Parts Reports -->
            <?php if (hasModuleAccess('reports_spares')): ?>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card report-card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="report-icon mb-3">
                            <i class="bi bi-tools display-4 text-warning"></i>
                        </div>
                        <h5 class="card-title">Spare Parts</h5>
                        <p class="card-text text-muted">Spare parts inventory and specifications reports</p>
                        <a href="reports/spares_filter.php" class="btn btn-warning">
                            <i class="bi bi-bar-chart"></i> View Reports
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Price Master Reports -->
            <?php if (hasModuleAccess('reports_price')): ?>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card report-card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="report-icon mb-3">
                            <i class="bi bi-currency-rupee display-4 text-info"></i>
                        </div>
                        <h5 class="card-title">Price Master</h5>
                        <p class="card-text text-muted">Pricing structure and cost analysis reports</p>
                        <a href="reports/price_filter.php" class="btn btn-info">
                            <i class="bi bi-bar-chart"></i> View Reports
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- MODULE TRANSACTIONS Section -->
    <div class="row">
        <div class="col-12">
            <h4 class="mb-4">
                <i class="bi bi-receipt-cutoff text-danger"></i> 
                MODULE TRANSACTIONS
            </h4>
        </div>
        
        <div class="row">
            <!-- Quotation Reports -->
            <?php if (hasModuleAccess('reports_quotations')): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card report-card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="report-icon mb-3">
                            <i class="bi bi-file-text-fill display-4 text-primary"></i>
                        </div>
                        <h5 class="card-title">Quotations</h5>
                        <p class="card-text text-muted">Quotation analysis, trends, and performance reports</p>
                        <div class="d-flex justify-content-center gap-2">
                            <span class="badge bg-primary">Q</span>
                            <span class="text-muted">Date, Customer, Machine filters</span>
                        </div>
                        <a href="reports/quotations_filter.php" class="btn btn-primary mt-3">
                            <i class="bi bi-bar-chart"></i> View Reports
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Sales Order Reports -->
            <?php if (hasModuleAccess('reports_sales_orders')): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card report-card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="report-icon mb-3">
                            <i class="bi bi-cart-check-fill display-4 text-success"></i>
                        </div>
                        <h5 class="card-title">Sales Orders</h5>
                        <p class="card-text text-muted">Sales order tracking, fulfillment, and revenue reports</p>
                        <div class="d-flex justify-content-center gap-2">
                            <span class="badge bg-success">SO</span>
                            <span class="text-muted">Date, Customer, Machine filters</span>
                        </div>
                        <a href="reports/sales_orders_filter.php" class="btn btn-success mt-3">
                            <i class="bi bi-bar-chart"></i> View Reports
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Purchase Order Reports -->
            <?php if (hasModuleAccess('reports_purchase_orders')): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card report-card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="report-icon mb-3">
                            <i class="bi bi-cart-plus-fill display-4 text-info"></i>
                        </div>
                        <h5 class="card-title">Purchase Orders</h5>
                        <p class="card-text text-muted">Purchase order management and vendor analysis reports</p>
                        <div class="d-flex justify-content-center gap-2">
                            <span class="badge bg-info">PO</span>
                            <span class="text-muted">Date, Vendor, Machine filters</span>
                        </div>
                        <a href="reports/purchase_orders_filter.php" class="btn btn-info mt-3">
                            <i class="bi bi-bar-chart"></i> View Reports
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Sales Invoice Reports -->
            <?php if (hasModuleAccess('reports_sales_invoices')): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card report-card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="report-icon mb-3">
                            <i class="bi bi-receipt display-4 text-warning"></i>
                        </div>
                        <h5 class="card-title">Sales Invoices</h5>
                        <p class="card-text text-muted">Invoice generation, payment tracking, and GST reports</p>
                        <div class="d-flex justify-content-center gap-2">
                            <span class="badge bg-warning">SI</span>
                            <span class="text-muted">Date, Customer, Machine filters</span>
                        </div>
                        <a href="reports/sales_invoices_filter.php" class="btn btn-warning mt-3">
                            <i class="bi bi-bar-chart"></i> View Reports
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Credit Note Reports -->
            <?php if (hasModuleAccess('reports_credit_notes')): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card report-card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="report-icon mb-3">
                            <i class="bi bi-arrow-counterclockwise display-4 text-danger"></i>
                        </div>
                        <h5 class="card-title">Credit Notes</h5>
                        <p class="card-text text-muted">Credit note management and refund analysis reports</p>
                        <div class="d-flex justify-content-center gap-2">
                            <span class="badge bg-danger">CR</span>
                            <span class="text-muted">Date, Customer filters</span>
                        </div>
                        <a href="reports/credit_notes_filter.php" class="btn btn-danger mt-3">
                            <i class="bi bi-bar-chart"></i> View Reports
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Debit Note Reports -->
            <?php if (hasModuleAccess('reports_debit_notes')): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card report-card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="report-icon mb-3">
                            <i class="bi bi-arrow-clockwise display-4 text-secondary"></i>
                        </div>
                        <h5 class="card-title">Debit Notes</h5>
                        <p class="card-text text-muted">Debit note tracking and additional charges reports</p>
                        <div class="d-flex justify-content-center gap-2">
                            <span class="badge bg-secondary">DR</span>
                            <span class="text-muted">Date, Customer filters</span>
                        </div>
                        <a href="reports/debit_notes_filter.php" class="btn btn-secondary mt-3">
                            <i class="bi bi-bar-chart"></i> View Reports
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.report-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border-radius: 15px;
}

.report-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
}

.report-icon {
    opacity: 0.8;
}

.report-card:hover .report-icon {
    opacity: 1;
}

.badge {
    font-size: 0.9em;
}

.card-title {
    color: #333;
    font-weight: 600;
}
</style>

<?php include 'footer.php'; ?>
