<?php


include 'header.php';
checkLogin();
include 'menu.php';

$message = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create_customer' && hasPermission('customers', 'create')) {
            $entity_type = sanitizeInput($_POST['entity_type']);
            $company_name = sanitizeInput($_POST['company_name']);
            $contact_person = sanitizeInput($_POST['contact_person']);
            $phone = sanitizeInput($_POST['phone']);
            $email = sanitizeInput($_POST['email']);
            $gst_no = sanitizeInput($_POST['gst_no']);
            $address = sanitizeInput($_POST['address']);
            $city = sanitizeInput($_POST['city']);
            $state = sanitizeInput($_POST['state']);
            $pincode = sanitizeInput($_POST['pincode']);
            
            if (!empty($company_name)) {
                // Handle empty GST number to avoid unique constraint violation
                $gst_value = !empty($gst_no) ? "'$gst_no'" : "NULL";
                
                $sql = "INSERT INTO customers (entity_type, company_name, contact_person, phone, email, gst_no, address, city, state, pincode) 
                        VALUES ('$entity_type', '$company_name', '$contact_person', '$phone', '$email', $gst_value, '$address', '$city', '$state', '$pincode')";
                
                if ($conn->query($sql)) {
                    $message = showSuccess('Customer created successfully!');
                } else {
                    $message = showError('Error creating customer: ' . $conn->error);
                }
            } else {
                $message = showError('Company name is required!');
            }
        } elseif ($_POST['action'] === 'update_customer' && hasPermission('customers', 'edit')) {
            $customer_id = intval($_POST['id']);
            $entity_type = sanitizeInput($_POST['entity_type']);
            $company_name = sanitizeInput($_POST['company_name']);
            $contact_person = sanitizeInput($_POST['contact_person']);
            $phone = sanitizeInput($_POST['phone']);
            $email = sanitizeInput($_POST['email']);
            $gst_no = sanitizeInput($_POST['gst_no']);
            $address = sanitizeInput($_POST['address']);
            $city = sanitizeInput($_POST['city']);
            $state = sanitizeInput($_POST['state']);
            $pincode = sanitizeInput($_POST['pincode']);
            
            if (!empty($company_name)) {
                // Handle empty GST number to avoid unique constraint violation
                $gst_value = !empty($gst_no) ? "'$gst_no'" : "NULL";
                
                $sql = "UPDATE customers SET 
                        entity_type = '$entity_type', 
                        company_name = '$company_name', 
                        contact_person = '$contact_person', 
                        phone = '$phone', 
                        email = '$email', 
                        gst_no = $gst_value, 
                        address = '$address', 
                        city = '$city', 
                        state = '$state', 
                        pincode = '$pincode' 
                        WHERE id = $customer_id";
                
                if ($conn->query($sql)) {
                    $message = showSuccess('Customer updated successfully!');
                } else {
                    $message = showError('Error updating customer: ' . $conn->error);
                }
            } else {
                $message = showError('Company name is required!');
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    if (!hasPermission('customers', 'delete')) {
        $message = showError("You don't have permission to delete customers!");
    } else {
        $id = (int)$_GET['delete'];
        $sql = "DELETE FROM customers WHERE id = $id";
        if ($conn->query($sql)) {
            $message = showSuccess("Customer deleted successfully!");
        } else {
            $message = showError("Error deleting customer: " . $conn->error);
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
    $where_clause = "WHERE company_name LIKE '%$search%' OR contact_person LIKE '%$search%' OR phone LIKE '%$search%' OR email LIKE '%$search%' OR city LIKE '%$search%' OR state LIKE '%$search%'";
}

// Count total records
$count_sql = "SELECT COUNT(*) as total FROM customers $where_clause";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-people"></i> Customer/Vendor Management</h2>
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
                        <label for="customerSearch" class="form-label">
                            <i class="bi bi-search"></i> Search Customers/Vendors
                        </label>
                        <input type="text" class="form-control" id="customerSearch" 
                               placeholder="Search by company name, contact person, phone, email..."
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
        <form method="POST" id="customerForm" class="row">
            <!-- Customer Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-person-plus"></i> <span id="formTitle">Create Customer/Vendor</span></h5>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="action" value="create_customer" id="formAction">
                        <input type="hidden" name="id" id="customerId">
                        
                        <div class="mb-3">
                            <label for="entity_type" class="form-label">Entity Type *</label>
                            <select class="form-control" id="entity_type" name="entity_type" required>
                                <option value="customer">Customer</option>
                                <option value="vendor">Vendor</option>
                                <option value="both">Both (Customer & Vendor)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="company_name" class="form-label">Company Name *</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="contact_person" class="form-label">Contact Person</label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person">
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        
                        <div class="mb-3">
                            <label for="gst_no" class="form-label">GST Number</label>
                            <input type="text" class="form-control" id="gst_no" name="gst_no">
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="state" class="form-label">State</label>
                                <input type="text" class="form-control" id="state" name="state">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="pincode" class="form-label">Pincode</label>
                            <input type="text" class="form-control" id="pincode" name="pincode">
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-success" id="saveBtn"><i class="bi bi-plus-circle"></i> Save Customer</button>
                            <button type="submit" class="btn btn-primary" id="updateBtn" style="display:none;"><i class="bi bi-check"></i> Update</button>
                            <button type="button" class="btn btn-warning" id="editBtn" style="display:none;"><i class="bi bi-pencil"></i> Edit</button>
                            <button type="button" class="btn btn-danger" id="deleteBtn" style="display:none;"><i class="bi bi-trash"></i> Delete</button>
                            <button type="button" class="btn btn-secondary" id="resetBtn"><i class="bi bi-arrow-clockwise"></i> Reset Form</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Customers List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="bi bi-list"></i> All Customers/Vendors (<?php echo $total_records; ?>)</h5>
                        <small>Page <?php echo $page; ?> of <?php echo $total_pages; ?></small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Type</th>
                                        <th>Company Name</th>
                                        <th>Contact Person</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>City/State</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT * FROM customers $where_clause ORDER BY created_at DESC LIMIT $records_per_page OFFSET $offset";
                                    $result = $conn->query($sql);
                                    
                                    if ($result && $result->num_rows > 0):
                                        while ($row = $result->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $type_class = '';
                                            switch($row['entity_type']) {
                                                case 'customer': $type_class = 'bg-primary'; break;
                                                case 'vendor': $type_class = 'bg-success'; break;
                                                case 'both': $type_class = 'bg-warning'; break;
                                                default: $type_class = 'bg-secondary';
                                            }
                                            ?>
                                            <span class="badge <?php echo $type_class; ?>"><?php echo ucfirst($row['entity_type']); ?></span>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($row['company_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['contact_person']); ?></td>
                                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($row['city']); ?>
                                            <?php if (!empty($row['state'])): ?>, <?php echo htmlspecialchars($row['state']); ?><?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary edit-customer" data-id="<?php echo $row['id']; ?>">
                                                <i class="bi bi-pencil"></i> View/Edit
                                            </button>
                                            <?php if (hasPermission('customers', 'delete')): ?>
                                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this customer?')">
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
                                        <td colspan="7" class="text-center py-4">
                                            <i class="bi bi-people display-1 text-muted"></i>
                                            <p class="mt-3">No customers found.</p>
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
        </form>
    </div>
</div>

<!-- Styles specific to this page -->
<style>
    .readonly-field { background: #f8f9fa !important; pointer-events: none; }
    .table-hover tbody tr:hover { background-color: rgba(0,0,0,.075); }

    /* Ensure search input is always functional */
    #customerSearch {
        pointer-events: auto !important;
        background: #fff !important;
        border: 1px solid #ced4da !important;
    }
    
    #customerSearch:focus {
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
        background: #fff url('data:image/gif;base64,R0lGODlhEAAQAPIAAP///wAAAMLCwkJCQgAAAGJiYoKCgpKSkiH/C05FVFNDQVBFMi4wAwEAAAAh/hpDcmVhdGVkIHdpdGggYWpheGxvYWQuaW5mbwAh+QQJCgAAACwAAAAAEAAQAAADMwi63P4wyklrE2MIOggZnAdOmGYJRbExwroUmcG2LmDEwnHQLVsYOd2mBzkYDAdKa+dIAAAh+QQJCgAAACwAAAAAEAAQAAADNAi63P5OjCEgG4QMu7DmikRxQlFUYDEZIGBMRVsaqHwctXXf7WEYB4Ag1xjihkMZsiUkKhIAIfkECQoAAAAsAAAAABAAEAAAAzYIujIjK8pByJDMlFYvBoVjHA70GU7xSUJhmKtwHPAKzLO9HMaoKwJZ7Rf8AYPDDzKpZBqfvwQAIfkECQoAAAAsAAAAABAAEAAAAzMIumIlK8oyhpHsnFZfhYumCYUhDAQxRIdhHBGqRoKw0R8DYlJd8z0fMDgsGo/IpHI5TAAAIfkECQoAAAAsAAAAABAAEAAAAzIIunInK0rnZBTwGPNMgQwmdsNgXGJUlIWEuR5oWUIpz8pAEAMe6TwfwyYsGo/IpHI5TAAAh+QQJCgAAACwAAAAAEAAQAAADMwi6IMKQORfjdOe82p4wGccc4CEuQradylesojEMBgsUc2G7sDX3lQGBMLAJibufbSlKAAAh+QQJCgAAACwAAAAAEAAQAAADMgi63P7wjQkLDfwACl3iyOsGgfFjhJUdZBmmBnSZYgYpvr7KfD4rGGF4/I5cUhTdACwWAA==') no-repeat right center;
        background-size: 16px 16px;
        padding-right: 40px;
    }
</style>

<script src="js/customers.js"></script>

<?php include 'footer.php'; ?>