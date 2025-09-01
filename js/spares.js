/* spares.js - Uniform design consistent with purchase_orders.js, quotations.js, and machines.js
   Requires: jQuery, jQuery UI (autocomplete), Bootstrap (for modals)
   Endpoints used:
     - ajax/unified_search.php           (AUTOCOMPLETE_SPARES, AUTOCOMPLETE_MACHINES)
     - ajax/get_spare_details.php        (id) - if needed
*/

// ---------- SMALL HELPERS ----------
function fmtDate(d){ if(!d) return ''; const dt=new Date(d); return dt.toLocaleDateString('en-GB'); }
function esc(t){ if(!t) return ''; const m={'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}; return t.toString().replace(/[&<>"']/g, x=>m[x]); }

$(document).ready(function() {
    
    // Autocomplete for Spare Search
    $('#spareSearch').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: 'ajax/unified_search.php',
                type: 'GET',
                data: {
                    term: request.term,
                    type: 'AUTOCOMPLETE_SPARES'
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
            $('#spareSearch').val(ui.item.value);
            $('#searchBtn').click();
            return false;
        }
    });

    // Autocomplete for Machine Name
    $('#machine_name').autocomplete({
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
            $('#machine_id').val(ui.item.id);
            $('#machine_name').val(ui.item.label);
            return false;
        },
        change: function(event, ui) {
            if (!ui.item) {
                $('#machine_id').val('');
            }
        }
    });

    // Search functionality
    $('#searchBtn').on('click', function() {
        const searchTerm = $('#spareSearch').val().trim();
        if (searchTerm) {
            window.location.href = 'spares.php?search=' + encodeURIComponent(searchTerm);
        }
    });

    $('#clearBtn').on('click', () => window.location.href = 'spares.php');

    $('#spareSearch').on('keypress', e => e.which === 13 && $('#searchBtn').click());

    // Edit spare from table
    $(document).on('click', '.edit-spare', function() {
        const spareId = $(this).data('id');
        
        // Get spare data from the row or fetch via AJAX
        const row = $(this).closest('tr');
        const partName = row.find('td:nth-child(1) strong').text();
        const partCode = row.find('td:nth-child(2) .badge').text();
        const priceText = row.find('td:nth-child(3)').text();
        const price = priceText.includes('₹') ? priceText.replace(/[₹,]/g, '') : '0';
        const machineName = row.find('td:nth-child(4) .badge').text();
        
        // Try to fetch complete spare details via AJAX first
        $.ajax({
            url: 'ajax/get_spare_details.php', // You may need to create this endpoint
            type: 'GET',
            data: { id: spareId },
            dataType: 'json',
            success: function(data) {
                if(data.success) {
                    fillFormWithSpareData(data.spare);
                } else {
                    // Fallback to basic data from table
                    fillFormWithBasicData({
                        id: spareId,
                        part_name: partName,
                        part_code: partCode,
                        price: price,
                        machine_name: machineName
                    });
                }
            },
            error: function() {
                // Fallback to basic data from table
                fillFormWithBasicData({
                    id: spareId,
                    part_name: partName,
                    part_code: partCode,
                    price: price,
                    machine_name: machineName
                });
            }
        });
    });

    function fillFormWithSpareData(spare) {
        $('#spareId').val(spare.id);
        $('#part_name').val(spare.part_name);
        $('#part_code').val(spare.part_code);
        $('#price').val(spare.price);
        $('#machine_id').val(spare.machine_id);
        $('#machine_name').val(spare.machine_name);
        $('#description').val(spare.description);
        
        setFormReadOnly(true);
        $('#saveBtn').hide();
        $('#editBtn').show();
        $('#deleteBtn').show();
        $('#updateBtn').hide();
        $('#formTitle').text('Spare Part Details - ' + spare.part_name);
        
        $('html, body').animate({ scrollTop: $('#spareForm').offset().top - 100 }, 500);
    }

    function fillFormWithBasicData(spare) {
        $('#spareId').val(spare.id);
        $('#part_name').val(spare.part_name);
        $('#part_code').val(spare.part_code);
        $('#price').val(spare.price);
        $('#machine_name').val(spare.machine_name);
        
        setFormReadOnly(true);
        $('#saveBtn').hide();
        $('#editBtn').show();
        $('#deleteBtn').show();
        $('#updateBtn').hide();
        $('#formTitle').text('Spare Part Details - ' + spare.part_name);
        
        $('html, body').animate({ scrollTop: $('#spareForm').offset().top - 100 }, 500);
    }

    $('#editBtn').on('click', function() {
        setFormReadOnly(false);
        $('#editBtn').hide();
        $('#updateBtn').show();
        $('#formAction').val('update_spare');
    });

    $('#deleteBtn').on('click', function() {
        const spareId = $('#spareId').val();
        const partName = $('#part_name').val();
        if (spareId && confirm('Are you sure you want to delete Spare Part "' + partName + '"?')) {
            window.location.href = 'spares.php?delete=' + spareId;
        }
    });

    $('#resetBtn').on('click', resetForm);

    function setFormReadOnly(readonly) {
        $('#spareForm input, #spareForm textarea').not('#spareId, #formAction, #machine_id').prop('readonly', readonly);
        
        if(readonly) {
            $('#editBtn, #deleteBtn').prop('disabled', false);
        }
    }

    function resetForm() {
        $('#spareForm')[0].reset();
        $('#spareId').val('');
        $('#machine_id').val('');
        $('#formAction').val('create_spare');
        setFormReadOnly(false);
        $('#saveBtn').show();
        $('#editBtn, #updateBtn, #deleteBtn').hide();
        $('#formTitle').text('Create Spare Part');
    }

    // Form validation
    $('#spareForm').on('submit', function(e) {
        const partName = $('#part_name').val().trim();
        if (!partName) {
            e.preventDefault();
            alert('Part name is required!');
            return false;
        }
        
        const price = parseFloat($('#price').val());
        if ($('#price').val() && (isNaN(price) || price < 0)) {
            e.preventDefault();
            alert('Please enter a valid price!');
            return false;
        }
    });

    // Price validation
    $('#price').on('input', function() {
        let value = $(this).val();
        // Remove any non-numeric characters except decimal point
        value = value.replace(/[^\d.]/g, '');
        // Ensure only one decimal point
        const parts = value.split('.');
        if (parts.length > 2) {
            value = parts[0] + '.' + parts.slice(1).join('');
        }
        $(this).val(value);
    });

    // Part code formatting (uppercase)
    $('#part_code').on('input', function() {
        $(this).val($(this).val().toUpperCase());
    });
});