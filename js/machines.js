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
        $.ajax({
            url: 'ajax/get_machine_details.php', // You may need to create this endpoint
            type: 'GET',
            data: { id: machineId },
            dataType: 'json',
            success: function(data) {
                if(data.success) {
                    fillFormWithMachineData(data.machine);
                } else {
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
            error: function() {
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
        $('#saveBtn').hide();
        $('#editBtn').show();
        $('#deleteBtn').show();
        $('#updateBtn').hide();
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
        $('#saveBtn').hide();
        $('#editBtn').show();
        $('#deleteBtn').show();
        $('#updateBtn').hide();
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
            $('#attachmentDownload').attr('href', machine.attachment_path);
            $('#currentAttachment').show();
        } else {
            $('#currentAttachment').hide();
        }
    }

    $('#editBtn').on('click', function() {
        setFormReadOnly(false);
        $('#editBtn').hide();
        $('#updateBtn').show();
        $('#formAction').val('update_machine');
    });

    $('#deleteBtn').on('click', function() {
        const machineId = $('#machineId').val();
        const machineName = $('#name').val();
        if (machineId && confirm('Are you sure you want to delete Machine "' + machineName + '"?')) {
            window.location.href = 'machines.php?delete=' + machineId;
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
        $('#saveBtn').show();
        $('#editBtn, #updateBtn, #deleteBtn').hide();
        $('#formTitle').text('Create Machine');
    }

    // Form validation
    $('#machineForm').on('submit', function(e) {
        const machineName = $('#name').val().trim();
        if (!machineName) {
            e.preventDefault();
            alert('Machine name is required!');
            return false;
        }
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