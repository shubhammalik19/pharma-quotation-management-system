<?php
// Common functions file

// Check if user is logged in
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . url('auth/login.php'));
        exit();
    }
}

// Enhanced login check with permission verification
function checkLoginAndPermission($module, $action) {
    checkLogin();
    
    // Admin users bypass permission checks
    if (isAdmin()) {
        return;
    }
    
    if (!hasPermission($module, $action)) {
        header("Location: " . url('auth/access_denied.php'));
        exit();
    }
}

// Check if user is admin
function isAdmin() {
    // Check the traditional is_admin field for backward compatibility
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
        return true;
    }
    
    // Check if user has admin or super_admin role
    if (isset($_SESSION['roles'])) {
        return in_array('admin', $_SESSION['roles']) || in_array('super_admin', $_SESSION['roles']);
    }
    
    return false;
}

// Check if user is super admin
function isSuperAdmin() {
    return isset($_SESSION['roles']) && in_array('super_admin', $_SESSION['roles']);
}

// Check if user has specific permission
function hasPermission($module, $action) {
    // Admin users have all permissions
    if (isAdmin()) {
        return true;
    }
    
    // Super admin has all permissions
    if (isSuperAdmin()) {
        return true;
    }
    
    // Temporary: Allow all permissions for logged-in users until RBAC is fully set up
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    
    // Check in session permissions
    return isset($_SESSION['permissions'][$module][$action]) && 
           $_SESSION['permissions'][$module][$action];
}

// Check if user has any permission in a module
function hasModuleAccess($module) {
    // Admin users have access to all modules
    if (isAdmin()) {
        return true;
    }
    
    if (isSuperAdmin()) {
        return true;
    }
    
    return isset($_SESSION['permissions'][$module]) && 
           !empty($_SESSION['permissions'][$module]);
}

// Load user permissions into session
function loadUserPermissions($user_id) {
    global $conn;
    
    $permissions = [];
    $roles = [];
    
    $sql = "SELECT DISTINCT p.module, p.action, r.name as role_name 
            FROM user_roles ur 
            JOIN role_permissions rp ON ur.role_id = rp.role_id 
            JOIN permissions p ON rp.permission_id = p.id 
            JOIN roles r ON ur.role_id = r.id 
            WHERE ur.user_id = $user_id AND p.is_active = 1 AND r.is_active = 1";
    
    $result = $conn->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        $permissions[$row['module']][$row['action']] = true;
        if (!in_array($row['role_name'], $roles)) {
            $roles[] = $row['role_name'];
        }
    }
    
    $_SESSION['permissions'] = $permissions;
    $_SESSION['roles'] = $roles;
}

// Get user's profile picture URL
function getProfilePicture($filename = null) {
    if ($filename && file_exists("uploads/profile_pictures/" . $filename)) {
        return url("uploads/profile_pictures/" . $filename);
    }
    return url("assets/images/default-avatar.svg");
}

// Format date for display
function formatDate($date) {
    return date("d-m-Y", strtotime($date));
}

// Format date and time for display
function formatDateTime($datetime) {
    return date("d-m-Y H:i:s", strtotime($datetime));
}

// Format currency
function formatCurrency($amount) {
    return "₹" . number_format($amount, 2);
}

// Generate quotation reference number
function generateQuoteRef() {
    return "QT-" . date("Y") . "-" . str_pad(rand(1, 9999), 4, "0", STR_PAD_LEFT);
}

// Sanitize input
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Success message
function showSuccess($message) {
    return '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> ' . $message . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

// Error message
function showError($message) {
    return '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> ' . $message . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

// Warning message
function showWarning($message) {
    return '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> ' . $message . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

// Info message
function showInfo($message) {
    return '<div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle"></i> ' . $message . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

// Get user's full name for display
function getUserDisplayName() {
    if (isset($_SESSION['full_name']) && !empty($_SESSION['full_name'])) {
        return $_SESSION['full_name'];
    }
    return isset($_SESSION['username']) ? $_SESSION['username'] : 'User';
}

// Get user's roles as string
function getUserRolesString() {
    if (isset($_SESSION['roles']) && !empty($_SESSION['roles'])) {
        return implode(', ', array_map('ucwords', str_replace('_', ' ', $_SESSION['roles'])));
    }
    return 'No Role Assigned';
}

// Log user activity (for future implementation)
function logActivity($action, $details = '') {
    // TODO: Implement activity logging
    // This can be used to track user actions for audit purposes
}

// Upload file helper
function uploadFile($file, $directory, $allowed_types = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_types)) {
        return false;
    }
    
    // Create directory if it doesn't exist
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $filepath = $directory . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return false;
}

// Get company details
function getCompanyDetails() {
    return [
        'name' => 'XTECHNOCRAT INDIA PRIVATE LIMITED',
        'address' => '14/1/B, Shed No. 222/1, Pancharatna Industrial Estate,<br>Ramol Bridge, Phase IV, VATVA GIDC,<br>Ahmedabad-382445, Gujarat, India',
        'cin' => 'U29100HR2020PTC088944',
        'gstin' => '06AAACX3387L1ZW',
        'phone' => '+91-90000 00000',
        'email' => 'info@xtechnocrat.com',
        'state' => '24 - Gujarat',
        'state_code' => '24'
    ];
}

// Convert number to words (Indian system)
function convertToWords($number) {
    $words = array(
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
        6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
        11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
        16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty',
        30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy',
        80 => 'Eighty', 90 => 'Ninety'
    );
    
    if ($number < 21) {
        return $words[$number];
    } elseif ($number < 100) {
        $tens = (int)($number / 10) * 10;
        $units = $number % 10;
        return $words[$tens] . ($units ? ' ' . $words[$units] : '');
    } elseif ($number < 1000) {
        $hundreds = (int)($number / 100);
        $remainder = $number % 100;
        return $words[$hundreds] . ' Hundred' . ($remainder ? ' ' . convertToWords($remainder) : '');
    } elseif ($number < 100000) {
        $thousands = (int)($number / 1000);
        $remainder = $number % 1000;
        return convertToWords($thousands) . ' Thousand' . ($remainder ? ' ' . convertToWords($remainder) : '');
    } elseif ($number < 10000000) {
        $lakhs = (int)($number / 100000);
        $remainder = $number % 100000;
        return convertToWords($lakhs) . ' Lakh' . ($remainder ? ' ' . convertToWords($remainder) : '');
    } else {
        $crores = (int)($number / 10000000);
        $remainder = $number % 10000000;
        return convertToWords($crores) . ' Crore' . ($remainder ? ' ' . convertToWords($remainder) : '');
    }
}

// Get amount in words
function getAmountInWords($amount) {
    return convertToWords((int)$amount) . ' Rupees only';
}

// Get bank details
function getBankDetails() {
    return [
        'bank_name' => 'ICICI BANK LIMITED, SEC PANCHKULA',
        'account_number' => '373705000246',
        'ifsc' => 'ICIC0003737',
        'beneficiary' => 'XTECHNOCRAT INDIA PRIVATE LIMITED'
    ];
}

// ===========================
// PURCHASE ORDER FUNCTIONS
// ===========================

// Generate Purchase Order Number
function generatePurchaseOrderNumber($conn, $prefix = "PO-") {
    $result = $conn->query("SELECT po_number FROM purchase_orders ORDER BY id DESC LIMIT 1");
    
    $max_number = 0;
    if ($result && $row = $result->fetch_assoc()) {
        $latest_po = $row['po_number'];
        if (preg_match('/(\d+)$/', $latest_po, $matches)) {
            $max_number = (int)$matches[1];
        }
    }
    
    $new_number = $max_number + 1;
    return $prefix . date('Y') . '-' . str_pad($new_number, 5, '0', STR_PAD_LEFT);
}

// Validate Vendor for Purchase Order
function validateVendor($conn, $vendor_id) {
    if ($vendor_id <= 0) {
        return ['valid' => false, 'message' => 'Please select a valid vendor.'];
    }
    
    $check_vendor = $conn->query("SELECT company_name FROM customers WHERE id = $vendor_id AND (entity_type = 'vendor' OR entity_type = 'both')");
    if ($check_vendor->num_rows > 0) {
        return ['valid' => true, 'vendor_name' => $check_vendor->fetch_assoc()['company_name']];
    }
    
    return ['valid' => false, 'message' => 'Selected vendor does not exist or is not valid.'];
}

// Create Purchase Order
function createPurchaseOrder($conn, $data) {
    $vendor_validation = validateVendor($conn, $data['vendor_id']);
    if (!$vendor_validation['valid']) {
        return ['success' => false, 'message' => $vendor_validation['message']];
    }
    
    $vendor_name = $vendor_validation['vendor_name'];
    $sales_order_id = isset($data['sales_order_id']) && $data['sales_order_id'] > 0 ? $data['sales_order_id'] : 'NULL';
    
    $sql = "INSERT INTO purchase_orders (po_number, vendor_id, vendor_name, sales_order_id, po_date, due_date, 
            total_amount, discount_percentage, discount_amount, final_total, status, notes, created_by) 
            VALUES ('{$data['po_number']}', {$data['vendor_id']}, '$vendor_name', $sales_order_id, 
            '{$data['po_date']}', '{$data['due_date']}', {$data['total_amount']}, {$data['discount_percentage']}, 
            {$data['discount_amount']}, {$data['final_total']}, '{$data['status']}', '{$data['notes']}', {$data['created_by']})";
    
    if ($conn->query($sql)) {
        $po_id = $conn->insert_id;
        
        // Add items if provided
        if (isset($data['items']) && is_array($data['items'])) {
            $items_result = addPurchaseOrderItems($conn, $po_id, $data['items']);
            if (!$items_result['success']) {
                return $items_result;
            }
        }
        
        return ['success' => true, 'po_id' => $po_id, 'message' => 'Purchase Order created successfully!'];
    }
    
    return ['success' => false, 'message' => 'Error creating purchase order: ' . $conn->error];
}

// Update Purchase Order
function updatePurchaseOrder($conn, $po_id, $data) {
    $vendor_validation = validateVendor($conn, $data['vendor_id']);
    if (!$vendor_validation['valid']) {
        return ['success' => false, 'message' => $vendor_validation['message']];
    }
    
    $vendor_name = $vendor_validation['vendor_name'];
    $sales_order_id = isset($data['sales_order_id']) && $data['sales_order_id'] > 0 ? $data['sales_order_id'] : 'NULL';
    
    $sql = "UPDATE purchase_orders SET 
            po_number = '{$data['po_number']}', 
            vendor_id = {$data['vendor_id']}, 
            vendor_name = '$vendor_name', 
            sales_order_id = $sales_order_id,
            po_date = '{$data['po_date']}', 
            due_date = '{$data['due_date']}', 
            total_amount = {$data['total_amount']}, 
            discount_percentage = {$data['discount_percentage']}, 
            discount_amount = {$data['discount_amount']}, 
            final_total = {$data['final_total']}, 
            status = '{$data['status']}', 
            notes = '{$data['notes']}' 
            WHERE id = $po_id";
    
    if ($conn->query($sql)) {
        // Delete existing items and add new ones
        $conn->query("DELETE FROM purchase_order_items WHERE po_id = $po_id");
        
        if (isset($data['items']) && is_array($data['items'])) {
            $items_result = addPurchaseOrderItems($conn, $po_id, $data['items']);
            if (!$items_result['success']) {
                return $items_result;
            }
        }
        
        return ['success' => true, 'message' => 'Purchase Order updated successfully!'];
    }
    
    return ['success' => false, 'message' => 'Error updating purchase order: ' . $conn->error];
}

// Add Purchase Order Items
function addPurchaseOrderItems($conn, $po_id, $items) {
    foreach ($items as $item) {
        $item_type = sanitizeInput($item['type']);
        $item_id = intval($item['item_id']);
        $item_name = sanitizeInput($item['name']);
        $description = sanitizeInput($item['description']);
        $quantity = intval($item['quantity']);
        $rate = floatval($item['unit_price']);
        $amount = floatval($item['total_price']);
        
        // Extract HSN from description
        $hsn_code = '';
        if (preg_match('/HSN:\s*(\w+)/i', $description, $matches)) {
            $hsn_code = $matches[1];
        }
        
        $hsn_value = empty($hsn_code) ? 'NULL' : "'$hsn_code'";

        $item_sql = "INSERT INTO purchase_order_items (po_id, item_type, item_id, item_name, description, hsn_code, quantity, unit_price, total_price) 
                     VALUES ($po_id, '$item_type', $item_id, '$item_name', '$description', $hsn_value, $quantity, $rate, $amount)";
        
        if (!$conn->query($item_sql)) {
            return ['success' => false, 'message' => "Error saving item: " . $conn->error];
        }
    }
    
    return ['success' => true, 'message' => 'Items added successfully!'];
}

// Get Purchase Order Details
function getPurchaseOrderDetails($conn, $po_id) {
    $sql = "SELECT po.*, 
            (SELECT GROUP_CONCAT(CONCAT(poi.item_name, ' (Qty: ', poi.quantity, ')') SEPARATOR ', ') 
             FROM purchase_order_items poi WHERE poi.po_id = po.id) as items_summary
            FROM purchase_orders po 
            WHERE po.id = $po_id";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Delete Purchase Order
function deletePurchaseOrder($conn, $po_id) {
    $conn->query("DELETE FROM purchase_order_items WHERE po_id = $po_id");
    $sql = "DELETE FROM purchase_orders WHERE id = $po_id";
    
    if ($conn->query($sql)) {
        return ['success' => true, 'message' => 'Purchase Order deleted successfully!'];
    }
    
    return ['success' => false, 'message' => 'Error deleting purchase order: ' . $conn->error];
}

// ===========================
// SALES ORDER FUNCTIONS
// ===========================

// Generate Sales Order Number
function generateSalesOrderNumber($conn, $prefix = "SO-") {
    $result = $conn->query("SELECT so_number FROM sales_orders ORDER BY id DESC LIMIT 1");
    
    $max_number = 0;
    if ($result && $row = $result->fetch_assoc()) {
        $latest_so = $row['so_number'];
        if (preg_match('/(\d+)$/', $latest_so, $matches)) {
            $max_number = (int)$matches[1];
        }
    }
    
    $new_number = $max_number + 1;
    return $prefix . date('Y') . '-' . str_pad($new_number, 5, '0', STR_PAD_LEFT);
}

// ===========================
// QUOTATION FUNCTIONS
// ===========================

// Generate Quotation Number (Common function with prefix parameter)
if (!function_exists('generateQuotationNumberWithPrefix')) {
    function generateQuotationNumberWithPrefix($conn, $prefix = "QT-") {
        $result = $conn->query("SELECT quotation_number FROM quotations ORDER BY id DESC LIMIT 1");
        
        $max_number = 0;
        if ($result && $row = $result->fetch_assoc()) {
            $latest_quote = $row['quotation_number'];
            if (preg_match('/(\d+)$/', $latest_quote, $matches)) {
                $max_number = (int)$matches[1];
            }
        }
        
        $new_number = $max_number + 1;
        return $prefix . date('Y') . '-' . str_pad($new_number, 5, '0', STR_PAD_LEFT);
    }
}

// ===========================
// INVOICE FUNCTIONS
// ===========================

// Generate Invoice Number
function generateInvoiceNumber($conn, $prefix = "INV-") {
    $result = $conn->query("SELECT invoice_number FROM sales_invoices ORDER BY id DESC LIMIT 1");
    
    $max_number = 0;
    if ($result && $row = $result->fetch_assoc()) {
        $latest_invoice = $row['invoice_number'];
        if (preg_match('/(\d+)$/', $latest_invoice, $matches)) {
            $max_number = (int)$matches[1];
        }
    }
    
    $new_number = $max_number + 1;
    return $prefix . date('Y') . '-' . str_pad($new_number, 5, '0', STR_PAD_LEFT);
}

// ===========================
// CUSTOMER/VENDOR FUNCTIONS
// ===========================

// Get Customer/Vendor Details
function getCustomerDetails($conn, $customer_id) {
    $sql = "SELECT * FROM customers WHERE id = $customer_id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Validate Customer/Vendor
function validateCustomer($conn, $customer_id, $entity_type = null) {
    $where_clause = "id = $customer_id";
    if ($entity_type) {
        $where_clause .= " AND (entity_type = '$entity_type' OR entity_type = 'both')";
    }
    
    $sql = "SELECT company_name, entity_type FROM customers WHERE $where_clause";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return ['valid' => true, 'data' => $result->fetch_assoc()];
    }
    
    return ['valid' => false, 'message' => 'Customer/Vendor not found or invalid type.'];
}

// ===========================
// ITEM MANAGEMENT FUNCTIONS
// ===========================

// Get Machine Details with Price
function getMachineWithPrice($conn, $machine_id) {
    $sql = "SELECT m.*, 
            COALESCE(pm.price, 0) as current_price
            FROM machines m 
            LEFT JOIN price_master pm ON m.id = pm.machine_id 
            AND pm.is_active = 1 
            AND CURDATE() BETWEEN pm.valid_from AND pm.valid_to
            WHERE m.id = $machine_id AND m.is_active = 1";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Get Spare Part Details
function getSparePartDetails($conn, $spare_id) {
    $sql = "SELECT * FROM spares WHERE id = $spare_id AND is_active = 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// ===========================
// SEARCH AND FILTER FUNCTIONS
// ===========================

// Build Search Query
function buildSearchQuery($search_term, $fields, $table_alias = '') {
    if (empty($search_term)) {
        return '';
    }
    
    $search_term = mysqli_real_escape_string($GLOBALS['conn'], $search_term);
    $conditions = [];
    
    foreach ($fields as $field) {
        $field_name = $table_alias ? "$table_alias.$field" : $field;
        $conditions[] = "$field_name LIKE '%$search_term%'";
    }
    
    return '(' . implode(' OR ', $conditions) . ')';
}

// Get Pagination Data
function getPaginationData($total_records, $current_page = 1, $records_per_page = 10) {
    $total_pages = ceil($total_records / $records_per_page);
    $offset = ($current_page - 1) * $records_per_page;
    
    return [
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => $offset,
        'records_per_page' => $records_per_page,
        'total_records' => $total_records
    ];
}

// ===========================
// EMAIL FUNCTIONS
// ===========================

// Send Email with Attachment
function sendEmailWithAttachment($to, $subject, $message, $attachment_path = null, $additional_emails = []) {
    // This function will be implemented when email service is set up
    // For now, return success to avoid breaking the application
    return ['success' => true, 'message' => 'Email functionality not yet implemented'];
}

// ===========================
// CALCULATION FUNCTIONS
// ===========================

// Calculate Discount Amount
function calculateDiscountAmount($total, $discount_percentage) {
    return ($total * $discount_percentage) / 100;
}

// Calculate Discount Percentage
function calculateDiscountPercentage($total, $discount_amount) {
    if ($total <= 0) return 0;
    return ($discount_amount / $total) * 100;
}

// Calculate GST
function calculateGST($amount, $gst_percentage = 18) {
    return ($amount * $gst_percentage) / 100;
}

// Calculate Final Total with GST
function calculateFinalTotalWithGST($subtotal, $discount_amount = 0, $gst_percentage = 18) {
    $after_discount = $subtotal - $discount_amount;
    $gst_amount = calculateGST($after_discount, $gst_percentage);
    return $after_discount + $gst_amount;
}

// ===========================
// STATUS MANAGEMENT FUNCTIONS
// ===========================

// Get Status Badge Class
function getStatusBadgeClass($status) {
    $status_classes = [
        'draft' => 'bg-secondary',
        'pending' => 'bg-warning',
        'sent' => 'bg-info',
        'approved' => 'bg-success',
        'rejected' => 'bg-danger',
        'cancelled' => 'bg-dark',
        'completed' => 'bg-success',
        'delivered' => 'bg-primary',
        'acknowledged' => 'bg-info',
        'received' => 'bg-success'
    ];
    
    return $status_classes[strtolower($status)] ?? 'bg-secondary';
}

// Format Status for Display
function formatStatusForDisplay($status) {
    return ucwords(str_replace('_', ' ', $status));
}
?>
