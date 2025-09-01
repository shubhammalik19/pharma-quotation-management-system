/* purchase_orders.js - Consistent with sales_orders.js
   Requires: jQuery, jQuery UI (autocomplete), Bootstrap (for modals)
   Endpoints used:
     - ../ajax/unified_search.php           (AUTOCOMPLETE_VENDORS, AUTOCOMPLETE_SO_NUMBERS, AUTOCOMPLETE_QUOTATIONS, AUTOCOMPLETE_PO)
     - ../ajax/get_quotation_full.php       (id)
     - ../ajax/get_sales_order_details.php  (id)
     - ../ajax/get_purchase_order_details.php (id)
     - ../ajax/send_purchase_order_email.php (POST form)
*/

// ---------- SMALL HELPERS ----------
function fmtDate(d){ if(!d) return ''; const dt=new Date(d); return dt.toLocaleDateString('en-GB'); }
function esc(t){ if(!t) return ''; const m={'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}; return t.toString().replace(/[&<>"']/g, x=>m[x]); }

// ---------- PRINT + EMAIL ----------
function printPO(poId){
  if (!poId) {
    poId = $('#poId').val();
  }
  if (poId) window.open(`../docs/print_purchase_order.php?id=${poId}`, '_blank');
}
function openEmailPOModal(poId){
  $.getJSON('../ajax/get_purchase_order_details.php', { id: poId }, function(res){
    if (res.success) {
      $('#emailPOModalLabel').text('Email Purchase Order: ' + res.po.po_number);
      $('#emailPOId').val(poId);
      $('#po_recipient_email').val(res.po.vendor_email || '');
      $('#emailPOModal').modal('show');
    } else {
      alert(res.message || 'Failed to fetch PO details');
    }
  });
}

// ---------- GLOBAL VARIABLES ----------
let poItems = [];

$(document).ready(function() {
    const machineModal = new bootstrap.Modal(document.getElementById('poMachineModal'));
    const spareModal = new bootstrap.Modal(document.getElementById('poSpareModal'));

    // Autocomplete for Vendor
    $('#vendor_name').autocomplete({
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
                    response([]);
                }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            $('#vendor_id').val(ui.item.id);
            $('#vendor_name').val(ui.item.label);
            return false;
        },
        change: function(event, ui) {
            if (!ui.item) {
                $('#vendor_id').val('');
            }
        }
    });

                // Autocomplete for Sales Order
    $('#sales_order_number').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: '../ajax/unified_search.php',
                type: 'GET',
                data: {
                    term: request.term,
                    type: 'AUTOCOMPLETE_SO_NUMBERS'
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
            $('#sales_order_id').val(ui.item.id);
            $('#sales_order_number').val(ui.item.value);
            
            // Fetch SO items and populate the PO
            if (ui.item.id) {
                $.ajax({
                    url: '../ajax/get_sales_order_details.php',
                    type: 'GET',
                    data: { id: ui.item.id },
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            if (data.items) {
                                poItems = []; // Clear existing items
                                data.items.forEach(item => {
                                    const newItem = {
                                        type: item.item_type,
                                        item_id: item.item_id,
                                        name: item.item_name || item.description, // Ensure name is displayed
                                        description: `HSN: ${item.hsn_code || ''}`, // Use description for extra info
                                        quantity: parseInt(item.quantity),
                                        unit_price: parseFloat(item.rate || item.unit_price),
                                        total_price: parseInt(item.quantity) * parseFloat(item.rate || item.unit_price),
                                        sale_order_number: item.sale_order_number
                                    };
                                    poItems.push(newItem);
                                });
                                renderPoItems();
                            }
                        }
                    },
                    error: function() {
                        alert('Could not fetch sales order items.');
                    }
                });
            }
            return false;
        },
        change: function(event, ui) {
            if (!ui.item) {
                $('#sales_order_id').val('');
            }
        }
    });
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

            $.getJSON('../ajax/get_quotation_full.php', { id: q.id }, function(res){
              if (res.success){
                $('#discount_percentage').val(res.quotation.discount_percentage || 0);
                $('#discount_amount').val(res.quotation.discount_amount || 0);

                poItems = (res.items || []).map(function(it, idx){
                  return {
                    type: it.item_type,               // 'machine' / 'spare'
                    item_id: it.item_id,
                    name: it.name || it.item_name || '',
                    description: it.description || '',
                    quantity: parseInt(it.quantity || 1),
                    unit_price: parseFloat(it.unit_price || 0),
                    total_price: parseFloat(it.total_price || 0)
                  };
                });
                renderPoItems();
                calculateTotals();
              } else {
                alert(res.message || 'Failed to load quotation details.');
              }
            });
            return false;
          }
        }
      });

    // Show modals
    $('#poAddMachineBtn').on('click', () => machineModal.show());
    $('#poAddSpareBtn').on('click', () => spareModal.show());

    // Pre-fill price and description on machine selection
    $('#poMachineSelect').on('change', function() {
        const selected = $(this).find('option:selected');
        const price = parseFloat(selected.data('price') || 0);
        const name = selected.data('name') || '';
        const model = selected.data('model') || '';
        const category = selected.data('category') || '';
        const validFrom = selected.data('valid_from') || '';
        const validTo = selected.data('valid_to') || '';
        
        // Set price (ensure minimum display)
        if (price > 0) {
            $('#poMachinePrice').val(price);
        } else {
            $('#poMachinePrice').val('');
            if (selected.val()) {
                alert('No current price found for this machine in Price Master.');
            }
        }
        
        // Set description with consistent format
        if (selected.val()) {
            let description = `${name} - ${category}`;
            if (model) description += ` (Model: ${model})`;
            if (validFrom && validTo) description += `\nPrice valid: ${validFrom} to ${validTo}`;
            $('#poMachineDesc').val(description);
        } else {
            $('#poMachineDesc').val('');
        }
    });

    // Pre-fill price and description on spare selection
    $('#poSpareSelect').on('change', function() {
        const selected = $(this).find('option:selected');
        const price = parseFloat(selected.data('price') || 0);
        const name = selected.data('name') || '';
        const code = selected.data('code') || '';
        
        if (price > 0) {
            $('#poSparePrice').val(price);
        }
        
        if (selected.val()) {
            $('#poSpareDesc').val(`${name} - Code: ${code}`);
        } else {
            $('#poSpareDesc').val('');
        }
    });

    // Add machine to PO with consistent naming
    $('#poAddMachineToPO').on('click', function() {
        const selected = $('#poMachineSelect').find('option:selected');
        const qty = parseInt($('#poMachineQty').val());
        const price = parseFloat($('#poMachinePrice').val());

        if (selected.val() && qty > 0 && price >= 0) {
            const item = {
                type: 'machine',
                item_id: selected.val(),
                name: selected.data('name') || 'Machine', // Ensure minimum name display
                description: $('#poMachineDesc').val(),
                quantity: qty,
                unit_price: price,
                total_price: qty * price
            };
            poItems.push(item);
            renderPoItems();
            machineModal.hide();
            
            // Reset form
            $('#poMachineSelect').val('');
            $('#poMachineQty').val(1);
            $('#poMachinePrice').val('');
            $('#poMachineDesc').val('');
        } else {
            alert('Please select a machine, quantity, and price.');
        }
    });

    // Add spare to PO with consistent naming
    $('#poAddSpareToPO').on('click', function() {
        const selected = $('#poSpareSelect').find('option:selected');
        const qty = parseInt($('#poSpareQty').val());
        const price = parseFloat($('#poSparePrice').val());

        if (selected.val() && qty > 0 && price >= 0) {
            const item = {
                type: 'spare',
                item_id: selected.val(),
                name: selected.data('name') || 'Spare Part', // Ensure minimum name display
                description: $('#poSpareDesc').val(),
                quantity: qty,
                unit_price: price,
                total_price: qty * price
            };
            poItems.push(item);
            renderPoItems();
            spareModal.hide();
            
            // Reset form
            $('#poSpareSelect').val('');
            $('#poSpareQty').val(1);
            $('#poSparePrice').val('');
            $('#poSpareDesc').val('');
        } else {
            alert('Please select a spare part, quantity, and price.');
        }
    });

    // Remove item
    $(document).on('click', '.remove-po-item', function() {
        const index = $(this).data('index');
        if (confirm('Are you sure you want to remove this item?')) {
            poItems.splice(index, 1);
            renderPoItems();
        }
    });

    function renderPoItems() {
        const itemsList = $('#poItemsList');
        itemsList.empty();
        if (poItems.length === 0) {
            itemsList.html('<div class="text-muted text-center py-4"><i class="bi bi-box fs-2"></i><br><strong>No items added yet</strong><br><small>Use the buttons above to add machines and spare parts</small></div>');
        } else {
            poItems.forEach((item, index) => {
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
                        <button type="button" class="btn btn-sm btn-outline-danger remove-po-item" data-index="${index}"><i class="bi bi-trash"></i></button>
                    </div>
                `;
                itemsList.append(itemHtml);
            });
            
            // Add hidden inputs for form submission
            $('#poForm input[name^="items["]').remove();
            poItems.forEach((item, index) => {
                $('#poForm').append(`
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
        const subtotal = poItems.reduce((sum, item) => sum + (item.total_price || 0), 0);
        $('#total_amount').val(subtotal.toFixed(2));
        poCalcDiscount();
    }

    window.poCalcDiscount = function() {
        const totalAmount = parseFloat($('#total_amount').val()) || 0;
        const percentage = parseFloat($('#discount_percentage').val()) || 0;
        const discountAmount = (totalAmount * percentage) / 100;
        $('#discount_amount').val(discountAmount.toFixed(2));
        calculateFinalTotal();
    }
    
    window.poCalcDiscountPct = function() {
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
        $('#grand_total').val(finalTotal.toFixed(2));
    }

    $('#discount_percentage').on('input', poCalcDiscount);
    $('#discount_amount').on('input', poCalcDiscountPct);

    // Search functionality
    $('#searchBtn').on('click', function() {
        const searchTerm = $('#poSearch').val().trim();
        if (searchTerm) {
            window.location.href = '../sales/purchase_orders.php?search=' + encodeURIComponent(searchTerm);
        }
    });

    $('#clearBtn').on('click', function() {
        window.location.href = '../sales/purchase_orders.php';
    });

    $('#poSearch').on('keypress', e => e.which === 13 && $('#searchBtn').click());

    // Edit PO from table
    $(document).on('click', '.edit-po', function() {
        const poId = $(this).data('id');
        $.ajax({
            url: '../ajax/get_purchase_order_details.php',
            type: 'GET',
            data: { id: poId },
            dataType: 'json',
            success: function(data) {
                if(data.success) {
                    $('#poId').val(data.po.id);
                    $('#po_number').val(data.po.po_number);
                    $('#vendor_name').val(data.po.vendor_name);
                    $('#vendor_id').val(data.po.vendor_id);
                    $('#sales_order_number').val(data.po.sales_order_number);
                    $('#sales_order_id').val(data.po.sales_order_id);
                    $('#quotation_number').val(data.po.quotation_number);
                    $('#quotation_id').val(data.po.quotation_id);
                    $('#po_date').val(data.po.po_date);
                    $('#due_date').val(data.po.due_date);
                    $('#status').val(data.po.status);
                    $('#notes').val(data.po.notes);
                    $('#discount_percentage').val(data.po.discount_percentage);
                    $('#discount_amount').val(data.po.discount_amount);

                    // Load PO items with consistent naming
                    poItems = (data.items || []).map(item => ({
                        type: item.item_type,
                        item_id: item.item_id,
                        name: item.item_name || (item.item_type === 'machine' ? 'Machine' : 'Spare Part'),
                        description: item.description || '',
                        quantity: parseInt(item.quantity),
                        unit_price: parseFloat(item.unit_price),
                        total_price: parseFloat(item.total_price)
                    }));
                    
                    renderPoItems();
                    
                    setFormReadOnly(true);
                    $('#saveBtn').hide();
                    $('#editBtn').show();
                    $('#deleteBtn').show();
                    $('#printBtn').show();
                    $('#emailBtn').show();
                    $('#updateBtn').hide();
                    $('#formTitle').text('Purchase Order Details - ' + data.po.po_number);
                    
                    $('html, body').animate({ scrollTop: $('#poForm').offset().top - 100 }, 500);
                } else {
                    alert(data.message);
                }
            }
        });
    });

    $('#editBtn').on('click', function() {
        setFormReadOnly(false);
        $('#editBtn').hide();
        $('#updateBtn').show();
        $('#formAction').val('update_po');
    });

    $('#deleteBtn').on('click', function() {
        const poId = $('#poId').val();
        const poNumber = $('#po_number').val();
        if (poId && confirm('Are you sure you want to delete Purchase Order "' + poNumber + '"?')) {
            window.location.href = 'purchase_orders.php?delete=' + poId;
        }
    });

    $('#resetBtn').on('click', resetForm);
    
    // Email functionality
    $(document).on('click', '.email-po', function() {
        openEmailPOModal($(this).data('id'));
    });
    
    $('#emailBtn').on('click', function() {
        const poId = $('#poId').val();
        if (poId) {
            openEmailPOModal(poId);
        }
    });
    
    $('#sendPOEmailBtn').on('click', function() {
        const btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Sending...');
        const formData = $('#emailPOForm').serialize();
        
        $.ajax({
            url: '../ajax/send_purchase_order_email.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    alert(res.message);
                    $('#emailPOModal').modal('hide');
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
        $('#poForm input, #poForm textarea, #poForm select').not('#poId, #formAction, #poSearch').prop('readonly', readonly);
        $('#poForm select').prop('disabled', readonly);
        $('#poAddMachineBtn, #poAddSpareBtn').prop('disabled', readonly);
        
        // Ensure search input is always functional
        $('#poSearch').prop('readonly', false).prop('disabled', false);
        
        if(readonly) {
            $('.remove-po-item').hide();
            $('#editBtn, #deleteBtn, #printBtn, #emailBtn').prop('disabled', false);
        } else {
            $('.remove-po-item').show();
        }
    }

    function resetForm() {
        $('#poForm')[0].reset();
        $('#poId').val('');
        $('#vendor_id').val('');
        $('#sales_order_id').val('');
        $('#quotation_id').val('');
        $('#formAction').val('create_po');
        poItems = [];
        renderPoItems();
        setFormReadOnly(false);
        $('#saveBtn').show();
        $('#editBtn, #updateBtn, #deleteBtn, #printBtn, #emailBtn').hide();
        $('#formTitle').text('Create Purchase Order');
        
        // Set default dates
        $('#po_date').val(new Date().toISOString().split('T')[0]);
        const dueDate = new Date();
        dueDate.setDate(dueDate.getDate() + 15);
        $('#due_date').val(dueDate.toISOString().split('T')[0]);
    }
    // Initial render
    renderPoItems();

    // Autocomplete for main PO search
    $('#poSearch').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: '../ajax/unified_search.php',
                type: 'GET',
                data: {
                    term: request.term,
                    type: 'AUTOCOMPLETE_PO'
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
                // Load the selected purchase order into the form like edit button
                loadPurchaseOrderData(ui.item.id);
            } else {
                // Just perform search if no specific item selected
                $('#poSearch').val(ui.item.value);
                $('#searchBtn').click();
            }
            return false;
        },
        focus: function(event, ui) {
            // Prevent input value from being updated on focus
            return false;
        }
    });

    // Function to load purchase order data (similar to edit button functionality)
    function loadPurchaseOrderData(poId) {
        $.ajax({
            url: '../ajax/get_purchase_order_details.php',
            type: 'GET',
            data: { id: poId },
            dataType: 'json',
            success: function(data) {
                if(data.success) {
                    // Fill form with PO data
                    $('#poId').val(data.po.id);
                    $('#po_number').val(data.po.po_number);
                    $('#vendor_name').val(data.po.vendor_name);
                    $('#vendor_id').val(data.po.vendor_id);
                    $('#sales_order_number').val(data.po.sales_order_number || '');
                    $('#sales_order_id').val(data.po.sales_order_id || '');
                    $('#quotation_number').val(data.po.quotation_number || '');
                    $('#quotation_id').val(data.po.quotation_id || '');
                    $('#po_date').val(data.po.po_date);
                    $('#due_date').val(data.po.due_date);
                    $('#status').val(data.po.status);
                    $('#notes').val(data.po.notes || '');
                    $('#discount_percentage').val(data.po.discount_percentage || 0);
                    $('#discount_amount').val(data.po.discount_amount || 0);

                    // Load PO items with consistent naming
                    poItems = (data.items || []).map(item => ({
                        type: item.item_type,
                        item_id: item.item_id,
                        name: item.item_name || (item.item_type === 'machine' ? 'Machine' : 'Spare Part'),
                        description: item.description || '',
                        quantity: parseInt(item.quantity),
                        unit_price: parseFloat(item.unit_price),
                        total_price: parseFloat(item.total_price)
                    }));
                    
                    renderPoItems();
                    
                    setFormReadOnly(true);
                    $('#saveBtn').hide();
                    $('#editBtn').show();
                    $('#deleteBtn').show();
                    $('#printBtn').show();
                    $('#emailBtn').show();
                    $('#updateBtn').hide();
                    $('#formTitle').text('Purchase Order Details - ' + data.po.po_number);
                    
                    // Clear search field
                    $('#poSearch').val('');
                    
                    // Scroll to form
                    $('html, body').animate({ scrollTop: $('#poForm').offset().top - 100 }, 500);
                } else {
                    alert(data.message || 'Failed to load purchase order details');
                }
            },
            error: function() {
                alert('Error loading purchase order details');
            }
        });
    }

    // Ensure search input always accepts keyboard input
    $('#poSearch').on('focus', function() {
        $(this).prop('readonly', false);
        console.log('Search input focused and enabled for typing');
    });

    // Debug event to check if typing is working
    $('#poSearch').on('input', function() {
        console.log('Search input changed:', $(this).val());
    });

    // Initial render
    renderPoItems();
});
