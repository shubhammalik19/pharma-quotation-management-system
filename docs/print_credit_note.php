<?php
// docs/print_credit_note.php (PDF-safe table layout)
require_once '../common/conn.php';
require_once '../common/functions.php';
require_once '../common/print_common.php';

if (!isset($_GET['pdf'])) {
    checkLogin();
}
if (!isset($_SESSION['user_id'])) die('Access denied');
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    logDocumentActivity('credit_notes', "Invalid credit note ID access attempt");
    die('Invalid credit note ID');
}
$cn_id = (int)$_GET['id'];

logDocumentActivity('credit_notes', "Accessing credit note print", $cn_id);

$sql = "SELECT cn.*, c.email as customer_email, c.phone as customer_phone,
               u.full_name as created_by_name
        FROM credit_notes cn
        LEFT JOIN customers c ON cn.customer_id = c.id
        LEFT JOIN users u ON cn.created_by = u.id
        WHERE cn.id = $cn_id";
$res = $conn->query($sql);
if (!$res || $res->num_rows === 0) {
    logDocumentActivity('credit_notes', "Credit note not found", $cn_id);
    die('Credit note not found');
}
$cn = $res->fetch_assoc();

logDocumentActivity('credit_notes', "Credit note print generated successfully", $cn_id);

$company = getCompanyDetails();
$bank    = getBankDetails();
$amount_in_words = getAmountInWords((float)($cn['total_amount'] ?? 0));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Credit Note — <?php echo htmlspecialchars($cn['credit_note_number']); ?></title>
<style>
body{font:14px/1.45 "DejaVu Sans",Arial,sans-serif;color:#222;margin:0}
.wrapper{max-width:800px;margin:0 auto;padding:10mm}
h1{font-size:18px;margin:0 0 3mm}
h2{font-size:15px;margin:6mm 0 3mm;color:#1d4ed8}
.small{color:#666;font-size:12px}
table{width:100%;border-collapse:collapse}
td,th{padding:6px 6px;vertical-align:top}
.kv td{border:1px solid #d1d5db}
.kv td:first-child{background:#f9fafb;width:160px;font-weight:bold}
.hr{height:1px;background:#e5e7eb;margin:6mm 0}
.items thead th{border-bottom:1px solid #374151;background:#f3f4f6;font-size:12px;text-align:left}
.items td{border-bottom:1px solid #e5e7eb}
.num{text-align:right;white-space:nowrap}
.badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;color:#fff}
.badge-issued{background:#16a34a}
.badge-other{background:#6b7280}
.box{border:1px solid #d1d5db;padding:6mm}
.footer{font-size:12px;border-top:1px solid #374151;margin-top:6mm;padding-top:2mm;color:#444}
@page { size:A4; margin:12mm }
thead { display: table-header-group; }
tfoot { display: table-row-group; }
tr { page-break-inside: avoid; }
@media print { .no-print { display:none !important; } .wrapper{max-width:100%;padding:0} }
</style>
</head>
<body>
<div class="wrapper">

  <!-- Header -->
  <table>
    <tr>
      <td width="72">
        <div style="width:60px;height:60px;background:#2563eb;color:#fff;text-align:center;line-height:60px;font-weight:bold;border-radius:6px">CN</div>
      </td>
      <td>
        <h1>Credit Note — <?php echo htmlspecialchars($cn['credit_note_number']); ?></h1>
        <div class="small">
          <?php echo htmlspecialchars($company['address'] ?? ''); ?><br>
          GSTIN: <?php echo htmlspecialchars($company['gstin'] ?? ($company['gst'] ?? '')); ?> · State: <?php echo htmlspecialchars($company['state'] ?? ''); ?> · CIN: <?php echo htmlspecialchars($company['cin'] ?? ''); ?><br>
          <?php echo htmlspecialchars($company['phone'] ?? ''); ?> · <?php echo htmlspecialchars($company['email'] ?? ''); ?>
        </div>
      </td>
      <td width="140" class="box" style="text-align:center;font-weight:700">CREDIT NOTE</td>
    </tr>
  </table>

  <!-- Two columns -->
  <table style="margin-top:6mm">
    <tr>
      <td width="50%" valign="top">
        <h2>Credit To</h2>
        <table class="kv">
          <tr><td>Name</td><td><?php echo htmlspecialchars($cn['customer_name'] ?? ''); ?></td></tr>
          <?php if (!empty($cn['customer_address'])): ?>
          <tr><td>Address</td><td><?php echo nl2br(htmlspecialchars($cn['customer_address'])); ?></td></tr>
          <?php endif; ?>
          <?php if (!empty($cn['customer_gstin'])): ?>
          <tr><td>GSTIN</td><td><?php echo htmlspecialchars($cn['customer_gstin']); ?></td></tr>
          <?php endif; ?>
          <?php if (!empty($cn['customer_phone']) || !empty($cn['customer_email'])): ?>
          <tr><td>Contact</td><td><?php echo htmlspecialchars($cn['customer_phone'] ?: $cn['customer_email']); ?></td></tr>
          <?php endif; ?>
        </table>
      </td>
      <td width="50%" valign="top">
        <h2>Credit Note Details</h2>
        <table class="kv">
          <tr><td>Credit Note No.</td><td><?php echo htmlspecialchars($cn['credit_note_number'] ?? ''); ?></td></tr>
          <tr><td>Date</td><td><?php echo !empty($cn['credit_date']) ? date('d.m.Y', strtotime($cn['credit_date'])) : ''; ?></td></tr>
          <?php if (!empty($cn['original_invoice'])): ?>
          <tr><td>Original Invoice</td><td><?php echo htmlspecialchars($cn['original_invoice']); ?></td></tr>
          <?php endif; ?>
          <tr>
            <td>Status</td>
            <td>
              <?php
                $isIssued = strtolower((string)($cn['status'] ?? '')) === 'issued';
                $cls = $isIssued ? 'badge-issued' : 'badge-other';
              ?>
              <span class="badge <?php echo $cls; ?>"><?php echo ucwords(htmlspecialchars($cn['status'] ?? '')); ?></span>
            </td>
          </tr>
          <tr><td>Created By</td><td><?php echo htmlspecialchars($cn['created_by_name'] ?? 'System'); ?></td></tr>
        </table>
      </td>
    </tr>
  </table>

  <!-- Amount -->
  <h2>Credit Amount</h2>
  <table class="kv" style="width:60%;margin-top:2mm">
    <tr><td>Amount</td><td class="num">₹ <?php echo number_format((float)($cn['total_amount'] ?? 0), 2); ?></td></tr>
    <tr><td>Amount in words</td><td><?php echo htmlspecialchars($amount_in_words); ?></td></tr>
  </table>

  <!-- Reason -->
  <h2>Reason for Credit</h2>
  <table class="kv">
    <tr><td>Reason</td><td><?php echo nl2br(htmlspecialchars($cn['reason'] ?? '')); ?></td></tr>
  </table>

  <!-- Bank + Declaration -->
  <table style="margin-top:6mm">
    <tr>
      <td width="50%" valign="top">
        <h2>Bank Details</h2>
        <table class="kv">
          <tr><td>Bank</td><td><?php echo htmlspecialchars($bank['bank_name'] ?? ''); ?></td></tr>
          <tr><td>Account No.</td><td><?php echo htmlspecialchars($bank['account_number'] ?? ''); ?></td></tr>
          <tr><td>IFSC</td><td><?php echo htmlspecialchars($bank['ifsc'] ?? ''); ?></td></tr>
          <tr><td>Beneficiary</td><td><?php echo htmlspecialchars($bank['beneficiary'] ?? ''); ?></td></tr>
        </table>
      </td>
      <td width="50%" valign="top">
        <h2>Declaration</h2>
        <div class="box">
          We declare that this credit note reflects the correct credit against the referenced transaction. All particulars are true and correct. Subject to Ahmedabad jurisdiction.
        </div>
      </td>
    </tr>
  </table>

  <!-- Signatures -->
  <table style="margin-top:10mm">
    <tr>
      <td width="50%" style="text-align:center;border-top:1px solid #9ca3af;padding-top:6px">Customer Signature</td>
      <td width="50%" style="text-align:center;border-top:1px solid #9ca3af;padding-top:6px">
        Authorized Signatory<br><span class="small"><?php echo htmlspecialchars($company['name'] ?? ''); ?></span>
      </td>
    </tr>
  </table>

  <!-- Footer -->
  <div class="footer">
    This is a system-generated Credit Note. For queries, contact <?php echo htmlspecialchars($company['email'] ?? ''); ?>.
    Generated on <?php echo date('d/m/Y H:i:s'); ?> | Credit Note ID: <?php echo (int)$cn_id; ?>
  </div>

</div>
</body>
</html>
