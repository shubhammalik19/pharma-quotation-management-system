<?php
// docs/print_debit_note.php
require_once '../common/conn.php';
require_once '../common/functions.php';

if (!isset($_SESSION['user_id'])) {
    die('Access denied');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid debit note ID');
}

$dn_id = (int)$_GET['id'];

// Get debit note details
$dn_sql = "SELECT dn.*, u.full_name as created_by_name
           FROM debit_notes dn
           LEFT JOIN users u ON dn.created_by = u.id
           WHERE dn.id = $dn_id";

$result = $conn->query($dn_sql);

if ($result->num_rows === 0) {
    die('Debit note not found');
}

$debit_note = $result->fetch_assoc();

// Get company and bank details from common functions
$company = getCompanyDetails();
$bank = getBankDetails();
$amount_in_words = getAmountInWords($debit_note['total_amount']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Debit Note — <?php echo htmlspecialchars($debit_note['debit_note_number']); ?></title>
  <style>
    :root{
      --ink:#111827;--muted:#6b7280;--line:#e5e7eb;--brand:#dc3545;--bg:#ffffff
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

    .amount-section{border:1px solid var(--line);border-radius:10px;padding:20px;margin:16px 0;text-align:center;background:linear-gradient(135deg, #dc3545 0%, #c82333 100%);color:white}
    .amount-label{font-size:18px;margin-bottom:5px;opacity:0.9}
    .amount-value{font-size:32px;font-weight:bold;margin-bottom:10px}
    .amount-words{font-size:14px;opacity:0.9}

    .reason-section{border:1px solid var(--line);border-radius:10px;padding:16px;margin:16px 0;background:#f8f9fa}
    .reason-section h3{margin:0 0 12px;font-size:15px;color:var(--brand);font-weight:bold}
    .reason-text{font-size:14px;line-height:1.6;margin:0}

    .terms-section{margin:16px 0}
    .terms-section h3{margin:0 0 12px;font-size:15px;color:var(--brand);font-weight:bold}
    .terms-list{margin:0;padding-left:20px;font-size:13px;line-height:1.5}
    .terms-list li{margin:4px 0}

    .signatures{display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:40px}
    .signature-box{text-align:center;border-top:1px solid var(--ink);padding-top:10px}
    .signature-label{font-weight:bold;font-size:14px}
    .signature-company{font-size:12px;color:var(--muted);margin-top:4px}

    .note{margin-top:8px;font-size:12px;color:var(--muted)}
    .sign{margin-top:24px;display:flex;justify-content:flex-end}
    .sign .box{border-top:1px solid var(--line);padding-top:8px;min-width:240px;text-align:center;font-size:12px;color:var(--muted)}

    .footer-section{margin-top:40px;padding-top:20px;border-top:1px solid var(--line);text-align:center;font-size:12px;color:var(--muted)}

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
      <div class="tag">Debit Note</div>
    </header>

    <div class="grid">
      <section class="panel">
        <h3>Debit To</h3>
        <div class="kv">
          <div class="key">Vendor Name</div><div><?php echo htmlspecialchars($debit_note['vendor_name']); ?></div>
          <?php if (!empty($debit_note['vendor_address'])): ?>
          <div class="key">Address</div><div><?php echo htmlspecialchars($debit_note['vendor_address']); ?></div>
          <?php endif; ?>
          <?php if (!empty($debit_note['vendor_gstin'])): ?>
          <div class="key">GSTIN</div><div><?php echo htmlspecialchars($debit_note['vendor_gstin']); ?></div>
          <?php endif; ?>
          <div class="key">State</div><div><?php echo $company['state']; ?></div>
        </div>
      </section>
      <section class="panel">
        <h3>Debit Note Details</h3>
        <div class="kv">
          <div class="key">Debit Note No.</div><div><?php echo htmlspecialchars($debit_note['debit_note_number']); ?></div>
          <div class="key">Date</div><div><?php echo date('d/m/Y', strtotime($debit_note['debit_date'])); ?></div>
          <?php if (!empty($debit_note['original_invoice'])): ?>
          <div class="key">Original Invoice</div><div><?php echo htmlspecialchars($debit_note['original_invoice']); ?></div>
          <?php endif; ?>
          <div class="key">Status</div><div><span style="background:<?php echo $debit_note['status'] == 'issued' ? '#28a745' : '#6c757d'; ?>;color:white;padding:2px 8px;border-radius:4px;font-size:11px;"><?php echo ucwords($debit_note['status']); ?></span></div>
          <div class="key">Created By</div><div><?php echo htmlspecialchars($debit_note['created_by_name'] ?: 'System'); ?></div>
        </div>
      </section>
    </div>

    <!-- Debit Amount Section -->
    <section class="amount-section">
      <div class="amount-label">DEBIT AMOUNT</div>
      <div class="amount-value">₹ <?php echo number_format($debit_note['total_amount'], 2); ?></div>
      <div class="amount-words"><?php echo $amount_in_words; ?></div>
    </section>

    <!-- Reason for Debit -->
    <section class="reason-section">
      <h3>Reason for Debit</h3>
      <p class="reason-text"><?php echo nl2br(htmlspecialchars($debit_note['reason'])); ?></p>
    </section>

    <!-- Terms & Conditions -->
    <section class="terms-section">
      <h3>Terms & Conditions</h3>
      <ul class="terms-list">
        <li>This debit note is issued against the original invoice/transaction mentioned above.</li>
        <li>The debit amount shall be adjusted against future transactions or invoices.</li>
        <li>This debit note is valid for 90 days from the date of issue.</li>
        <li>Any disputes regarding this debit note should be raised within 7 days of receipt.</li>
        <li>This is a system-generated debit note and does not require physical signature.</li>
        <li>All disputes are subject to Ahmedabad jurisdiction only.</li>
      </ul>
    </section>

    <div class="grid" style="margin-top:24px;">
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
        <h3>Declaration</h3>
        <p class="note">We declare that this debit note shows the actual debit amount and that all particulars are true and correct. Subject to Ahmedabad jurisdiction.</p>
        <p class="note" style="margin-top:12px;"><strong>Note:</strong> This debit note represents an additional charge to the vendor for the reasons specified above.</p>
      </section>
    </div>

    <!-- Signatures -->
    <div class="signatures">
      <div class="signature-box">
        <div class="signature-label">Vendor Acknowledgment</div>
      </div>
      <div class="signature-box">
        <div class="signature-label">Authorized Signatory</div>
        <div class="signature-company"><?php echo $company['name']; ?></div>
      </div>
    </div>

    <!-- Footer -->
    <div class="footer-section">
      <p><strong>This is a system-generated Debit Note</strong></p>
      <p>For any queries regarding this debit note, please contact us at <?php echo $company['email']; ?></p>
      <p>Generated on <?php echo date('d/m/Y H:i:s'); ?> | Debit Note ID: <?php echo $dn_id; ?></p>
    </div>

    <!-- Print Controls -->
    <div class="no-print" style="margin-top:24px;text-align:center;border-top:1px solid var(--line);padding-top:16px;">
      <button onclick="window.print()" style="background:var(--brand);color:white;border:none;padding:10px 20px;border-radius:6px;cursor:pointer;margin-right:10px;">Print Debit Note</button>
      <button onclick="window.close()" style="background:var(--muted);color:white;border:none;padding:10px 20px;border-radius:6px;cursor:pointer;">Close</button>
    </div>
  </article>
</body>
</html>
