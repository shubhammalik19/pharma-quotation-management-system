/* machines.js - Uniform design consistent with purchase_orders.js and quotations.js
   Requires: jQuery, jQuery UI (autocomplete), Bootstrap (for modals)
   Endpoints used:
     - ajax/unified_search.php           (AUTOCOMPLETE_MACHINES, AUTOCOMPLETE_CATEGORIES)
     - ajax/get_machine_details.php      (id) - if needed
*/

// ---------- SMALL HELPERS ----------
function fmtDate(d){ if(!d) return ''; const dt=new Date(d); return dt.toLocaleDateString('en-GB'); }
function esc(t){ if(!t) return ''; const m={'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}; return t.toString().replace(/[&<>"']/g, x=>m[x]); }

// Helper function to format file size
function formatFileSize(bytes) {
    if (!bytes) return '0 bytes';
    if (bytes >= 1073741824) {
        return (bytes / 1073741824).toFixed(2) + ' GB';
    } else if (bytes >= 1048576) {
        return (bytes / 1048576).toFixed(2) + ' MB';
    } else if (bytes >= 1024) {
        return (bytes / 1024).toFixed(2) + ' KB';
    } else {
        return bytes + ' bytes';
    }
}

$(document).ready(function() {
    
    // Function to reload page after actions (for manual use if needed)
    window.reloadAfterAction = function(delay = 1500) {
        setTimeout(function() {
            window.location.reload();
        }, delay);
    };
    
    // Autocomplete for Machine Search
    $('#machineSearch').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: 'ajax/unified_search.php',
                type: 'GET',
                data: {
                    term: request.term,
                    type: 'AUTOCOMPLETE_MACHINES'
                },
                dataType: 'json',
                success: function(data) {
                    response(data);
                },
                error: function() {
                    response([]);
                }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            $('#machineSearch').val(ui.item.value);
            $('#searchBtn').click();
            return false;
        }
    });

    // Autocomplete for Category
    $('#category').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: 'ajax/unified_search.php',
                type: 'GET',
                data: {
                    term: request.term,
                    type: 'AUTOCOMPLETE_CATEGORIES'
                },
                dataType: 'json',
                success: function(data) {
                    response(data);
                },
                error: function() {
                    response([]);
                }
            });
        },
        minLength: 2
    });

    // Search functionality
    $('#searchBtn').on('click', function() {
        const searchTerm = $('#machineSearch').val().trim();
        if (searchTerm) {
            window.location.href = 'machines.php?search=' + encodeURIComponent(searchTerm);
        }
    });

    $('#clearBtn').on('click', () => window.location.href = 'machines.php');

    $('#machineSearch').on('keypress', e => e.which === 13 && $('#searchBtn').click());

    // Edit machine from table
    $(document).on('click', '.edit-machine', function() {
        const machineId = $(this).data('id');
        
        // Get machine data from the row or fetch via AJAX
        const row = $(this).closest('tr');
        const machineName = row.find('td:nth-child(1) strong').text();
        const model = row.find('td:nth-child(2)').text();
        const category = row.find('td:nth-child(3)').text();
        const partCode = row.find('td:nth-child(4) .badge').text();
        
        // Try to fetch complete machine details via AJAX first
        console.log('Fetching machine details for ID:', machineId);
        $.ajax({
            url: 'ajax/get_machine_details.php',
            type: 'GET',
            data: { id: machineId },
            dataType: 'json',
            success: function(data) {
                console.log('AJAX response:', data);
                if(data.success) {
                    console.log('Filling form with machine data:', data.machine);
                    fillFormWithMachineData(data.machine);
                } else {
                    console.log('AJAX failed, using basic data. Error:', data.message);
                    // Fallback to basic data from table
                    fillFormWithBasicData({
                        id: machineId,
                        name: machineName,
                        model: model,
                        category: category,
                        part_code: partCode
                    });
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX error:', status, error);
                // Fallback to basic data from table
                fillFormWithBasicData({
                    id: machineId,
                    name: machineName,
                    model: model,
                    category: category,
                    part_code: partCode
                });
            }
        });
    });

    function fillFormWithMachineData(machine) {
        $('#machineId').val(machine.id);
        $('#name').val(machine.name);
        $('#model').val(machine.model);
        $('#category').val(machine.category);
        $('#part_code').val(machine.part_code);
        $('#description').val(machine.description);
        $('#tech_specs').val(machine.tech_specs);
        
        // Display attachment if exists
        displayAttachment(machine.attachment_filename, machine.attachment_path, machine.attachment_size, machine.id);
        
        setFormReadOnly(true);
        
        // Show/hide buttons with explicit style changes
        $('#saveBtn').css('display', 'none');
        $('#editBtn').css('display', 'block');
        $('#deleteBtn').css('display', 'block');
        $('#updateBtn').css('display', 'none');
        $('#formTitle').text('Machine Details - ' + machine.name);
        
        $('html, body').animate({ scrollTop: $('#machineForm').offset().top - 100 }, 500);
    }

    function fillFormWithBasicData(machine) {
        $('#machineId').val(machine.id);
        $('#name').val(machine.name);
        $('#model').val(machine.model);
        $('#category').val(machine.category);
        $('#part_code').val(machine.part_code);
        
        setFormReadOnly(true);
        
        // Show/hide buttons with explicit style changes
        $('#saveBtn').css('display', 'none');
        $('#editBtn').css('display', 'block');
        $('#deleteBtn').css('display', 'block');
        $('#updateBtn').css('display', 'none');
        $('#formTitle').text('Machine Details - ' + machine.name);
        
        $('html, body').animate({ scrollTop: $('#machineForm').offset().top - 100 }, 500);
    }

    // Helper function to display attachment info
    function displayAttachment(filename, path, size, machineId) {
        if (filename && path) {
            const fileExt = filename.split('.').pop().toLowerCase();
            let icon = '';
            if (fileExt === 'pdf') {
                icon = '<i class="bi bi-file-earmark-pdf text-danger fs-5"></i>';
            } else {
                icon = '<i class="bi bi-file-earmark-image text-primary fs-5"></i>';
            }
            
            $('#attachmentIcon').html(icon);
            $('#attachmentName').text(filename);
            $('#attachmentSize').text(formatFileSize(size));
            $('#attachmentDownload').attr('href', path);
            $('#currentAttachment').show();
        } else {
            $('#currentAttachment').hide();
        }
    }

    $('#editBtn').on('click', function() {
        setFormReadOnly(false);
        $('#editBtn').css('display', 'none');
        $('#updateBtn').css('display', 'block');
        $('#formAction').val('update_machine');
    });

    $('#deleteBtn').on('click', function() {
        const machineId = $('#machineId').val();
        const machineName = $('#name').val();
        const deleteBtn = $(this);
        
        if (machineId) {
            // Show loading state
            deleteBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Checking...');
            
            // Check dependencies via AJAX
            $.ajax({
                url: 'ajax/check_machine_dependencies.php',
                type: 'GET',
                data: { id: machineId },
                dataType: 'json',
                success: function(response) {
                    deleteBtn.prop('disabled', false).html('<i class="bi bi-trash"></i> Delete');
                    
                    if (response.success) {
                        if (response.can_delete) {
                            // Safe to delete
                            const confirmMessage = `Are you sure you want to delete Machine "${response.machine_name}"?\n\n` +
                                                 `This action cannot be undone!`;
                            
                            if (confirm(confirmMessage)) {
                                deleteBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Deleting...');
                                window.location.href = 'machines.php?delete=' + machineId;
                            }
                        } else {
                            // Has dependencies
                            const dependencyList = response.dependencies.join(', ');
                            alert(`Cannot delete Machine "${response.machine_name}"!\n\n` +
                                  `This machine is referenced in:\n${dependencyList}\n\n` +
                                  `Please remove these references first before deleting the machine.`);
                        }
                    } else {
                        alert('Error checking dependencies: ' + response.message);
                    }
                },
                error: function() {
                    deleteBtn.prop('disabled', false).html('<i class="bi bi-trash"></i> Delete');
                    
                    // Fallback to basic confirmation
                    const confirmMessage = `Are you sure you want to delete Machine "${machineName}"?\n\n` +
                                         `This will check for any related records and prevent deletion if dependencies exist.\n\n` +
                                         `This action cannot be undone!`;
                    
                    if (confirm(confirmMessage)) {
                        deleteBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Deleting...');
                        window.location.href = 'machines.php?delete=' + machineId;
                    }
                }
            });
        }
    });

    $('#resetBtn').on('click', resetForm);

    // Remove attachment button
    $('#removeAttachment').on('click', function() {
        if (confirm('Are you sure you want to remove this attachment?')) {
            $('#currentAttachment').hide();
            // Add a hidden field to indicate attachment should be removed
            if ($('#removeAttachmentFlag').length === 0) {
                $('#machineForm').append('<input type="hidden" id="removeAttachmentFlag" name="remove_attachment" value="1">');
            }
        }
    });

    function setFormReadOnly(readonly) {
        $('#machineForm input, #machineForm textarea').not('#machineId, #formAction').prop('readonly', readonly);
        $('#attachment').prop('disabled', readonly);
        
        if(readonly) {
            $('#editBtn, #deleteBtn').prop('disabled', false);
        }
    }

    function resetForm() {
        $('#machineForm')[0].reset();
        $('#machineId').val('');
        $('#formAction').val('create_machine');
        $('#currentAttachment').hide();
        $('#removeAttachmentFlag').remove();
        setFormReadOnly(false);
        
        // Show/hide buttons with explicit style changes
        $('#saveBtn').css('display', 'block');
        $('#editBtn').css('display', 'none');
        $('#updateBtn').css('display', 'none');
        $('#deleteBtn').css('display', 'none');
        $('#formTitle').text('Create Machine');
    }

    // Form validation and submission
    $('#machineForm').on('submit', function(e) {
        const machineName = $('#name').val().trim();
        if (!machineName) {
            e.preventDefault();
            alert('Machine name is required!');
            return false;
        }
        
        // Add loading state to submit button
        const submitBtn = $(this).find('button[type="submit"]:visible');
        submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Processing...');
        
        // The form will submit normally and redirect via PHP
        return true;
    });

    // Enhanced delete confirmation for table delete links
    $(document).on('click', 'a[href*="delete="]', function(e) {
        e.preventDefault();
        const deleteUrl = $(this).attr('href');
        const machineId = deleteUrl.split('delete=')[1];
        const machineName = $(this).closest('tr').find('td:nth-child(1) strong').text();
        const deleteLink = $(this);
        
        // Show loading state
        const originalHtml = deleteLink.html();
        deleteLink.html('<i class="bi bi-hourglass-split"></i>').addClass('disabled');
        
        // Check dependencies via AJAX
        $.ajax({
            url: 'ajax/check_machine_dependencies.php',
            type: 'GET',
            data: { id: machineId },
            dataType: 'json',
            success: function(response) {
                deleteLink.html(originalHtml).removeClass('disabled');
                
                if (response.success) {
                    if (response.can_delete) {
                        // Safe to delete
                        const confirmMessage = `Are you sure you want to delete Machine "${response.machine_name}"?\n\n` +
                                             `This action cannot be undone!`;
                        
                        if (confirm(confirmMessage)) {
                            deleteLink.html('<i class="bi bi-hourglass-split"></i>').addClass('disabled');
                            window.location.href = deleteUrl;
                        }
                    } else {
                        // Has dependencies
                        const dependencyList = response.dependencies.join(', ');
                        alert(`Cannot delete Machine "${response.machine_name}"!\n\n` +
                              `This machine is referenced in:\n${dependencyList}\n\n` +
                              `Please remove these references first before deleting the machine.`);
                    }
                } else {
                    alert('Error checking dependencies: ' + response.message);
                }
            },
            error: function() {
                deleteLink.html(originalHtml).removeClass('disabled');
                
                // Fallback to basic confirmation
                const confirmMessage = `Are you sure you want to delete Machine "${machineName}"?\n\n` +
                                     `This will check for any related records and prevent deletion if dependencies exist.\n\n` +
                                     `This action cannot be undone!`;
                
                if (confirm(confirmMessage)) {
                    window.location.href = deleteUrl;
                }
            }
        });
    });

    // File input change handler
    $('#attachment').on('change', function() {
        const file = this.files[0];
        if (file) {
            // Validate file size
            if (file.size > 10 * 1024 * 1024) {
                alert('File too large! Maximum size is 10MB.');
                $(this).val('');
                return;
            }
            
            // Validate file type
            const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Invalid file type! Allowed: PDF, JPG, PNG, GIF');
                $(this).val('');
                return;
            }
        }
    });
});