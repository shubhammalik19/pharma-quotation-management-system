<?php
include '../header.php';
checkLogin();
include '../menu.php';

$prefix = "SO-";

// Generate new sales order number
function generateSONumber($conn)
{
    global $prefix;
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

$initial_so_number = generateSONumber($conn);

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create_so' && hasPermission('sales_orders', 'create')) {
            $so_number = sanitizeInput($_POST['so_number']);
            $customer_name = sanitizeInput($_POST['customer_name']);
            $customer_id = intval($_POST['customer_id'] ?? 0);
            $customer_address = sanitizeInput($_POST['customer_address'] ?? '');
            $customer_gstin = sanitizeInput($_POST['customer_gstin'] ?? '');
            $customer_contact = sanitizeInput($_POST['customer_contact'] ?? '');
            $quotation_id = intval($_POST['quotation_id'] ?? 0);
            $quotation_number = sanitizeInput($_POST['quotation_number'] ?? '');
            $so_date = sanitizeInput($_POST['so_date']);
            $delivery_date = sanitizeInput($_POST['delivery_date']);
            $notes = sanitizeInput($_POST['notes']);
            $status = sanitizeInput($_POST['status']);
            $total_amount = floatval($_POST['total_amount'] ?? 0);
            $discount_percentage = floatval($_POST['discount_percentage'] ?? 0);
            $discount_amount = floatval($_POST['discount_amount'] ?? 0);

            $final_total = $total_amount - $discount_amount;

            // Validate customer exists and get additional data
            if ($customer_id > 0) {
                $check_customer = $conn->query("SELECT id, company_name, address, gst_no, phone FROM customers WHERE id = $customer_id AND (entity_type = 'customer' OR entity_type = 'both')");
                if ($check_customer->num_rows === 0) {
                    redirectWithError('Selected customer does not exist or is not valid.');
                } else {
                    $customer_data = $check_customer->fetch_assoc();
                    $customer_name = $customer_data['company_name'];
                    // Auto-fill customer details if not provided
                    if (empty($customer_address)) $customer_address = $customer_data['address'] ?? '';
                    if (empty($customer_gstin)) $customer_gstin = $customer_data['gst_no'] ?? '';
                    if (empty($customer_contact)) $customer_contact = $customer_data['phone'] ?? '';
                }
            } else {
                redirectWithError('Please select a valid customer.');
            }

            // Validate quotation if provided
            if (!empty($quotation_id)) {
                $check_quotation = $conn->query("SELECT id, quotation_number FROM quotations WHERE id = $quotation_id");
                if ($check_quotation->num_rows === 0) {
                    redirectWithError('Selected quotation does not exist.');
                } else {
                    $quotation_data = $check_quotation->fetch_assoc();
                    $quotation_number = $quotation_data['quotation_number'];
                }
            }

            // Start transaction
            $conn->begin_transaction();
            
            try {
                $sql = "INSERT INTO sales_orders (so_number, customer_name, customer_id, customer_address, customer_gstin, customer_contact, quotation_id, quotation_number, so_date, delivery_date, notes, status, total_amount, discount_percentage, discount_amount, final_total, created_by) 
                        VALUES ('$so_number', '$customer_name', $customer_id, '$customer_address', '$customer_gstin', '$customer_contact', " . ($quotation_id ?: 'NULL') . ", " . ($quotation_number ? "'$quotation_number'" : 'NULL') . ", '$so_date', " . ($delivery_date ? "'$delivery_date'" : "NULL") . ", '$notes', '$status', $total_amount, $discount_percentage, $discount_amount, $final_total, {$_SESSION['user_id']})";

                if (!$conn->query($sql)) {
                    throw new Exception('Error creating sales order: ' . $conn->error);
                }
                
                $so_id = $conn->insert_id;

                // Handle SO items
                if (isset($_POST['items']) && is_array($_POST['items'])) {
                    foreach ($_POST['items'] as $item) {
                        $item_type = sanitizeInput($item['type']);
                        $item_id = intval($item['item_id']);
                        $item_name = sanitizeInput($item['name']);
                        $description = sanitizeInput($item['description']);
                        $hsn_code = sanitizeInput($item['hsn_code'] ?? '');
                        $quantity = intval($item['quantity']);
                        $unit = sanitizeInput($item['unit'] ?? 'Nos');
                        $unit_price = floatval($item['unit_price']);
                        $rate = floatval($item['rate'] ?? $item['unit_price']);
                        $gst_rate = floatval($item['gst_rate'] ?? 18);
                        $amount = floatval($item['amount'] ?? $item['total_price']);
                        $total_price = floatval($item['total_price']);
                        $sl_no = intval($item['sl_no'] ?? 0);

                        $item_sql = "INSERT INTO sales_order_items (so_id, item_type, item_id, item_name, description, hsn_code, quantity, unit_price, total_price, sl_no, unit, rate, gst_rate, amount) 
                                     VALUES ($so_id, '$item_type', $item_id, '$item_name', '$description', '$hsn_code', $quantity, $unit_price, $total_price, $sl_no, '$unit', $rate, $gst_rate, $amount)";
                        
                        if (!$conn->query($item_sql)) {
                            throw new Exception("Error saving item: " . $conn->error);
                        }
                    }
                }

                // Commit transaction
                $conn->commit();
                redirectWithSuccess('Sales Order created successfully!');
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                redirectWithError($e->getMessage());
            }
        } elseif ($_POST['action'] === 'update_so' && hasPermission('sales_orders', 'edit')) {
            $so_id = intval($_POST['id']);
            $so_number = sanitizeInput($_POST['so_number']);
            $customer_name = sanitizeInput($_POST['customer_name']);
            $customer_id = intval($_POST['customer_id'] ?? 0);
            $customer_address = sanitizeInput($_POST['customer_address'] ?? '');
            $customer_gstin = sanitizeInput($_POST['customer_gstin'] ?? '');
            $customer_contact = sanitizeInput($_POST['customer_contact'] ?? '');
            $quotation_id = intval($_POST['quotation_id'] ?? 0);
            $quotation_number = sanitizeInput($_POST['quotation_number'] ?? '');
            $so_date = sanitizeInput($_POST['so_date']);
            $delivery_date = sanitizeInput($_POST['delivery_date']);
            $notes = sanitizeInput($_POST['notes']);
            $status = sanitizeInput($_POST['status']);
            $total_amount = floatval($_POST['total_amount'] ?? 0);
            $discount_percentage = floatval($_POST['discount_percentage'] ?? 0);
            $discount_amount = floatval($_POST['discount_amount'] ?? 0);
            $final_total = $total_amount - $discount_amount;

            $poCheck = $conn->query("SELECT id FROM purchase_orders WHERE sales_order_id = $so_id LIMIT 1");
            if ($poCheck && $poCheck->num_rows > 0) {
                redirectWithError('This Sales Order is already used in a Purchase Order and cannot be edited.');
            }
            
            // Validate customer exists and get additional data
            if ($customer_id > 0) {
                $check_customer = $conn->query("SELECT id, company_name, address,gst_no as gstin, phone FROM customers WHERE id = $customer_id AND (entity_type = 'customer' OR entity_type = 'both')");
                if ($check_customer->num_rows === 0) {
                    redirectWithError('Selected customer does not exist or is not valid.');
                } else {
                    $customer_data = $check_customer->fetch_assoc();
                    $customer_name = $customer_data['company_name'];
                    // Auto-fill customer details if not provided
                    if (empty($customer_address)) $customer_address = $customer_data['address'] ?? '';
                    if (empty($customer_gstin)) $customer_gstin = $customer_data['gstin'] ?? '';
                    if (empty($customer_contact)) $customer_contact = $customer_data['phone'] ?? '';
                }
            } else {
                redirectWithError('Please select a valid customer.');
            }

            // Validate quotation if provided
            if (!empty($quotation_id)) {
                $check_quotation = $conn->query("SELECT id, quotation_number FROM quotations WHERE id = $quotation_id");
                if ($check_quotation->num_rows === 0) {
                    redirectWithError('Selected quotation does not exist.');
                } else {
                    $quotation_data = $check_quotation->fetch_assoc();
                    $quotation_number = $quotation_data['quotation_number'];
                }
            }

            // Start transaction
            $conn->begin_transaction();
            
            try {
                $sql = "UPDATE sales_orders SET 
                        so_number = '$so_number', 
                        customer_name = '$customer_name', 
                        customer_id = $customer_id, 
                        customer_address = '$customer_address',
                        customer_gstin = '$customer_gstin',
                        customer_contact = '$customer_contact',
                        quotation_id = " . ($quotation_id ?: 'NULL') . ",
                        quotation_number = " . ($quotation_number ? "'$quotation_number'" : 'NULL') . ",
                        so_date = '$so_date', 
                        delivery_date = " . ($delivery_date ? "'$delivery_date'" : "NULL") . ", 
                        notes = '$notes', 
                        status = '$status',
                        total_amount = $total_amount,
                        discount_percentage = $discount_percentage,
                        discount_amount = $discount_amount,
                        final_total = $final_total
                        WHERE id = $so_id";

                if (!$conn->query($sql)) {
                    throw new Exception('Error updating sales order: ' . $conn->error);
                }
                
                // Delete existing items and insert new ones
                if (!$conn->query("DELETE FROM sales_order_items WHERE so_id = $so_id")) {
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
                        $unit = sanitizeInput($item['unit'] ?? 'Nos');
                        $unit_price = floatval($item['unit_price']);
                        $rate = floatval($item['rate'] ?? $item['unit_price']);
                        $gst_rate = floatval($item['gst_rate'] ?? 18);
                        $amount = floatval($item['amount'] ?? $item['total_price']);
                        $total_price = floatval($item['total_price']);
                        $sl_no = intval($item['sl_no'] ?? 0);

                        $item_sql = "INSERT INTO sales_order_items (so_id, item_type, item_id, item_name, description, hsn_code, quantity, unit_price, total_price, sl_no, unit, rate, gst_rate, amount) 
                                     VALUES ($so_id, '$item_type', $item_id, '$item_name', '$description', '$hsn_code', $quantity, $unit_price, $total_price, $sl_no, '$unit', $rate, $gst_rate, $amount)";
                        
                        if (!$conn->query($item_sql)) {
                            throw new Exception("Error saving item: " . $conn->error);
                        }
                    }
                }

                // Commit transaction
                $conn->commit();
                redirectWithSuccess('Sales Order updated successfully!');
                
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
    if (!hasPermission('sales_orders', 'delete')) {
        redirectWithError("You don't have permission to delete sales orders!");
    } else {
        $id = (int)$_GET['delete'];

        $poCheck = $conn->query("SELECT id FROM purchase_orders WHERE sales_order_id = $id LIMIT 1");
        if ($poCheck && $poCheck->num_rows > 0) {
            redirectWithError('This Sales Order is already used in a Purchase Order and cannot be deleted.');
        }
        
        // Get SO number for confirmation message
        $so_sql = "SELECT so_number FROM sales_orders WHERE id = $id";
        $so_result = $conn->query($so_sql);
        $so_number = '';
        if ($so_result && $so_row = $so_result->fetch_assoc()) {
            $so_number = $so_row['so_number'];
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Delete SO items first
            if (!$conn->query("DELETE FROM sales_order_items WHERE so_id = $id")) {
                throw new Exception("Error deleting sales order items: " . $conn->error);
            }
            
            // Delete sales order
            $sql = "DELETE FROM sales_orders WHERE id = $id";
            if (!$conn->query($sql)) {
                throw new Exception("Error deleting sales order: " . $conn->error);
            }
            
            if ($conn->affected_rows > 0) {
                $conn->commit();
                redirectWithSuccess("Sales Order '$so_number' deleted successfully!");
            } else {
                $conn->rollback();
                redirectWithError("Sales Order not found or already deleted!");
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            redirectWithError($e->getMessage());
        }
    }
}

// Get search parameter
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build search query
$whereClause = '';
if (!empty($search)) {
    $whereClause = "WHERE so.so_number LIKE '%$search%' OR so.customer_name LIKE '%$search%' OR so.quotation_number LIKE '%$search%' OR so.status LIKE '%$search%'";
}

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM sales_orders so $whereClause";
$countResult = $conn->query($countSql);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Get all sales orders with pagination
$so_sql = "SELECT so.*, u.full_name as created_by_name 
           FROM sales_orders so 
           LEFT JOIN users u ON so.created_by = u.id 
           $whereClause
           ORDER BY so.created_at DESC
           LIMIT $limit OFFSET $offset";
$so_result = $conn->query($so_sql);

// Get machines and spares for SO items with current pricing
$machines = $conn->query("
    SELECT m.id, m.name, m.model, m.category, 
           COALESCE(pm.price, 0) as price,
           pm.valid_from, pm.valid_to
    FROM machines m 
    LEFT JOIN price_master pm ON m.id = pm.machine_id 
        AND CURDATE() BETWEEN pm.valid_from AND pm.valid_to
    WHERE m.is_active = 1 
    ORDER BY m.name
");

$spares = $conn->query("SELECT id, part_name, part_code, price FROM spares WHERE is_active = 1 ORDER BY part_name");
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-cart-check"></i> Sales Orders</h2>
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
                        <label for="soSearch" class="form-label">
                            <i class="bi bi-search"></i> Search Sales Orders
                        </label>
                        <input type="text" class="form-control" id="soSearch"
                               placeholder="Search by SO number, customer name, quotation number or status..."
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
        <form method="POST" id="soForm" class="row">
            <!-- Sales Order Form -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-cart-plus"></i> <span id="formTitle">Create Sales Order</span></h5>
                    </div>
                    <div class="card-body">
                       
                            <input type="hidden" name="action" value="create_so" id="formAction">
                            <input type="hidden" name="id" id="soId">

                            <div class="mb-3">
                                <label for="so_number" class="form-label">SO Number *</label>
                                <input type="text" class="form-control" id="so_number" name="so_number"
                                    value="<?php echo htmlspecialchars($initial_so_number); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="so_date" class="form-label">SO Date *</label>
                                <input type="date" class="form-control" id="so_date" name="so_date"
                                    value="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="quotation_number" class="form-label">Quotation No.</label>
                                <input type="text" class="form-control" id="quotation_number" name="quotation_number"
                                    autocomplete="off" placeholder="Type to search quotation...">
                                <input type="hidden" id="quotation_id" name="quotation_id">
                                <small class="text-muted">Pick an existing quotation to preload items &amp; values.</small>
                            </div>

                            <div class="mb-3">
                                <label for="customer_name" class="form-label">Customer Name *</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" required autocomplete="off">
                                <input type="hidden" id="customer_id" name="customer_id">
                            </div>

                            <div class="mb-3">
                                <label for="delivery_date" class="form-label">Delivery Date</label>
                                <input type="date" class="form-control" id="delivery_date" name="delivery_date">
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="draft">Draft</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="processing">Processing</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="delivered">Delivered</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-success" id="saveBtn">
                                    <i class="bi bi-plus-circle"></i> Save Sales Order
                                </button>
                                <button type="button" class="btn btn-warning" id="editBtn" style="display:none;">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button type="submit" class="btn btn-primary" id="updateBtn" style="display:none;">
                                    <i class="bi bi-check"></i> Update
                                </button>
                                <button type="button" class="btn btn-danger" id="deleteBtn" style="display:none;">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                                <button type="button" class="btn btn-info" id="printBtn" onclick="printSO()">
                                    <i class="bi bi-printer"></i> Print SO
                                </button>
                                <button type="button" class="btn btn-success" id="emailBtn" style="display:none;">
                                    <i class="bi bi-envelope"></i> Email SO
                                </button>
                                <button type="button" class="btn btn-secondary" id="resetBtn">
                                    <i class="bi bi-arrow-clockwise"></i> Reset Form
                                </button>
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
                        <!-- Add Items Buttons -->
                        <div class="mb-3">
                            <label class="form-label">Add Items</label>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary" id="soAddMachineBtn">
                                    <i class="bi bi-gear"></i> Add Machine
                                </button>
                                <button type="button" class="btn btn-outline-success" id="soAddSpareBtn">
                                    <i class="bi bi-tools"></i> Add Spare Part
                                </button>
                            </div>
                        </div>

                        <!-- Items List -->
                        <div class="mb-3">
                            <label class="form-label">Items List</label>
                            <div id="soItemsList" class="border rounded p-2" style="min-height: 200px; max-height: 300px; overflow-y: auto; background-color: #f8f9fa;">
                                <div class="text-muted text-center py-4">
                                    <i class="bi bi-box fs-2"></i><br>
                                    <strong>No items added yet</strong><br>
                                    <small>Use the buttons above to add machines and spare parts</small>
                                </div>
                            </div>
                        </div>

                        <!-- Calculations -->
                        <div class="border rounded p-3" style="background-color: #f8f9fa;">
                            <h6 class="text-primary mb-3"><i class="bi bi-calculator"></i> Calculations</h6>

                            <div class="mb-3">
                                <label for="total_amount" class="form-label">Subtotal (₹)</label>
                                <input type="number" class="form-control" id="total_amount" name="total_amount" step="0.01" min="0" readonly style="background-color:#fff;">
                                <small class="text-muted">Automatically calculated from items</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="discount_percentage" class="form-label">Discount %</label>
                                        <input type="number" class="form-control" id="discount_percentage" name="discount_percentage"
                                            step="0.01" min="0" max="100" value="0" onchange="soCalcDiscount()">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="discount_amount" class="form-label">Discount Amount (₹)</label>
                                        <input type="number" class="form-control" id="discount_amount" name="discount_amount"
                                            step="0.01" min="0" value="0" onchange="soCalcDiscountPct()">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-0">
                                <label for="final_total" class="form-label"><strong>Final Total (₹)</strong></label>
                                <input type="number" class="form-control fw-bold text-success fs-5" name="final_total" id="final_total"
                                    step="0.01" readonly style="background-color:#fff; border:2px solid #28a745;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
         </form>
    </div>

    <!-- ROW 2: SALES ORDERS TABLE -->
    <div class="row mt-4">
        <div class="col-12">
            <!-- Sales Orders List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="bi bi-list"></i> All Sales Orders (<?php echo $totalRecords; ?>)</h5>
                    <small>Page <?php echo $page; ?> of <?php echo $totalPages; ?></small>
                </div>
                <div class="card-body">
                    <?php if ($so_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                <tr>
                                    <th>SO Number</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                    <th>Print</th>
                                    <th>Email</th>
                                </tr>
                                </thead>
                                <tbody id="soTableBody">
                                <?php while ($so = $so_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($so['so_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($so['customer_name']); ?></td>
                                        <td><?php echo formatDate($so['so_date']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $so['status'] == 'confirmed' ? 'success' : ($so['status'] == 'draft' ? 'secondary' : 'primary'); ?>">
                                                <?php echo ucwords($so['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary edit-so"
                                                    data-id="<?php echo $so['id']; ?>"
                                                    data-so_number="<?php echo htmlspecialchars($so['so_number']); ?>"
                                                    data-customer_name="<?php echo htmlspecialchars($so['customer_name']); ?>"
                                                    data-customer_id="<?php echo $so['customer_id']; ?>"
                                                    data-so_date="<?php echo $so['so_date']; ?>"
                                                    data-delivery_date="<?php echo $so['delivery_date']; ?>"
                                                    data-status="<?php echo $so['status']; ?>"
                                                    data-notes="<?php echo htmlspecialchars($so['notes']); ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <?php if (hasPermission('sales_orders', 'delete')): ?>
                                                <a href="?delete=<?php echo $so['id']; ?>" class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to delete this sales order?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="../docs/print_sales_order.php?id=<?php echo $so['id']; ?>"
                                               class="btn btn-sm btn-outline-info" target="_blank" title="Print SO">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-success email-so"
                                                    data-id="<?php echo $so['id']; ?>" title="Email SO">
                                                <i class="bi bi-envelope"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Sales Orders pagination">
                                <ul class="pagination justify-content-center" id="soPagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo ($page - 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo ($page + 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox display-1 text-muted"></i>
                            <p class="mt-3">No sales orders found.</p>
                            <?php if (!empty($search)): ?>
                                <a href="sales_orders.php" class="btn btn-outline-primary">Clear Search</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- SO: Add Machine Modal -->
    <div class="modal fade" id="soMachineModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-gear"></i> Add Machine</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="soMachineSelect" class="form-label">Select Machine *</label>
                        <select class="form-select" id="soMachineSelect">
                            <option value="">Choose Machine...</option>
                            <?php $machines->data_seek(0); while ($m = $machines->fetch_assoc()): ?>
                                <option value="<?php echo $m['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($m['name']); ?>"
                                        data-model="<?php echo htmlspecialchars($m['model']); ?>"
                                        data-category="<?php echo htmlspecialchars($m['category']); ?>"
                                        data-price="<?php echo $m['price']; ?>"
                                        data-valid_from="<?php echo $m['valid_from']; ?>"
                                        data-valid_to="<?php echo $m['valid_to']; ?>">
                                    <?php
                                    $display_price = $m['price'] > 0 ? " - ₹" . number_format($m['price'], 2) : " - No price set";
                                    echo htmlspecialchars($m['name'] . ' (' . $m['model'] . ')' . $display_price);
                                    ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <small class="text-muted">Prices from Price Master (current valid prices only)</small>
                    </div>
                    <div class="mb-3">
                        <label for="soMachineQty" class="form-label">Quantity *</label>
                        <input type="number" class="form-control" id="soMachineQty" min="1" value="1">
                    </div>
                    <div class="mb-3">
                        <label for="soMachinePrice" class="form-label">Unit Price (₹) *</label>
                        <input type="number" class="form-control" id="soMachinePrice" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div class="mb-3">
                        <label for="soMachineDesc" class="form-label">Description</label>
                        <textarea class="form-control" id="soMachineDesc" rows="3" placeholder="Machine specifications..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="soAddMachineToSO">Add Machine</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SO: Add Spare Modal -->
    <div class="modal fade" id="soSpareModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-tools"></i> Add Spare Part</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="soSpareSelect" class="form-label">Select Spare Part *</label>
                        <select class="form-select" id="soSpareSelect">
                            <option value="">Choose Spare Part...</option>
                            <?php $spares->data_seek(0); while ($s = $spares->fetch_assoc()): ?>
                                <option value="<?php echo $s['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($s['part_name']); ?>"
                                        data-code="<?php echo htmlspecialchars($s['part_code']); ?>"
                                        data-price="<?php echo $s['price']; ?>">
                                    <?php echo htmlspecialchars($s['part_name'] . ' (' . $s['part_code'] . ')'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="soSpareQty" class="form-label">Quantity *</label>
                        <input type="number" class="form-control" id="soSpareQty" min="1" value="1">
                    </div>
                    <div class="mb-3">
                        <label for="soSparePrice" class="form-label">Unit Price (₹) *</label>
                        <input type="number" class="form-control" id="soSparePrice" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div class="mb-3">
                        <label for="soSpareDesc" class="form-label">Description</label>
                        <textarea class="form-control" id="soSpareDesc" rows="3" placeholder="Spare part specifications..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="soAddSpareToSO">Add Spare Part</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Email SO Modal (reuse of quotation modal layout) -->
    <div class="modal fade" id="emailQuotationModal" tabindex="-1" aria-labelledby="emailQuotationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="emailQuotationModalLabel">Email Sales Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="emailQuotationForm">
                        <input type="hidden" id="emailQuotationId" name="sales_order_id">
                        <div class="mb-3">
                            <label for="recipient_email" class="form-label">Recipient Email</label>
                            <input type="email" class="form-control" id="recipient_email" name="recipient_email" required>
                        </div>
                        <div class="mb-3">
                            <label for="additional_emails" class="form-label">Additional Emails (comma-separated)</label>
                            <input type="text" class="form-control" id="additional_emails" name="additional_emails">
                        </div>
                        <div class="mb-3">
                            <label for="custom_message" class="form-label">Custom Message</label>
                            <textarea class="form-control" id="custom_message" name="custom_message" rows="4"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="sendEmailBtn">Send Email</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles specific to this page -->
<style>
    .readonly-field { background: #f8f9fa !important; pointer-events: none; }
    .table-hover tbody tr:hover { background-color: rgba(0,0,0,.075); }

    /* Ensure search input is properly styled and accessible */
    #soSearch {
        background-color: #fff !important;
        pointer-events: auto !important;
        cursor: text !important;
    }
    
    #soSearch:focus {
        border-color: #86b7fe !important;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
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

<script src="../js/sale_oreders.js"></script>

<?php include '../footer.php'; ?>
