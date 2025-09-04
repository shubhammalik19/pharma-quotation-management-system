<?php
/**
 * Print Purchase Invoice Document
 * Follows the consistent pattern used by other print documents in the system
 */

require_once '../common/conn.php';
require_once '../common/functions.php';
require_once '../common/print_common.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Get purchase invoice ID from URL
$id = (int)($_GET['id'] ?? 0);
$pdf_mode = (bool)($_GET['pdf'] ?? false);

if (!$id) {
    die('Purchase Invoice ID is required');
}

// Fetch purchase invoice details with vendor and company info
$sql = "SELECT pi.*, 
               cust.company_name as vendor_name, cust.email as vendor_email, 
               cust.phone as vendor_phone, cust.address as vendor_address, 
               cust.gst_no as vendor_gstin, cust.city as vendor_city,
               cust.state as vendor_state, cust.pincode as vendor_pincode,
               comp.company_name, comp.corporate_office as company_address, 
               comp.contact as company_phone, comp.email as company_email, 
               comp.gst as company_gstin, comp.cin, comp.manufacturing_unit, 
               comp.tagline
        FROM purchase_invoices pi
        LEFT JOIN customers cust ON pi.vendor_id = cust.id AND cust.entity_type IN ('vendor', 'both')
        LEFT JOIN company_info comp ON comp.id = 1
        WHERE pi.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$purchase_invoice = $result->fetch_assoc();

if (!$purchase_invoice) {
    die('Purchase Invoice not found');
}

// Fetch purchase invoice items
$items_sql = "SELECT pii.*, 
              m.name as machine_name,
              CASE 
                WHEN pii.item_type = 'machine' THEN m.name
                WHEN pii.item_type = 'spare' THEN s.part_name
                ELSE pii.item_name
              END as display_item_name,
              CASE 
                WHEN pii.item_type = 'spare' THEN s.part_code
                ELSE pii.hsn_code
              END as item_hsn_code,
              machine_link.name as linked_machine_name
              FROM purchase_invoice_items pii
              LEFT JOIN machines m ON pii.item_type = 'machine' AND pii.item_id = m.id
              LEFT JOIN spares s ON pii.item_type = 'spare' AND pii.item_id = s.id
              LEFT JOIN machines machine_link ON pii.machine_id = machine_link.id
              WHERE pii.pi_id = ?
              ORDER BY pii.machine_id, pii.id";

$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$items = $items_result->fetch_all(MYSQLI_ASSOC);

// Log document access using common function
logDocumentActivity('purchase_invoice', "Accessed Purchase Invoice: {$id}", $id);

// Prepare company data for common functions
$company = [
    'name' => $purchase_invoice['company_name'] ?? 'Company Name',
    'tagline' => $purchase_invoice['tagline'] ?? 'Professional Business Solutions',
    'address' => $purchase_invoice['company_address'] ?? '',
    'corporate_office' => $purchase_invoice['company_address'] ?? '',
    'manufacturing_unit' => $purchase_invoice['manufacturing_unit'] ?? '',
    'phone' => $purchase_invoice['company_phone'] ?? '',
    'contact' => $purchase_invoice['company_phone'] ?? '',
    'email' => $purchase_invoice['company_email'] ?? '',
    'cin' => $purchase_invoice['cin'] ?? '',
    'gst' => $purchase_invoice['company_gstin'] ?? '',
    'gstin' => $purchase_invoice['company_gstin'] ?? ''
];

// If PDF mode, generate and serve PDF
if ($pdf_mode) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="purchase_invoice_' . $purchase_invoice['pi_number'] . '.pdf"');
    
    // Generate HTML content
    ob_start();
    include 'print_purchase_invoice_content.php';
    $html_content = ob_get_clean();
    
    // Generate PDF using wkhtmltopdf (following existing pattern)
    $temp_html = tempnam(sys_get_temp_dir(), 'purchase_invoice_');
    file_put_contents($temp_html, $html_content);
    
    $pdf_path = tempnam(sys_get_temp_dir(), 'purchase_invoice_pdf_');
    $command = "wkhtmltopdf --page-size A4 --margin-top 12mm --margin-right 12mm --margin-bottom 12mm --margin-left 12mm '$temp_html' '$pdf_path'";
    exec($command);
    
    if (file_exists($pdf_path)) {
        readfile($pdf_path);
        unlink($pdf_path);
    }
    unlink($temp_html);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Invoice - <?= e($purchase_invoice['pi_number']) ?></title>
    <style>
        <?= getPrintStyles() ?>
        
        /* Enhanced styles for machine hierarchy */
        .machine-header {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-left: 5px solid #0f6abf;
            font-weight: bold;
            color: #0f6abf;
        }
        
        .machine-item {
            background: #f8fffe;
            border-left: 3px solid #e0f2f1;
        }
        
        .spare-indicator {
            color: #0f6abf;
            font-style: italic;
            font-size: 11px;
        }
        
        .independent-header {
            background: linear-gradient(135deg, #fff3e0 0%, #ffcc02 20%);
            border-left: 5px solid #ff9800;
            font-weight: bold;
            color: #ff9800;
        }
        
        .hierarchy-icon {
            color: #666;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php if (!$pdf_mode): ?>
            <?= getPrintNavigation('../sales/purchase_invoices.php', $id) ?>
        <?php endif; ?>

        <?= getPrintHeader($company, 'PURCHASE INVOICE', $purchase_invoice['pi_number'], $purchase_invoice['status']) ?>

        <!-- Vendor Information -->
        <h2>Vendor Information</h2>
        <table class="kv">
            <tr><td>Vendor Name</td><td><?= e($purchase_invoice['vendor_name']) ?></td></tr>
            <tr><td>Address</td><td><?= e($purchase_invoice['vendor_address']) ?></td></tr>
            <tr><td>Phone</td><td><?= e($purchase_invoice['vendor_phone']) ?></td></tr>
            <tr><td>Email</td><td><?= e($purchase_invoice['vendor_email']) ?></td></tr>
            <tr><td>GSTIN</td><td><?= e($purchase_invoice['vendor_gstin']) ?></td></tr>
        </table>

        <!-- Invoice Details -->
        <h2>Invoice Details</h2>
        <table class="kv">
            <tr><td>Invoice Number</td><td><?= e($purchase_invoice['pi_number']) ?></td></tr>
            <tr><td>Invoice Date</td><td><?= e(date('d-m-Y', strtotime($purchase_invoice['pi_date']))) ?></td></tr>
            <tr><td>Due Date</td><td><?= e(date('d-m-Y', strtotime($purchase_invoice['due_date']))) ?></td></tr>
            <tr><td>Payment Terms</td><td><?= e($purchase_invoice['payment_terms'] ?? 'As per agreement') ?></td></tr>
        </table>

        <!-- Items Table -->
        <h2>Items Details</h2>
        <table class="items">
            <thead>
                <tr>
                    <th style="width:40px">S.No</th>
                    <th>Category/Machine</th>
                    <th>Item Description</th>
                    <th style="width:80px">HSN Code</th>
                    <th style="width:60px">Qty</th>
                    <th style="width:100px">Unit Price</th>
                    <th style="width:100px">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sno = 1;
                $subtotal = 0;
                $current_machine = null;
                $machine_items = [];
                $independent_items = [];
                
                // Group items by machine
                foreach ($items as $item) {
                    $display_machine_name = !empty($item['linked_machine_name']) ? $item['linked_machine_name'] : $item['machine_name'];
                    
                    if (!empty($display_machine_name)) {
                        if (!isset($machine_items[$display_machine_name])) {
                            $machine_items[$display_machine_name] = [];
                        }
                        $machine_items[$display_machine_name][] = $item;
                    } else {
                        $independent_items[] = $item;
                    }
                }
                
                // Display machine groups first
                foreach ($machine_items as $machine_name => $machine_item_list):
                ?>
                    <!-- Machine Header -->
                    <tr class="machine-header">
                        <td colspan="7" style="padding:12px;font-size:14px;">
                            <span class="hierarchy-icon">🔧</span><strong>MACHINE: <?= e($machine_name) ?></strong>
                        </td>
                    </tr>
                    
                    <?php foreach ($machine_item_list as $item): 
                        $item_total = $item['quantity'] * $item['unit_price'];
                        $subtotal += $item_total;
                    ?>
                    <tr class="machine-item">
                        <td style="text-align:center;padding-left:20px;"><?= $sno++ ?></td>
                        <td style="padding-left:30px;color:#666;">
                            <span class="hierarchy-icon">└─</span><?= e($machine_name) ?>
                        </td>
                        <td style="padding-left:20px;">
                            <strong><?= e($item['display_item_name'] ?: $item['item_name']) ?></strong>
                            <?php if ($item['item_type'] == 'spare'): ?>
                                <br><span class="spare-indicator">└─ Spare Part (Linked to Machine)</span>
                            <?php elseif ($item['item_type'] == 'machine'): ?>
                                <br><span class="spare-indicator">└─ Main Machine</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center"><?= e($item['item_hsn_code'] ?: $item['hsn_code']) ?></td>
                        <td class="num"><?= number_format($item['quantity'], 2) ?></td>
                        <td class="num"><?= fmt_money($item['unit_price']) ?></td>
                        <td class="num"><?= fmt_money($item_total) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                <?php endforeach; ?>
                
                <?php if (!empty($independent_items)): ?>
                    <!-- Independent Items Header -->
                    <tr class="independent-header">
                        <td colspan="7" style="padding:12px;font-size:14px;">
                            <span class="hierarchy-icon">📦</span><strong>INDEPENDENT ITEMS</strong>
                        </td>
                    </tr>
                    
                    <?php foreach ($independent_items as $item): 
                        $item_total = $item['quantity'] * $item['unit_price'];
                        $subtotal += $item_total;
                    ?>
                    <tr>
                        <td style="text-align:center"><?= $sno++ ?></td>
                        <td><?= e($item['item_type'] == 'machine' ? 'Machine' : 'Independent Item') ?></td>
                        <td><strong><?= e($item['display_item_name'] ?: $item['item_name']) ?></strong></td>
                        <td style="text-align:center"><?= e($item['item_hsn_code'] ?: $item['hsn_code']) ?></td>
                        <td class="num"><?= number_format($item['quantity'], 2) ?></td>
                        <td class="num"><?= fmt_money($item['unit_price']) ?></td>
                        <td class="num"><?= fmt_money($item_total) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- Subtotal -->
                <tr style="border-top:2px solid #0f6abf;font-weight:bold;">
                    <td colspan="6" style="text-align:right;padding:8px;">Subtotal:</td>
                    <td class="num" style="padding:8px;"><?= fmt_money($subtotal) ?></td>
                </tr>
                
                <!-- Tax -->
                <?php if (!empty($purchase_invoice['discount_percentage']) && $purchase_invoice['discount_percentage'] > 0): ?>
                <tr>
                    <td colspan="6" style="text-align:right;padding:4px 8px;">
                        Discount @ <?= number_format($purchase_invoice['discount_percentage'], 2) ?>%:
                    </td>
                    <td class="num" style="padding:4px 8px;">- <?= fmt_money($purchase_invoice['discount_amount']) ?></td>
                </tr>
                <?php endif; ?>
                
                <!-- Grand Total -->
                <tr style="background:#eef6ff;font-weight:bold;font-size:14px;">
                    <td colspan="6" style="text-align:right;padding:8px;">Grand Total:</td>
                    <td class="num" style="padding:8px;"><?= fmt_money($purchase_invoice['final_total']) ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Amount in Words -->
        <div style="margin:8px 0;padding:8px;background:#f8f9fa;border-left:4px solid #0f6abf;">
            <strong>Amount in Words:</strong> <?= e(numberToWords($purchase_invoice['final_total'])) ?> Rupees Only
        </div>

        <?php if (!empty($purchase_invoice['notes'])): ?>
        <!-- Notes -->
        <h2>Additional Notes</h2>
        <div class="note" style="padding:8px;border:1px solid #e7e7e7;background:#f9f9f9;">
            <?= nl2br(e($purchase_invoice['notes'])) ?>
        </div>
        <?php endif; ?>

        <?= getCompanyInfoSection($company) ?>
        
        <?= getTermsAndConditions('general') ?>

        <?= getPrintFooter($company) ?>
        
        <!-- Document Generation Info -->
        <div style="text-align:center;margin-top:8px;font-size:11px;color:#999;">
            Document generated on <?= date('d-m-Y H:i:s') ?> | User: <?= e($_SESSION['username'] ?? $_SESSION['user_id']) ?>
        </div>
    </div>
</body>
</html>
