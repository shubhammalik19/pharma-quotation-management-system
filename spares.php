<?php


include 'header.php';
checkLogin();
include 'menu.php';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create_spare' && hasPermission('spares', 'create')) {
            $part_name = sanitizeInput($_POST['part_name']);
            $part_code = sanitizeInput($_POST['part_code']);
            $description = sanitizeInput($_POST['description']);
            $price = floatval($_POST['price'] ?? 0);
            $machine_id = intval($_POST['machine_id'] ?? 0);
            $is_active = 1;
            
            if (!empty($part_name)) {
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    $sql = "INSERT INTO spares (part_name, part_code, description, price, machine_id, is_active) 
                            VALUES ('$part_name', '$part_code', '$description', $price, " . ($machine_id ?: 'NULL') . ", $is_active)";
                    
                    if (!$conn->query($sql)) {
                        throw new Exception('Error creating spare part: ' . $conn->error);
                    }
                    
                    $conn->commit();
                    redirectWithSuccess('Spare part created successfully!');
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    redirectWithError($e->getMessage());
                }
            } else {
                redirectWithError('Part name is required!');
            }
        } elseif ($_POST['action'] === 'update_spare' && hasPermission('spares', 'edit')) {
            $spare_id = intval($_POST['id']);
            $part_name = sanitizeInput($_POST['part_name']);
            $part_code = sanitizeInput($_POST['part_code']);
            $description = sanitizeInput($_POST['description']);
            $price = floatval($_POST['price'] ?? 0);
            $machine_id = intval($_POST['machine_id'] ?? 0);
            
            if (!empty($part_name)) {
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    $sql = "UPDATE spares SET 
                            part_name = '$part_name', 
                            part_code = '$part_code', 
                            description = '$description', 
                            price = $price, 
                            machine_id = " . ($machine_id ?: 'NULL') . "
                            WHERE id = $spare_id";
                    
                    if (!$conn->query($sql)) {
                        throw new Exception('Error updating spare part: ' . $conn->error);
                    }
                    
                    $conn->commit();
                    redirectWithSuccess('Spare part updated successfully!');
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    redirectWithError($e->getMessage());
                }
            } else {
                redirectWithError('Part name is required!');
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    if (!hasPermission('spares', 'delete')) {
        redirectWithError("You don't have permission to delete spare parts!");
    } else {
        $id = (int)$_GET['delete'];
        
        // Get spare part name for confirmation message
        $spare_sql = "SELECT part_name FROM spares WHERE id = $id";
        $spare_result = $conn->query($spare_sql);
        $spare_name = '';
        if ($spare_result && $spare_row = $spare_result->fetch_assoc()) {
            $spare_name = $spare_row['part_name'];
        }
        
        $sql = "DELETE FROM spares WHERE id = $id";
        if ($conn->query($sql)) {
            if ($conn->affected_rows > 0) {
                redirectWithSuccess("Spare part '$spare_name' deleted successfully!");
            } else {
                redirectWithError("Spare part not found or already deleted!");
            }
        } else {
            redirectWithError("Error deleting spare part: " . $conn->error);
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
    $where_clause = "WHERE s.part_name LIKE '%$search%' OR s.part_code LIKE '%$search%' OR s.description LIKE '%$search%' OR m.name LIKE '%$search%'";
}

// Count total records
$count_sql = "SELECT COUNT(*) as total FROM spares s LEFT JOIN machines m ON s.machine_id = m.id $where_clause";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get machines for dropdown
$machines = $conn->query("SELECT id, name FROM machines WHERE is_active = 1 ORDER BY name");
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-tools"></i> Spare Parts Management</h2>
            <hr>
        </div>
    </div>
    
    <?php echo getAllMessages(); ?>
    
    <!-- Search Box -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="search-box">
                <div class="row">
                    <div class="col-md-6">
                        <label for="spareSearch" class="form-label">
                            <i class="bi bi-search"></i> Search Spare Parts
                        </label>
                        <input type="text" class="form-control" id="spareSearch" 
                               placeholder="Search by part name, code, description or machine..."
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
        <form method="POST" id="spareForm" class="row">
            <!-- Spare Parts Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-tools"></i> <span id="formTitle">Create Spare Part</span></h5>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="action" value="create_spare" id="formAction">
                        <input type="hidden" name="id" id="spareId">
                        
                        <div class="mb-3">
                            <label for="part_name" class="form-label">Part Name *</label>
                            <input type="text" class="form-control" id="part_name" name="part_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="part_code" class="form-label">Part Code</label>
                            <input type="text" class="form-control" id="part_code" name="part_code">
                        </div>
                        
                        <div class="mb-3">
                            <label for="price" class="form-label">Price (₹)</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0">
                        </div>
                        
                        <div class="mb-3">
                            <label for="machine_name" class="form-label">Associated Machine</label>
                            <input type="text" class="form-control" id="machine_name" name="machine_name" autocomplete="off" placeholder="Type to search for a machine...">
                            <input type="hidden" id="machine_id" name="machine_id">
                            <small class="text-muted">Optional - link this spare to a specific machine</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-success" id="saveBtn"><i class="bi bi-plus-circle"></i> Save Spare Part</button>
                            <button type="submit" class="btn btn-primary" id="updateBtn" style="display:none;"><i class="bi bi-check"></i> Update</button>
                            <button type="button" class="btn btn-warning" id="editBtn" style="display:none;"><i class="bi bi-pencil"></i> Edit</button>
                            <button type="button" class="btn btn-danger" id="deleteBtn" style="display:none;"><i class="bi bi-trash"></i> Delete</button>
                            <button type="button" class="btn btn-secondary" id="resetBtn"><i class="bi bi-arrow-clockwise"></i> Reset Form</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Spare Parts List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="bi bi-list"></i> All Spare Parts (<?php echo $total_records; ?>)</h5>
                        <small>Page <?php echo $page; ?> of <?php echo $total_pages; ?></small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Part Name</th>
                                        <th>Part Code</th>
                                        <th>Price</th>
                                        <th>Machine</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT s.*, m.name as machine_name FROM spares s LEFT JOIN machines m ON s.machine_id = m.id $where_clause ORDER BY s.created_at DESC LIMIT $records_per_page OFFSET $offset";
                                    $result = $conn->query($sql);
                                    
                                    if ($result && $result->num_rows > 0):
                                        while ($row = $result->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['part_name']); ?></strong></td>
                                        <td>
                                            <?php if (!empty($row['part_code'])): ?>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($row['part_code']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['price'] > 0): ?>
                                                ₹<?php echo number_format($row['price'], 2); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not set</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['machine_name'])): ?>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($row['machine_name']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">General</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $row['is_active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary edit-spare" data-id="<?php echo $row['id']; ?>">
                                                <i class="bi bi-pencil"></i> View/Edit
                                            </button>
                                            <?php if (hasPermission('spares', 'delete')): ?>
                                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this spare part?')">
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
                                        <td colspan="6" class="text-center py-4">
                                            <i class="bi bi-tools display-1 text-muted"></i>
                                            <p class="mt-3">No spare parts found.</p>
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

    /* Ensure search input is always functional */
    #spareSearch {
        pointer-events: auto !important;
        background: #fff !important;
        border: 1px solid #ced4da !important;
    }
    
    #spareSearch:focus {
        border-color: #80bdff !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
        outline: 0 !important;
    }

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

<script src="js/spares.js"></script>

<?php include 'footer.php'; ?>