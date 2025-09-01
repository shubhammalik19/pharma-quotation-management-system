<?php
include '../header.php';
checkLoginAndPermission('users', 'view');
include '../menu.php';

$message = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create_role') {
            if (hasPermission('users', 'create')) {
                $name = strtolower(str_replace(' ', '_', sanitizeInput($_POST['name'])));
                $display_name = sanitizeInput($_POST['display_name']);
                $description = sanitizeInput($_POST['description']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                // Check if role exists
                $check_sql = "SELECT id FROM roles WHERE name = '$name'";
                $check_result = $conn->query($check_sql);
                
                if ($check_result->num_rows > 0) {
                    $message = showError('Role name already exists!');
                } else {
                    $sql = "INSERT INTO roles (name, display_name, description, is_active) 
                            VALUES ('$name', '$display_name', '$description', $is_active)";
                    
                    if ($conn->query($sql)) {
                        $role_id = $conn->insert_id;
                        
                        // Add permissions
                        if (isset($_POST['permissions'])) {
                            foreach ($_POST['permissions'] as $permission_id) {
                                $perm_sql = "INSERT INTO role_permissions (role_id, permission_id) 
                                            VALUES ($role_id, " . intval($permission_id) . ")";
                                $conn->query($perm_sql);
                            }
                        }
                        
                        $message = showSuccess('Role created successfully!');
                    } else {
                        $message = showError('Error creating role: ' . $conn->error);
                    }
                }
            }
        } elseif ($_POST['action'] === 'edit_role') {
            if (hasPermission('users', 'edit')) {
                $role_id = intval($_POST['role_id']);
                $display_name = sanitizeInput($_POST['display_name']);
                $description = sanitizeInput($_POST['description']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $sql = "UPDATE roles SET display_name = '$display_name', description = '$description', is_active = $is_active WHERE id = $role_id";
                
                if ($conn->query($sql)) {
                    // Delete old permissions
                    $conn->query("DELETE FROM role_permissions WHERE role_id = $role_id");
                    
                    // Add new permissions
                    if (isset($_POST['permissions'])) {
                        foreach ($_POST['permissions'] as $permission_id) {
                            $perm_sql = "INSERT INTO role_permissions (role_id, permission_id) VALUES ($role_id, " . intval($permission_id) . ")";
                            $conn->query($perm_sql);
                        }
                    }
                    
                    $message = showSuccess('Role updated successfully!');
                } else {
                    $message = showError('Error updating role: ' . $conn->error);
                }
            }
        } elseif ($_POST['action'] === 'delete_role') {
            if (hasPermission('users', 'delete')) {
                $role_id = intval($_POST['role_id']);
                
                // Check if role is used
                $check_users = "SELECT COUNT(*) as count FROM user_roles WHERE role_id = $role_id";
                $check_result = $conn->query($check_users);
                $user_count = $check_result->fetch_assoc()['count'];
                
                if ($user_count > 0) {
                    $message = showError('Cannot delete role. It is used by ' . $user_count . ' user(s).');
                } else {
                    $sql = "DELETE FROM roles WHERE id = $role_id AND name NOT IN ('super_admin', 'admin')";
                    if ($conn->query($sql)) {
                        $message = showSuccess('Role deleted successfully!');
                    } else {
                        $message = showError('Cannot delete protected role.');
                    }
                }
            }
        }
    }
}

// Get all roles
$roles_sql = "SELECT r.*, 
              (SELECT COUNT(*) FROM user_roles ur WHERE ur.role_id = r.id) as user_count,
              (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) as permission_count
              FROM roles r 
              ORDER BY r.id";
$roles_result = $conn->query($roles_sql);

// Get all permissions grouped by module
$permissions_sql = "SELECT * FROM permissions ORDER BY module, action";
$permissions_result = $conn->query($permissions_sql);

$permissions_by_page = array();
$page_icons = array(
    'dashboard' => 'bi-speedometer2',
    'customers' => 'bi-people',
    'machines' => 'bi-gear',
    'spares' => 'bi-tools',
    'price_master' => 'bi-currency-rupee',
    'quotations' => 'bi-file-text',
    'reports' => 'bi-graph-up',
    'users' => 'bi-person-gear',
    'system' => 'bi-gear-wide-connected',
    'settings' => 'bi-gear-wide',
    'sales_orders' => 'bi-cart-check',
    'purchase_orders' => 'bi-cart-plus',
    'sales_invoices' => 'bi-receipt',
    'credit_notes' => 'bi-arrow-counterclockwise',
    'debit_notes' => 'bi-arrow-clockwise'
);

while ($perm = $permissions_result->fetch_assoc()) {
    $permissions_by_page[$perm['module']][] = $perm;
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-shield-check"></i> Role Management</h2>
            <hr>
        </div>
    </div>
    
    <?php echo $message; ?>
    
    <div class="row">
        <!-- Role Form -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><span id="formTitle">Create Role</span></h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="roleForm">
                        <input type="hidden" name="action" value="create_role" id="formAction">
                        <input type="hidden" name="role_id" id="roleId">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Role Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="display_name" class="form-label">Display Name *</label>
                            <input type="text" class="form-control" id="display_name" name="display_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                        
                        <!-- Permissions -->
                        <div class="mb-3">
                            <label class="form-label">Permissions</label>
                                                    <!-- Page-wise Permissions -->
                        <div class="mb-3">
                            <label class="form-label">Page Permissions</label>
                            <small class="text-muted d-block mb-2">Select what actions users with this role can perform on each page</small>
                            
                            <div class="accordion" id="permissionAccordion">
                                <?php foreach ($permissions_by_page as $page => $permissions): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading_<?php echo $page; ?>">
                                            <button class="accordion-button collapsed" type="button" 
                                                    data-bs-toggle="collapse" data-bs-target="#collapse_<?php echo $page; ?>">
                                                <i class="<?php echo $page_icons[$page] ?? 'bi-folder'; ?> me-2"></i>
                                                <?php echo ucwords(str_replace('_', ' ', $page)); ?> Page
                                                <span class="badge bg-primary ms-2" id="count_<?php echo $page; ?>">0</span>
                                            </button>
                                        </h2>
                                        <div id="collapse_<?php echo $page; ?>" class="accordion-collapse collapse" 
                                             data-bs-parent="#permissionAccordion">
                                            <div class="accordion-body">
                                                <div class="row">
                                                    <div class="col-12 mb-2">
                                                        <button type="button" class="btn btn-outline-success btn-sm select-page-all" 
                                                                data-page="<?php echo $page; ?>">
                                                            <i class="bi bi-check-all"></i> Select All
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger btn-sm clear-page-all" 
                                                                data-page="<?php echo $page; ?>">
                                                            <i class="bi bi-x-circle"></i> Clear All
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <?php foreach ($permissions as $perm): ?>
                                                        <div class="col-md-6 mb-2">
                                                            <div class="form-check">
                                                                <input class="form-check-input permission-checkbox page-<?php echo $page; ?>" 
                                                                       type="checkbox" name="permissions[]" 
                                                                       value="<?php echo $perm['id']; ?>" 
                                                                       id="perm_<?php echo $perm['id']; ?>"
                                                                       data-page="<?php echo $page; ?>">
                                                                <label class="form-check-label" for="perm_<?php echo $perm['id']; ?>">
                                                                    <strong><?php echo ucfirst($perm['action']); ?></strong>
                                                                    <br><small class="text-muted"><?php echo $perm['description']; ?></small>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="mt-3 d-flex gap-2">
                                <button type="button" class="btn btn-success btn-sm" onclick="selectAllPermissions()">
                                    <i class="bi bi-check-all"></i> Select All Pages
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="clearAllPermissions()">
                                    <i class="bi bi-x-circle"></i> Clear All Pages
                                </button>
                            </div>
                        </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success">
                            <span id="saveText">Create Role</span>
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">Reset</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Roles List -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>System Roles</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Role</th>
                                    <th>Description</th>
                                    <th>Users</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($role = $roles_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $role['display_name']; ?></strong>
                                            <?php if (in_array($role['name'], ['super_admin', 'admin'])): ?>
                                                <span class="badge bg-warning">Protected</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $role['description'] ?: '-'; ?></td>
                                        <td><?php echo $role['user_count']; ?></td>
                                        <td>
                                            <?php if ($role['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (hasPermission('users', 'edit')): ?>
                                                <button class="btn btn-sm btn-outline-primary edit-role" 
                                                        data-role='<?php echo json_encode($role); ?>'>
                                                    Edit
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if (hasPermission('users', 'delete') && !in_array($role['name'], ['super_admin', 'admin'])): ?>
                                                <button class="btn btn-sm btn-outline-danger delete-role" 
                                                        data-id="<?php echo $role['id']; ?>"
                                                        data-name="<?php echo $role['display_name']; ?>">
                                                    Delete
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../js/roles.js"></script>

<?php include '../footer.php'; ?>
