<?php
require_once __DIR__ . '/../common/conn.php';
require_once __DIR__ . '/../common/functions.php';
require_once __DIR__ . '/../common/print_common.php';

if (!isset($_GET['pdf'])) {
    checkLogin();
}

// Inputs
$so_id       = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_pdf_mode = isset($_GET['pdf']) && $_GET['pdf'] == '1';
if (!$so_id) {
    logDocumentActivity('sales_orders', "Invalid sales order ID access attempt");
    die('Invalid sales order ID');
}

logDocumentActivity('sales_orders', "Accessing sales order print", $so_id);

// Fetch Sales Order + Customer
$so_sql = "SELECT so.*,
           c.company_name   AS customer_company_name,
           c.contact_person AS customer_contact_person,
           c.phone          AS customer_phone,
           c.email          AS customer_email,
           c.address        AS customer_full_address,
           c.city           AS customer_city,
           c.state          AS customer_state,
           c.gst_no         AS customer_gstin_number,
           c.pincode        AS customer_pincode
           FROM sales_orders so
           LEFT JOIN customers c ON so.customer_id = c.id
           WHERE so.id = $so_id";
$so_result = $conn->query($so_sql);
if (!$so_result || $so_result->num_rows === 0) {
    logDocumentActivity('sales_orders', "Sales order not found", $so_id);
    die('Sales Order not found');
}
$sales_order = $so_result->fetch_assoc();

logDocumentActivity('sales_orders', "Sales order print generated successfully", $so_id);

// Items
$items_sql = "SELECT soi.*,
              CASE WHEN soi.item_type='machine' THEN m.name
                   WHEN soi.item_type='spare'   THEN s.part_name END AS item_name,
              CASE WHEN soi.item_type='machine' THEN m.model
                   WHEN soi.item_type='spare'   THEN s.part_code END AS item_code
              FROM sales_order_items soi
              LEFT JOIN machines m ON soi.item_type='machine' AND soi.item_id=m.id
              LEFT JOIN spares   s ON soi.item_type='spare'   AND soi.item_id=s.id
              WHERE soi.so_id = $so_id
              ORDER BY soi.id";
$items_result = $conn->query($items_sql);

// Company + Bank
$company = getCompanyDetails();
$bank    = getBankDetails();

// Totals
$subtotal = 0.0; $items=[];
if ($items_result) {
  while ($row = $items_result->fetch_assoc()) {
    $row['unit_price']  = (float)($row['unit_price'] ?? 0);
    $row['quantity']    = (float)($row['quantity'] ?? 0);
    $row['total_price'] = (float)($row['total_price'] ?? ($row['unit_price'] * $row['quantity']));
    $items[] = $row;
    $subtotal += $row['total_price'];
  }
}
$gst_rate   = (float)($sales_order['gst_rate'] ?? 18);
$gst_amount = ($subtotal * $gst_rate) / 100;
$discount_amount = (float)($sales_order['discount_amount'] ?? 0);
$final_total     = $subtotal + $gst_amount - $discount_amount;

// Amount in words
$amount_in_words = numberToWords($final_total) . ' Rupees Only';

// Show HSN col?
$show_hsn=false; foreach($items as $it){ if(!empty($it['hsn_code'])){$show_hsn=true;break;} }

// Suppress warnings in PDF
if ($is_pdf_mode){ ini_set('display_errors','0'); error_reporting(E_ALL); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?php echo e($company['name'] ?? ''); ?> — Sales Order <?php echo e($sales_order['so_number'] ?? ''); ?></title>
<style>
<?php echo getPrintStyles(); ?>
</style>
</head>
<body>

<?php if(!$is_pdf_mode): ?>
<?php echo getPrintNavigation('../sales/sales_orders.php', $so_id); ?>
<?php endif; ?>

<div class="wrapper">

  <?php echo getPrintHeader($company, 'SALES ORDER', $sales_order['so_number'], $sales_order['status']); ?>

  <!-- Bill To / Order Details -->
  <table style="margin-top:6mm">
    <tr>
      <td width="50%" valign="top">
        <h2>Bill To</h2>
        <table class="kv">
          <tr><td>Name</td><td><?php echo htmlspecialchars($sales_order['customer_name'] ?? $sales_order['customer_company_name'] ?? ''); ?></td></tr>
          <?php
            $addr = $sales_order['customer_address'] ?? $sales_order['customer_full_address'] ?? '';
            $parts=[]; if($addr) $parts[]=$addr;
            if(!empty($sales_order['customer_city'])) $parts[]=$sales_order['customer_city'];
            if(!empty($sales_order['customer_state'])) $parts[]=$sales_order['customer_state'];
            if(!empty($sales_order['customer_pincode'])) $parts[]=$sales_order['customer_pincode'];
            $addr_str=implode(', ',$parts);
          ?>
          <?php if($addr_str): ?><tr><td>Address</td><td><?php echo htmlspecialchars($addr_str); ?></td></tr><?php endif; ?>
          <?php if(!empty($sales_order['customer_contact'])): ?>
          <tr><td>Contact</td><td><?php echo htmlspecialchars($sales_order['customer_contact']); ?></td></tr>
          <?php elseif(!empty($sales_order['customer_phone'])): ?>
          <tr><td>Contact</td><td><?php echo htmlspecialchars($sales_order['customer_phone']); ?></td></tr>
          <?php endif; ?>
          <?php $gstin=$sales_order['customer_gstin'] ?? $sales_order['customer_gstin_number'] ?? ''; if($gstin): ?>
          <tr><td>GSTIN</td><td><?php echo htmlspecialchars($gstin); ?></td></tr>
          <?php endif; ?>
          <?php if(!empty($sales_order['customer_state'])): ?>
          <tr><td>State</td><td><?php echo htmlspecialchars($sales_order['customer_state']); ?></td></tr>
          <?php endif; ?>
        </table>
      </td>
      <td width="50%" valign="top">
        <h2>Order Details</h2>
        <table class="kv">
          <tr><td>SO Number</td><td><?php echo htmlspecialchars($sales_order['so_number'] ?? ''); ?></td></tr>
          <tr><td>Date</td><td><?php echo !empty($sales_order['so_date']) ? date('d.m.Y',strtotime($sales_order['so_date'])) : ''; ?></td></tr>
          <?php if(!empty($sales_order['delivery_date'])): ?>
          <tr><td>Delivery Date</td><td><?php echo date('d.m.Y',strtotime($sales_order['delivery_date'])); ?></td></tr>
          <?php endif; ?>
          <tr><td>Status</td><td><?php echo htmlspecialchars(ucfirst($sales_order['status'] ?? '')); ?></td></tr>
          <?php if(!empty($sales_order['quotation_number'])): ?>
          <tr><td>Quotation No.</td><td><?php echo htmlspecialchars($sales_order['quotation_number']); ?></td></tr>
          <?php endif; ?>
        </table>
      </td>
    </tr>
  </table>

  <!-- Items -->
  <h2>Items</h2>
  <table class="items">
    <thead>
      <tr>
        <th width="32">#</th>
        <th>Description</th>
        <?php if($show_hsn): ?><th width="90">HSN/SAC</th><?php endif; ?>
        <th width="60" class="num">Qty</th>
        <th width="60">Unit</th>
        <th width="100" class="num">Unit Price</th>
        <th width="110" class="num">Amount</th>
      </tr>
    </thead>
    <tbody>
    <?php
    if(count($items)===0){
      echo '<tr><td colspan="'.($show_hsn?7:6).'" style="text-align:center;color:#666">No items</td></tr>';
    } else {
      $i=1;
      foreach($items as $it): ?>
      <tr>
        <td><?php echo (int)($it['sl_no'] ?? $i); ?></td>
        <td>
          <strong><?php echo htmlspecialchars($it['item_name'] ?? ''); ?></strong>
          <?php if(!empty($it['item_code'])): ?><div><small>Code: <?php echo htmlspecialchars($it['item_code']); ?></small></div><?php endif; ?>
          <?php if(!empty($it['description'])): ?><div><small><?php echo nl2br(htmlspecialchars($it['description'])); ?></small></div><?php endif; ?>
        </td>
        <?php if($show_hsn): ?><td><?php echo htmlspecialchars($it['hsn_code'] ?? ''); ?></td><?php endif; ?>
        <td class="num"><?php echo rtrim(rtrim(number_format((float)$it['quantity'],2,'.',''),'0'),'.'); ?></td>
        <td><?php echo e($it['unit'] ?? 'Nos'); ?></td>
        <td class="num"><?php echo fmt_money($it['unit_price']); ?></td>
        <td class="num"><?php echo fmt_money($it['total_price']); ?></td>
      </tr>
    <?php $i++; endforeach; } ?>
    </tbody>
  </table>

  <!-- Totals -->
  <table style="margin-top:4mm">
    <tr>
      <td></td>
      <td width="320" valign="top">
        <table class="kv">
          <tr><td>Sub Total</td><td class="num"><?php echo fmt_money($subtotal); ?></td></tr>
          <?php if($gst_rate>0): ?>
          <tr><td>IGST @ <?php echo rtrim(rtrim(number_format($gst_rate,2), '0'), '.'); ?>%</td><td class="num"><?php echo fmt_money($gst_amount); ?></td></tr>
          <?php endif; ?>
          <?php if($discount_amount>0): ?>
          <tr><td>Discount<?php echo !empty($sales_order['discount_percentage'])?' ('.number_format((float)$sales_order['discount_percentage'],1).'%)':''; ?></td><td class="num">- <?php echo fmt_money($discount_amount); ?></td></tr>
          <?php endif; ?>
          <tr><td><b>Total</b></td><td class="num"><b><?php echo fmt_money($final_total); ?></b></td></tr>
        </table>
        <div class="note" style="margin-top:3mm"><b>Amount in words:</b> <?php echo e($amount_in_words); ?></div>
      </td>
    </tr>
  </table>

  <!-- Notes -->
  <?php if(!empty($sales_order['notes'])): ?>
  <h2>Notes</h2>
  <div class="note"><?php echo nl2br(e($sales_order['notes'])); ?></div>
  <?php endif; ?>

  <?php echo getTermsAndConditions('sales_order'); ?>

  <!-- Signature -->
  <table style="margin-top:10mm">
    <tr>
      <td width="60%"></td>
      <td width="40%" align="center" style="padding-top:36px;border-top:1px solid #888;font-weight:bold">
        For: <?php echo e($company['name'] ?? ''); ?><br><br>Authorised Signatory
      </td>
    </tr>
  </table>

  <?php echo getPrintFooter($company); ?>

</div>
</body>
</html>
