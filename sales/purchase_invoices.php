<?php

include '../header.php';
checkLogin();
include '../menu.php';

$prefix = "PI-";

// Get initial PI number
$initial_pi_number = generatePurchaseInvoiceNumber($conn, $prefix);

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create_pi' && hasPermission('purchase_invoices', 'create')) {
            $pi_data = [
                'pi_number' => sanitizeInput($_POST['pi_number']),
                'vendor_id' => intval($_POST['vendor_id'] ?? 0),
                'purchase_order_id' => intval($_POST['purchase_order_id'] ?? 0),
                'pi_date' => sanitizeInput($_POST['pi_date']),
                'due_date' => sanitizeInput($_POST['due_date']),
                'status' => sanitizeInput($_POST['status']),
                'notes' => sanitizeInput($_POST['notes']),
                'total_amount' => floatval($_POST['total_amount'] ?? 0),
                'discount_percentage' => floatval($_POST['discount_percentage'] ?? 0),
                'discount_amount' => floatval($_POST['discount_amount'] ?? 0),
                'final_total' => floatval($_POST['total_amount'] ?? 0) - floatval($_POST['discount_amount'] ?? 0),
                'created_by' => $_SESSION['user_id'],
                'items' => $_POST['items'] ?? []
            ];
            
            $result = createPurchaseInvoice($conn, $pi_data);
            if ($result['success']) {
                redirectWithSuccess($result['message']);
            } else {
                redirectWithError($result['message']);
            }
            
        } elseif ($_POST['action'] === 'update_pi' && hasPermission('purchase_invoices', 'edit')) {
            $pi_id = intval($_POST['id']);
            $pi_data = [
                'pi_number' => sanitizeInput($_POST['pi_number']),
                'vendor_id' => intval($_POST['vendor_id'] ?? 0),
                'purchase_order_id' => intval($_POST['purchase_order_id'] ?? 0),
                'pi_date' => sanitizeInput($_POST['pi_date']),
                'due_date' => sanitizeInput($_POST['due_date']),
                'status' => sanitizeInput($_POST['status']),
                'notes' => sanitizeInput($_POST['notes']),
                'total_amount' => floatval($_POST['total_amount'] ?? 0),
                'discount_percentage' => floatval($_POST['discount_percentage'] ?? 0),
                'discount_amount' => floatval($_POST['discount_amount'] ?? 0),
                'final_total' => floatval($_POST['total_amount'] ?? 0) - floatval($_POST['discount_amount'] ?? 0),
                'items' => $_POST['items'] ?? []
            ];
            
            $result = updatePurchaseInvoice($conn, $pi_id, $pi_data);
            if ($result['success']) {
                redirectWithSuccess($result['message']);
            } else {
                redirectWithError($result['message']);
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    if (!hasPermission('purchase_invoices', 'delete')) {
        redirectWithError("You don't have permission to delete purchase invoices!");
    } else {
        $id = (int)$_GET['delete'];
        $result = deletePurchaseInvoice($conn, $id);
        if ($result['success']) {
            redirectWithSuccess($result['message']);
        } else {
            redirectWithError($result['message']);
        }
    }
}

// Get search parameter
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;

// Build search query using common function
$search_fields = ['vendor_name', 'pi_number', 'status'];
$where_clause = '';
if (!empty($search)) {
    $search_query = buildSearchQuery($search, $search_fields, 'pi');
    $where_clause = "WHERE $search_query";
}

// Get pagination data
$count_sql = "SELECT COUNT(*) as total FROM purchase_invoices pi $where_clause";
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
            <h2><i class="bi bi-receipt-cutoff"></i> Purchase Invoice Management</h2>
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
                        <label for="piSearch" class="form-label">
                            <i class="bi bi-search"></i> Search Purchase Invoices
                        </label>
                        <input type="text" class="form-control" id="piSearch" 
                               placeholder="Search by PI number or vendor name..."
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
        <form method="POST" id="piForm" class="row">
            <!-- Purchase Invoice Form -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-receipt-cutoff"></i> <span id="formTitle">Create Purchase Invoice</span></h5>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="action" value="create_pi" id="formAction">
                        <input type="hidden" name="id" id="piId">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="pi_number" class="form-label">PI Number *</label>
                                <input type="text" class="form-control" id="pi_number" name="pi_number" value="<?php echo $initial_pi_number; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="pi_date" class="form-label">Invoice Date *</label>
                                <input type="date" class="form-control" id="pi_date" name="pi_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="vendor_name" class="form-label">Vendor Name *</label>
                            <input type="text" class="form-control" id="vendor_name" name="vendor_name" required autocomplete="off" placeholder="Type to search for a vendor...">
                            <input type="hidden" id="vendor_id" name="vendor_id">
                        </div>

                        <div class="mb-3">
                            <label for="purchase_order_number" class="form-label">Purchase Order No.</label>
                            <input type="text" class="form-control" id="purchase_order_number" name="purchase_order_number"
                                autocomplete="off" placeholder="Type to search purchase order...">
                            <input type="hidden" id="purchase_order_id" name="purchase_order_id">
                            <small class="text-muted">Pick an existing purchase order to preload items &amp; values.</small>
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
                                    <option value="pending">Pending</option>
                                    <option value="paid">Paid</option>
                                    <option value="partially_paid">Partially Paid</option>
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
                            <button type="submit" class="btn btn-success" id="saveBtn"><i class="bi bi-plus-circle"></i> Save Purchase Invoice</button>
                            <button type="submit" class="btn btn-primary" id="updateBtn" style="display:none;"><i class="bi bi-check"></i> Update</button>
                            <button type="button" class="btn btn-warning" id="editBtn" style="display:none;"><i class="bi bi-pencil"></i> Edit</button>
                            <button type="button" class="btn btn-danger" id="deleteBtn" style="display:none;"><i class="bi bi-trash"></i> Delete</button>
                            <button type="button" class="btn btn-info" id="printBtn" style="display:none;" onclick="printPI()"><i class="bi bi-printer"></i> Print Invoice</button>
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
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-outline-primary" id="piAddMachineBtn">
                                    <i class="bi bi-gear"></i> Add Machine
                                </button>
                                <button type="button" class="btn btn-outline-success" id="piAddSpareBtn">
                                    <i class="bi bi-tools"></i> Add Spare (Separate)
                                </button>
                                <button type="button" class="btn btn-outline-info" id="piAddSpareToMachineBtn">
                                    <i class="bi bi-link-45deg"></i> Add Spare to Machine
                                </button>
                            </div>
                            <small class="text-muted">
                                <strong>Separate:</strong> Independent spare parts | 
                                <strong>To Machine:</strong> Spares linked to specific machines
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Items List</label>
                            <div id="piItemsList" class="border rounded p-2" style="min-height: 200px; max-height: 300px; overflow-y: auto; background-color: #f8f9fa;">
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
                                    <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" step="0.01" min="0" max="100" value="0" onchange="piCalcDiscount()">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="discount_amount" class="form-label">Discount Amount (₹)</label>
                                    <input type="number" class="form-control" id="discount_amount" name="discount_amount" step="0.01" min="0" value="0" onchange="piCalcDiscountPct()">
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
        <!-- Purchase Invoices List -->
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="bi bi-list"></i> All Purchase Invoices (<?php echo $total_records; ?>)</h5>
                    <small>Page <?php echo $pagination['current_page']; ?> of <?php echo $pagination['total_pages']; ?></small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Invoice Number</th>
                                    <th>Vendor</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT pi.* FROM purchase_invoices pi $where_clause ORDER BY pi.created_at DESC LIMIT {$pagination['records_per_page']} OFFSET {$pagination['offset']}";
                                $result = $conn->query($sql);
                                
                                if ($result && $result->num_rows > 0):
                                    while ($row = $result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['pi_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['vendor_name']); ?></td>
                                    <td><?php echo formatDate($row['pi_date']); ?></td>
                                    <td>₹<?php echo number_format($row['final_total'] ?? 0, 2); ?></td>
                                    <td>
                                        <span class="badge <?php echo getStatusBadgeClass($row['status']); ?>">
                                            <?php echo formatStatusForDisplay($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-pi" data-id="<?php echo $row['id']; ?>">
                                            <i class="bi bi-pencil"></i> View/Edit
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="printPI(<?php echo $row['id']; ?>)">
                                            <i class="bi bi-printer"></i> Print
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-success email-pi" data-id="<?php echo $row['id']; ?>">
                                            <i class="bi bi-envelope"></i> Email
                                        </button>
                                        <?php if (hasPermission('purchase_invoices', 'delete')): ?>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this purchase invoice?')">
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
                                        <p class="mt-3">No purchase invoices found.</p>
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
<div class="modal fade" id="piMachineModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-gear"></i> Add Machine with Spares</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="piMachineSelect" class="form-label">Select Machine *</label>
                    <select class="form-select" id="piMachineSelect">
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
                        <label for="piMachineQty" class="form-label">Quantity *</label>
                        <input type="number" class="form-control" id="piMachineQty" min="1" value="1">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="piMachinePrice" class="form-label">Unit Price (₹) *</label>
                        <input type="number" class="form-control" id="piMachinePrice" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="piMachineDesc" class="form-label">Description</label>
                    <textarea class="form-control" id="piMachineDesc" rows="2"></textarea>
                </div>

                <!-- Related Spares Section -->
                <div id="machineSparesList" class="mt-4" style="display: none;">
                    <hr>
                    <h6 class="text-primary"><i class="bi bi-tools"></i> Related Spare Parts</h6>
                    <p class="text-muted small">Select spare parts to purchase along with this machine:</p>
                    <div id="sparePartsContainer" class="border rounded p-3" style="max-height: 200px; overflow-y: auto; background-color: #f8f9fa;">
                        <!-- Spare parts will be loaded here -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="piAddMachineToPI">Add Machine</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Spare Modal -->
<div class="modal fade" id="piSpareModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-tools"></i> Add Spare Part (Separate)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> This spare will be added as an independent item, not linked to any machine.
                </div>
                <div class="mb-3">
                    <label for="piSpareSelect" class="form-label">Select Spare Part *</label>
                    <select class="form-select" id="piSpareSelect">
                        <option value="">Choose Spare Part...</option>
                        <?php $spares->data_seek(0); while ($s = $spares->fetch_assoc()): ?>
                            <option value="<?php echo $s['id']; ?>" data-name="<?php echo htmlspecialchars($s['part_name']); ?>" data-price="<?php echo $s['price']; ?>">
                                <?php echo htmlspecialchars($s['part_name'] . ' (' . $s['part_code'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="piSpareQty" class="form-label">Quantity *</label>
                    <input type="number" class="form-control" id="piSpareQty" min="1" value="1">
                </div>
                <div class="mb-3">
                    <label for="piSparePrice" class="form-label">Unit Price (₹) *</label>
                    <input type="number" class="form-control" id="piSparePrice" step="0.01" min="0" placeholder="0.00">
                </div>
                <div class="mb-3">
                    <label for="piSpareDesc" class="form-label">Description</label>
                    <textarea class="form-control" id="piSpareDesc" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="piAddSpareToPI">Add Spare Part</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Spare to Machine Modal -->
<div class="modal fade" id="piSpareToMachineModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-link-45deg"></i> Add Spare to Machine</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> This spare will be linked to a specific machine. First select a machine that already exists in your invoice.
                </div>
                
                <div class="mb-3">
                    <label for="piTargetMachine" class="form-label">Select Target Machine *</label>
                    <select class="form-select" id="piTargetMachine">
                        <option value="">First add a machine to your invoice...</option>
                    </select>
                    <small class="text-muted">Only machines already added to this invoice are shown</small>
                </div>
                
                <div class="mb-3">
                    <label for="piSpareToMachineSelect" class="form-label">Select Spare Part *</label>
                    <select class="form-select" id="piSpareToMachineSelect">
                        <option value="">Choose Spare Part...</option>
                        <?php $spares->data_seek(0); while ($s = $spares->fetch_assoc()): ?>
                            <option value="<?php echo $s['id']; ?>" data-name="<?php echo htmlspecialchars($s['part_name']); ?>" data-price="<?php echo $s['price']; ?>">
                                <?php echo htmlspecialchars($s['part_name'] . ' (' . $s['part_code'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="piSpareToMachineQty" class="form-label">Quantity *</label>
                        <input type="number" class="form-control" id="piSpareToMachineQty" min="1" value="1">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="piSpareToMachinePrice" class="form-label">Unit Price (₹) *</label>
                        <input type="number" class="form-control" id="piSpareToMachinePrice" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="piSpareToMachineDesc" class="form-label">Description</label>
                    <textarea class="form-control" id="piSpareToMachineDesc" rows="2" placeholder="Additional notes for this spare part..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info" id="piAddSpareToMachineToPI">Link Spare to Machine</button>
            </div>
        </div>
    </div>
</div>

<!-- Email PI Modal -->
<div class="modal fade" id="emailPIModal" tabindex="-1" aria-labelledby="emailPIModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailPIModalLabel">Email Purchase Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="emailPIForm">
                    <input type="hidden" id="emailPIId" name="purchase_invoice_id">
                    <div class="mb-3">
                        <label for="pi_recipient_email" class="form-label">Primary Recipient Email *</label>
                        <input type="email" class="form-control" id="pi_recipient_email" name="recipient_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="pi_additional_emails" class="form-label">Additional Emails (Optional)</label>
                        <input type="text" class="form-control" id="pi_additional_emails" name="additional_emails" placeholder="email1@example.com, email2@example.com">
                        <small class="text-muted">Separate multiple emails with commas</small>
                    </div>
                    <div class="mb-3">
                        <label for="pi_custom_message" class="form-label">Custom Message (Optional)</label>
                        <textarea class="form-control" id="pi_custom_message" name="custom_message" rows="4" placeholder="Add a custom message to the email..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="sendPIEmailBtn">Send Email</button>
            </div>
        </div>
    </div>
</div>

<!-- Styles specific to this page -->
<style>
    .readonly-field { background: #f8f9fa !important; pointer-events: none; }
    .table-hover tbody tr:hover { background-color: rgba(0,0,0,.075); }

    /* Ensure search input is always functional */
    #piSearch {
        pointer-events: auto !important;
        background: #fff !important;
        border: 1px solid #ced4da !important;
    }
    
    #piSearch:focus {
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

    /* Spare parts selection styling */
    .spare-item {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        padding: 10px;
        margin-bottom: 8px;
        background: white;
    }
    .spare-item:hover {
        background-color: #f8f9fa;
    }
    .spare-item.selected {
        border-color: #0d6efd;
        background-color: rgba(13, 110, 253, 0.1);
    }

    /* Machine grouping styles */
    .machine-group {
        border-left: 4px solid #0d6efd !important;
    }
    .linked-spares {
        border-left: 3px solid #17a2b8;
        background: rgba(23, 162, 184, 0.05);
        border-radius: 0.375rem;
        padding: 10px;
    }
    .separate-spares {
        border-left: 3px solid #28a745;
        background: rgba(40, 167, 69, 0.05);
        border-radius: 0.375rem;
        padding: 10px;
    }
</style>

<script src="../js/purchase_invoices.js"></script>

<?php include '../footer.php'; ?>
