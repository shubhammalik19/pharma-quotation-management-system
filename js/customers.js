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
    
    // Function to reload page after actions (for manual use if needed)
    window.reloadAfterAction = function(delay = 1500) {
        setTimeout(function() {
            window.location.reload();
        }, delay);
    };
    
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
        const deleteBtn = $(this);
        
        if (customerId) {
            // Show loading state
            deleteBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Checking...');
            
            // Check dependencies via AJAX
            $.ajax({
                url: 'ajax/check_customer_dependencies.php',
                type: 'GET',
                data: { id: customerId },
                dataType: 'json',
                success: function(response) {
                    deleteBtn.prop('disabled', false).html('<i class="bi bi-trash"></i> Delete');
                    
                    if (response.success) {
                        if (response.can_delete) {
                            // Safe to delete
                            const confirmMessage = `Are you sure you want to delete ${response.entity_type} "${response.customer_name}"?\n\n` +
                                                 `This action cannot be undone!`;
                            
                            if (confirm(confirmMessage)) {
                                deleteBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Deleting...');
                                window.location.href = 'customers.php?delete=' + customerId;
                            }
                        } else {
                            // Has dependencies
                            const dependencyList = response.dependencies.join(', ');
                            alert(`Cannot delete ${response.entity_type} "${response.customer_name}"!\n\n` +
                                  `This customer is referenced in:\n${dependencyList}\n\n` +
                                  `Please remove these references first before deleting the customer.`);
                        }
                    } else {
                        alert('Error checking dependencies: ' + response.message);
                    }
                },
                error: function() {
                    deleteBtn.prop('disabled', false).html('<i class="bi bi-trash"></i> Delete');
                    
                    // Fallback to basic confirmation
                    const confirmMessage = `Are you sure you want to delete Customer "${companyName}"?\n\n` +
                                         `This will check for any related records and prevent deletion if dependencies exist.\n\n` +
                                         `This action cannot be undone!`;
                    
                    if (confirm(confirmMessage)) {
                        deleteBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Deleting...');
                        window.location.href = 'customers.php?delete=' + customerId;
                    }
                }
            });
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

    // Form validation and submission
    $('#customerForm').on('submit', function(e) {
        const companyName = $('#company_name').val().trim();
        if (!companyName) {
            e.preventDefault();
            alert('Company name is required!');
            return false;
        }
        
        // Add loading state to submit button
        const submitBtn = $(this).find('button[type="submit"]:visible');
        submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Processing...');
        
        // The form will submit normally and redirect via PHP
        return true;
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
    
    // Enhanced delete confirmation for table delete links
    $(document).on('click', 'a[href*="delete="]', function(e) {
        e.preventDefault();
        const deleteUrl = $(this).attr('href');
        const customerId = deleteUrl.split('delete=')[1];
        const customerName = $(this).closest('tr').find('td:nth-child(2) strong').text();
        const deleteLink = $(this);
        
        // Show loading state
        const originalHtml = deleteLink.html();
        deleteLink.html('<i class="bi bi-hourglass-split"></i>').addClass('disabled');
        
        // Check dependencies via AJAX
        $.ajax({
            url: 'ajax/check_customer_dependencies.php',
            type: 'GET',
            data: { id: customerId },
            dataType: 'json',
            success: function(response) {
                deleteLink.html(originalHtml).removeClass('disabled');
                
                if (response.success) {
                    if (response.can_delete) {
                        // Safe to delete
                        const confirmMessage = `Are you sure you want to delete ${response.entity_type} "${response.customer_name}"?\n\n` +
                                             `This action cannot be undone!`;
                        
                        if (confirm(confirmMessage)) {
                            deleteLink.html('<i class="bi bi-hourglass-split"></i>').addClass('disabled');
                            window.location.href = deleteUrl;
                        }
                    } else {
                        // Has dependencies
                        const dependencyList = response.dependencies.join(', ');
                        alert(`Cannot delete ${response.entity_type} "${response.customer_name}"!\n\n` +
                              `This customer is referenced in:\n${dependencyList}\n\n` +
                              `Please remove these references first before deleting the customer.`);
                    }
                } else {
                    alert('Error checking dependencies: ' + response.message);
                }
            },
            error: function() {
                deleteLink.html(originalHtml).removeClass('disabled');
                
                // Fallback to basic confirmation
                const confirmMessage = `Are you sure you want to delete Customer "${customerName}"?\n\n` +
                                     `This will check for any related records and prevent deletion if dependencies exist.\n\n` +
                                     `This action cannot be undone!`;
                
                if (confirm(confirmMessage)) {
                    window.location.href = deleteUrl;
                }
            }
        });
    });
});