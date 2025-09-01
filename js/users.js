/* users.js - Uniform User Management JavaScript
   Requires: jQuery, Bootstrap (for form interactions)
   Endpoints used:
     - ../ajax/get_user_details.php    (id)
*/

// ---------- SMALL HELPERS ----------
function fmtDate(d){ if(!d) return ''; const dt=new Date(d); return dt.toLocaleDateString('en-GB'); }
function esc(t){ if(!t) return ''; const m={'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}; return t.toString().replace(/[&<>"']/g, x=>m[x]); }

$(document).ready(function() {
    // Search functionality
    $('#searchBtn').on('click', function() {
        const searchTerm = $('#userSearch').val().trim();
        if (searchTerm) {
            window.location.href = 'users.php?search=' + encodeURIComponent(searchTerm);
        }
    });

    $('#clearBtn').on('click', () => window.location.href = 'users.php');

    $('#userSearch').on('keypress', e => e.which === 13 && $('#searchBtn').click());

    // Edit user from table
    $(document).on('click', '.edit-user', function() {
        const userId = $(this).data('id');
        const roleId = $(this).data('role-id');
        
        // Get data from the table row
        const row = $(this).closest('tr');
        const userData = {
            id: userId,
            username: row.find('td:nth-child(2) strong').text(),
            full_name: row.find('td:nth-child(3)').text(),
            email: row.find('td:nth-child(4)').text(),
            role_id: roleId || '',
            role_name: row.find('td:nth-child(5) .badge').text().replace('No Role', ''),
            is_active: row.find('td:nth-child(6) .badge').hasClass('bg-success') ? 1 : 0,
            is_admin: row.find('td:nth-child(2) .bi-shield-check').length > 0 ? 1 : 0
        };
        
        loadUserData(userData);
    });

    function loadUserData(data) {
        $('#userId').val(data.id);
        $('#username').val(data.username);
        $('#full_name').val(data.full_name || '');
        $('#email').val(data.email || '');
        
        // Set role using the role_id from data attribute
        $('#user_role').val(data.role_id || '');
        
        $('#is_admin').prop('checked', data.is_admin == 1);
        $('#is_active').prop('checked', data.is_active == 1);
        
        setFormReadOnly(true);
        $('#saveBtn').hide();
        $('#editBtn').show();
        $('#deleteBtn').show();
        $('#updateBtn').hide();
        $('#formTitle').text('User Details - ' + data.username);
        
        // Clear password field and show help text
        $('#password').val('');
        $('#passwordRequired').hide();
        $('#passwordHelp').show();
        
        $('html, body').animate({ scrollTop: $('#userForm').offset().top - 100 }, 500);
    }

    $('#editBtn').on('click', function() {
        setFormReadOnly(false);
        $('#editBtn').hide();
        $('#updateBtn').show();
        $('#formAction').val('update_user');
    });

    $('#deleteBtn').on('click', function() {
        const userId = $('#userId').val();
        const username = $('#username').val();
        if (userId && confirm('Are you sure you want to delete user "' + username + '"?')) {
            window.location.href = 'users.php?delete=' + userId;
        }
    });

    $('#resetBtn').on('click', resetForm);

    function setFormReadOnly(readonly) {
        $('#userForm input, #userForm select').not('#userId, #formAction').prop('readonly', readonly);
        $('#userForm select').prop('disabled', readonly);
        $('#userForm input[type="checkbox"]').prop('disabled', readonly);
        $('#userForm input[type="file"]').prop('disabled', readonly);
        
        if(readonly) {
            $('#editBtn, #deleteBtn').prop('disabled', false);
        }
    }

    function resetForm() {
        $('#userForm')[0].reset();
        $('#userId').val('');
        $('#formAction').val('create_user');
        setFormReadOnly(false);
        $('#saveBtn').show();
        $('#editBtn, #updateBtn, #deleteBtn').hide();
        $('#formTitle').text('Create User');
        $('#passwordRequired').show();
        $('#passwordHelp').hide();
        $('#is_active').prop('checked', true);
    }

    // Role description helper (optional - could add descriptions later)
    $('#user_role').on('change', function() {
        const roleId = $(this).val();
        const selectedText = $(this).find('option:selected').text();
        
        // Optional: You could add role descriptions based on the selected role
        // For now, we'll just show the selected role name
        
        // You could add a description element if needed
        // $('#roleDescription').text('Selected: ' + selectedText);
    });

    // File upload validation
    $('#profile_picture').on('change', function() {
        const file = this.files[0];
        if (file) {
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!validTypes.includes(file.type)) {
                alert('Please select a valid image file (JPG, PNG, GIF)');
                this.value = '';
                return;
            }
            
            if (file.size > maxSize) {
                alert('File size must be less than 5MB');
                this.value = '';
                return;
            }
        }
    });

    // Form validation before submission
    $('#userForm').on('submit', function(e) {
        const username = $('#username').val().trim();
        const password = $('#password').val();
        const action = $('#formAction').val();
        
        if (!username) {
            alert('Username is required');
            e.preventDefault();
            return;
        }
        
        if (action === 'create_user' && !password) {
            alert('Password is required for new users');
            e.preventDefault();
            return;
        }
        
        if (password && password.length < 6) {
            alert('Password must be at least 6 characters long');
            e.preventDefault();
            return;
        }
    });

    // Auto-focus on username field when creating new user
    if ($('#formAction').val() === 'create_user') {
        $('#username').focus();
    }
});