<?php
include '../header.php';
checkLogin();

// Check if print mode
$print_mode = isset($_GET['print']) && $_GET['print'] == '1';

if (!$print_mode) {
    include '../menu.php';
}

$message = '';
$quotation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (isset($_GET['success'])) {
    $message = showSuccess("Quotation created successfully!");
}

if (!$quotation_id) {
    header("Location: quotations.php");
    exit();
}

// Get quotation details with customer info
$sql = "SELECT q.*, c.company_name as customer_name, c.phone, c.email, c.gst_no, c.address, c.contact_person 
        FROM quotations q 
        LEFT JOIN customers c ON q.customer_id = c.id 
        WHERE q.id = $quotation_id";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    header("Location: quotations.php");
    exit();
}

$quotation = $result->fetch_assoc();

// Get company info
$company_sql = "SELECT * FROM company_info LIMIT 1";
$company_result = $conn->query($company_sql);
$company = $company_result->fetch_assoc();

// Get quotation items
$items_sql = "SELECT qi.*, 
              CASE 
                WHEN qi.item_type = 'machine' THEN m.name 
                WHEN qi.item_type = 'spare' THEN s.part_name 
              END as item_name,
              CASE 
                WHEN qi.item_type = 'machine' THEN m.model 
                WHEN qi.item_type = 'spare' THEN s.part_code 
              END as item_detail,
              CASE 
                WHEN qi.item_type = 'machine' THEN m.tech_specs 
                WHEN qi.item_type = 'spare' THEN s.description 
              END as item_specs
              FROM quotation_items qi 
              LEFT JOIN machines m ON qi.item_type = 'machine' AND qi.item_id = m.id 
              LEFT JOIN spares s ON qi.item_type = 'spare' AND qi.item_id = s.id 
              WHERE qi.quotation_id = $quotation_id 
              ORDER BY qi.sl_no, qi.id";
$items = $conn->query($items_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Techno-Commercial Offer – <?php echo $quotation['quote_ref']; ?></title>
    <style>
        :root {
            --brand: #0f6abf;
            --ink: #1b1f23;
            --muted: #5f6b7a;
            --border: #e5e7eb;
            --bg: #ffffff;
            --accent: #eef6ff;
        }
        
        * { box-sizing: border-box; }
        
        html, body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font: 15px/1.5 system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, sans-serif;
        }
        
        .sheet {
            max-width: 900px;
            margin: 24px auto;
            padding: 28px;
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 2px 30px rgba(0,0,0,.06);
        }
        
        header {
            display: flex;
            gap: 16px;
            align-items: flex-start;
            border-bottom: 2px solid var(--border);
            padding-bottom: 16px;
            margin-bottom: 18px;
        }
        
        .brandmark {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--brand), #6fb1ff);
            display: grid;
            place-items: center;
            color: #fff;
            font-weight: 700;
            font-size: 18px;
        }
        
        h1 {
            font-size: 22px;
            line-height: 1.2;
            margin: 0 0 6px;
        }
        
        .subtitle {
            color: var(--muted);
            font-size: 13px;
        }
        
        .tag {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            background: var(--accent);
            color: var(--brand);
            font-weight: 600;
            font-size: 12px;
        }
        
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        
        .card {
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px;
        }
        
        .card h3 {
            margin: .2rem 0 .6rem;
            font-size: 15px;
            color: var(--brand);
        }
        
        .kv {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 8px 12px;
        }
        
        .kv div {
            padding: 2px 0;
        }
        
        .kv label {
            color: var(--muted);
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 6px 0 0;
        }
        
        th, td {
            padding: 10px 12px;
        }
        
        thead th {
            font-size: 13px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .04em;
            text-align: left;
            border-bottom: 2px solid var(--border);
        }
        
        tbody td {
            border-bottom: 1px solid var(--border);
            vertical-align: top;
        }
        
        tbody tr:last-child td {
            border-bottom: 0;
        }
        
        .num {
            text-align: right;
            white-space: nowrap;
        }
        
        .muted {
            color: var(--muted);
        }
        
        .section {
            margin-top: 26px;
        }
        
        .section h2 {
            font-size: 18px;
            margin: 0 0 8px;
            color: var(--brand);
        }
        
        .price-total {
            display: flex;
            justify-content: flex-end;
            margin-top: 6px;
        }
        
        .price-total .wrap {
            min-width: 320px;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 10px 14px;
        }
        
        .price-total .wrap div {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
        }
        
        .price-total .wrap div:last-child {
            font-size: 18px;
            font-weight: 800;
            color: var(--ink);
            border-top: 1px dashed var(--border);
            margin-top: 6px;
        }
        
        .note {
            background: var(--accent);
            border: 1px dashed #b8dbff;
            color: #074a86;
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 13px;
            margin-top: 10px;
        }
        
        footer {
            margin-top: 22px;
            padding-top: 14px;
            border-top: 1px dashed var(--border);
            color: var(--muted);
            font-size: 13px;
        }
        
        .no-print {
            display: block;
        }
        
        @media print {
            body { background: #fff; }
            .sheet { 
                box-shadow: none; 
                border: 0; 
                margin: 0; 
                max-width: 100%; 
            }
            header { 
                border: 0; 
                padding-bottom: 0; 
                margin-bottom: 8px; 
            }
            .tag { background: #dcebff; }
            a[href]::after { content: ""; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<?php if (!$print_mode): ?>
<div class="container-fluid mt-4 no-print">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-file-text"></i> Quotation Details</h2>
                <div>
                    <a href="<?php echo url('quotations/edit_quotation.php?id=' . $quotation_id); ?>" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <a href="<?php echo url('quotations/send_quotation.php?id=' . $quotation_id); ?>" class="btn btn-success">
                        <i class="bi bi-send"></i> Send
                    </a>
                    <a href="?id=<?php echo $quotation_id; ?>&print=1" target="_blank" class="btn btn-info">
                        <i class="bi bi-printer"></i> Print View
                    </a>
                    <button onclick="window.print()" class="btn btn-info">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <a href="<?php echo url('quotations/quotations.php'); ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            <hr>
        </div>
    </div>
    <?php echo $message; ?>
</div>
<?php endif; ?>

<main class="sheet">
    <header>
        <div class="brandmark">PM</div>
        <div>
            <h1>Techno-Commercial Offer — <?php echo $quotation['customer_name']; ?> <span class="tag"><?php echo $quotation['quotation_number']; ?></span></h1>
            <div class="subtitle"><?php echo strtoupper($company['tagline'] ?? 'TURNKEY PROJECT EXPERT'); ?> · <?php echo strtoupper($company['company_name'] ?? 'PHARMA MACHINERY COMPANY'); ?></div>
        </div>
    </header>

    <section class="grid section">
        <div class="card">
            <h3>Customer Details</h3>
            <div class="kv">
                <label>Customer</label><div><?php echo $quotation['customer_name']; ?></div>
                <?php if ($quotation['address']): ?>
                <label>Address</label><div><?php echo nl2br($quotation['address']); ?></div>
                <?php endif; ?>
                <?php if ($quotation['contact_person']): ?>
                <label>Contact Person</label><div><?php echo $quotation['contact_person']; ?></div>
                <?php endif; ?>
                <?php if ($quotation['phone']): ?>
                <label>Contact No.</label><div><?php echo $quotation['phone']; ?></div>
                <?php endif; ?>
                <?php if ($quotation['email']): ?>
                <label>Email</label><div><?php echo $quotation['email']; ?></div>
                <?php endif; ?>
                <?php if ($quotation['gst_no']): ?>
                <label>GST No.</label><div><?php echo $quotation['gst_no']; ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <h3>Offer Details</h3>
            <div class="kv">
                <label>Enquiry Ref.</label><div><?php echo $quotation['enquiry_ref'] ?: 'Direct'; ?></div>
                <label>Quote Ref.</label><div><?php echo $quotation['quotation_number']; ?></div>
                <label>Date</label><div><?php echo date("d.m.Y", strtotime($quotation['created_at'])); ?></div>
                <label>Revision</label><div>R<?php echo $quotation['revision_no']; ?></div>
                <label>Prepared By</label><div><?php echo $quotation['prepared_by']; ?></div>
            </div>
        </div>
    </section>

    <?php if ($company): ?>
    <section class="section">
        <h2>Company Information</h2>
        <div class="card">
            <div class="kv">
                <?php if ($company['corporate_office']): ?>
                <label>Corporate Office</label><div><?php echo $company['corporate_office']; ?></div>
                <?php endif; ?>
                <?php if ($company['manufacturing_unit']): ?>
                <label>Mfg. Unit</label><div><?php echo $company['manufacturing_unit']; ?></div>
                <?php endif; ?>
                <?php if ($company['cin']): ?>
                <label>CIN</label><div><?php echo $company['cin']; ?></div>
                <?php endif; ?>
                <?php if ($company['gst']): ?>
                <label>GST</label><div><?php echo $company['gst']; ?></div>
                <?php endif; ?>
                <label>Contact</label><div><?php echo $company['contact']; ?> · <?php echo $company['email']; ?> · <?php echo $company['website']; ?></div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="section">
        <h2>Bill of Quantity & Prices</h2>
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
                $sl_no = 1;
                while ($item = $items->fetch_assoc()): 
                    $subtotal += $item['total_price'];
                ?>
                    <tr>
                        <td><?php echo $sl_no++; ?></td>
                        <td>
                            <strong><?php echo $item['item_name']; ?></strong>
                            <?php if ($item['item_detail']): ?>
                                <br><span class="muted">Model: <?php echo $item['item_detail']; ?></span>
                            <?php endif; ?>
                            <?php if ($item['item_specs']): ?>
                                <br><span class="muted"><?php echo substr($item['item_specs'], 0, 200) . (strlen($item['item_specs']) > 200 ? '...' : ''); ?></span>
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
                <div><span class="muted">Sub-Total</span><span class="num">₹ <?php echo number_format($subtotal, 2); ?></span></div>
                <?php if ($quotation['discount_amount'] > 0): ?>
                <div><span class="muted">Discount (<?php echo number_format($quotation['discount_percentage'], 1); ?>%)</span><span class="num">- ₹ <?php echo number_format($quotation['discount_amount'], 2); ?></span></div>
                <?php endif; ?>
                <div><span>Total</span><span class="num">₹ <?php echo number_format($quotation['grand_total'] ?: $subtotal, 2); ?></span></div>
            </div>
        </div>
        <div class="note">
            <strong>Terms & Conditions:</strong> Quotation valid for 30 days from date of issue. Payment terms: 30% advance, 60% on delivery, 10% after installation. Delivery time: 6-8 weeks from receipt of advance payment. Installation and commissioning included. One year warranty on manufacturing defects.
        </div>
    </section>

    <footer>
        <?php echo strtoupper($company['company_name'] ?? 'PHARMA MACHINERY COMPANY'); ?> · <?php echo $company['cin'] ?? ''; ?> · <?php echo $company['gst'] ?? ''; ?> · <?php echo $company['email'] ?? ''; ?>
    </footer>
</main>

<?php if (!$print_mode): ?>
<?php include '../footer.php'; ?>
<?php else: ?>
</body>
</html>
<?php endif; ?>
