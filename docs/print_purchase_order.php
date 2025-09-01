<?php


include '../common/conn.php';
include '../common/functions.php';

$po_id = intval($_GET['id'] ?? 0);

if ($po_id <= 0) {
    die('Invalid purchase order ID');
}

// Get purchase order details
$po_sql = "SELECT po.*, c.company_name, c.address, c.gst_no, c.phone, c.email,
           u.full_name as created_by_name
           FROM purchase_orders po 
           LEFT JOIN customers c ON po.vendor_id = c.id 
           LEFT JOIN users u ON po.created_by = u.id
           WHERE po.id = $po_id";

$po_result = $conn->query($po_sql);

if (!$po_result || $po_result->num_rows === 0) {
    die('Purchase order not found');
}

$po = $po_result->fetch_assoc();

// Get purchase order items
$items_sql = "SELECT * FROM purchase_order_items WHERE po_id = $po_id ORDER BY id";
$items_result = $conn->query($items_sql);

// Get company and bank details from common functions
$company = getCompanyDetails();
$bank = getBankDetails();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order - <?php echo htmlspecialchars($po['po_number']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }
        
        .print-container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            border-bottom: 3px solid #2c5aa0;
            padding-bottom: 20px;
        }
        
        .company-info {
            flex: 1;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c5aa0;
            margin-bottom: 5px;
        }
        
        .company-details {
            font-size: 11px;
            color: #666;
            line-height: 1.5;
        }
        
        .po-title {
            text-align: right;
            flex: 0 0 auto;
        }
        
        .po-title h1 {
            font-size: 28px;
            color: #2c5aa0;
            margin-bottom: 5px;
        }
        
        .po-number {
            font-size: 14px;
            color: #666;
        }
        
        .po-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .vendor-info, .po-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #2c5aa0;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #2c5aa0;
            margin-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            width: 100px;
            color: #555;
        }
        
        .info-value {
            flex: 1;
            color: #333;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .items-table th {
            background: #2c5aa0;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #2c5aa0;
        }
        
        .items-table td {
            padding: 10px 8px;
            border: 1px solid #dee2e6;
            vertical-align: top;
        }
        
        .items-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .items-table tbody tr:hover {
            background: #e9ecef;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .totals-table {
            width: 300px;
            border-collapse: collapse;
        }
        
        .totals-table td {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
        }
        
        .totals-table .total-label {
            background: #f8f9fa;
            font-weight: bold;
            text-align: right;
            width: 60%;
        }
        
        .totals-table .total-amount {
            text-align: right;
            width: 40%;
        }
        
        .grand-total {
            background: #2c5aa0 !important;
            color: white !important;
            font-weight: bold;
            font-size: 14px;
        }
        
        .notes-section {
            margin-top: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #2c5aa0;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #dee2e6;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .signature-section {
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 5px;
            font-weight: bold;
        }
        
        .print-info {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        
        @media print {
            .print-container {
                margin: 0;
                padding: 0;
                max-width: none;
            }
            
            .print-info {
                display: none;
            }
            
            body {
                font-size: 11px;
            }
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .badge-warning { background: #ffc107; color: #212529; }
        .badge-success { background: #28a745; color: white; }
        .badge-info { background: #17a2b8; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-secondary { background: #6c757d; color: white; }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <div class="company-name">XTECHNOCRAT INDIA PRIVATE LIMITED</div>
                <div class="company-details">
                    PLOT NO. 9, SECTOR 22, IT PARK, PANCHKULA<br>
                    Phone: +91 9560239666 | Email: info@xtechnocrat.com<br>
                    GST: 06AAACX3387L1ZW | State: 06 - Haryana
                </div>
            </div>
            <div class="po-title">
                <h1>PURCHASE ORDER</h1>
                <div class="po-number"><?php echo htmlspecialchars($po['po_number']); ?></div>
            </div>
        </div>

        <!-- PO and Vendor Details -->
        <div class="po-details">
            <div class="vendor-info">
                <div class="section-title">Vendor Information</div>
                <div class="info-row">
                    <span class="info-label">Vendor:</span>
                    <span class="info-value"><?php echo htmlspecialchars($po['vendor_name']); ?></span>
                </div>
                <?php if (!empty($po['address'])): ?>
                <div class="info-row">
                    <span class="info-label">Address:</span>
                    <span class="info-value"><?php echo nl2br(htmlspecialchars($po['address'])); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($po['gst_no'])): ?>
                <div class="info-row">
                    <span class="info-label">GSTIN:</span>
                    <span class="info-value"><?php echo htmlspecialchars($po['gst_no']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($po['phone'])): ?>
                <div class="info-row">
                    <span class="info-label">Contact:</span>
                    <span class="info-value"><?php echo htmlspecialchars($po['phone']); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <div class="po-info">
                <div class="section-title">Purchase Order Details</div>
                <div class="info-row">
                    <span class="info-label">PO Date:</span>
                    <span class="info-value"><?php echo date('d-m-Y', strtotime($po['po_date'])); ?></span>
                </div>
                <?php if (!empty($po['due_date'])): ?>
                <div class="info-row">
                    <span class="info-label">Due Date:</span>
                    <span class="info-value"><?php echo date('d-m-Y', strtotime($po['due_date'])); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($po['sales_order_number'])): ?>
                <div class="info-row">
                    <span class="info-label">Sales Order:</span>
                    <span class="info-value"><?php echo htmlspecialchars($po['sales_order_number']); ?></span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span class="badge badge-<?php 
                            echo match($po['status']) {
                                'draft' => 'secondary',
                                'pending' => 'warning',
                                'approved' => 'info',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'secondary'
                            };
                        ?>">
                            <?php echo ucfirst(htmlspecialchars($po['status'])); ?>
                        </span>
                    </span>
                </div>
                <?php if (!empty($po['created_by_name'])): ?>
                <div class="info-row">
                    <span class="info-label">Created By:</span>
                    <span class="info-value"><?php echo htmlspecialchars($po['created_by_name']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Items Table -->
        <?php if ($items_result && $items_result->num_rows > 0): ?>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%">Sl#</th>
                    <th style="width: 25%">Item Name</th>
                    <th style="width: 20%">Description</th>
                    <th style="width: 8%">HSN</th>
                    <th style="width: 8%">Qty</th>
                    <th style="width: 8%">Unit</th>
                    <th style="width: 10%">Rate</th>
                    <th style="width: 8%">GST%</th>
                    <th style="width: 12%">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $subtotal = 0;
                $sl_no = 1;
                while ($item = $items_result->fetch_assoc()): 
                    $subtotal += $item['total_price'];
                ?>
                <tr>
                    <td class="text-center"><?php echo $sl_no++; ?></td>
                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                    <td><?php echo htmlspecialchars($item['description'] ?? ''); ?></td>
                    <td class="text-center">-</td>
                    <td class="text-center"><?php echo number_format($item['quantity']); ?></td>
                    <td class="text-center">Nos</td>
                    <td class="text-right">₹<?php echo number_format($item['unit_price'], 2); ?></td>
                    <td class="text-center">18%</td>
                    <td class="text-right">₹<?php echo number_format($item['total_price'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="total-label">Subtotal:</td>
                    <td class="total-amount">₹<?php echo number_format($subtotal, 2); ?></td>
                </tr>
                <?php if ($po['discount_percentage'] > 0 || $po['discount_amount'] > 0): ?>
                <tr>
                    <td class="total-label">
                        Discount <?php echo $po['discount_percentage'] > 0 ? '(' . $po['discount_percentage'] . '%)' : ''; ?>:
                    </td>
                    <td class="total-amount">-₹<?php echo number_format($po['discount_amount'], 2); ?></td>
                </tr>
                <?php endif; ?>
                <tr class="grand-total">
                    <td class="total-label">Total Amount:</td>
                    <td class="total-amount">₹<?php echo number_format($po['final_total'], 2); ?></td>
                </tr>
            </table>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 40px; color: #666;">
            <i>No items found for this purchase order.</i>
        </div>
        <?php endif; ?>

        <!-- Notes -->
        <?php if (!empty($po['notes'])): ?>
        <div class="notes-section">
            <div class="section-title">Notes</div>
            <p><?php echo nl2br(htmlspecialchars($po['notes'])); ?></p>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <div class="signature-section">
                <div class="signature-line">Authorized Signature</div>
            </div>
            <div class="signature-section">
                <div class="signature-line">Vendor Signature</div>
            </div>
        </div>

        <div class="print-info">
            Generated on <?php echo date('d-m-Y H:i:s'); ?> | Purchase Order #<?php echo htmlspecialchars($po['po_number']); ?>
        </div>
    </div>

    <script>
        // Auto print when opened
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
