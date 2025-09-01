<?php



echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>";

echo "<h1>Complete System Status Check</h1>";

try {
    include 'common/conn.php';
    include 'common/functions.php';
    
    echo "<h2>1. Database Connection</h2>";
    if ($conn->ping()) {
        echo "<p class='success'>✓ Database connection successful</p>";
    } else {
        echo "<p class='error'>✗ Database connection failed</p>";
        exit;
    }
    
    echo "<h2>2. RBAC Tables Status</h2>";
    $tables = ['roles', 'permissions', 'role_permissions', 'user_roles'];
    $all_tables_exist = true;
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "<p class='success'>✓ Table '$table' exists</p>";
            
            // Check record count
            $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
            $count = $count_result->fetch_assoc()['count'];
            echo "<p>&nbsp;&nbsp;&nbsp;└─ $count records</p>";
        } else {
            echo "<p class='error'>✗ Table '$table' does NOT exist</p>";
            $all_tables_exist = false;
        }
    }
    
    echo "<h2>3. Users Table Status</h2>";
    $result = $conn->query("DESCRIBE users");
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Status</th></tr>";
    $required_fields = ['full_name', 'email', 'profile_picture', 'is_admin', 'is_active'];
    $existing_fields = [];
    
    while ($row = $result->fetch_assoc()) {
        $existing_fields[] = $row['Field'];
        $status = in_array($row['Field'], $required_fields) ? 'RBAC Field' : 'Original';
        echo "<tr><td>" . $row['Field'] . "</td><td>" . $row['Type'] . "</td><td>$status</td></tr>";
    }
    echo "</table>";
    
    foreach ($required_fields as $field) {
        if (!in_array($field, $existing_fields)) {
            echo "<p class='error'>✗ Missing required field: $field</p>";
        }
    }
    
    echo "<h2>4. Sample Data Check</h2>";
    
    // Check roles
    $result = $conn->query("SELECT id, name, display_name FROM roles ORDER BY id");
    echo "<h3>Roles:</h3>";
    if ($result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Display Name</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>" . $row['id'] . "</td><td>" . $row['name'] . "</td><td>" . $row['display_name'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>✗ No roles found</p>";
    }
    
    // Check permissions for users module
    $result = $conn->query("SELECT name, display_name, module, action FROM permissions WHERE module = 'users' ORDER BY id");
    echo "<h3>Users Module Permissions:</h3>";
    if ($result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>Name</th><th>Display Name</th><th>Module</th><th>Action</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>" . $row['name'] . "</td><td>" . $row['display_name'] . "</td><td>" . $row['module'] . "</td><td>" . $row['action'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>✗ No users permissions found</p>";
    }
    
    // Check users
    $result = $conn->query("SELECT id, username, full_name, is_admin, is_active FROM users ORDER BY id");
    echo "<h3>Users:</h3>";
    if ($result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Is Admin</th><th>Is Active</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>" . $row['id'] . "</td><td>" . $row['username'] . "</td><td>" . $row['full_name'] . "</td><td>" . ($row['is_admin'] ? 'Yes' : 'No') . "</td><td>" . ($row['is_active'] ? 'Yes' : 'No') . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>✗ No users found</p>";
    }
    
    echo "<h2>5. Permission Assignment Check</h2>";
    
    // Check role permissions for Super Admin (role ID 1)
    $result = $conn->query("
        SELECT r.name as role_name, p.name as permission_name, p.module, p.action 
        FROM role_permissions rp 
        JOIN roles r ON rp.role_id = r.id 
        JOIN permissions p ON rp.permission_id = p.id 
        WHERE r.id = 1 AND p.module = 'users'
        ORDER BY p.name
    ");
    
    echo "<h3>Super Admin permissions for 'users' module:</h3>";
    if ($result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>Role</th><th>Permission</th><th>Module</th><th>Action</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>" . $row['role_name'] . "</td><td>" . $row['permission_name'] . "</td><td>" . $row['module'] . "</td><td>" . $row['action'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>✗ No permissions found for Super Admin on users module</p>";
    }
    
    echo "<h2>6. User Role Assignment Check</h2>";
    
    // Check user role assignments
    $result = $conn->query("
        SELECT u.username, r.name as role_name, r.display_name 
        FROM user_roles ur 
        JOIN users u ON ur.user_id = u.id 
        JOIN roles r ON ur.role_id = r.id 
        ORDER BY u.username
    ");
    
    if ($result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>Username</th><th>Role</th><th>Role Display Name</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>" . $row['username'] . "</td><td>" . $row['role_name'] . "</td><td>" . $row['display_name'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>✗ No user role assignments found</p>";
    }
    
    echo "<h2>7. File Permission Check</h2>";
    
    $files_to_check = [
        'price_master.php' => 'price_master',
        'spares.php' => 'spares',
        'auth/users.php' => 'users',
        'customers.php' => 'customers',
        'machines.php' => 'machines',
        'reports.php' => 'reports',
        'dashboard.php' => 'dashboard'
    ];
    
    foreach ($files_to_check as $file => $module) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if (strpos($content, "checkLoginAndPermission('$module', 'view')") !== false) {
                echo "<p class='success'>✓ $file has correct permission check</p>";
            } else {
                echo "<p class='error'>✗ $file missing or incorrect permission check</p>";
            }
        } else {
            echo "<p class='error'>✗ $file does not exist</p>";
        }
    }
    
    echo "<h2>8. Session Test</h2>";
    session_start();
    if (isset($_SESSION['user_id'])) {
        echo "<p class='success'>✓ Active session found</p>";
        echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
        echo "<p>Username: " . ($_SESSION['username'] ?? 'Not set') . "</p>";
        echo "<p>Is Admin: " . ($_SESSION['is_admin'] ?? 'Not set') . "</p>";
    } else {
        echo "<p class='warning'>⚠ No active session (user not logged in)</p>";
    }
    
    echo "<h2>9. Quick Actions</h2>";
    echo "<p><a href='auth/login.php' style='background: #007bff; color: white; padding: 10px; text-decoration: none; border-radius: 4px;'>Go to Login</a></p>";
    echo "<p><a href='login_test.php' style='background: #28a745; color: white; padding: 10px; text-decoration: none; border-radius: 4px;'>Auto Login as Admin</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>Fatal Error: " . $e->getMessage() . "</p>";
}
?>
