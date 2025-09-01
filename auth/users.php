<?php


include '../header.php';
checkLogin();
include '../menu.php';

$message = '';

// Fetch active roles from database
$roles_query = "SELECT id, name, display_name FROM roles WHERE is_active = 1 ORDER BY display_name";
$roles_result = $conn->query($roles_query);
$available_roles = [];
if ($roles_result && $roles_result->num_rows > 0) {
    while ($role = $roles_result->fetch_assoc()) {
        $available_roles[$role['id']] = [
            'name' => $role['name'],
            'display_name' => $role['display_name']
        ];
    }
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create_user' && hasPermission('users', 'create')) {
            $username = sanitizeInput($_POST['username']);
            $full_name = sanitizeInput($_POST['full_name']);
            $email = sanitizeInput($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $is_admin = isset($_POST['is_admin']) ? 1 : 0;
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $role_id = intval($_POST['user_role']);
            
            // Handle profile picture upload
            $profile_picture = null;
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                $upload_dir = '../uploads/profile_pictures/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $profile_picture = time() . '_' . $_FILES['profile_picture']['name'];
                    move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $profile_picture);
                }
            }
            
            // Check if username exists
            $check_sql = "SELECT id FROM users WHERE username = '$username'";
            $check_result = $conn->query($check_sql);
            
            if ($check_result->num_rows > 0) {
                $message = showError('Username already exists!');
            } else {
                $sql = "INSERT INTO users (username, full_name, email, password, profile_picture, is_admin, is_active) 
                        VALUES ('$username', '$full_name', '$email', '$password', " . ($profile_picture ? "'$profile_picture'" : 'NULL') . ", $is_admin, $is_active)";
                
                if ($conn->query($sql)) {
                    $user_id = $conn->insert_id;
                    
                    // Assign role
                    if ($role_id && isset($available_roles[$role_id])) {
                        $user_role_sql = "INSERT INTO user_roles (user_id, role_id, assigned_by) 
                                         VALUES ($user_id, $role_id, {$_SESSION['user_id']})";
                        $conn->query($user_role_sql);
                    }
                    
                    $message = showSuccess('User created successfully!');
                } else {
                    $message = showError('Error creating user: ' . $conn->error);
                }
            }
        } elseif ($_POST['action'] === 'update_user' && hasPermission('users', 'edit')) {
            $user_id = intval($_POST['id']);
            $username = sanitizeInput($_POST['username']);
            $full_name = sanitizeInput($_POST['full_name']);
            $email = sanitizeInput($_POST['email']);
            $is_admin = isset($_POST['is_admin']) ? 1 : 0;
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $role_id = intval($_POST['user_role']);
            
            // Handle profile picture
            $profile_picture_sql = "";
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                $upload_dir = '../uploads/profile_pictures/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $profile_picture = time() . '_' . $_FILES['profile_picture']['name'];
                    move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $profile_picture);
                    $profile_picture_sql = ", profile_picture = '$profile_picture'";
                }
            }
            
            // Handle password
            $password_sql = "";
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $password_sql = ", password = '$password'";
            }
            
            $sql = "UPDATE users SET 
                    username = '$username', 
                    full_name = '$full_name', 
                    email = '$email', 
                    is_admin = $is_admin, 
                    is_active = $is_active
                    $profile_picture_sql
                    $password_sql
                    WHERE id = $user_id";
            
            if ($conn->query($sql)) {
                // Update role
                if ($role_id && isset($available_roles[$role_id])) {
                    $conn->query("DELETE FROM user_roles WHERE user_id = $user_id");
                    
                    $user_role_sql = "INSERT INTO user_roles (user_id, role_id, assigned_by) 
                                     VALUES ($user_id, $role_id, {$_SESSION['user_id']})";
                    $conn->query($user_role_sql);
                }
                
                $message = showSuccess('User updated successfully!');
            } else {
                $message = showError('Error updating user: ' . $conn->error);
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    if (!hasPermission('users', 'delete')) {
        $message = showError("You don't have permission to delete users!");
    } else {
        $id = (int)$_GET['delete'];
        
        if ($id == $_SESSION['user_id']) {
            $message = showError('You cannot delete your own account.');
        } else {
            $conn->query("DELETE FROM user_roles WHERE user_id = $id");
            $sql = "DELETE FROM users WHERE id = $id";
            if ($conn->query($sql)) {
                $message = showSuccess("User deleted successfully!");
            } else {
                $message = showError("Error deleting user: " . $conn->error);
            }
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
    $where_clause = "WHERE u.username LIKE '%$search%' OR u.full_name LIKE '%$search%' OR u.email LIKE '%$search%'";
}

// Count total records
$count_sql = "SELECT COUNT(*) as total FROM users u $where_clause";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-people"></i> User Management</h2>
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
                        <label for="userSearch" class="form-label">
                            <i class="bi bi-search"></i> Search Users
                        </label>
                        <input type="text" class="form-control" id="userSearch" 
                               placeholder="Search by username, name or email..."
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
        <form method="POST" id="userForm" enctype="multipart/form-data" class="row">
            <!-- User Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-person-plus"></i> <span id="formTitle">Create User</span></h5>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="action" value="create_user" id="formAction">
                        <input type="hidden" name="id" id="userId">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name">
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger" id="passwordRequired">*</span></label>
                            <input type="password" class="form-control" id="password" name="password">
                            <small class="text-muted" id="passwordHelp" style="display:none;">Leave blank to keep current password</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="user_role" class="form-label">User Role *</label>
                            <select class="form-control" id="user_role" name="user_role" required>
                                <option value="">Select Role...</option>
                                <?php foreach ($available_roles as $role_id => $role_data): ?>
                                    <option value="<?php echo $role_id; ?>"><?php echo htmlspecialchars($role_data['display_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="profile_picture" class="form-label">Profile Picture</label>
                            <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                            <small class="text-muted">JPG, JPEG, PNG, GIF (Max: 5MB)</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_admin" name="is_admin">
                                    <label class="form-check-label" for="is_admin">Administrator</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-success" id="saveBtn"><i class="bi bi-plus-circle"></i> Save User</button>
                            <button type="submit" class="btn btn-primary" id="updateBtn" style="display:none;"><i class="bi bi-check"></i> Update</button>
                            <button type="button" class="btn btn-warning" id="editBtn" style="display:none;"><i class="bi bi-pencil"></i> Edit</button>
                            <button type="button" class="btn btn-danger" id="deleteBtn" style="display:none;"><i class="bi bi-trash"></i> Delete</button>
                            <button type="button" class="btn btn-secondary" id="resetBtn"><i class="bi bi-arrow-clockwise"></i> Reset Form</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Users List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="bi bi-list"></i> All Users (<?php echo $total_records; ?>)</h5>
                        <small>Page <?php echo $page; ?> of <?php echo $total_pages; ?></small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Avatar</th>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT u.*, r.id as role_id, r.name as role_name, r.display_name as role_display 
                                            FROM users u 
                                            LEFT JOIN user_roles ur ON u.id = ur.user_id 
                                            LEFT JOIN roles r ON ur.role_id = r.id 
                                            $where_clause
                                            ORDER BY u.created_at DESC 
                                            LIMIT $records_per_page OFFSET $offset";
                                    $result = $conn->query($sql);
                                    
                                    if ($result && $result->num_rows > 0):
                                        while ($row = $result->fetch_assoc()):
                                            $profile_pic = $row['profile_picture'] ? '../uploads/profile_pictures/' . $row['profile_picture'] : 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16"%3E%3Cpath d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/%3E%3Cpath fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/%3E%3C/svg%3E';
                                    ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo $profile_pic; ?>" alt="Avatar" 
                                                 class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['username']); ?></strong>
                                            <?php if ($row['is_admin']): ?>
                                                <i class="bi bi-shield-check text-warning ms-1" title="Administrator"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td>
                                            <?php if ($row['role_display']): ?>
                                                <span class="badge bg-info"><?php echo $row['role_display']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No Role</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $row['is_active'] ? 'success' : 'danger'; ?>">
                                                <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $row['last_login'] ? formatDate($row['last_login']) : 'Never'; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary edit-user" 
                                                    data-id="<?php echo $row['id']; ?>"
                                                    data-role-id="<?php echo $row['role_id']; ?>">
                                                <i class="bi bi-pencil"></i> View/Edit
                                            </button>
                                            <?php if (hasPermission('users', 'delete') && $row['id'] != $_SESSION['user_id']): ?>
                                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this user?')">
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
                                        <td colspan="8" class="text-center py-4">
                                            <i class="bi bi-people display-1 text-muted"></i>
                                            <p class="mt-3">No users found.</p>
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
    #userSearch {
        pointer-events: auto !important;
        background: #fff !important;
        border: 1px solid #ced4da !important;
    }
    
    #userSearch:focus {
        border-color: #80bdff !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
        outline: 0 !important;
    }
</style>

<script src="../js/users.js"></script>

<?php include '../footer.php'; ?>