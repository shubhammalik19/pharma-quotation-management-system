<?php
include '../header.php';
checkLogin();
include '../menu.php';

$message = '';

// Handle form submission
if ($_POST) {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $quotation_number = sanitizeInput($_POST['quotation_number']);
        $customer_id = (int)$_POST['customer_id'];
        $quotation_date = $_POST['quotation_date'];
        $valid_until = $_POST['valid_until'];
        $total_amount = (float)$_POST['total_amount'];
        $discount_percentage = (float)$_POST['discount_percentage'];
        $discount_amount = (float)$_POST['discount_amount'];
        $status = sanitizeInput($_POST['status']);
        
        if (!empty($quotation_number) && $customer_id > 0) {
            // Check for duplicate quotation number
            $check_sql = "SELECT id FROM quotations WHERE quotation_number = '" . $conn->real_escape_string($quotation_number) . "'";
            $check_result = $conn->query($check_sql);
            
            if ($check_result && $check_result->num_rows > 0) {
                $message = showError("Error: Quotation number '$quotation_number' already exists! Please use a different quotation number.");
            } else {
                // Calculate final amount after discount
                $final_amount = $total_amount - $discount_amount;
                
                $sql = "INSERT INTO quotations (quotation_number, customer_id, quotation_date, valid_until, total_amount, discount_percentage, discount_amount, grand_total, status) 
                        VALUES ('"
                    . $conn->real_escape_string($quotation_number) . "', "
                    . intval($customer_id) . ", '"
                    . $quotation_date . "', '"
                    . $valid_until . "', "
                    . floatval($total_amount) . ", "
                    . floatval($discount_percentage) . ", "
                    . floatval($discount_amount) . ", "
                    . floatval($final_amount) . ", '"
                    . $conn->real_escape_string($status) . "')";
                
                if ($conn->query($sql)) {
                    $message = showSuccess("Quotation created successfully!");
                    // Clear form after successful creation
                    echo "<script>setTimeout(function(){ document.getElementById('quotationForm').reset(); resetCustomerAutocomplete(); }, 1000);</script>";
                } else {
                    $message = showError("Error: " . $conn->error);
                }
            }
        } else {
            $message = showError("Quotation number and customer are required!");
        }
    }
}

// Get customers for dropdown
// $customers = $conn->query("SELECT id, company_name FROM customers WHERE entity_type IN ('customer', 'both') ORDER BY company_name");
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-plus-circle"></i> Create New Quotation</h2>
            <hr>
        </div>
    </div>
    
    <?php echo $message; ?>
    
    <div class="row">
        <!-- Back Button -->
        <div class="col-12 mb-3">
            <a href="quotations.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Quotations
            </a>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <!-- Quotation Form -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-file-text"></i> New Quotation Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="quotationForm">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="quotation_number" class="form-label">Quotation Number *</label>
                                    <input type="text" class="form-control" id="quotation_number" name="quotation_number" 
                                           required autofocus placeholder="e.g., Q-2025-001">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_name" class="form-label">Customer *</label>
                                    <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                           required placeholder="Start typing customer name..." autocomplete="off">
                                    <input type="hidden" id="customer_id" name="customer_id" required>
                                    <div class="form-text">Type customer name to search from registered customers</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="quotation_date" class="form-label">Quotation Date *</label>
                                    <input type="date" class="form-control" id="quotation_date" name="quotation_date" 
                                           required value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="valid_until" class="form-label">Valid Until *</label>
                                    <input type="date" class="form-control" id="valid_until" name="valid_until" 
                                           required value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="total_amount" class="form-label">Total Amount (₹) *</label>
                                    <input type="number" class="form-control" id="total_amount" name="total_amount" 
                                           step="0.01" min="0" required placeholder="0.00" onchange="calculateFinalTotal()">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="pending" selected>Pending</option>
                                        <option value="approved">Approved</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Discount Section -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="bi bi-percent"></i> Discount & Total</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Subtotal -->
                                        <div class="row mb-2">
                                            <div class="col-6">
                                                <strong>Subtotal:</strong>
                                            </div>
                                            <div class="col-6 text-end">
                                                <strong>₹<span id="subtotalAmount">0.00</span></strong>
                                            </div>
                                        </div>
                                        
                                        <!-- Discount Fields -->
                                        <hr class="my-2">
                                        <div class="row mb-2">
                                            <div class="col-md-4">
                                                <label class="form-label small">Discount %:</label>
                                                <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" 
                                                       min="0" max="100" step="0.01" value="0" onchange="calculateDiscount()">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">Discount Amount (₹):</label>
                                                <input type="number" class="form-control" id="discount_amount" name="discount_amount" 
                                                       min="0" step="0.01" value="0" onchange="calculateDiscountPercentage()">
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <label class="form-label small">&nbsp;</label>
                                                <div class="text-muted small mt-2">
                                                    <i class="bi bi-info-circle"></i> Enter % or amount
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Final Total -->
                                        <hr class="my-2">
                                        <div class="row">
                                            <div class="col-6">
                                                <strong class="text-primary">Final Total:</strong>
                                            </div>
                                            <div class="col-6 text-end">
                                                <strong class="text-primary fs-5">₹<span id="finalTotal">0.00</span></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="bi bi-check-circle"></i> Create Quotation
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-lg" id="resetBtn">
                                        <i class="bi bi-arrow-clockwise"></i> Reset Form
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../js/common.js"></script>
<script>
$(document).ready(function() {
    // Auto-generate quotation number
    function generateQuotationNumber() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const time = String(now.getHours()).padStart(2, '0') + String(now.getMinutes()).padStart(2, '0');
        return `Q-${year}${month}${day}-${time}`;
    }
    
    // Set auto-generated quotation number if field is empty
    if ($('#quotation_number').val() === '') {
        $('#quotation_number').val(generateQuotationNumber());
    }
    
    // Initialize customer autocomplete
    initAutocomplete('#customer_name', 'AUTOCOMPLETE_CUSTOMERS', 2, function(event, ui) {
        if (ui.item && ui.item.data) {
            $('#customer_id').val(ui.item.data.id);
            $('#customer_name').val(ui.item.data.company_name);
        }
        return false;
    });
    
    // Reset customer autocomplete
    window.resetCustomerAutocomplete = function() {
        $('#customer_name').val('');
        $('#customer_id').val('');
    };
    
    // Discount calculation functions
    window.calculateDiscount = function() {
        const total = parseFloat($('#total_amount').val()) || 0;
        const percentage = parseFloat($('#discount_percentage').val()) || 0;
        const discountAmount = (total * percentage) / 100;
        
        $('#discount_amount').val(discountAmount.toFixed(2));
        calculateFinalTotal();
    };
    
    window.calculateDiscountPercentage = function() {
        const total = parseFloat($('#total_amount').val()) || 0;
        const discountAmount = parseFloat($('#discount_amount').val()) || 0;
        
        if (total > 0) {
            const percentage = (discountAmount / total) * 100;
            $('#discount_percentage').val(percentage.toFixed(2));
        }
        calculateFinalTotal();
    };
    
    window.calculateFinalTotal = function() {
        const total = parseFloat($('#total_amount').val()) || 0;
        const discountAmount = parseFloat($('#discount_amount').val()) || 0;
        const finalTotal = total - discountAmount;
        
        $('#subtotalAmount').text(total.toFixed(2));
        $('#finalTotal').text(finalTotal.toFixed(2));
    };
    
    // Reset button functionality
    $('#resetBtn').on('click', function() {
        $('#quotationForm')[0].reset();
        
        // Reset to default values
        $('#quotation_date').val('<?php echo date("Y-m-d"); ?>');
        $('#valid_until').val('<?php echo date("Y-m-d", strtotime("+30 days")); ?>');
        $('#quotation_number').val(generateQuotationNumber());
        $('#status').val('pending');
        $('#discount_percentage').val('0');
        $('#discount_amount').val('0');
        
        // Reset autocomplete
        resetCustomerAutocomplete();
        calculateFinalTotal();
        
        // Focus on first field
        $('#quotation_number').focus();
    });
    
    // Form validation
    $('#quotationForm').on('submit', function(e) {
        const quotationNumber = $('#quotation_number').val().trim();
        const customerId = $('#customer_id').val();
        const totalAmount = parseFloat($('#total_amount').val()) || 0;
        
        if (!quotationNumber) {
            e.preventDefault();
            alert('Quotation number is required!');
            $('#quotation_number').focus();
            return false;
        }
        
        if (!customerId) {
            e.preventDefault();
            alert('Please select a customer from the suggestions!');
            $('#customer_name').focus();
            return false;
        }
        
        if (totalAmount <= 0) {
            e.preventDefault();
            alert('Total amount must be greater than 0!');
            $('#total_amount').focus();
            return false;
        }
        
        return true;
    });
    
    // Calculate total when amount changes
    $('#total_amount').on('input change', function() {
        calculateFinalTotal();
    });
    
    // Initial calculation
    calculateFinalTotal();
});
</script>

<?php include '../footer.php'; ?>
