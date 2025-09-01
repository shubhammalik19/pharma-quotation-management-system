<?php
include '../common/conn.php';
include '../common/functions.php';

// Check if user is logged in
checkLogin();

// Get quotation ID from URL
$quotation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_pdf_mode = isset($_GET['pdf']) && $_GET['pdf'] == '1';

if (!$quotation_id) {
    die('Invalid quotation ID');
}

// Get quotation details with customer information
$quotation_sql = "SELECT q.*, c.company_name, c.contact_person, c.phone, c.email, c.address, c.city, c.state, c.gst_no 
                  FROM quotations q 
                  LEFT JOIN customers c ON q.customer_id = c.id 
                  WHERE q.id = $quotation_id";
$quotation_result = $conn->query($quotation_sql);

if ($quotation_result->num_rows === 0) {
    die('Quotation not found');
}

$quotation = $quotation_result->fetch_assoc();

// Get quotation items
$items_sql = "SELECT qi.*, 
              CASE 
                WHEN qi.item_type = 'machine' THEN m.name 
                WHEN qi.item_type = 'spare' THEN s.part_name 
              END as item_name,
              CASE 
                WHEN qi.item_type = 'machine' THEN m.model 
                WHEN qi.item_type = 'spare' THEN s.part_code 
              END as item_code
              FROM quotation_items qi 
              LEFT JOIN machines m ON qi.item_type = 'machine' AND qi.item_id = m.id
              LEFT JOIN spares s ON qi.item_type = 'spare' AND qi.item_id = s.id
              WHERE qi.quotation_id = $quotation_id 
              ORDER BY qi.sl_no";
$items_result = $conn->query($items_sql);

// Get company and bank details from common functions
$company_info = getCompanyDetails();
$bank_details = getBankDetails();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Techno‑Commercial Offer – <?php echo htmlspecialchars($quotation['quotation_number']); ?></title>
  <style>
    :root{
      --brand:#0f6abf;
      --ink:#1b1f23;
      --muted:#5f6b7a;
      --border:#e5e7eb;
      --bg:#ffffff;
      --accent:#eef6ff;
    }
    *{box-sizing:border-box}
    html,body{margin:0;background:var(--bg);color:var(--ink);font:15px/1.5 system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,sans-serif}
    .sheet{max-width:900px;margin:24px auto;padding:28px;border:1px solid var(--border);border-radius:16px;box-shadow:0 2px 30px rgba(0,0,0,.06)}
    header{display:flex;gap:16px;align-items:flex-start;border-bottom:2px solid var(--border);padding-bottom:16px;margin-bottom:18px}
    .brandmark{width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,var(--brand),#6fb1ff);display:grid;place-items:center;color:#fff;font-weight:700}
    h1{font-size:22px;line-height:1.2;margin:0 0 6px}
    .subtitle{color:var(--muted);font-size:13px}
    .tag{display:inline-block;padding:2px 10px;border-radius:999px;background:var(--accent);color:var(--brand);font-weight:600;font-size:12px}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}
    .card{border:1px solid var(--border);border-radius:14px;padding:14px}
    .card h3{margin:.2rem 0 .6rem;font-size:15px;color:var(--brand)}
    .kv{display:grid;grid-template-columns:140px 1fr;gap:8px 12px}
    .kv div{padding:2px 0}
    .kv label{color:var(--muted)}
    table{width:100%;border-collapse:separate;border-spacing:0;margin:6px 0 0}
    th,td{padding:10px 12px}
    thead th{font-size:13px;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;text-align:left;border-bottom:2px solid var(--border)}
    tbody td{border-bottom:1px solid var(--border);vertical-align:top}
    tbody tr:last-child td{border-bottom:0}
    .num{text-align:right;white-space:nowrap}
    .muted{color:var(--muted)}
    .section{margin-top:26px}
    .section h2{font-size:18px;margin:0 0 8px;color:var(--brand)}
    .bullets{margin:8px 0 0 0;padding-left:18px}
    .bullets li{margin:4px 0}
    .note{background:var(--accent);border:1px dashed #b8dbff;color:#074a86;border-radius:12px;padding:10px 12px;font-size:13px}
    footer{margin-top:22px;padding-top:14px;border-top:1px dashed var(--border);color:var(--muted);font-size:13px}
    .price-total{display:flex;justify-content:flex-end;margin-top:6px}
    .price-total .wrap{min-width:320px;border:1px solid var(--border);border-radius:12px;padding:10px 14px}
    .price-total .wrap div{display:flex;justify-content:space-between;padding:6px 0}
    .price-total .wrap div:last-child{font-size:18px;font-weight:800;color:var(--ink);border-top:1px dashed var(--border);margin-top:6px}
    .chip{display:inline-block;border:1px solid var(--border);padding:2px 8px;border-radius:999px;font-size:12px;color:var(--muted)}
    .two-col{columns:2;column-gap:22px}
    .spec{break-inside:avoid;border:1px solid var(--border);border-radius:12px;padding:12px;margin:0 0 12px}
    .spec h4{margin:0 0 6px;color:#0d5aa0}
    .spec ul{margin:0;padding-left:18px}
    .spec li{margin:4px 0}

    /* Print controls */
    .print-controls{position:fixed;top:20px;right:20px;z-index:1000;display:flex;gap:10px}
    .print-btn{background:var(--brand);color:white;border:none;padding:12px 20px;border-radius:8px;cursor:pointer;font-size:14px;display:flex;align-items:center;gap:8px;text-decoration:none}
    .print-btn:hover{background:#0856a0}

    /* print */
    @media print{
      body{background:#fff}
      .sheet{box-shadow:none;border:0;margin:0;max-width:100%}
      header{border:0;padding-bottom:0;margin-bottom:8px}
      .tag{background:#dcebff}
      a[href]::after{content:""} /* hide link targets in print */
      .print-controls{display:none !important}
    }
  </style>
</head>
<body>
  <!-- Print Controls -->
  <?php if (!$is_pdf_mode): ?>
  <div class="print-controls">
    <button class="print-btn" onclick="window.print()">
      🖨️ Print Quotation
    </button>
    <button class="print-btn" onclick="generatePDF()" style="background:#28a745">
      📄 Generate PDF
    </button>
    <button class="print-btn" onclick="showEmailModal()" style="background:#17a2b8">
      📧 Email PDF
    </button>
    <a href="../quotations/quotations.php" class="print-btn" style="background:#6c757d">
      ← Back to List
    </a>
  </div>
  <?php endif; ?>

  <main class="sheet">
    <header>
      <div class="brandmark">PM</div>
      <div>
        <h1>Techno‑Commercial Offer — <?php echo htmlspecialchars($quotation['quotation_number']); ?> <span class="tag"><?php echo ucfirst($quotation['status']); ?></span></h1>
        <div class="subtitle"><?php echo htmlspecialchars($company_info['tagline']); ?></div>
      </div>
    </header>

    <section class="grid section">
      <div class="card">
        <h3>Bill To</h3>
        <div class="kv">
          <label>Customer</label><div><?php echo htmlspecialchars($quotation['company_name']); ?></div>
          <?php if ($quotation['contact_person']): ?>
          <label>Contact Person</label><div><?php echo htmlspecialchars($quotation['contact_person']); ?></div>
          <?php endif; ?>
          <?php if ($quotation['city'] && $quotation['state']): ?>
          <label>Location</label><div><?php echo htmlspecialchars($quotation['city'] . ', ' . $quotation['state']); ?></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="card">
        <h3>Quotation Details</h3>
        <div class="kv">
          <label>Quotation No.</label><div><?php echo htmlspecialchars($quotation['quotation_number']); ?></div>
          <label>Date</label><div><?php echo date('d.m.Y', strtotime($quotation['quotation_date'])); ?></div>
          <label>Valid Until</label><div><?php echo date('d.m.Y', strtotime($quotation['valid_until'])); ?></div>
          <label>Status</label><div><span class="chip"><?php echo ucfirst($quotation['status']); ?></span></div>
          <?php if ($quotation['enquiry_ref']): ?>
          <label>Enquiry Ref.</label><div><?php echo htmlspecialchars($quotation['enquiry_ref']); ?></div>
          <?php endif; ?>
          <?php if ($quotation['prepared_by']): ?>
          <label>Prepared By</label><div><?php echo htmlspecialchars($quotation['prepared_by']); ?></div>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="section">
      <h2>Company Information</h2>
      <div class="card">
        <div class="kv">
          <label>Corporate Office</label><div><?php echo htmlspecialchars($company_info['corporate_office']); ?></div>
          <label>Mfg. Unit</label><div><?php echo htmlspecialchars($company_info['manufacturing_unit']); ?></div>
          <label>CIN</label><div><?php echo htmlspecialchars($company_info['cin']); ?></div>
          <label>GST</label><div><?php echo htmlspecialchars($company_info['gst']); ?></div>
          <label>Contact</label><div><?php echo htmlspecialchars($company_info['contact']); ?></div>
        </div>
      </div>
    </section>

    <section class="section">
      <h2>Bill of Quantity &amp; Prices</h2>
      <table>
        <thead>
          <tr>
            <th style="width:60px">Sl.</th>
            <th>Description</th>
            <th class="num">Unit Price (INR)</th>
            <th class="num">Qty</th>
            <th class="num">Line Total (INR)</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $subtotal = 0;
          while ($item = $items_result->fetch_assoc()): 
            $subtotal += $item['total_price'];
          ?>
          <tr>
            <td><?php echo $item['sl_no']; ?></td>
            <td>
              <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
              <?php if ($item['item_code']): ?>
                <span class="chip"><?php echo htmlspecialchars($item['item_code']); ?></span>
              <?php endif; ?>
              <?php if ($item['description']): ?>
                <br/><span class="muted"><?php echo htmlspecialchars($item['description']); ?></span>
              <?php endif; ?>
            </td>
            <td class="num"><?php echo number_format($item['unit_price'], 2); ?></td>
            <td class="num"><?php echo $item['quantity']; ?></td>
            <td class="num"><?php echo number_format($item['total_price'], 2); ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <div class="price-total">
        <div class="wrap">
          <div><span class="muted">Sub‑Total</span><span class="num">₹ <?php echo number_format($subtotal, 2); ?></span></div>
          <?php if ($quotation['discount_amount'] > 0): ?>
          <div><span class="muted">Discount (<?php echo number_format($quotation['discount_percentage'], 1); ?>%)</span><span class="num">- ₹ <?php echo number_format($quotation['discount_amount'], 2); ?></span></div>
          <?php endif; ?>
          <div><span>Total</span><span class="num">₹ <?php echo number_format($quotation['grand_total'] ?: $quotation['total_amount'], 2); ?></span></div>
        </div>
      </div>
      <?php if ($quotation['notes']): ?>
      <div class="note" style="margin-top:10px">
        <?php echo nl2br(htmlspecialchars($quotation['notes'])); ?>
      </div>
      <?php endif; ?>
    </section>

    <section class="section">
      <h2>Terms &amp; Conditions</h2>
      <div class="two-col">
        <div class="spec">
          <h4>Payment Terms</h4>
          <ul>
            <li>30% advance with purchase order</li>
            <li>50% against material readiness</li>
            <li>20% against satisfactory installation & commissioning</li>
            <li>Payment through RTGS/NEFT only</li>
          </ul>
        </div>
        <div class="spec">
          <h4>Delivery & Installation</h4>
          <ul>
            <li>Delivery: 16-20 weeks from order confirmation</li>
            <li>Installation & commissioning included</li>
            <li>Training of customer personnel included</li>
            <li>One year comprehensive warranty</li>
          </ul>
        </div>
        <div class="spec">
          <h4>Technical Support</h4>
          <ul>
            <li>Detailed engineering drawings provided</li>
            <li>Operation & maintenance manual</li>
            <li>24/7 technical support helpline</li>
            <li>Annual maintenance contract available</li>
          </ul>
        </div>
        <div class="spec">
          <h4>Exclusions</h4>
          <ul>
            <li>Civil foundation work by customer</li>
            <li>Electrical supply up to control panel</li>
            <li>Utilities: compressed air, steam, water</li>
            <li>Any statutory approvals/NOCs</li>
          </ul>
        </div>
      </div>
      <div class="chip">This offer is valid for 30 days from the date of quotation. Prices are subject to change without notice.</div>
    </section>

    <footer>
      <?php echo strtoupper($company_info['name']); ?> · <?php echo $company_info['address']; ?> · CIN <?php echo $company_info['cin']; ?> · GST <?php echo $company_info['gstin']; ?> · <?php echo $company_info['phone']; ?> · <?php echo $company_info['email']; ?>
    </footer>
  </main>

  <!-- Email Modal -->
  <?php if (!$is_pdf_mode): ?>
  <div id="emailModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000;">
    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:30px; border-radius:10px; width:90%; max-width:500px;">
      <h3 style="margin-top:0; color:#333;">Email Quotation PDF</h3>
      <form id="emailForm">
        <div style="margin-bottom:15px;">
          <label for="recipientEmail" style="display:block; margin-bottom:5px; font-weight:bold;">Recipient Email:</label>
          <input type="email" id="recipientEmail" name="recipientEmail" 
                 value="<?php echo htmlspecialchars($quotation['email'] ?? ''); ?>" 
                 style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" required>
        </div>
        <div style="margin-bottom:15px;">
          <label for="recipientName" style="display:block; margin-bottom:5px; font-weight:bold;">Recipient Name:</label>
          <input type="text" id="recipientName" name="recipientName" 
                 value="<?php echo htmlspecialchars($quotation['contact_person'] ?? ''); ?>" 
                 style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
        </div>
        <div style="margin-bottom:20px;">
          <label for="customMessage" style="display:block; margin-bottom:5px; font-weight:bold;">Custom Message (Optional):</label>
          <textarea id="customMessage" name="customMessage" rows="4" 
                    style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;"
                    placeholder="Add a custom message to include in the email..."></textarea>
        </div>
        <div style="text-align:right;">
          <button type="button" onclick="closeEmailModal()" 
                  style="background:#6c757d; color:white; border:none; padding:10px 20px; border-radius:5px; margin-right:10px;">
            Cancel
          </button>
          <button type="submit" 
                  style="background:#007bff; color:white; border:none; padding:10px 20px; border-radius:5px;">
            📧 Send Email
          </button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <script>
    // Auto-print if print parameter is passed
    if (new URLSearchParams(window.location.search).get('print') === '1') {
      window.onload = function() {
        window.print();
      };
    }

    // PDF Generation
    function generatePDF() {
      window.open('../pdf_handler.php?action=generate&id=<?php echo $quotation_id; ?>', '_blank');
    }

    // Email Modal Functions
    function showEmailModal() {
      document.getElementById('emailModal').style.display = 'block';
    }

    function closeEmailModal() {
      document.getElementById('emailModal').style.display = 'none';
    }

    // Handle email form submission
    document.getElementById('emailForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData();
      formData.append('action', 'email');
      formData.append('quotation_id', '<?php echo $quotation_id; ?>');
      formData.append('recipient_email', document.getElementById('recipientEmail').value);
      formData.append('recipient_name', document.getElementById('recipientName').value);
      formData.append('custom_message', document.getElementById('customMessage').value);
      
      // Show loading
      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      submitBtn.innerHTML = '⏳ Sending...';
      submitBtn.disabled = true;
      
      fetch('../pdf_handler.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Email sent successfully!');
          closeEmailModal();
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(error => {
        alert('Error sending email: ' + error.message);
      })
      .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
      });
    });

    // Close modal when clicking outside
    document.getElementById('emailModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeEmailModal();
      }
    });
  </script>
</body>
</html>
