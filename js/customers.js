/* customers.js - Autocomplete functionality for customer search
   Requires: jQuery, jQuery UI (autocomplete), Bootstrap (for modals)
   Endpoints used:
     - ajax/unified_search.php           (AUTOCOMPLETE_CUSTOMERS, AUTOCOMPLETE_CITIES, AUTOCOMPLETE_STATES)
     - ajax/get_customer_details.php     (id) - if needed
*/

// ---------- SMALL HELPERS ----------
function fmtDate(d){ if(!d) return ''; const dt=new Date(d); return dt.toLocaleDateString('en-GB'); }
function esc(t){ if(!t) return ''; const m={'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}; return t.toString().replace(/[&<>"']/g, x=>m[x]); }

$(document).ready(function() {
    
    // Autocomplete for Customer Search
    $('#customerSearch').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: 'ajax/unified_search.php',
                type: 'GET',
                data: {
                    term: request.term,
                    type: 'AUTOCOMPLETE_CUSTOMERS'
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
        delay: 300,
        select: function(event, ui) {
            $('#customerSearch').val(ui.item.value);
            $('#searchBtn').click();
            return false;
        }
    });

    // Autocomplete for City
    $('#city').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: 'ajax/unified_search.php',
                type: 'GET',
                data: {
                    term: request.term,
                    type: 'AUTOCOMPLETE_CITIES'
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
        delay: 300
    });

    // Autocomplete for State
    $('#state').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: 'ajax/unified_search.php',
                type: 'GET',
                data: {
                    term: request.term,
                    type: 'AUTOCOMPLETE_STATES'
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
        delay: 300
    });

    // Search functionality - works without autocomplete
    $('#searchBtn').on('click', function() {
        const searchTerm = $('#customerSearch').val().trim();
        console.log('Search button clicked, term:', searchTerm);
        if (searchTerm) {
            window.location.href = 'customers.php?search=' + encodeURIComponent(searchTerm);
        } else {
            window.location.href = 'customers.php';
        }
    });

    $('#clearBtn').on('click', function() {
        console.log('Clear button clicked');
        window.location.href = 'customers.php';
    });

    $('#customerSearch').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            console.log('Enter key pressed in search');
            $('#searchBtn').click();
        }
    });

    // Edit customer from table
    $(document).on('click', '.edit-customer', function() {
        const customerId = $(this).data('id');
        
        // Get customer data from the row
        const row = $(this).closest('tr');
        const entityType = $(this).closest('tr').find('.badge').text().toLowerCase();
        const companyName = row.find('td:nth-child(2) strong').text();
        const contactPerson = row.find('td:nth-child(3)').text();
        const phone = row.find('td:nth-child(4)').text();
        const email = row.find('td:nth-child(5)').text();
        const cityState = row.find('td:nth-child(6)').text();
        
        // Parse city and state
        const cityStateParts = cityState.split(', ');
        const city = cityStateParts[0] || '';
        const state = cityStateParts[1] || '';
        
        // We need to fetch complete customer details via AJAX
        $.ajax({
            url: 'ajax/get_customer_details.php', // You may need to create this endpoint
            type: 'GET',
            data: { id: customerId },
            dataType: 'json',
            success: function(data) {
                if(data.success) {
                    fillFormWithCustomerData(data.customer);
                } else {
                    // Fallback to basic data from table
                    fillFormWithBasicData({
                        id: customerId,
                        entity_type: entityType,
                        company_name: companyName,
                        contact_person: contactPerson,
                        phone: phone,
                        email: email,
                        city: city,
                        state: state
                    });
                }
            },
            error: function() {
                // Fallback to basic data from table
                fillFormWithBasicData({
                    id: customerId,
                    entity_type: entityType,
                    company_name: companyName,
                    contact_person: contactPerson,
                    phone: phone,
                    email: email,
                    city: city,
                    state: state
                });
            }
        });
    });

    function fillFormWithCustomerData(customer) {
        $('#customerId').val(customer.id);
        $('#entity_type').val(customer.entity_type);
        $('#company_name').val(customer.company_name);
        $('#contact_person').val(customer.contact_person);
        $('#phone').val(customer.phone);
        $('#email').val(customer.email);
        $('#gst_no').val(customer.gst_no);
        $('#address').val(customer.address);
        $('#city').val(customer.city);
        $('#state').val(customer.state);
        $('#pincode').val(customer.pincode);
        
        setFormReadOnly(true);
        $('#saveBtn').hide();
        $('#editBtn').show();
        $('#deleteBtn').show();
        $('#updateBtn').hide();
        $('#formTitle').text('Customer Details - ' + customer.company_name);
        
        $('html, body').animate({ scrollTop: $('#customerForm').offset().top - 100 }, 500);
    }

    function fillFormWithBasicData(customer) {
        $('#customerId').val(customer.id);
        $('#entity_type').val(customer.entity_type);
        $('#company_name').val(customer.company_name);
        $('#contact_person').val(customer.contact_person);
        $('#phone').val(customer.phone);
        $('#email').val(customer.email);
        $('#city').val(customer.city);
        $('#state').val(customer.state);
        
        setFormReadOnly(true);
        $('#saveBtn').hide();
        $('#editBtn').show();
        $('#deleteBtn').show();
        $('#updateBtn').hide();
        $('#formTitle').text('Customer Details - ' + customer.company_name);
        
        $('html, body').animate({ scrollTop: $('#customerForm').offset().top - 100 }, 500);
    }

    $('#editBtn').on('click', function() {
        setFormReadOnly(false);
        $('#editBtn').hide();
        $('#updateBtn').show();
        $('#formAction').val('update_customer');
    });

    $('#deleteBtn').on('click', function() {
        const customerId = $('#customerId').val();
        const companyName = $('#company_name').val();
        if (customerId && confirm('Are you sure you want to delete Customer "' + companyName + '"?')) {
            window.location.href = 'customers.php?delete=' + customerId;
        }
    });

    $('#resetBtn').on('click', resetForm);

    function setFormReadOnly(readonly) {
        $('#customerForm input, #customerForm textarea, #customerForm select').not('#customerId, #formAction').prop('readonly', readonly);
        $('#customerForm select').prop('disabled', readonly);
        
        if(readonly) {
            $('#editBtn, #deleteBtn').prop('disabled', false);
        }
    }

    function resetForm() {
        $('#customerForm')[0].reset();
        $('#customerId').val('');
        $('#formAction').val('create_customer');
        setFormReadOnly(false);
        $('#saveBtn').show();
        $('#editBtn, #updateBtn, #deleteBtn').hide();
        $('#formTitle').text('Create Customer/Vendor');
    }

    // Form validation
    $('#customerForm').on('submit', function(e) {
        const companyName = $('#company_name').val().trim();
        if (!companyName) {
            e.preventDefault();
            alert('Company name is required!');
            return false;
        }
    });

    // GST number formatting
    $('#gst_no').on('input', function() {
        let value = $(this).val().toUpperCase();
        // Remove any non-alphanumeric characters
        value = value.replace(/[^A-Z0-9]/g, '');
        // Limit to 15 characters (GST format)
        if (value.length > 15) {
            value = value.substring(0, 15);
        }
        $(this).val(value);
    });

    // Phone number formatting
    $('#phone').on('input', function() {
        let value = $(this).val();
        // Remove any non-numeric characters except + and spaces
        value = value.replace(/[^\d\+\s\-\(\)]/g, '');
        $(this).val(value);
    });

    // Pincode validation
    $('#pincode').on('input', function() {
        let value = $(this).val();
        // Remove any non-numeric characters
        value = value.replace(/[^\d]/g, '');
        // Limit to 6 digits
        if (value.length > 6) {
            value = value.substring(0, 6);
        }
        $(this).val(value);
    });
});