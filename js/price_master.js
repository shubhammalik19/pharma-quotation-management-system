/* price_master.js - Uniform design consistent with other pages
   Requires: jQuery, jQuery UI (autocomplete), Bootstrap (for modals)
   Endpoints used:
     - ajax/unified_search.php           (AUTOCOMPLETE_MACHINES)
     - ajax/get_price_details.php        (id) - if needed
*/

// ---------- SMALL HELPERS ----------
function fmtDate(d){ if(!d) return ''; const dt=new Date(d); return dt.toLocaleDateString('en-GB'); }
function esc(t){ if(!t) return ''; const m={'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}; return t.toString().replace(/[&<>"']/g, x=>m[x]); }

$(document).ready(function() {
    
    // Autocomplete for Price Search (Machine-based)
    $('#priceSearch').autocomplete({
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
            $('#priceSearch').val(ui.item.value);
            $('#searchBtn').click();
            return false;
        }
    });

    // Search functionality
    $('#searchBtn').on('click', function() {
        const searchTerm = $('#priceSearch').val().trim();
        if (searchTerm) {
            window.location.href = 'price_master.php?search=' + encodeURIComponent(searchTerm);
        }
    });

    $('#clearBtn').on('click', () => window.location.href = 'price_master.php');

    $('#priceSearch').on('keypress', e => e.which === 13 && $('#searchBtn').click());

    // Edit price from table
    $(document).on('click', '.edit-price', function() {
        const priceId = $(this).data('id');
        
        // Get price data from the row
        const row = $(this).closest('tr');
        const machineName = row.find('td:nth-child(1) strong').text();
        const model = row.find('td:nth-child(2)').text();
        const priceText = row.find('td:nth-child(3) strong').text();
        const price = priceText.replace(/[₹,]/g, '');
        const validFromText = row.find('td:nth-child(4)').text();
        const validToText = row.find('td:nth-child(5)').text();
        
        // Convert dates from DD-MM-YYYY to YYYY-MM-DD for input fields
        const validFrom = convertDateFormat(validFromText);
        const validTo = convertDateFormat(validToText);
        
        // Try to fetch complete price details via AJAX first
        $.ajax({
            url: 'ajax/get_price_details.php', // You may need to create this endpoint
            type: 'GET',
            data: { id: priceId },
            dataType: 'json',
            success: function(data) {
                if(data.success) {
                    fillFormWithPriceData(data.price);
                } else {
                    // Fallback to basic data from table
                    fillFormWithBasicData({
                        id: priceId,
                        machine_name: machineName,
                        price: price,
                        valid_from: validFrom,
                        valid_to: validTo
                    });
                }
            },
            error: function() {
                // Fallback to basic data from table
                fillFormWithBasicData({
                    id: priceId,
                    machine_name: machineName,
                    price: price,
                    valid_from: validFrom,
                    valid_to: validTo
                });
            }
        });
    });

    function fillFormWithPriceData(price) {
        $('#priceId').val(price.id);
        $('#machine_id').val(price.machine_id);
        $('#price').val(price.price);
        $('#valid_from').val(price.valid_from);
        $('#valid_to').val(price.valid_to);
        
        setFormReadOnly(true);
        $('#saveBtn').hide();
        $('#editBtn').show();
        $('#deleteBtn').show();
        $('#updateBtn').hide();
        $('#formTitle').text('Price Details - ' + price.machine_name);
        
        $('html, body').animate({ scrollTop: $('#priceForm').offset().top - 100 }, 500);
    }

    function fillFormWithBasicData(price) {
        $('#priceId').val(price.id);
        $('#price').val(price.price);
        $('#valid_from').val(price.valid_from);
        $('#valid_to').val(price.valid_to);
        
        // Find machine option by name (fallback method)
        $('#machine_id option').each(function() {
            if ($(this).text().includes(price.machine_name)) {
                $(this).prop('selected', true);
                return false;
            }
        });
        
        setFormReadOnly(true);
        $('#saveBtn').hide();
        $('#editBtn').show();
        $('#deleteBtn').show();
        $('#updateBtn').hide();
        $('#formTitle').text('Price Details - ' + price.machine_name);
        
        $('html, body').animate({ scrollTop: $('#priceForm').offset().top - 100 }, 500);
    }

    // Helper function to convert DD-MM-YYYY to YYYY-MM-DD
    function convertDateFormat(dateStr) {
        if (!dateStr) return '';
        const parts = dateStr.split('-');
        if (parts.length === 3) {
            return parts[2] + '-' + parts[1] + '-' + parts[0]; // YYYY-MM-DD
        }
        return dateStr;
    }

    $('#editBtn').on('click', function() {
        setFormReadOnly(false);
        $('#editBtn').hide();
        $('#updateBtn').show();
        $('#formAction').val('update_price');
    });

    $('#deleteBtn').on('click', function() {
        const priceId = $('#priceId').val();
        const machineName = $('#machine_id option:selected').text();
        if (priceId && confirm('Are you sure you want to delete Price record for "' + machineName + '"?')) {
            window.location.href = 'price_master.php?delete=' + priceId;
        }
    });

    $('#resetBtn').on('click', resetForm);

    function setFormReadOnly(readonly) {
        $('#priceForm input').not('#priceId, #formAction').prop('readonly', readonly);
        $('#machine_id').prop('disabled', readonly);
        
        if(readonly) {
            $('#editBtn, #deleteBtn').prop('disabled', false);
        }
    }

    function resetForm() {
        $('#priceForm')[0].reset();
        $('#priceId').val('');
        $('#formAction').val('create_price');
        setFormReadOnly(false);
        $('#saveBtn').show();
        $('#editBtn, #updateBtn, #deleteBtn').hide();
        $('#formTitle').text('Create Price Entry');
        
        // Set default dates
        const today = new Date().toISOString().split('T')[0];
        const nextYear = new Date();
        nextYear.setFullYear(nextYear.getFullYear() + 1);
        const nextYearDate = nextYear.toISOString().split('T')[0];
        
        $('#valid_from').val(today);
        $('#valid_to').val(nextYearDate);
    }

    // Form validation
    $('#priceForm').on('submit', function(e) {
        const machineId = $('#machine_id').val();
        const price = $('#price').val();
        const validFrom = $('#valid_from').val();
        const validTo = $('#valid_to').val();
        
        // Basic required field validation
        if (!machineId || !price || !validFrom || !validTo) {
            e.preventDefault();
            alert('All fields are required!');
            return false;
        }
        
        // Check if valid_from is before valid_to
        if (new Date(validFrom) >= new Date(validTo)) {
            e.preventDefault();
            alert('Valid From date must be before Valid To date!');
            return false;
        }
        
        // Price validation
        if (parseFloat(price) <= 0) {
            e.preventDefault();
            alert('Price must be greater than 0!');
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

    // Date validation - ensure valid_from is not after valid_to
    $('#valid_from, #valid_to').on('change', function() {
        const validFrom = $('#valid_from').val();
        const validTo = $('#valid_to').val();
        
        if (validFrom && validTo && new Date(validFrom) >= new Date(validTo)) {
            alert('Valid From date must be before Valid To date!');
            if ($(this).attr('id') === 'valid_from') {
                $(this).val('');
            } else {
                $('#valid_to').val('');
            }
        }
    });

    // Initialize with default dates
    resetForm();
});