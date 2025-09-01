<?php
include 'header.php';
checkLoginAndPermission('dashboard', 'view');
include 'menu.php';

// Get dashboard statistics (only show stats for modules user has access to)
$total_customers = hasModuleAccess('customers') ? $conn->query("SELECT COUNT(*) as count FROM customers")->fetch_assoc()['count'] : 0;
$total_machines = hasModuleAccess('machines') ? $conn->query("SELECT COUNT(*) as count FROM machines")->fetch_assoc()['count'] : 0;
$total_spares = hasModuleAccess('spares') ? $conn->query("SELECT COUNT(*) as count FROM spares")->fetch_assoc()['count'] : 0;
$total_quotations = hasModuleAccess('quotations') ? $conn->query("SELECT COUNT(*) as count FROM quotations")->fetch_assoc()['count'] : 0;

// Recent quotations
$recent_quotations = $conn->query("
    SELECT q.id, q.quotation_number, c.company_name as customer_name, q.total_amount, q.created_at 
    FROM quotations q 
    LEFT JOIN customers c ON q.customer_id = c.id 
    ORDER BY q.created_at DESC 
    LIMIT 5
");
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
            <hr>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3><?php echo $total_customers; ?></h3>
                            <p class="mb-0">Customers</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-people fs-1"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?php echo url('customers.php'); ?>" class="text-white text-decoration-none">
                        View Details <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3><?php echo $total_machines; ?></h3>
                            <p class="mb-0">Machines</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-gear fs-1"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?php echo url('machines.php'); ?>" class="text-white text-decoration-none">
                        View Details <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3><?php echo $total_spares; ?></h3>
                            <p class="mb-0">Spares</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-tools fs-1"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?php echo url('spares.php'); ?>" class="text-white text-decoration-none">
                        View Details <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3><?php echo $total_quotations; ?></h3>
                            <p class="mb-0">Quotations</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-file-text fs-1"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?php echo url('quotations/quotations.php'); ?>" class="text-white text-decoration-none">
                        View Details <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Quotations -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-clock-history"></i> Recent Quotations</h5>
                </div>
                <div class="card-body">
                    <?php if ($recent_quotations->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Quote Ref</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($quote = $recent_quotations->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $quote['quotation_number']; ?></td>
                                            <td><?php echo $quote['customer_name']; ?></td>
                                            <td><?php echo formatCurrency($quote['total_amount']); ?></td>
                                            <td><?php echo formatDate($quote['created_at']); ?></td>
                                            <td>
                                                <a href="<?php echo url('view_quotation.php?id=' . $quote['id']); ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">No quotations found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-lightning"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo url('customers.php'); ?>" class="btn btn-outline-primary">
                            <i class="bi bi-person-plus"></i> Add Customer
                        </a>
                        <a href="<?php echo url('quotations/quotations.php'); ?>" class="btn btn-outline-success">
                            <i class="bi bi-file-plus"></i> Create Quotation
                        </a>
                        <a href="<?php echo url('machines.php'); ?>" class="btn btn-outline-info">
                            <i class="bi bi-gear-wide"></i> Manage Machines
                        </a>
                        <a href="<?php echo url('price_master.php'); ?>" class="btn btn-outline-warning">
                            <i class="bi bi-currency-rupee"></i> Update Prices
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
