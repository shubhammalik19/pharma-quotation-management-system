<?php
include '../header.php';
checkLogin();
include '../menu.php';

$message = '';
$quotation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$quotation_id) {
    header("Location: quotations.php");
    exit();
}

// Get quotation details with customer info
$sql = "SELECT q.*, c.name as customer_name, c.phone, c.email 
        FROM quotations q 
        LEFT JOIN customers c ON q.customer_id = c.id 
        WHERE q.id = $quotation_id";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    header("Location: quotations.php");
    exit();
}

$quotation = $result->fetch_assoc();

// Handle form submission
if ($_POST) {
    $send_method = $_POST['send_method'];
    $recipient = sanitizeInput($_POST['recipient']);
    $message_text = sanitizeInput($_POST['message']);
    
    if ($send_method && $recipient) {
        // Update quotation status to 'sent'
        $update_sql = "UPDATE quotations SET status = 'sent' WHERE id = $quotation_id";
        $conn->query($update_sql);
        
        // In a real application, you would integrate with email service or WhatsApp API
        // For this demo, we'll just show a success message
        $success_msg = "Quotation sent successfully via " . ucfirst($send_method) . " to: " . $recipient;
        $message = showSuccess($success_msg);
        
        // Redirect after 3 seconds
        echo "<script>
            setTimeout(function() {
                window.location.href = 'view_quotation.php?id=$quotation_id';
            }, 3000);
        </script>";
    } else {
        $message = showError("Please fill all required fields!");
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-send"></i> Send Quotation</h2>
            <hr>
        </div>
    </div>
    
    <?php echo $message; ?>
    
    <div class="row">
        <div class="col-md-8">
            <!-- Send Form -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-envelope"></i> Send Quotation: <?php echo $quotation['quote_ref']; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="send_method" class="form-label">Send Method *</label>
                                    <select class="form-select" id="send_method" name="send_method" required>
                                        <option value="">Choose Method...</option>
                                        <option value="email">Email</option>
                                        <option value="whatsapp">WhatsApp</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="recipient" class="form-label">Recipient *</label>
                                    <input type="text" class="form-control" id="recipient" name="recipient" 
                                           placeholder="Enter email or phone number" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="4" 
                                      placeholder="Enter your message (optional)">Dear <?php echo $quotation['customer_name']; ?>,

Please find attached our quotation (<?php echo $quotation['quote_ref']; ?>) for your requirements.

The quotation is valid for 30 days from the date of issue. Please let us know if you need any clarifications.

Thank you for your business.

Best regards,
Pharma Machinery Company</textarea>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-send"></i> Send Quotation
                            </button>
                            <a href="view_quotation.php?id=<?php echo $quotation_id; ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Quotation
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Customer Info -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-person"></i> Customer Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo $quotation['customer_name']; ?></p>
                    <?php if ($quotation['email']): ?>
                        <p><strong>Email:</strong> <?php echo $quotation['email']; ?></p>
                    <?php endif; ?>
                    <?php if ($quotation['phone']): ?>
                        <p><strong>Phone:</strong> <?php echo $quotation['phone']; ?></p>
                    <?php endif; ?>
                    <p><strong>Quote Ref:</strong> <?php echo $quotation['quote_ref']; ?></p>
                    <p><strong>Total Amount:</strong> <?php echo formatCurrency($quotation['total_amount']); ?></p>
                </div>
            </div>
            
            <!-- Quick Fill Buttons -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5><i class="bi bi-lightning"></i> Quick Fill</h5>
                </div>
                <div class="card-body">
                    <?php if ($quotation['email']): ?>
                        <button type="button" class="btn btn-outline-primary btn-sm mb-2" 
                                onclick="fillEmail('<?php echo $quotation['email']; ?>')">
                            <i class="bi bi-envelope"></i> Use Customer Email
                        </button><br>
                    <?php endif; ?>
                    
                    <?php if ($quotation['phone']): ?>
                        <button type="button" class="btn btn-outline-success btn-sm" 
                                onclick="fillPhone('<?php echo $quotation['phone']; ?>')">
                            <i class="bi bi-whatsapp"></i> Use Customer Phone
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Send History (Mock) -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5><i class="bi bi-clock-history"></i> Send History</h5>
                </div>
                <div class="card-body">
                    <small class="text-muted">No previous sends for this quotation.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Update recipient placeholder based on send method
    $('#send_method').change(function() {
        const method = $(this).val();
        const recipientField = $('#recipient');
        
        if (method === 'email') {
            recipientField.attr('placeholder', 'Enter email address');
            recipientField.attr('type', 'email');
        } else if (method === 'whatsapp') {
            recipientField.attr('placeholder', 'Enter phone number');
            recipientField.attr('type', 'tel');
        } else {
            recipientField.attr('placeholder', 'Enter email or phone number');
            recipientField.attr('type', 'text');
        }
    });
});

function fillEmail(email) {
    $('#send_method').val('email');
    $('#recipient').val(email).attr('type', 'email').attr('placeholder', 'Enter email address');
}

function fillPhone(phone) {
    $('#send_method').val('whatsapp');
    $('#recipient').val(phone).attr('type', 'tel').attr('placeholder', 'Enter phone number');
}
</script>

<?php include '../footer.php'; ?>
