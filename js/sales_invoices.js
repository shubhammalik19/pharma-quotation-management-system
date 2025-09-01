/* sales_invoices.js - Complete Sales Invoice Management
   Dependencies: jQuery, jQuery UI (autocomplete), Bootstrap (for modals)
   API Endpoints:
     - ../ajax/unified_search.php
     - ../ajax/get_purchase_order_details.php
     - ../ajax/get_sales_invoice_details.php
     - ../ajax/send_sales_invoice_email.php
*/

// ---------- UTILITY FUNCTIONS ----------
function formatDate(dateStr) { 
    if (!dateStr) return ''; 
    const dt = new Date(dateStr); 
    return dt.toLocaleDateString('en-GB'); 
}

function escapeHtml(text) { 
    if (!text) return '';    $('#invoiceSearch').on('keypress', function(e) {
        if (e.which === 13) {
            $('#searchBtn').click();
        }
    });

    // Ensure search input always accepts keyboard input
    $('#invoiceSearch').on('focus', function() {
        $(this).prop('readonly', false);
        console.log('Search input focused and enabled for typing');
    });

    // Debug event to check if typing is working
    $('#invoiceSearch').on('input', function() {
        console.log('Search input changed:', $(this).val());
    });

    // ---------- FORM VALIDATION ----------onst map = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}; 
    return text.toString().replace(/[&<>"']/g, x => map[x]); 
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        minimumFractionDigits: 2
    }).format(amount || 0);
}

// ---------- PRINT & EMAIL FUNCTIONS ----------
function printInvoice(invoiceId) {
    if (!invoiceId) {
        invoiceId = $('#invoiceId').val();
    }
    if (invoiceId) {
        window.open(`../docs/print_sales_invoice.php?id=${invoiceId}`, '_blank');
    } else {
        alert('Please select an invoice to print');
    }
}

function openEmailInvoiceModal(invoiceId) {
    if (!invoiceId) {
        alert('Please select an invoice to email');
        return;
    }
    
    $.getJSON('../ajax/get_sales_invoice_details.php', { id: invoiceId })
    .done(function(response) {
        if (response.success) {
            $('#emailInvoiceModalLabel').text('Email Sales Invoice: ' + response.invoice.invoice_number);
            $('#emailInvoiceId').val(invoiceId);
            $('#invoice_recipient_email').val(response.invoice.customer_email || '');
            $('#invoice_additional_emails').val('');
            $('#invoice_custom_message').val('');
            $('#emailInvoiceModal').modal('show');
        } else {
            alert(response.message || 'Failed to fetch invoice details');
        }
    })
    .fail(function() {
        alert('Error loading invoice details');
    });
}

// ---------- GLOBAL VARIABLES ----------
let invoiceItems = [];
let isFormReadOnly = false;

$(document).ready(function() {
    // Initialize modals
    const machineModal = new bootstrap.Modal(document.getElementById('invoiceMachineModal'));
    const spareModal = new bootstrap.Modal(document.getElementById('invoiceSpareModal'));
    const emailModal = new bootstrap.Modal(document.getElementById('emailInvoiceModal'));

    // ---------- AUTOCOMPLETE SETUP ----------
    
    // Customer autocomplete with validation
    $('#customer_name').autocomplete({
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
            $('#customer_id').val(ui.item.id);
            $('#customer_name').val(ui.item.value);
            return false;
        },
        change: function(event, ui) {
            if (!ui.item) {
                $('#customer_id').val('');
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

    // Purchase Order autocomplete with item import
    $('#purchase_order_number').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: '../ajax/unified_search.php',
                type: 'GET',
                data: {
                    term: request.term,
                    type: 'AUTOCOMPLETE_PO_NUMBERS'
                },
                dataType: 'json',
                success: function(data) {
                    response(data);
                },
                error: function() {
                    console.error('Purchase order search failed');
                    response([]);
                }
            });
        },
        minLength: 2,
        delay: 300,
        select: function(event, ui) {
            $('#purchase_order_id').val(ui.item.id);
            $('#purchase_order_number').val(ui.item.value);
            
            // Import items from purchase order
            if (ui.item.id) {
                loadPurchaseOrderItems(ui.item.id);
            }
            return false;
        },
        change: function(event, ui) {
            if (!ui.item) {
                $('#purchase_order_id').val('');
            }
        }
    });

    // Invoice search autocomplete
    $('#invoiceSearch').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: '../ajax/unified_search.php',
                type: 'GET',
                data: {
                    term: request.term,
                    type: 'AUTOCOMPLETE_INVOICES'
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
            if (ui.item && ui.item.id) {
                // Load the selected sales invoice into the form like edit button
                loadSalesInvoiceData(ui.item.id);
            } else {
                // Just perform search if no specific item selected
                $('#invoiceSearch').val(ui.item.value);
                $('#searchBtn').click();
            }
            return false;
        },
        focus: function(event, ui) {
            // Prevent input value from being updated on focus
            return false;
        }
    });

    // Function to load sales invoice data (similar to edit button functionality)
    function loadSalesInvoiceData(invoiceId) {
        $.ajax({
            url: '../ajax/get_sales_invoice_details.php',
            type: 'GET',
            data: { id: invoiceId },
            dataType: 'json',
            beforeSend: function() {
                showNotification('Loading invoice details...', 'info');
            },
            success: function(data) {
                if (data.success) {
                    populateForm(data.invoice, data.items);
                    setFormReadOnly(true);
                    showEditModeButtons();
                    
                    // Clear search field
                    $('#invoiceSearch').val('');
                    
                    // Scroll to form
                    scrollToForm();
                    showNotification('Invoice loaded successfully', 'success');
                } else {
                    showNotification(data.message || 'Failed to load invoice details', 'error');
                }
            },
            error: function() {
                showNotification('Error loading invoice details', 'error');
            }
        });
    }

    // ---------- PURCHASE ORDER ITEM IMPORT ----------
    function loadPurchaseOrderItems(poId) {
        if (!poId) return;
        
        $.ajax({
            url: '../ajax/get_purchase_order_details.php',
            type: 'GET',
            data: { id: poId },
            dataType: 'json',
            beforeSend: function() {
                $('#purchase_order_number').prop('disabled', true);
            },
            success: function(data) {
                if (data.success && data.items) {
                    // Clear existing items
                    invoiceItems = [];
                    
                    // Import items from PO
                    data.items.forEach(item => {
                        const newItem = {
                            type: item.item_type || 'machine',
                            item_id: item.item_id || 0,
                            name: item.item_name || item.description || 'Item',
                            description: item.description || `HSN: ${item.hsn_code || ''}`,
                            quantity: parseInt(item.quantity) || 1,
                            unit_price: parseFloat(item.unit_price) || 0,
                            gst_rate: parseFloat(item.gst_rate) || 18,
                            total_price: parseFloat(item.total_price) || 0
                        };
                        invoiceItems.push(newItem);
                    });
                    
                    renderInvoiceItems();
                    
                    // Show success message
                    showNotification('Items imported from Purchase Order successfully', 'success');
                } else {
                    showNotification('No items found in the selected purchase order', 'warning');
                }
            },
            error: function() {
                showNotification('Failed to load purchase order items', 'error');
            },
            complete: function() {
                $('#purchase_order_number').prop('disabled', false);
            }
        });
    }

    // ---------- MODAL OPERATIONS ----------
    
    // Show machine modal
    $('#invoiceAddMachineBtn').on('click', function() {
        if (isFormReadOnly) return;
        resetMachineModal();
        machineModal.show();
    });

    // Show spare modal
    $('#invoiceAddSpareBtn').on('click', function() {
        if (isFormReadOnly) return;
        resetSpareModal();
        spareModal.show();
    });

    // Machine selection handler
    $('#invoiceMachineSelect').on('change', function() {
        const selected = $(this).find('option:selected');
        const price = parseFloat(selected.data('price') || 0);
        const name = selected.data('name') || '';
        const model = selected.data('model') || '';
        const category = selected.data('category') || '';
        
        if (price > 0) {
            $('#invoiceMachinePrice').val(price.toFixed(2));
        } else {
            $('#invoiceMachinePrice').val('');
            if (selected.val()) {
                showNotification('No current price found for this machine', 'warning');
            }
        }
        
        if (selected.val()) {
            let description = `${name} - ${category}`;
            if (model) description += ` (Model: ${model})`;
            $('#invoiceMachineDesc').val(description);
        } else {
            $('#invoiceMachineDesc').val('');
        }
    });

    // Spare selection handler
    $('#invoiceSpareSelect').on('change', function() {
        const selected = $(this).find('option:selected');
        const price = parseFloat(selected.data('price') || 0);
        const name = selected.data('name') || '';
        const code = selected.data('code') || '';
        
        if (price > 0) {
            $('#invoiceSparePrice').val(price.toFixed(2));
        } else {
            $('#invoiceSparePrice').val('');
        }
        
        if (selected.val()) {
            $('#invoiceSpareDesc').val(`${name} - Code: ${code}`);
        } else {
            $('#invoiceSpareDesc').val('');
        }
    });

    // Add machine to invoice
    $('#invoiceAddMachineToInvoice').on('click', function() {
        const selected = $('#invoiceMachineSelect').find('option:selected');
        const qty = parseInt($('#invoiceMachineQty').val()) || 0;
        const price = parseFloat($('#invoiceMachinePrice').val()) || 0;
        const gstRate = parseFloat($('#invoiceMachineGST').val()) || 18;

        // Validation
        if (!selected.val()) {
            showNotification('Please select a machine', 'error');
            return;
        }
        if (qty <= 0) {
            showNotification('Please enter a valid quantity', 'error');
            return;
        }
        if (price < 0) {
            showNotification('Please enter a valid price', 'error');
            return;
        }

        const item = {
            type: 'machine',
            item_id: parseInt(selected.val()),
            name: selected.data('name') || 'Machine',
            description: $('#invoiceMachineDesc').val() || '',
            quantity: qty,
            unit_price: price,
            gst_rate: gstRate,
            total_price: qty * price
        };

        invoiceItems.push(item);
        renderInvoiceItems();
        machineModal.hide();
        showNotification('Machine added successfully', 'success');
    });

    // Add spare to invoice
    $('#invoiceAddSpareToInvoice').on('click', function() {
        const selected = $('#invoiceSpareSelect').find('option:selected');
        const qty = parseInt($('#invoiceSpareQty').val()) || 0;
        const price = parseFloat($('#invoiceSparePrice').val()) || 0;
        const gstRate = parseFloat($('#invoiceSpareGST').val()) || 18;

        // Validation
        if (!selected.val()) {
            showNotification('Please select a spare part', 'error');
            return;
        }
        if (qty <= 0) {
            showNotification('Please enter a valid quantity', 'error');
            return;
        }
        if (price < 0) {
            showNotification('Please enter a valid price', 'error');
            return;
        }

        const item = {
            type: 'spare',
            item_id: parseInt(selected.val()),
            name: selected.data('name') || 'Spare Part',
            description: $('#invoiceSpareDesc').val() || '',
            quantity: qty,
            unit_price: price,
            gst_rate: gstRate,
            total_price: qty * price
        };

        invoiceItems.push(item);
        renderInvoiceItems();
        spareModal.hide();
        showNotification('Spare part added successfully', 'success');
    });

    // Remove item from invoice
    $(document).on('click', '.remove-invoice-item', function() {
        if (isFormReadOnly) return;
        
        const index = $(this).data('index');
        if (confirm('Are you sure you want to remove this item?')) {
            invoiceItems.splice(index, 1);
            renderInvoiceItems();
            showNotification('Item removed successfully', 'info');
        }
    });

    // ---------- ITEM RENDERING & CALCULATIONS ----------
    
    function renderInvoiceItems() {
        const itemsList = $('#invoiceItemsList');
        itemsList.empty();
        
        if (invoiceItems.length === 0) {
            itemsList.html(`
                <div class="text-muted text-center py-4">
                    <i class="bi bi-box fs-2"></i><br>
                    <strong>No items added yet</strong><br>
                    <small>Use the buttons above to add machines and spare parts</small>
                </div>
            `);
        } else {
            invoiceItems.forEach((item, index) => {
                const icon = item.type === 'machine' ? 'bi-gear' : 'bi-tools';
                const badge = item.type === 'machine' ? 'bg-primary' : 'bg-success';
                const displayName = escapeHtml(item.name || (item.type === 'machine' ? 'Machine' : 'Spare Part'));
                const displayPrice = (item.unit_price || 0).toFixed(2);
                const gstDisplay = item.gst_rate ? ` (GST: ${item.gst_rate}%)` : '';
                const removeBtn = isFormReadOnly ? '' : `<button type="button" class="btn btn-sm btn-outline-danger remove-invoice-item" data-index="${index}"><i class="bi bi-trash"></i></button>`;
                
                const itemHtml = `
                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                        <div class="flex-grow-1">
                            <i class="bi ${icon}"></i>
                            <strong>${displayName}</strong>
                            <span class="badge ${badge} ms-1">${item.type}</span>
                            <br><small class="text-muted">Qty: ${item.quantity} × ₹${displayPrice} = ₹${(item.total_price || 0).toFixed(2)}${gstDisplay}</small>
                            ${item.description ? '<br><small class="text-muted">' + escapeHtml(item.description) + '</small>' : ''}
                        </div>
                        ${removeBtn}
                    </div>
                `;
                itemsList.append(itemHtml);
            });
            
            // Add hidden form inputs
            updateFormInputs();
        }
        
        calculateTotals();
    }

    function updateFormInputs() {
        // Remove existing hidden inputs
        $('#invoiceForm input[name^="items["]').remove();
        
        // Add new hidden inputs
        invoiceItems.forEach((item, index) => {
            $('#invoiceForm').append(`
                <input type="hidden" name="items[${index}][type]" value="${escapeHtml(item.type)}">
                <input type="hidden" name="items[${index}][item_id]" value="${item.item_id}">
                <input type="hidden" name="items[${index}][name]" value="${escapeHtml(item.name)}">
                <input type="hidden" name="items[${index}][description]" value="${escapeHtml(item.description || '')}">
                <input type="hidden" name="items[${index}][quantity]" value="${item.quantity}">
                <input type="hidden" name="items[${index}][unit_price]" value="${item.unit_price}">
                <input type="hidden" name="items[${index}][gst_rate]" value="${item.gst_rate}">
                <input type="hidden" name="items[${index}][total_price]" value="${item.total_price}">
            `);
        });
    }

    function calculateTotals() {
        const subtotal = invoiceItems.reduce((sum, item) => sum + (item.total_price || 0), 0);
        $('#total_amount').val(subtotal.toFixed(2));
        
        // Trigger discount and tax calculations
        invoiceCalcDiscount();
        invoiceCalcTax();
    }

    // Calculation functions
    window.invoiceCalcDiscount = function() {
        const totalAmount = parseFloat($('#total_amount').val()) || 0;
        const percentage = parseFloat($('#discount_percentage').val()) || 0;
        const discountAmount = (totalAmount * percentage) / 100;
        $('#discount_amount').val(discountAmount.toFixed(2));
        calculateFinalTotal();
    };
    
    window.invoiceCalcDiscountPct = function() {
        const totalAmount = parseFloat($('#total_amount').val()) || 0;
        const discountAmount = parseFloat($('#discount_amount').val()) || 0;
        if (totalAmount > 0) {
            const percentage = (discountAmount / totalAmount) * 100;
            $('#discount_percentage').val(Math.min(percentage, 100).toFixed(2));
        } else {
            $('#discount_percentage').val(0);
        }
        calculateFinalTotal();
    };

    window.invoiceCalcTax = function() {
        const subtotal = parseFloat($('#total_amount').val()) || 0;
        const discountAmount = parseFloat($('#discount_amount').val()) || 0;
        const taxableAmount = subtotal - discountAmount;
        const taxPercentage = parseFloat($('#tax_percentage').val()) || 0;
        const taxAmount = (taxableAmount * taxPercentage) / 100;
        $('#tax_amount').val(taxAmount.toFixed(2));
        calculateFinalTotal();
    };

    function calculateFinalTotal() {
        const totalAmount = parseFloat($('#total_amount').val()) || 0;
        const discountAmount = parseFloat($('#discount_amount').val()) || 0;
        const taxAmount = parseFloat($('#tax_amount').val()) || 0;
        const finalTotal = totalAmount - discountAmount + taxAmount;
        $('#grand_total').val(Math.max(finalTotal, 0).toFixed(2));
    }

    // Bind calculation events
    $('#discount_percentage').on('input', invoiceCalcDiscount);
    $('#discount_amount').on('input', invoiceCalcDiscountPct);
    $('#tax_percentage').on('input', invoiceCalcTax);

    // ---------- SEARCH FUNCTIONALITY ----------
    
    $('#searchBtn').on('click', function() {
        const searchTerm = $('#invoiceSearch').val().trim();
        if (searchTerm) {
            window.location.href = 'sales_invoices.php?search=' + encodeURIComponent(searchTerm);
        }
    });

    $('#clearBtn').on('click', function() {
        window.location.href = 'sales_invoices.php';
    });

    $('#invoiceSearch').on('keypress', function(e) {
        if (e.which === 13) {
            $('#searchBtn').click();
        }
    });

    // ---------- FORM OPERATIONS ----------
    
    // Edit invoice from table
    $(document).on('click', '.edit-invoice', function() {
        const invoiceId = $(this).data('id');
        loadInvoiceForEdit(invoiceId);
    });

    function loadInvoiceForEdit(invoiceId) {
        $.ajax({
            url: '../ajax/get_sales_invoice_details.php',
            type: 'GET',
            data: { id: invoiceId },
            dataType: 'json',
            beforeSend: function() {
                showNotification('Loading invoice details...', 'info');
            },
            success: function(data) {
                if (data.success) {
                    populateForm(data.invoice, data.items);
                    setFormReadOnly(true);
                    showEditModeButtons();
                    scrollToForm();
                    showNotification('Invoice loaded successfully', 'success');
                } else {
                    showNotification(data.message || 'Failed to load invoice', 'error');
                }
            },
            error: function() {
                showNotification('Error loading invoice details', 'error');
            }
        });
    }

    function populateForm(invoice, items) {
        // Populate basic fields
        $('#invoiceId').val(invoice.id);
        $('#invoice_number').val(invoice.invoice_number);
        $('#customer_name').val(invoice.customer_name);
        $('#customer_id').val(invoice.customer_id);
        $('#purchase_order_number').val(invoice.purchase_order_number || '');
        $('#purchase_order_id').val(invoice.purchase_order_id || '');
        $('#invoice_date').val(invoice.invoice_date);
        $('#due_date').val(invoice.due_date);
        $('#status').val(invoice.status);
        $('#notes').val(invoice.notes || '');
        $('#discount_percentage').val(invoice.discount_percentage || 0);
        $('#discount_amount').val(invoice.discount_amount || 0);
        $('#tax_percentage').val(invoice.tax_percentage || 0);
        $('#tax_amount').val(invoice.tax_amount || 0);

        // Load items
        invoiceItems = (items || []).map(item => ({
            type: item.item_type || 'machine',
            item_id: item.item_id || 0,
            name: item.item_name || item.display_name || 'Item',
            description: item.description || '',
            quantity: parseInt(item.quantity) || 1,
            unit_price: parseFloat(item.unit_price) || 0,
            gst_rate: parseFloat(item.gst_rate) || 18,
            total_price: parseFloat(item.total_price) || 0
        }));
        
        renderInvoiceItems();
        
        $('#formAction').val('update_invoice');
        $('#formTitle').text('Sales Invoice Details - ' + invoice.invoice_number);
    }

    function showEditModeButtons() {
        $('#saveBtn').hide();
        $('#editBtn').show();
        $('#deleteBtn').show();
        $('#printBtn').show();
        $('#emailBtn').show();
        $('#updateBtn').hide();
    }

    function setFormReadOnly(readonly) {
        isFormReadOnly = readonly;
        $('#invoiceForm input, #invoiceForm textarea, #invoiceForm select').not('#invoiceId, #formAction, #invoiceSearch').prop('readonly', readonly);
        $('#invoiceForm select').prop('disabled', readonly);
        $('#invoiceAddMachineBtn, #invoiceAddSpareBtn').prop('disabled', readonly);
        
        // Ensure search input is always functional
        $('#invoiceSearch').prop('readonly', false).prop('disabled', false);
        
        if (readonly) {
            $('.remove-invoice-item').hide();
        } else {
            $('.remove-invoice-item').show();
        }
    }

    function scrollToForm() {
        $('html, body').animate({
            scrollTop: $('#invoiceForm').offset().top - 100
        }, 500);
    }

    // Form button handlers
    $('#editBtn').on('click', function() {
        setFormReadOnly(false);
        $('#editBtn').hide();
        $('#updateBtn').show();
        showNotification('Form is now editable', 'info');
    });

    $('#deleteBtn').on('click', function() {
        const invoiceId = $('#invoiceId').val();
        const invoiceNumber = $('#invoice_number').val();
        
        if (invoiceId && confirm(`Are you sure you want to delete Sales Invoice "${invoiceNumber}"? This action cannot be undone.`)) {
            window.location.href = 'sales_invoices.php?delete=' + invoiceId;
        }
    });

    $('#resetBtn').on('click', function() {
        if (confirm('Are you sure you want to reset the form? All unsaved changes will be lost.')) {
            resetForm();
        }
    });

    function resetForm() {
        $('#invoiceForm')[0].reset();
        $('#invoiceId').val('');
        $('#customer_id').val('');
        $('#purchase_order_id').val('');
        $('#formAction').val('create_invoice');
        invoiceItems = [];
        renderInvoiceItems();
        setFormReadOnly(false);
        
        // Reset button states
        $('#saveBtn').show();
        $('#editBtn, #updateBtn, #deleteBtn, #printBtn, #emailBtn').hide();
        $('#formTitle').text('Create Sales Invoice');
        
        // Set default values
        $('#invoice_date').val(new Date().toISOString().split('T')[0]);
        const dueDate = new Date();
        dueDate.setDate(dueDate.getDate() + 30);
        $('#due_date').val(dueDate.toISOString().split('T')[0]);
        $('#tax_percentage').val(18);
        $('#discount_percentage').val(0);
        $('#discount_amount').val(0);
        $('#tax_amount').val(0);
        $('#grand_total').val(0);
        
        // Remove validation classes
        $('#customer_name').removeClass('is-invalid');
    }

    // ---------- EMAIL FUNCTIONALITY ----------
    
    $(document).on('click', '.email-invoice', function() {
        openEmailInvoiceModal($(this).data('id'));
    });
    
    $('#emailBtn').on('click', function() {
        const invoiceId = $('#invoiceId').val();
        if (invoiceId) {
            openEmailInvoiceModal(invoiceId);
        }
    });
    
    $('#sendInvoiceEmailBtn').on('click', function() {
        const btn = $(this);
        const originalText = btn.html();
        const formData = $('#emailInvoiceForm').serialize();
        
        // Validation
        const recipientEmail = $('#invoice_recipient_email').val().trim();
        if (!recipientEmail) {
            showNotification('Please enter a recipient email address', 'error');
            return;
        }
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Sending...');
        
        $.ajax({
            url: '../ajax/send_sales_invoice_email.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.message || 'Email sent successfully', 'success');
                    emailModal.hide();
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

    // ---------- MODAL RESET FUNCTIONS ----------
    
    function resetMachineModal() {
        $('#invoiceMachineSelect').val('');
        $('#invoiceMachineQty').val(1);
        $('#invoiceMachinePrice').val('');
        $('#invoiceMachineGST').val(18);
        $('#invoiceMachineDesc').val('');
    }

    function resetSpareModal() {
        $('#invoiceSpareSelect').val('');
        $('#invoiceSpareQty').val(1);
        $('#invoiceSparePrice').val('');
        $('#invoiceSpareGST').val(18);
        $('#invoiceSpareDesc').val('');
    }

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

    // ---------- FORM VALIDATION ----------
    
    $('#invoiceForm').on('submit', function(e) {
        let isValid = true;
        
        // Customer validation
        const customerName = $('#customer_name').val().trim();
        const customerId = $('#customer_id').val();
        if (!customerName || !customerId) {
            $('#customer_name').addClass('is-invalid');
            showNotification('Please select a valid customer', 'error');
            isValid = false;
        }
        
        // Items validation
        if (invoiceItems.length === 0) {
            showNotification('Please add at least one item to the invoice', 'error');
            isValid = false;
        }
        
        // Amount validation
        const grandTotal = parseFloat($('#grand_total').val()) || 0;
        if (grandTotal <= 0) {
            showNotification('Invoice total must be greater than zero', 'error');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        const submitBtn = $(this).find('[type="submit"]:visible');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');
        
        return true;
    });

    // ---------- INITIALIZATION ----------
    
    // Initial render
    renderInvoiceItems();
    
    // Set initial form state
    resetForm();
    
    console.log('Sales Invoice management system initialized successfully');
});