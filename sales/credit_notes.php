<?php
include '../header.php';
checkLogin();
include '../menu.php';

$prefix = "CN-";

// Generate Credit Note Number
function generateCNNumber($conn) {
    global $prefix;
    $result = $conn->query("SELECT credit_note_number FROM credit_notes ORDER BY id DESC LIMIT 1");

    $max_number = 0;
    if ($result && $row = $result->fetch_assoc()) {
        $latest_cn = $row['credit_note_number'];
        if (preg_match('/(\d+)$/', $latest_cn, $matches)) {
            $max_number = (int)$matches[1];
        }
    }

    $new_number = $max_number + 1;
    return $prefix . date('Y') . '-' . str_pad($new_number, 5, '0', STR_PAD_LEFT);
}

$initial_cn_number = generateCNNumber($conn);

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create_cn' && hasPermission('credit_notes', 'create')) {
            $cn_number = sanitizeInput($_POST['cn_number']);
            $customer_name = sanitizeInput($_POST['customer_name']);
            $customer_id = intval($_POST['customer_id'] ?? 0);
            $original_invoice = sanitizeInput($_POST['original_invoice']);
            $credit_date = sanitizeInput($_POST['credit_date']);
            $total_amount = floatval($_POST['total_amount']);
            $reason = sanitizeInput($_POST['reason']);
            $status = sanitizeInput($_POST['status']);
            
            // Get customer details including address and GSTIN
            $customer_address = '';
            $customer_gstin = '';
            
            if ($customer_id > 0) {
                $check_customer = $conn->query("SELECT id, company_name, address, gst_no FROM customers WHERE id = $customer_id AND (entity_type = 'customer' OR entity_type = 'both')");
                if ($check_customer->num_rows === 0) {
                    redirectWithError('Selected customer does not exist or is not valid.');
                } else {
                    $customer_data = $check_customer->fetch_assoc();
                    $customer_name = $customer_data['company_name'];
                    $customer_address = $customer_data['address'] ?? '';
                    $customer_gstin = $customer_data['gst_no'] ?? '';
                }
            } else {
                redirectWithError('Please select a valid customer.');
            }
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                $sql = "INSERT INTO credit_notes (credit_note_number, customer_id, customer_name, customer_address, customer_gstin, original_invoice, credit_date, total_amount, reason, status, created_by) 
                        VALUES ('$cn_number', $customer_id, '$customer_name', '$customer_address', '$customer_gstin', '$original_invoice', '$credit_date', $total_amount, '$reason', '$status', {$_SESSION['user_id']})";
                
                if (!$conn->query($sql)) {
                    throw new Exception('Error creating credit note: ' . $conn->error);
                }
                
                // Commit transaction
                $conn->commit();
                redirectWithSuccess('Credit Note created successfully!');
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                redirectWithError($e->getMessage());
            }
        } elseif ($_POST['action'] === 'update_cn' && hasPermission('credit_notes', 'edit')) {
            $cn_id = intval($_POST['id']);
            $cn_number = sanitizeInput($_POST['cn_number']);
            $customer_name = sanitizeInput($_POST['customer_name']);
            $customer_id = intval($_POST['customer_id'] ?? 0);
            $original_invoice = sanitizeInput($_POST['original_invoice']);
            $credit_date = sanitizeInput($_POST['credit_date']);
            $total_amount = floatval($_POST['total_amount']);
            $reason = sanitizeInput($_POST['reason']);
            $status = sanitizeInput($_POST['status']);
            
            // Get customer details including address and GSTIN
            $customer_address = '';
            $customer_gstin = '';
            
            if ($customer_id > 0) {
                $check_customer = $conn->query("SELECT id, company_name, address, gst_no FROM customers WHERE id = $customer_id AND (entity_type = 'customer' OR entity_type = 'both')");
                if ($check_customer->num_rows === 0) {
                    redirectWithError('Selected customer does not exist or is not valid.');
                } else {
                    $customer_data = $check_customer->fetch_assoc();
                    $customer_name = $customer_data['company_name'];
                    $customer_address = $customer_data['address'] ?? '';
                    $customer_gstin = $customer_data['gst_no'] ?? '';
                }
            } else {
                redirectWithError('Please select a valid customer.');
            }
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                $sql = "UPDATE credit_notes SET 
                        credit_note_number = '$cn_number', 
                        customer_id = $customer_id,
                        customer_name = '$customer_name', 
                        customer_address = '$customer_address',
                        customer_gstin = '$customer_gstin',
                        original_invoice = '$original_invoice', 
                        credit_date = '$credit_date', 
                        total_amount = $total_amount, 
                        reason = '$reason', 
                        status = '$status' 
                        WHERE id = $cn_id";
                
                if (!$conn->query($sql)) {
                    throw new Exception('Error updating credit note: ' . $conn->error);
                }
                
                // Commit transaction
                $conn->commit();
                redirectWithSuccess('Credit Note updated successfully!');
                
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
    if (!hasPermission('credit_notes', 'delete')) {
        redirectWithError("You don't have permission to delete credit notes!");
    } else {
        $id = (int)$_GET['delete'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            $sql = "DELETE FROM credit_notes WHERE id = $id";
            if (!$conn->query($sql)) {
                throw new Exception("Error deleting credit note: " . $conn->error);
            }
            
            // Commit transaction
            $conn->commit();
            redirectWithSuccess("Credit Note deleted successfully!");
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            redirectWithError($e->getMessage());
        }
    }
}

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = '';
$whereClause = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = sanitizeInput($_GET['search']);
    $whereClause = "WHERE cn.credit_note_number LIKE '%$search%' OR cn.customer_name LIKE '%$search%'";
}

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM credit_notes cn $whereClause";
$countResult = $conn->query($countSql);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Get all credit notes with pagination
$cn_sql = "SELECT cn.*, u.full_name as created_by_name 
           FROM credit_notes cn 
           LEFT JOIN users u ON cn.created_by = u.id 
           $whereClause
           ORDER BY cn.created_at DESC
           LIMIT $limit OFFSET $offset";
$cn_result = $conn->query($cn_sql);
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-arrow-counterclockwise"></i> Credit Notes</h2>
            <hr>
        </div>
    </div>
    
    <?php echo getAllMessages(); ?>
    
    <!-- Search Box -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="search-box">
                <div class="row">
                    <div class="col-md-6">
                        <label for="cnSearch" class="form-label">
                            <i class="bi bi-search"></i> Search Credit Notes
                        </label>
                        <input type="text" class="form-control" id="cnSearch" 
                               placeholder="Search by CN number or customer name..."
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
        <!-- Credit Note Form -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-arrow-counterclockwise"></i> <span id="formTitle">Credit Note Details</span></h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="cnForm">
                        <input type="hidden" name="action" value="create_cn" id="formAction">
                        <input type="hidden" name="id" id="cnId">
                        
                        <div class="mb-3">
                            <label for="cn_number" class="form-label">Credit Note Number *</label>
                            <input type="text" class="form-control" id="cn_number" name="cn_number" 
                                   value="<?php echo $initial_cn_number; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="credit_date" class="form-label">Credit Date *</label>
                            <input type="date" class="form-control" id="credit_date" name="credit_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="customer_name" class="form-label">Customer Name *</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" required autocomplete="off" placeholder="Type to search for a customer...">
                            <input type="hidden" id="customer_id" name="customer_id">
                        </div>
                        
                        <div class="mb-3">
                            <label for="original_invoice" class="form-label">Original Invoice</label>
                            <input type="text" class="form-control" id="original_invoice" name="original_invoice" 
                                   placeholder="e.g., INV-2025-001">
                        </div>
                        
                        <div class="mb-3">
                            <label for="total_amount" class="form-label">Credit Amount (₹) *</label>
                            <input type="number" class="form-control" id="total_amount" name="total_amount" 
                                   step="0.01" min="0" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Credit *</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" required 
                                      placeholder="e.g., Product return, Billing error, Discount adjustment"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="draft">Draft</option>
                                <option value="issued">Issued</option>
                                <option value="applied">Applied</option>
                            </select>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-success" id="saveBtn">
                                <i class="bi bi-plus-circle"></i> Save Credit Note
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
                            <button type="button" class="btn btn-info" id="printBtn" style="display:none;" onclick="printCreditNote()">
                                <i class="bi bi-printer"></i> Print CN
                            </button>
                            <button type="button" class="btn btn-success" id="emailBtn" style="display:none;">
                                <i class="bi bi-envelope"></i> Email CN
                            </button>
                            <button type="button" class="btn btn-secondary" id="resetBtn">
                                <i class="bi bi-arrow-clockwise"></i> Reset Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Credit Notes List -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="bi bi-list"></i> All Credit Notes (<?php echo $totalRecords; ?>)</h5>
                    <small>Page <?php echo $page; ?> of <?php echo $totalPages; ?></small>
                </div>
                <div class="card-body">
                    <?php if ($cn_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>CN Number</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($cn = $cn_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($cn['credit_note_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($cn['customer_name']); ?></td>
                                            <td><?php echo formatDate($cn['credit_date']); ?></td>
                                            <td>₹<?php echo number_format($cn['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $cn['status'] == 'applied' ? 'success' : ($cn['status'] == 'draft' ? 'secondary' : 'primary'); ?>">
                                                    <?php echo ucwords($cn['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary edit-cn" 
                                                        data-id="<?php echo $cn['id']; ?>"
                                                        data-cn_number="<?php echo htmlspecialchars($cn['credit_note_number']); ?>"
                                                        data-customer_name="<?php echo htmlspecialchars($cn['customer_name']); ?>"
                                                        data-customer_id="<?php echo $cn['customer_id']; ?>"
                                                        data-credit_date="<?php echo $cn['credit_date']; ?>"
                                                        data-original_invoice="<?php echo htmlspecialchars($cn['original_invoice']); ?>"
                                                        data-total_amount="<?php echo $cn['total_amount']; ?>"
                                                        data-reason="<?php echo htmlspecialchars($cn['reason']); ?>"
                                                        data-status="<?php echo $cn['status']; ?>">
                                                    <i class="bi bi-pencil"></i> View/Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-info" onclick="printCreditNote(<?php echo $cn['id']; ?>)">
                                                    <i class="bi bi-printer"></i> Print
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-success email-cn" data-id="<?php echo $cn['id']; ?>">
                                                    <i class="bi bi-envelope"></i> Email
                                                </button>
                                                <?php if (hasPermission('credit_notes', 'delete')): ?>
                                                <a href="?delete=<?php echo $cn['id']; ?>" class="btn btn-sm btn-outline-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this credit note?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Credit Notes pagination">
                                <ul class="pagination justify-content-center">
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
                            <p class="mt-3">No credit notes found.</p>
                            <?php if (!empty($search)): ?>
                                <a href="credit_notes.php" class="btn btn-outline-primary">Clear Search</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Email Credit Note Modal -->
<div class="modal fade" id="emailCreditNoteModal" tabindex="-1" aria-labelledby="emailCreditNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailCreditNoteModalLabel">Email Credit Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="emailCreditNoteForm">
                    <input type="hidden" id="emailCreditNoteId" name="credit_note_id">
                    <div class="mb-3">
                        <label for="cn_recipient_email" class="form-label">Primary Recipient Email *</label>
                        <input type="email" class="form-control" id="cn_recipient_email" name="recipient_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="cn_additional_emails" class="form-label">Additional Emails (Optional)</label>
                        <input type="text" class="form-control" id="cn_additional_emails" name="additional_emails" placeholder="email1@example.com, email2@example.com">
                        <small class="text-muted">Separate multiple emails with commas</small>
                    </div>
                    <div class="mb-3">
                        <label for="cn_custom_message" class="form-label">Custom Message (Optional)</label>
                        <textarea class="form-control" id="cn_custom_message" name="custom_message" rows="4" placeholder="Add a custom message to the email..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="sendCreditNoteEmailBtn">Send Email</button>
            </div>
        </div>
    </div>
</div>

<!-- Print function -->
<script>
function printCreditNote(cnId) {
    if (!cnId) {
        cnId = $('#cnId').val();
    }
    if (cnId) {
        window.open(`../docs/print_credit_note.php?id=${cnId}`, '_blank');
    } else {
        alert('Please select a credit note to print');
    }
}
</script>

<!-- Include separate JavaScript file -->
<script src="../js/credit_notes.js"></script>

<?php include '../footer.php'; ?>