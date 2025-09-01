<?php
require_once 'common/conn.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirm password do not match.";
    } elseif (strlen($new_password) < 4) {
        $error = "New password must be at least 4 characters long.";
    } else {
        // Get current user data
        $user_id = $_SESSION['user_id'];
        $query = "SELECT password FROM users WHERE id = $user_id";
        $result = mysqli_query($conn, $query);
        $user = mysqli_fetch_assoc($result);
        
        // Check current password (plain text comparison as per your requirement)
        if ($user['password'] !== $current_password) {
            $error = "Current password is incorrect.";
        } else {
            // Update password (storing as plain text as per your requirement)
            $update_query = "UPDATE users SET password = '$new_password' WHERE id = $user_id";
            if (mysqli_query($conn, $update_query)) {
                $message = "Password changed successfully!";
            } else {
                $error = "Error changing password: " . mysqli_error($conn);
            }
        }
    }
}

require_once 'header.php';
require_once 'menu.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-key text-primary"></i> Change Password</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="bi bi-shield-lock"></i> Change Your Password</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">
                            <i class="bi bi-lock"></i> Current Password
                        </label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">
                            <i class="bi bi-key-fill"></i> New Password
                        </label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="form-text">Password must be at least 4 characters long.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">
                            <i class="bi bi-key-fill"></i> Confirm New Password
                        </label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Change Password
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0"><i class="bi bi-info-circle"></i> Password Guidelines</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="bi bi-check text-success"></i> Use at least 4 characters</li>
                    <li class="mb-2"><i class="bi bi-check text-success"></i> Include letters and numbers</li>
                    <li class="mb-2"><i class="bi bi-check text-success"></i> Avoid common passwords</li>
                    <li class="mb-2"><i class="bi bi-check text-success"></i> Keep it memorable but secure</li>
                </ul>
                
                <div class="alert alert-warning mt-3">
                    <small><i class="bi bi-exclamation-triangle"></i> Remember to keep your password safe and don't share it with others.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
