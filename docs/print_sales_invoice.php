<?php
// docs/print_sales_invoice.php
require_once '../common/conn.php';
require_once '../common/functions.php';

if (!isset($_SESSION['user_id'])) {
    die('Access denied');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid invoice ID');
}

$invoice_id = (int)$_GET['id'];

// Get invoice details
$invoice_sql = "SELECT si.*, c.email as customer_email, c.phone as customer_phone
                FROM sales_invoices si
                LEFT JOIN customers c ON si.customer_id = c.id
                WHERE si.id = $invoice_id";

$result = $conn->query($invoice_sql);

if ($result->num_rows === 0) {
    die('Sales invoice not found');
}

$invoice = $result->fetch_assoc();

// Get invoice items
$items_sql = "SELECT sii.*, 
                     CASE 
                        WHEN sii.item_type = 'machine' THEN m.name
                        WHEN sii.item_type = 'spare' THEN s.part_name
                        ELSE sii.item_name
                     END as display_name
              FROM sales_invoice_items sii
              LEFT JOIN machines m ON sii.item_type = 'machine' AND sii.item_id = m.id
              LEFT JOIN spares s ON sii.item_type = 'spare' AND sii.item_id = s.id
              WHERE sii.invoice_id = $invoice_id
              ORDER BY sii.id";

$items_result = $conn->query($items_sql);

$items = [];
while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
}

// Calculate totals
$subtotal = $invoice['subtotal'] ?: 0;
$discount_amount = $invoice['discount_amount'] ?: 0;
$tax_amount = $invoice['tax_amount'] ?: 0;
$grand_total = $invoice['final_total'] ?: $invoice['total_amount'];

// Get company and bank details from common functions
$company = getCompanyDetails();
$bank = getBankDetails();
$amount_in_words = getAmountInWords($grand_total);

// Calculate GST breakdown
$cgst_total = 0;
$sgst_total = 0;
$igst_total = 0;
foreach ($items as $item) {
    $item_total = $item['total_price'];
    $gst_rate = $item['gst_rate'] ?: 0;
    $taxable_value = $item_total / (1 + ($gst_rate / 100));
    $gst_amount = $item_total - $taxable_value;
    
    // Assuming IGST for inter-state and CGST+SGST for intra-state
    // You can modify this logic based on your requirements
    if (true) { // Change this condition based on your state logic
        $igst_total += $gst_amount;
    } else {
        $cgst_total += $gst_amount / 2;
        $sgst_total += $gst_amount / 2;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Tax Invoice (GST) — <?php echo htmlspecialchars($invoice['invoice_number']); ?></title>
  <style>
    :root{
      --ink:#111827;--muted:#6b7280;--line:#e5e7eb;--brand:#0ea5e9;--bg:#ffffff
    }
    html,body{margin:0;padding:0;background:var(--bg);color:var(--ink);font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Helvetica,Arial}
    .doc{max-width:900px;margin:24px auto;padding:24px;border:1px solid var(--line);border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.04)}
    .brandbar{display:flex;justify-content:space-between;gap:16px}
    .brand h1{margin:0 0 4px;font-size:22px;letter-spacing:.3px}
    .brand p{margin:0;color:var(--muted);font-size:12px;line-height:1.4}
    .tag{font-weight:800;font-size:24px;padding:6px 12px;border-radius:10px;border:2px solid var(--brand);color:var(--brand);height:max-content}

    .grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:16px}
    .panel{border:1px solid var(--line);border-radius:10px;padding:12px}
    .panel h3{margin:0 0 8px;font-size:13px;text-transform:uppercase;letter-spacing:.8px;color:var(--muted)}
    .kv{display:grid;grid-template-columns:160px 1fr;gap:6px 10px;font-size:13px}
    .kv .key{color:var(--muted)}

    table{width:100%;border-collapse:collapse;margin-top:16px;font-size:13px}
    thead th{font-size:12px;color:var(--muted);text-align:left;border-bottom:2px solid var(--line);padding:8px}
    tbody td{border-bottom:1px solid var(--line);padding:8px;vertical-align:top}
    tfoot td{padding:8px}
    .num{text-align:right;white-space:nowrap}
    .nowrap{white-space:nowrap}

    .totals{width:100%;margin-top:10px;border-collapse:collapse}
    .totals td{padding:8px}
    .totals .label{text-align:right;color:var(--muted)}
    .totals .amount{text-align:right;font-weight:700}
    .totals tr+tr td{border-top:1px dashed var(--line)}

    .note{margin-top:8px;font-size:12px;color:var(--muted)}
    .sign{margin-top:24px;display:flex;justify-content:flex-end}
    .sign .box{border-top:1px solid var(--line);padding-top:8px;min-width:240px;text-align:center;font-size:12px;color:var(--muted)}

    .no-print{display:block;}
    @media print{
      .doc{box-shadow:none;border:none;margin:0;padding:16px;}
      .no-print{display:none !important;}
      body{margin:0;}
    }
  </style>
</head>
<body>
  <article class="doc">
    <header class="brandbar">
      <div class="brand">
        <h1><?php echo $company['name']; ?></h1>
        <p><?php echo $company['address']; ?><br>
        GSTIN: <?php echo $company['gstin']; ?> • State: <?php echo $company['state']; ?> • CIN: <?php echo $company['cin']; ?><br>
        <?php echo $company['phone']; ?> • <?php echo $company['email']; ?></p>
      </div>
      <div class="tag">Tax Invoice</div>
    </header>

    <div class="grid">
      <section class="panel">
        <h3>Bill To</h3>
        <div class="kv">
          <div class="key">Name</div><div><?php echo htmlspecialchars($invoice['customer_name']); ?></div>
          <div class="key">Address</div><div><?php echo htmlspecialchars($invoice['customer_address']); ?></div>
          <?php if (!empty($invoice['customer_gstin'])): ?>
          <div class="key">GSTIN</div><div><?php echo htmlspecialchars($invoice['customer_gstin']); ?></div>
          <?php endif; ?>
          <div class="key">State</div><div><?php echo $company['state']; ?></div>
          <div class="key">Contact</div><div><?php echo htmlspecialchars($invoice['customer_contact']); ?></div>
        </div>
      </section>
      <section class="panel">
        <h3>Invoice Details</h3>
        <div class="kv">
          <div class="key">Invoice No.</div><div><?php echo htmlspecialchars($invoice['invoice_number']); ?></div>
          <div class="key">Date</div><div><?php echo date('d/m/Y', strtotime($invoice['invoice_date'])); ?></div>
          <div class="key">Place of Supply</div><div><?php echo $company['state']; ?></div>
          <?php if (!empty($invoice['purchase_order_id'])): ?>
          <div class="key">PO/Ref</div><div><?php echo htmlspecialchars($invoice['purchase_order_id']); ?></div>
          <?php endif; ?>
          <div class="key">Due Date</div><div><?php echo date('d/m/Y', strtotime($invoice['due_date'])); ?></div>
        </div>
      </section>
    </div>

    <section class="panel" style="margin-top:12px;">
      <h3>Ship To</h3>
      <div class="kv">
        <div class="key">Address</div><div>Shipping address same as Bill To</div>
      </div>
    </section>

    <table aria-label="Invoice Items">
      <thead>
        <tr>
          <th style="width:36px;">#</th>
          <th>Description of Goods/Services</th>
          <th class="nowrap">HSN/SAC</th>
          <th class="nowrap">Qty</th>
          <th class="nowrap">Unit</th>
          <th class="num">Rate</th>
          <th class="num nowrap">Taxable Value</th>
          <th class="num nowrap">CGST</th>
          <th class="num nowrap">SGST</th>
          <th class="num nowrap">IGST</th>
          <th class="num">Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $sr_no = 1;
        foreach ($items as $item): 
          $item_total = $item['total_price'];
          $gst_rate = $item['gst_rate'] ?: 0;
          $taxable_value = $item_total / (1 + ($gst_rate / 100));
          $gst_amount = $item_total - $taxable_value;
          
          // Assuming IGST for inter-state
          $cgst = 0;
          $sgst = 0;
          $igst = $gst_amount;
        ?>
        <tr>
          <td><?php echo $sr_no++; ?></td>
          <td>
            <?php echo htmlspecialchars($item['display_name'] ?: $item['item_name']); ?>
            <?php if (!empty($item['description'])): ?>
            <br><small><?php echo htmlspecialchars($item['description']); ?></small>
            <?php endif; ?>
          </td>
          <td><?php echo htmlspecialchars($item['hsn_code']); ?></td>
          <td class="nowrap"><?php echo number_format($item['quantity'], 2); ?></td>
          <td><?php echo htmlspecialchars($item['unit'] ?: 'Nos'); ?></td>
          <td class="num">₹ <?php echo number_format($item['unit_price'], 2); ?></td>
          <td class="num">₹ <?php echo number_format($taxable_value, 2); ?></td>
          <td class="num">₹ <?php echo number_format($cgst, 2); ?></td>
          <td class="num">₹ <?php echo number_format($sgst, 2); ?></td>
          <td class="num">₹ <?php echo number_format($igst, 2); ?></td>
          <td class="num">₹ <?php echo number_format($item_total, 2); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <table class="totals" aria-label="Totals">
      <tr>
        <td class="label">Total Taxable Value</td>
        <td class="amount">₹ <?php echo number_format($subtotal - $tax_amount, 2); ?></td>
      </tr>
      <tr>
        <td class="label">CGST Total</td>
        <td class="amount">₹ <?php echo number_format($cgst_total, 2); ?></td>
      </tr>
      <tr>
        <td class="label">SGST Total</td>
        <td class="amount">₹ <?php echo number_format($sgst_total, 2); ?></td>
      </tr>
      <tr>
        <td class="label">IGST Total</td>
        <td class="amount">₹ <?php echo number_format($igst_total, 2); ?></td>
      </tr>
      <?php if ($discount_amount > 0): ?>
      <tr>
        <td class="label">Discount</td>
        <td class="amount">-₹ <?php echo number_format($discount_amount, 2); ?></td>
      </tr>
      <?php endif; ?>
      <tr>
        <td class="label" style="font-weight:800;">Grand Total</td>
        <td class="amount" style="font-weight:800;">₹ <?php echo number_format($grand_total, 2); ?></td>
      </tr>
      <tr>
        <td class="label">Round Off</td>
        <td class="amount">₹ 0.00</td>
      </tr>
      <tr>
        <td class="label">Amount Receivable</td>
        <td class="amount">₹ <?php echo number_format($grand_total, 2); ?></td>
      </tr>
    </table>

    <p class="note"><strong>Amount in Words:</strong> <?php echo $amount_in_words; ?></p>

    <div class="grid" style="margin-top:8px;">
      <section class="panel">
        <h3>Bank Details</h3>
        <div class="kv">
          <div class="key">Bank</div><div><?php echo $bank['bank_name']; ?></div>
          <div class="key">Account No.</div><div><?php echo $bank['account_number']; ?></div>
          <div class="key">IFSC</div><div><?php echo $bank['ifsc']; ?></div>
          <div class="key">Beneficiary</div><div><?php echo $bank['beneficiary']; ?></div>
        </div>
      </section>
      <section class="panel">
        <h3>Declaration / Terms</h3>
        <p class="note">We declare that this invoice shows the actual price of the goods/services described and that all particulars are true and correct. Subject to [Your City] jurisdiction.</p>
        <?php if (!empty($invoice['notes'])): ?>
        <p class="note"><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
        <?php endif; ?>
      </section>
    </div>

    <div class="sign">
      <div class="box">For: <?php echo $company['name']; ?><br><br>Authorized Signatory</div>
    </div>

    <!-- Print Controls -->
    <div class="no-print" style="margin-top:24px;text-align:center;border-top:1px solid var(--line);padding-top:16px;">
      <button onclick="window.print()" style="background:var(--brand);color:white;border:none;padding:10px 20px;border-radius:6px;cursor:pointer;margin-right:10px;">Print Invoice</button>
      <button onclick="window.close()" style="background:var(--muted);color:white;border:none;padding:10px 20px;border-radius:6px;cursor:pointer;">Close</button>
    </div>
  </article>
</body>
</html>