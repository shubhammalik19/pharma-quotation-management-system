/* purchase_invoices.js - Purchase Invoice Management
   Requires: jQuery, jQuery UI (autocomplete), Bootstrap (for modals)
   Endpoints used:
     - ../ajax/unified_search.php           (AUTOCOMPLETE_VENDORS, AUTOCOMPLETE_PO_NUMBERS)
     - ../ajax/get_purchase_order_details.php (id)
     - ../ajax/get_purchase_invoice_details.php (id)
     - ../ajax/get_machine_spares.php (machine_id)
     - ../ajax/send_purchase_invoice_email.php (POST form)
*/

// ---------- SMALL HELPERS ----------
function fmtDate(d){ if(!d) return ''; const dt=new Date(d); return dt.toLocaleDateString('en-GB'); }
function esc(t){ if(!t) return ''; const m={'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}; return t.toString().replace(/[&<>"']/g, x=>m[x]); }

// ---------- PRINT + EMAIL ----------
function printPI(piId){
  if (!piId) {
    piId = $('#piId').val();
  }
  if (piId) window.open(`../docs/print_purchase_invoice.php?id=${piId}`, '_blank');
}
function openEmailPIModal(piId){
  $.getJSON('../ajax/get_purchase_invoice_details.php', { id: piId }, function(res){
    if (res.success) {
      $('#emailPIModalLabel').text('Email Purchase Invoice: ' + res.pi.pi_number);
      $('#emailPIId').val(piId);
      $('#pi_recipient_email').val(res.pi.vendor_email || '');
      $('#emailPIModal').modal('show');
    } else {
      alert(res.message || 'Failed to fetch PI details');
    }
  });
}

// ---------- GLOBAL VARIABLES ----------
let piItems = [];

$(document).ready(function() {
    const machineModal = new bootstrap.Modal(document.getElementById('piMachineModal'));
    const spareModal = new bootstrap.Modal(document.getElementById('piSpareModal'));
    const spareToMachineModal = new bootstrap.Modal(document.getElementById('piSpareToMachineModal'));

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

    // Autocomplete for Purchase Order
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
                    response([]);
                }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            $('#purchase_order_id').val(ui.item.id);
            $('#purchase_order_number').val(ui.item.value);
            
            // Fetch PO items and populate the PI
            if (ui.item.id) {
                $.ajax({
                    url: '../ajax/get_purchase_order_details.php',
                    type: 'GET',
                    data: { id: ui.item.id },
                    dataType: 'json',
                    success: function(data) {
                        if (data.success && data.items) {
                            // Clear existing items
                            piItems = [];
                            
                            // Add PO items to PI
                            data.items.forEach(function(item) {
                                piItems.push({
                                    type: item.item_type,
                                    item_id: item.item_id,
                                    name: item.item_name,
                                    description: item.description || '',
                                    quantity: item.quantity,
                                    unit_price: parseFloat(item.unit_price),
                                    total_price: parseFloat(item.total_price),
                                    machine_id: item.machine_id || null
                                });
                            });
                            
                            // Update UI
                            piRenderItems();
                            piCalcTotals();
                            updateTargetMachineDropdown(); // Update machine dropdown
                            
                            // Set vendor details if available
                            if (data.po.vendor_name) {
                                $('#vendor_name').val(data.po.vendor_name);
                                $('#vendor_id').val(data.po.vendor_id);
                            }
                        }
                    },
                    error: function() {
                        alert('Error loading purchase order details');
                    }
                });
            }
            return false;
        },
        change: function(event, ui) {
            if (!ui.item) {
                $('#purchase_order_id').val('');
            }
        }
    });

    // Search functionality
    $('#searchBtn').click(function() {
        const searchTerm = $('#piSearch').val().trim();
        if (searchTerm) {
            window.location.href = `?search=${encodeURIComponent(searchTerm)}`;
        }
    });

    $('#clearBtn').click(function() {
        $('#piSearch').val('');
        window.location.href = window.location.pathname;
    });

    $('#piSearch').keypress(function(e) {
        if (e.which === 13) {
            $('#searchBtn').click();
        }
    });

    // Add Machine Button
    $('#piAddMachineBtn').click(function() {
        $('#piMachineSelect').val('');
        $('#piMachineQty').val(1);
        $('#piMachinePrice').val('');
        $('#piMachineDesc').val('');
        $('#machineSparesList').hide();
        $('#sparePartsContainer').empty();
        machineModal.show();
    });

    // Add Spare Button (Separate)
    $('#piAddSpareBtn').click(function() {
        $('#piSpareSelect').val('');
        $('#piSpareQty').val(1);
        $('#piSparePrice').val('');
        $('#piSpareDesc').val('');
        spareModal.show();
    });

    // Add Spare to Machine Button
    $('#piAddSpareToMachineBtn').click(function() {
        // Update machine dropdown with current machines in invoice
        updateTargetMachineDropdown();
        
        $('#piSpareToMachineSelect').val('');
        $('#piSpareToMachineQty').val(1);
        $('#piSpareToMachinePrice').val('');
        $('#piSpareToMachineDesc').val('');
        spareToMachineModal.show();
    });

    // Machine selection change - Load related spares
    $('#piMachineSelect').change(function() {
        const machineId = $(this).val();
        const selectedOption = $(this).find('option:selected');
        
        if (machineId) {
            // Set machine price
            const price = selectedOption.data('price') || 0;
            $('#piMachinePrice').val(price);
            
            // Load related spares
            loadMachineSpares(machineId);
        } else {
            $('#piMachinePrice').val('');
            $('#machineSparesList').hide();
        }
    });

    // Spare selection change - Auto fill price
    $('#piSpareSelect, #piSpareToMachineSelect').change(function() {
        const selectedOption = $(this).find('option:selected');
        const targetPriceField = $(this).attr('id') === 'piSpareSelect' ? '#piSparePrice' : '#piSpareToMachinePrice';
        
        if (selectedOption.val()) {
            const price = selectedOption.data('price') || 0;
            $(targetPriceField).val(price);
        }
    });

    // Add Machine to PI
    $('#piAddMachineToPI').click(function() {
        const machineSelect = $('#piMachineSelect');
        const selectedOption = machineSelect.find('option:selected');
        
        if (!selectedOption.val()) {
            alert('Please select a machine');
            return;
        }

        const quantity = parseInt($('#piMachineQty').val()) || 1;
        const unitPrice = parseFloat($('#piMachinePrice').val()) || 0;
        
        if (unitPrice <= 0) {
            alert('Please enter a valid unit price');
            return;
        }

        // Add machine to items
        const machineItem = {
            type: 'machine',
            item_id: selectedOption.val(),
            name: selectedOption.data('name'),
            description: $('#piMachineDesc').val(),
            quantity: quantity,
            unit_price: unitPrice,
            total_price: quantity * unitPrice
        };

        piItems.push(machineItem);

        // Add selected spares
        $('#sparePartsContainer .spare-item.selected').each(function() {
            const spareData = $(this).data();
            const spareQty = parseInt($(this).find('.spare-quantity').val()) || 1;
            const sparePrice = parseFloat($(this).find('.spare-price').val()) || 0;

            if (sparePrice > 0) {
                const spareItem = {
                    type: 'spare',
                    item_id: spareData.id,
                    name: spareData.name,
                    description: spareData.description || '',
                    quantity: spareQty,
                    unit_price: sparePrice,
                    total_price: spareQty * sparePrice,
                    machine_id: selectedOption.val() // Link spare to machine
                };

                piItems.push(spareItem);
            }
        });

        piRenderItems();
        piCalcTotals();
        updateTargetMachineDropdown(); // Update machine dropdown for spare linking
        machineModal.hide();
    });

    // Add Spare to PI (Separate)
    $('#piAddSpareToPI').click(function() {
        const spareSelect = $('#piSpareSelect');
        const selectedOption = spareSelect.find('option:selected');
        
        if (!selectedOption.val()) {
            alert('Please select a spare part');
            return;
        }

        const quantity = parseInt($('#piSpareQty').val()) || 1;
        const unitPrice = parseFloat($('#piSparePrice').val()) || 0;
        
        if (unitPrice <= 0) {
            alert('Please enter a valid unit price');
            return;
        }

        const spareItem = {
            type: 'spare',
            item_id: selectedOption.val(),
            name: selectedOption.data('name'),
            description: $('#piSpareDesc').val(),
            quantity: quantity,
            unit_price: unitPrice,
            total_price: quantity * unitPrice,
            machine_id: null // Separate spare, not linked to machine
        };

        piItems.push(spareItem);
        piRenderItems();
        piCalcTotals();
        updateTargetMachineDropdown(); // Update machine dropdown
        spareModal.hide();
    });

    // Add Spare to Machine
    $('#piAddSpareToMachineToPI').click(function() {
        const targetMachine = $('#piTargetMachine').val();
        const spareSelect = $('#piSpareToMachineSelect');
        const selectedOption = spareSelect.find('option:selected');
        
        if (!targetMachine) {
            alert('Please select a target machine');
            return;
        }
        
        if (!selectedOption.val()) {
            alert('Please select a spare part');
            return;
        }

        const quantity = parseInt($('#piSpareToMachineQty').val()) || 1;
        const unitPrice = parseFloat($('#piSpareToMachinePrice').val()) || 0;
        
        if (unitPrice <= 0) {
            alert('Please enter a valid unit price');
            return;
        }

        const spareItem = {
            type: 'spare',
            item_id: selectedOption.val(),
            name: selectedOption.data('name'),
            description: $('#piSpareToMachineDesc').val(),
            quantity: quantity,
            unit_price: unitPrice,
            total_price: quantity * unitPrice,
            machine_id: parseInt(targetMachine) // Linked to specific machine
        };

        piItems.push(spareItem);
        piRenderItems();
        piCalcTotals();
        updateTargetMachineDropdown(); // Update machine dropdown
        spareToMachineModal.hide();
    });

    // Form submission
    $('#piForm').submit(function(e) {
        e.preventDefault();
        
        if (piItems.length === 0) {
            alert('Please add at least one item to the invoice');
            return;
        }

        // Add items to form
        const formData = new FormData(this);
        piItems.forEach((item, index) => {
            Object.keys(item).forEach(key => {
                formData.append(`items[${index}][${key}]`, item[key]);
            });
        });

        // Submit form
        $.ajax({
            url: '',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                location.reload();
            },
            error: function() {
                alert('Error submitting form');
            }
        });
    });

    // Edit PI button
    $(document).on('click', '.edit-pi', function() {
        const piId = $(this).data('id');
        loadPIDetails(piId);
    });

    // Email PI button
    $(document).on('click', '.email-pi', function() {
        const piId = $(this).data('id');
        openEmailPIModal(piId);
    });

    // Send email
    $('#sendPIEmailBtn').click(function() {
        const formData = $('#emailPIForm').serialize();
        
        $.ajax({
            url: '../ajax/send_purchase_invoice_email.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Email sent successfully!');
                    $('#emailPIModal').modal('hide');
                } else {
                    alert('Error: ' + (response.message || 'Failed to send email'));
                }
            },
            error: function() {
                alert('Error sending email');
            }
        });
    });

    // Reset form
    $('#resetBtn').click(function() {
        $('#piForm')[0].reset();
        piItems = [];
        piRenderItems();
        piCalcTotals();
        updateTargetMachineDropdown(); // Clear machine dropdown
        $('#formAction').val('create_pi');
        $('#piId').val('');
        $('#formTitle').text('Create Purchase Invoice');
        $('.form-control').removeClass('readonly-field');
        $('#saveBtn').show();
        $('#updateBtn, #editBtn, #deleteBtn, #printBtn, #emailBtn').hide();
    });

    // Edit button - Enable form for editing
    $('#editBtn').click(function() {
        $('#formTitle').text('Edit Purchase Invoice');
        $('.form-control').removeClass('readonly-field');
        $('#editBtn, #printBtn, #emailBtn').hide();
        $('#updateBtn, #deleteBtn').show();
    });

    // Delete button
    $('#deleteBtn').click(function() {
        const piId = $('#piId').val();
        const piNumber = $('#pi_number').val();
        
        if (confirm(`Are you sure you want to delete Purchase Invoice ${piNumber}? This action cannot be undone.`)) {
            window.location.href = `?delete=${piId}`;
        }
    });

    // Print button
    $('#printBtn').click(function() {
        const piId = $('#piId').val();
        if (piId) {
            printPI(piId);
        }
    });

    // Email button
    $('#emailBtn').click(function() {
        const piId = $('#piId').val();
        if (piId) {
            openEmailPIModal(piId);
        }
    });
});

// Load machine related spares
function loadMachineSpares(machineId) {
    $.ajax({
        url: '../ajax/get_machine_spares.php',
        type: 'GET',
        data: { machine_id: machineId },
        dataType: 'json',
        success: function(data) {
            if (data.success && data.spares.length > 0) {
                renderMachineSpares(data.spares);
                $('#machineSparesList').show();
            } else {
                $('#machineSparesList').hide();
            }
        },
        error: function() {
            $('#machineSparesList').hide();
        }
    });
}

// Render machine spares selection
function renderMachineSpares(spares) {
    let html = '';
    spares.forEach(spare => {
        html += `
            <div class="spare-item" data-id="${spare.id}" data-name="${esc(spare.part_name)}" data-description="${esc(spare.description || '')}">
                <div class="form-check">
                    <input class="form-check-input spare-checkbox" type="checkbox" id="spare_${spare.id}">
                    <label class="form-check-label fw-bold" for="spare_${spare.id}">
                        ${esc(spare.part_name)} ${spare.part_code ? '(' + esc(spare.part_code) + ')' : ''}
                    </label>
                </div>
                <div class="row mt-2" style="display: none;">
                    <div class="col-md-4">
                        <input type="number" class="form-control form-control-sm spare-quantity" placeholder="Qty" value="1" min="1">
                    </div>
                    <div class="col-md-8">
                        <input type="number" class="form-control form-control-sm spare-price" placeholder="Unit Price" value="${spare.price || 0}" step="0.01" min="0">
                    </div>
                </div>
            </div>
        `;
    });
    
    $('#sparePartsContainer').html(html);
    
    // Handle spare selection
    $('.spare-checkbox').change(function() {
        const spareItem = $(this).closest('.spare-item');
        const row = spareItem.find('.row');
        
        if ($(this).is(':checked')) {
            spareItem.addClass('selected');
            row.show();
        } else {
            spareItem.removeClass('selected');
            row.hide();
        }
    });
}

// Update target machine dropdown
function updateTargetMachineDropdown() {
    const machineDropdown = $('#piTargetMachine');
    machineDropdown.empty();
    
    // Get all machines currently in the invoice
    const machines = piItems.filter(item => item.type === 'machine');
    
    if (machines.length === 0) {
        machineDropdown.append('<option value="">No machines added yet. Add a machine first.</option>');
        return;
    }
    
    machineDropdown.append('<option value="">Select target machine...</option>');
    machines.forEach((machine, index) => {
        machineDropdown.append(`<option value="${machine.item_id}" data-index="${index}">${esc(machine.name)} (Qty: ${machine.quantity})</option>`);
    });
}

// Render PI Items with enhanced machine linking display
function piRenderItems() {
    const container = $('#piItemsList');
    
    if (piItems.length === 0) {
        container.html('<p class="text-muted text-center m-3">No items added yet.</p>');
        return;
    }
    
    let html = '';
    
    // Group items by machine (machines first, then their linked spares, then separate spares)
    const machines = piItems.filter(item => item.type === 'machine');
    const separateSpares = piItems.filter(item => item.type === 'spare' && !item.machine_id);
    
    // Render machines with their linked spares
    machines.forEach((machine, machineIndex) => {
        const linkedSpares = piItems.filter(item => 
            item.type === 'spare' && item.machine_id && item.machine_id === machine.item_id
        );
        
        // Render machine
        html += `
            <div class="machine-group mb-3 border rounded p-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #e3f2fd 100%);">
                <div class="item-row">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center mb-1">
                                <i class="bi bi-gear me-2 text-primary"></i>
                                <strong class="text-primary">${esc(machine.name)}</strong>
                                <span class="badge bg-primary ms-2">MACHINE</span>
                            </div>
                            ${machine.description ? `<small class="text-muted">${esc(machine.description)}</small><br>` : ''}
                            <small><strong>Qty:</strong> ${machine.quantity} | <strong>Price:</strong> ₹${Number(machine.unit_price).toFixed(2)} | <strong>Total:</strong> ₹${Number(machine.total_price).toFixed(2)}</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="piRemoveItem(${piItems.indexOf(machine)})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
        `;
        
        // Render linked spares under this machine
        if (linkedSpares.length > 0) {
            html += `
                <div class="linked-spares mt-3 ps-4">
                    <h6 class="text-info mb-2"><i class="bi bi-link-45deg"></i> Linked Spare Parts:</h6>
            `;
            
            linkedSpares.forEach(spare => {
                html += `
                    <div class="item-row border rounded p-2 mb-2 bg-white">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="bi bi-tools me-2 text-success"></i>
                                    <strong>${esc(spare.name)}</strong>
                                    <span class="badge bg-success ms-2">SPARE</span>
                                    <span class="badge bg-info ms-1">LINKED</span>
                                </div>
                                ${spare.description ? `<small class="text-muted">${esc(spare.description)}</small><br>` : ''}
                                <small><strong>Qty:</strong> ${spare.quantity} | <strong>Price:</strong> ₹${Number(spare.unit_price).toFixed(2)} | <strong>Total:</strong> ₹${Number(spare.total_price).toFixed(2)}</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="piRemoveItem(${piItems.indexOf(spare)})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            html += `</div>`;
        }
        
        html += `</div>`;
    });
    
    // Render separate spares
    if (separateSpares.length > 0) {
        html += `
            <div class="separate-spares mb-3">
                <h6 class="text-success mb-2"><i class="bi bi-tools"></i> Independent Spare Parts:</h6>
        `;
        
        separateSpares.forEach(spare => {
            html += `
                <div class="item-row border rounded p-2 mb-2 bg-white">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center mb-1">
                                <i class="bi bi-tools me-2 text-success"></i>
                                <strong>${esc(spare.name)}</strong>
                                <span class="badge bg-success ms-2">SPARE</span>
                                <span class="badge bg-secondary ms-1">SEPARATE</span>
                            </div>
                            ${spare.description ? `<small class="text-muted">${esc(spare.description)}</small><br>` : ''}
                            <small><strong>Qty:</strong> ${spare.quantity} | <strong>Price:</strong> ₹${Number(spare.unit_price).toFixed(2)} | <strong>Total:</strong> ₹${Number(spare.total_price).toFixed(2)}</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="piRemoveItem(${piItems.indexOf(spare)})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        
        html += `</div>`;
    }
    
    container.html(html);
}

// Remove item
function piRemoveItem(index) {
    if (index >= 0 && index < piItems.length) {
        piItems.splice(index, 1);
        piRenderItems();
        piCalcTotals();
        updateTargetMachineDropdown(); // Update machine dropdown when items are removed
    }
}

// Calculate totals
function piCalcTotals() {
    const subtotal = piItems.reduce((sum, item) => sum + parseFloat(item.total_price), 0);
    $('#total_amount').val(subtotal.toFixed(2));
    
    const discountAmount = parseFloat($('#discount_amount').val()) || 0;
    const grandTotal = subtotal - discountAmount;
    $('#grand_total').val(grandTotal.toFixed(2));
}

// Calculate discount
function piCalcDiscount() {
    const subtotal = parseFloat($('#total_amount').val()) || 0;
    const discountPct = parseFloat($('#discount_percentage').val()) || 0;
    const discountAmount = (subtotal * discountPct) / 100;
    
    $('#discount_amount').val(discountAmount.toFixed(2));
    piCalcTotals();
}

// Calculate discount percentage
function piCalcDiscountPct() {
    const subtotal = parseFloat($('#total_amount').val()) || 0;
    const discountAmount = parseFloat($('#discount_amount').val()) || 0;
    
    if (subtotal > 0) {
        const discountPct = (discountAmount / subtotal) * 100;
        $('#discount_percentage').val(discountPct.toFixed(2));
    }
    
    piCalcTotals();
}

// Load PI details for editing
function loadPIDetails(piId) {
    $.ajax({
        url: '../ajax/get_purchase_invoice_details.php',
        type: 'GET',
        data: { id: piId },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                const pi = data.pi;
                
                // Fill form fields
                $('#piId').val(pi.id);
                $('#pi_number').val(pi.pi_number);
                $('#vendor_name').val(pi.vendor_name);
                $('#vendor_id').val(pi.vendor_id);
                $('#purchase_order_number').val(pi.purchase_order_number || '');
                $('#purchase_order_id').val(pi.purchase_order_id || '');
                $('#pi_date').val(pi.pi_date);
                $('#due_date').val(pi.due_date);
                $('#status').val(pi.status);
                $('#notes').val(pi.notes);
                $('#total_amount').val(pi.total_amount);
                $('#discount_percentage').val(pi.discount_percentage);
                $('#discount_amount').val(pi.discount_amount);
                $('#grand_total').val(pi.final_total);
                
                // Load items and transform them to the expected structure
                piItems = [];
                if (data.items && data.items.length > 0) {
                    data.items.forEach(function(item) {
                        piItems.push({
                            type: item.item_type,
                            item_id: parseInt(item.item_id),
                            name: item.item_name,
                            description: item.description || '',
                            quantity: parseInt(item.quantity),
                            unit_price: parseFloat(item.unit_price),
                            total_price: parseFloat(item.total_price),
                            machine_id: item.machine_id ? parseInt(item.machine_id) : null
                        });
                    });
                }
                piRenderItems();
                updateTargetMachineDropdown(); // Update machine dropdown
                
                // Update UI for view mode (readonly)
                $('#formTitle').text('View Purchase Invoice');
                $('#formAction').val('update_pi');
                $('.form-control').addClass('readonly-field');
                $('#saveBtn').hide();
                $('#updateBtn').hide(); // Hide update button initially
                $('#editBtn, #deleteBtn, #printBtn, #emailBtn').show();
                
                // Scroll to form
                $('html, body').animate({
                    scrollTop: $('#piForm').offset().top
                }, 500);
            } else {
                alert('Error loading purchase invoice details: ' + data.message);
            }
        },
        error: function() {
            alert('Error loading purchase invoice details');
        }
    });
}
