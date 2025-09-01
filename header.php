<?php
// Include common files from project root
include_once __DIR__ . '/common/conn.php';
include_once __DIR__ . '/common/functions.php';

// Generalized asset path calculation
// Count how many directories deep the current script is relative to project root
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$depth = substr_count(trim($scriptDir, '/'), '/');

// If we’re in the root (depth=0), asset path is ''
// If we’re in one subfolder (depth=1), asset path is '../'
// If we’re in two subfolders (depth=2), asset path is '../../', etc.
if (strpos($_SERVER['SCRIPT_NAME'], '/sales/') !== false || 
    strpos($_SERVER['SCRIPT_NAME'], '/auth/') !== false || 
    strpos($_SERVER['SCRIPT_NAME'], '/quotations/') !== false ||
    strpos($_SERVER['SCRIPT_NAME'], '/email/') !== false ||
    strpos($_SERVER['SCRIPT_NAME'], '/docs/') !== false ||
    strpos($_SERVER['SCRIPT_NAME'], '/reports/') !== false) {
    $asset_path = '../';
} else {
    $asset_path = '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo SITE_NAME; ?> - Professional Management System</title>
<meta name="description" content="Professional Pharma Quotation Management System for streamlined business operations">
<link rel="icon" type="image/x-icon" href="<?php echo $asset_path; ?>assets/images/favicon.ico">
<!-- jQuery - Local -->
<script src="<?php echo $asset_path; ?>assets/js/jquery-3.7.1.min.js"></script>
<!-- Bootstrap CSS - Local -->
<link href="<?php echo $asset_path; ?>assets/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons - Local -->
<link href="<?php echo $asset_path; ?>assets/css/bootstrap-icons.css" rel="stylesheet">
<!-- Common CSS - Professional Design System -->
<link href="<?php echo $asset_path; ?>assets/css/common.css" rel="stylesheet">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<!-- jQuery UI CSS for Autocomplete - Local -->
<link rel="stylesheet" href="<?php echo $asset_path; ?>js/lib/jquery-ui.min.css">
<!-- jQuery UI JS for Autocomplete - Local -->
<script src="<?php echo $asset_path; ?>js/lib/jquery-ui.min.js"></script>
<!-- Common JavaScript -->
<script src="<?php echo $asset_path; ?>js/common.js"></script>
<!-- Bootstrap JS - Local -->
<script src="<?php echo $asset_path; ?>assets/js/bootstrap.bundle.min.js"></script>

<script>
// Combined document ready function
$(document).ready(function() {
    console.log('Current asset path:', '<?php echo $asset_path; ?>');
    console.log('Current script:', '<?php echo $_SERVER["SCRIPT_NAME"]; ?>');
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Check if jQuery UI is loaded
    if (typeof $.ui === 'undefined') {
        console.error('jQuery UI is not loaded!');
        console.error('Expected path: <?php echo $asset_path; ?>js/lib/jquery-ui.min.js');
    } else {
        console.log('jQuery UI loaded successfully');
        if ($.ui.autocomplete) {
            console.log('jQuery UI Autocomplete is available');
        } else {
            console.error('jQuery UI Autocomplete is not available');
        }
    }
    
    // Debug jQuery version
    console.log('jQuery version:', $.fn.jquery);
    
    // Check if jQuery UI version is available
    if ($.ui && $.ui.version) {
        console.log('jQuery UI version:', $.ui.version);
    }
    
    // Check if CSS files are loading by testing for Bootstrap classes
    if ($('body').css('margin') !== undefined) {
        console.log('✓ CSS files loaded successfully');
    } else {
        console.error('✗ CSS files failed to load');
    }
});
</script>
</head>
<body>
<!-- Top Navigation Bar -->
<nav class="navbar navbar-expand-lg top-navbar fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo $asset_path; ?>dashboard.php">
            <i class="bi bi-clipboard-data-fill"></i>
            <?php echo SITE_NAME; ?>
        </a>
        
        <!-- Mobile toggle button -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" style="color: white;">
            <i class="bi bi-list"></i>
        </button>
        
        <!-- User info and actions -->
        <div class="user-info d-none d-md-flex">
            <img src="<?php echo getProfilePicture($_SESSION['profile_picture'] ?? null); ?>" 
                 alt="Profile" class="user-avatar">
            <div class="user-details">
                <h6><?php echo getUserDisplayName(); ?></h6>
                <small><?php echo getUserRolesString(); ?></small>
            </div>
        </div>
    </div>
</nav>

<!-- Add top spacing for fixed navbar -->
<div style="height: 76px;"></div>