// Roles Management JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize page
    initializeRolesPage();
});

function initializeRolesPage() {
    // Initialize permission counts
    updatePermissionCounts();
    
    // Setup event listeners
    setupEventListeners();
    
    // Initialize accordions if Bootstrap is available
    if (typeof bootstrap !== 'undefined') {
        // Bootstrap accordion is auto-initialized
    }
}

function setupEventListeners() {
    // Select all for specific page
    document.querySelectorAll('.select-page-all').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.dataset.page;
            document.querySelectorAll('.page-' + CSS.escape(page)).forEach(cb => cb.checked = true);
            updatePermissionCounts();
        });
    });

    // Clear all for specific page
    document.querySelectorAll('.clear-page-all').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.dataset.page;
            document.querySelectorAll('.page-' + CSS.escape(page)).forEach(cb => cb.checked = false);
            updatePermissionCounts();
        });
    });

    // Update counts when checkboxes change
    document.querySelectorAll('.permission-checkbox').forEach(cb => {
        cb.addEventListener('change', updatePermissionCounts);
    });

    // Edit role buttons
    document.querySelectorAll('.edit-role').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const role = JSON.parse(this.dataset.role);
            editRole(role);
        });
    });

    // Delete role buttons
    document.querySelectorAll('.delete-role').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const roleId = this.dataset.id;
            const roleName = this.dataset.name;
            deleteRole(roleId, roleName);
        });
    });

    // Auto-convert role name
    const nameField = document.getElementById('name');
    if (nameField) {
        nameField.addEventListener('input', function() {
            this.value = this.value.toLowerCase().replace(/[^a-z0-9_]/g, '_');
        });
    }

    // Form validation
    const roleForm = document.getElementById('roleForm');
    if (roleForm) {
        roleForm.addEventListener('submit', function(e) {
            if (!validateRoleForm()) {
                e.preventDefault();
            }
        });
    }
}

function resetForm() {
    const form = document.getElementById('roleForm');
    if (form) {
        form.reset();
    }
    
    document.getElementById('formAction').value = 'create_role';
    document.getElementById('roleId').value = '';
    document.getElementById('formTitle').textContent = 'Create Role';
    document.getElementById('saveText').textContent = 'Create Role';
    
    const nameField = document.getElementById('name');
    if (nameField) {
        nameField.disabled = false;
    }
    
    clearAllPermissions();
    updatePermissionCounts();
}

function selectAllPermissions() {
    document.querySelectorAll('.permission-checkbox').forEach(cb => {
        cb.checked = true;
    });
    updatePermissionCounts();
}

function clearAllPermissions() {
    document.querySelectorAll('.permission-checkbox').forEach(cb => {
        cb.checked = false;
    });
    updatePermissionCounts();
}

function updatePermissionCounts() {
    // Get all unique page classes dynamically
    const pageElements = document.querySelectorAll('[data-page]');
    const pages = [...new Set(Array.from(pageElements).map(el => el.dataset.page))];
    
    pages.forEach(page => {
        const pageCount = document.querySelectorAll('.page-' + CSS.escape(page) + ':checked').length;
        const countElement = document.getElementById('count_' + page);
        if (countElement) {
            countElement.textContent = pageCount;
            countElement.className = 'badge ms-2 ' + (pageCount > 0 ? 'bg-success' : 'bg-secondary');
        }
    });
}

function editRole(role) {
    document.getElementById('formAction').value = 'edit_role';
    document.getElementById('roleId').value = role.id;
    document.getElementById('formTitle').textContent = 'Edit Role';
    document.getElementById('saveText').textContent = 'Update Role';
    document.getElementById('name').value = role.name;
    document.getElementById('name').disabled = true;
    document.getElementById('display_name').value = role.display_name;
    document.getElementById('description').value = role.description || '';
    document.getElementById('is_active').checked = role.is_active == 1;
    
    // Load permissions for this role
    clearAllPermissions();
    loadRolePermissions(role.id);
}

function loadRolePermissions(roleId) {
    fetch('../ajax/get_role_permissions.php?role_id=' + roleId)
        .then(response => response.json())
        .then(permissions => {
            permissions.forEach(permId => {
                const checkbox = document.getElementById('perm_' + permId);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
            updatePermissionCounts();
        })
        .catch(error => {
            console.error('Error loading role permissions:', error);
        });
}

function deleteRole(roleId, roleName) {
    if (confirm('Are you sure you want to delete "' + roleName + '"? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="delete_role">' +
                        '<input type="hidden" name="role_id" value="' + roleId + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

function validateRoleForm() {
    const name = document.getElementById('name').value.trim();
    const displayName = document.getElementById('display_name').value.trim();
    
    if (!name) {
        alert('Please enter a role name.');
        document.getElementById('name').focus();
        return false;
    }
    
    if (!displayName) {
        alert('Please enter a display name.');
        document.getElementById('display_name').focus();
        return false;
    }
    
    // Check if at least one permission is selected
    const selectedPermissions = document.querySelectorAll('.permission-checkbox:checked');
    if (selectedPermissions.length === 0) {
        alert('Please select at least one permission for this role.');
        return false;
    }
    
    return true;
}

function selectPagePermissions(page) {
    document.querySelectorAll('.page-' + CSS.escape(page)).forEach(cb => {
        cb.checked = true;
    });
    updatePermissionCounts();
}

function clearPagePermissions(page) {
    document.querySelectorAll('.page-' + CSS.escape(page)).forEach(cb => {
        cb.checked = false;
    });
    updatePermissionCounts();
}

// Utility function to show notifications (if needed)
function showNotification(message, type = 'info') {
    // This can be enhanced with a proper notification system
    console.log(`${type.toUpperCase()}: ${message}`);
}

// Export functions for global access
window.resetForm = resetForm;
window.selectAllPermissions = selectAllPermissions;
window.clearAllPermissions = clearAllPermissions;
window.selectPagePermissions = selectPagePermissions;
window.clearPagePermissions = clearPagePermissions;
