/* debit_notes.js - Debit Note Management
   Dependencies: jQuery, jQuery UI (autocomplete), Bootstrap
   API Endpoints:
     - ../ajax/unified_search.php
     - ../ajax/send_debit_note_email.php
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
function printDebitNote(dnId) {
    if (!dnId) {
        dnId = $('#dnId').val();
    }
    if (dnId) {
        window.open(`../docs/print_debit_note.php?id=${dnId}`, '_blank');
    } else {
        alert('Please select a debit note to print');
    }
}

function openEmailDebitNoteModal(dnId) {
    if (!dnId) {
        alert('Please select a debit note to email');
        return;
    }
    
    $.getJSON('../ajax/get_debit_note_details.php', { id: dnId })
    .done(function(response) {
        if (response.success) {
            $('#emailDebitNoteModalLabel').text('Email Debit Note: ' + response.debit_note.debit_note_number);
            $('#emailDebitNoteId').val(dnId);
            $('#dn_recipient_email').val(response.debit_note.vendor_email || '');
            $('#dn_additional_emails').val('');
            $('#dn_custom_message').val('');
            $('#emailDebitNoteModal').modal('show');
        } else {
            alert(response.message || 'Failed to fetch debit note details');
        }
    })
    .fail(function() {
        alert('Error loading debit note details');
    });
}

$(document).ready(function() {
    // ---------- AUTOCOMPLETE SETUP ----------
    
    // Vendor autocomplete
    $("#vendor_name").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: '../ajax/unified_search.php',
                type: 'GET',
                data: {
                    term: request.term,
                    type: 'AUTOCOMPLETE_VENDORS'
                },
                dataType: 'json',
                success: function(data) {
                    response(data);
                },
                error: function() {
                    console.error('Vendor search failed');
                    response([]);
                }
            });
        },
        minLength: 2,
        delay: 300,
        select: function(event, ui) {
            $("#vendor_id").val(ui.item.id);
            $("#vendor_name").val(ui.item.value);
            return false;
        },
        change: function(event, ui) {
            if (!ui.item) {
                $("#vendor_id").val('');
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
    $("#dnSearch").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: '../ajax/unified_search.php',
                type: 'GET',
                data: {
                    term: request.term,
                    type: 'AUTOCOMPLETE_DEBIT_NOTES'
                },
                dataType: 'json',
                success: function(data) {
                    response(data);
                },
                error: function() {
                    console.error('Debit note search failed');
                    response([]);
                }
            });
        },
        minLength: 2,
        delay: 300,
        select: function(event, ui) {
            if (ui.item && ui.item.id) {
                loadDebitNoteData(ui.item.id);
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
    
    // Function to load debit note data
    function loadDebitNoteData(dnId) {
        if (!dnId) return;
        
        $.ajax({
            url: '../ajax/get_debit_note_details.php',
            type: 'GET',
            data: { id: dnId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const data = response.debit_note;
                    loadForEdit({
                        id: data.id,
                        dn_number: data.debit_note_number,
                        debit_date: data.debit_date,
                        vendor_name: data.vendor_name,
                        vendor_id: '', // Will be filled by autocomplete if vendor exists
                        original_invoice: data.original_invoice,
                        total_amount: data.total_amount,
                        reason: data.reason,
                        status: data.status
                    });
                } else {
                    showNotification(response.message || 'Failed to load debit note details', 'error');
                }
            },
            error: function() {
                showNotification('Error loading debit note details', 'error');
            }
        });
    }
    
    $("#searchBtn").click(function() {
        const searchTerm = $("#dnSearch").val().trim();
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
        $("#dnSearch").val('');
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.delete('search');
        currentUrl.searchParams.delete('page');
        window.location.href = currentUrl.toString();
    });
    
    // Enter key search
    $("#dnSearch").keypress(function(e) {
        if (e.which == 13) {
            $("#searchBtn").click();
        }
    });
    
    // ---------- FORM STATE MANAGEMENT ----------
    
    let isEditing = false;
    
    function resetForm() {
        $("#dnForm")[0].reset();
        $("#dnId").val('');
        $("#vendor_id").val('');
        $("#formAction").val('create_dn');
        $("#formTitle").text('Debit Note Details');
        
        // Generate new DN number
        const year = new Date().getFullYear();
        const random = String(Math.floor(Math.random() * 9999) + 1).padStart(4, '0');
        $("#dn_number").val('DN-' + year + '-' + random);
        $("#debit_date").val(new Date().toISOString().split('T')[0]);
        
        // Reset button states
        $("#saveBtn").show();
        $("#editBtn, #updateBtn, #deleteBtn, #printBtn, #emailBtn").hide();
        
        // Enable all fields
        $("#dnForm input, #dnForm textarea, #dnForm select").prop('disabled', false);
        
        // Remove validation classes
        $("#vendor_name").removeClass('is-invalid');
        
        isEditing = false;
        
        showNotification('Form reset successfully', 'info');
    }
    
    function loadForEdit(data) {
        $("#dnId").val(data.id);
        $("#dn_number").val(data.dn_number);
        $("#debit_date").val(data.debit_date);
        $("#vendor_name").val(data.vendor_name);
        $("#vendor_id").val(data.vendor_id || '');
        $("#original_invoice").val(data.original_invoice);
        $("#total_amount").val(data.total_amount);
        $("#reason").val(data.reason);
        $("#status").val(data.status);
        
        $("#formAction").val('update_dn');
        $("#formTitle").text('Edit Debit Note - ' + data.dn_number);
        
        // Button states for viewing
        $("#saveBtn").hide();
        $("#editBtn, #printBtn, #emailBtn").show();
        $("#updateBtn, #deleteBtn").hide();
        
        // Disable all fields initially (view mode)
        $("#dnForm input, #dnForm textarea, #dnForm select").prop('disabled', true);
        
        isEditing = false;
        
        // Scroll to form
        $('html, body').animate({
            scrollTop: $('#dnForm').offset().top - 100
        }, 500);
        
        showNotification('Debit note loaded for viewing', 'success');
    }
    
    // ---------- BUTTON HANDLERS ----------
    
    // Edit button click handlers from table
    $(document).on('click', '.edit-dn', function() {
        const data = $(this).data();
        loadForEdit(data);
    });
    
    // Edit button functionality (enable editing)
    $("#editBtn").click(function() {
        // Enable all fields
        $("#dnForm input, #dnForm textarea, #dnForm select").prop('disabled', false);
        
        // Update button states
        $("#editBtn").hide();
        $("#updateBtn, #deleteBtn").show();
        
        isEditing = true;
        showNotification('Form is now editable', 'info');
    });
    
    // Update button functionality
    $("#updateBtn").click(function() {
        $("#dnForm").submit();
    });
    
    // Delete button functionality
    $("#deleteBtn").click(function() {
        const dnNumber = $("#dn_number").val();
        if (confirm(`Are you sure you want to delete Debit Note "${dnNumber}"? This action cannot be undone.`)) {
            const id = $("#dnId").val();
            window.location.href = '?delete=' + id;
        }
    });
    
    // Print button functionality
    $(document).on('click', '#printBtn', function() {
        const dnId = $("#dnId").val();
        if (dnId) {
            printDebitNote(dnId);
        }
    });
    
    // Email button functionality
    $(document).on('click', '#emailBtn', function() {
        const dnId = $("#dnId").val();
        if (dnId) {
            openEmailDebitNoteModal(dnId);
        }
    });
    
    // Reset button functionality
    $("#resetBtn").click(function() {
        if (confirm('Are you sure you want to reset the form? All unsaved changes will be lost.')) {
            resetForm();
        }
    });
    
    // ---------- FORM VALIDATION & SUBMISSION ----------
    
    $("#dnForm").submit(function(e) {
        let isValid = true;
        
        // Vendor validation
        const vendorName = $("#vendor_name").val().trim();
        const vendorId = $("#vendor_id").val();
        
        if (!vendorName) {
            $("#vendor_name").addClass('is-invalid');
            showNotification('Please enter vendor name', 'error');
            isValid = false;
        } else {
            $("#vendor_name").removeClass('is-invalid');
        }
        
        // Only check vendor_id if it's required for autocomplete functionality
        // Allow manual vendor names for flexibility
        if (vendorName && !vendorId) {
            // This is acceptable - user can enter vendor name manually
            $("#vendor_name").removeClass('is-invalid');
        }
        
        // Amount validation
        const totalAmount = parseFloat($("#total_amount").val());
        if (isNaN(totalAmount) || totalAmount <= 0) {
            $("#total_amount").addClass('is-invalid');
            showNotification('Please enter a valid debit amount', 'error');
            isValid = false;
        } else {
            $("#total_amount").removeClass('is-invalid');
        }
        
        // Reason validation
        const reason = $("#reason").val().trim();
        if (!reason) {
            $("#reason").addClass('is-invalid');
            showNotification('Please provide a reason for the debit note', 'error');
            isValid = false;
        } else {
            $("#reason").removeClass('is-invalid');
        }
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
        
        // Show confirmation for updates
        if (isEditing && $("#formAction").val() === 'update_dn') {
            if (!confirm('Are you sure you want to update this debit note?')) {
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
    
    // Auto-generate DN number when vendor changes (for new entries only)
    $("#vendor_name").on('autocompleteselect', function(event, ui) {
        if ($("#formAction").val() === 'create_dn') {
            const vendorCode = ui.item.value.substring(0, 3).toUpperCase();
            const year = new Date().getFullYear();
            const random = String(Math.floor(Math.random() * 9999) + 1).padStart(4, '0');
            $("#dn_number").val('DN-' + year + '-' + vendorCode + '-' + random);
        }
    });
    
    // ---------- EMAIL FUNCTIONALITY ----------
    
    $(document).on('click', '.email-dn', function() {
        openEmailDebitNoteModal($(this).data('id'));
    });
    
    $('#sendDebitNoteEmailBtn').on('click', function() {
        const btn = $(this);
        const originalText = btn.html();
        const formData = $('#emailDebitNoteForm').serialize();
        
        // Validation
        const recipientEmail = $('#dn_recipient_email').val().trim();
        if (!recipientEmail) {
            showNotification('Please enter a recipient email address', 'error');
            return;
        }
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Sending...');
        
        $.ajax({
            url: '../ajax/send_debit_note_email.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.message || 'Email sent successfully', 'success');
                    $('#emailDebitNoteModal').modal('hide');
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
    
    console.log('Debit Note management system initialized successfully');
});