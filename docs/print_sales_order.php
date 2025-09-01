<?php
require '../common/conn.php';
require '../common/functions.php';

// Check if user is logged in
checkLogin();

// Get sales order ID from URL
$so_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_pdf_mode = isset($_GET['pdf']) && $_GET['pdf'] == '1';

if (!$so_id) {
    die('Invalid sales order ID');
}

// Get sales order details with customer information
$so_sql = "SELECT so.*, 
           c.company_name as customer_company_name, 
           c.contact_person as customer_contact_person, 
           c.phone as customer_phone, 
           c.email as customer_email, 
           c.address as customer_full_address, 
           c.city as customer_city, 
           c.state as customer_state, 
           c.gst_no as customer_gstin_number, 
           c.pincode as customer_pincode
           FROM sales_orders so 
           LEFT JOIN customers c ON so.customer_id = c.id 
           WHERE so.id = $so_id";
$so_result = $conn->query($so_sql);

if ($so_result->num_rows === 0) {
    die('Sales Order not found');
}

$sales_order = $so_result->fetch_assoc();

// Get sales order items
$items_sql = "SELECT soi.*, 
              CASE 
                WHEN soi.item_type = 'machine' THEN m.name 
                WHEN soi.item_type = 'spare' THEN s.part_name 
              END as item_name,
              CASE 
                WHEN soi.item_type = 'machine' THEN m.model 
                WHEN soi.item_type = 'spare' THEN s.part_code 
              END as item_code
              FROM sales_order_items soi 
              LEFT JOIN machines m ON soi.item_type = 'machine' AND soi.item_id = m.id
              LEFT JOIN spares s ON soi.item_type = 'spare' AND soi.item_id = s.id
              WHERE soi.so_id = $so_id 
              ORDER BY soi.id";
$items_result = $conn->query($items_sql);

// Get company and bank details from common functions
$company = getCompanyDetails();
$bank = getBankDetails();

// Calculate totals
$subtotal = 0;
$items_array = [];
while ($item = $items_result->fetch_assoc()) {
    $items_array[] = $item;
    $subtotal += $item['total_price'];
}

// Calculate tax (assuming 18% GST for inter-state)
$gst_rate = 18;
$gst_amount = ($subtotal * $gst_rate) / 100;
$grand_total = $subtotal + $gst_amount;

// Apply discount if any
$discount_amount = $sales_order['discount_amount'] ?: 0;
$final_total = $grand_total - $discount_amount;

// Convert amount to words function
function numberToWords($num) {
    $ones = array(
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
        'Seventeen', 'Eighteen', 'Nineteen'
    );
    $tens = array('', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety');
    
    if ($num < 20) return $ones[$num];
    if ($num < 100) return $tens[intval($num/10)] . ($num%10 ? ' ' . $ones[$num%10] : '');
    if ($num < 1000) return $ones[intval($num/100)] . ' Hundred' . ($num%100 ? ' ' . numberToWords($num%100) : '');
    if ($num < 100000) return numberToWords(intval($num/1000)) . ' Thousand' . ($num%1000 ? ' ' . numberToWords($num%1000) : '');
    if ($num < 10000000) return numberToWords(intval($num/100000)) . ' Lakh' . ($num%100000 ? ' ' . numberToWords($num%100000) : '');
    return numberToWords(intval($num/10000000)) . ' Crore' . ($num%10000000 ? ' ' . numberToWords($num%10000000) : '');
}

$amount_in_words = numberToWords($final_total) . ' Rupees only';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo htmlspecialchars($company_info['name']); ?> — Sales Order <?php echo htmlspecialchars($sales_order['so_number']); ?></title>
  <style>
    :root {
      --ink: #111827; /* slate-900 */
      --muted: #6b7280; /* gray-500 */
      --line: #e5e7eb;  /* gray-200 */
      --brand: #0ea5e9; /* sky-500 */
    }
    html, body { margin: 0; padding: 0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, "Helvetica Neue", Arial, "Apple Color Emoji", "Segoe UI Emoji"; color: var(--ink); }
    .doc { max-width: 800px; margin: 24px auto; padding: 24px; border: 1px solid var(--line); border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.04); }
    .brandbar { display:flex; align-items:flex-start; justify-content:space-between; gap: 16px; }
    .brand h1 { margin: 0 0 4px; font-size: 20px; letter-spacing: 0.3px; }
    .brand p { margin: 0; color: var(--muted); font-size: 12px; line-height: 1.35; }
    .tag { font-weight: 700; font-size: 24px; letter-spacing: 0.4px; padding: 6px 12px; border-radius: 8px; border: 2px solid var(--brand); color: var(--brand); white-space: nowrap; }

    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 16px; }
    .panel { border: 1px solid var(--line); border-radius: 10px; padding: 12px; }
    .panel h3 { margin: 0 0 8px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.8px; color: var(--muted); }
    .kv { display: grid; grid-template-columns: 150px 1fr; gap: 6px 10px; font-size: 13px; }
    .kv div.key { color: var(--muted); }

    table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 13px; }
    thead th { text-align: left; font-size: 12px; color: var(--muted); border-bottom: 2px solid var(--line); padding: 10px 8px; }
    tbody td { border-bottom: 1px solid var(--line); padding: 10px 8px; vertical-align: top; }
    tfoot td { padding: 6px 8px; }
    .num { text-align: right; white-space: nowrap; }
    .hsn, .qty, .unit { white-space: nowrap; }
    .desc { white-space: pre-line; }

    .totals { width: 100%; margin-top: 8px; border-collapse: collapse; }
    .totals td { padding: 8px; }
    .totals tr + tr td { border-top: 1px dashed var(--line); }
    .totals .label { text-align: right; color: var(--muted); }
    .totals .amount { text-align: right; font-weight: 700; }

    .note { margin-top: 10px; font-size: 12px; color: var(--muted); }
    .sign { margin-top: 28px; display:flex; justify-content:flex-end; }
    .sign .box { border-top: 1px solid var(--line); padding-top: 8px; min-width: 220px; text-align: center; font-size: 12px; color: var(--muted); }

    .page-title { margin: 18px 0 10px; font-size: 18px; }
    .hr { height: 1px; background: var(--line); margin: 14px 0; }

    .print-controls { position: fixed; top: 20px; right: 20px; z-index: 1000; display: flex; gap: 10px; }
    .print-btn { background: var(--brand); color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-size: 14px; display: flex; align-items: center; gap: 8px; text-decoration: none; }
    .print-btn:hover { background: #0284c7; }

    @media print {
      body { background: white; }
      .doc { box-shadow: none; border: none; margin: 0; max-width: 100%; }
      .print-controls { display: none !important; }
    }
  </style>
</head>
<body>

  <!-- Print Controls -->
  <?php if (!$is_pdf_mode): ?>
  <div class="print-controls">
    <button class="print-btn" onclick="window.print()">
      🖨️ Print
    </button>
    <a href="../sales/sales_orders.php" class="print-btn" style="background:#6c757d">
      ← Back to List
    </a>
  </div>
  <?php endif; ?>

  <article class="doc" id="sales-order-<?php echo htmlspecialchars($sales_order['so_number']); ?>">
    <header class="brandbar">
      <div class="brand">
        <h1><?php echo htmlspecialchars($company_info['name']); ?></h1>
        <p><?php echo htmlspecialchars($company_info['address']); ?><br>
           GSTIN: <?php echo htmlspecialchars($company_info['gstin']); ?> &nbsp;•&nbsp; State: <?php echo htmlspecialchars($company_info['state']); ?><br>
           <?php echo htmlspecialchars($company_info['phone']); ?> &nbsp;•&nbsp; <?php echo htmlspecialchars($company_info['email']); ?></p>
      </div>
      <div class="tag">Sales Order</div>
    </header>

    <div class="grid">
      <section class="panel">
        <h3>Bill To</h3>
        <div class="kv">
          <div class="key">Name</div><div><?php echo htmlspecialchars($sales_order['customer_name']); ?></div>
          <?php if ($sales_order['customer_address']): ?>
          <div class="key">Address</div><div><?php echo htmlspecialchars($sales_order['customer_address']); ?></div>
          <?php elseif ($sales_order['customer_full_address']): ?>
          <div class="key">Address</div><div><?php echo htmlspecialchars($sales_order['customer_full_address']); ?><?php if ($sales_order['customer_city']) echo ', ' . htmlspecialchars($sales_order['customer_city']); ?><?php if ($sales_order['customer_state']) echo ', ' . htmlspecialchars($sales_order['customer_state']); ?><?php if ($sales_order['customer_pincode']) echo ' - ' . htmlspecialchars($sales_order['customer_pincode']); ?></div>
          <?php endif; ?>
          <?php if ($sales_order['customer_contact']): ?>
          <div class="key">Contact</div><div><?php echo htmlspecialchars($sales_order['customer_contact']); ?></div>
          <?php elseif ($sales_order['customer_phone']): ?>
          <div class="key">Contact</div><div><?php echo htmlspecialchars($sales_order['customer_phone']); ?></div>
          <?php endif; ?>
          <?php if ($sales_order['customer_gstin']): ?>
          <div class="key">GSTIN</div><div><?php echo htmlspecialchars($sales_order['customer_gstin']); ?></div>
          <?php elseif ($sales_order['customer_gstin_number']): ?>
          <div class="key">GSTIN</div><div><?php echo htmlspecialchars($sales_order['customer_gstin_number']); ?></div>
          <?php endif; ?>
          <?php if ($sales_order['customer_state']): ?>
          <div class="key">State</div><div><?php echo htmlspecialchars($sales_order['customer_state']); ?></div>
          <?php endif; ?>
        </div>
      </section>
      <section class="panel">
        <h3>Order Details</h3>
        <div class="kv">
          <div class="key">SO Number</div><div><?php echo htmlspecialchars($sales_order['so_number']); ?></div>
          <div class="key">Date</div><div><?php echo date('d/m/Y', strtotime($sales_order['so_date'])); ?></div>
          <?php if ($sales_order['delivery_date']): ?>
          <div class="key">Delivery Date</div><div><?php echo date('d/m/Y', strtotime($sales_order['delivery_date'])); ?></div>
          <?php endif; ?>
          <div class="key">Status</div><div><?php echo ucfirst($sales_order['status']); ?></div>
          <?php if ($sales_order['quotation_number']): ?>
          <div class="key">Quotation No.</div><div><?php echo htmlspecialchars($sales_order['quotation_number']); ?></div>
          <?php endif; ?>
        </div>
      </section>
    </div>

    <?php if ($sales_order['customer_address']): ?>
    <section class="panel" style="margin-top:14px;">
      <h3>Ship To</h3>
      <div class="kv">
        <div class="key">Address</div><div><?php echo htmlspecialchars($sales_order['customer_address']); ?></div>
      </div>
    </section>
    <?php endif; ?>

    <table aria-label="Sales Order Items">
      <thead>
        <tr>
          <th style="width:32px;">#</th>
          <th>Description</th>
          <?php if (!empty($items_array) && !empty($items_array[0]['hsn_code'])): ?>
          <th class="hsn">HSN/SAC</th>
          <?php endif; ?>
          <th class="qty">Qty</th>
          <th class="unit">Unit</th>
          <th class="num">Unit Price</th>
          <th class="num">Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $sl_no = 1;
        foreach ($items_array as $item): 
        ?>
        <tr>
          <td><?php echo $item['sl_no'] ?: $sl_no; ?></td>
          <td class="desc">
            <?php echo htmlspecialchars($item['item_name'] ?: $item['item_name']); ?>
            <?php if ($item['item_code']): ?>
            <br><small style="color: var(--muted);">Code: <?php echo htmlspecialchars($item['item_code']); ?></small>
            <?php endif; ?>
            <?php if ($item['description']): ?>
            <br><?php echo nl2br(htmlspecialchars($item['description'])); ?>
            <?php endif; ?>
          </td>
          <?php if (!empty($items_array) && !empty($items_array[0]['hsn_code'])): ?>
          <td class="hsn"><?php echo htmlspecialchars($item['hsn_code'] ?: ''); ?></td>
          <?php endif; ?>
          <td class="qty"><?php echo $item['quantity']; ?></td>
          <td class="unit"><?php echo htmlspecialchars($item['unit'] ?: 'Nos'); ?></td>
          <td class="num">₹ <?php echo number_format($item['unit_price'], 2); ?></td>
          <td class="num">₹ <?php echo number_format($item['total_price'], 2); ?></td>
        </tr>
        <?php 
        $sl_no++;
        endforeach; 
        ?>
      </tbody>
    </table>

    <table class="totals" aria-label="Totals">
      <tr>
        <td class="label">Sub Total</td>
        <td class="amount">₹ <?php echo number_format($subtotal, 2); ?></td>
      </tr>
      <?php if ($gst_amount > 0): ?>
      <tr>
        <td class="label">IGST @ <?php echo $gst_rate; ?>%</td>
        <td class="amount">₹ <?php echo number_format($gst_amount, 2); ?></td>
      </tr>
      <?php endif; ?>
      <?php if ($discount_amount > 0): ?>
      <tr>
        <td class="label">Discount<?php if ($sales_order['discount_percentage'] > 0) echo ' (' . number_format($sales_order['discount_percentage'], 1) . '%)'; ?></td>
        <td class="amount">- ₹ <?php echo number_format($discount_amount, 2); ?></td>
      </tr>
      <?php endif; ?>
      <tr>
        <td class="label" style="font-weight:800;">Total</td>
        <td class="amount" style="font-weight:800;">₹ <?php echo number_format($final_total, 2); ?></td>
      </tr>
    </table>

    <p class="note"><strong>Order Amount in Words:</strong> <?php echo $amount_in_words; ?></p>

    <?php if ($sales_order['notes']): ?>
    <div class="hr"></div>
    <section class="panel">
      <h3>Notes</h3>
      <p class="note"><?php echo nl2br(htmlspecialchars($sales_order['notes'])); ?></p>
    </section>
    <?php endif; ?>

    <div class="hr"></div>

    <section class="grid">
      <div class="panel">
        <h3>Payment Terms</h3>
        <div class="note">
          • 30% advance payment with order confirmation<br>
          • 50% against material readiness before dispatch<br>
          • 20% against successful installation & commissioning<br>
          • Payment through RTGS/NEFT only
        </div>
      </div>
      <div class="panel">
        <h3>Terms &amp; Conditions</h3>
        <div class="note">
          • Delivery as per agreed schedule<br>
          • Installation & commissioning included<br>
          • One year comprehensive warranty<br>
          • All disputes subject to Panchkula jurisdiction
        </div>
      </div>
    </section>

    <div class="sign">
      <div class="box">For: <?php echo htmlspecialchars($company_info['name']); ?><br><br>Authorized Signatory</div>
    </div>
  </article>

  <script>
    // Auto-print if print parameter is passed
    if (new URLSearchParams(window.location.search).get('print') === '1') {
      window.onload = function() {
        window.print();
      };
    }
  </script>
</body>
</html>
