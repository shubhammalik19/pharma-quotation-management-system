<?php
include 'header.php';
checkLogin();
include 'menu.php';

// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create_machine' && hasPermission('machines', 'create')) {
            $name = sanitizeInput($_POST['name']);
            $model = sanitizeInput($_POST['model']);
            $category = sanitizeInput($_POST['category']);
            $description = sanitizeInput($_POST['description']);
            $tech_specs = sanitizeInput($_POST['tech_specs']);
            $part_code = sanitizeInput($_POST['part_code']);
            $is_active = 1;
            
            // Handle file upload
            $attachment_filename = null;
            $attachment_path = null;
            $attachment_size = null;
            $attachment_type = null;
            
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/machines/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $allowed_types = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
                $max_size = 10 * 1024 * 1024; // 10MB
                
                $file_info = pathinfo($_FILES['attachment']['name']);
                $file_extension = strtolower($file_info['extension']);
                
                if (!in_array($file_extension, $allowed_types)) {
                    redirectWithError("Invalid file type! Allowed: " . implode(', ', $allowed_types));
                } elseif ($_FILES['attachment']['size'] > $max_size) {
                    redirectWithError("File too large! Maximum size is 10MB.");
                } else {
                    $attachment_filename = $_FILES['attachment']['name'];
                    $unique_filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $attachment_filename);
                    $attachment_path = $upload_dir . $unique_filename;
                    $attachment_size = $_FILES['attachment']['size'];
                    $attachment_type = $_FILES['attachment']['type'];
                    
                    if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $attachment_path)) {
                        redirectWithError("Failed to upload file!");
                    }
                }
            }
            
            if (!empty($name)) {
                
                // Check for duplicate part_code if provided
                $part_code_check = '';
                if (!empty($part_code)) {
                    $check_sql = "SELECT id FROM machines WHERE part_code = '$part_code'";
                    $check_result = $conn->query($check_sql);
                    if ($check_result && $check_result->num_rows > 0) {
                        redirectWithError("Part code '$part_code' already exists! Please use a different part code.");
                    }
                    $part_code_check = "'$part_code'";
                } else {
                    $part_code_check = 'NULL';
                }
                
                $sql = "INSERT INTO machines (name, model, category, description, tech_specs, part_code, attachment_filename, attachment_path, attachment_size, attachment_type, is_active) 
                        VALUES ('$name', '$model', '$category', '$description', '$tech_specs', $part_code_check, " . 
                        ($attachment_filename ? "'$attachment_filename'" : 'NULL') . ", " .
                        ($attachment_path ? "'$attachment_path'" : 'NULL') . ", " .
                        ($attachment_size ? $attachment_size : 'NULL') . ", " .
                        ($attachment_type ? "'$attachment_type'" : 'NULL') . ", $is_active)";
                
                if ($conn->query($sql)) {
                    $new_machine_id = $conn->insert_id;
                    redirectWithSuccess('Machine created successfully!');
                } else {
                    // Handle specific duplicate part_code error
                    if (strpos($conn->error, 'unique_part_code') !== false) {
                        $message = "Part code '$part_code' already exists! Please use a different part code.";
                    } else {
                        $message = 'Error creating machine: ' . $conn->error;
                    }
                    // Delete uploaded file if database insert failed
                    if ($attachment_path && file_exists($attachment_path)) {
                        unlink($attachment_path);
                    }
                    redirectWithError($message);
                }
            } else {
                // Delete uploaded file if validation failed
                if ($attachment_path && file_exists($attachment_path)) {
                    unlink($attachment_path);
                }
                redirectWithError('Machine name is required!');
            }
        } elseif ($_POST['action'] === 'update_machine' && hasPermission('machines', 'edit')) {
            $machine_id = intval($_POST['id']);
            $name = sanitizeInput($_POST['name']);
            $model = sanitizeInput($_POST['model']);
            $category = sanitizeInput($_POST['category']);
            $description = sanitizeInput($_POST['description']);
            $tech_specs = sanitizeInput($_POST['tech_specs']);
            $part_code = sanitizeInput($_POST['part_code']);
            
            // Get current machine data for file management
            $currentMachine = $conn->query("SELECT * FROM machines WHERE id = $machine_id")->fetch_assoc();
            
            // Handle file upload for update
            $attachment_fields = "";
            
            // Check if attachment should be removed
            if (isset($_POST['remove_attachment']) && $_POST['remove_attachment'] == '1') {
                // Delete old file if exists
                if ($currentMachine && $currentMachine['attachment_path'] && file_exists($currentMachine['attachment_path'])) {
                    unlink($currentMachine['attachment_path']);
                }
                $attachment_fields = ", attachment_filename = NULL, attachment_path = NULL, attachment_size = NULL, attachment_type = NULL";
            } elseif (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/machines/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $allowed_types = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
                $max_size = 10 * 1024 * 1024; // 10MB
                
                $file_info = pathinfo($_FILES['attachment']['name']);
                $file_extension = strtolower($file_info['extension']);
                
                if (!in_array($file_extension, $allowed_types)) {
                    redirectWithError("Invalid file type! Allowed: " . implode(', ', $allowed_types));
                } elseif ($_FILES['attachment']['size'] > $max_size) {
                    redirectWithError("File too large! Maximum size is 10MB.");
                } else {
                    // Delete old file if exists
                    if ($currentMachine && $currentMachine['attachment_path'] && file_exists($currentMachine['attachment_path'])) {
                        unlink($currentMachine['attachment_path']);
                    }
                    
                    $attachment_filename = $_FILES['attachment']['name'];
                    $unique_filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $attachment_filename);
                    $attachment_path = $upload_dir . $unique_filename;
                    $attachment_size = $_FILES['attachment']['size'];
                    $attachment_type = $_FILES['attachment']['type'];
                    
                    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $attachment_path)) {
                        $attachment_fields = ", attachment_filename = '$attachment_filename', attachment_path = '$attachment_path', attachment_size = $attachment_size, attachment_type = '$attachment_type'";
                    } else {
                        redirectWithError("Failed to upload file!");
                    }
                }
            }
            
            if (!empty($name)) {
                // Check for duplicate part_code if provided (excluding current machine)
                if (!empty($part_code)) {
                    $check_sql = "SELECT id FROM machines WHERE part_code = '$part_code' AND id != $machine_id";
                    $check_result = $conn->query($check_sql);
                    if ($check_result && $check_result->num_rows > 0) {
                        setErrorMessage("Part code '$part_code' already exists! Please use a different part code.");
                        echo "<script>window.location.href = '" . $_SERVER['PHP_SELF'] . "';</script>";
                        exit();
                    }
                }
                
                $sql = "UPDATE machines SET 
                        name = '$name', 
                        model = '$model', 
                        category = '$category', 
                        description = '$description', 
                        tech_specs = '$tech_specs', 
                        part_code = " . (!empty($part_code) ? "'$part_code'" : 'NULL') . "
                        $attachment_fields
                        WHERE id = $machine_id";
                
                if ($conn->query($sql)) {
                    redirectWithSuccess('Machine updated successfully!');
                } else {
                    // Handle specific duplicate part_code error
                    if (strpos($conn->error, 'unique_part_code') !== false) {
                        redirectWithError("Part code '$part_code' already exists! Please use a different part code.");
                    } else {
                        redirectWithError('Error updating machine: ' . $conn->error);
                    }
                }
            } else {
                redirectWithError('Machine name is required!');
            }
        }
    }
}

// Handle feature delete
if (isset($_GET['delete_feature'])) {
    if (!hasPermission('machines', 'delete')) {
        redirectWithError("You don't have permission to delete features!");
    } else {
        $feature_id = (int)$_GET['delete_feature'];
        
        // Get feature name for confirmation message
        $feature_sql = "SELECT feature_name FROM machine_features WHERE id = $feature_id";
        $feature_result = $conn->query($feature_sql);
        $feature_name = '';
        if ($feature_result && $feature_row = $feature_result->fetch_assoc()) {
            $feature_name = $feature_row['feature_name'];
        }
        
        $sql = "DELETE FROM machine_features WHERE id = $feature_id";
        if ($conn->query($sql)) {
            if ($conn->affected_rows > 0) {
                redirectWithSuccess("Feature '$feature_name' deleted successfully!");
            } else {
                redirectWithError("Feature not found or already deleted!");
            }
        } else {
            redirectWithError("Error deleting feature: " . $conn->error);
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    if (!hasPermission('machines', 'delete')) {
        redirectWithError("You don't have permission to delete machines!");
    } else {
        $id = (int)$_GET['delete'];
        
        // Check for dependencies before deletion
        $dependencies = checkMachineDependencies($conn, $id);
        
        if (!empty($dependencies)) {
            $dependencyList = implode(', ', $dependencies);
            redirectWithError("Cannot delete machine! This machine is referenced in: " . $dependencyList . ". Please remove these references first.");
        }
        
        // Get machine data to delete associated file and name for confirmation
        $machine_sql = "SELECT name, attachment_path FROM machines WHERE id = $id";
        $machine_result = $conn->query($machine_sql);
        $machine_name = '';
        $attachment_path = '';
        if ($machine_result && $machine_row = $machine_result->fetch_assoc()) {
            $machine_name = $machine_row['name'];
            $attachment_path = $machine_row['attachment_path'];
        }
        
        $sql = "DELETE FROM machines WHERE id = $id";
        if ($conn->query($sql)) {
            if ($conn->affected_rows > 0) {
                // Delete associated file if exists
                if ($attachment_path && file_exists($attachment_path)) {
                    unlink($attachment_path);
                }
                redirectWithSuccess("Machine '$machine_name' deleted successfully!");
            } else {
                redirectWithError("Machine not found or already deleted!");
            }
        } else {
            redirectWithError("Error deleting machine: " . $conn->error);
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
    $where_clause = "WHERE name LIKE '%$search%' OR model LIKE '%$search%' OR category LIKE '%$search%' OR part_code LIKE '%$search%'";
}

// Count total records
$count_sql = "SELECT COUNT(*) as total FROM machines $where_clause";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-gear"></i> Machine Management</h2>
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
                        <label for="machineSearch" class="form-label">
                            <i class="bi bi-search"></i> Search Machines
                        </label>
                        <input type="text" class="form-control" id="machineSearch" 
                               placeholder="Search by name, model, category or part code..."
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
        <form method="POST" id="machineForm" enctype="multipart/form-data" class="row">
            <!-- Machine Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-gear-wide"></i> <span id="formTitle">Create Machine</span></h5>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="action" value="create_machine" id="formAction">
                        <input type="hidden" name="id" id="machineId">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Machine Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="model" class="form-label">Model</label>
                            <input type="text" class="form-control" id="model" name="model">
                        </div>
                        
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="category" name="category" placeholder="e.g., Granulation Equipment">
                        </div>
                        
                        <div class="mb-3">
                            <label for="part_code" class="form-label">Part Code</label>
                            <input type="text" class="form-control" id="part_code" name="part_code">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tech_specs" class="form-label">Technical Specifications</label>
                            <textarea class="form-control" id="tech_specs" name="tech_specs" rows="3"></textarea>
                        </div>
                        
                        <!-- File Attachment Section -->
                        <div class="mb-3">
                            <label for="attachment" class="form-label">
                                <i class="bi bi-paperclip"></i> Specification File
                            </label>
                            <input type="file" class="form-control" id="attachment" name="attachment" 
                                   accept=".pdf,.jpg,.jpeg,.png,.gif">
                            <div class="form-text">
                                Allowed: PDF, JPG, PNG, GIF (Max: 10MB)
                            </div>
                            
                            <!-- Current Attachment Display -->
                            <div id="currentAttachment" style="display: none;" class="mt-2 p-2 bg-light rounded">
                                <strong>Current File:</strong>
                                <div id="attachmentInfo" class="d-flex align-items-center mt-1">
                                    <span id="attachmentIcon" class="me-2"></span>
                                    <span id="attachmentName" class="me-2"></span>
                                    <span id="attachmentSize" class="badge bg-info me-2"></span>
                                    <a href="#" id="attachmentDownload" class="btn btn-sm btn-outline-primary me-2" target="_blank">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <button type="button" id="removeAttachment" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-success" id="saveBtn"><i class="bi bi-plus-circle"></i> Save Machine</button>
                            <button type="submit" class="btn btn-primary" id="updateBtn" style="display:none;"><i class="bi bi-check"></i> Update</button>
                            <button type="button" class="btn btn-warning" id="editBtn" style="display:none;"><i class="bi bi-pencil"></i> Edit</button>
                            <button type="button" class="btn btn-danger" id="deleteBtn" style="display:none;"><i class="bi bi-trash"></i> Delete</button>
                            <button type="button" class="btn btn-secondary" id="resetBtn"><i class="bi bi-arrow-clockwise"></i> Reset Form</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Feature Management Section -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-gear-fill"></i> Machine Features</h5>
                    </div>
                    <div class="card-body">
                        <!-- Add Feature Form -->
                        <div class="mb-4" id="featureSection">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="feature_name" class="form-label">Feature Name</label>
                                    <input type="text" class="form-control" id="feature_name" name="feature_name" placeholder="Enter feature name..." disabled>
                                    <small class="text-muted">Select or create a machine first to add features</small>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="button" class="btn btn-success" id="addFeatureBtn" disabled>
                                        <i class="bi bi-plus-circle"></i> ADD
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Features List -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>SNO</th>
                                        <th>Feature Name</th>
                                        <th>ACTION</th>
                                    </tr>
                                </thead>
                                <tbody id="featuresTableBody">
                                    <tr>
                                        <td colspan="3" class="text-center py-4">
                                            <i class="bi bi-gear display-1 text-muted"></i>
                                            <p class="mt-3">Select a machine to view/add features.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Machines List Section (Moved to Bottom) -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="bi bi-list"></i> All Machines (<?php echo $total_records; ?>)</h5>
                    <small>Page <?php echo $page; ?> of <?php echo $total_pages; ?></small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Name</th>
                                    <th>Model</th>
                                    <th>Category</th>
                                    <th>Part Code</th>
                                    <th>Attachment</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT * FROM machines $where_clause ORDER BY created_at DESC LIMIT $records_per_page OFFSET $offset";
                                $result = $conn->query($sql);
                                
                                if ($result && $result->num_rows > 0):
                                    while ($row = $result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['model']); ?></td>
                                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                                    <td>
                                        <?php if (!empty($row['part_code'])): ?>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($row['part_code']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['attachment_filename'])): ?>
                                            <div class="d-flex align-items-center">
                                                <?php 
                                                $ext = strtolower(pathinfo($row['attachment_filename'], PATHINFO_EXTENSION));
                                                if ($ext === 'pdf'): ?>
                                                    <i class="bi bi-file-earmark-pdf text-danger me-1"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-file-earmark-image text-primary me-1"></i>
                                                <?php endif; ?>
                                                <small class="text-muted me-2"><?php echo formatFileSize($row['attachment_size']); ?></small>
                                                <a href="<?php echo $row['attachment_path']; ?>" 
                                                   class="btn btn-sm btn-outline-success" 
                                                   target="_blank" 
                                                   title="Download <?php echo htmlspecialchars($row['attachment_filename']); ?>">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">No file</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-machine" data-id="<?php echo $row['id']; ?>">
                                            <i class="bi bi-pencil"></i> View/Edit
                                        </button>
                                        <?php if (hasPermission('machines', 'delete')): ?>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger machine-delete-btn" 
                                           title="Delete Machine">
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
                                        <i class="bi bi-gear display-1 text-muted"></i>
                                        <p class="mt-3">No machines found.</p>
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
        background: #fff url('data:image/gif;base64,R0lGODlhEAAQAPIAAP///wAAAMLCwkJCQgAAAGJiYoKCgpKSkiH/C05FVFNDQVBFMi4wAwEAAAAh/hpDcmVhdGVkIHdpdGggYWpheGxvYWQuaW5mbwAh+QQJCgAAACwAAAAAEAAQAAADMwi63P4wyklrE2MIOggZnAdOmGYJRbExwroUmcG2LmDEwnHQLVsYOd2mBzkYDAdKa+dIAAAh+QQJCgAAACwAAAAAEAAQAAADNAi63P5OjCEgG4QMu7DmikRxQlFUYDEZIGBMRVsaqHwctXXf7WEYB4Ag1xjihkMZsiUkKhIAIfkECQoAAAAsAAAAABAAEAAAAzYIujIjK8pByJDMlFYvBoVjHA70GU7xSUJhmKtwHPAKzLO9HMaoKwJZ7Rf8AYPDDzKpZBqfvwQAIfkECQoAAAAsAAAAABAAEAAAAzMIumIlK8oyhpHsnFZfhYumCYUhDAQxRIdhHBGqRoKw0R8DYlJd8z0fMDgsGo/IpHI5TAAAIfkECQoAAAAsAAAAABAAEAAAAzIIunInK0rnZBTwGPNMgQwmdsNgXGJUlIWEuR5oWUIpz8pAEAMe6TwfwyYsGo/IpHI5TAAAh+QQJCgAAACwAAAAAEAAQAAADMwi6IMKQORfjdOe82p4wGccc4CEuQradylesojEMBgsUc2G7sDX3lQGBMLAJibufbSlKAAAh+QQJCgAAACwAAAAAEAAQAAADMgi63P7wjQkLDfwACl3iyOsGgfFjhJUdZBmmBnSZYgYpvr7KfD4rGGF4/I5cUhTdACwWAA==') no-repeat right center;
        background-size: 16px 16px;
        padding-right: 40px;
    }
</style>

<script>
// Global variable to store current machine ID for features
let currentMachineId = null;

// Function to load features for a machine
function loadMachineFeatures(machineId) {
    currentMachineId = machineId;
    
    if (!machineId) {
        // Reset features display - no machine selected or creating new machine
        $('#featuresTableBody').html(`
            <tr>
                <td colspan="3" class="text-center py-4">
                    <i class="bi bi-gear display-1 text-muted"></i>
                    <p class="mt-3">Select an existing machine to view/add features.</p>
                </td>
            </tr>
        `);
        $('#feature_name').prop('disabled', true);
        $('#addFeatureBtn').prop('disabled', true);
        $('#feature_name + small').text('Features can only be added to existing machines');
        return;
    }

    // Enable feature input only for existing machines during editing
    $('#feature_name').prop('disabled', false);
    $('#addFeatureBtn').prop('disabled', false);
    $('#feature_name + small').text('Add features for the selected machine');

    // Load features via AJAX using jQuery
    $.ajax({
        url: 'ajax/get_machine_features.php',
        type: 'GET',
        data: { machine_id: machineId },
        dataType: 'json',
        success: function(data) {
            let tbody = $('#featuresTableBody');
            if (data.success && data.features.length > 0) {
                let featuresHtml = data.features.map((feature, index) => `
                    <tr>
                        <td>${index + 1}</td>
                        <td><strong>${feature.feature_name}</strong></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-feature-btn" 
                                    data-id="${feature.id}" data-name="${feature.feature_name}"
                                    title="Delete Feature">
                                <i class="bi bi-trash"></i> DELETE
                            </button>
                        </td>
                    </tr>
                `).join('');
                tbody.html(featuresHtml);
            } else {
                tbody.html(`
                    <tr>
                        <td colspan="3" class="text-center py-4">
                            <i class="bi bi-gear display-1 text-muted"></i>
                            <p class="mt-3">No features added yet for this machine.</p>
                        </td>
                    </tr>
                `);
            }
        },
        error: function() {
            console.error('Error loading features');
            $('#featuresTableBody').html(`
                <tr>
                    <td colspan="3" class="text-center py-4 text-danger">
                        <i class="bi bi-exclamation-triangle display-1"></i>
                        <p class="mt-3">Error loading features</p>
                    </td>
                </tr>
            `);
        }
    });
}

// Function to add a feature
function addMachineFeature() {
    if (!currentMachineId) {
        alert('Please select a machine first');
        return;
    }

    const featureName = $('#feature_name').val().trim();
    if (!featureName) {
        alert('Please enter a feature name');
        return;
    }

    // Show loading state
    const addBtn = $('#addFeatureBtn');
    const originalText = addBtn.html();
    addBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Adding...');

    // Add feature via AJAX using jQuery
    $.ajax({
        url: 'ajax/add_machine_feature.php',
        type: 'POST',
        data: {
            machine_id: currentMachineId,
            feature_name: featureName
        },
        dataType: 'json',
        success: function(data) {
            addBtn.prop('disabled', false).html(originalText);
            if (data.success) {
                $('#feature_name').val('');
                loadMachineFeatures(currentMachineId);
                alert('✓ ' + data.message);
            } else {
                alert('✗ ' + data.message);
            }
        },
        error: function() {
            addBtn.prop('disabled', false).html(originalText);
            console.error('Error adding feature');
            alert('✗ Error adding feature');
        }
    });
}

// jQuery Document Ready
$(document).ready(function() {
    
    // Initially disable features section
    loadMachineFeatures(null);
    
    // Add feature button click event
    $('#addFeatureBtn').on('click', addMachineFeature);

    // Feature name input enter key
    $('#feature_name').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            addMachineFeature();
        }
    });

    // Delete feature button click event (delegated for dynamic buttons)
    $(document).on('click', '.delete-feature-btn', function() {
        const featureId = $(this).data('id');
        const featureName = $(this).data('name');
        
        if (!confirm(`Are you sure you want to delete the feature "${featureName}"?`)) {
            return;
        }

        const deleteBtn = $(this);
        const originalHtml = deleteBtn.html();
        deleteBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i>');

        $.ajax({
            url: 'ajax/delete_machine_feature.php',
            type: 'GET',
            data: { id: featureId },
            dataType: 'json',
            success: function(data) {
                deleteBtn.prop('disabled', false).html(originalHtml);
                if (data.success) {
                    loadMachineFeatures(currentMachineId);
                    alert('✓ ' + data.message);
                } else {
                    alert('✗ ' + data.message);
                }
            },
            error: function() {
                deleteBtn.prop('disabled', false).html(originalHtml);
                console.error('Error deleting feature');
                alert('✗ Error deleting feature');
            }
        });
    });
});
</script>

<script src="js/machines.js"></script>

<?php include 'footer.php'; ?>

