<?php


include 'header.php';
checkLogin();
include 'menu.php';

$message = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create_price' && hasPermission('price_master', 'create')) {
            $machine_id = intval($_POST['machine_id'] ?? 0);
            $price = floatval($_POST['price'] ?? 0);
            $valid_from = sanitizeInput($_POST['valid_from']);
            $valid_to = sanitizeInput($_POST['valid_to']);
            $is_active = 1;
            
            if ($machine_id && $price && $valid_from && $valid_to) {
                // Check for overlapping date ranges for the same machine
                $checkSql = "SELECT id FROM price_master 
                            WHERE machine_id = $machine_id 
                            AND (
                                ('$valid_from' BETWEEN valid_from AND valid_to) OR 
                                ('$valid_to' BETWEEN valid_from AND valid_to) OR 
                                (valid_from BETWEEN '$valid_from' AND '$valid_to') OR 
                                (valid_to BETWEEN '$valid_from' AND '$valid_to')
                            )";
                
                $checkResult = $conn->query($checkSql);
                
                if ($checkResult->num_rows > 0) {
                    $message = showError("A price record already exists for this machine with overlapping date range!");
                } else {
                    $sql = "INSERT INTO price_master (machine_id, price, valid_from, valid_to, is_active) 
                            VALUES ($machine_id, $price, '$valid_from', '$valid_to', $is_active)";
                    
                    if ($conn->query($sql)) {
                        $message = showSuccess('Price created successfully!');
                    } else {
                        $message = showError('Error creating price: ' . $conn->error);
                    }
                }
            } else {
                $message = showError('All fields are required!');
            }
        } elseif ($_POST['action'] === 'update_price' && hasPermission('price_master', 'edit')) {
            $price_id = intval($_POST['id']);
            $machine_id = intval($_POST['machine_id'] ?? 0);
            $price = floatval($_POST['price'] ?? 0);
            $valid_from = sanitizeInput($_POST['valid_from']);
            $valid_to = sanitizeInput($_POST['valid_to']);
            
            if ($machine_id && $price && $valid_from && $valid_to) {
                // Check for overlapping date ranges for the same machine (excluding current record)
                $checkSql = "SELECT id FROM price_master 
                            WHERE machine_id = $machine_id 
                            AND id != $price_id
                            AND (
                                ('$valid_from' BETWEEN valid_from AND valid_to) OR 
                                ('$valid_to' BETWEEN valid_from AND valid_to) OR 
                                (valid_from BETWEEN '$valid_from' AND '$valid_to') OR 
                                (valid_to BETWEEN '$valid_from' AND '$valid_to')
                            )";
                
                $checkResult = $conn->query($checkSql);
                
                if ($checkResult->num_rows > 0) {
                    $message = showError("A price record already exists for this machine with overlapping date range!");
                } else {
                    $sql = "UPDATE price_master SET 
                            machine_id = $machine_id, 
                            price = $price, 
                            valid_from = '$valid_from', 
                            valid_to = '$valid_to'
                            WHERE id = $price_id";
                    
                    if ($conn->query($sql)) {
                        $message = showSuccess('Price updated successfully!');
                    } else {
                        $message = showError('Error updating price: ' . $conn->error);
                    }
                }
            } else {
                $message = showError('All fields are required!');
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    if (!hasPermission('price_master', 'delete')) {
        $message = showError("You don't have permission to delete price entries!");
    } else {
        $id = (int)$_GET['delete'];
        $sql = "DELETE FROM price_master WHERE id = $id";
        if ($conn->query($sql)) {
            $message = showSuccess("Price record deleted successfully!");
        } else {
            $message = showError("Error deleting price record: " . $conn->error);
        }
    }
}

// Get search parameter
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Build search query
$where_clause = '';
if (!empty($search)) {
    $where_clause = "WHERE m.name LIKE '%$search%' OR m.model LIKE '%$search%' OR m.category LIKE '%$search%'";
}

// Count total records
$count_sql = "SELECT COUNT(*) as total FROM price_master pm LEFT JOIN machines m ON pm.machine_id = m.id $where_clause";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get machines for dropdown
$machines = $conn->query("SELECT id, name, model FROM machines WHERE is_active = 1 ORDER BY name");
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-currency-rupee"></i> Price Master Management</h2>
            <hr>
        </div>
    </div>
    
    <?php echo $message; ?>
    
    <!-- Search Box -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="search-box">
                <div class="row">
                    <div class="col-md-6">
                        <label for="priceSearch" class="form-label">
                            <i class="bi bi-search"></i> Search Machine Prices
                        </label>
                        <input type="text" class="form-control" id="priceSearch" 
                               placeholder="Search by machine name, model or category..."
                               value="<?php echo htmlspecialchars($search); ?>"
                               autocomplete="off">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-primary" id="searchBtn">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <button type="button" class="btn btn-secondary ms-2" id="clearBtn">
                            <i class="bi bi-x-circle"></i> Clear
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <form method="POST" id="priceForm" class="row">
            <!-- Price Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-currency-rupee"></i> <span id="formTitle">Create Price Entry</span></h5>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="action" value="create_price" id="formAction">
                        <input type="hidden" name="id" id="priceId">
                        
                        <div class="mb-3">
                            <label for="machine_id" class="form-label">Select Machine *</label>
                            <select class="form-control" id="machine_id" name="machine_id" required>
                                <option value="">Choose Machine...</option>
                                <?php 
                                $machines->data_seek(0);
                                while ($machine = $machines->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $machine['id']; ?>">
                                        <?php echo htmlspecialchars($machine['name'] . ' (' . $machine['model'] . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="price" class="form-label">Price (₹) *</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="valid_from" class="form-label">Valid From *</label>
                                <input type="date" class="form-control" id="valid_from" name="valid_from" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="valid_to" class="form-label">Valid To *</label>
                                <input type="date" class="form-control" id="valid_to" name="valid_to" required>
                            </div>
                        </div>
                        
                        <!-- Information Alert -->
                        <div class="alert alert-info">
                            <small>
                                <i class="bi bi-info-circle"></i> 
                                <strong>Note:</strong> System checks for overlapping date ranges for the same machine.
                            </small>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-success" id="saveBtn"><i class="bi bi-plus-circle"></i> Save Price</button>
                            <button type="submit" class="btn btn-primary" id="updateBtn" style="display:none;"><i class="bi bi-check"></i> Update</button>
                            <button type="button" class="btn btn-warning" id="editBtn" style="display:none;"><i class="bi bi-pencil"></i> Edit</button>
                            <button type="button" class="btn btn-danger" id="deleteBtn" style="display:none;"><i class="bi bi-trash"></i> Delete</button>
                            <button type="button" class="btn btn-secondary" id="resetBtn"><i class="bi bi-arrow-clockwise"></i> Reset Form</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Price List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="bi bi-list"></i> All Machine Prices (<?php echo $total_records; ?>)</h5>
                        <small>Page <?php echo $page; ?> of <?php echo $total_pages; ?></small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Machine</th>
                                        <th>Model</th>
                                        <th>Price</th>
                                        <th>Valid From</th>
                                        <th>Valid To</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT pm.*, m.name as machine_name, m.model FROM price_master pm LEFT JOIN machines m ON pm.machine_id = m.id $where_clause ORDER BY pm.created_at DESC LIMIT $records_per_page OFFSET $offset";
                                    $result = $conn->query($sql);
                                    
                                    if ($result && $result->num_rows > 0):
                                        while ($row = $result->fetch_assoc()):
                                            $today = date('Y-m-d');
                                            $status = 'expired';
                                            $status_class = 'danger';
                                            
                                            if ($today >= $row['valid_from'] && $today <= $row['valid_to']) {
                                                $status = 'active';
                                                $status_class = 'success';
                                            } elseif ($today < $row['valid_from']) {
                                                $status = 'future';
                                                $status_class = 'warning';
                                            }
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['machine_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['model']); ?></td>
                                        <td><strong>₹<?php echo number_format($row['price'], 2); ?></strong></td>
                                        <td><?php echo formatDate($row['valid_from']); ?></td>
                                        <td><?php echo formatDate($row['valid_to']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $status_class; ?>"><?php echo ucfirst($status); ?></span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary edit-price" data-id="<?php echo $row['id']; ?>">
                                                <i class="bi bi-pencil"></i> View/Edit
                                            </button>
                                            <?php if (hasPermission('price_master', 'delete')): ?>
                                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this price record?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="bi bi-currency-rupee display-1 text-muted"></i>
                                            <p class="mt-3">No price records found.</p>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav><ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item"><a class="page-link" href="?page=<?php echo ($page - 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Previous</a></li>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a></li>
                                <?php endfor; ?>
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item"><a class="page-link" href="?page=<?php echo ($page + 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next</a></li>
                                <?php endif; ?>
                            </ul></nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Styles specific to this page -->
<style>
    .readonly-field { background: #f8f9fa !important; pointer-events: none; }
    .table-hover tbody tr:hover { background-color: rgba(0,0,0,.075); }

    /* jQuery UI Autocomplete look */
    .ui-autocomplete {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #ced4da;
        border-radius: .375rem;
        background: #fff;
        z-index: 1050 !important;
        box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
    }
    .ui-menu-item > div { padding: 12px 15px; cursor: pointer; }
    .ui-menu-item:hover > div, .ui-state-focus > div {
        background-color: #e9ecef !important; border: none !important;
    }
    .ui-autocomplete-loading {
        background: #fff url('data:image/gif;base64,R0lGODlhEAAQAPIAAP///wAAAMLCwkJCQgAAAGJiYoKCgpKSkiH/C05FVFNDQVBFMi4wAwEAAAAh/hpDcmVhdGVkIHdpdGggYWpheGxvYWQuaW5mbwAh+QQJCgAAACwAAAAAEAAQAAADMwi63P4wyklrE2MIOggZnAdOmGYJRbExwroUmcG2LmDEwnHQLVsYOd2mBzkYDAdKa+dIAAAh+QQJCgAAACwAAAAAEAAQAAADNAi63P5OjCEgG4QMu7DmikRxQlFUYDEZIGBMRVsaqHwctXXf7WEYB4Ag1xjihkMZsiUkKhIAIfkECQoAAAAsAAAAABAAEAAAAzYIujIjK8pByJDMlFYvBoVjHA70GU7xSUJhmKtwHPAKzLO9HMaoKwJZ7Rf8AYPDDzKpZBqfvwQAIfkECQoAAAAsAAAAABAAEAAAAzMIumIlK8oyhpHsnFZfhYumCYUhDAQxRIdhHBGqRoKw0R8DYlJd8z0fMDgsGo/IpHI5TAAAIfkECQoAAAAsAAAAABAAEAAAAzIIunInK0rnZBTwGPNMgQwmdsNgXGJUlIWEuR5oWUIpz8pAEAMe6TwfwyYsGo/IpHI5TAAAh+QQJCgAAACwAAAAAEAAQAAADMwi6IMKQORfjdOe82p4wGccc4CEuQradylesojEMBgsUc2G7sDX3lQGBMLAJibufbSlKAAAh+QQJCgAAACwAAAAAEAAQAAADMgi63P7wjQkLDfwACl3iyOsGgfFjhJUdZBmmBnSZYgYpvr7KfD4rGGF4/I5cUhTdACwWAA==') no-repeat right center;
        background-size: 16px 16px;
        padding-right: 40px;
    }
</style>

<script src="js/price_master.js"></script>

<?php include 'footer.php'; ?>