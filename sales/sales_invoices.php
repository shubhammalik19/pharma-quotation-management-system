<?php


include '../header.php';
checkLogin();
include '../menu.php';

$message = '';
$prefix = "INV-";

$initial_invoice_number = generateInvoiceNumber($conn, $prefix);

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create_invoice' && hasPermission('sales_invoices', 'create')) {
            $invoice_number = sanitizeInput($_POST['invoice_number']);
            $customer_id = intval($_POST['customer_id'] ?? 0);
            $purchase_order_id = intval($_POST['purchase_order_id'] ?? 0);
            $invoice_date = sanitizeInput($_POST['invoice_date']);
            $due_date = sanitizeInput($_POST['due_date']);
            $status = sanitizeInput($_POST['status']);
            $notes = sanitizeInput($_POST['notes']);
            $subtotal = floatval($_POST['total_amount'] ?? 0);
            $discount_percentage = floatval($_POST['discount_percentage'] ?? 0);
            $discount_amount = floatval($_POST['discount_amount'] ?? 0);
            $tax_percentage = floatval($_POST['tax_percentage'] ?? 0);
            $tax_amount = floatval($_POST['tax_amount'] ?? 0);
            $total_amount = $subtotal - $discount_amount + $tax_amount;
            $final_total = $total_amount;

            // Validate customer and get additional details
            $customer_name = '';
            $customer_address = '';
            $customer_gstin = '';
            $customer_contact = '';
            
            
            if ($customer_id > 0) {
                $check_customer = $conn->query("SELECT company_name, address, gst_no, phone FROM customers WHERE id = $customer_id AND (entity_type = 'customer' OR entity_type = 'both')");
                if ($check_customer->num_rows > 0) {
                    $customer_data = $check_customer->fetch_assoc();
                    $customer_name = $customer_data['company_name'];
                    $customer_address = $customer_data['address'];
                    $customer_gstin = $customer_data['gst_no'];
                    $customer_contact = $customer_data['phone'];
                } else {
                    $message = showError('Selected customer does not exist or is not valid.');
                }
            } else {
                $message = showError('Please select a valid customer.');
            }
            
            if (empty($message)) {
                $sql = "INSERT INTO sales_invoices (invoice_number, customer_id, customer_name, customer_address, customer_gstin, customer_contact, purchase_order_id, invoice_date, due_date, subtotal, discount_percentage, discount_amount, tax_percentage, tax_amount, total_amount, final_total, status, notes, created_by) 
                        VALUES ('$invoice_number', $customer_id, '$customer_name', '$customer_address', '$customer_gstin', '$customer_contact', " . ($purchase_order_id ?: 'NULL') . ", '$invoice_date', '$due_date', $subtotal, $discount_percentage, $discount_amount, $tax_percentage, $tax_amount, $total_amount, $final_total, '$status', '$notes', {$_SESSION['user_id']})";
                
                if ($conn->query($sql)) {
                    $invoice_id = $conn->insert_id;
                    // Handle Invoice items
                    if (isset($_POST['items']) && is_array($_POST['items'])) {
                        foreach ($_POST['items'] as $item) {
                            $item_type = sanitizeInput($item['type']);
                            $item_id = intval($item['item_id']);
                            $item_name = sanitizeInput($item['name']);
                            $description = sanitizeInput($item['description']);
                            $hsn_string = sanitizeInput($item['description']);
                            $quantity = intval($item['quantity']);
                            $unit = 'Nos'; // Default unit, can be made dynamic
                            $unit_price = floatval($item['unit_price']);
                            $gst_rate = floatval($item['gst_rate'] ?? 18); // Default GST rate
                            $total_price = floatval($item['total_price']);
                            
                            // Extract HSN from "HSN: xxx"
                            $hsn_code = '';
                            if (preg_match('/HSN:\s*(\w+)/i', $hsn_string, $matches)) {
                                $hsn_code = $matches[1];
                            }
                            
                            // Handle empty HSN code
                            $hsn_value = empty($hsn_code) ? 'NULL' : "'$hsn_code'";

                            $item_sql = "INSERT INTO sales_invoice_items (invoice_id, item_type, item_id, item_name, description, hsn_code, quantity, unit, unit_price, gst_rate, total_price) 
                                         VALUES ($invoice_id, '$item_type', $item_id, '$item_name', '$description', $hsn_value, '$unit', $unit_price, $gst_rate, $total_price)";
                            if (!$conn->query($item_sql)) {
                                $message .= showError("Error saving item: " . $conn->error);
                            }
                        }
                    }
                    $message = showSuccess('Sales Invoice created successfully!');
                } else {
                    $message = showError('Error creating sales invoice: ' . $conn->error);
                }
            }
        } elseif ($_POST['action'] === 'update_invoice' && hasPermission('sales_invoices', 'edit')) {
            $invoice_id = intval($_POST['id']);
            $invoice_number = sanitizeInput($_POST['invoice_number']);
            $customer_id = intval($_POST['customer_id'] ?? 0);
            $purchase_order_id = intval($_POST['purchase_order_id'] ?? 0);
            $invoice_date = sanitizeInput($_POST['invoice_date']);
            $due_date = sanitizeInput($_POST['due_date']);
            $notes = sanitizeInput($_POST['notes']);
            $subtotal = floatval($_POST['total_amount'] ?? 0);
            $discount_percentage = floatval($_POST['discount_percentage'] ?? 0);
            $discount_amount = floatval($_POST['discount_amount'] ?? 0);
            $tax_percentage = floatval($_POST['tax_percentage'] ?? 0);
            $tax_amount = floatval($_POST['tax_amount'] ?? 0);
            $total_amount = $subtotal - $discount_amount + $tax_amount;
            $final_total = $total_amount;

            // Validate customer and get additional details
            $customer_name = '';
            $customer_address = '';
            $customer_gstin = '';
            $customer_contact = '';
            
            if ($customer_id > 0) {
                $check_customer = $conn->query("SELECT company_name, address, gst_no, phone FROM customers WHERE id = $customer_id AND (entity_type = 'customer' OR entity_type = 'both')");
                if ($check_customer->num_rows > 0) {
                    $customer_data = $check_customer->fetch_assoc();
                    $customer_name = $customer_data['company_name'];
                    $customer_address = $customer_data['address'];
                    $customer_gstin = $customer_data['gst_no'];
                    $customer_contact = $customer_data['phone'];
                } else {
                    $message = showError('Selected customer does not exist or is not valid.');
                }
            } else {
                $message = showError('Please select a valid customer.');
            }
            
            if (empty($message)) {
                $status = 'sent';
                $sql = "UPDATE sales_invoices SET 
                        invoice_number = '$invoice_number', 
                        customer_id = $customer_id, 
                        customer_name = '$customer_name', 
                        customer_address = '$customer_address',
                        customer_gstin = '$customer_gstin',
                        customer_contact = '$customer_contact',
                        purchase_order_id = " . ($purchase_order_id ?: 'NULL') . ",
                        invoice_date = '$invoice_date', 
                        due_date = '$due_date', 
                        subtotal = $subtotal, 
                        discount_percentage = $discount_percentage, 
                        discount_amount = $discount_amount, 
                        tax_percentage = $tax_percentage, 
                        tax_amount = $tax_amount, 
                        total_amount = $total_amount, 
                        final_total = $final_total, 
                        status = '$status', 
                        notes = '$notes' 
                        WHERE id = $invoice_id";
                
                if ($conn->query($sql)) {
                    // Delete existing items and insert new ones
                    $conn->query("DELETE FROM sales_invoice_items WHERE invoice_id = $invoice_id");
                    if (isset($_POST['items']) && is_array($_POST['items'])) {
                        foreach ($_POST['items'] as $item) {
                            $item_type = sanitizeInput($item['type']);
                            $item_id = intval($item['item_id']);
                            $item_name = sanitizeInput($item['name']);
                            $description = sanitizeInput($item['description']);
                            $hsn_string = sanitizeInput($item['description']);
                            $quantity = intval($item['quantity']);
                            $unit = 'Nos'; // Default unit
                            $unit_price = floatval($item['unit_price']);
                            $gst_rate = floatval($item['gst_rate'] ?? 18);
                            $total_price = floatval($item['total_price']);
                            
                            // Extract HSN from "HSN: xxx"
                            $hsn_code = '';
                            if (preg_match('/HSN:\s*(\w+)/i', $hsn_string, $matches)) {
                                $hsn_code = $matches[1];
                            }
                            
                            // Handle empty HSN code
                            $hsn_value = empty($hsn_code) ? 'NULL' : "'$hsn_code'";

                            $item_sql = "INSERT INTO sales_invoice_items (invoice_id, item_type, item_id, item_name, description, hsn_code, quantity, unit, unit_price, gst_rate, total_price) 
                                         VALUES ($invoice_id, '$item_type', $item_id, '$item_name', '$description', $hsn_value, '$unit', $unit_price, $gst_rate, $total_price)";
                            if (!$conn->query($item_sql)) {
                                $message .= showError("Error saving item: " . $conn->error);
                            }
                        }
                    }
                    $message = showSuccess('Sales Invoice updated successfully!');
                } else {
                    $message = showError('Error updating sales invoice: ' . $conn->error);
                }
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    if (!hasPermission('sales_invoices', 'delete')) {
        $message = showError("You don't have permission to delete sales invoices!");
    } else {
        $id = (int)$_GET['delete'];
        $conn->query("DELETE FROM sales_invoice_items WHERE invoice_id = $id");
        $sql = "DELETE FROM sales_invoices WHERE id = $id";
        if ($conn->query($sql)) {
            $message = showSuccess("Sales Invoice deleted successfully!");
        } else {
            $message = showError("Error deleting sales invoice: " . $conn->error);
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
    $where_clause = "WHERE si.customer_name LIKE '%$search%' OR si.invoice_number LIKE '%$search%' OR si.status LIKE '%$search%'";
}

// Count total records
$count_sql = "SELECT COUNT(*) as total FROM sales_invoices si $where_clause";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get machines and spares for item modals
$machines = $conn->query("
    SELECT m.id, m.name, m.model, m.category,
           COALESCE(pm.price, 0) as price,
           pm.valid_from, pm.valid_to
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
            <h2><i class="bi bi-receipt"></i> Sales Invoice Management</h2>
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
                        <label for="invoiceSearch" class="form-label">
                            <i class="bi bi-search"></i> Search Sales Invoices
                        </label>
                        <input type="text" class="form-control" id="invoiceSearch" 
                               placeholder="Search by invoice number or customer name..."
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
        <form method="POST" id="invoiceForm" class="row">
            <!-- Sales Invoice Form -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-receipt"></i> <span id="formTitle">Create Sales Invoice</span></h5>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="action" value="create_invoice" id="formAction">
                        <input type="hidden" name="id" id="invoiceId">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="invoice_number" class="form-label">Invoice Number *</label>
                                <input type="text" class="form-control" id="invoice_number" name="invoice_number" value="<?php echo $initial_invoice_number; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="invoice_date" class="form-label">Invoice Date *</label>
                                <input type="date" class="form-control" id="invoice_date" name="invoice_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="customer_name" class="form-label">Customer Name *</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" required autocomplete="off" placeholder="Type to search for a customer...">
                            <input type="hidden" id="customer_id" name="customer_id">
                        </div>

                        <div class="mb-3">
                            <label for="purchase_order_number" class="form-label">Purchase Order No.</label>
                            <input type="text" class="form-control" id="purchase_order_number" name="purchase_order_number"
                                autocomplete="off" placeholder="Type to search purchase order...">
                            <input type="hidden" id="purchase_order_id" name="purchase_order_id">
                            <small class="text-muted">Pick an existing purchase order to preload items & values.</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="draft">Draft</option>
                                    <option value="sent">Sent</option>
                                    <option value="paid">Paid</option>
                                    <option value="overdue">Overdue</option>
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
                            <button type="submit" class="btn btn-success" id="saveBtn"><i class="bi bi-plus-circle"></i> Save Invoice</button>
                            <button type="submit" class="btn btn-primary" id="updateBtn" style="display:none;"><i class="bi bi-check"></i> Update</button>
                            <button type="button" class="btn btn-warning" id="editBtn" style="display:none;"><i class="bi bi-pencil"></i> Edit</button>
                            <button type="button" class="btn btn-danger" id="deleteBtn" style="display:none;"><i class="bi bi-trash"></i> Delete</button>
                            <button type="button" class="btn btn-info" id="printBtn" style="display:none;" onclick="printInvoice()"><i class="bi bi-printer"></i> Print Invoice</button>
                            <button type="button" class="btn btn-success" id="emailBtn" style="display:none;"><i class="bi bi-envelope"></i> Email Invoice</button>
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
                                <button type="button" class="btn btn-outline-primary" id="invoiceAddMachineBtn"><i class="bi bi-gear"></i> Add Machine</button>
                                <button type="button" class="btn btn-outline-success" id="invoiceAddSpareBtn"><i class="bi bi-tools"></i> Add Spare Part</button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Items List</label>
                            <div id="invoiceItemsList" class="border rounded p-2" style="min-height: 200px; max-height: 300px; overflow-y: auto; background-color: #f8f9fa;">
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
                                    <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" step="0.01" min="0" max="100" value="0" onchange="invoiceCalcDiscount()">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="discount_amount" class="form-label">Discount Amount (₹)</label>
                                    <input type="number" class="form-control" id="discount_amount" name="discount_amount" step="0.01" min="0" value="0" onchange="invoiceCalcDiscountPct()">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="tax_percentage" class="form-label">Tax %</label>
                                    <input type="number" class="form-control" id="tax_percentage" name="tax_percentage" step="0.01" min="0" value="18" onchange="invoiceCalcTax()">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tax_amount" class="form-label">Tax Amount (₹)</label>
                                    <input type="number" class="form-control" id="tax_amount" name="tax_amount" step="0.01" min="0" value="0" readonly style="background-color:#fff;">
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
        <!-- Sales Invoices List -->
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="bi bi-list"></i> All Sales Invoices (<?php echo $total_records; ?>)</h5>
                    <small>Page <?php echo $page; ?> of <?php echo $total_pages; ?></small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Invoice Number</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT si.* FROM sales_invoices si $where_clause ORDER BY si.created_at DESC LIMIT $records_per_page OFFSET $offset";
                                $result = $conn->query($sql);
                                
                                if ($result && $result->num_rows > 0):
                                    while ($row = $result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['invoice_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                    <td><?php echo formatDate($row['invoice_date']); ?></td>
                                    <td>₹<?php echo number_format($row['final_total'] ?? $row['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['status'] == 'paid' ? 'success' : ($row['status'] == 'draft' ? 'secondary' : ($row['status'] == 'overdue' ? 'danger' : 'primary')); ?>">
                                            <?php echo ucwords($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-invoice" data-id="<?php echo $row['id']; ?>">
                                            <i class="bi bi-pencil"></i> View/Edit
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="printInvoice(<?php echo $row['id']; ?>)">
                                            <i class="bi bi-printer"></i> Print
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-success email-invoice" data-id="<?php echo $row['id']; ?>">
                                            <i class="bi bi-envelope"></i> Email
                                        </button>
                                        <?php if (hasPermission('sales_invoices', 'delete')): ?>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this sales invoice?')">
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
                                        <p class="mt-3">No sales invoices found.</p>
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
<div class="modal fade" id="invoiceMachineModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-gear"></i> Add Machine</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="invoiceMachineSelect" class="form-label">Select Machine *</label>
                    <select class="form-select" id="invoiceMachineSelect">
                        <option value="">Choose Machine...</option>
                        <?php $machines->data_seek(0); while ($m = $machines->fetch_assoc()): ?>
                            <option value="<?php echo $m['id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($m['name']); ?>" 
                                    data-model="<?php echo htmlspecialchars($m['model']); ?>"
                                    data-category="<?php echo htmlspecialchars($m['category']); ?>"
                                    data-price="<?php echo $m['price']; ?>"
                                    data-valid_from="<?php echo $m['valid_from']; ?>"
                                    data-valid_to="<?php echo $m['valid_to']; ?>">
                                <?php echo htmlspecialchars($m['name'] . ' (' . $m['model'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3"><label for="invoiceMachineQty" class="form-label">Quantity *</label><input type="number" class="form-control" id="invoiceMachineQty" min="1" value="1"></div>
                <div class="mb-3"><label for="invoiceMachinePrice" class="form-label">Unit Price (₹) *</label><input type="number" class="form-control" id="invoiceMachinePrice" step="0.01" min="0" placeholder="0.00"></div>
                <div class="mb-3"><label for="invoiceMachineGST" class="form-label">GST Rate (%)</label><input type="number" class="form-control" id="invoiceMachineGST" step="0.01" min="0" value="18"></div>
                <div class="mb-3"><label for="invoiceMachineDesc" class="form-label">Description</label><textarea class="form-control" id="invoiceMachineDesc" rows="3"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="invoiceAddMachineToInvoice">Add Machine</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Spare Modal -->
<div class="modal fade" id="invoiceSpareModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-tools"></i> Add Spare Part</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="invoiceSpareSelect" class="form-label">Select Spare Part *</label>
                    <select class="form-select" id="invoiceSpareSelect">
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
                <div class="mb-3"><label for="invoiceSpareQty" class="form-label">Quantity *</label><input type="number" class="form-control" id="invoiceSpareQty" min="1" value="1"></div>
                <div class="mb-3"><label for="invoiceSparePrice" class="form-label">Unit Price (₹) *</label><input type="number" class="form-control" id="invoiceSparePrice" step="0.01" min="0" placeholder="0.00"></div>
                <div class="mb-3"><label for="invoiceSpareGST" class="form-label">GST Rate (%)</label><input type="number" class="form-control" id="invoiceSpareGST" step="0.01" min="0" value="18"></div>
                <div class="mb-3"><label for="invoiceSpareDesc" class="form-label">Description</label><textarea class="form-control" id="invoiceSpareDesc" rows="3"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="invoiceAddSpareToInvoice">Add Spare Part</button>
            </div>
        </div>
    </div>
</div>

<!-- Email Invoice Modal -->
<div class="modal fade" id="emailInvoiceModal" tabindex="-1" aria-labelledby="emailInvoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailInvoiceModalLabel">Email Sales Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="emailInvoiceForm">
                    <input type="hidden" id="emailInvoiceId" name="sales_invoice_id">
                    <div class="mb-3">
                        <label for="invoice_recipient_email" class="form-label">Primary Recipient Email *</label>
                        <input type="email" class="form-control" id="invoice_recipient_email" name="recipient_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="invoice_additional_emails" class="form-label">Additional Emails (Optional)</label>
                        <input type="text" class="form-control" id="invoice_additional_emails" name="additional_emails" placeholder="email1@example.com, email2@example.com">
                        <small class="text-muted">Separate multiple emails with commas</small>
                    </div>
                    <div class="mb-3">
                        <label for="invoice_custom_message" class="form-label">Custom Message (Optional)</label>
                        <textarea class="form-control" id="invoice_custom_message" name="custom_message" rows="4" placeholder="Add a custom message to the email..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="sendInvoiceEmailBtn">Send Email</button>
            </div>
        </div>
    </div>
</div>

<!-- Styles specific to this page -->
<style>
    .readonly-field { background: #f8f9fa !important; pointer-events: none; }
    .table-hover tbody tr:hover { background-color: rgba(0,0,0,.075); }

    /* Ensure search input is always functional */
    #invoiceSearch {
        pointer-events: auto !important;
        background: #fff !important;
        border: 1px solid #ced4da !important;
    }
    
    #invoiceSearch:focus {
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

<script src="../js/sales_invoices.js"></script>

<?php include '../footer.php'; ?>