<?php


include '../header.php';
checkLogin();
include '../menu.php';

$prefix = "QUO-";

function generateQuotationNumber($conn)
{
    global $prefix;
    $result = $conn->query("SELECT quotation_number FROM quotations ORDER BY id DESC LIMIT 1");

    $max_number = 0;
    if ($result && $row = $result->fetch_assoc()) {
        $latest_quo = $row['quotation_number'];
        if (preg_match('/(\d+)$/', $latest_quo, $matches)) {
            $max_number = (int)$matches[1];
        }
    }

    $new_number = $max_number + 1;
    return $prefix . date('Y') . '-' . str_pad($new_number, 5, '0', STR_PAD_LEFT);
}

$initial_quotation_number = generateQuotationNumber($conn);

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create_quotation' && hasPermission('quotations', 'create')) {
            $quotation_number = sanitizeInput($_POST['quotation_number']);
            $customer_id = intval($_POST['customer_id'] ?? 0);
            $quotation_date = sanitizeInput($_POST['quotation_date']);
            $valid_until = sanitizeInput($_POST['valid_until']);
            $total_amount = floatval($_POST['total_amount'] ?? 0);
            $discount_percentage = floatval($_POST['discount_percentage'] ?? 0);
            $discount_amount = floatval($_POST['discount_amount'] ?? 0);
            $grand_total = $total_amount - $discount_amount;
            $status = sanitizeInput($_POST['status']);
            $enquiry_ref = sanitizeInput($_POST['enquiry_ref'] ?? '');
            $prepared_by = sanitizeInput($_POST['prepared_by'] ?? 'Sales Department');
            $notes = sanitizeInput($_POST['notes'] ?? '');

            // Validate customer
            $customer_name = '';
            if ($customer_id > 0) {
                $check_customer = $conn->query("SELECT company_name FROM customers WHERE id = $customer_id AND (entity_type = 'customer' OR entity_type = 'both')");
                if ($check_customer->num_rows > 0) {
                    $customer_name = $check_customer->fetch_assoc()['company_name'];
                } else {
                    redirectWithError('Selected customer does not exist or is not valid.');
                }
            } else {
                redirectWithError('Please select a valid customer.');
            }
            
            if (empty($message)) {
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Extract max_no from quotation_number for database
                    $max_no = 0;
                    if (preg_match('/(\d+)$/', $quotation_number, $matches)) {
                        $max_no = (int)$matches[1];
                    }
                    
                    $sql = "INSERT INTO quotations (prefix, max_no, quotation_number, customer_id, quotation_date, valid_until, total_amount, discount_percentage, discount_amount, grand_total, status, enquiry_ref, prepared_by, notes) 
                            VALUES ('$prefix', $max_no, '$quotation_number', $customer_id, '$quotation_date', '$valid_until', $total_amount, $discount_percentage, $discount_amount, $grand_total, '$status', '$enquiry_ref', '$prepared_by', '$notes')";
                    
                    if (!$conn->query($sql)) {
                        throw new Exception('Error creating quotation: ' . $conn->error);
                    }
                    
                    $quotation_id = $conn->insert_id;
                    
                    // Handle quotation items
                    if (isset($_POST['items']) && is_array($_POST['items'])) {
                        foreach ($_POST['items'] as $item) {
                            $item_type = sanitizeInput($item['type']);
                            $item_id = intval($item['item_id']);
                            $description = sanitizeInput($item['description']);
                            $specifications = sanitizeInput($item['specifications'] ?? '');
                            $quantity = intval($item['quantity']);
                            $rate = floatval($item['unit_price']);
                            $amount = floatval($item['total_price']);
                            $sl_no = intval($item['sl_no'] ?? 1);
                            
                            $item_sql = "INSERT INTO quotation_items (quotation_id, item_type, item_id, description, specifications, quantity, unit_price, total_price, sl_no) 
                                         VALUES ($quotation_id, '$item_type', $item_id, '$description', '$specifications', $quantity, $rate, $amount, $sl_no)";
                            
                            if (!$conn->query($item_sql)) {
                                throw new Exception("Error saving item: " . $conn->error);
                            }
                            
                            $quotation_item_id = $conn->insert_id;
                            
                            // Handle machine features if item type is machine and features are provided
                            if ($item_type === 'machine' && isset($item['features']) && is_array($item['features'])) {
                                foreach ($item['features'] as $feature) {
                                    $feature_name = sanitizeInput($feature['name']);
                                    $feature_price = floatval($feature['price']);
                                    $feature_quantity = intval($feature['quantity'] ?? 1);
                                    $feature_total = $feature_price * $feature_quantity;
                                    
                                    if ($feature_price > 0) {
                                        $feature_sql = "INSERT INTO quotation_machine_features (quotation_item_id, feature_name, price, quantity, total_price) 
                                                        VALUES ($quotation_item_id, '$feature_name', $feature_price, $feature_quantity, $feature_total)";
                                        
                                        if (!$conn->query($feature_sql)) {
                                            throw new Exception("Error saving machine feature: " . $conn->error);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    // Commit transaction
                    $conn->commit();
                    redirectWithSuccess('Quotation created successfully!');
                    
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $conn->rollback();
                    redirectWithError($e->getMessage());
                }
            } else {
                redirectWithError('Please fix the validation errors.');
            }
        } elseif ($_POST['action'] === 'update_quotation' && hasPermission('quotations', 'edit')) {
            $quotation_id = intval($_POST['id']);
            $quotation_number = sanitizeInput($_POST['quotation_number']);
            $customer_id = intval($_POST['customer_id'] ?? 0);
            $quotation_date = sanitizeInput($_POST['quotation_date']);
            $valid_until = sanitizeInput($_POST['valid_until']);
            $total_amount = floatval($_POST['total_amount'] ?? 0);
            $discount_percentage = floatval($_POST['discount_percentage'] ?? 0);
            $discount_amount = floatval($_POST['discount_amount'] ?? 0);
            $grand_total = $total_amount - $discount_amount;
            $status = sanitizeInput($_POST['status']);
            $enquiry_ref = sanitizeInput($_POST['enquiry_ref'] ?? '');
            $prepared_by = sanitizeInput($_POST['prepared_by'] ?? 'Sales Department');
            $notes = sanitizeInput($_POST['notes'] ?? '');
            
            $lockCheck = $conn->query("SELECT id FROM sales_orders WHERE quotation_id = $quotation_id LIMIT 1");
            if ($lockCheck && $lockCheck->num_rows > 0) {
                redirectWithError('This quotation has already gone into a Sales Order. Cannot edit.');
            }
            
            // Validate customer
            $customer_name = '';
            if ($customer_id > 0) {
                $check_customer = $conn->query("SELECT company_name FROM customers WHERE id = $customer_id AND (entity_type = 'customer' OR entity_type = 'both')");
                if ($check_customer->num_rows > 0) {
                    $customer_name = $check_customer->fetch_assoc()['company_name'];
                } else {
                    redirectWithError('Selected customer does not exist or is not valid.');
                }
            } else {
                redirectWithError('Please select a valid customer.');
            }
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                $sql = "UPDATE quotations SET 
                        quotation_number = '$quotation_number', 
                        customer_id = $customer_id, 
                        quotation_date = '$quotation_date', 
                        valid_until = '$valid_until', 
                        total_amount = $total_amount, 
                        discount_percentage = $discount_percentage, 
                        discount_amount = $discount_amount, 
                        grand_total = $grand_total, 
                        status = '$status', 
                        enquiry_ref = '$enquiry_ref', 
                        prepared_by = '$prepared_by', 
                        notes = '$notes' 
                        WHERE id = $quotation_id";
                
                if (!$conn->query($sql)) {
                    throw new Exception('Error updating quotation: ' . $conn->error);
                }
                
                // Delete existing items and insert new ones
                if (!$conn->query("DELETE FROM quotation_items WHERE quotation_id = $quotation_id")) {
                    throw new Exception('Error deleting existing items: ' . $conn->error);
                }
                
                if (isset($_POST['items']) && is_array($_POST['items'])) {
                    foreach ($_POST['items'] as $item) {
                        $item_type = sanitizeInput($item['type']);
                        $item_id = intval($item['item_id']);
                        $description = sanitizeInput($item['description']);
                        $specifications = sanitizeInput($item['specifications'] ?? '');
                        $quantity = intval($item['quantity']);
                        $rate = floatval($item['unit_price']);
                        $amount = floatval($item['total_price']);
                        $sl_no = intval($item['sl_no'] ?? 1);
                        
                        $item_sql = "INSERT INTO quotation_items (quotation_id, item_type, item_id, description, specifications, quantity, unit_price, total_price, sl_no) 
                                     VALUES ($quotation_id, '$item_type', $item_id, '$description', '$specifications', $quantity, $rate, $amount, $sl_no)";
                        
                        if (!$conn->query($item_sql)) {
                            throw new Exception("Error saving item: " . $conn->error);
                        }
                        
                        $quotation_item_id = $conn->insert_id;
                        
                        // Handle machine features if item type is machine and features are provided
                        if ($item_type === 'machine' && isset($item['features']) && is_array($item['features'])) {
                            foreach ($item['features'] as $feature) {
                                $feature_name = sanitizeInput($feature['name']);
                                $feature_price = floatval($feature['price']);
                                $feature_quantity = intval($feature['quantity'] ?? 1);
                                $feature_total = $feature_price * $feature_quantity;
                                
                                if ($feature_price > 0) {
                                    $feature_sql = "INSERT INTO quotation_machine_features (quotation_item_id, feature_name, price, quantity, total_price) 
                                                    VALUES ($quotation_item_id, '$feature_name', $feature_price, $feature_quantity, $feature_total)";
                                    
                                    if (!$conn->query($feature_sql)) {
                                        throw new Exception("Error saving machine feature: " . $conn->error);
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Commit transaction
                $conn->commit();
                redirectWithSuccess('Quotation updated successfully!');
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                redirectWithError($e->getMessage());
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    if (!hasPermission('quotations', 'delete')) {
        redirectWithError("You don't have permission to delete quotations!");
    } else {
        $id = (int)$_GET['delete'];
        
        // Check for dependencies before deletion
        $dependencies = checkQuotationDependencies($conn, $id);
        
        if (!empty($dependencies)) {
            $dependencyList = implode(', ', $dependencies);
            redirectWithError("Cannot delete quotation! This quotation is referenced in: " . $dependencyList . ". Please remove these references first.");
        }
        
        // Get quotation number for confirmation message
        $quotation_sql = "SELECT quotation_number FROM quotations WHERE id = $id";
        $quotation_result = $conn->query($quotation_sql);
        $quotation_number = '';
        if ($quotation_result && $quotation_row = $quotation_result->fetch_assoc()) {
            $quotation_number = $quotation_row['quotation_number'];
        }
        
        // Start transaction
        $conn->begin_transaction();

        
        
        try {
            // Delete quotation items first
            if (!$conn->query("DELETE FROM quotation_items WHERE quotation_id = $id")) {
                throw new Exception("Error deleting quotation items: " . $conn->error);
            }
            
            // Delete quotation
            $sql = "DELETE FROM quotations WHERE id = $id";
            if (!$conn->query($sql)) {
                throw new Exception("Error deleting quotation: " . $conn->error);
            }
            
            if ($conn->affected_rows > 0) {
                $conn->commit();
                redirectWithSuccess("Quotation '$quotation_number' deleted successfully!");
            } else {
                $conn->rollback();
                redirectWithError("Quotation not found or already deleted!");
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            redirectWithError($e->getMessage());
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
    $where_clause = "WHERE q.quotation_number LIKE '%$search%' OR c.company_name LIKE '%$search%' OR q.status LIKE '%$search%'";
}

// Count total records
$count_sql = "SELECT COUNT(*) as total FROM quotations q LEFT JOIN customers c ON q.customer_id = c.id $where_clause";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get machines and spares for item modals
$machines = $conn->query("
    SELECT m.id, m.name, m.model, m.category,
           COALESCE(pm.price, 0) as price
    FROM machines m 
    LEFT JOIN price_master pm ON m.id = pm.machine_id AND pm.is_active = 1 AND CURDATE() BETWEEN pm.valid_from AND pm.valid_to
    WHERE m.is_active = 1 
    ORDER BY m.name
");
$spares = $conn->query("
    SELECT s.id, s.part_name, s.part_code, 
           COALESCE(sp.price, s.price, 0) as price
    FROM spares s 
    LEFT JOIN spare_prices sp ON s.id = sp.spare_id AND sp.is_active = 1 AND CURDATE() BETWEEN sp.valid_from AND sp.valid_to
    WHERE s.is_active = 1 
    ORDER BY s.part_name
");
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-file-text"></i> Quotation Management</h2>
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
                        <label for="quotationSearch" class="form-label">
                            <i class="bi bi-search"></i> Search Quotations
                        </label>
                        <input type="text" class="form-control" id="quotationSearch" 
                               placeholder="Search by quotation number, customer name or status..."
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
        <form method="POST" id="quotationForm" class="row">
            <!-- Quotation Form -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-file-text"></i> <span id="formTitle">Create Quotation</span></h5>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="action" value="create_quotation" id="formAction">
                        <input type="hidden" name="id" id="quotationId">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="quotation_number" class="form-label">Quotation Number *</label>
                                <input type="text" class="form-control" id="quotation_number" name="quotation_number" value="<?php echo $initial_quotation_number; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="quotation_date" class="form-label">Quotation Date *</label>
                                <input type="date" class="form-control" id="quotation_date" name="quotation_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="customer_name" class="form-label">Customer Name *</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" required autocomplete="off" placeholder="Type to search for a customer...">
                            <input type="hidden" id="customer_id" name="customer_id">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="valid_until" class="form-label">Valid Until</label>
                                <input type="date" class="form-control" id="valid_until" name="valid_until" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="enquiry_ref" class="form-label">Enquiry Reference</label>
                            <input type="text" class="form-control" id="enquiry_ref" name="enquiry_ref" placeholder="e.g., Indiamart, Website">
                        </div>
                        
                        <div class="mb-3">
                            <label for="prepared_by" class="form-label">Prepared By</label>
                            <input type="text" class="form-control" id="prepared_by" name="prepared_by" value="Sales Department">
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-success" id="saveBtn"><i class="bi bi-plus-circle"></i> Save Quotation</button>
                            <button type="submit" class="btn btn-primary" id="updateBtn" style="display:none;"><i class="bi bi-check"></i> Update</button>
                            <button type="button" class="btn btn-warning" id="editBtn" style="display:none;"><i class="bi bi-pencil"></i> Edit</button>
                            <button type="button" class="btn btn-danger" id="deleteBtn" style="display:none;"><i class="bi bi-trash"></i> Delete</button>
                            <button type="button" class="btn btn-info" id="printBtn" style="display:none;" onclick="printQuotation()"><i class="bi bi-printer"></i> Print Quotation</button>
                            <button type="button" class="btn btn-success" id="emailBtn" style="display:none;"><i class="bi bi-envelope"></i> Email Quotation</button>
                            <button type="button" class="btn btn-secondary" id="resetBtn"><i class="bi bi-arrow-clockwise"></i> Reset Form</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Items & Calculations -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-list-ul"></i> Items &amp; Calculations</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Add Items</label>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary" id="quotationAddMachineBtn"><i class="bi bi-gear"></i> Add Machine</button>
                                <button type="button" class="btn btn-outline-success" id="quotationAddSpareBtn"><i class="bi bi-tools"></i> Add Spare Part</button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Items List</label>
                            <div id="quotationItemsList" class="border rounded p-2" style="min-height: 200px; max-height: 300px; overflow-y: auto; background-color: #f8f9fa;">
                                <!-- Items will be rendered here by JavaScript -->
                            </div>
                        </div>

                        <div class="border rounded p-3" style="background-color: #f8f9fa;">
                            <h6 class="text-primary mb-3"><i class="bi bi-calculator"></i> Calculations</h6>
                            <div class="mb-3">
                                <label for="total_amount" class="form-label">Subtotal (₹)</label>
                                <input type="number" class="form-control" id="total_amount" name="total_amount" step="0.01" min="0" readonly style="background-color:#fff;">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="discount_percentage" class="form-label">Discount %</label>
                                    <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" step="0.01" min="0" max="100" value="0" onchange="quotationCalcDiscount()">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="discount_amount" class="form-label">Discount Amount (₹)</label>
                                    <input type="number" class="form-control" id="discount_amount" name="discount_amount" step="0.01" min="0" value="0" onchange="quotationCalcDiscountPct()">
                                </div>
                            </div>
                            <div class="mb-0">
                                <label for="grand_total" class="form-label"><strong>Grand Total (₹)</strong></label>
                                <input type="number" class="form-control fw-bold text-success fs-5" name="grand_total" id="grand_total" step="0.01" readonly style="background-color:#fff; border:2px solid #28a745;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="row mt-4">
        <!-- Quotations List -->
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="bi bi-list"></i> All Quotations (<?php echo $total_records; ?>)</h5>
                    <small>Page <?php echo $page; ?> of <?php echo $total_pages; ?></small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Quotation No</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT q.*, c.company_name FROM quotations q LEFT JOIN customers c ON q.customer_id = c.id $where_clause ORDER BY q.created_at DESC LIMIT $records_per_page OFFSET $offset";
                                $result = $conn->query($sql);
                                
                                if ($result && $result->num_rows > 0):
                                    while ($row = $result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['quotation_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                                    <td><?php echo formatDate($row['quotation_date']); ?></td>
                                    <td>₹<?php echo number_format($row['grand_total'] ?? $row['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['status'] == 'approved' ? 'success' : ($row['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucwords($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-quotation" data-id="<?php echo $row['id']; ?>">
                                            <i class="bi bi-pencil"></i> View/Edit
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="printQuotation(<?php echo $row['id']; ?>)">
                                            <i class="bi bi-printer"></i> Print
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-success email-quotation" data-id="<?php echo $row['id']; ?>">
                                            <i class="bi bi-envelope"></i> Email
                                        </button>
                                        <?php if (hasPermission('quotations', 'delete')): ?>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this quotation?')">
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
                                        <i class="bi bi-file-text display-1 text-muted"></i>
                                        <p class="mt-3">No quotations found.</p>
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
    </div>
</div>

<!-- Add Machine Modal -->
<div class="modal fade" id="quotationMachineModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-gear"></i> Add Machine with Features</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="quotationMachineSelect" class="form-label">Select Machine *</label>
                    <select class="form-select" id="quotationMachineSelect">
                        <option value="">Choose Machine...</option>
                        <?php $machines->data_seek(0); while ($m = $machines->fetch_assoc()): ?>
                            <option value="<?php echo $m['id']; ?>" data-name="<?php echo htmlspecialchars($m['name']); ?>" data-price="<?php echo $m['price']; ?>">
                                <?php echo htmlspecialchars($m['name'] . ' (' . $m['model'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="quotationMachineQty" class="form-label">Quantity *</label>
                        <input type="number" class="form-control" id="quotationMachineQty" min="1" value="1">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="quotationMachinePrice" class="form-label">Unit Price (₹) *</label>
                        <input type="number" class="form-control" id="quotationMachinePrice" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="quotationMachineDesc" class="form-label">Description</label>
                    <textarea class="form-control" id="quotationMachineDesc" rows="2"></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="quotationMachineSpecs" class="form-label">Specifications</label>
                    <textarea class="form-control" id="quotationMachineSpecs" rows="2"></textarea>
                </div>

                <!-- Machine Features Section -->
                <div id="machineFeaturesList" class="mt-4" style="display: none;">
                    <hr>
                    <h6 class="text-primary"><i class="bi bi-stars"></i> Machine Features (Optional)</h6>
                    <p class="text-muted small">Select features to include with this machine in the quotation:</p>
                    <div id="featuresContainer" class="border rounded p-3" style="max-height: 300px; overflow-y: auto; background-color: #f8f9fa;">
                        <!-- Features will be loaded here -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="quotationAddMachineToQuotation">Add Machine</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Spare Modal -->
<div class="modal fade" id="quotationSpareModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-tools"></i> Add Spare Part</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="quotationSpareSelect" class="form-label">Select Spare Part *</label>
                    <select class="form-select" id="quotationSpareSelect">
                        <option value="">Choose Spare Part...</option>
                        <?php $spares->data_seek(0); while ($s = $spares->fetch_assoc()): ?>
                            <option value="<?php echo $s['id']; ?>" data-name="<?php echo htmlspecialchars($s['part_name']); ?>" data-price="<?php echo $s['price']; ?>">
                                <?php echo htmlspecialchars($s['part_name'] . ' (' . $s['part_code'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3"><label for="quotationSpareQty" class="form-label">Quantity *</label><input type="number" class="form-control" id="quotationSpareQty" min="1" value="1"></div>
                <div class="mb-3"><label for="quotationSparePrice" class="form-label">Unit Price (₹) *</label><input type="number" class="form-control" id="quotationSparePrice" step="0.01" min="0" placeholder="0.00"></div>
                <div class="mb-3"><label for="quotationSpareDesc" class="form-label">Description</label><textarea class="form-control" id="quotationSpareDesc" rows="3"></textarea></div>
                <div class="mb-3"><label for="quotationSpareSpecs" class="form-label">Specifications</label><textarea class="form-control" id="quotationSpareSpecs" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="quotationAddSpareToQuotation">Add Spare Part</button>
            </div>
        </div>
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
        background: #fff url('data:image/gif;base64,R0lGODlhEAAQAPIAAP///wAAAMLCwkJCQgAAAGJiYoKCgpKSkiH/C05FVFNDQVBFMi4wAwEAAAAh/hpDcmVhdGVkIHdpdGggYWpheGxvYWQuaW5mbwAh+QQJCgAAACwAAAAAEAAQAAADMwi63P4wyklrE2MIOggZnAdOmGYJRbExwroUmcG2LmDEwnHQLVsYOd2mBzkYDAdKa+dIAAAh+QQJCgAAACwAAAAAEAAQAAADNAi63P5OjCEgG4QMu7DmikRxQlFUYDEZIGBMRVsaqHwctXXf7WEYB4Ag1xjihkMZsiUkKhIAIfkECQoAAAAsAAAAABAAEAAAAzYIujIjK8pByJDMlFYvBoVjHA70GU7xSUJhmKtwHPAKzLO9HMaoKwJZ7Rf8AYPDDzKpZBqfvwQAIfkECQoAAAAsAAAAABAAEAAAAzMIumIlK8oyhpHsnFZfhYumCYUhDAQxRIdhHBGqRoKw0R8DYlJd8z0fMDgsGo/IpHI5TAAAIfkECQoAAAAsAAAAABAAEAAAAzIIunInK0rnZBTwGPNMgQwmdsNgXGJUlIWEuR5oWUIpz8pAEAMe6TwfwyYsGo/IpFKSAAAh+QQJCgAAACwAAAAAEAAQAAADMwi6IMKQORfjdOe82p4wGccc4CEuQradylesojEMBgsUc2G7sDX3lQGBMLAJibufbSlKAAAh+QQJCgAAACwAAAAAEAAQAAADMgi63P7wjQkLDfwACl3iyOsGgfFjhJUdZBmmBnSZYgYpvr7KfD4rGGF4/I5cUhTdACwWAA==') no-repeat right center;
        background-size: 16px 16px;
        padding-right: 40px;
    }

    /* Machine features styling */
    .feature-item {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        padding: 10px;
        margin-bottom: 8px;
        background: white;
        cursor: pointer;
    }
    .feature-item:hover {
        background-color: #f8f9fa;
    }
    .feature-item.selected {
        border-color: #0d6efd;
        background-color: rgba(13, 110, 253, 0.1);
    }
    .feature-item .feature-price {
        font-weight: bold;
        color: #28a745;
    }
    .feature-item .no-price {
        color: #6c757d;
        font-style: italic;
    }
</style>

<!-- Email Quotation Modal -->
<div class="modal fade" id="emailQuotationModal" tabindex="-1" aria-labelledby="emailQuotationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailQuotationModalLabel">Email Quotation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="emailQuotationForm">
                    <input type="hidden" id="emailQuotationId" name="quotation_id">
                    <div class="mb-3">
                        <label for="quotation_recipient_email" class="form-label">Primary Recipient Email *</label>
                        <input type="email" class="form-control" id="quotation_recipient_email" name="recipient_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="quotation_additional_emails" class="form-label">Additional Emails (Optional)</label>
                        <input type="text" class="form-control" id="quotation_additional_emails" name="additional_emails" placeholder="email1@example.com, email2@example.com">
                        <small class="text-muted">Separate multiple emails with commas</small>
                    </div>
                    <div class="mb-3">
                        <label for="quotation_custom_message" class="form-label">Custom Message (Optional)</label>
                        <textarea class="form-control" id="quotation_custom_message" name="custom_message" rows="4" placeholder="Add a custom message to the email..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="sendQuotationEmailBtn">Send Email</button>
            </div>
        </div>
    </div>
</div>

<script src="../js/quotations.js"></script>

<?php include '../footer.php'; ?>