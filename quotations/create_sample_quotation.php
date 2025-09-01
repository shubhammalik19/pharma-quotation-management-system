<?php
// Import sample quotation script
include '../common/conn.php';

echo "<h2>Creating Sample Professional Quotation</h2>";

// First, ensure we have the customer from your attachment
$customer_sql = "UPDATE customers SET 
    name = 'M/S Pallota Nutritions',
    contact_person = 'Mr. Vishal Otia',
    phone = '+91 9687320500',
    email = 'project@pallotanutritions.in',
    address = 'Khatraj, Gandhinagar',
    gst_no = '24ABCDE1234F1Z5'
    WHERE id = 1";
$conn->query($customer_sql);

// Create a professional quotation with multiple items like in your format
$quote_sql = "UPDATE quotations SET 
    quote_ref = 'XT/2526/Q030A',
    enquiry_ref = 'Indiamart',
    revision_no = 4,
    prepared_by = 'PHARMA MACHINERY COMPANY',
    total_amount = 8838500.00
    WHERE id = 1";
$conn->query($quote_sql);

// Clear existing items
$conn->query("DELETE FROM quotation_items WHERE quotation_id = 1");

// Insert detailed items matching your quotation format
$items = [
    [1, 'machine', 1, 1, 2772000.00, 2772000.00, 1, 'Basic Equipment – Complete Assembly'],
    [1, 'spare', 4, 1, 195000.00, 195000.00, 2, 'PLC + HMI (Delta) — Limited recipes, login modes, HD mimic display'],
    [1, 'machine', 5, 1, 365000.00, 365000.00, 3, 'Co-Mill Attachment'],
    [1, 'machine', 2, 1, 3020000.00, 3020000.00, 4, 'Automatic Fluid Bed Processor (Top Spray)'],
    [1, 'spare', 6, 1, 195000.00, 195000.00, 5, 'PLC & HMI (FBD)'],
    [1, 'spare', 1, 1, 88000.00, 88000.00, 6, 'Solid Flow Monitor — Detects powder flow / broken bag'],
    [1, 'machine', 3, 1, 890000.00, 890000.00, 7, 'Octagonal Blender – 2000 L (GMP)'],
    [1, 'machine', 4, 1, 215000.00, 215000.00, 8, 'Single-Deck Vibro Sifter – 48"'],
    [1, 'spare', 7, 1, 18500.00, 18500.00, 9, 'Extra Silicon-Moulded Sieve'],
    [1, 'machine', 6, 1, 680000.00, 680000.00, 10, 'Vacuum Transfer System with Structure']
];

foreach ($items as $item) {
    $sql = "INSERT INTO quotation_items (quotation_id, item_type, item_id, quantity, price, total, sl_no, description) 
            VALUES ({$item[0]}, '{$item[1]}', {$item[2]}, {$item[3]}, {$item[4]}, {$item[5]}, {$item[6]}, '{$item[7]}')";
    $conn->query($sql);
}

echo "<div style='color: green;'>";
echo "✅ Professional quotation created successfully!<br>";
echo "✅ Customer: M/S Pallota Nutritions<br>";
echo "✅ Quote Ref: XT/2526/Q030A<br>";
echo "✅ Total Amount: ₹88,38,500.00<br>";
echo "✅ 10 items added with detailed descriptions<br>";
echo "</div>";

echo "<br><h3>Next Steps:</h3>";
echo "1. <a href='login.php'>Login to System</a> (admin/admin123)<br>";
echo "2. <a href='view_quotation.php?id=1'>View Professional Quotation</a><br>";
echo "3. <a href='view_quotation.php?id=1&print=1' target='_blank'>View Print Version</a><br>";

echo "<br><div style='background: #f0f9ff; padding: 15px; border: 1px solid #0ea5e9; border-radius: 8px;'>";
echo "<strong>Features Implemented:</strong><br>";
echo "✅ Professional quotation layout matching your design<br>";
echo "✅ Company branding with logo placeholder<br>";
echo "✅ Customer details section<br>";
echo "✅ Offer details with revision tracking<br>";
echo "✅ Company information section<br>";
echo "✅ Detailed bill of quantities<br>";
echo "✅ Print-optimized styling<br>";
echo "✅ Professional terms & conditions<br>";
echo "✅ Database structure for all data<br>";
echo "</div>";

$conn->close();
?>

<style>
body { font-family: system-ui, sans-serif; padding: 20px; line-height: 1.5; }
h2 { color: #0f6abf; }
a { color: #0f6abf; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
