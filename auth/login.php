<?php

#die( password_hash('admin', PASSWORD_DEFAULT));
require_once '../common/conn.php';
require_once '../common/functions.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    redirect('../dashboard.php');
}

$error = '';

if ($_POST) {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        $sql = "SELECT id, username, full_name, email, password, is_admin, is_active, profile_picture FROM users WHERE username = '$username' AND is_active = 1";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password']) || ($password === $user['password'])) { // Support old plain text passwords temporarily
                // Update last login
                $update_sql = "UPDATE users SET last_login = NOW() WHERE id = " . $user['id'];
                $conn->query($update_sql);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['is_admin'] = $user['is_admin'];
                $_SESSION['profile_picture'] = $user['profile_picture'];
                
                // Load user permissions
                loadUserPermissions($user['id']);
                
                redirect('../dashboard.php');
            } else {
                $error = "Invalid password!";
            }
        } else {
            $error = "Username not found or account is disabled!";
        }
    } else {
        $error = "Please fill all fields!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Login</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 2rem 2rem 1rem;
            text-align: center;
        }
        .login-header h2 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .login-header p {
            opacity: 0.9;
            margin: 0;
            font-size: 0.9rem;
        }
        .login-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .btn-login {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }
        .error-alert {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 1rem;
        }
        .default-creds {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 12px;
            padding: 16px;
            margin-top: 1rem;
            text-align: center;
        }
        .system-info {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="mb-3">
                    <i class="bi bi-clipboard-data" style="font-size: 3rem;"></i>
                </div>
                <h2>Welcome Back</h2>
                <p>Sign in to your account</p>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="error-alert">
                        <i class="bi bi-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="loginForm">
                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold">
                            <i class="bi bi-person"></i> Username
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username" 
                               placeholder="Enter your username"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                               required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold">
                            <i class="bi bi-lock"></i> Password
                        </label>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               placeholder="Enter your password"
                               required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login w-100">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Sign In
                    </button>
                </form>
                
                <div class="default-creds">
                    <small class="text-muted">
                        <strong>Default Credentials:</strong><br>
                        Username: <code>admin</code><br>
                        Password: <code>admin</code>
                    </small>
                </div>
                
                <div class="text-center mt-3">
                    <small>
                        <a href="<?php echo url('setup_database.php'); ?>" class="text-decoration-none">
                            <i class="bi bi-database"></i> Setup Database
                        </a>
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="system-info">
        <div>
            <strong><?php echo SITE_NAME; ?></strong><br>
            Version <?php echo VERSION; ?> | © 2025 Pharma Systems
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Focus on username field when page loads
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
        
        // Add loading state to login button
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Signing in...';
            btn.disabled = true;
        });
    </script>
</body>
</html>
