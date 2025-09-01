/* sale_oreders.js - Sales Orders JavaScript - Consistent with purchase_orders.js
   Requires: jQuery, jQuery UI (autocomplete), Bootstrap (for modals)
   Endpoints used:
     - ../ajax/unified_search.php           (AUTOCOMPLETE_CUSTOMERS, AUTOCOMPLETE_QUOTATIONS, AUTOCOMPLETE_SO)
     - ../ajax/get_quotation_full.php       (id)
     - ../ajax/get_sales_order_details.php  (id)
     - ../ajax/send_sales_order_email.php   (POST form)
*/

// ---------- SMALL HELPERS ----------
function fmtDate(d){ if(!d) return ''; const dt=new Date(d); return dt.toLocaleDateString('en-GB'); }
function esc(t){ if(!t) return ''; const m={'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}; return t.toString().replace(/[&<>"']/g, x=>m[x]); }

// ---------- PRINT + EMAIL ----------
function printSO(soId){
  if (!soId) {
    soId = $('#soId').val();
  }
  if (soId) window.open(`../docs/print_sales_order.php?id=${soId}`, '_blank');
}
function openEmailSOModal(soId){
  $.getJSON('../ajax/get_sales_order_details.php', { id: soId }, function(res){
    if (res.success) {
      $('#emailQuotationModalLabel').text('Email Sales Order: ' + res.sales_order.so_number);
      $('#emailQuotationId').val(soId);
      $('#recipient_email').val(res.sales_order.customer_email || '');
      $('#emailQuotationModal').modal('show');
    } else {
      alert(res.message || 'Failed to fetch SO details');
    }
  });
}

// ---------- GLOBAL VARIABLES ----------
let soItems = [];

$(document).ready(function() {
    const machineModal = new bootstrap.Modal(document.getElementById('soMachineModal'));
    const spareModal = new bootstrap.Modal(document.getElementById('soSpareModal'));

    // Ensure search input can accept keyboard input immediately
    $('#soSearch').prop('readonly', false).prop('disabled', false).attr('tabindex', '0');
    
    // Debug search input
    $('#soSearch').on('focus', function() {
        console.log('Search input focused');
        $(this).prop('readonly', false);
    });

    // Autocomplete for Customer
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
                    response([]);
                }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            $('#customer_id').val(ui.item.id);
            $('#customer_name').val(ui.item.label);
            return false;
        },
        change: function(event, ui) {
            if (!ui.item) {
                $('#customer_id').val('');
            }
        }
    });

    // Autocomplete for Quotation Number
    $('#quotation_number').autocomplete({
        source: function(request, response){
          $.ajax({
            url: '../ajax/unified_search.php',
            type: 'GET',
            dataType: 'json',
            data: { type:'AUTOCOMPLETE_QUOTATIONS', term: request.term },
            success: response,
            error: function(){ response([]); }
          });
        },
        minLength: 1, delay: 200,
        select: function(event, ui){
          if (ui.item && ui.item.data){
            const q = ui.item.data;
            $('#quotation_id').val(q.id);
            $('#quotation_number').val(q.quotation_number);

            // Auto-fill customer details from quotation
            if (q.customer_id && q.customer_name) {
                $('#customer_id').val(q.customer_id);
                $('#customer_name').val(q.customer_name);
            }

            $.getJSON('../ajax/get_quotation_full.php', { id: q.id }, function(res){
              if (res.success){
                // Load quotation items into SO
                if (res.items && res.items.length > 0) {
                    soItems = []; // Clear existing items
                    res.items.forEach(item => {
                        const newItem = {
                            type: item.item_type,
                            item_id: item.item_id,
                            name: item.item_name || item.description,
                            description: item.description || '',
                            quantity: parseInt(item.quantity),
                            unit_price: parseFloat(item.rate || item.unit_price),
                            total_price: parseInt(item.quantity) * parseFloat(item.rate || item.unit_price)
                        };
                        soItems.push(newItem);
                    });
                    renderSoItems();
                }
              } else {
                alert(res.message || 'Failed to load quotation details');
              }
            });
            return false;
          }
        }
    });

    // Show modals
    $('#soAddMachineBtn').on('click', () => machineModal.show());
    $('#soAddSpareBtn').on('click', () => spareModal.show());

    // Pre-fill price and description on machine selection
    $('#soMachineSelect').on('change', function() {
        const selected = $(this).find('option:selected');
        const price = parseFloat(selected.data('price') || 0);
        const name = selected.data('name') || '';
        const model = selected.data('model') || '';
        const category = selected.data('category') || '';
        const validFrom = selected.data('valid_from') || '';
        const validTo = selected.data('valid_to') || '';
        
        // Set price (ensure minimum display)
        if (price > 0) {
            $('#soMachinePrice').val(price);
        } else {
            $('#soMachinePrice').val('');
            if (selected.val()) {
                alert('No current price found for this machine in Price Master.');
            }
        }
        
        // Set description with consistent format
        if (selected.val()) {
            let description = `${name} - ${category}`;
            if (model) description += ` (Model: ${model})`;
            if (validFrom && validTo) description += `\nPrice valid: ${validFrom} to ${validTo}`;
            $('#soMachineDesc').val(description);
        } else {
            $('#soMachineDesc').val('');
        }
    });

    // Pre-fill price and description on spare selection
    $('#soSpareSelect').on('change', function() {
        const selected = $(this).find('option:selected');
        const price = parseFloat(selected.data('price') || 0);
        const name = selected.data('name') || '';
        const code = selected.data('code') || '';
        
        if (price > 0) {
            $('#soSparePrice').val(price);
        }
        
        if (selected.val()) {
            $('#soSpareDesc').val(`${name} - Code: ${code}`);
        } else {
            $('#soSpareDesc').val('');
        }
    });

    // Add machine to SO with consistent naming
    $('#soAddMachineToSO').on('click', function() {
        const selected = $('#soMachineSelect').find('option:selected');
        const qty = parseInt($('#soMachineQty').val());
        const price = parseFloat($('#soMachinePrice').val());

        if (selected.val() && qty > 0 && price >= 0) {
            const item = {
                type: 'machine',
                item_id: selected.val(),
                name: selected.data('name') || 'Machine',
                description: $('#soMachineDesc').val(),
                quantity: qty,
                unit_price: price,
                total_price: qty * price
            };
            soItems.push(item);
            renderSoItems();
            machineModal.hide();
            
            // Reset form
            $('#soMachineSelect').val('');
            $('#soMachineQty').val(1);
            $('#soMachinePrice').val('');
            $('#soMachineDesc').val('');
        } else {
            alert('Please select a machine, quantity, and price.');
        }
    });

    // Add spare to SO with consistent naming
    $('#soAddSpareToSO').on('click', function() {
        const selected = $('#soSpareSelect').find('option:selected');
        const qty = parseInt($('#soSpareQty').val());
        const price = parseFloat($('#soSparePrice').val());

        if (selected.val() && qty > 0 && price >= 0) {
            const item = {
                type: 'spare',
                item_id: selected.val(),
                name: selected.data('name') || 'Spare Part',
                description: $('#soSpareDesc').val(),
                quantity: qty,
                unit_price: price,
                total_price: qty * price
            };
            soItems.push(item);
            renderSoItems();
            spareModal.hide();
            
            // Reset form
            $('#soSpareSelect').val('');
            $('#soSpareQty').val(1);
            $('#soSparePrice').val('');
            $('#soSpareDesc').val('');
        } else {
            alert('Please select a spare part, quantity, and price.');
        }
    });

    // Remove item
    $(document).on('click', '.remove-so-item', function() {
        const index = $(this).data('index');
        if (confirm('Are you sure you want to remove this item?')) {
            soItems.splice(index, 1);
            renderSoItems();
        }
    });

    function renderSoItems() {
        const itemsList = $('#soItemsList');
        itemsList.empty();
        if (soItems.length === 0) {
            itemsList.html('<div class="text-muted text-center py-4"><i class="bi bi-box fs-2"></i><br><strong>No items added yet</strong><br><small>Use the buttons above to add machines and spare parts</small></div>');
        } else {
            soItems.forEach((item, index) => {
                const icon = item.type === 'machine' ? 'bi-gear' : 'bi-tools';
                const badge = item.type === 'machine' ? 'bg-primary' : 'bg-success';
                const displayName = item.name || (item.type === 'machine' ? 'Machine' : 'Spare Part');
                const displayPrice = (item.unit_price || 0).toFixed(2);
                
                const itemHtml = `
                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                        <div>
                            <i class="bi ${icon}"></i>
                            <strong>${esc(displayName)}</strong>
                            <span class="badge ${badge} ms-1">${item.type}</span>
                            <br><small class="text-muted">Qty: ${item.quantity} × ₹${displayPrice} = ₹${(item.total_price || 0).toFixed(2)}</small>
                            ${item.description ? '<br><small class="text-muted">' + esc(item.description) + '</small>' : ''}
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-so-item" data-index="${index}"><i class="bi bi-trash"></i></button>
                    </div>
                `;
                itemsList.append(itemHtml);
            });
            
            // Add hidden inputs for form submission
            $('#soForm input[name^="items["]').remove();
            soItems.forEach((item, index) => {
                $('#soForm').append(`
                    <input type="hidden" name="items[${index}][type]" value="${item.type}">
                    <input type="hidden" name="items[${index}][item_id]" value="${item.item_id}">
                    <input type="hidden" name="items[${index}][name]" value="${esc(item.name)}">
                    <input type="hidden" name="items[${index}][description]" value="${esc(item.description || '')}">
                    <input type="hidden" name="items[${index}][quantity]" value="${item.quantity}">
                    <input type="hidden" name="items[${index}][unit_price]" value="${item.unit_price}">
                    <input type="hidden" name="items[${index}][total_price]" value="${item.total_price}">
                `);
            });
        }
        calculateTotals();
    }

    function calculateTotals() {
        const subtotal = soItems.reduce((sum, item) => sum + (item.total_price || 0), 0);
        $('#total_amount').val(subtotal.toFixed(2));
        soCalcDiscount();
    }

    window.soCalcDiscount = function() {
        const totalAmount = parseFloat($('#total_amount').val()) || 0;
        const percentage = parseFloat($('#discount_percentage').val()) || 0;
        const discountAmount = (totalAmount * percentage) / 100;
        $('#discount_amount').val(discountAmount.toFixed(2));
        calculateFinalTotal();
    }
    
    window.soCalcDiscountPct = function() {
        const totalAmount = parseFloat($('#total_amount').val()) || 0;
        const discountAmount = parseFloat($('#discount_amount').val()) || 0;
        if (totalAmount > 0) {
            const percentage = (discountAmount / totalAmount) * 100;
            $('#discount_percentage').val(percentage.toFixed(2));
        } else {
            $('#discount_percentage').val(0);
        }
        calculateFinalTotal();
    }

    function calculateFinalTotal() {
        const totalAmount = parseFloat($('#total_amount').val()) || 0;
        const discountAmount = parseFloat($('#discount_amount').val()) || 0;
        const finalTotal = totalAmount - discountAmount;
        $('#final_total').val(finalTotal.toFixed(2));
    }

    $('#discount_percentage').on('input', soCalcDiscount);
    $('#discount_amount').on('input', soCalcDiscountPct);

    // Search functionality
    $('#searchBtn').on('click', function() {
        const searchTerm = $('#soSearch').val().trim();
        if (searchTerm) {
            window.location.href = 'sales_orders.php?search=' + encodeURIComponent(searchTerm);
        }
    });

    $('#clearBtn').on('click', () => {
        $('#soSearch').val('');
        window.location.href = 'sales_orders.php';
    });

    $('#soSearch').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            $('#searchBtn').click();
        }
    });

    // Ensure search input can accept keyboard input
    $('#soSearch').on('input', function() {
        // This ensures the input field remains active for typing
        console.log('Search input changed:', $(this).val());
    });

    // Make sure search field is focusable and editable
    $('#soSearch').prop('readonly', false).prop('disabled', false);

    // Edit SO from table
    $(document).on('click', '.edit-so', function() {
        const soId = $(this).data('id');
        loadSalesOrderData(soId);
    });

    $('#editBtn').on('click', function() {
        setFormReadOnly(false);
        $('#editBtn').hide();
        $('#updateBtn').show();
        $('#formAction').val('update_so');
    });

    $('#deleteBtn').on('click', function() {
        const soId = $('#soId').val();
        const soNumber = $('#so_number').val();
        if (soId && confirm('Are you sure you want to delete Sales Order "' + soNumber + '"?')) {
            window.location.href = 'sales_orders.php?delete=' + soId;
        }
    });

    $('#resetBtn').on('click', resetForm);
    
    // Email functionality
    $(document).on('click', '.email-so', function() {
        openEmailSOModal($(this).data('id'));
    });
    
    $('#emailBtn').on('click', function() {
        const soId = $('#soId').val();
        if (soId) {
            openEmailSOModal(soId);
        }
    });
    
    $('#sendEmailBtn').on('click', function() {
        const btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Sending...');
        const formData = $('#emailQuotationForm').serialize();
        
        $.ajax({
            url: '../ajax/send_sales_order_email.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    alert(res.message);
                    $('#emailQuotationModal').modal('hide');
                } else {
                    alert('Error: ' + (res.message || 'Failed to send email'));
                }
            },
            error: function() {
                alert('Error: Failed to send email');
            },
            complete: function() {
                btn.prop('disabled', false).html('Send Email');
            }
        });
    });

    function setFormReadOnly(readonly) {
        $('#soForm input, #soForm textarea, #soForm select').not('#soId, #formAction').prop('readonly', readonly);
        $('#soForm select').prop('disabled', readonly);
        $('#soAddMachineBtn, #soAddSpareBtn').prop('disabled', readonly);
        
        // Ensure search input is never affected by form readonly state
        $('#soSearch').prop('readonly', false).prop('disabled', false);
        
        if(readonly) {
            $('.remove-so-item').hide();
            $('#editBtn, #deleteBtn, #printBtn, #emailBtn').prop('disabled', false);
        } else {
            $('.remove-so-item').show();
        }
    }

    function resetForm() {
        $('#soForm')[0].reset();
        $('#soId').val('');
        $('#customer_id').val('');
        $('#quotation_id').val('');
        $('#formAction').val('create_so');
        soItems = [];
        renderSoItems();
        setFormReadOnly(false);
        $('#saveBtn').show();
        $('#editBtn, #updateBtn, #deleteBtn, #printBtn, #emailBtn').hide();
        $('#formTitle').text('Create Sales Order');
        
        // Set default dates
        $('#so_date').val(new Date().toISOString().split('T')[0]);
        $('#discount_percentage').val(0);
        $('#discount_amount').val(0);
    }

    // Initial render
    renderSoItems();

    // Autocomplete for main SO search
    $('#soSearch').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: '../ajax/unified_search.php',
                type: 'GET',
                data: {
                    term: request.term,
                    type: 'AUTOCOMPLETE_SO'
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
                // Load the selected sales order into the form like edit button
                loadSalesOrderData(ui.item.id);
            } else {
                // Just perform search if no specific item selected
                $('#soSearch').val(ui.item.value);
                $('#searchBtn').click();
            }
            return false;
        },
        focus: function(event, ui) {
            // Prevent input value from being updated on focus
            return false;
        }
    });

    // Function to load sales order data (similar to edit button functionality)
    function loadSalesOrderData(soId) {
        $.ajax({
            url: '../ajax/get_sales_order_details.php',
            type: 'GET',
            data: { id: soId },
            dataType: 'json',
            success: function(data) {
                if(data.success) {
                    // Fill form with SO data
                    $('#soId').val(data.sales_order.id);
                    $('#so_number').val(data.sales_order.so_number);
                    $('#customer_name').val(data.sales_order.customer_name);
                    $('#customer_id').val(data.sales_order.customer_id);
                    $('#so_date').val(data.sales_order.so_date);
                    $('#delivery_date').val(data.sales_order.delivery_date || '');
                    $('#status').val(data.sales_order.status);
                    $('#notes').val(data.sales_order.notes || '');
                    $('#quotation_number').val(data.sales_order.quotation_number || '');
                    $('#quotation_id').val(data.sales_order.quotation_id || '');
                    $('#discount_percentage').val(data.sales_order.discount_percentage || 0);
                    $('#discount_amount').val(data.sales_order.discount_amount || 0);

                    // Load SO items with consistent naming
                    soItems = (data.items || []).map(item => ({
                        type: item.item_type,
                        item_id: item.item_id,
                        name: item.item_name || (item.item_type === 'machine' ? 'Machine' : 'Spare Part'),
                        description: item.description || '',
                        quantity: parseInt(item.quantity),
                        unit_price: parseFloat(item.unit_price),
                        total_price: parseFloat(item.total_price)
                    }));
                    
                    renderSoItems();
                    
                    setFormReadOnly(true);
                    $('#saveBtn').hide();
                    $('#editBtn').show();
                    $('#deleteBtn').show();
                    $('#printBtn').show();
                    $('#emailBtn').show();
                    $('#updateBtn').hide();
                    $('#formTitle').text('Sales Order Details - ' + data.sales_order.so_number);
                    
                    // Clear search field
                    $('#soSearch').val('');
                    
                    // Scroll to form
                    $('html, body').animate({ scrollTop: $('#soForm').offset().top - 100 }, 500);
                } else {
                    alert(data.message || 'Failed to load sales order details');
                }
            },
            error: function() {
                alert('Error loading sales order details');
            }
        });
    }
});