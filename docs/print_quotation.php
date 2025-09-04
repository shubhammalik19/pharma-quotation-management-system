<?php
require_once __DIR__ . '/../common/conn.php';
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../common/print_common.php';

if (!isset($_GET['pdf'])) {
    checkLogin();
}

/* ---------- Inputs ---------- */
$quotation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_pdf_mode  = isset($_GET['pdf']) && $_GET['pdf'] == '1';

if ($quotation_id <= 0) {
    logDocumentActivity('quotations', "Invalid quotation ID access attempt");
    die('Invalid quotation ID');
}

logDocumentActivity('quotations', "Accessing quotation print", $quotation_id);

/* ---------- Fetch Quotation + Customer ---------- */
$quotation_sql = "SELECT q.*,
                  c.company_name,
                  c.contact_person,
                  c.phone,
                  c.email,
                  c.address,
                  c.city,
                  c.state,
                  c.gst_no
                  FROM quotations q
                  LEFT JOIN customers c ON q.customer_id = c.id
                  WHERE q.id = $quotation_id";
$quotation_result = $conn->query($quotation_sql);
if (!$quotation_result || $quotation_result->num_rows === 0) {
    logDocumentActivity('quotations', "Quotation not found", $quotation_id);
    die('Quotation not found');
}
$quotation = $quotation_result->fetch_assoc();

logDocumentActivity('quotations', "Quotation print generated successfully", $quotation_id);

/* ---------- Items ---------- */
$items_sql = "SELECT qi.*,
              CASE WHEN qi.item_type = 'machine' THEN m.name
                   WHEN qi.item_type = 'spare'   THEN s.part_name END AS item_name,
              CASE WHEN qi.item_type = 'machine' THEN m.model
                   WHEN qi.item_type = 'spare'   THEN s.part_code END AS item_code
              FROM quotation_items qi
              LEFT JOIN machines m ON qi.item_type='machine' AND qi.item_id=m.id
              LEFT JOIN spares   s ON qi.item_type='spare'   AND qi.item_id=s.id
              WHERE qi.quotation_id = $quotation_id
              ORDER BY qi.sl_no";
$items_result = $conn->query($items_sql);

/* ---------- Load Machine Features ---------- */
$machine_features = [];
$features_sql = "SELECT qmf.quotation_item_id, qmf.feature_name, qmf.price, qmf.quantity, qmf.total_price
                 FROM quotation_machine_features qmf
                 INNER JOIN quotation_items qi ON qmf.quotation_item_id = qi.id
                 WHERE qi.quotation_id = $quotation_id
                 ORDER BY qmf.id";
$features_result = $conn->query($features_sql);
if ($features_result) {
    while ($feature = $features_result->fetch_assoc()) {
        $item_id = $feature['quotation_item_id'];
        if (!isset($machine_features[$item_id])) {
            $machine_features[$item_id] = [];
        }
        $machine_features[$item_id][] = $feature;
    }
}

/* ---------- Totals ---------- */
$subtotal = 0.0;
$items = [];
if ($items_result) {
  while ($row = $items_result->fetch_assoc()) {
    $row['unit_price']  = (float)($row['unit_price'] ?? 0);
    $row['quantity']    = (float)($row['quantity'] ?? 0);
    $row['total_price'] = (float)($row['total_price'] ?? ($row['unit_price'] * $row['quantity']));
    $items[] = $row;
    $subtotal += $row['total_price'];
    
    // Add machine features to subtotal
    if (isset($machine_features[$row['id']])) {
        foreach ($machine_features[$row['id']] as $feature) {
            $subtotal += (float)$feature['total_price'];
        }
    }
  }
}
$discount_amount = (float)($quotation['discount_amount'] ?? 0);
$discount_pct    = isset($quotation['discount_percentage']) ? (float)$quotation['discount_percentage'] : 0;
$grand_total     = (float)($quotation['grand_total'] ?: ($quotation['total_amount'] ?: ($subtotal - $discount_amount)));
if ($grand_total <= 0) $grand_total = max(0, $subtotal - $discount_amount);

/* ---------- Company ---------- */
$company = getCompanyDetails() ?: [];

/* ---------- PDF noise suppression ---------- */
if ($is_pdf_mode) { ini_set('display_errors','0'); error_reporting(E_ALL); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Quotation — <?php echo e($quotation['quotation_number']); ?></title>
<style>
<?php echo getPrintStyles(); ?>
</style>
</head>
<body>

<?php if(!$is_pdf_mode): ?>
  <?php echo getPrintNavigation('../quotations/quotations.php', $quotation_id); ?>
<?php endif; ?>

<div class="wrapper">

  <?php echo getPrintHeader($company, 'QUOTATION', $quotation['quotation_number'], $quotation['status']); ?>

  <!-- Bill To / Quotation Details -->
  <table style="margin-top:6mm">
    <tr>
      <td width="50%" valign="top">
        <h2>Bill To</h2>
        <table class="kv">
          <tr><td>Customer</td><td><?php echo e($quotation['company_name'] ?? ''); ?></td></tr>
          <?php if (!empty($quotation['contact_person'])): ?>
          <tr><td>Contact</td><td><?php echo e($quotation['contact_person']); ?></td></tr>
          <?php endif; ?>
          <?php
            $parts=[]; if(!empty($quotation['address'])) $parts[]=$quotation['address'];
            if(!empty($quotation['city'])) $parts[]=$quotation['city'];
            if(!empty($quotation['state'])) $parts[]=$quotation['state'];
            $loc = implode(', ', $parts);
          ?>
          <?php if ($loc): ?><tr><td>Location</td><td><?php echo e($loc); ?></td></tr><?php endif; ?>
          <?php if (!empty($quotation['phone'])): ?><tr><td>Phone</td><td><?php echo e($quotation['phone']); ?></td></tr><?php endif; ?>
          <?php if (!empty($quotation['email'])): ?><tr><td>Email</td><td><?php echo e($quotation['email']); ?></td></tr><?php endif; ?>
          <?php if (!empty($quotation['gst_no'])): ?><tr><td>GSTIN</td><td><?php echo e($quotation['gst_no']); ?></td></tr><?php endif; ?>
        </table>
      </td>
      <td width="50%" valign="top">
        <h2>Quotation Details</h2>
        <table class="kv">
          <tr><td>Quotation No.</td><td><?php echo e($quotation['quotation_number'] ?? ''); ?></td></tr>
          <tr><td>Date</td><td><?php echo !empty($quotation['quotation_date']) ? date('d.m.Y', strtotime($quotation['quotation_date'])) : ''; ?></td></tr>
          <tr><td>Valid Until</td><td><?php echo !empty($quotation['valid_until']) ? date('d.m.Y', strtotime($quotation['valid_until'])) : ''; ?></td></tr>
          <?php if (!empty($quotation['enquiry_ref'])): ?><tr><td>Enquiry Ref.</td><td><?php echo e($quotation['enquiry_ref']); ?></td></tr><?php endif; ?>
          <?php if (!empty($quotation['prepared_by'])): ?><tr><td>Prepared By</td><td><?php echo e($quotation['prepared_by']); ?></td></tr><?php endif; ?>
        </table>
      </td>
    </tr>
  </table>

  <!-- Company Information -->
  <?php echo getCompanyInfoSection($company); ?>

  <!-- Items -->
  <h2>Bill of Quantity & Prices</h2>
  <table class="items" aria-label="Quotation Items">
    <thead>
      <tr>
        <th width="40">Sl.</th>
        <th>Description</th>
        <th width="100" class="num">Unit Price</th>
        <th width="60"  class="num">Qty</th>
        <th width="120" class="num">Line Total</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($items) === 0): ?>
        <tr><td colspan="5" style="text-align:center;color:#666">No items</td></tr>
      <?php else:
        foreach ($items as $it): ?>
        <tr>
          <td><?php echo (int)($it['sl_no'] ?? 0); ?></td>
          <td>
            <strong><?php echo e($it['item_name'] ?? ''); ?></strong>
            <?php if (!empty($it['item_code'])): ?>
              <div><small>Code: <?php echo e($it['item_code']); ?></small></div>
            <?php endif; ?>
            <?php if (!empty($it['description'])): ?>
              <div><small><?php echo nl2br(e($it['description'])); ?></small></div>
            <?php endif; ?>
          </td>
          <td class="num"><?php echo fmt_money($it['unit_price']); ?></td>
          <td class="num"><?php echo rtrim(rtrim(number_format((float)$it['quantity'],2,'.',''), '0'), '.'); ?></td>
          <td class="num"><?php echo fmt_money($it['total_price']); ?></td>
        </tr>
        
        <?php 
        // Display machine features if this is a machine item
        if ($it['item_type'] === 'machine' && isset($machine_features[$it['id']])): 
          foreach ($machine_features[$it['id']] as $feature): ?>
        <tr style="background-color: #f8f9fa;">
          <td></td>
          <td style="padding-left: 20px;">
            <small><em>— <?php echo e($feature['feature_name']); ?></em></small>
          </td>
          <td class="num"><small><?php echo fmt_money($feature['price']); ?></small></td>
          <td class="num"><small><?php echo rtrim(rtrim(number_format((float)$feature['quantity'],2,'.',''), '0'), '.'); ?></small></td>
          <td class="num"><small><?php echo fmt_money($feature['total_price']); ?></small></td>
        </tr>
        <?php endforeach; endif; ?>
        
      <?php endforeach; endif; ?>
    </tbody>
  </table>

  <!-- Totals -->
  <table style="margin-top:4mm">
    <tr>
      <td></td>
      <td width="320" valign="top">
        <table class="kv">
          <tr><td>Sub Total</td><td class="num"><?php echo fmt_money($subtotal); ?></td></tr>
          <?php if ($discount_amount > 0): ?>
          <tr>
            <td>Discount<?php echo $discount_pct > 0 ? ' ('.rtrim(rtrim(number_format($discount_pct,2,'.',''),'0'),'.').'%)' : ''; ?></td>
            <td class="num">- <?php echo fmt_money($discount_amount); ?></td>
          </tr>
          <?php endif; ?>
          <tr><td><b>Total</b></td><td class="num"><b><?php echo fmt_money($grand_total); ?></b></td></tr>
        </table>
        <?php if (!empty($quotation['notes'])): ?>
          <div class="note" style="margin-top:3mm"><b>Note:</b> <?php echo nl2br(e($quotation['notes'])); ?></div>
        <?php endif; ?>
      </td>
    </tr>
  </table>

  <!-- Terms & Conditions -->
  <?php echo getTermsAndConditions('quotation'); ?>

  <!-- Footer -->
  <?php echo getPrintFooter($company); ?>

</div>
</body>
</html>
