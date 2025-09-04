<?php
require_once __DIR__ . '/../common/conn.php';
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../common/print_common.php';

if (!isset($_GET['pdf'])) {
    checkLogin();
}

/* ---------- Inputs ---------- */
$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_pdf_mode = isset($_GET['pdf']) && $_GET['pdf'] == '1';

if ($invoice_id <= 0) {
    logDocumentActivity('sales_invoices', "Invalid sales invoice ID access attempt");
    die('Invalid invoice ID');
}

logDocumentActivity('sales_invoices', "Accessing sales invoice print", $invoice_id);

/* ---------- Fetch Sales Invoice + Customer ---------- */
$invoice_sql = "SELECT si.*, c.email as customer_email, c.phone as customer_phone
                FROM sales_invoices si
                LEFT JOIN customers c ON si.customer_id = c.id
                WHERE si.id = $invoice_id";
$res = $conn->query($invoice_sql);
if (!$res || $res->num_rows === 0) {
    logDocumentActivity('sales_invoices', "Sales invoice not found", $invoice_id);
    die('Sales invoice not found');
}
$inv = $res->fetch_assoc();

logDocumentActivity('sales_invoices', "Sales invoice print generated successfully", $invoice_id);

/* ---------- Items ---------- */
$items_sql = "SELECT sii.*,
                     CASE WHEN sii.item_type='machine' THEN m.name
                          WHEN sii.item_type='spare'   THEN s.part_name
                          ELSE sii.item_name END as display_name
              FROM sales_invoice_items sii
              LEFT JOIN machines m ON sii.item_type='machine' AND sii.item_id=m.id
              LEFT JOIN spares s   ON sii.item_type='spare'   AND sii.item_id=s.id
              WHERE sii.invoice_id = $invoice_id
              ORDER BY sii.id";
$r_items = $conn->query($items_sql);
$items = [];
if ($r_items) while($row=$r_items->fetch_assoc()) $items[] = $row;

/* ---------- Company & Bank Details ---------- */
$company = getCompanyDetails() ?: [];
$bank = getBankDetails() ?: [];

/* ---------- Totals ---------- */
$subtotal = (float)($inv['subtotal'] ?? 0);
$discount_amount = (float)($inv['discount_amount'] ?? 0);
$tax_amount = (float)($inv['tax_amount'] ?? 0);
$grand_total = (float)($inv['final_total'] ?? ($inv['total_amount'] ?? 0));
$amount_in_words = getAmountInWords($grand_total);

/* ---------- GST Calculations ---------- */
function gst_state_code($gstin){
  $gstin = trim((string)$gstin);
  if (strlen($gstin) >= 2 && ctype_digit(substr($gstin,0,2))) return substr($gstin,0,2);
  return '';
}
$company_state_code = gst_state_code($company['gstin'] ?? ($company['gst'] ?? ''));
$customer_state_code = gst_state_code($inv['customer_gstin'] ?? '');
$is_intra = $company_state_code && $customer_state_code && $company_state_code === $customer_state_code;

// Recompute per-item splits (from item totals that include GST)
$cgst_total = 0.0; $sgst_total = 0.0; $igst_total = 0.0; $taxable_sum = 0.0;

foreach ($items as $it) {
  $total = (float)($it['total_price'] ?? 0);
  $rate = (float)($it['gst_rate'] ?? 0);
  if ($rate > 0) {
    $taxable = $total / (1 + ($rate/100));
    $gst = $total - $taxable;
  } else {
    $taxable = $total;
    $gst = 0.0;
  }
  $taxable_sum += $taxable;

  if ($is_intra) {
    $cgst_total += $gst/2;
    $sgst_total += $gst/2;
  } else {
    $igst_total += $gst;
  }
}

/* ---------- PDF noise suppression ---------- */
if ($is_pdf_mode) { ini_set('display_errors','0'); error_reporting(E_ALL); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Tax Invoice — <?php echo e($inv['invoice_number']); ?></title>
<style>
<?php echo getPrintStyles(); ?>
</style>
</head>
<body>

<?php if(!$is_pdf_mode): ?>
  <?php echo getPrintNavigation('../sales/', $invoice_id); ?>
<?php endif; ?>

<div class="wrapper">

  <?php echo getPrintHeader($company, 'TAX INVOICE', $inv['invoice_number'], $inv['status'] ?? 'Final'); ?>

  <!-- Bill To / Invoice Details -->
  <table style="margin-top:6mm">
    <tr>
      <td width="50%" valign="top">
        <h2>Bill To</h2>
        <table class="kv">
          <tr><td>Name</td><td><?php echo e($inv['customer_name'] ?? ''); ?></td></tr>
          <?php if (!empty($inv['customer_address'])): ?>
          <tr><td>Address</td><td><?php echo nl2br(e($inv['customer_address'])); ?></td></tr>
          <?php endif; ?>
          <?php if (!empty($inv['customer_gstin'])): ?>
          <tr><td>GSTIN</td><td><?php echo e($inv['customer_gstin']); ?></td></tr>
          <?php endif; ?>
          <tr><td>State</td><td><?php echo e($company['state'] ?? ''); ?></td></tr>
          <?php if (!empty($inv['customer_contact'])): ?>
          <tr><td>Contact</td><td><?php echo e($inv['customer_contact']); ?></td></tr>
          <?php endif; ?>
        </table>
      </td>
      <td width="50%" valign="top">
        <h2>Invoice Details</h2>
        <table class="kv">
          <tr><td>Invoice No.</td><td><?php echo e($inv['invoice_number'] ?? ''); ?></td></tr>
          <tr><td>Date</td><td><?php echo !empty($inv['invoice_date']) ? date('d.m.Y', strtotime($inv['invoice_date'])) : ''; ?></td></tr>
          <tr><td>Place of Supply</td><td><?php echo e($company['state'] ?? ''); ?></td></tr>
          <?php if (!empty($inv['purchase_order_id'])): ?>
          <tr><td>PO/Ref</td><td><?php echo e($inv['purchase_order_id']); ?></td></tr>
          <?php endif; ?>
          <?php if (!empty($inv['due_date'])): ?>
          <tr><td>Due Date</td><td><?php echo date('d.m.Y', strtotime($inv['due_date'])); ?></td></tr>
          <?php endif; ?>
        </table>
      </td>
    </tr>
  </table>

  <!-- Ship To -->
  <h2>Ship To</h2>
  <table class="kv">
    <tr><td>Address</td><td>Shipping address same as Bill To</td></tr>
  </table>

  <!-- Items -->
  <h2>Items</h2>
  <table class="items">
    <thead>
      <tr>
        <th width="32">#</th>
        <th>Description of Goods/Services</th>
        <th width="90">HSN/SAC</th>
        <th width="60" class="num">Qty</th>
        <th width="60">Unit</th>
        <th width="90" class="num">Rate</th>
        <th width="110" class="num">Taxable Value</th>
        <th width="90" class="num">CGST</th>
        <th width="90" class="num">SGST</th>
        <th width="90" class="num">IGST</th>
        <th width="110" class="num">Amount</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($items) === 0): ?>
        <tr><td colspan="11" style="text-align:center;color:#666">No items</td></tr>
      <?php else:
        $i = 1;
        foreach ($items as $it):
          $total = (float)($it['total_price'] ?? 0);
          $rate = (float)($it['gst_rate'] ?? 0);
          if ($rate > 0) {
            $taxable = $total / (1 + ($rate/100));
            $gst = $total - $taxable;
          } else {
            $taxable = $total; 
            $gst = 0.0;
          }
          $cgst = $is_intra ? ($gst/2) : 0.0;
          $sgst = $is_intra ? ($gst/2) : 0.0;
          $igst = $is_intra ? 0.0 : $gst;
      ?>
      <tr>
        <td><?php echo $i++; ?></td>
        <td>
          <strong><?php echo e($it['display_name'] ?? $it['item_name'] ?? ''); ?></strong>
          <?php if (!empty($it['description'])): ?>
            <div><small><?php echo nl2br(e($it['description'])); ?></small></div>
          <?php endif; ?>
        </td>
        <td><?php echo e($it['hsn_code'] ?? ''); ?></td>
        <td class="num"><?php echo rtrim(rtrim(number_format((float)($it['quantity'] ?? 0),2,'.',''), '0'), '.'); ?></td>
        <td><?php echo e($it['unit'] ?? 'Nos'); ?></td>
        <td class="num"><?php echo fmt_money((float)($it['unit_price'] ?? 0)); ?></td>
        <td class="num"><?php echo fmt_money($taxable); ?></td>
        <td class="num"><?php echo fmt_money($cgst); ?></td>
        <td class="num"><?php echo fmt_money($sgst); ?></td>
        <td class="num"><?php echo fmt_money($igst); ?></td>
        <td class="num"><?php echo fmt_money($total); ?></td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>

  <!-- Totals -->
  <h2>Totals</h2>
  <table class="kv" style="width:60%">
    <tr><td>Total Taxable Value</td><td class="num"><?php echo fmt_money($taxable_sum); ?></td></tr>
    <tr><td>CGST Total</td><td class="num"><?php echo fmt_money($cgst_total); ?></td></tr>
    <tr><td>SGST Total</td><td class="num"><?php echo fmt_money($sgst_total); ?></td></tr>
    <tr><td>IGST Total</td><td class="num"><?php echo fmt_money($igst_total); ?></td></tr>
    <?php if ($discount_amount > 0): ?>
    <tr><td>Discount</td><td class="num">- <?php echo fmt_money($discount_amount); ?></td></tr>
    <?php endif; ?>
    <tr><td><b>Grand Total</b></td><td class="num"><b><?php echo fmt_money($grand_total); ?></b></td></tr>
    <tr><td>Round Off</td><td class="num"><?php echo fmt_money(0); ?></td></tr>
    <tr><td>Amount Receivable</td><td class="num"><?php echo fmt_money($grand_total); ?></td></tr>
  </table>

  <div class="note" style="margin-top:3mm"><b>Amount in words:</b> <?php echo e($amount_in_words); ?></div>

  <!-- Bank Details & Terms -->
  <table style="margin-top:6mm">
    <tr>
      <td width="50%" valign="top">
        <h2>Bank Details</h2>
        <table class="kv">
          <tr><td>Bank</td><td><?php echo e($bank['bank_name'] ?? ''); ?></td></tr>
          <tr><td>Account No.</td><td><?php echo e($bank['account_number'] ?? ''); ?></td></tr>
          <tr><td>IFSC</td><td><?php echo e($bank['ifsc'] ?? ''); ?></td></tr>
          <tr><td>Beneficiary</td><td><?php echo e($bank['beneficiary'] ?? ''); ?></td></tr>
        </table>
      </td>
      <td width="50%" valign="top">
        <h2>Declaration / Terms</h2>
        <div style="border:1px solid #ddd;padding:6mm">
          We declare that this invoice shows the actual price of the goods/services described and that all particulars are true and correct. Subject to <?php echo e($company['state'] ?? 'local'); ?> jurisdiction.
          <?php if (!empty($inv['notes'])): ?>
          <div style="margin-top:4mm"><b>Notes:</b><br><?php echo nl2br(e($inv['notes'])); ?></div>
          <?php endif; ?>
        </div>
      </td>
    </tr>
  </table>

  <!-- Signature -->
  <table style="margin-top:10mm">
    <tr>
      <td width="60%"></td>
      <td width="40%" style="text-align:center;border-top:1px solid #9ca3af;padding-top:6px">
        For: <?php echo e($company['name'] ?? ''); ?><br><br>Authorized Signatory
      </td>
    </tr>
  </table>

  <!-- Footer -->
  <?php echo getPrintFooter($company); ?>

  <!-- Optional print controls (hidden in PDF/print) -->
  <?php if(!$is_pdf_mode): ?>
  <div class="no-print" style="margin-top:8mm;text-align:center;border-top:1px solid #e5e7eb;padding-top:6mm">
    <button onclick="window.print()" style="background:#0f6abf;color:#fff;border:0;padding:8px 12px;border-radius:6px;cursor:pointer">🖨️ Print</button>
    <button onclick="window.close()" style="background:#6c757d;color:#fff;border:0;padding:8px 12px;border-radius:6px;cursor:pointer;margin-left:6px">Close</button>
  </div>
  <?php endif; ?>

</div>
</body>
</html>
