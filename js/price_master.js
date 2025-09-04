/* price_master.js - Uniform design consistent with other pages
   Requires: jQuery, jQuery UI (autocomplete), Bootstrap (for modals)
   Endpoints used:
     - ajax/unified_search.php           (AUTOCOMPLETE_MACHINES)
     - ajax/get_price_details.php        (id) - if needed
     - ajax/get_machine_features_for_pricing.php (machine_id)
     - ajax/get_feature_prices.php       (machine_id)
*/

// ---------- SMALL HELPERS ----------
function fmtDate(d){ if(!d) return ''; const dt=new Date(d); return dt.toLocaleDateString('en-GB'); }
function esc(t){ if(!t) return ''; const m={'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}; return t.toString().replace(/[&<>"']/g, x=>m[x]); }

$(document).ready(function() {
    
    // Global variables
    let selectedMachineId = null;
    let selectedMachineName = '';
    
    // Function to reload page after actions (for manual use if needed)
    window.reloadAfterAction = function(delay = 1500) {
        setTimeout(function() {
            window.location.reload();
        }, delay);
    };
    
    // Price type toggle handling
    $('input[name="price_type"]').on('change', function() {
        const priceType = $(this).val();
        
        if (priceType === 'machine') {
            $('#machine_id').prop('required', true);
            $('#spare_id').prop('required', false);
            $('#spareSelection').hide();
            $('#machineSelection').show();
            
            // Update form action and field names for machine pricing
            $('#formAction').val('create_price');
            
            // Load features if machine is already selected
            const currentMachineId = $('#machine_id').val();
            if (currentMachineId) {
                selectedMachineId = currentMachineId;
                selectedMachineName = $('#machine_id option:selected').text();
                loadMachineFeaturesForPricing(currentMachineId);
            }
            
        } else if (priceType === 'spare') {
            $('#machine_id').prop('required', false);
            $('#spare_id').prop('required', true);
            $('#spareSelection').show();
            $('#machineSelection').hide();
            
            // Update form action and field names for spare pricing
            $('#formAction').val('create_spare_price');
            
            // Clear features list when switching to spare parts
            clearFeaturesList();
        }
        
        // Clear selections without triggering resetForm
        if (priceType === 'spare') {
            $('#machine_id').val('');
        } else {
            $('#spare_id').val('');
        }
    });
    
    // Machine selection handling
    $('#machine_id').on('change', function() {
        selectedMachineId = $(this).val();
        selectedMachineName = $(this).find('option:selected').text();
        
        console.log('Machine selected:', selectedMachineId, selectedMachineName);
        console.log('Current price type:', $('input[name="price_type"]:checked').val());
        
        if (selectedMachineId) {
            // Load machine features automatically when machine is selected
            console.log('About to load features for machine:', selectedMachineId);
            loadMachineFeaturesForPricing(selectedMachineId);
        } else {
            console.log('No machine selected, clearing features');
            clearFeaturesList();
        }
    });
    
    // Also trigger on page load if machine is already selected
    $(document).ready(function() {
        const currentMachineId = $('#machine_id').val();
        if (currentMachineId) {
            selectedMachineId = currentMachineId;
            selectedMachineName = $('#machine_id option:selected').text();
            loadMachineFeaturesForPricing(currentMachineId);
        }
    });
    
    // Function to load machine features for pricing
    function loadMachineFeaturesForPricing(machineId) {
        if (!machineId) return;
        
        // Only load features for machine price type
        const priceType = $('input[name="price_type"]:checked').val();
        if (priceType !== 'machine') {
            clearFeaturesList();
            return;
        }
        
        console.log('Loading features for machine:', machineId);
        
        // Prepare AJAX data
        const ajaxData = { machine_id: machineId };
        
        // If we're editing (have price ID), include date range to get specific pricing
        const priceId = $('#priceId').val();
        if (priceId && $('#valid_from').val() && $('#valid_to').val()) {
            ajaxData.valid_from = $('#valid_from').val();
            ajaxData.valid_to = $('#valid_to').val();
        }
        
        $.ajax({
            url: 'ajax/get_machine_features_with_pricing.php',
            type: 'GET',
            data: ajaxData,
            dataType: 'json',
            success: function(data) {
                console.log('Features loaded:', data);
                if (data.success && data.features && data.features.length > 0) {
                    displayMachineFeatures(data.features);
                } else {
                    clearFeaturesList();
                    console.log('No features found or error:', data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading machine features:', error);
                clearFeaturesList();
            }
        });
    }
    
    // Function to display machine features with pricing inputs
    function displayMachineFeatures(features) {
        console.log('displayMachineFeatures called with:', features);
        
        if (!features || features.length === 0) {
            console.log('No features to display, clearing list');
            clearFeaturesList();
            return;
        }
        
        console.log('Displaying', features.length, 'features');
        
        // Show machine info
        $('#selectedMachineDisplay').text(selectedMachineName);
        $('#selectedMachineInfo').show();
        
        // Create features table with editable price inputs that integrate with main form
        let featuresHtml = '<div class="table-responsive">';
        featuresHtml += '<table class="table table-sm table-striped">';
        featuresHtml += '<thead class="table-dark">';
        featuresHtml += '<tr><th>Feature Name</th><th>Current Price (₹)</th><th>New Price (₹)</th></tr>';
        featuresHtml += '</thead><tbody>';
        
        features.forEach(function(feature, index) {
            const currentPrice = feature.feature_price > 0 ? parseFloat(feature.feature_price).toFixed(2) : '0.00';
            const featureId = feature.feature_id;
            
            featuresHtml += '<tr>';
            featuresHtml += '<td>';
            featuresHtml += '<input type="hidden" name="feature_ids[]" value="' + featureId + '">';
            featuresHtml += '<strong>' + esc(feature.feature_name) + '</strong>';
            featuresHtml += '</td>';
            featuresHtml += '<td>';
            if (feature.feature_price > 0) {
                featuresHtml += '<span class="text-success">₹' + currentPrice + '</span>';
            } else {
                featuresHtml += '<span class="text-muted">Not set</span>';
            }
            featuresHtml += '</td>';
            featuresHtml += '<td>';
            featuresHtml += '<input type="number" class="form-control form-control-sm feature-price-input" ';
            featuresHtml += 'name="feature_prices[' + featureId + ']" ';
            featuresHtml += 'value="' + currentPrice + '" ';
            featuresHtml += 'step="0.01" min="0" placeholder="Enter price">';
            featuresHtml += '</td>';
            featuresHtml += '</tr>';
        });
        
        featuresHtml += '</tbody></table></div>';
        featuresHtml += '<div class="mt-2">';
        featuresHtml += '<small class="text-muted"><i class="bi bi-info-circle"></i> Feature prices will be saved with the main form</small>';
        featuresHtml += '</div>';
        
        $('#featurePricesList').html(featuresHtml);
        
        console.log('About to show feature sections');
        
        // Show feature section
        $('#featurePricesListSection').show();
        $('#priceListSection').removeClass('col-md-12').addClass('col-md-8');
        
        console.log('Feature display complete');
    }
    
    // Function to clear features list
    function clearFeaturesList() {
        $('#featurePricesList').html('<p class="text-muted text-center py-4"><i class="bi bi-gear display-1"></i><br>Select a machine to view and edit feature prices</p>');
        $('#selectedMachineInfo').hide();
        $('#featurePricesListSection').hide();
        $('#priceListSection').removeClass('col-md-8').addClass('col-md-12');
    }
    
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

    // Edit price from table (updated to handle both machine and spare prices)
    $(document).on('click', '.edit-price', function() {
        const priceId = $(this).data('id');
        const priceType = $(this).data('type') || 'machine';
        
        // Get price data from the row
        const row = $(this).closest('tr');
        const typeText = row.find('td:nth-child(1) .badge').text();
        const itemName = row.find('td:nth-child(2) strong').text();
        const itemCode = row.find('td:nth-child(3) .badge').text();
        const priceText = row.find('td:nth-child(4) strong').text();
        const price = priceText.replace(/[₹,]/g, '');
        const validFromText = row.find('td:nth-child(5)').text();
        const validToText = row.find('td:nth-child(6)').text();
        
        // Convert dates from DD-MM-YYYY to YYYY-MM-DD for input fields
        const validFrom = convertDateFormat(validFromText);
        const validTo = convertDateFormat(validToText);
        
        // Set the correct price type
        if (priceType === 'spare') {
            $('#spare_price').prop('checked', true);
            $('#spare_price').trigger('change');
        } else {
            $('#machine_price').prop('checked', true);
            $('#machine_price').trigger('change');
        }
        
        // Fill form with basic data
        fillFormWithBasicData({
            id: priceId,
            item_name: itemName,
            item_code: itemCode,
            price: price,
            valid_from: validFrom,
            valid_to: validTo,
            price_type: priceType
        });
    });

    function fillFormWithBasicData(priceData) {
        $('#priceId').val(priceData.id);
        $('#price').val(priceData.price);
        $('#valid_from').val(priceData.valid_from);
        $('#valid_to').val(priceData.valid_to);
        
        if (priceData.price_type === 'spare') {
            // Find spare option by name
            $('#spare_id option').each(function() {
                if ($(this).text().includes(priceData.item_name)) {
                    $(this).prop('selected', true);
                    return false;
                }
            });
            $('#formAction').val('update_spare_price');
        } else {
            // Find machine option by name
            $('#machine_id option').each(function() {
                if ($(this).text().includes(priceData.item_name)) {
                    $(this).prop('selected', true);
                    // Trigger change event to load features
                    $('#machine_id').trigger('change');
                    return false;
                }
            });
            $('#formAction').val('update_price');
        }
        
        setFormReadOnly(true);
        $('#saveBtn').hide();
        $('#editBtn').show();
        $('#deleteBtn').show();
        $('#updateBtn').hide();
        $('#formTitle').text('Price Details - ' + priceData.item_name);
        
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
        
        // Update form action based on price type
        const priceType = $('input[name="price_type"]:checked').val();
        if (priceType === 'spare') {
            $('#formAction').val('update_spare_price');
        } else {
            $('#formAction').val('update_price');
            
            // Reload features for machine editing to show current feature prices
            const currentMachineId = $('#machine_id').val();
            if (currentMachineId) {
                selectedMachineId = currentMachineId;
                selectedMachineName = $('#machine_id option:selected').text();
                loadMachineFeaturesForPricing(currentMachineId);
            }
        }
    });

    $('#deleteBtn').on('click', function() {
        const priceId = $('#priceId').val();
        const priceType = $('input[name="price_type"]:checked').val();
        const itemName = priceType === 'spare' ? $('#spare_id option:selected').text() : $('#machine_id option:selected').text();
        
        if (priceId && confirm('Are you sure you want to delete this price record for "' + itemName + '"?')) {
            const deleteParam = priceType === 'spare' ? 'delete_spare' : 'delete';
            window.location.href = 'price_master.php?' + deleteParam + '=' + priceId;
        }
    });

    $('#resetBtn').on('click', resetForm);

    function setFormReadOnly(readonly) {
        $('#priceForm input').not('#priceId, #formAction').prop('readonly', readonly);
        $('#machine_id, #spare_id').prop('disabled', readonly);
        
        // Price type should always be disabled when editing (when priceId exists)
        const isEditing = $('#priceId').val() !== '';
        if (isEditing) {
            $('input[name="price_type"]').prop('disabled', true);
            $('#priceTypeLockNotice').show();
        } else {
            $('input[name="price_type"]').prop('disabled', readonly);
            $('#priceTypeLockNotice').hide();
        }
        
        if(readonly) {
            $('#editBtn, #deleteBtn').prop('disabled', false);
        }
    }

    function resetForm() {
        $('#priceForm')[0].reset();
        $('#priceId').val('');
        
        // Reset to machine price type WITHOUT triggering change event
        $('#machine_price').prop('checked', true);
        $('#formAction').val('create_price');
        $('#machineSelection').show();
        $('#spareSelection').hide();
        
        // Hide price type lock notice for new records
        $('#priceTypeLockNotice').hide();
        
        setFormReadOnly(false);
        $('#saveBtn').show();
        $('#editBtn, #updateBtn, #deleteBtn').hide();
        $('#formTitle').html('<i class="bi bi-currency-rupee"></i> Create Price Entry');
        
        // Reset feature pricing elements
        selectedMachineId = null;
        selectedMachineName = '';
        clearFeaturesList();
        
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
        const priceType = $('input[name="price_type"]:checked').val();
        const price = $('#price').val();
        const validFrom = $('#valid_from').val();
        const validTo = $('#valid_to').val();
        
        // Basic required field validation
        if (!price || !validFrom || !validTo) {
            e.preventDefault();
            alert('Price and date fields are required!');
            return false;
        }
        
        // Type-specific validation
        if (priceType === 'machine') {
            const machineId = $('#machine_id').val();
            if (!machineId) {
                e.preventDefault();
                alert('Please select a machine!');
                return false;
            }
        } else if (priceType === 'spare') {
            const spareId = $('#spare_id').val();
            if (!spareId) {
                e.preventDefault();
                alert('Please select a spare part!');
                return false;
            }
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