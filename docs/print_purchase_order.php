<?php
require_once __DIR__ . '/../common/conn.php';
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../common/print_common.php';

if (!isset($_GET['pdf'])) {
    checkLogin();
}

/* ---------- Inputs ---------- */
$po_id       = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_pdf_mode = isset($_GET['pdf']) && $_GET['pdf'] == '1';
if ($po_id <= 0) {
    logDocumentActivity('purchase_orders', "Invalid purchase order ID access attempt");
    die('Invalid purchase order ID');
}

logDocumentActivity('purchase_orders', "Accessing purchase order print", $po_id);

/* ---------- Fetch PO + Vendor ---------- */
$po_sql = "SELECT po.*,
           c.company_name  AS vendor_name,
           c.address       AS vendor_address,
           c.city          AS vendor_city,
           c.state         AS vendor_state,
           c.pincode       AS vendor_pincode,
           c.gst_no        AS vendor_gstin,
           c.phone         AS vendor_phone,
           c.email         AS vendor_email,
           u.full_name     AS created_by_name
           FROM purchase_orders po
           LEFT JOIN customers c ON po.vendor_id = c.id
           LEFT JOIN users     u ON po.created_by = u.id
           WHERE po.id = $po_id";
$po_result = $conn->query($po_sql);
if (!$po_result || $po_result->num_rows === 0) {
    logDocumentActivity('purchase_orders', "Purchase order not found", $po_id);
    die('Purchase order not found');
}
$po = $po_result->fetch_assoc();

logDocumentActivity('purchase_orders', "Purchase order print generated successfully", $po_id);

/* ---------- Items ---------- */
$items_sql = "SELECT *
              FROM purchase_order_items
              WHERE po_id = $po_id
              ORDER BY id";
$items_result = $conn->query($items_sql);

/* ---------- Company + Bank ---------- */
$company = getCompanyDetails() ?: [];
$bank    = getBankDetails()    ?: [];

logDocumentActivity('purchase_orders', "Purchase order print generated successfully", $po_id);

/* ---------- Totals (with tax split) ---------- */
$company_state = strtolower(trim($company['state'] ?? ''));     // e.g. 'Haryana'
$vendor_state  = strtolower(trim($po['vendor_state'] ?? ''));   // vendor state
$intra_state   = ($company_state && $vendor_state && $company_state === $vendor_state);

// default rate (fallback); you can also store GST in items table per row
$default_gst_rate = isset($po['gst_rate']) ? (float)$po['gst_rate'] : 18.0;

$items = [];
$subtotal = 0.0; $tax_total = 0.0;
if ($items_result) {
  while ($it = $items_result->fetch_assoc()) {
    $qty   = (float)($it['quantity']    ?? 0);
    $rate  = (float)($it['unit_price']  ?? 0);
    $gst_r = isset($it['gst_rate']) ? (float)$it['gst_rate'] : $default_gst_rate;   // per item or default
    $line  = (float)($it['total_price'] ?? ($qty * $rate));
    $tax   = ($line * $gst_r)/100.0;

    $it['_calc_qty']   = $qty;
    $it['_calc_rate']  = $rate;
    $it['_calc_line']  = $line;
    $it['_calc_gst_r'] = $gst_r;
    $it['_calc_tax']   = $tax;

    $items[] = $it;
    $subtotal += $line;
    $tax_total += $tax;
  }
}

$discount_amount = (float)($po['discount_amount'] ?? 0.0);
$pretax_total    = $subtotal;
$grand_before_disc = $pretax_total + $tax_total;
$grand_total     = max(0, $grand_before_disc - $discount_amount);

// Split tax for intra-state
$cgst = $sgst = $igst = 0.0;
if ($intra_state) {
  $cgst = $tax_total/2.0;
  $sgst = $tax_total/2.0;
} else {
  $igst = $tax_total;
}

$amount_in_words = numberToWords($grand_total) . ' Rupees Only';

// Hide PHP warnings inside PDF output
if ($is_pdf_mode) { ini_set('display_errors','0'); error_reporting(E_ALL); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Purchase Order — <?php echo e($po['po_number']); ?></title>
<style>
<?php echo getPrintStyles(); ?>
.badge{display:inline-block;padding:2px 6px;font-size:11px;border-radius:4px;color:#fff}
.status-draft{background:#6c757d}.status-pending{background:#ffc107;color:#212529}.status-approved{background:#17a2b8}.status-completed{background:#28a745}.status-cancelled{background:#dc3545}
</style>
</head>
<body>

<?php if(!$is_pdf_mode): ?>
  <?php echo getPrintNavigation('../purchase/purchase_orders.php', $po_id); ?>
<?php endif; ?>

<div class="wrapper">

  <?php echo getPrintHeader($company, 'PURCHASE ORDER', $po['po_number'], $po['status']); ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Purchase Order — <?php echo e($po['po_number']); ?></title>
<style>
body{font:13px/1.45 "DejaVu Sans", Arial, sans-serif;color:#222;margin:0}
.wrapper{max-width:800px;margin:0 auto;padding:10mm}
h1{font-size:20px;margin:0 0 3mm}
h2{font-size:15px;margin:6mm 0 3mm;color:#2c5aa0}
small{color:#666}
table{width:100%;border-collapse:collapse}
td,th{padding:6px 6px;vertical-align:top}
.hr{height:1px;background:#ddd;margin:5mm 0}
.box{border:1px solid #ccc}
.kv td{border:1px solid #ddd}
.kv td:first-child{background:#f8f8f8;width:140px;font-weight:bold}
.items thead th{border-bottom:2px solid #2c5aa0;background:#e9f0ff;font-size:12px;text-align:left}
.items td{border-bottom:1px solid #e7e7e7}
.num{text-align:right;white-space:nowrap}
.badge{display:inline-block;padding:2px 6px;font-size:11px;border-radius:4px;color:#fff}
.status-draft{background:#6c757d}.status-pending{background:#ffc107;color:#212529}.status-approved{background:#17a2b8}.status-completed{background:#28a745}.status-cancelled{background:#dc3545}
.footer{font-size:12px;border-top:1px solid #444;margin-top:6mm;padding-top:2mm}
.note{font-size:12px;color:#555}
@page { size:A4; margin:12mm }
thead { display: table-header-group; }
tfoot { display: table-row-group; }
tr { page-break-inside: avoid; }
@media print { .no-print { display:none !important; } .wrapper{max-width:100%;padding:0} }
</style>
</head>
<body>

<?php if(!$is_pdf_mode): ?>
  <div class="no-print" style="position:fixed;top:16px;right:16px;display:flex;gap:8px;z-index:1000">
    <a href="../purchase/purchase_orders.php" style="background:#6c757d;color:#fff;text-decoration:none;padding:8px 12px;border-radius:6px">← Back</a>
    <a href="?id=<?php echo $po_id; ?>&pdf=1" target="_blank" style="background:#2c5aa0;color:#fff;text-decoration:none;padding:8px 12px;border-radius:6px">Open PDF</a>
    <button onclick="window.print()" style="background:#0f766e;color:#fff;border:0;padding:8px 12px;border-radius:6px;cursor:pointer">🖨️ Print</button>
  </div>
<?php endif; ?>


  <!-- Header (table-only) -->

  <!-- Vendor & PO Details (2 columns, tables) -->
  <table style="margin-top:6mm">
    <tr>
      <td width="50%" valign="top">
        <h2>Vendor Information</h2>
        <table class="kv">
          <tr><td>Vendor</td><td><?php echo e($po['vendor_name'] ?? ''); ?></td></tr>
          <?php
            $vaddr_parts=[];
            if (!empty($po['vendor_address'])) $vaddr_parts[]=$po['vendor_address'];
            $city_state = trim(($po['vendor_city'] ?? '').((!empty($po['vendor_city']) && !empty($po['vendor_state']))?', ':'').($po['vendor_state'] ?? ''));
            if ($city_state) $vaddr_parts[]=$city_state;
            if (!empty($po['vendor_pincode'])) $vaddr_parts[]=$po['vendor_pincode'];
            $vaddr_str = implode(', ', array_filter($vaddr_parts));
          ?>
          <?php if ($vaddr_str): ?><tr><td>Address</td><td><?php echo nl2br(e($vaddr_str)); ?></td></tr><?php endif; ?>
          <?php if (!empty($po['vendor_gstin'])): ?><tr><td>GSTIN</td><td><?php echo e($po['vendor_gstin']); ?></td></tr><?php endif; ?>
          <?php if (!empty($po['vendor_phone'])): ?><tr><td>Phone</td><td><?php echo e($po['vendor_phone']); ?></td></tr><?php endif; ?>
          <?php if (!empty($po['vendor_email'])): ?><tr><td>Email</td><td><?php echo e($po['vendor_email']); ?></td></tr><?php endif; ?>
        </table>
      </td>
      <td width="50%" valign="top">
        <h2>Order Details</h2>
        <table class="kv">
          <tr><td>PO Date</td><td><?php echo !empty($po['po_date']) ? date('d.m.Y', strtotime($po['po_date'])) : ''; ?></td></tr>
          <?php if (!empty($po['due_date'])): ?><tr><td>Due Date</td><td><?php echo date('d.m.Y', strtotime($po['due_date'])); ?></td></tr><?php endif; ?>
          <?php if (!empty($po['sales_order_number'])): ?><tr><td>Sales Order</td><td><?php echo e($po['sales_order_number']); ?></td></tr><?php endif; ?>
          <tr><td>Status</td>
              <td>
                <?php
                  $status = strtolower((string)($po['status'] ?? 'draft'));
                  $badge  = ['draft'=>'status-draft','pending'=>'status-pending','approved'=>'status-approved','completed'=>'status-completed','cancelled'=>'status-cancelled'][$status] ?? 'status-draft';
                ?>
                <span class="badge <?php echo $badge; ?>"><?php echo ucfirst(e($status)); ?></span>
              </td>
          </tr>
          <?php if (!empty($po['created_by_name'])): ?><tr><td>Created By</td><td><?php echo e($po['created_by_name']); ?></td></tr><?php endif; ?>
        </table>
      </td>
    </tr>
  </table>

  <!-- Items -->
  <h2>Items</h2>
  <table class="items" aria-label="Purchase Order Items">
    <thead>
      <tr>
        <th width="36">#</th>
        <th>Description</th>
        <th width="70">HSN</th>
        <th width="70" class="num">Qty</th>
        <th width="70">Unit</th>
        <th width="100" class="num">Rate</th>
        <th width="70"  class="num">GST%</th>
        <th width="110" class="num">Amount</th>
      </tr>
    </thead>
    <tbody>
      <?php
      if (count($items) === 0) {
        echo '<tr><td colspan="8" style="text-align:center;color:#666">No items</td></tr>';
      } else {
        $i=1;
        foreach ($items as $it):
      ?>
      <tr>
        <td><?php echo $i++; ?></td>
        <td>
          <strong><?php echo e($it['item_name'] ?? $it['description'] ?? ''); ?></strong>
          <?php if (!empty($it['item_code'])): ?><div><small>Code: <?php echo e($it['item_code']); ?></small></div><?php endif; ?>
          <?php if (!empty($it['description'])): ?><div><small><?php echo nl2br(e($it['description'])); ?></small></div><?php endif; ?>
        </td>
        <td><?php echo e($it['hsn_code'] ?? '-'); ?></td>
        <td class="num"><?php echo rtrim(rtrim(number_format((float)$it['_calc_qty'],2,'.',''), '0'), '.'); ?></td>
        <td><?php echo e($it['unit'] ?? 'Nos'); ?></td>
        <td class="num"><?php echo fmt_money($it['_calc_rate']); ?></td>
        <td class="num"><?php echo rtrim(rtrim(number_format((float)$it['_calc_gst_r'],2,'.',''), '0'), '.'); ?></td>
        <td class="num"><?php echo fmt_money($it['_calc_line']); ?></td>
      </tr>
      <?php endforeach; } ?>
    </tbody>
  </table>

  <!-- Totals -->
  <table style="margin-top:6mm">
    <tr>
      <td></td>
      <td width="320" valign="top">
        <table class="kv">
          <tr><td>Sub Total</td><td class="num"><?php echo fmt_money($subtotal); ?></td></tr>
          <?php if ($intra_state): ?>
            <tr><td>CGST</td><td class="num"><?php echo fmt_money($cgst); ?></td></tr>
            <tr><td>SGST</td><td class="num"><?php echo fmt_money($sgst); ?></td></tr>
          <?php else: ?>
            <tr><td>IGST</td><td class="num"><?php echo fmt_money($igst); ?></td></tr>
          <?php endif; ?>
          <?php if ($discount_amount > 0): ?>
            <tr><td>Discount</td><td class="num">- <?php echo fmt_money($discount_amount); ?></td></tr>
          <?php endif; ?>
          <tr><td><b>Total</b></td><td class="num"><b><?php echo fmt_money($grand_total); ?></b></td></tr>
        </table>
        <div class="note" style="margin-top:3mm"><b>Amount in words:</b> <?php echo e($amount_in_words); ?></div>
      </td>
    </tr>
  </table>

  <!-- Notes -->
  <?php if (!empty($po['notes'])): ?>
    <div class="hr"></div>
    <h2>Notes</h2>
    <div class="note"><?php echo nl2br(e($po['notes'])); ?></div>
  <?php endif; ?>

  <div class="hr"></div>

  <!-- Terms (optional) -->
  <h2>Terms & Conditions</h2>
  <table class="kv">
    <tr><td>Payment</td><td>As per agreed terms. Payments via RTGS/NEFT.</td></tr>
    <tr><td>Delivery</td><td>As per schedule in PO. Any variation to be mutually agreed.</td></tr>
    <?php if (!empty($bank['bank_name'])): ?>
    <tr><td>Bank</td><td>
      <?php
        echo e($bank['bank_name'] ?? '');
        if (!empty($bank['account_name']))  echo ' · A/c: '.e($bank['account_name']);
        if (!empty($bank['account_number']))echo ' · No: '.e($bank['account_number']);
        if (!empty($bank['ifsc']))          echo ' · IFSC: '.e($bank['ifsc']);
        if (!empty($bank['branch']))        echo ' · Branch: '.e($bank['branch']);
      ?>
    </td></tr>
    <?php endif; ?>
  </table>

  <!-- Signatures -->
  <table style="margin-top:12mm">
    <tr>
      <td width="50%" align="center" style="padding-top:36px;border-top:1px solid #888;font-weight:bold">
        For: <?php echo e($company['name'] ?? ''); ?><br><br>Authorised Signatory
      </td>
      <td width="50%" align="center" style="padding-top:36px;border-top:1px solid #888;font-weight:bold">
        Vendor Seal & Signature
      </td>
    </tr>
  </table>

  <?php echo getPrintFooter($company); ?>

</div>
</body>
</html>
