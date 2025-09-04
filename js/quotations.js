/* quotations.js - Uniform design consistent with purchase_orders.js
   Requires: jQuery, jQuery UI (autocomplete), Bootstrap (for modals)
   Endpoints used:
     - ../ajax/unified_search.php           (AUTOCOMPLETE_CUSTOMERS, AUTOCOMPLETE_QUOTATIONS)
     - ../ajax/get_quotation_details.php    (id)
     - ../ajax/send_quotation_email.php     (POST form)
*/

// ---------- SMALL HELPERS ----------
function fmtDate(d){ if(!d) return ''; const dt=new Date(d); return dt.toLocaleDateString('en-GB'); }
function esc(t){ if(!t) return ''; const m={'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}; return t.toString().replace(/[&<>"']/g, x=>m[x]); }

// ---------- PRINT + EMAIL ----------
function printQuotation(quotationId){
  if (!quotationId) {
    quotationId = $('#quotationId').val();
  }
  if (quotationId) window.open(`../docs/print_quotation.php?id=${quotationId}`, '_blank');
}
function openEmailQuotationModal(quotationId){
  $.getJSON('../ajax/get_quotation_details.php', { id: quotationId }, function(res){
    if (res.success) {
      $('#emailQuotationModalLabel').text('Email Quotation: ' + res.quotation.quotation_number);
      $('#emailQuotationId').val(quotationId);
      $('#quotation_recipient_email').val(res.quotation.customer_email || '');
      $('#emailQuotationModal').modal('show');
    } else {
      alert(res.message || 'Failed to fetch quotation details');
    }
  });
}

// Function to load machine features
function loadMachineFeatures(machineId) {
    if (!machineId) {
        $('#machineFeaturesList').hide();
        return;
    }
    
    $.ajax({
        url: '../ajax/get_machine_features_for_quotation.php',
        type: 'GET',
        data: { machine_id: machineId },
        dataType: 'json',
        success: function(data) {
            if (data.success && data.features && data.features.length > 0) {
                displayMachineFeatures(data.features);
            } else {
                $('#machineFeaturesList').hide();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading machine features:', error);
            $('#machineFeaturesList').hide();
        }
    });
}

// Function to display machine features
function displayMachineFeatures(features) {
    let featuresHtml = '';
    
    features.forEach(function(feature, index) {
        const hasPrice = feature.has_price && feature.feature_price > 0;
        const priceDisplay = hasPrice ? `₹${parseFloat(feature.feature_price).toFixed(2)}` : 'No price set';
        const priceClass = hasPrice ? 'feature-price' : 'no-price';
        
        featuresHtml += `
            <div class="feature-item" data-feature-id="${feature.feature_id}" data-feature-name="${esc(feature.feature_name)}" data-feature-price="${feature.feature_price || 0}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <input type="checkbox" class="form-check-input me-2" id="feature_${feature.feature_id}">
                        <label for="feature_${feature.feature_id}" class="form-check-label">
                            <strong>${esc(feature.feature_name)}</strong>
                        </label>
                    </div>
                    <div class="${priceClass}">${priceDisplay}</div>
                </div>
                ${hasPrice ? `
                <div class="mt-2">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label small">Quantity</label>
                            <input type="number" class="form-control form-control-sm feature-qty" min="1" value="1" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Unit Price (₹)</label>
                            <input type="number" class="form-control form-control-sm feature-price-input" step="0.01" min="0" value="${feature.feature_price}" disabled>
                        </div>
                    </div>
                </div>
                ` : ''}
            </div>
        `;
    });
    
    $('#featuresContainer').html(featuresHtml);
    $('#machineFeaturesList').show();
    
    // Handle feature selection
    $('.feature-item').on('click', function() {
        const checkbox = $(this).find('input[type="checkbox"]');
        const isChecked = !checkbox.prop('checked');
        checkbox.prop('checked', isChecked);
        
        if (isChecked) {
            $(this).addClass('selected');
            $(this).find('.feature-qty, .feature-price-input').prop('disabled', false);
        } else {
            $(this).removeClass('selected');
            $(this).find('.feature-qty, .feature-price-input').prop('disabled', true);
        }
    });
    
    // Handle checkbox clicks
    $('.feature-item input[type="checkbox"]').on('click', function(e) {
        e.stopPropagation();
        const featureItem = $(this).closest('.feature-item');
        if ($(this).prop('checked')) {
            featureItem.addClass('selected');
            featureItem.find('.feature-qty, .feature-price-input').prop('disabled', false);
        } else {
            featureItem.removeClass('selected');
            featureItem.find('.feature-qty, .feature-price-input').prop('disabled', true);
        }
    });
}

// ---------- GLOBAL VARIABLES ----------
let quotationItems = [];

$(document).ready(function() {
    const machineModal = new bootstrap.Modal(document.getElementById('quotationMachineModal'));
    const spareModal = new bootstrap.Modal(document.getElementById('quotationSpareModal'));

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

    // Show modals
    $('#quotationAddMachineBtn').on('click', () => machineModal.show());
    $('#quotationAddSpareBtn').on('click', () => spareModal.show());

    // Pre-fill price and description on machine selection
    $('#quotationMachineSelect').on('change', function() {
        const selected = $(this).find('option:selected');
        const price = parseFloat(selected.data('price') || 0);
        const name = selected.data('name') || '';
        const machineId = selected.val();
        
        // Set price (ensure minimum display)
        if (price > 0) {
            $('#quotationMachinePrice').val(price);
        } else {
            $('#quotationMachinePrice').val('');
            if (selected.val()) {
                alert('No current price found for this machine in Price Master.');
            }
        }
        
        // Set description with consistent format
        if (selected.val()) {
            $('#quotationMachineDesc').val(`${name} - Machine`);
        } else {
            $('#quotationMachineDesc').val('');
        }
        
        // Load machine features
        if (machineId) {
            loadMachineFeatures(machineId);
        } else {
            $('#machineFeaturesList').hide();
        }
    });

    // Pre-fill price and description on spare selection
    $('#quotationSpareSelect').on('change', function() {
        const selected = $(this).find('option:selected');
        const price = parseFloat(selected.data('price') || 0);
        const name = selected.data('name') || '';
        
        if (price > 0) {
            $('#quotationSparePrice').val(price);
        }
        
        if (selected.val()) {
            $('#quotationSpareDesc').val(`${name} - Spare Part`);
        } else {
            $('#quotationSpareDesc').val('');
        }
    });

    // Add machine to quotation with consistent naming
    $('#quotationAddMachineToQuotation').on('click', function() {
        const selected = $('#quotationMachineSelect').find('option:selected');
        const qty = parseInt($('#quotationMachineQty').val());
        const price = parseFloat($('#quotationMachinePrice').val());

        if (selected.val() && qty > 0 && price >= 0) {
            // Collect selected features
            const selectedFeatures = [];
            $('.feature-item.selected').each(function() {
                const featureName = $(this).data('feature-name');
                const featurePrice = parseFloat($(this).find('.feature-price-input').val() || 0);
                const featureQty = parseInt($(this).find('.feature-qty').val() || 1);
                
                if (featurePrice > 0) {
                    selectedFeatures.push({
                        name: featureName,
                        price: featurePrice,
                        quantity: featureQty
                    });
                }
            });
            
            const item = {
                type: 'machine',
                item_id: selected.val(),
                name: selected.data('name') || 'Machine',
                description: $('#quotationMachineDesc').val(),
                specifications: $('#quotationMachineSpecs').val(),
                quantity: qty,
                unit_price: price,
                total_price: qty * price,
                sl_no: quotationItems.length + 1,
                features: selectedFeatures // Add features to the item
            };
            quotationItems.push(item);
            renderQuotationItems();
            machineModal.hide();
            
            // Reset form
            $('#quotationMachineSelect').val('');
            $('#quotationMachineQty').val(1);
            $('#quotationMachinePrice').val('');
            $('#quotationMachineDesc').val('');
            $('#quotationMachineSpecs').val('');
            $('#machineFeaturesList').hide();
        } else {
            alert('Please select a machine, quantity, and price.');
        }
    });

    // Add spare to quotation with consistent naming
    $('#quotationAddSpareToQuotation').on('click', function() {
        const selected = $('#quotationSpareSelect').find('option:selected');
        const qty = parseInt($('#quotationSpareQty').val());
        const price = parseFloat($('#quotationSparePrice').val());

        if (selected.val() && qty > 0 && price >= 0) {
            const item = {
                type: 'spare',
                item_id: selected.val(),
                name: selected.data('name') || 'Spare Part',
                description: $('#quotationSpareDesc').val(),
                specifications: $('#quotationSpareSpecs').val(),
                quantity: qty,
                unit_price: price,
                total_price: qty * price,
                sl_no: quotationItems.length + 1
            };
            quotationItems.push(item);
            renderQuotationItems();
            spareModal.hide();
            
            // Reset form
            $('#quotationSpareSelect').val('');
            $('#quotationSpareQty').val(1);
            $('#quotationSparePrice').val('');
            $('#quotationSpareDesc').val('');
            $('#quotationSpareSpecs').val('');
        } else {
            alert('Please select a spare part, quantity, and price.');
        }
    });

    // Remove item
    $(document).on('click', '.remove-quotation-item', function() {
        const index = $(this).data('index');
        if (confirm('Are you sure you want to remove this item?')) {
            quotationItems.splice(index, 1);
            renderQuotationItems();
        }
    });

    function renderQuotationItems() {
        const itemsList = $('#quotationItemsList');
        itemsList.empty();
        if (quotationItems.length === 0) {
            itemsList.html('<div class="text-muted text-center py-4"><i class="bi bi-box fs-2"></i><br><strong>No items added yet</strong><br><small>Use the buttons above to add machines and spare parts</small></div>');
        } else {
            quotationItems.forEach((item, index) => {
                const icon = item.type === 'machine' ? 'bi-gear' : 'bi-tools';
                const badge = item.type === 'machine' ? 'bg-primary' : 'bg-success';
                const displayName = item.name || (item.type === 'machine' ? 'Machine' : 'Spare Part');
                const displayPrice = (item.unit_price || 0).toFixed(2);
                
                // Build features display for machines
                let featuresHtml = '';
                if (item.type === 'machine' && item.features && item.features.length > 0) {
                    featuresHtml = '<div class="mt-2"><small class="text-primary"><i class="bi bi-stars"></i> Features:</small>';
                    item.features.forEach(feature => {
                        const featureTotal = feature.price * feature.quantity;
                        featuresHtml += `<br><small class="text-muted ms-3">• ${esc(feature.name)} (Qty: ${feature.quantity} × ₹${feature.price.toFixed(2)} = ₹${featureTotal.toFixed(2)})</small>`;
                    });
                    featuresHtml += '</div>';
                }
                
                const itemHtml = `
                    <div class="d-flex justify-content-between align-items-start mb-2 p-2 border rounded">
                        <div>
                            <i class="bi ${icon}"></i>
                            <strong>${esc(displayName)}</strong>
                            <span class="badge ${badge} ms-1">${item.type}</span>
                            <br><small class="text-muted">Qty: ${item.quantity} × ₹${displayPrice} = ₹${(item.total_price || 0).toFixed(2)}</small>
                            ${item.description ? '<br><small class="text-muted">' + esc(item.description) + '</small>' : ''}
                            ${featuresHtml}
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-quotation-item" data-index="${index}"><i class="bi bi-trash"></i></button>
                    </div>
                `;
                itemsList.append(itemHtml);
            });
            
            // Add hidden inputs for form submission
            $('#quotationForm input[name^="items["]').remove();
            quotationItems.forEach((item, index) => {
                $('#quotationForm').append(`
                    <input type="hidden" name="items[${index}][type]" value="${item.type}">
                    <input type="hidden" name="items[${index}][item_id]" value="${item.item_id}">
                    <input type="hidden" name="items[${index}][name]" value="${esc(item.name)}">
                    <input type="hidden" name="items[${index}][description]" value="${esc(item.description || '')}">
                    <input type="hidden" name="items[${index}][specifications]" value="${esc(item.specifications || '')}">
                    <input type="hidden" name="items[${index}][quantity]" value="${item.quantity}">
                    <input type="hidden" name="items[${index}][unit_price]" value="${item.unit_price}">
                    <input type="hidden" name="items[${index}][total_price]" value="${item.total_price}">
                    <input type="hidden" name="items[${index}][sl_no]" value="${item.sl_no}">
                `);
                
                // Add machine features to hidden inputs
                if (item.type === 'machine' && item.features && item.features.length > 0) {
                    item.features.forEach((feature, featureIndex) => {
                        $('#quotationForm').append(`
                            <input type="hidden" name="items[${index}][features][${featureIndex}][name]" value="${esc(feature.name)}">
                            <input type="hidden" name="items[${index}][features][${featureIndex}][price]" value="${feature.price}">
                            <input type="hidden" name="items[${index}][features][${featureIndex}][quantity]" value="${feature.quantity}">
                        `);
                    });
                }
            });
        }
        calculateTotals();
    }

    function calculateTotals() {
        let subtotal = 0;
        
        quotationItems.forEach(item => {
            // Add the item price
            subtotal += (item.total_price || 0);
            
            // Add feature prices for machine items
            if (item.type === 'machine' && item.features && item.features.length > 0) {
                item.features.forEach(feature => {
                    subtotal += (feature.price * feature.quantity) || 0;
                });
            }
        });
        
        $('#total_amount').val(subtotal.toFixed(2));
        quotationCalcDiscount();
    }

    window.quotationCalcDiscount = function() {
        const totalAmount = parseFloat($('#total_amount').val()) || 0;
        const percentage = parseFloat($('#discount_percentage').val()) || 0;
        const discountAmount = (totalAmount * percentage) / 100;
        $('#discount_amount').val(discountAmount.toFixed(2));
        calculateFinalTotal();
    }
    
    window.quotationCalcDiscountPct = function() {
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

    $('#discount_percentage').on('input', quotationCalcDiscount);
    $('#discount_amount').on('input', quotationCalcDiscountPct);

    // ---------- SEARCH FUNCTIONALITY ----------
    
    function searchAndLoadQuotation(searchTerm) {
        if (!searchTerm) return;
        
        // Show loading message
        $('#quotationSearch').prop('disabled', true);
        $('#searchBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Searching...');
        
        // First, search for quotations that match the term
        $.ajax({
            url: '../ajax/unified_search.php',
            type: 'GET',
            data: {
                term: searchTerm,
                type: 'AUTOCOMPLETE_QUOTATIONS'
            },
            dataType: 'json',
            success: function(results) {
                if (results && results.length > 0) {
                    // Found matching quotations, load the first one
                    const firstMatch = results[0];
                    if (firstMatch.id) {
                        loadQuotationById(firstMatch.id);
                    }
                } else {
                    // No exact matches found, try to search in the table
                    window.location.href = 'quotations.php?search=' + encodeURIComponent(searchTerm);
                }
            },
            error: function() {
                // Fallback to table search
                window.location.href = 'quotations.php?search=' + encodeURIComponent(searchTerm);
            },
            complete: function() {
                // Reset search button
                $('#quotationSearch').prop('disabled', false);
                $('#searchBtn').prop('disabled', false).html('<i class="bi bi-search"></i> Search');
            }
        });
    }
    
    function loadQuotationById(quotationId) {
        $.ajax({
            url: '../ajax/get_quotation_details.php',
            type: 'GET',
            data: { id: quotationId },
            dataType: 'json',
            success: function(data) {
                if(data.success) {
                    $('#quotationId').val(data.quotation.id);
                    $('#quotation_number').val(data.quotation.quotation_number);
                    $('#customer_name').val(data.quotation.customer_name);
                    $('#customer_id').val(data.quotation.customer_id);
                    $('#quotation_date').val(data.quotation.quotation_date);
                    $('#valid_until').val(data.quotation.valid_until);
                    $('#status').val(data.quotation.status);
                    $('#enquiry_ref').val(data.quotation.enquiry_ref);
                    $('#prepared_by').val(data.quotation.prepared_by);
                    $('#notes').val(data.quotation.notes);
                    $('#discount_percentage').val(data.quotation.discount_percentage);
                    $('#discount_amount').val(data.quotation.discount_amount);

                    // Load quotation items with consistent naming
                    quotationItems = (data.items || []).map(item => ({
                        type: item.item_type,
                        item_id: item.item_id,
                        name: item.item_name || (item.item_type === 'machine' ? 'Machine' : 'Spare Part'),
                        description: item.description || '',
                        specifications: item.specifications || '',
                        quantity: parseInt(item.quantity),
                        unit_price: parseFloat(item.unit_price),
                        total_price: parseFloat(item.total_price),
                        sl_no: parseInt(item.sl_no),
                        features: item.features || [] // Include features for machine items
                    }));
                    
                    renderQuotationItems();
                    
                    setFormReadOnly(true);
                    $('#saveBtn').hide();
                    $('#editBtn').show();
                    $('#deleteBtn').show();
                    $('#printBtn').show();
                    $('#emailBtn').show();
                    $('#updateBtn').hide();
                    $('#formTitle').text('Quotation Details - ' + data.quotation.quotation_number);
                    
                    $('html, body').animate({ scrollTop: $('#quotationForm').offset().top - 100 }, 500);
                } else {
                    alert(data.message);
                }
            },
            error: function() {
                alert('Error loading quotation details');
            }
        });
    }
    
    $('#searchBtn').on('click', function() {
        const searchTerm = $('#quotationSearch').val().trim();
        if (searchTerm) {
            searchAndLoadQuotation(searchTerm);
        } else {
            window.location.href = 'quotations.php';
        }
    });

    $('#clearBtn').on('click', function() {
        window.location.href = 'quotations.php';
    });

    $('#quotationSearch').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            $('#searchBtn').click();
        }
    });

    // Edit quotation from table
    $(document).on('click', '.edit-quotation', function() {
        const quotationId = $(this).data('id');
        loadQuotationById(quotationId);
    });

    $('#editBtn').on('click', function() {
        setFormReadOnly(false);
        $('#editBtn').hide();
        $('#updateBtn').show();
        $('#formAction').val('update_quotation');
    });

    $('#deleteBtn').on('click', function() {
        const quotationId = $('#quotationId').val();
        const quotationNumber = $('#quotation_number').val();
        if (quotationId && confirm('Are you sure you want to delete Quotation "' + quotationNumber + '"?')) {
            window.location.href = 'quotations.php?delete=' + quotationId;
        }
    });

    $('#resetBtn').on('click', resetForm);
    
    // Email functionality
    $(document).on('click', '.email-quotation', function() {
        openEmailQuotationModal($(this).data('id'));
    });
    
    $('#emailBtn').on('click', function() {
        const quotationId = $('#quotationId').val();
        if (quotationId) {
            openEmailQuotationModal(quotationId);
        }
    });
    
    $('#sendQuotationEmailBtn').on('click', function() {
        const btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Sending...');
        const formData = $('#emailQuotationForm').serialize();
        
        $.ajax({
            url: '../ajax/send_quotation_email.php',
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
        $('#quotationForm input, #quotationForm textarea, #quotationForm select').not('#quotationId, #formAction').prop('readonly', readonly);
        $('#quotationForm select').prop('disabled', readonly);
        $('#quotationAddMachineBtn, #quotationAddSpareBtn').prop('disabled', readonly);
        
        if(readonly) {
            $('.remove-quotation-item').hide();
            $('#editBtn, #deleteBtn, #printBtn, #emailBtn').prop('disabled', false);
        } else {
            $('.remove-quotation-item').show();
        }
    }

    function resetForm() {
        $('#quotationForm')[0].reset();
        $('#quotationId').val('');
        $('#customer_id').val('');
        $('#formAction').val('create_quotation');
        quotationItems = [];
        renderQuotationItems();
        setFormReadOnly(false);
        $('#saveBtn').show();
        $('#editBtn, #updateBtn, #deleteBtn, #printBtn, #emailBtn').hide();
        $('#formTitle').text('Create Quotation');
        
        // Set default dates
        $('#quotation_date').val(new Date().toISOString().split('T')[0]);
        const validDate = new Date();
        validDate.setDate(validDate.getDate() + 30);
        $('#valid_until').val(validDate.toISOString().split('T')[0]);
    }

    // Initial render
    renderQuotationItems();

    // Autocomplete for main quotation search
    $('#quotationSearch').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: '../ajax/unified_search.php',
                type: 'GET',
                data: {
                    term: request.term,
                    type: 'AUTOCOMPLETE_QUOTATIONS'
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
            // Load the selected quotation using the same function as View/Edit
            if (ui.item.id) {
                loadQuotationById(ui.item.id);
            }
            return false;
        }
    });
});