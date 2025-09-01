<?php
include '../header.php';
checkLogin();
include '../menu.php';

$message = '';
$prefix = "DN-";

// Generate Debit Note Number
function generateDNNumber($conn) {
    global $prefix;
    $result = $conn->query("SELECT debit_note_number FROM debit_notes ORDER BY id DESC LIMIT 1");

    $max_number = 0;
    if ($result && $row = $result->fetch_assoc()) {
        $latest_dn = $row['debit_note_number'];
        if (preg_match('/(\d+)$/', $latest_dn, $matches)) {
            $max_number = (int)$matches[1];
        }
    }

    $new_number = $max_number + 1;
    return $prefix . date('Y') . '-' . str_pad($new_number, 5, '0', STR_PAD_LEFT);
}

$initial_dn_number = generateDNNumber($conn);

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create_dn' && hasPermission('debit_notes', 'create')) {
            $dn_number = sanitizeInput($_POST['dn_number']);
            $vendor_name = sanitizeInput($_POST['vendor_name']);
            $vendor_id = intval($_POST['vendor_id'] ?? 0);
            $debit_date = sanitizeInput($_POST['debit_date']);
            $original_invoice = sanitizeInput($_POST['original_invoice']);
            $total_amount = floatval($_POST['total_amount']);
            $reason = sanitizeInput($_POST['reason']);
            $status = sanitizeInput($_POST['status']);
            
            // Get vendor details including address and GSTIN
            $vendor_address = '';
            $vendor_gstin = '';
            
            if ($vendor_id > 0) {
                $check_vendor = $conn->query("SELECT id, company_name, address, gst_no FROM customers WHERE id = $vendor_id AND (entity_type = 'vendor' OR entity_type = 'both')");
                if ($check_vendor->num_rows === 0) {
                    $message = showError('Selected vendor does not exist or is not valid.');
                } else {
                    $vendor_data = $check_vendor->fetch_assoc();
                    $vendor_name = $vendor_data['company_name'];
                    $vendor_address = $vendor_data['address'] ?? '';
                    $vendor_gstin = $vendor_data['gst_no'] ?? '';
                }
            } else {
                $message = showError('Please select a valid vendor.');
            }
            
            if (empty($message)) {
                $sql = "INSERT INTO debit_notes (debit_note_number, vendor_name, vendor_address, vendor_gstin, original_invoice, debit_date, total_amount, reason, status, created_by) 
                        VALUES ('$dn_number', '$vendor_name', '$vendor_address', '$vendor_gstin', '$original_invoice', '$debit_date', $total_amount, '$reason', '$status', {$_SESSION['user_id']})";
                
                if ($conn->query($sql)) {
                    $message = showSuccess('Debit Note created successfully!');
                } else {
                    $message = showError('Error creating debit note: ' . $conn->error);
                }
            }
        } elseif ($_POST['action'] === 'update_dn' && hasPermission('debit_notes', 'edit')) {
            $dn_id = intval($_POST['id']);
            $dn_number = sanitizeInput($_POST['dn_number']);
            $vendor_name = sanitizeInput($_POST['vendor_name']);
            $vendor_id = intval($_POST['vendor_id'] ?? 0);
            $debit_date = sanitizeInput($_POST['debit_date']);
            $original_invoice = sanitizeInput($_POST['original_invoice']);
            $total_amount = floatval($_POST['total_amount']);
            $reason = sanitizeInput($_POST['reason']);
            $status = sanitizeInput($_POST['status']);
            
            // Get vendor details including address and GSTIN
            $vendor_address = '';
            $vendor_gstin = '';
            
            if ($vendor_id > 0) {
                $check_vendor = $conn->query("SELECT id, company_name, address, gst_no FROM customers WHERE id = $vendor_id AND (entity_type = 'vendor' OR entity_type = 'both')");
                if ($check_vendor->num_rows === 0) {
                    $message = showError('Selected vendor does not exist or is not valid.');
                } else {
                    $vendor_data = $check_vendor->fetch_assoc();
                    $vendor_name = $vendor_data['company_name'];
                    $vendor_address = $vendor_data['address'] ?? '';
                    $vendor_gstin = $vendor_data['gst_no'] ?? '';
                }
            } else {
                $message = showError('Please select a valid vendor.');
            }
            
            if (empty($message)) {
                $sql = "UPDATE debit_notes SET 
                        debit_note_number = '$dn_number', 
                        vendor_name = '$vendor_name', 
                        vendor_address = '$vendor_address',
                        vendor_gstin = '$vendor_gstin',
                        original_invoice = '$original_invoice', 
                        debit_date = '$debit_date', 
                        total_amount = $total_amount, 
                        reason = '$reason', 
                        status = '$status' 
                        WHERE id = $dn_id";
                
                if ($conn->query($sql)) {
                    $message = showSuccess('Debit Note updated successfully!');
                } else {
                    $message = showError('Error updating debit note: ' . $conn->error);
                }
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    if (!hasPermission('debit_notes', 'delete')) {
        $message = showError("You don't have permission to delete debit notes!");
    } else {
        $id = (int)$_GET['delete'];
        
        $sql = "DELETE FROM debit_notes WHERE id = $id";
        if ($conn->query($sql)) {
            $message = showSuccess("Debit Note deleted successfully!");
        } else {
            $message = showError("Error deleting debit note: " . $conn->error);
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
    $whereClause = "WHERE dn.debit_note_number LIKE '%$search%' OR dn.vendor_name LIKE '%$search%'";
}

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM debit_notes dn $whereClause";
$countResult = $conn->query($countSql);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Get all debit notes with pagination
$dn_sql = "SELECT dn.*, u.full_name as created_by_name 
           FROM debit_notes dn 
           LEFT JOIN users u ON dn.created_by = u.id 
           $whereClause
           ORDER BY dn.created_at DESC
           LIMIT $limit OFFSET $offset";
$dn_result = $conn->query($dn_sql);
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-plus-circle"></i> Debit Notes</h2>
            <hr>
        </div>
    </div>
    
    <?php echo $message; ?>
    
    <!-- Search Box -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="search-box">
                <div class="row">
                    <div class="col-md-6">
                        <label for="dnSearch" class="form-label">
                            <i class="bi bi-search"></i> Search Debit Notes
                        </label>
                        <input type="text" class="form-control" id="dnSearch" 
                               placeholder="Search by DN number or vendor name..."
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
        <!-- Debit Note Form -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-plus-circle"></i> <span id="formTitle">Debit Note Details</span></h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="dnForm">
                        <input type="hidden" name="action" value="create_dn" id="formAction">
                        <input type="hidden" name="id" id="dnId">
                        <input type="hidden" name="vendor_id" id="vendor_id">
                        
                        <div class="mb-3">
                            <label for="dn_number" class="form-label">Debit Note Number *</label>
                            <input type="text" class="form-control" id="dn_number" name="dn_number" 
                                   value="<?php echo $initial_dn_number; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="debit_date" class="form-label">Debit Date *</label>
                            <input type="date" class="form-control" id="debit_date" name="debit_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="vendor_name" class="form-label">Vendor Name *</label>
                            <input type="text" class="form-control" id="vendor_name" name="vendor_name" required autocomplete="off" placeholder="Type to search for a vendor...">
                        </div>
                        
                        <div class="mb-3">
                            <label for="original_invoice" class="form-label">Original Invoice/Bill</label>
                            <input type="text" class="form-control" id="original_invoice" name="original_invoice" 
                                   placeholder="e.g., BILL-2025-001">
                        </div>
                        
                        <div class="mb-3">
                            <label for="total_amount" class="form-label">Debit Amount (₹) *</label>
                            <input type="number" class="form-control" id="total_amount" name="total_amount" 
                                   step="0.01" min="0" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Debit *</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" required 
                                      placeholder="e.g., Additional charges, Penalty, Service charges"></textarea>
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
                                <i class="bi bi-plus-circle"></i> Save Debit Note
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
                            <button type="button" class="btn btn-info" id="printBtn" style="display:none;" onclick="printDebitNote()">
                                <i class="bi bi-printer"></i> Print DN
                            </button>
                            <button type="button" class="btn btn-success" id="emailBtn" style="display:none;">
                                <i class="bi bi-envelope"></i> Email DN
                            </button>
                            <button type="button" class="btn btn-secondary" id="resetBtn">
                                <i class="bi bi-arrow-clockwise"></i> Reset Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Debit Notes List -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="bi bi-list"></i> All Debit Notes (<?php echo $totalRecords; ?>)</h5>
                    <small>Page <?php echo $page; ?> of <?php echo $totalPages; ?></small>
                </div>
                <div class="card-body">
                    <?php if ($dn_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>DN Number</th>
                                        <th>Vendor</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($dn = $dn_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($dn['debit_note_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($dn['vendor_name']); ?></td>
                                            <td><?php echo formatDate($dn['debit_date']); ?></td>
                                            <td>₹<?php echo number_format($dn['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $dn['status'] == 'applied' ? 'success' : ($dn['status'] == 'draft' ? 'secondary' : 'primary'); ?>">
                                                    <?php echo ucwords($dn['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary edit-dn" 
                                                        data-id="<?php echo $dn['id']; ?>"
                                                        data-dn_number="<?php echo htmlspecialchars($dn['debit_note_number']); ?>"
                                                        data-vendor_name="<?php echo htmlspecialchars($dn['vendor_name']); ?>"
                                                        data-debit_date="<?php echo $dn['debit_date']; ?>"
                                                        data-original_invoice="<?php echo htmlspecialchars($dn['original_invoice']); ?>"
                                                        data-total_amount="<?php echo $dn['total_amount']; ?>"
                                                        data-reason="<?php echo htmlspecialchars($dn['reason']); ?>"
                                                        data-status="<?php echo $dn['status']; ?>">
                                                    <i class="bi bi-pencil"></i> View/Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-info" onclick="printDebitNote(<?php echo $dn['id']; ?>)">
                                                    <i class="bi bi-printer"></i> Print
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-success email-dn" data-id="<?php echo $dn['id']; ?>">
                                                    <i class="bi bi-envelope"></i> Email
                                                </button>
                                                <?php if (hasPermission('debit_notes', 'delete')): ?>
                                                <a href="?delete=<?php echo $dn['id']; ?>" class="btn btn-sm btn-outline-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this debit note?')">
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
                            <nav aria-label="Debit Notes pagination">
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
                            <p class="mt-3">No debit notes found.</p>
                            <?php if (!empty($search)): ?>
                                <a href="debit_notes.php" class="btn btn-outline-primary">Clear Search</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print function -->
<script>
function printDebitNote(dnId) {
    if (!dnId) {
        dnId = $('#dnId').val();
    }
    if (dnId) {
        window.open(`../docs/print_debit_note.php?id=${dnId}`, '_blank');
    } else {
        alert('Please select a debit note to print');
    }
}
</script>

<!-- Include separate JavaScript file -->
<script src="../js/debit_notes.js"></script>

<?php include '../footer.php'; ?>