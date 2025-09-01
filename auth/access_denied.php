<?php
require_once '../common/conn.php';
require_once '../common/functions.php';

// Check if user is logged in
checkLogin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Access Denied</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .access-denied-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .access-denied-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .access-denied-header {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: white;
            padding: 2rem;
        }
        .access-denied-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .access-denied-body {
            padding: 2rem;
        }
        .btn-back {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
            color: white;
        }
        .user-info {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1rem;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="access-denied-container">
        <div class="access-denied-card">
            <div class="access-denied-header">
                <div class="access-denied-icon">
                    <i class="bi bi-shield-exclamation"></i>
                </div>
                <h2>Access Denied</h2>
                <p>You don't have permission to access this resource</p>
            </div>
            <div class="access-denied-body">
                <div class="user-info">
                    <strong>Current User:</strong> <?php echo getUserDisplayName(); ?><br>
                    <strong>Role:</strong> <?php echo getUserRolesString(); ?>
                </div>
                
                <p class="text-muted">
                    You need appropriate permissions to access this page. 
                    Please contact your administrator if you believe this is an error.
                </p>
                
                <div class="mt-4">
                    <a href="<?php echo url('dashboard.php'); ?>" class="btn-back">
                        <i class="bi bi-house"></i> Back to Dashboard
                    </a>
                </div>
                
                <div class="mt-3">
                    <small class="text-muted">
                        Need help? Contact: <strong>admin@pharmamachinery.com</strong>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
