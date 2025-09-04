<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="container-fluid">
    <div class="row">
        <!-- Professional Sidebar -->
        <nav class="col-lg-3 col-xl-2 d-lg-block sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebarMenu">
            <!-- Enhanced Mobile Header -->
            <div class="offcanvas-header d-lg-none bg-primary text-white">
                <h5 class="offcanvas-title fw-bold" id="sidebarMenuLabel">
                    <i class="bi bi-clipboard-data-fill me-2"></i>
                    <?php echo SITE_NAME; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Close"></button>
            </div>
            
            <div class="offcanvas-body p-0">
                <div class="sidebar-nav">
                    <!-- USER PROFILE AS MENU ITEM -->
                    <div class="sidebar-section">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <div class="nav-link user-profile-menu">
                                    <div class="user-avatar-inline">
                                        <img src="<?php echo getProfilePicture($_SESSION['profile_picture'] ?? null); ?>" 
                                             alt="Profile" class="avatar-mini">
                                    </div>
                                    <div class="user-info-inline">
                                        <span class="user-name-menu"><?php echo getUserDisplayName(); ?></span>
                                        <small class="user-role-menu"><?php echo getUserRolesString(); ?></small>
                                    </div>
                                    <div class="dropdown ms-auto">
                                        <button class="btn btn-sm btn-outline-light" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="/auth/change_password.php">
                                                <i class="bi bi-key"></i> Change Password
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="/auth/logout.php">
                                                <i class="bi bi-box-arrow-right"></i> Logout
                                            </a></li>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div class="sidebar-divider"></div>

                    <!-- CORE NAVIGATION -->
                    <div class="sidebar-section">
                        <h6 class="sidebar-section-title">
                            <i class="bi bi-speedometer2"></i>
                            Core
                        </h6>
                        <ul class="nav flex-column">
                            <?php if (hasPermission('dashboard', 'view')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="/dashboard.php">
                                    <i class="bi bi-speedometer2"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- MASTER DATA SECTION -->
                    <?php if (hasModuleAccess('customers') || hasModuleAccess('machines') || hasModuleAccess('spares') || hasModuleAccess('price_master')): ?>
                    <div class="sidebar-section">
                        <h6 class="sidebar-section-title">
                            <i class="bi bi-database"></i>
                            Master Data
                        </h6>
                        <ul class="nav flex-column">
                            <?php if (hasModuleAccess('customers')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'customers.php') ? 'active' : ''; ?>" href="/customers.php">
                                    <i class="bi bi-people-fill"></i>
                                    <span>Customers & Vendors</span>
                                    <?php if (hasPermission('customers', 'create')): ?>
                                        <span class="badge badge-success ms-auto">+</span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasModuleAccess('machines')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'machines.php') ? 'active' : ''; ?>" href="/machines.php">
                                    <i class="bi bi-gear-wide-connected"></i>
                                    <span>Machines</span>
                                    <?php if (hasPermission('machines', 'create')): ?>
                                        <span class="badge badge-success ms-auto">+</span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasModuleAccess('spares')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'spares.php') ? 'active' : ''; ?>" href="/spares.php">
                                    <i class="bi bi-tools"></i>
                                    <span>Spare Parts</span>
                                    <?php if (hasPermission('spares', 'create')): ?>
                                        <span class="badge badge-success ms-auto">+</span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasModuleAccess('price_master')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'price_master.php') ? 'active' : ''; ?>" href="/price_master.php">
                                    <i class="bi bi-currency-rupee"></i>
                                    <span>Price Master</span>
                                    <?php if (hasPermission('price_master', 'create')): ?>
                                        <span class="badge badge-warning ms-auto">₹</span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- SALES & QUOTATIONS SECTION -->
                    <?php if (hasModuleAccess('quotations') || hasModuleAccess('sales_orders') || hasModuleAccess('purchase_orders') || hasModuleAccess('sales_invoices')): ?>
                    <div class="sidebar-section">
                        <h6 class="sidebar-section-title">
                            <i class="bi bi-cart-check"></i>
                            Sales & Orders
                        </h6>
                        <ul class="nav flex-column">
                            <?php if (hasModuleAccess('quotations')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'quotations.php') ? 'active' : ''; ?>" href="/quotations/quotations.php">
                                    <i class="bi bi-file-text-fill"></i>
                                    <span>Quotations</span>
                                    <?php if (hasPermission('quotations', 'create')): ?>
                                        <span class="badge badge-primary ms-auto">Q</span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasModuleAccess('sales_orders')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'sales_orders.php') ? 'active' : ''; ?>" href="/sales/sales_orders.php">
                                    <i class="bi bi-cart-check-fill"></i>
                                    <span>Sales Orders</span>
                                    <?php if (hasPermission('sales_orders', 'create')): ?>
                                        <span class="badge badge-success ms-auto">SO</span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasModuleAccess('purchase_orders')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'purchase_orders.php') ? 'active' : ''; ?>" href="/sales/purchase_orders.php">
                                    <i class="bi bi-cart-plus-fill"></i>
                                    <span>Purchase Orders</span>
                                    <?php if (hasPermission('purchase_orders', 'create')): ?>
                                        <span class="badge badge-info ms-auto">PO</span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasModuleAccess('sales_invoices')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'sales_invoices.php') ? 'active' : ''; ?>" href="/sales/sales_invoices.php">
                                    <i class="bi bi-receipt"></i>
                                    <span>Sales Invoices</span>
                                    <?php if (hasPermission('sales_invoices', 'create')): ?>
                                        <span class="badge badge-success ms-auto">SI</span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasModuleAccess('purchase_invoices')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'purchase_invoices.php') ? 'active' : ''; ?>" href="/sales/purchase_invoices.php">
                                    <i class="bi bi-receipt-cutoff"></i>
                                    <span>Purchase Invoices</span>
                                    <?php if (hasPermission('purchase_invoices', 'create')): ?>
                                        <span class="badge badge-warning ms-auto">PI</span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- FINANCIAL DOCUMENTS SECTION -->
                    <?php if (hasModuleAccess('credit_notes') || hasModuleAccess('debit_notes')): ?>
                    <div class="sidebar-section">
                        <h6 class="sidebar-section-title">
                            <i class="bi bi-receipt-cutoff"></i>
                            Financial Notes
                        </h6>
                        <ul class="nav flex-column">
                            <?php if (hasModuleAccess('credit_notes')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'credit_notes.php') ? 'active' : ''; ?>" href="/sales/credit_notes.php">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                    <span>Credit Notes</span>
                                    <?php if (hasPermission('credit_notes', 'create')): ?>
                                        <span class="badge badge-warning ms-auto">CR</span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasModuleAccess('debit_notes')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'debit_notes.php') ? 'active' : ''; ?>" href="/sales/debit_notes.php">
                                    <i class="bi bi-arrow-clockwise"></i>
                                    <span>Debit Notes</span>
                                    <?php if (hasPermission('debit_notes', 'create')): ?>
                                        <span class="badge badge-warning ms-auto">DR</span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- ANALYTICS & REPORTS SECTION -->
                    <?php if (hasModuleAccess('reports') || hasModuleAccess('reports_customers') || hasModuleAccess('reports_machines') || hasModuleAccess('reports_spares') || hasModuleAccess('reports_price') || hasModuleAccess('reports_quotations') || hasModuleAccess('reports_sales_orders') || hasModuleAccess('reports_purchase_orders') || hasModuleAccess('reports_sales_invoices') || hasModuleAccess('reports_credit_notes') || hasModuleAccess('reports_debit_notes')): ?>
                    <div class="sidebar-section">
                        <h6 class="sidebar-section-title">
                            <i class="bi bi-graph-up-arrow"></i>
                            Reports & Analytics
                        </h6>
                        <ul class="nav flex-column">
                            <!-- Main Reports Dashboard -->
                            <?php if (hasModuleAccess('reports')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>" href="/reports.php">
                                    <i class="bi bi-bar-chart-fill"></i>
                                    <span>Reports Dashboard</span>
                                    <?php if (hasPermission('reports', 'export')): ?>
                                        <span class="badge badge-info ms-auto"><i class="bi bi-download"></i></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endif; ?>

                            <!-- Master Data Reports Submenu -->
                            <?php if (hasModuleAccess('reports_customers') || hasModuleAccess('reports_machines') || hasModuleAccess('reports_spares') || hasModuleAccess('reports_price')): ?>
                            <li class="nav-item">
                                <a class="nav-link collapsed" data-bs-toggle="collapse" data-bs-target="#masterReportsSubmenu" aria-expanded="false">
                                    <i class="bi bi-database-fill"></i>
                                    <span>Master Data Reports</span>
                                    <i class="bi bi-chevron-down ms-auto"></i>
                                </a>
                                <div class="collapse" id="masterReportsSubmenu">
                                    <ul class="nav flex-column ps-3">
                                        <?php if (hasModuleAccess('reports_customers')): ?>
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo ($current_page == 'customers_filter.php') ? 'active' : ''; ?>" href="/reports/customers_filter.php">
                                                <i class="bi bi-people"></i>
                                                <span>Customer/Vendor Reports</span>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <?php if (hasModuleAccess('reports_machines')): ?>
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo ($current_page == 'machines_filter.php') ? 'active' : ''; ?>" href="/reports/machines_filter.php">
                                                <i class="bi bi-gear-wide-connected"></i>
                                                <span>Machine Reports</span>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <?php if (hasModuleAccess('reports_spares')): ?>
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo ($current_page == 'spares_filter.php') ? 'active' : ''; ?>" href="/reports/spares_filter.php">
                                                <i class="bi bi-tools"></i>
                                                <span>Spare Parts Reports</span>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <?php if (hasModuleAccess('reports_price')): ?>
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo ($current_page == 'price_filter.php') ? 'active' : ''; ?>" href="/reports/price_filter.php">
                                                <i class="bi bi-currency-rupee"></i>
                                                <span>Price Master Reports</span>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </li>
                            <?php endif; ?>

                            <!-- Transaction Reports Submenu -->
                            <?php if (hasModuleAccess('reports_quotations') || hasModuleAccess('reports_sales_orders') || hasModuleAccess('reports_purchase_orders') || hasModuleAccess('reports_sales_invoices') || hasModuleAccess('reports_credit_notes') || hasModuleAccess('reports_debit_notes')): ?>
                            <li class="nav-item">
                                <a class="nav-link collapsed" data-bs-toggle="collapse" data-bs-target="#transactionReportsSubmenu" aria-expanded="false">
                                    <i class="bi bi-receipt-cutoff"></i>
                                    <span>Transaction Reports</span>
                                    <i class="bi bi-chevron-down ms-auto"></i>
                                </a>
                                <div class="collapse" id="transactionReportsSubmenu">
                                    <ul class="nav flex-column ps-3">
                                        <?php if (hasModuleAccess('reports_quotations')): ?>
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo ($current_page == 'quotations_filter.php') ? 'active' : ''; ?>" href="/reports/quotations_filter.php">
                                                <i class="bi bi-file-text"></i>
                                                <span>Quotation Reports</span>
                                                <span class="badge badge-primary ms-auto">Q</span>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <?php if (hasModuleAccess('reports_sales_orders')): ?>
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo ($current_page == 'sales_orders_filter.php') ? 'active' : ''; ?>" href="/reports/sales_orders_filter.php">
                                                <i class="bi bi-cart-check"></i>
                                                <span>Sales Order Reports</span>
                                                <span class="badge badge-success ms-auto">SO</span>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <?php if (hasModuleAccess('reports_purchase_orders')): ?>
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo ($current_page == 'purchase_orders_filter.php') ? 'active' : ''; ?>" href="/reports/purchase_orders_filter.php">
                                                <i class="bi bi-cart-plus"></i>
                                                <span>Purchase Order Reports</span>
                                                <span class="badge badge-info ms-auto">PO</span>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <?php if (hasModuleAccess('reports_sales_invoices')): ?>
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo ($current_page == 'sales_invoices_filter.php') ? 'active' : ''; ?>" href="/reports/sales_invoices_filter.php">
                                                <i class="bi bi-receipt"></i>
                                                <span>Sales Invoice Reports</span>
                                                <span class="badge badge-success ms-auto">SI</span>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        <?php if (hasModuleAccess('reports_purchase_invoices')): ?>
                                            <li class="nav-item">
                                                <a class="nav-link <?php echo ($current_page == 'purchase_invoices_filter.php') ? 'active' : ''; ?>" href="/reports/purchase_invoices_filter.php">
                                                    <i class="bi bi-file-earmark-text"></i>
                                                    <span>Purchase Invoice Reports</span>
                                                    <span class="badge badge-info ms-auto">PI</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php if (hasModuleAccess('reports_credit_notes')): ?>
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo ($current_page == 'credit_notes_filter.php') ? 'active' : ''; ?>" href="/reports/credit_notes_filter.php">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                                <span>Credit Note Reports</span>
                                                <span class="badge badge-warning ms-auto">CR</span>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <?php if (hasModuleAccess('reports_debit_notes')): ?>
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo ($current_page == 'debit_notes_filter.php') ? 'active' : ''; ?>" href="/reports/debit_notes_filter.php">
                                                <i class="bi bi-arrow-clockwise"></i>
                                                <span>Debit Note Reports</span>
                                                <span class="badge badge-warning ms-auto">DR</span>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <div class="sidebar-divider"></div>

                    <!-- ADMINISTRATION SECTION -->
                    <?php if (isAdmin() || hasModuleAccess('users') || hasModuleAccess('settings')): ?>
                    <div class="sidebar-section">
                        <h6 class="sidebar-section-title">
                            <i class="bi bi-shield-lock"></i>
                            Administration
                        </h6>
                        <ul class="nav flex-column">
                            <?php if (hasModuleAccess('users')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>" href="/auth/users.php">
                                    <i class="bi bi-people-fill"></i>
                                    <span>User Management</span>
                                    <?php if (hasPermission('users', 'create')): ?>
                                        <span class="badge badge-warning ms-auto"><i class="bi bi-person-plus"></i></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('users', 'edit') || isSuperAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'roles.php') ? 'active' : ''; ?>" href="/auth/roles.php">
                                    <i class="bi bi-shield-check"></i>
                                    <span>Role Management</span>
                                    <span class="badge badge-primary ms-auto"><i class="bi bi-shield"></i></span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasModuleAccess('settings')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'email_config.php') ? 'active' : ''; ?>" href="/email/email_config.php">
                                    <i class="bi bi-envelope-gear"></i>
                                    <span>Email Settings</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (isAdmin() || hasModuleAccess('settings')): ?>
                         <!--  
                         DONT ACTIVATE IT       
                         <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'view_logs.php') ? 'active' : ''; ?>" href="/docs/view_logs.php">
                                    <i class="bi bi-file-text"></i>
                                    <span>Document Logs</span>
                                    <span class="badge badge-info ms-auto"><i class="bi bi-activity"></i></span>
                                </a>
                            </li> -->
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <!-- Main content wrapper -->
        <main class="col-lg-9 col-xl-10 ms-lg-auto main-content">
            <div class="container-fluid fade-in">
