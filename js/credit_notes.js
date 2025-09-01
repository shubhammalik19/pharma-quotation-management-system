/* credit_notes.js - Credit Note Management
   Dependencies: jQuery, jQuery UI (autocomplete), Bootstrap
   API Endpoints:
     - ../ajax/unified_search.php
     - ../ajax/send_credit_note_email.php
*/

// ---------- UTILITY FUNCTIONS ----------
function formatDate(dateStr) { 
    if (!dateStr) return ''; 
    const dt = new Date(dateStr); 
    return dt.toLocaleDateString('en-GB'); 
}

function escapeHtml(text) { 
    if (!text) return ''; 
    const map = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}; 
    return text.toString().replace(/[&<>"']/g, x => map[x]); 
}

// ---------- PRINT & EMAIL FUNCTIONS ----------
function printCreditNote(cnId) {
    if (!cnId) {
        cnId = $('#cnId').val();
    }
    if (cnId) {
        window.open(`../docs/print_credit_note.php?id=${cnId}`, '_blank');
    } else {
        alert('Please select a credit note to print');
    }
}

function openEmailCreditNoteModal(cnId) {
    if (!cnId) {
        alert('Please select a credit note to email');
        return;
    }
    
    $.getJSON('../ajax/get_credit_note_details.php', { id: cnId })
    .done(function(response) {
        if (response.success) {
            $('#emailCreditNoteModalLabel').text('Email Credit Note: ' + response.credit_note.credit_note_number);
            $('#emailCreditNoteId').val(cnId);
            $('#cn_recipient_email').val(response.credit_note.customer_email || '');
            $('#cn_additional_emails').val('');
            $('#cn_custom_message').val('');
            $('#emailCreditNoteModal').modal('show');
        } else {
            alert(response.message || 'Failed to fetch credit note details');
        }
    })
    .fail(function() {
        alert('Error loading credit note details');
    });
}

$(document).ready(function() {
    // ---------- AUTOCOMPLETE SETUP ----------
    
    // Customer autocomplete
    $("#customer_name").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: '../ajax/unified_search.php',
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
                    console.error('Customer search failed');
                    response([]);
                }
            });
        },
        minLength: 2,
        delay: 300,
        select: function(event, ui) {
            $("#customer_id").val(ui.item.id);
            $("#customer_name").val(ui.item.value);
            return false;
        },
        change: function(event, ui) {
            if (!ui.item) {
                $("#customer_id").val('');
                if ($(this).val().length > 0) {
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            } else {
                $(this).removeClass('is-invalid');
            }
        },
        search: function() {
            $(this).addClass('ui-autocomplete-loading');
        },
        response: function() {
            $(this).removeClass('ui-autocomplete-loading');
        }
    });

    // ---------- SEARCH FUNCTIONALITY ----------
    
    // Enhanced search with autocomplete and data loading
    $("#cnSearch").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: '../ajax/unified_search.php',
                type: 'GET',
                data: {
                    term: request.term,
                    type: 'AUTOCOMPLETE_CREDIT_NOTES'
                },
                dataType: 'json',
                success: function(data) {
                    response(data);
                },
                error: function() {
                    console.error('Credit note search failed');
                    response([]);
                }
            });
        },
        minLength: 2,
        delay: 300,
        select: function(event, ui) {
            if (ui.item && ui.item.id) {
                loadCreditNoteData(ui.item.id);
                $(this).val(ui.item.value);
                return false;
            }
        },
        search: function() {
            $(this).addClass('ui-autocomplete-loading');
        },
        response: function() {
            $(this).removeClass('ui-autocomplete-loading');
        }
    }).focus(function() {
        $(this).css({
            'background-color': '#f8f9fa',
            'border-color': '#007bff',
            'box-shadow': '0 0 0 0.2rem rgba(0, 123, 255, 0.25)'
        });
    }).blur(function() {
        $(this).css({
            'background-color': '',
            'border-color': '',
            'box-shadow': ''
        });
    });
    
    // Function to load credit note data
    function loadCreditNoteData(cnId) {
        if (!cnId) return;
        
        $.ajax({
            url: '../ajax/get_credit_note_details.php',
            type: 'GET',
            data: { id: cnId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const data = response.credit_note;
                    loadForEdit({
                        id: data.id,
                        cn_number: data.credit_note_number,
                        credit_date: data.credit_date,
                        customer_name: data.customer_name,
                        customer_id: data.customer_id,
                        original_invoice: data.original_invoice,
                        total_amount: data.total_amount,
                        reason: data.reason,
                        status: data.status
                    });
                } else {
                    showNotification(response.message || 'Failed to load credit note details', 'error');
                }
            },
            error: function() {
                showNotification('Error loading credit note details', 'error');
            }
        });
    }
    
    $("#searchBtn").click(function() {
        const searchTerm = $("#cnSearch").val().trim();
        const currentUrl = new URL(window.location.href);
        if (searchTerm) {
            currentUrl.searchParams.set('search', searchTerm);
        } else {
            currentUrl.searchParams.delete('search');
        }
        currentUrl.searchParams.delete('page');
        window.location.href = currentUrl.toString();
    });
    
    $("#clearBtn").click(function() {
        $("#cnSearch").val('');
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.delete('search');
        currentUrl.searchParams.delete('page');
        window.location.href = currentUrl.toString();
    });
    
    // Enter key search
    $("#cnSearch").keypress(function(e) {
        if (e.which == 13) {
            $("#searchBtn").click();
        }
    });
    
    // ---------- FORM STATE MANAGEMENT ----------
    
    let isEditing = false;
    
    function resetForm() {
        $("#cnForm")[0].reset();
        $("#cnId").val('');
        $("#customer_id").val('');
        $("#formAction").val('create_cn');
        $("#formTitle").text('Credit Note Details');
        
        // Generate new CN number
        const year = new Date().getFullYear();
        const random = String(Math.floor(Math.random() * 9999) + 1).padStart(4, '0');
        $("#cn_number").val('CN-' + year + '-' + random);
        $("#credit_date").val(new Date().toISOString().split('T')[0]);
        
        // Reset button states
        $("#saveBtn").show();
        $("#editBtn, #updateBtn, #deleteBtn, #printBtn, #emailBtn").hide();
        
        // Enable all fields
        $("#cnForm input, #cnForm textarea, #cnForm select").prop('disabled', false);
        
        // Remove validation classes
        $("#customer_name").removeClass('is-invalid');
        
        isEditing = false;
        
        showNotification('Form reset successfully', 'info');
    }
    
    function loadForEdit(data) {
        $("#cnId").val(data.id);
        $("#cn_number").val(data.cn_number);
        $("#credit_date").val(data.credit_date);
        $("#customer_name").val(data.customer_name);
        $("#customer_id").val(data.customer_id);
        $("#original_invoice").val(data.original_invoice);
        $("#total_amount").val(data.total_amount);
        $("#reason").val(data.reason);
        $("#status").val(data.status);
        
        $("#formAction").val('update_cn');
        $("#formTitle").text('Edit Credit Note - ' + data.cn_number);
        
        // Button states for viewing
        $("#saveBtn").hide();
        $("#editBtn, #printBtn, #emailBtn").show();
        $("#updateBtn, #deleteBtn").hide();
        
        // Disable all fields initially (view mode)
        $("#cnForm input, #cnForm textarea, #cnForm select").prop('disabled', true);
        
        isEditing = false;
        
        // Scroll to form
        $('html, body').animate({
            scrollTop: $('#cnForm').offset().top - 100
        }, 500);
        
        showNotification('Credit note loaded for viewing', 'success');
    }
    
    // ---------- BUTTON HANDLERS ----------
    
    // Edit button click handlers from table
    $(document).on('click', '.edit-cn', function() {
        const data = $(this).data();
        loadForEdit(data);
    });
    
    // Edit button functionality (enable editing)
    $("#editBtn").click(function() {
        // Enable all fields
        $("#cnForm input, #cnForm textarea, #cnForm select").prop('disabled', false);
        
        // Update button states
        $("#editBtn").hide();
        $("#updateBtn, #deleteBtn").show();
        
        isEditing = true;
        showNotification('Form is now editable', 'info');
    });
    
    // Update button functionality
    $("#updateBtn").click(function() {
        $("#cnForm").submit();
    });
    
    // Delete button functionality
    $("#deleteBtn").click(function() {
        const cnNumber = $("#cn_number").val();
        if (confirm(`Are you sure you want to delete Credit Note "${cnNumber}"? This action cannot be undone.`)) {
            const id = $("#cnId").val();
            window.location.href = '?delete=' + id;
        }
    });
    
    // Print button functionality
    $(document).on('click', '#printBtn', function() {
        const cnId = $("#cnId").val();
        if (cnId) {
            printCreditNote(cnId);
        }
    });
    
    // Email button functionality
    $(document).on('click', '#emailBtn', function() {
        const cnId = $("#cnId").val();
        if (cnId) {
            openEmailCreditNoteModal(cnId);
        }
    });
    
    // Reset button functionality
    $("#resetBtn").click(function() {
        if (confirm('Are you sure you want to reset the form? All unsaved changes will be lost.')) {
            resetForm();
        }
    });
    
    // ---------- FORM VALIDATION & SUBMISSION ----------
    
    $("#cnForm").submit(function(e) {
        let isValid = true;
        
        // Customer validation
        const customerName = $("#customer_name").val().trim();
        const customerId = $("#customer_id").val();
        
        if (!customerName) {
            $("#customer_name").addClass('is-invalid');
            showNotification('Please enter customer name', 'error');
            isValid = false;
        }
        
        if (!customerId) {
            $("#customer_name").addClass('is-invalid');
            showNotification('Please select a valid customer from the dropdown', 'error');
            isValid = false;
        }
        
        // Amount validation
        const totalAmount = parseFloat($("#total_amount").val());
        if (isNaN(totalAmount) || totalAmount <= 0) {
            $("#total_amount").addClass('is-invalid');
            showNotification('Please enter a valid credit amount', 'error');
            isValid = false;
        } else {
            $("#total_amount").removeClass('is-invalid');
        }
        
        // Reason validation
        const reason = $("#reason").val().trim();
        if (!reason) {
            $("#reason").addClass('is-invalid');
            showNotification('Please provide a reason for the credit note', 'error');
            isValid = false;
        } else {
            $("#reason").removeClass('is-invalid');
        }
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
        
        // Show confirmation for updates
        if (isEditing && $("#formAction").val() === 'update_cn') {
            if (!confirm('Are you sure you want to update this credit note?')) {
                e.preventDefault();
                return false;
            }
        }
        
        // Show loading state
        const submitBtn = $(this).find('[type="submit"]:visible');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');
        
        return true;
    });
    
    // ---------- AUTO-GENERATION ----------
    
    // Auto-generate CN number when customer changes (for new entries only)
    $("#customer_name").on('autocompleteselect', function(event, ui) {
        if ($("#formAction").val() === 'create_cn') {
            const customerCode = ui.item.value.substring(0, 3).toUpperCase();
            const year = new Date().getFullYear();
            const random = String(Math.floor(Math.random() * 9999) + 1).padStart(4, '0');
            $("#cn_number").val('CN-' + year + '-' + customerCode + '-' + random);
        }
    });
    
    // ---------- EMAIL FUNCTIONALITY ----------
    
    $('#sendCreditNoteEmailBtn').on('click', function() {
        const btn = $(this);
        const originalText = btn.html();
        const formData = $('#emailCreditNoteForm').serialize();
        
        // Validation
        const recipientEmail = $('#cn_recipient_email').val().trim();
        if (!recipientEmail) {
            showNotification('Please enter a recipient email address', 'error');
            return;
        }
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Sending...');
        
        $.ajax({
            url: '../ajax/send_credit_note_email.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.message || 'Email sent successfully', 'success');
                    $('#emailCreditNoteModal').modal('hide');
                } else {
                    showNotification(response.message || 'Failed to send email', 'error');
                }
            },
            error: function() {
                showNotification('Error sending email. Please try again.', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // ---------- NOTIFICATION SYSTEM ----------
    
    function showNotification(message, type = 'info') {
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        }[type] || 'alert-info';

        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        // Remove existing notifications
        $('.alert').remove();
        
        // Add new notification at top of container
        $('.container-fluid').first().prepend(alertHtml);

        // Auto-hide after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }
    
    // ---------- INITIALIZATION ----------
    
    console.log('Credit Note management system initialized successfully');
}); 