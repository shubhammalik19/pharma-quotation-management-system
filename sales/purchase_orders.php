<?php
include '../header.php';
checkLogin();
include '../menu.php';

$prefix = "PO-";

// Get initial PO number
$initial_po_number = generatePurchaseOrderNumber($conn, $prefix);

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create_po' && hasPermission('purchase_orders', 'create')) {
            $po_number = sanitizeInput($_POST['po_number']);
            $vendor_id = intval($_POST['vendor_id'] ?? 0);
            $sales_order_id = intval($_POST['sales_order_id'] ?? 0);
            $po_date = sanitizeInput($_POST['po_date']);
            $due_date = sanitizeInput($_POST['due_date']);
            $status = sanitizeInput($_POST['status']);
            $notes = sanitizeInput($_POST['notes']);
            $total_amount = floatval($_POST['total_amount'] ?? 0);
            $discount_percentage = floatval($_POST['discount_percentage'] ?? 0);
            $discount_amount = floatval($_POST['discount_amount'] ?? 0);
            $final_total = $total_amount - $discount_amount;

            // Validate vendor
            $vendor_name = '';
            if ($vendor_id > 0) {
                $check_vendor = $conn->query("SELECT company_name FROM customers WHERE id = $vendor_id AND (entity_type = 'vendor' OR entity_type = 'both')");
                if ($check_vendor->num_rows > 0) {
                    $vendor_name = $check_vendor->fetch_assoc()['company_name'];
                } else {
                    redirectWithError('Selected vendor does not exist or is not valid.');
                }
            } else {
                redirectWithError('Please select a valid vendor.');
            }

            // Validate sales order if provided
            if (!empty($sales_order_id)) {
                $check_so = $conn->query("SELECT id, so_number FROM sales_orders WHERE id = $sales_order_id");
                if ($check_so->num_rows === 0) {
                    redirectWithError('Selected sales order does not exist.');
                }
            }

            // Start transaction
            $conn->begin_transaction();
            
            try {
                $sql = "INSERT INTO purchase_orders (po_number, vendor_name, vendor_id, sales_order_id, po_date, due_date, status, notes, total_amount, discount_percentage, discount_amount, final_total, created_by) 
                        VALUES ('$po_number', '$vendor_name', $vendor_id, " . ($sales_order_id ?: 'NULL') . ", '$po_date', " . ($due_date ? "'$due_date'" : "NULL") . ", '$status', '$notes', $total_amount, $discount_percentage, $discount_amount, $final_total, {$_SESSION['user_id']})";

                if (!$conn->query($sql)) {
                    throw new Exception('Error creating purchase order: ' . $conn->error);
                }
                
                $po_id = $conn->insert_id;

                // Handle PO items
                if (isset($_POST['items']) && is_array($_POST['items'])) {
                    foreach ($_POST['items'] as $item) {
                        $item_type = sanitizeInput($item['type']);
                        $item_id = intval($item['item_id']);
                        $item_name = sanitizeInput($item['name']);
                        $description = sanitizeInput($item['description']);
                        $hsn_code = sanitizeInput($item['hsn_code'] ?? '');
                        $quantity = intval($item['quantity']);
                        $unit_price = floatval($item['unit_price']);
                        $total_price = floatval($item['total_price']);

                        $item_sql = "INSERT INTO purchase_order_items (po_id, item_type, item_id, item_name, description, hsn_code, quantity, unit_price, total_price) 
                                     VALUES ($po_id, '$item_type', $item_id, '$item_name', '$description', '$hsn_code', $quantity, $unit_price, $total_price)";
                        
                        if (!$conn->query($item_sql)) {
                            throw new Exception("Error saving item: " . $conn->error);
                        }
                    }
                }

                // Commit transaction
                $conn->commit();
                redirectWithSuccess('Purchase Order created successfully!');
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                redirectWithError($e->getMessage());
            }
            
        } elseif ($_POST['action'] === 'update_po' && hasPermission('purchase_orders', 'edit')) {
            $po_id = intval($_POST['id']);
            
            // Check if PO is already used in sales invoice
            $invCheck = $conn->query("SELECT id FROM sales_invoices WHERE purchase_order_id = $po_id LIMIT 1");
            if ($invCheck && $invCheck->num_rows > 0) {
                redirectWithError('This Purchase Order is already used in a Sales Invoice and cannot be edited.');
            }
            
            $po_number = sanitizeInput($_POST['po_number']);
            $vendor_id = intval($_POST['vendor_id'] ?? 0);
            $sales_order_id = intval($_POST['sales_order_id'] ?? 0);
            $po_date = sanitizeInput($_POST['po_date']);
            $due_date = sanitizeInput($_POST['due_date']);
            $status = sanitizeInput($_POST['status']);
            $notes = sanitizeInput($_POST['notes']);
            $total_amount = floatval($_POST['total_amount'] ?? 0);
            $discount_percentage = floatval($_POST['discount_percentage'] ?? 0);
            $discount_amount = floatval($_POST['discount_amount'] ?? 0);
            $final_total = $total_amount - $discount_amount;

            // Validate vendor
            $vendor_name = '';
            if ($vendor_id > 0) {
                $check_vendor = $conn->query("SELECT company_name FROM customers WHERE id = $vendor_id AND (entity_type = 'vendor' OR entity_type = 'both')");
                if ($check_vendor->num_rows > 0) {
                    $vendor_name = $check_vendor->fetch_assoc()['company_name'];
                } else {
                    redirectWithError('Selected vendor does not exist or is not valid.');
                }
            } else {
                redirectWithError('Please select a valid vendor.');
            }

            // Validate sales order if provided
            if (!empty($sales_order_id)) {
                $check_so = $conn->query("SELECT id, so_number FROM sales_orders WHERE id = $sales_order_id");
                if ($check_so->num_rows === 0) {
                    redirectWithError('Selected sales order does not exist.');
                }
            }

            // Start transaction
            $conn->begin_transaction();
            
            try {
                $sql = "UPDATE purchase_orders SET 
                        po_number = '$po_number',
                        vendor_name = '$vendor_name',
                        vendor_id = $vendor_id,
                        sales_order_id = " . ($sales_order_id ?: 'NULL') . ",
                        po_date = '$po_date',
                        due_date = " . ($due_date ? "'$due_date'" : "NULL") . ",
                        status = '$status',
                        notes = '$notes',
                        total_amount = $total_amount,
                        discount_percentage = $discount_percentage,
                        discount_amount = $discount_amount,
                        final_total = $final_total
                        WHERE id = $po_id";

                if (!$conn->query($sql)) {
                    throw new Exception('Error updating purchase order: ' . $conn->error);
                }
                
                // Delete existing items and insert new ones
                if (!$conn->query("DELETE FROM purchase_order_items WHERE po_id = $po_id")) {
                    throw new Exception('Error deleting existing items: ' . $conn->error);
                }

                if (isset($_POST['items']) && is_array($_POST['items'])) {
                    foreach ($_POST['items'] as $item) {
                        $item_type = sanitizeInput($item['type']);
                        $item_id = intval($item['item_id']);
                        $item_name = sanitizeInput($item['name']);
                        $description = sanitizeInput($item['description']);
                        $hsn_code = sanitizeInput($item['hsn_code'] ?? '');
                        $quantity = intval($item['quantity']);
                        $unit_price = floatval($item['unit_price']);
                        $total_price = floatval($item['total_price']);

                        $item_sql = "INSERT INTO purchase_order_items (po_id, item_type, item_id, item_name, description, hsn_code, quantity, unit_price, total_price) 
                                     VALUES ($po_id, '$item_type', $item_id, '$item_name', '$description', '$hsn_code', $quantity, $unit_price, $total_price)";
                        
                        if (!$conn->query($item_sql)) {
                            throw new Exception("Error saving item: " . $conn->error);
                        }
                    }
                }

                // Commit transaction
                $conn->commit();
                redirectWithSuccess('Purchase Order updated successfully!');
                
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
    if (!hasPermission('purchase_orders', 'delete')) {
        redirectWithError("You don't have permission to delete purchase orders!");
    } else {
        $id = (int)$_GET['delete'];
        
        // Check if PO is already used in sales invoice
        $invCheck = $conn->query("SELECT id FROM sales_invoices WHERE purchase_order_id = $id LIMIT 1");
        if ($invCheck && $invCheck->num_rows > 0) {
            redirectWithError("This Purchase Order is already used in a Sales Invoice and cannot be deleted.");
        }
        
        // Get PO number for confirmation message
        $po_sql = "SELECT po_number FROM purchase_orders WHERE id = $id";
        $po_result = $conn->query($po_sql);
        $po_number = '';
        if ($po_result && $po_row = $po_result->fetch_assoc()) {
            $po_number = $po_row['po_number'];
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Delete PO items first
            if (!$conn->query("DELETE FROM purchase_order_items WHERE po_id = $id")) {
                throw new Exception("Error deleting purchase order items: " . $conn->error);
            }
            
            // Delete purchase order
            $sql = "DELETE FROM purchase_orders WHERE id = $id";
            if (!$conn->query($sql)) {
                throw new Exception("Error deleting purchase order: " . $conn->error);
            }
            
            if ($conn->affected_rows > 0) {
                $conn->commit();
                redirectWithSuccess("Purchase Order '$po_number' deleted successfully!");
            } else {
                $conn->rollback();
                redirectWithError("Purchase Order not found or already deleted!");
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

// Build search query using common function
$search_fields = ['vendor_name', 'po_number', 'status'];
$where_clause = '';
if (!empty($search)) {
    $search_query = buildSearchQuery($search, $search_fields, 'po');
    $where_clause = "WHERE $search_query";
}

// Get pagination data
$count_sql = "SELECT COUNT(*) as total FROM purchase_orders po $where_clause";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];

$pagination = getPaginationData($total_records, $page, $records_per_page);

// Get machines and spares for item modals
$machines = $conn->query("
    SELECT m.id, m.name, m.model,
           COALESCE(pm.price, 0) as price
    FROM machines m 
    LEFT JOIN price_master pm ON m.id = pm.machine_id AND pm.is_active = 1 AND CURDATE() BETWEEN pm.valid_from AND pm.valid_to
    WHERE m.is_active = 1 
    ORDER BY m.name
");
$spares = $conn->query("SELECT id, part_name, part_code, price FROM spares WHERE is_active = 1 ORDER BY part_name");
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-cart-plus"></i> Purchase Order Management</h2>
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
                        <label for="poSearch" class="form-label">
                            <i class="bi bi-search"></i> Search Purchase Orders
                        </label>
                        <input type="text" class="form-control" id="poSearch" 
                               placeholder="Search by PO number or vendor name..."
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
        <form method="POST" id="poForm" class="row">
            <!-- Purchase Order Form -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-file-earmark-text"></i> <span id="formTitle">Create Purchase Order</span></h5>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="action" value="create_po" id="formAction">
                        <input type="hidden" name="id" id="poId">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="po_number" class="form-label">PO Number *</label>
                                <input type="text" class="form-control" id="po_number" name="po_number" value="<?php echo $initial_po_number; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="po_date" class="form-label">PO Date *</label>
                                <input type="date" class="form-control" id="po_date" name="po_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="vendor_name" class="form-label">Vendor Name *</label>
                            <input type="text" class="form-control" id="vendor_name" name="vendor_name" required autocomplete="off" placeholder="Type to search for a vendor...">
                            <input type="hidden" id="vendor_id" name="vendor_id">
                        </div>

                         <div class="mb-3">
                                <label for="sales_order_number" class="form-label">Sale Order No.</label>
                                <input type="text" class="form-control" id="sales_order_number" name="sales_order_number"
                                    autocomplete="off" placeholder="Type to search sale order...">
                                <input type="hidden" id="sales_order_id" name="sales_order_id">
                                <small class="text-muted">Pick an existing sale order to preload items &amp; values.</small>
                        </div>

                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo date('Y-m-d', strtotime('+15 days')); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="draft">Draft</option>
                                    <option value="sent">Sent</option>
                                    <option value="acknowledged">Acknowledged</option>
                                    <option value="received">Received</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-success" id="saveBtn"><i class="bi bi-plus-circle"></i> Save Purchase Order</button>
                            <button type="submit" class="btn btn-primary" id="updateBtn" style="display:none;"><i class="bi bi-check"></i> Update</button>
                            <button type="button" class="btn btn-warning" id="editBtn" style="display:none;"><i class="bi bi-pencil"></i> Edit</button>
                            <button type="button" class="btn btn-danger" id="deleteBtn" style="display:none;"><i class="bi bi-trash"></i> Delete</button>
                            <button type="button" class="btn btn-info" id="printBtn" style="display:none;" onclick="printPO()"><i class="bi bi-printer"></i> Print PO</button>
                            <button type="button" class="btn btn-success" id="emailBtn" style="display:none;"><i class="bi bi-envelope"></i> Email PO</button>
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
                                <button type="button" class="btn btn-outline-primary" id="poAddMachineBtn"><i class="bi bi-gear"></i> Add Machine</button>
                                <button type="button" class="btn btn-outline-success" id="poAddSpareBtn"><i class="bi bi-tools"></i> Add Spare Part</button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Items List</label>
                            <div id="poItemsList" class="border rounded p-2" style="min-height: 200px; max-height: 300px; overflow-y: auto; background-color: #f8f9fa;">
                                <div class="text-muted text-center py-4">
                                    <i class="bi bi-box fs-2"></i><br>
                                    <strong>No items added yet</strong><br>
                                    <small>Use the buttons above to add machines and spare parts</small>
                                </div>
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
                                    <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" step="0.01" min="0" max="100" value="0" onchange="poCalcDiscount()">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="discount_amount" class="form-label">Discount Amount (₹)</label>
                                    <input type="number" class="form-control" id="discount_amount" name="discount_amount" step="0.01" min="0" value="0" onchange="poCalcDiscountPct()">
                                </div>
                            </div>
                            <div class="mb-0">
                                <label for="final_total" class="form-label"><strong>Final Total (₹)</strong></label>
                                <input type="number" class="form-control fw-bold text-success fs-5" name="final_total" id="final_total" step="0.01" readonly style="background-color:#fff; border:2px solid #28a745;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="row mt-4">
        <!-- Purchase Orders List -->
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="bi bi-list"></i> All Purchase Orders (<?php echo $total_records; ?>)</h5>
                    <small>Page <?php echo $pagination['current_page']; ?> of <?php echo $pagination['total_pages']; ?></small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>PO Number</th>
                                    <th>Vendor</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT po.* FROM purchase_orders po $where_clause ORDER BY po.created_at DESC LIMIT {$pagination['records_per_page']} OFFSET {$pagination['offset']}";
                                $result = $conn->query($sql);
                                
                                if ($result && $result->num_rows > 0):
                                    while ($row = $result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['po_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['vendor_name']); ?></td>
                                    <td><?php echo formatDate($row['po_date']); ?></td>
                                    <td>₹<?php echo number_format($row['final_total'] ?? 0, 2); ?></td>
                                    <td>
                                        <span class="badge <?php echo getStatusBadgeClass($row['status']); ?>">
                                            <?php echo formatStatusForDisplay($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-po" data-id="<?php echo $row['id']; ?>">
                                            <i class="bi bi-pencil"></i> View/Edit
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="printPO(<?php echo $row['id']; ?>)">
                                            <i class="bi bi-printer"></i> Print
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-success email-po" data-id="<?php echo $row['id']; ?>">
                                            <i class="bi bi-envelope"></i> Email
                                        </button>
                                        <?php if (hasPermission('purchase_orders', 'delete')): ?>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this purchase order?')">
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
                                        <i class="bi bi-inbox display-1 text-muted"></i>
                                        <p class="mt-3">No purchase orders found.</p>
                                        <?php if (!empty($search)): ?>
                                            <a href="purchase_orders.php" class="btn btn-outline-primary">Clear Search</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($pagination['total_pages'] > 1): ?>
                        <nav><ul class="pagination justify-content-center">
                            <?php if ($pagination['current_page'] > 1): ?>
                                <li class="page-item"><a class="page-link" href="?page=<?php echo ($pagination['current_page'] - 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Previous</a></li>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                <li class="page-item <?php echo ($i == $pagination['current_page']) ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a></li>
                            <?php endfor; ?>
                            <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                <li class="page-item"><a class="page-link" href="?page=<?php echo ($pagination['current_page'] + 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next</a></li>
                            <?php endif; ?>
                        </ul></nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Machine Modal -->
<div class="modal fade" id="poMachineModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-gear"></i> Add Machine</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="poMachineSelect" class="form-label">Select Machine *</label>
                    <select class="form-select" id="poMachineSelect">
                        <option value="">Choose Machine...</option>
                        <?php $machines->data_seek(0); while ($m = $machines->fetch_assoc()): ?>
                            <option value="<?php echo $m['id']; ?>" data-name="<?php echo htmlspecialchars($m['name']); ?>" data-price="<?php echo $m['price']; ?>">
                                <?php echo htmlspecialchars($m['name'] . ' (' . $m['model'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3"><label for="poMachineQty" class="form-label">Quantity *</label><input type="number" class="form-control" id="poMachineQty" min="1" value="1"></div>
                <div class="mb-3"><label for="poMachinePrice" class="form-label">Unit Price (₹) *</label><input type="number" class="form-control" id="poMachinePrice" step="0.01" min="0" placeholder="0.00"></div>
                <div class="mb-3"><label for="poMachineDesc" class="form-label">Description</label><textarea class="form-control" id="poMachineDesc" rows="3"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="poAddMachineToPO">Add Machine</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Spare Modal -->
<div class="modal fade" id="poSpareModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-tools"></i> Add Spare Part</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="poSpareSelect" class="form-label">Select Spare Part *</label>
                    <select class="form-select" id="poSpareSelect">
                        <option value="">Choose Spare Part...</option>
                        <?php $spares->data_seek(0); while ($s = $spares->fetch_assoc()): ?>
                            <option value="<?php echo $s['id']; ?>" data-name="<?php echo htmlspecialchars($s['part_name']); ?>" data-price="<?php echo $s['price']; ?>">
                                <?php echo htmlspecialchars($s['part_name'] . ' (' . $s['part_code'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3"><label for="poSpareQty" class="form-label">Quantity *</label><input type="number" class="form-control" id="poSpareQty" min="1" value="1"></div>
                <div class="mb-3"><label for="poSparePrice" class="form-label">Unit Price (₹) *</label><input type="number" class="form-control" id="poSparePrice" step="0.01" min="0" placeholder="0.00"></div>
                <div class="mb-3"><label for="poSpareDesc" class="form-label">Description</label><textarea class="form-control" id="poSpareDesc" rows="3"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="poAddSpareToPO">Add Spare Part</button>
            </div>
        </div>
    </div>
</div>

<!-- Styles specific to this page -->
<style>
    .readonly-field { background: #f8f9fa !important; pointer-events: none; }
    .table-hover tbody tr:hover { background-color: rgba(0,0,0,.075); }

    /* Ensure search input is always functional */
    #poSearch {
        pointer-events: auto !important;
        background: #fff !important;
        border: 1px solid #ced4da !important;
    }
    
    #poSearch:focus {
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
        background: #fff url('data:image/gif;base64,R0lGODlhEAAQAPIAAP///wAAAMLCwkJCQgAAAGJiYoKCgpKSkiH/C05FVFNDQVBFMi4wAwEAAAAh/hpDcmVhdGVkIHdpdGggYWpheGxvYWQuaW5mbwAh+QQJCgAAACwAAAAAEAAQAAADMwi63P4wyklrE2MIOggZnAdOmGYJRbExwroUmcG2LmDEwnHQLVsYOd2mBzkYDAdKa+dIAAAh+QQJCgAAACwAAAAAEAAQAAADNAi63P5OjCEgG4QMu7DmikRxQlFUYDEZIGBMRVsaqHwctXXf7WEYB4Ag1xjihkMZsiUkKhIAIfkECQoAAAAsAAAAABAAEAAAAzYIujIjK8pByJDMlFYvBoVjHA70GU7xSUJhmKtwHPAKzLO9HMaoKwJZ7Rf8AYPDDzKpZBqfvwQAIfkECQoAAAAsAAAAABAAEAAAAzMIumIlK8oyhpHsnFZfhYumCYUhDAQxRIdhHBGqRoKw0R8DYlJd8z0fMDgsGo/IpHI5TAAAIfkECQoAAAAsAAAAABAAEAAAAzIIunInK0rnZBTwGPNMgQwmdsNgXGJUlIWEuR5oWUIpz8pAEAMe6TwfwyYsGo/IpFKSAAAh+QQJCgAAACwAAAAAEAAQAAADMwi6IMKQORfjdOe82p4wGccc4CEuQradylesojEMBgsUc2G7sDX3lQGBMLAJibufbSlKAAAh+QQJCgAAACwAAAAAEAAQAAADMgi63P7wjQkLDfwACl3iyOsGgfFjhJUdZBmmBnSZYgYpvr7KfD4rGGF4/I5cUhTdACwWAA==') no-repeat right center;
        background-size: 16px 16px;
        padding-right: 40px;
    }
</style>

<!-- Email PO Modal -->
<div class="modal fade" id="emailPOModal" tabindex="-1" aria-labelledby="emailPOModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailPOModalLabel">Email Purchase Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="emailPOForm">
                    <input type="hidden" id="emailPOId" name="purchase_order_id">
                    <div class="mb-3">
                        <label for="po_recipient_email" class="form-label">Primary Recipient Email *</label>
                        <input type="email" class="form-control" id="po_recipient_email" name="recipient_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="po_additional_emails" class="form-label">Additional Emails (Optional)</label>
                        <input type="text" class="form-control" id="po_additional_emails" name="additional_emails" placeholder="email1@example.com, email2@example.com">
                        <small class="text-muted">Separate multiple emails with commas</small>
                    </div>
                    <div class="mb-3">
                        <label for="po_custom_message" class="form-label">Custom Message (Optional)</label>
                        <textarea class="form-control" id="po_custom_message" name="custom_message" rows="4" placeholder="Add a custom message to the email..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="sendPOEmailBtn">Send Email</button>
            </div>
        </div>
    </div>
</div>

<script src="../js/purchase_orders.js"></script>

<?php include '../footer.php'; ?>
